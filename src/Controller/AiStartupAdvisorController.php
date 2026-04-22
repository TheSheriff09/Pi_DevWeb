<?php

namespace App\Controller;

use App\Entity\Startup;
use App\Entity\Businessplan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiStartupAdvisorController extends AbstractController
{
    #[Route('/entrepreneur/startups/{id}/ai-chat', name: 'app_startup_ai_chat', methods: ['POST'])]
    public function chat(int $id, Request $request, EntityManagerInterface $em, HttpClientInterface $client): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $startup = $em->getRepository(Startup::class)->find($id);
        if (!$startup || $startup->getUserId() !== $userId) {
            return $this->json(['error' => 'Startup not found or unauthorized'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? null;

        if (!$userMessage) {
            return $this->json(['error' => 'No message provided'], Response::HTTP_BAD_REQUEST);
        }

        $businessPlan = $em->getRepository(Businessplan::class)->findOneBy(['startupID' => $id]);

        $apiKey = $_ENV['GEMINI_API_KEY'] ?? null;
        if (!$apiKey) {
            return $this->json(['error' => 'AI API Key not configured'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Construct context
        $context = sprintf(
            "Startup Name: %s\nSector: %s\nDescription: %s\nFunding: %s\nStage: %s\n",
            $startup->getName(),
            $startup->getSector(),
            $startup->getDescription(),
            $startup->getFundingAmount(),
            $startup->getStage()
        );

        if ($businessPlan) {
            $context .= sprintf(
                "Business Plan Title: %s\nMarket Analysis: %s\nValue Proposition: %s\nBusiness Model: %s\n",
                $businessPlan->getTitle(),
                $businessPlan->getMarketAnalysis(),
                $businessPlan->getValueProposition(),
                $businessPlan->getBusinessModel()
            );
        }

        $systemPrompt = "You are a highly experienced Venture Capitalist and Startup Advisor. 
        Your goal is to provide deep, actionable, and professional advice to entrepreneurs.
        Analyze the startup data provided and answer the user's question based ONLY on this context.
        Be critical where needed but always constructive. Use a professional, expert tone.
        
        STARTUP CONTEXT:
        $context
        
        USER QUESTION:
        $userMessage";

        try {
            $response = $client->request('POST', 'https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash-lite:generateContent?key=' . $apiKey, [
                'json' => [
                    'contents' => [
                        ['parts' => [['text' => $systemPrompt]]]
                    ]
                ]
            ]);

            $result = $response->toArray();
            $aiResponse = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'I apologize, but I could not analyze the startup at this moment.';

            return $this->json(['response' => trim($aiResponse)]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'AI Service Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
