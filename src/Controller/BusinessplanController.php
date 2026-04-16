<?php

namespace App\Controller;

use App\Entity\Businessplan;
use App\Entity\Startup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BusinessplanController extends AbstractController
{
    #[Route('/entrepreneur/startups/{startupId}/businessplan/new', name: 'app_entrepreneur_businessplan_new')]
    public function new(Request $request, EntityManagerInterface $em, int $startupId, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Business Plan features.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }

        $startup = $em->getRepository(Startup::class)->find($startupId);
        
        if (!$startup || $startup->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }

        // Redirect if a business plan already exists for this startup
        $existingPlan = $em->getRepository(Businessplan::class)->findOneBy(['startupID' => $startupId]);
        if ($existingPlan) {
            return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $startupId]);
        }

        if ($request->isMethod('POST')) {
            $bp = new Businessplan();
            
            $bp->setTitle($request->request->get('title'));
            $bp->setDescription($request->request->get('description'));
            $bp->setMarketAnalysis($request->request->get('marketAnalysis'));
            $bp->setValueProposition($request->request->get('valueProposition'));
            $bp->setBusinessModel($request->request->get('businessModel'));
            $bp->setMarketingStrategy($request->request->get('marketingStrategy'));
            $bp->setFinancialForecast($request->request->get('financialForecast'));
            
            if ($request->request->get('fundingRequired')) {
                $bp->setFundingRequired(floatval($request->request->get('fundingRequired')));
            }

            $bp->setTimeline($request->request->get('timeline'));
            $bp->setStatus($request->request->get('status') ?: 'Draft');
            
            $now = new \DateTime();
            $bp->setCreationDate($now);
            $bp->setLastUpdate($now);
            
            $bp->setStartupID($startupId);
            $bp->setUserId($userId);
            
            $errors = $validator->validate($bp);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->redirectToRoute('app_entrepreneur_businessplan_new', ['startupId' => $startupId]);
            }
            
            $em->persist($bp);
            $em->flush();
            
            // Also update the startup's businessPlanID field to link them mutually
            $startup->setBusinessPlanID($bp->getBusinessPlanID());
            $em->flush();

            return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $startupId]);
        }

        return $this->render('FrontOffice/businessplan/new.html.twig', [
            'startup' => $startup
        ]);
    }

    #[Route('/entrepreneur/businessplan/{id}/edit', name: 'app_entrepreneur_businessplan_edit')]
    public function edit(Request $request, EntityManagerInterface $em, int $id, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Business Plan features.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }

        $bp = $em->getRepository(Businessplan::class)->find($id);
        
        if (!$bp || $bp->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }

        if ($request->isMethod('POST')) {
            $bp->setTitle($request->request->get('title'));
            $bp->setDescription($request->request->get('description'));
            $bp->setMarketAnalysis($request->request->get('marketAnalysis'));
            $bp->setValueProposition($request->request->get('valueProposition'));
            $bp->setBusinessModel($request->request->get('businessModel'));
            $bp->setMarketingStrategy($request->request->get('marketingStrategy'));
            $bp->setFinancialForecast($request->request->get('financialForecast'));
            
            if ($request->request->get('fundingRequired')) {
                $bp->setFundingRequired(floatval($request->request->get('fundingRequired')));
            }

            $bp->setTimeline($request->request->get('timeline'));
            $bp->setStatus($request->request->get('status'));
            $bp->setLastUpdate(new \DateTime());
            
            $errors = $validator->validate($bp);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->redirectToRoute('app_entrepreneur_businessplan_edit', ['id' => $bp->getBusinessPlanID()]);
            }
            
            $em->flush();

            return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $bp->getStartupID()]);
        }

        $startup = $em->getRepository(Startup::class)->find($bp->getStartupID());

        return $this->render('FrontOffice/businessplan/edit.html.twig', [
            'businessplan' => $bp,
            'startup' => $startup
        ]);
    }

    #[Route('/entrepreneur/businessplan/{id}/delete', name: 'app_entrepreneur_businessplan_delete')]
    public function delete(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Business Plan features.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }

        $bp = $em->getRepository(Businessplan::class)->find($id);
        
        if (!$bp || $bp->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }

        $startupId = $bp->getStartupID();

        // Mutually Nullify the business plan reference from Startup before dropping it
        $startup = $em->getRepository(Startup::class)->find($startupId);
        if($startup) {
            $startup->setBusinessPlanID(null);
        }

        $em->remove($bp);
        $em->flush();

        return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $startupId]);
    }

    #[Route('/entrepreneur/businessplan/ai-generate', name: 'app_entrepreneur_businessplan_ai_generate', methods: ['POST'])]
    public function generateAI(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return new JsonResponse(['error' => 'Access Denied: Evaluators are not allowed to access Business Plan features.'], Response::HTTP_FORBIDDEN);
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $startupId = $request->request->get('startupId');
        $startup = $em->getRepository(Startup::class)->find($startupId);

        if (!$startup || $startup->getUserId() !== $userId) {
            return new JsonResponse(['error' => 'Startup not found'], Response::HTTP_NOT_FOUND);
        }

        $targetMarket = $request->request->get('targetMarket', 'General Public');
        
        $projectDir = $this->getParameter('kernel.project_dir');
        $pythonScript = $projectDir . '/bin/business_plan_generator.py';
        $pythonExe = DIRECTORY_SEPARATOR === '\\' ? 'py' : 'python3';

        $process = new Process([
            $pythonExe, 
            $pythonScript, 
            (string)($startup->getName() ?: 'My Startup'),
            (string)($startup->getSector() ?: 'Generic Sector'),
            (string)($startup->getDescription() ?: 'An innovative new business.'),
            (string)($startup->getFundingAmount() ?: '0'),
            (string)$targetMarket
        ]);
        $process->setTimeout(300);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            $data = json_decode(trim($output), true);
            
            if (!$data || isset($data['error'])) {
                return new JsonResponse(['error' => $data['error'] ?? 'AI generator returned invalid data'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            
            return new JsonResponse($data);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Process Error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
