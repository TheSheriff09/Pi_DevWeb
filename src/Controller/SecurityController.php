<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            
            $user = $em->getRepository(Users::class)->findOneBy(['email' => $email]);
            
            if ($user && (password_verify($password, $user->getPasswordHash()) || $password === $user->getPasswordHash())) {
                if (strtoupper($user->getStatus()) === 'BLOCKED') {
                    return $this->render('FrontOffice/security/login.html.twig', [
                        'error' => 'Your account is blocked. Please contact support.'
                    ]);
                }
                
                // Successful login -> manual session storage
                $request->getSession()->set('user_id', $user->getId());
                $request->getSession()->set('user_role', $user->getRole());
                
                // Redirect based on role
                $role = strtoupper($user->getRole());
                if ($role === 'ADMIN') {
                    return $this->redirectToRoute('app_admin_panel');
                }
                
                return $this->redirectToRoute('app_home');
            }
            
            // On failure, we'll route back to login probably with an error flag
            // (For premium UI later, we could pass an error message to twig)
            return $this->render('FrontOffice/security/login.html.twig', [
                'error' => 'Invalid email or password'
            ]);
        }

        return $this->render('FrontOffice/security/login.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role'); // entrepreneur, mentor, evaluator
            
            $user = new Users();
            $user->setFullName($name);
            $user->setEmail($email);
            
            // Basic password hashing logic for custom User implementation
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $user->setPasswordHash($hashedPassword);
            
            // Enforce UPPERCASE to guarantee correct ENUM matching in MySQL users table
            $user->setRole(strtoupper($role));
            $user->setStatus('ACTIVE');
            $user->setCreatedAt(new \DateTime());
            
            // Conditionally capture dynamic fields based on role
            if ($role === 'mentor') {
                $user->setMentorExpertise($request->request->get('mentorExpertise'));
            } elseif ($role === 'evaluator') {
                $user->setEvaluatorLevel($request->request->get('evaluatorLevel'));
            }
            
            try {
                $em->persist($user);
                $em->flush();
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                // If it fails (e.g. duplicate email), it intercepts and clearly dumps the exact SQL error for the user
                dd('Database Error: ' . $e->getMessage());
            }
        }

        return $this->render('FrontOffice/security/signup.html.twig');
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(Request $request): Response
    {
        // Clear all manual session variables
        $request->getSession()->invalidate();
        return $this->redirectToRoute('app_home');
    }
}
