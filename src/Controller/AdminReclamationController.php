<?php

namespace App\Controller;

use App\Entity\Reclamations;
use App\Entity\Responses as ReclamationResponses;
use App\Entity\Users;
use App\Entity\Fundingevaluation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AdminReclamationController extends AbstractController
{
    private function isAdmin(Request $request): bool
    {
        $role = $request->getSession()->get('user_role');
        return $role && strtoupper($role) === 'ADMIN';
    }

    #[Route('/admin/reclamations', name: 'app_admin_reclamations')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $evaluations = $em->getRepository(Fundingevaluation::class)->findAll();
        $existingAlerts = $em->getRepository(Reclamations::class)->findBy(['title' => 'AI Bias Alert']);
        $existingAppIds = [];
        foreach ($existingAlerts as $alert) {
            if (preg_match('/Application #(\d+)/', $alert->getDescription(), $matches)) {
                $existingAppIds[] = (int)$matches[1];
            }
        }
        
        $flushNeeded = false;
        foreach ($evaluations as $eval) {
            $decision = $eval->getDecision();
            $score = $eval->getScore() !== null ? (float)$eval->getScore() : 50.0;
            $evalId = $eval->getEvaluatorId() ?: 0;
            $appId = $eval->getFundingApplicationId() ?: 0;

            if (($decision === 'Approved' && $score < 40) || ($decision === 'Rejected' && $score > 70)) {
                if (!in_array($appId, $existingAppIds)) {
                    $cat = $eval->getFundingCategory() ?: 'General';
                    $message = "Reclamation Alert: Application #{$appId} " . strtolower($decision) . " with probability {$score}%. Possible bias detected.";
                    
                    $biasRec = new \App\Entity\Reclamations();
                    $biasRec->setTitle('AI Bias Alert');
                    $biasRec->setDescription($message . " (Category: {$cat})");
                    $biasRec->setRequestedId(0); // System
                    $biasRec->setTargetId($evalId);
                    $biasRec->setStatus('OPEN');
                    $biasRec->setCreatedAt($eval->getCreatedAt() ?: new \DateTime());
                    
                    $em->persist($biasRec);
                    $existingAppIds[] = $appId;
                    $flushNeeded = true;
                }
            }
        }
        
        if ($flushNeeded) {
            $em->flush();
        }

        $reclamationsRaw = $em->getRepository(Reclamations::class)->findAll();
        
        $usersResult = $em->getRepository(Users::class)->findAll();
        $usersMap = [0 => 'System']; // 0 is used for System requests
        $adminsMap = [];
        foreach ($usersResult as $user) {
            $usersMap[$user->getId()] = $user->getFullName();
            if ($user->getRole() === 'ADMIN' || $user->getRole() === 'admin') {
                $adminsMap[$user->getId()] = $user->getFullName();
            }
        }

        $responsesRepo = $em->getRepository(ReclamationResponses::class);
        $reclamations = [];
        
        foreach ($reclamationsRaw as $rec) {
            $responses = $responsesRepo->findBy(['reclamationId' => $rec->getId()], ['createdAt' => 'ASC']);
            
            $resList = [];
            foreach ($responses as $r) {
                // Determine admin name safely, default to 'Admin' if not mapped
                $adminName = $usersMap[$r->getResponderUserId()] ?? 'Admin';
                
                $resList[] = [
                    'id' => $r->getId(),
                    'content' => $r->getContent(),
                    'date' => $r->getCreatedAt()->format('Y-m-d H:i'),
                    'adminName' => $adminName
                ];
            }
            
            $reclamations[] = [
                'entity' => $rec,
                'responsesList' => $resList
            ];
        }

        return $this->render('BackOffice/reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'usersMap' => $usersMap,
            'current_module' => 'reclamations'
        ]);
    }

    #[Route('/admin/reclamation/delete/{id}', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        if ($reclamation) {
            // Also delete related responses implicitly, or let cascade do it if defined. 
            // Better to delete explicitly if cascade isn't defined
            $responses = $em->getRepository(ReclamationResponses::class)->findBy(['reclamationId' => $reclamation->getId()]);
            foreach ($responses as $r) {
                $em->remove($r);
            }
            
            $em->remove($reclamation);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/admin/reclamation/status/{id}', name: 'app_admin_reclamation_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $status = $request->request->get('status');
        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        
        $validStatuses = ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'REJECTED'];

        if ($reclamation && $status && in_array($status, $validStatuses)) {
            $reclamation->setStatus($status);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/admin/reclamation/respond/{id}', name: 'app_admin_reclamation_respond', methods: ['POST'])]
    public function respond(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $content = $request->request->get('response_content');
        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        
        if ($reclamation && $content) {
            $response = new ReclamationResponses();
            $response->setContent($content);
            $response->setCreatedAt(new \DateTime());
            $response->setReclamationId($reclamation->getId());
            $response->setResponderUserId($request->getSession()->get('user_id'));
            
            $em->persist($response);
            
            // Optionally, we could set status to IN_PROGRESS if it's OPEN
            if ($reclamation->getStatus() === 'OPEN') {
                $reclamation->setStatus('IN_PROGRESS');
            }

            $em->flush();
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/admin/reclamation/ai-respond/{id}', name: 'app_admin_reclamation_ai_respond', methods: ['POST'])]
    public function aiRespond(int $id, EntityManagerInterface $em, HttpClientInterface $client, Request $request): JsonResponse
    {
        if (!$this->isAdmin($request)) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        if (!$reclamation) {
            return $this->json(['error' => 'Reclamation not found'], Response::HTTP_NOT_FOUND);
        }

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$apiKey) {
            return $this->json(['error' => 'AI API Key not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $prompt = sprintf(
            "You are a customer support administrator for 'StartupFlow Tunisia'. 
            You are responding to a user reclamation.
            RECLAMATION TYPE: %s
            DESCRIPTION: %s
            
            Write a professional, empathetic, and helpful response to this user. 
            Keep it concise and official. 
            If it's an 'AI Bias Alert', explain that our technical team will review the evaluation process immediately.
            If it's a general problem, assure the user that we are looking into it.
            Response:",
            $reclamation->getTitle(),
            $reclamation->getDescription()
        );

        try {
            $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash-lite:generateContent?key=' . $apiKey, [
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]
            ]);

            $result = $response->toArray();
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'Could not generate AI response.';

            return $this->json(['response' => trim($aiText)]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'AI Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
