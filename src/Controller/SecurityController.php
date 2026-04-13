<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
    public function register(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            $role = $request->request->get('role'); // entrepreneur, mentor, evaluator
            
            $user = new Users();
            $user->setFullName($name);
            $user->setEmail($email);
            
            // Support for Google auth skip-password
            if ($request->getSession()->get('google_email') === $email) {
                $user->setPasswordHash(null);
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $user->setPasswordHash($hashedPassword);
            }
            
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
            
            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->render('FrontOffice/security/signup.html.twig', [
                    'error' => 'Please fix the highlighted errors.'
                ]);
            }
            
            try {
                $em->persist($user);
                $em->flush();
                
                // Send Welcome Email
                $projectDir = $this->getParameter('kernel.project_dir');
                $logoPath = $projectDir . '/public/email_logo.png';
                $loginUrl = $request->getSchemeAndHttpHost() . $this->generateUrl('app_login');
                
                $emailMsg = (new TemplatedEmail())
                    ->from(new Address('linafadhel09@gmail.com', 'StartupFlow'))
                    ->to(new Address($user->getEmail(), $user->getFullName() ?: 'User'))
                    ->subject('Welcome to StartupFlow 🚀')
                    ->htmlTemplate('FrontOffice/email/welcome.html.twig')
                    ->context([
                        'full_name' => $user->getFullName() ?: 'User',
                        'login_url' => $loginUrl,
                        'year'      => date('Y'),
                    ]);
                    
                if (file_exists($logoPath)) {
                    $emailMsg->embedFromPath($logoPath, 'startupflow_logo');
                }
                
                try {
                    $mailer->send($emailMsg);
                } catch (\Exception $e) {
                    // Fail silently on email failure to not break registration
                }
                
                // Clear session tracking variables
                $request->getSession()->remove('google_email');
                $request->getSession()->remove('google_name');

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

    #[Route('/google/login', name: 'app_google_login')]
    public function googleLogin(): Response
    {
        $clientId = $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? '';
        $redirectUri = $this->generateUrl('app_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);
        
        return $this->redirect($url);
    }

    #[Route('/google/callback', name: 'app_google_callback')]
    public function googleCallback(Request $request, HttpClientInterface $httpClient, EntityManagerInterface $em): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response("Authentication failed or cancelled.", 400);
        }
        
        $clientId = $_ENV['GOOGLE_OAUTH_CLIENT_ID'] ?? '';
        $clientSecret = $_ENV['GOOGLE_OAUTH_CLIENT_SECRET'] ?? '';
        $redirectUri = $this->generateUrl('app_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $response = $httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
            'body' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]
        ]);
        
        $data = $response->toArray(false);
        if (!isset($data['access_token'])) {
            return new Response("Failed to retrieve access token.", 400);
        }
        
        $userInfoResponse = $httpClient->request('GET', 'https://openidconnect.googleapis.com/v1/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer ' . $data['access_token']
            ]
        ]);
        
        $userInfo = $userInfoResponse->toArray(false);
        $email = $userInfo['email'] ?? null;
        $name = $userInfo['name'] ?? '';
        
        if (!$email) {
            return new Response("Failed to retrieve email from Google.", 400);
        }
        
        $user = $em->getRepository(Users::class)->findOneBy(['email' => $email]);
        
        if ($user) {
            if (strtoupper($user->getStatus()) === 'BLOCKED') {
                return new Response("Your account is blocked.");
            }
            
            $request->getSession()->set('user_id', $user->getId());
            $request->getSession()->set('user_role', $user->getRole());
            
            if (strtoupper($user->getRole()) === 'ADMIN') {
                return $this->redirectToRoute('app_admin_panel');
            }
            return $this->redirectToRoute('app_home');
        }
        
        $request->getSession()->set('google_email', $email);
        $request->getSession()->set('google_name', $name);
        
        return $this->redirectToRoute('app_register');
    }

    #[Route('/api/face-login', name: 'app_face_login', methods: ['POST'])]
    public function faceLogin(Request $request, HttpClientInterface $httpClient, EntityManagerInterface $em): Response
    {
        $data = json_decode($request->getContent(), true);
        $base64Image = $data['image'] ?? null;
        if (!$base64Image) {
            return $this->json(['status' => 'error', 'message' => 'No image provided.']);
        }

        try {
            $response = $httpClient->request('POST', 'http://127.0.0.1:5001/api/login-face', [
                'json' => ['image' => $base64Image],
                'timeout' => 15
            ]);
            $pyData = $response->toArray(false);
            
            if (($pyData['status'] ?? '') === 'success' && isset($pyData['user_id'])) {
                $user = $em->getRepository(Users::class)->find($pyData['user_id']);
                if ($user) {
                    if (strtoupper($user->getStatus()) === 'BLOCKED') {
                        return $this->json(['status' => 'error', 'message' => 'Your account is blocked. Please contact support.']);
                    }
                    
                    // Securely assign session locally
                    $request->getSession()->set('user_id', $user->getId());
                    $request->getSession()->set('user_role', $user->getRole());

                    $role = strtoupper($user->getRole());
                    $redirectUrl = ($role === 'ADMIN') ? $this->generateUrl('app_admin_panel') : $this->generateUrl('app_home');
                    
                    return $this->json(['status' => 'success', 'redirect' => $redirectUrl]);
                }
            }

            return $this->json(['status' => 'error', 'message' => $pyData['message'] ?? 'This face is not recognized. Please try again or use email/password.']);

        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Face Login server is offline/timeout.']);
        }
    }
}
