<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\UserActivityLogger;
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
    public function login(Request $request, EntityManagerInterface $em, UserActivityLogger $logger, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $password = $request->request->get('password');
            
            $user = $em->getRepository(Users::class)->findOneBy(['email' => $email]);
            
            if ($user && (password_verify($password, $user->getPasswordHash()) || $password === $user->getPasswordHash())) {
                if (strtoupper($user->getStatus()) === 'BLOCKED') {
                    $logger->log('LOGIN_FAILED', "Blocked account login attempt for: " . $email, 'FAILED', $user);
                    return $this->render('FrontOffice/security/login.html.twig', [
                        'error' => 'Your account is blocked. Please contact support.'
                    ]);
                }
                
                if ($user->isTwoFactorEmailEnabled()) {
                    $this->triggerEmail2FA($user, $request, $mailer, $em);
                    return $this->redirectToRoute('app_login_2fa');
                }
                
                // Successful login -> manual session storage
                $request->getSession()->set('user_id', $user->getId());
                $request->getSession()->set('user_role', $user->getRole());
                
                $logger->log('LOGIN', 'User logged in successfully', 'SUCCESS', $user);
                
                // Redirect based on role
                $role = strtoupper($user->getRole());
                if ($role === 'ADMIN') {
                    return $this->redirectToRoute('app_admin_panel');
                }
                
                return $this->redirectToRoute('app_home');
            }
            
            $logger->log('LOGIN_FAILED', "Invalid credentials for: " . $email, 'FAILED');
            
            return $this->render('FrontOffice/security/login.html.twig', [
                'error' => 'Invalid email or password'
            ]);
        }

        return $this->render('FrontOffice/security/login.html.twig');
    }

    private function triggerEmail2FA(Users $user, Request $request, MailerInterface $mailer, EntityManagerInterface $em): void
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->setTwoFactorEmailCode($code);
        $em->flush();

        $request->getSession()->set('pending_2fa_user_id', $user->getId());

        $emailMsg = (new TemplatedEmail())
            ->from(new Address('linafadhel09@gmail.com', 'StartupFlow Security'))
            ->to(new Address($user->getEmail(), $user->getFullName() ?: 'User'))
            ->subject('Your 2FA Verification Code 🔒')
            ->htmlTemplate('FrontOffice/email/two_factor.html.twig')
            ->context([
                'code' => $code,
                'full_name' => $user->getFullName(),
                'year' => date('Y'),
            ]);

        try {
            $mailer->send($emailMsg);
        } catch (\Exception $e) {
            // Silently carry on to allow UI to show up at least
        }
    }

    #[Route('/login/2fa', name: 'app_login_2fa')]
    public function login2FA(Request $request, EntityManagerInterface $em, UserActivityLogger $logger): Response
    {
        $pendingUserId = $request->getSession()->get('pending_2fa_user_id');
        if (!$pendingUserId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(Users::class)->find($pendingUserId);
        if (!$user) {
            $request->getSession()->remove('pending_2fa_user_id');
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $submittedCode = $request->request->get('code');
            if ($submittedCode === $user->getTwoFactorEmailCode()) {
                // Correct code!
                $user->setTwoFactorEmailCode(null);
                $em->flush();

                $request->getSession()->remove('pending_2fa_user_id');
                $request->getSession()->set('user_id', $user->getId());
                $request->getSession()->set('user_role', $user->getRole());
                
                $logger->log('LOGIN', 'User logged in via 2FA successfully', 'SUCCESS', $user);
                
                $role = strtoupper($user->getRole());
                if ($role === 'ADMIN') {
                    return $this->redirectToRoute('app_admin_panel');
                }
                return $this->redirectToRoute('app_home');
            }
            
            return $this->render('FrontOffice/security/2fa.html.twig', [
                'error' => 'Invalid code. Please check your email and try again.'
            ]);
        }

        return $this->render('FrontOffice/security/2fa.html.twig');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, MailerInterface $mailer, UserActivityLogger $logger): Response
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
                
                $logger->log('REGISTER', 'New user registered', 'SUCCESS', $user);
                
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
    public function logout(Request $request, UserActivityLogger $logger): Response
    {
        $logger->log('LOGOUT', 'User logged out', 'SUCCESS');
        
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
    public function googleCallback(Request $request, HttpClientInterface $httpClient, EntityManagerInterface $em, UserActivityLogger $logger, MailerInterface $mailer): Response
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
                $logger->log('LOGIN_FAILED', "Blocked account login attempt via Google: " . $email, 'FAILED', $user);
                return new Response("Your account is blocked.");
            }
            
            if ($user->isTwoFactorEmailEnabled()) {
                $this->triggerEmail2FA($user, $request, $mailer, $em);
                return $this->redirectToRoute('app_login_2fa');
            }
            
            $request->getSession()->set('user_id', $user->getId());
            $request->getSession()->set('user_role', $user->getRole());
            
            $logger->log('LOGIN', 'User logged in via Google', 'SUCCESS', $user);
            
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
    public function faceLogin(Request $request, HttpClientInterface $httpClient, EntityManagerInterface $em, UserActivityLogger $logger, MailerInterface $mailer): Response
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
                        $logger->log('LOGIN_FAILED', "Blocked account login attempt via Face", 'FAILED', $user);
                        return $this->json(['status' => 'error', 'message' => 'Your account is blocked. Please contact support.']);
                    }
                    
                    if ($user->isTwoFactorEmailEnabled()) {
                        $this->triggerEmail2FA($user, $request, $mailer, $em);
                        $redirectUrl = $this->generateUrl('app_login_2fa');
                        return $this->json(['status' => 'success', 'redirect' => $redirectUrl]);
                    }
                    
                    // Securely assign session locally
                    $request->getSession()->set('user_id', $user->getId());
                    $request->getSession()->set('user_role', $user->getRole());

                    $logger->log('LOGIN', 'User logged in via Face Recognition', 'SUCCESS', $user);

                    $role = strtoupper($user->getRole());
                    $redirectUrl = ($role === 'ADMIN') ? $this->generateUrl('app_admin_panel') : $this->generateUrl('app_home');
                    
                    return $this->json(['status' => 'success', 'redirect' => $redirectUrl]);
                }
            }

            $logger->log('LOGIN_FAILED', "Failed face recognition attempt", 'FAILED');
            return $this->json(['status' => 'error', 'message' => $pyData['message'] ?? 'This face is not recognized. Please try again or use email/password.']);

        } catch (\Exception $e) {
            $logger->log('LOGIN_FAILED', "Face Login server offline", 'FAILED');
            return $this->json(['status' => 'error', 'message' => 'Face Login server is offline/timeout.']);
        }
    }
}
