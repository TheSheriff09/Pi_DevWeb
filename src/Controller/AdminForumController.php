<?php

namespace App\Controller;

use App\Entity\ForumPosts;
use App\Entity\Comments;
use App\Entity\Interactions;
use App\Entity\BannedWord;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/forum')]
class AdminForumController extends AbstractController
{
    private function ensureAdmin(Request $request): ?Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login'); // Security check
        }
        return null;
    }

    #[Route('/', name: 'app_admin_forum_dashboard')]
    public function dashboard(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $postsCount = $em->getRepository(ForumPosts::class)->count([]);
        $commentsCount = $em->getRepository(Comments::class)->count([]);
        $interactionsCount = $em->getRepository(Interactions::class)->count([]);

        $latestPosts = $em->getRepository(ForumPosts::class)->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('BackOffice/forum/dashboard.html.twig', [
            'postsCount' => $postsCount,
            'commentsCount' => $commentsCount,
            'interactionsCount' => $interactionsCount,
            'latestPosts' => $latestPosts,
            'current_menu' => 'dashboard'
        ]);
    }

    #[Route('/posts', name: 'app_admin_forum_posts')]
    public function posts(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $query = $request->query->get('q', '');
        if ($query) {
            // Basic manual search via QueryBuilder for title or content
            $qb = $em->getRepository(ForumPosts::class)->createQueryBuilder('p');
            $posts = $qb->where('p.title LIKE :query')
                       ->orWhere('p.content LIKE :query')
                       ->setParameter('query', '%' . $query . '%')
                       ->orderBy('p.createdAt', 'DESC')
                       ->getQuery()
                       ->getResult();
        } else {
            $posts = $em->getRepository(ForumPosts::class)->findBy([], ['createdAt' => 'DESC']);
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/forum/_posts_tbody.html.twig', [
                'posts' => $posts
            ]);
        }

        return $this->render('BackOffice/forum/posts.html.twig', [
            'posts' => $posts,
            'current_menu' => 'posts',
            'searchQuery' => $query
        ]);
    }

    #[Route('/posts/{id}/delete', name: 'app_admin_forum_post_delete', methods: ['POST'])]
    public function deletePost(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $post = $em->getRepository(ForumPosts::class)->find($id);
        if ($post) {
            $em->remove($post);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_forum_posts');
    }

    #[Route('/comments', name: 'app_admin_forum_comments')]
    public function comments(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $comments = $em->getRepository(Comments::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('BackOffice/forum/comments.html.twig', [
            'comments' => $comments,
            'current_menu' => 'comments'
        ]);
    }

    #[Route('/comments/{id}/delete', name: 'app_admin_forum_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $comment = $em->getRepository(Comments::class)->find($id);
        if ($comment) {
            $em->remove($comment);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_forum_comments');
    }

    #[Route('/interactions', name: 'app_admin_forum_interactions')]
    public function interactions(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $interactions = $em->getRepository(Interactions::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('BackOffice/forum/interactions.html.twig', [
            'interactions' => $interactions,
            'current_menu' => 'interactions'
        ]);
    }

    #[Route('/interactions/{id}/delete', name: 'app_admin_forum_interaction_delete', methods: ['POST'])]
    public function deleteInteraction(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $interaction = $em->getRepository(Interactions::class)->find($id);
        if ($interaction) {
            $em->remove($interaction);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_forum_interactions');
    }

    #[Route('/banned-words', name: 'app_admin_forum_banned_words')]
    public function bannedWords(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $words = $em->getRepository(BannedWord::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('BackOffice/forum/banned_words.html.twig', [
            'words' => $words,
            'current_menu' => 'banned_words'
        ]);
    }

    #[Route('/banned-words/add', name: 'app_admin_forum_banned_words_add', methods: ['POST'])]
    public function addBannedWord(Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $wordText = $request->request->get('word');
        if ($wordText) {
            $existing = $em->getRepository(BannedWord::class)->findOneBy(['word' => mb_strtolower(trim($wordText))]);
            if (!$existing) {
                $bw = new BannedWord();
                $bw->setWord($wordText);
                $em->persist($bw);
                $em->flush();
                $this->addFlash('success', 'Banned word added successfully!');
            } else {
                $this->addFlash('error', 'This word is already in the list.');
            }
        }

        return $this->redirectToRoute('app_admin_forum_banned_words');
    }

    #[Route('/banned-words/{id}/delete', name: 'app_admin_forum_banned_words_delete', methods: ['POST'])]
    public function deleteBannedWord(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $bw = $em->getRepository(BannedWord::class)->find($id);
        if ($bw) {
            $em->remove($bw);
            $em->flush();
            $this->addFlash('success', 'Banned word removed.');
        }

        return $this->redirectToRoute('app_admin_forum_banned_words');
    }
}
