<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminUsersController extends AbstractController
{
    private function isAdmin(Request $request): bool
    {
        $role = $request->getSession()->get('user_role');
        return $role && strtoupper($role) === 'ADMIN';
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $users = $em->getRepository(Users::class)->findAll();

        return $this->render('BackOffice/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/status/{id}', name: 'app_admin_users_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $status = strtoupper($request->request->get('status'));
        $user = $em->getRepository(Users::class)->find($id);
        
        $validStatuses = ['ACTIVE', 'BLOCKED'];

        if ($user && in_array($status, $validStatuses)) {
            $user->setStatus($status);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
