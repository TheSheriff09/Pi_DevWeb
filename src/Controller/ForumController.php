<?php

namespace App\Controller;

use App\Entity\ForumPosts;
use App\Entity\ForumFollow;
use App\Entity\Comments;
use App\Entity\Interactions;
use App\Entity\Users;
use App\Entity\CommentReaction;
use App\Entity\Report;
use App\Service\BadWordsFilter;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/forum')]
class ForumController extends AbstractController
{
    #[Route('/', name: 'app_forum_index')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $userId = $request->getSession()->get('user_id');
        $isLoggedIn = !empty($userId);

        $followingIds = [];
        if ($isLoggedIn) {
            $follows = $em->getRepository(ForumFollow::class)->findBy(['followerId' => $userId]);
            foreach ($follows as $f) {
                $followingIds[] = $f->getFollowingId();
            }
        }

        if ($isLoggedIn && count($followingIds) > 0) {
            $qb = $em->getRepository(ForumPosts::class)->createQueryBuilder('p');
            $qb->addSelect('(CASE WHEN p.userId IN (:followingIds) THEN 1 ELSE 0 END) AS HIDDEN isFollowed')
               ->setParameter('followingIds', $followingIds)
               ->orderBy('p.isPinned', 'DESC')
               ->addOrderBy('isFollowed', 'DESC')
               ->addOrderBy('p.createdAt', 'DESC');
            $allPosts = $qb->getQuery()->getResult();
        } else {
            $allPosts = $em->getRepository(ForumPosts::class)->findBy([], ['isPinned' => 'DESC', 'createdAt' => 'DESC']);
        }

        $postsData = [];

        // Analytics Data
        $totalPostsCount = count($allPosts);
        $totalCommentsCount = $em->getRepository(Comments::class)->count([]);
        $trendingPosts = $em->getRepository(ForumPosts::class)->findBy([], ['views' => 'DESC'], 3);

        foreach ($allPosts as $post) {
            if (!$post->getIsActive() && (!isset($userId) || $post->getUserId() !== $userId)) {
                continue; // Skip inactive posts for non-owners
            }
            $postId = $post->getId();
            
            // Count comments
            $commentCount = $em->getRepository(Comments::class)->count(['postId' => $postId]);
            
            // Count likes (type LIKE or LOVE etc. let's just count all interactions or type = 'like')
            // Using generic count matching 'type' => 'like' (ignoring case usually or just enforce lower/upper)
            $likeCount = $em->getRepository(Interactions::class)->count(['postId' => $postId]);

            $userLiked = false;
            if ($isLoggedIn) {
                $interaction = $em->getRepository(Interactions::class)->findOneBy(['postId' => $postId, 'userId' => $userId]);
                if ($interaction) {
                    $userLiked = true;
                }
            }
            
            $authorUser = $em->getRepository(Users::class)->find($post->getUserId());

            $postsData[] = [
                'post' => $post,
                'commentCount' => $commentCount,
                'likeCount' => $likeCount,
                'userLiked' => $userLiked,
                'author' => $authorUser
            ];
        }

