<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
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
                $user->setPasswordHash(password_hash($password, PASSWORD_BCRYPT));
            }

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                $message = 'Validation Error: ' . $errors[0]->getPropertyPath() . ' - ' . $errors[0]->getMessage();
                $this->addFlash('error', $message);
            } else {
                try {
                    $em->flush();
                    $message = 'Profile updated successfully!';
                } catch (\Exception $e) {
                    $message = 'Error updating profile: ' . $e->getMessage();
                }
            }
        }

        return $this->render('FrontOffice/profile/index.html.twig', [
            'user' => $user,
            'message' => $message
        ]);
    }
}
