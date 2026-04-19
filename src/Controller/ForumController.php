<?php

namespace App\Controller;

use App\Entity\ForumPosts;
use App\Entity\Comments;
use App\Entity\Interactions;
use App\Entity\Users;
use App\Service\BadWordsFilter;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
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

        $posts = $em->getRepository(ForumPosts::class)->findBy([], ['createdAt' => 'DESC']);
        $postsData = [];

        foreach ($posts as $post) {
            $postId = $post->getId();
            
            // Count comments
            $commentCount = $em->getRepository(Comments::class)->count(['postId' => $postId]);
            
            // Count likes (type LIKE or LOVE etc. let's just count all interactions or type = 'like')
            // Using generic count matching 'type' => 'like' (ignoring case usually or just enforce lower/upper)
            $likeCount = $em->getRepository(Interactions::class)->count(['postId' => $postId]);

            // Optional: determine if currentUser liked it
            $userLiked = false;
            if ($isLoggedIn) {
                $interaction = $em->getRepository(Interactions::class)->findOneBy(['postId' => $postId, 'userId' => $userId]);
                if ($interaction) {
                    $userLiked = true;
                }
            }

            $postsData[] = [
                'post' => $post,
                'commentCount' => $commentCount,
                'likeCount' => $likeCount,
                'userLiked' => $userLiked
            ];
        }

        return $this->render('FrontOffice/forum/index.html.twig', [
            'postsData'  => $postsData,
            'isLoggedIn' => $isLoggedIn,
            'languages'  => TranslationService::supportedLanguages(),
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

        $comments = $em->getRepository(Comments::class)->findBy(['postId' => $id], ['createdAt' => 'ASC']);
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
            'comments'      => $comments,
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

        if ($post && $user) {
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

            $errors = $validator->validate($comment);
            if (count($errors) > 0) {
                $this->addFlash('error', 'Comment validation failed: ' . $errors[0]->getMessage());
                return $this->redirectToRoute('app_forum_show', ['id' => $id]);
            }

            $em->persist($comment);
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
}