        return $this->render('FrontOffice/forum/index.html.twig', [
            'postsData'  => $postsData,
            'isLoggedIn' => $isLoggedIn,
            'languages'  => TranslationService::supportedLanguages(),
            'totalPostsCount' => $totalPostsCount,
            'totalCommentsCount' => $totalCommentsCount,
            'trendingPosts' => $trendingPosts,
        ]);
    }

    #[Route('/ai/generate', name: 'app_forum_ai_generate', methods: ['POST'])]
    public function generateAiContent(Request $request, HttpClientInterface $client): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? null;
        
        if (!$prompt) {
            return $this->json(['error' => 'No prompt provided.'], 400);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        
        if ($apiKey) {
            try {
                $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey, [
                    'json' => [
                        'contents' => [
                            ['parts' => [['text' => "Write a small paragraph for a forum post based on this idea. Do not include quotes or conversational filler, just the raw post text: " . $prompt]]]
                        ]
                    ]
                ]);

                $result = $response->toArray();
                $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                
                if ($content) {
                    return $this->json(['content' => trim($content)]);
                }
            } catch (\Exception $e) {
                // Fallback to simulated if API fails
            }
        }

        // Simulated Fallback
        $simulated = "Hey community! I wanted to start a discussion about " . htmlspecialchars($prompt) . ". I've been thinking a lot about the implications of this and how it impacts our workflows. What are your thoughts or experiences with this? I'd love to hear some different perspectives!";
        
        return $this->json(['content' => $simulated]);
    }

    #[Route('/post/new', name: 'app_forum_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, BadWordsFilter $filter): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        $title = $request->request->get('title');
        $content = $request->request->get('content');
        $imageFile = $request->files->get('image');

        // --- Bad words check on title and content ---
        $violation = $filter->findViolation($title . ' ' . $content);
        if ($violation !== null) {
            $this->addFlash('error', '🚫 Your post contains a forbidden word or phrase and cannot be published. Please review and edit your content.');
            return $this->redirectToRoute('app_forum_index');
        }

        $post = new ForumPosts();
        $post->setTitle($title);
        $post->setContent($content);
        $post->setCreatedAt(new \DateTime());
        $post->setUpdatedAt(new \DateTime());
        $post->setUserId($user->getId());
        $post->setAuthorName($user->getFullName());
        
        $imageName = 'default_post.png';
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = $originalFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/forum',
                    $newFilename
                );
                $imageName = $newFilename;
            } catch (\Exception $e) {
                // Ignore upload failure, use default
            }
        }

        $post->setImageUrl($imageName);

        $errors = $validator->validate($post);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
            }
            return $this->redirectToRoute('app_forum_index');
        }

        $em->persist($post);
        $user->setGamificationPoints($user->getGamificationPoints() + 10);
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Post created successfully!');
        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/post/{id}', name: 'app_forum_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em, Request $request): Response
    {
        $userId = $request->getSession()->get('user_id');
        $isLoggedIn = !empty($userId);

        $post = $em->getRepository(ForumPosts::class)->find($id);
        if (!$post) {
            return $this->redirectToRoute('app_forum_index');
        }

        // Increment Views
        $post->setViews($post->getViews() + 1);
        $em->flush();

        $allComments = $em->getRepository(Comments::class)->findBy(['postId' => $id], ['createdAt' => 'ASC']);
        $rootComments = [];
        $replies = [];
        
        foreach ($allComments as $c) {
            if ($c->getParentId()) {
                $replies[$c->getParentId()][] = $c;
            } else {
                $rootComments[] = $c;
            }
        }
        
        // Sort root comments by upvotes
        usort($rootComments, fn($a, $b) => $b->getUpvotes() <=> $a->getUpvotes());

        $likeCount = $em->getRepository(Interactions::class)->count(['postId' => $id]);
        
        $userLiked = false;
        if ($isLoggedIn) {
            $interaction = $em->getRepository(Interactions::class)->findOneBy(['postId' => $id, 'userId' => $userId]);
            if ($interaction) {
                $userLiked = true;
            }
        }

        return $this->render('FrontOffice/forum/show.html.twig', [
            'post'          => $post,
            'rootComments'  => $rootComments,
            'replies'       => $replies,
            'totalComments' => count($allComments),
            'likeCount'     => $likeCount,
            'userLiked'     => $userLiked,
            'isLoggedIn'    => $isLoggedIn,
            'currentUserId' => $userId,
            'languages'     => TranslationService::supportedLanguages(),
        ]);
    }

    #[Route('/post/{id}/comment', name: 'app_forum_comment', methods: ['POST'])]
    public function comment(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator, BadWordsFilter $filter): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        $post = $em->getRepository(ForumPosts::class)->find($id);
        $parentId = $request->request->get('parentId');

        if ($post && $user) {
            if ($post->getIsCommentsLocked()) {
                $this->addFlash('error', '🔒 This post is locked by the owner. You cannot add new comments.');
                return $this->redirectToRoute('app_forum_show', ['id' => $id]);
            }

            $content = trim($request->request->get('content', ''));

            // ── Validation 1: empty comment ───────────────────────────────
            if ($content === '') {
                $this->addFlash('error', '✏️ Your comment cannot be empty. Please write something before posting.');
                return $this->redirectToRoute('app_forum_show', ['id' => $id]);
            }

            // ── Validation 2: comment identical to post content ───────────
            $postContent = trim($post->getContent() ?? '');
            if (mb_strtolower($content) === mb_strtolower($postContent)) {
                $this->addFlash('error', '🔁 Your comment cannot be identical to the post content. Please add your own thoughts.');
                return $this->redirectToRoute('app_forum_show', ['id' => $id]);
            }

            // ── Validation 3: bad words filter ────────────────────────────
            $violation = $filter->findViolation($content);
            if ($violation !== null) {
                $this->addFlash('error', '🚫 Your comment contains a forbidden word or phrase. Please keep the discussion respectful.');
                return $this->redirectToRoute('app_forum_show', ['id' => $id]);
            }

            $comment = new Comments();
            $comment->setContent($content);
            $comment->setCreatedAt(new \DateTime());
            $comment->setPostId($post->getId());
            $comment->setUserId($user->getId());
            $comment->setAuthorName($user->getFullName());
            
            if ($parentId) {
                $comment->setParentId((int)$parentId);
            }

            $errors = $validator->validate($comment);
            if (count($errors) > 0) {
                $this->addFlash('error', 'Comment validation failed: ' . $errors[0]->getMessage());
                return $this->redirectToRoute('app_forum_show', ['id' => $id]);
            }

            $em->persist($comment);
            $user->setGamificationPoints($user->getGamificationPoints() + 5);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', '💬 Comment posted successfully!');
        }

        return $this->redirectToRoute('app_forum_show', ['id' => $id]);
    }

    #[Route('/post/{id}/react', name: 'app_forum_react', methods: ['POST'])]
    public function react(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        $post = $em->getRepository(ForumPosts::class)->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found'], 404);
        }
        
        $type = $request->query->get('type', 'LIKE');
        $validTypes = ['LIKE', 'LOVE', 'HAHA', 'WOW', 'SAD', 'ANGRY'];
        if (!in_array($type, $validTypes)) {
            $type = 'LIKE';
        }

        $existingInteraction = $em->getRepository(Interactions::class)->findOneBy([
            'postId' => $id,
            'userId' => $userId
        ]);

        if ($existingInteraction) {
            if ($existingInteraction->getType() === $type) {
                // Remove if clicking same reaction
                $em->remove($existingInteraction);
                $em->flush();
                $reacted = false;
                $currentType = null;
            } else {
                // Change reaction type
                $existingInteraction->setType($type);
                $em->flush();
                $reacted = true;
                $currentType = $type;
            }
        } else {
            // New reaction
            $interaction = new Interactions();
            $interaction->setPostId($id);
            $interaction->setUserId($userId);
            $interaction->setType($type);
            $interaction->setCreatedAt(new \DateTime());

            $em->persist($interaction);
            $em->flush();
            $reacted = true;
            $currentType = $type;
        }

        $totalCount = $em->getRepository(Interactions::class)->count(['postId' => $id]);
        return $this->json(['reacted' => $reacted, 'type' => $currentType, 'count' => $totalCount]);
    }

    #[Route('/post/{id}/delete', name: 'app_forum_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $post = $em->getRepository(ForumPosts::class)->find($id);
        if ($post && $post->getUserId() === $userId) {
            // Optional: delete related comments and interactions or rely on DB CASCADE
            // Since constraints have CASCADE, deleting the post deletes comments/interactions seamlessly.
            $em->remove($post);
            $em->flush();
        }

        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/api/recommended-post', name: 'api_recommended_post', methods: ['GET'])]
    public function getRecommendedPost(Request $request): JsonResponse
    {
        $jsonPath = $this->getParameter('kernel.project_dir') . '/var/recommended_post.json';
        if (!file_exists($jsonPath)) {
            return $this->json(['error' => 'No recommendations yet.'], 404);
        }
        
        $data = json_decode(file_get_contents($jsonPath), true);
        
        $notifiedPosts = $request->getSession()->get('notified_posts', []);
        
        if (in_array($data['post_id'], $notifiedPosts)) {
            return $this->json(['post_id' => $data['post_id'], 'show_toast' => false]);
        }
        
        $notifiedPosts[] = $data['post_id'];
        $request->getSession()->set('notified_posts', $notifiedPosts);
        
        return $this->json([
            'post_id' => $data['post_id'],
            'title'   => $data['title'],
            'score'   => $data['trending_probability'],
            'show_toast' => true
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    //  Translation endpoint
    // ──────────────────────────────────────────────────────────────

    #[Route('/post/{id}/translate', name: 'app_forum_translate', methods: ['POST'])]
    public function translate(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        TranslationService $translator
    ): JsonResponse {
        $post = $em->getRepository(ForumPosts::class)->find($id);
        if (!$post) {
            return $this->json(['error' => 'Post not found.'], 404);
        }

        $targetLang = $request->request->get('lang', 'fr');
        $allowed    = array_keys(TranslationService::supportedLanguages());

        if (!in_array($targetLang, $allowed, true)) {
            return $this->json(['error' => 'Unsupported language.'], 400);
        }

        try {
            $translatedTitle   = $translator->translate($post->getTitle(),   $targetLang);
            $translatedContent = $translator->translate($post->getContent(), $targetLang);

            return $this->json([
                'title'    => $translatedTitle,
                'content'  => $translatedContent,
                'lang'     => $targetLang,
                'langName' => TranslationService::supportedLanguages()[$targetLang]['name'],
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 503);
        }
    }

    #[Route('/post/{id}/toggle-active', name: 'app_forum_toggle_active', methods: ['POST'])]
    public function toggleActive(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $post = $em->getRepository(ForumPosts::class)->find($id);
        
        if ($post && $post->getUserId() === $userId) {
            $post->setIsActive(!$post->getIsActive());
            $em->flush();
            $this->addFlash('success', 'Post visibility toggled.');
        } else {
            $this->addFlash('error', 'Unauthorized action.');
        }
        
        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/post/{id}/toggle-lock', name: 'app_forum_toggle_lock', methods: ['POST'])]
    public function toggleLock(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $post = $em->getRepository(ForumPosts::class)->find($id);
        
        if ($post && $post->getUserId() === $userId) {
            $post->setIsCommentsLocked(!$post->getIsCommentsLocked());
            $em->flush();
            $this->addFlash('success', 'Comments lock state updated.');
        } else {
            $this->addFlash('error', 'Unauthorized action.');
        }
        
        return $this->redirectToRoute('app_forum_show', ['id' => $id]);
    }

    #[Route('/comment/{id}/edit', name: 'app_forum_comment_edit', methods: ['POST'])]
    public function editComment(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator, BadWordsFilter $filter): Response
    {
        $userId = $request->getSession()->get('user_id');
        $comment = $em->getRepository(Comments::class)->find($id);

        if (!$comment || $comment->getUserId() !== $userId) {
            $this->addFlash('error', 'Unauthorized or comment not found.');
            return $this->redirectToRoute('app_forum_index');
        }

        // Time window check (15 minutes)
        if ((new \DateTime())->diff($comment->getCreatedAt())->i >= 15) {
            $this->addFlash('error', 'You can no longer edit this comment. The 15-minute window has passed.');
            return $this->redirectToRoute('app_forum_show', ['id' => $comment->getPostId()]);
        }

        $content = trim($request->request->get('content', ''));
        if ($content !== '') {
            $violation = $filter->findViolation($content);
            if ($violation !== null) {
                $this->addFlash('error', 'Forbidden word in edited comment.');
            } else {
                $comment->setContent($content);
                $comment->setIsEdited(true);
                $em->flush();
                $this->addFlash('success', 'Comment updated.');
            }
        } else {
            $this->addFlash('error', 'Comment cannot be empty.');
        }

        return $this->redirectToRoute('app_forum_show', ['id' => $comment->getPostId()]);
    }

    #[Route('/comment/{id}/delete', name: 'app_forum_comment_delete_user', methods: ['POST'])]
    public function deleteCommentUser(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $comment = $em->getRepository(Comments::class)->find($id);

        if (!$comment || $comment->getUserId() !== $userId) {
            $this->addFlash('error', 'Unauthorized or comment not found.');
            return $this->redirectToRoute('app_forum_index');
        }

        // Time window check (15 minutes)
        if ((new \DateTime())->diff($comment->getCreatedAt())->i >= 15) {
            $this->addFlash('error', 'You can no longer delete this comment. The 15-minute window has passed.');
            return $this->redirectToRoute('app_forum_show', ['id' => $comment->getPostId()]);
        }

        $postId = $comment->getPostId();
        $em->remove($comment);
        $em->flush();
        $this->addFlash('success', 'Comment deleted.');

        return $this->redirectToRoute('app_forum_show', ['id' => $postId]);
    }

    #[Route('/comment/{id}/vote', name: 'app_forum_comment_vote', methods: ['POST'])]
    public function voteComment(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->json(['error' => 'Not authenticated'], 401);

        $comment = $em->getRepository(Comments::class)->find($id);
        if (!$comment) return $this->json(['error' => 'Comment not found'], 404);

        $type = $request->query->get('type', 'upvote'); // 'upvote' or 'downvote'
        if (!in_array($type, ['upvote', 'downvote'])) $type = 'upvote';

        $reaction = $em->getRepository(CommentReaction::class)->findOneBy(['commentId' => $id, 'userId' => $userId]);

        if ($reaction) {
            if ($reaction->getType() === $type) {
                // remove vote
                $em->remove($reaction);
                if ($type === 'upvote') $comment->setUpvotes(max(0, $comment->getUpvotes() - 1));
                else $comment->setDownvotes(max(0, $comment->getDownvotes() - 1));
            } else {
                // switch vote
                if ($type === 'upvote') {
                    $comment->setDownvotes(max(0, $comment->getDownvotes() - 1));
                    $comment->setUpvotes($comment->getUpvotes() + 1);
                } else {
                    $comment->setUpvotes(max(0, $comment->getUpvotes() - 1));
                    $comment->setDownvotes($comment->getDownvotes() + 1);
                }
                $reaction->setType($type);
            }
        } else {
            // new vote
            $reaction = new CommentReaction();
            $reaction->setCommentId($id);
            $reaction->setUserId($userId);
            $reaction->setType($type);
            $em->persist($reaction);

            if ($type === 'upvote') $comment->setUpvotes($comment->getUpvotes() + 1);
            else $comment->setDownvotes($comment->getDownvotes() + 1);
        }

        $em->flush();

        return $this->json(['upvotes' => $comment->getUpvotes(), 'downvotes' => $comment->getDownvotes()]);
    }

    #[Route('/api/users/mention-search', name: 'app_forum_mention_search', methods: ['GET'])]
    public function mentionSearch(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $query = $request->query->get('q', '');

        $qb = $em->getRepository(Users::class)->createQueryBuilder('u');
        
        if (strlen($query) > 0) {
            $qb->where('u.fullName LIKE :query')
               ->setParameter('query', $query . '%');
        }

        $users = $qb->setMaxResults(50)
                    ->getQuery()
                    ->getResult();

        $result = [];
        foreach ($users as $user) {
            $result[] = ['key' => $user->getFullName(), 'value' => $user->getFullName()];
        }
        return $this->json($result);
    }

    #[Route('/report', name: 'app_forum_report', methods: ['POST'])]
    public function reportContent(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('app_login');

        $type = $request->request->get('type'); // 'post' or 'comment'
        $targetId = $request->request->get('target_id');
        $reason = trim($request->request->get('reason', ''));

        if ($type && $targetId && $reason !== '') {
            $reporter = $em->getRepository(Users::class)->find($userId);
            
            $report = new Report();
            $report->setReporterId($userId);
            $report->setTargetType($type);
            $report->setTargetId((int)$targetId);
            $report->setReason($reason);
            $report->setCreatedAt(new \DateTime());
            $em->persist($report);
            $em->flush();

            // ── Prepare Email Data ──
            $targetContent = 'Content not found';
            $targetAuthor = 'Unknown';
            $actionUrl = $this->generateUrl('app_forum_index', [], UrlGeneratorInterface::ABSOLUTE_URL);

            if ($type === 'post') {
                $post = $em->getRepository(ForumPosts::class)->find($targetId);
                if ($post) {
                    $targetContent = "[TITLE: " . $post->getTitle() . "] " . $post->getContent();
                    $targetAuthor = $post->getAuthorName();
                    $actionUrl = $this->generateUrl('app_forum_show', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
                }
            } else {
                $comment = $em->getRepository(Comments::class)->find($targetId);
                if ($comment) {
                    $targetContent = $comment->getContent();
                    $targetAuthor = $comment->getAuthorName();
                    $actionUrl = $this->generateUrl('app_forum_show', ['id' => $comment->getPostId()], UrlGeneratorInterface::ABSOLUTE_URL);
                }
            }

            try {
                $email = (new TemplatedEmail())
                    ->from(new Address('spankyzaiem@gmail.com', 'StartupFlow Forum'))
                    ->to('spankyzaiem@gmail.com')
                    ->subject('🚨 Forum Report: ' . strtoupper($type) . ' #' . $targetId)
                    ->htmlTemplate('FrontOffice/email/forum_report.html.twig')
                    ->context([
                        'report'        => $report,
                        'reporter'      => $reporter,
                        'targetContent' => $targetContent,
                        'targetAuthor'  => $targetAuthor,
                        'actionUrl'     => $actionUrl
                    ]);
                
                $mailer->send($email);
            } catch (\Exception $e) {
                $this->addFlash('error', '⚠️ Report saved but email notification failed: ' . $e->getMessage());
            }

            $this->addFlash('success', 'Content reported successfully. The administration has been notified.');
        } else {
            $this->addFlash('error', 'Invalid report submission.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_forum_index'));
    }

    #[Route('/post/{id}/pin', name: 'app_forum_pin_post', methods: ['POST'])]
    public function pinPost(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return $this->redirectToRoute('app_login');

        $user = $em->getRepository(Users::class)->find($userId);
        $post = $em->getRepository(ForumPosts::class)->find($id);

        if ($post && $user) {
            if ($post->getUserId() !== $userId) {
                $this->addFlash('error', 'You can only pin your own posts.');
            } else if ($user->getGamificationPoints() >= 50) {
                $user->setGamificationPoints($user->getGamificationPoints() - 50);
                $post->setIsPinned(true);
                $em->flush();
                $this->addFlash('success', 'Post pinned successfully for 50 points!');
            } else {
                $this->addFlash('error', 'You need at least 50 points to pin your post.');
            }
        }
        
        return $this->redirectToRoute('app_forum_index');
    }

    #[Route('/profile/{id}', name: 'app_forum_profile')]
    public function profile(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $currentUserId = $request->getSession()->get('user_id');
        $isLoggedIn = !empty($currentUserId);
        
        $targetUser = $em->getRepository(Users::class)->find($id);
        if (!$targetUser) {
            return $this->redirectToRoute('app_forum_index');
        }

        if ($isLoggedIn && $currentUserId === $targetUser->getId() && $request->isMethod('POST')) {
            $bio = $request->request->get('forumBio');
            if ($bio !== null) $targetUser->setForumBio($bio);
            
            $file = $request->files->get('forumImage');
            if ($file) {
                $filename = uniqid() . '.' . $file->guessExtension();
                $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/forum_profiles';
                if (!is_dir($dir)) mkdir($dir, 0777, true);
                $file->move($dir, $filename);
                $targetUser->setForumImage($filename);
            }
            $em->flush();
            $this->addFlash('success', 'Profile updated successfully!');
            return $this->redirectToRoute('app_forum_profile', ['id' => $id]);
        }

        $followerCount = $em->getRepository(ForumFollow::class)->count(['followingId' => $id]);
        $followingCount = $em->getRepository(ForumFollow::class)->count(['followerId' => $id]);

        $postRepo = $em->getRepository(ForumPosts::class);
        $commentRepo = $em->getRepository(Comments::class);

        $totalPosts = $postRepo->count(['userId' => $id]);
        $totalComments = $commentRepo->count(['userId' => $id]);

        $bestPost = $postRepo->findOneBy(['userId' => $id], ['views' => 'DESC']);
        $bestComment = $commentRepo->findOneBy(['userId' => $id], ['upvotes' => 'DESC']);

        $isFollowing = false;
        if ($isLoggedIn) {
            $isFollowing = (bool) $em->getRepository(ForumFollow::class)->count([
                'followerId' => $currentUserId,
                'followingId' => $id
            ]);
        }

        return $this->render('FrontOffice/forum/profile.html.twig', [
            'targetUser' => $targetUser,
            'isOwner' => ($isLoggedIn && $currentUserId === $id),
            'isFollowing' => $isFollowing,
            'isLoggedIn' => $isLoggedIn,
            'stats' => [
                'followers' => $followerCount,
                'following' => $followingCount,
                'totalPosts' => $totalPosts,
                'totalComments' => $totalComments
            ],
            'bestPost' => $bestPost,
            'bestComment' => $bestComment
        ]);
    }

    #[Route('/profile/{id}/follow-toggle', name: 'app_forum_toggle_follow', methods: ['POST'])]
    public function toggleFollow(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $currentUserId = $request->getSession()->get('user_id');
        if (!$currentUserId) {
            return $this->redirectToRoute('app_login');
        }

        if ($currentUserId === $id) {
            return $this->redirectToRoute('app_forum_profile', ['id' => $id]);
        }

        $followRepo = $em->getRepository(ForumFollow::class);
        $existing = $followRepo->findOneBy([
            'followerId' => $currentUserId,
            'followingId' => $id
        ]);

        if ($existing) {
            $em->remove($existing);
            $this->addFlash('success', 'You unfollowed this user.');
        } else {
            $follow = new ForumFollow();
            $follow->setFollowerId($currentUserId);
            $follow->setFollowingId($id);
            $follow->setCreatedAt(new \DateTime());
            $em->persist($follow);
            $this->addFlash('success', 'You are now following this user!');
        }
        $em->flush();

        return $this->redirectToRoute('app_forum_profile', ['id' => $id]);
    }
}
