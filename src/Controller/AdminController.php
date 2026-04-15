<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_panel')]
    public function index(Request $request): Response
    {
        // Simple security check based on manual session
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('BackOffice/admin/panel.html.twig');
    }
}
