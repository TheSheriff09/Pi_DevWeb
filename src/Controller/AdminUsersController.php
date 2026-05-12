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

        $sort = $request->query->get('sort', 'id');
        $direction = strtoupper($request->query->get('direction', 'ASC'));
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $validSorts = [
            'id' => 'u.id',
            'fullName' => 'u.fullName',
            'email' => 'u.email',
            'role' => 'u.role',
            'createdAt' => 'u.createdAt',
            'status' => 'u.status'
        ];
        $sortField = $validSorts[$sort] ?? 'u.id';

        $users = $em->getRepository(Users::class)->createQueryBuilder('u')
            ->orderBy($sortField, $direction)
            ->getQuery()
            ->getResult();

        return $this->render('BackOffice/users/index.html.twig', [
            'users' => $users,
            'current_module' => 'users',
            'current_menu' => 'list'
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
    #[Route('/admin/users/ajax', name: 'app_admin_users_ajax', methods: ['GET'])]
    public function ajaxUsers(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return new Response('Unauthorized', 403);
        }

        $searchQuery = $request->query->get('searchId');
        $sort = $request->query->get('sort', 'id');
        $direction = strtoupper($request->query->get('direction', 'ASC'));
        
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }

        $validSorts = [
            'id' => 'u.id',
            'fullName' => 'u.fullName',
            'email' => 'u.email',
            'role' => 'u.role',
            'createdAt' => 'u.createdAt',
            'status' => 'u.status'
        ];
        $sortField = $validSorts[$sort] ?? 'u.id';

        $qb = $em->getRepository(Users::class)->createQueryBuilder('u');

        if ($searchQuery) {
            $qb->andWhere('u.id = :searchId')
               ->setParameter('searchId', $searchQuery);
        }

        $qb->orderBy($sortField, $direction);

        $users = $qb->getQuery()->getResult();

        return $this->render('BackOffice/users/_users_tbody.html.twig', [
            'users' => $users,
        ]);
    }
}
