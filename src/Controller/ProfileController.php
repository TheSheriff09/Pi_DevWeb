<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    #[Route('/profile/register-face', name: 'app_profile_face_register', methods: ['POST'])]
    public function registerFace(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['status' => 'error', 'message' => 'Not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $base64Image = $data['image'] ?? null;

        if (!$base64Image) {
            return $this->json(['status' => 'error', 'message' => 'No image provided.']);
        }

        try {
            // Forward base64 to Python AI server securely on backend port
            $response = $httpClient->request('POST', 'http://127.0.0.1:5001/api/register-face', [
                'json' => [
                    'user_id' => $userId,
                    'image' => $base64Image
                ],
                'timeout' => 15
            ]);
            
            $pyData = $response->toArray(false); // Parse Json regardless of python HTTP error code
            return $this->json($pyData);

        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Face Registration server is currently offline.']);
        }
    }

    #[Route('/profile/2fa/toggle', name: 'app_profile_2fa_toggle', methods: ['POST'])]
    public function toggle2fa(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['status' => 'error', 'message' => 'Not authenticated'], 401);
        }

        $user = $em->getRepository(Users::class)->find($userId);
        if (!$user) {
            return $this->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $enable = $data['enable'] ?? false;

        $user->setIsTwoFactorEmailEnabled((bool)$enable);
        $em->flush();

        return $this->json([
            'status' => 'success', 
            'message' => 'Email Authentication algorithm has been ' . ($enable ? 'activated' : 'disabled') . ' successfully.'
        ]);
    }
}
