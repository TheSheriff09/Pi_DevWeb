<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            $request->getSession()->invalidate();
            return $this->redirectToRoute('app_login');
        }

        $message = null;

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');

            if ($name) $user->setFullName($name);
            if ($email) $user->setEmail($email);
            
            if ($password) {
                // Assuming legacy plain text wasn't fixed for them, we hash new passwords properly.
                // Or if we want to be consistent with the system, maybe we don't hash, but let's hash.
                $user->setPasswordHash(password_hash($password, PASSWORD_BCRYPT));
            }

            try {
                $em->flush();
                $message = 'Profile updated successfully!';
            } catch (\Exception $e) {
                $message = 'Error updating profile: ' . $e->getMessage();
            }
        }

        return $this->render('FrontOffice/profile/index.html.twig', [
            'user' => $user,
            'message' => $message
        ]);
    }
}
