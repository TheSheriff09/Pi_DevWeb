<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private function getUserFromSession(Request $request, EntityManagerInterface $em): ?Users
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) return null;
        return $em->getRepository(Users::class)->find($userId);
    }

    #[Route('/dashboard/entrepreneur', name: 'app_dashboard_entrepreneur')]
    public function entrepreneur(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($request, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($user->getRole()) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This dashboard is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('FrontOffice/dashboard/entrepreneur.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/dashboard/mentor', name: 'app_dashboard_mentor')]
    public function mentor(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($request, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($user->getRole()) !== 'MENTOR') {
            $this->addFlash('error', 'Access Denied: This dashboard is reserved for Mentors.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('FrontOffice/dashboard/mentor.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/dashboard/evaluator', name: 'app_dashboard_evaluator')]
    public function evaluator(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUserFromSession($request, $em);
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($user->getRole()) !== 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: This dashboard is reserved for Evaluators.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('FrontOffice/dashboard/evaluator.html.twig', [
            'user' => $user
        ]);
    }
}
