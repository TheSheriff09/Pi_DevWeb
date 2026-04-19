<?php

namespace App\Controller;

use App\Entity\Fundingapplication;
use App\Entity\Fundingevaluation;
use App\Entity\Startup;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FundingEvaluationController extends AbstractController
{
    #[Route('/evaluator/funding', name: 'app_evaluator_funding_list')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        // Strict role validation
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        if (strtoupper($userRole) !== 'EVALUATOR') {
            $this->addFlash('error', 'You do not have the right access to access this page.');
            return $this->redirectToRoute('app_home');
        }

        // Fetch all funding applications (potentially order by pending first)
        $applications = $em->getRepository(Fundingapplication::class)->findBy([], ['submissionDate' => 'DESC']);
        
        // Map Startups dynamically to avoid joining loops in twig
        $startupsMap = [];
        foreach ($applications as $app) {
            $startup = $em->getRepository(Startup::class)->find($app->getProjectId());
            if ($startup) {
                $startupsMap[$app->getId()] = $startup;
            }
        }

        return $this->render('FrontOffice/fundingevaluation/index.html.twig', [
            'applications' => $applications,
            'startupsMap' => $startupsMap,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }

    #[Route('/evaluator/funding/{id}/evaluate', name: 'app_evaluator_funding_evaluate')]
    public function evaluate(Request $request, EntityManagerInterface $em, int $id, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        if (strtoupper($userRole) !== 'EVALUATOR') {
            $this->addFlash('error', 'You do not have the right access to access this page.');
            return $this->redirectToRoute('app_home');
        }

        $application = $em->getRepository(Fundingapplication::class)->find($id);
        
        if (!$application) {
            return $this->redirectToRoute('app_evaluator_funding_list');
        }

        $startup = $em->getRepository(Startup::class)->find($application->getProjectId());

        // Block re-evaluating if something already fully accepted/rejected without a reset process.
        if ($application->getStatus() !== 'Pending') {
            return $this->redirectToRoute('app_evaluator_funding_list');
        }

        if ($request->isMethod('POST')) {
            $evaluation = new Fundingevaluation();
            // Fix for missing Auto-Increment constraint in schema
            $maxId = $em->createQuery('SELECT MAX(f.id) FROM App\Entity\Fundingevaluation f')->getSingleScalarResult();
            $evaluation->setId($maxId ? $maxId + 1 : 1);
            
            // Map manual inputs
            $evaluation->setScore((int)$request->request->get('score'));
            $decision = $request->request->get('decision'); // "Accepted" or "Rejected"
            $evaluation->setDecision($decision);
            $evaluation->setEvaluationComments($request->request->get('evaluationComments'));
            $evaluation->setRiskLevel($request->request->get('riskLevel'));
            $evaluation->setFundingCategory($request->request->get('fundingCategory'));

            // Map strict automated metrics
            $evaluation->setFundingApplicationId($id);
            $evaluation->setEvaluatorId($userId);
            $evaluation->setCreatedAt(new \DateTime());
            
            // Critical feature: automatically morph the actual application status implicitly!
            $application->setStatus($decision);

            $errors = $validator->validate($evaluation);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->redirectToRoute('app_evaluator_funding_evaluate', ['id' => $id]);
            }

            $em->persist($evaluation);
            $em->flush(); // Flushes both the evaluation AND the updated Application status symmetrically

            return $this->redirectToRoute('app_evaluator_funding_list');
        }

        return $this->render('FrontOffice/fundingevaluation/evaluate.html.twig', [
            'application' => $application,
            'startup' => $startup,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }

    #[Route('/evaluator/funding/{id}/autoevaluate', name: 'app_evaluator_funding_autoevaluate', methods: ['POST'])]
    public function autoEvaluate(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId || strtoupper($userRole) !== 'EVALUATOR') {
            return $this->redirectToRoute('app_login');
        }

        $application = $em->getRepository(Fundingapplication::class)->find($id);
        if (!$application || $application->getStatus() !== 'Pending') {
            return $this->redirectToRoute('app_evaluator_funding_list');
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $pythonScript = $projectDir . '/train_funding_model.py';

        $schedule = $application->getPaymentSchedule() ?: '';
        $amount = $application->getAmount() ?: 0;

        $pythonExe = DIRECTORY_SEPARATOR === '\\' ? $projectDir . '\.venv\Scripts\python.exe' : $projectDir . '/.venv/bin/python';
        if (!file_exists($pythonExe)) {
            $pythonExe = DIRECTORY_SEPARATOR === '\\' ? 'python' : 'python3';
        }
        
        $process = new \Symfony\Component\Process\Process([$pythonExe, $pythonScript, 'predict', $schedule, $amount]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->addFlash('error', 'Auto-Evaluation system engine encountered a failure.');
            return $this->redirectToRoute('app_evaluator_funding_list');
        }

        $output = json_decode($process->getOutput(), true);
        if (!isset($output['probability'])) {
            $this->addFlash('error', 'Auto-Evaluation failed to read statistical probabilities.');
            return $this->redirectToRoute('app_evaluator_funding_list');
        }

        $probability = (float) $output['probability'];
        $score = (int) round($probability * 100);
        $decision = $probability > 0.50 ? 'Accepted' : 'Rejected';
        
        $evaluation = new Fundingevaluation();
        $maxId = $em->createQuery('SELECT MAX(f.id) FROM App\Entity\Fundingevaluation f')->getSingleScalarResult();
        $evaluation->setId($maxId ? $maxId + 1 : 1);
        
        $evaluation->setFundingApplicationId($id);
        $evaluation->setScore($score);
        $evaluation->setDecision($decision);
        $evaluation->setEvaluationComments("Auto-evaluated by ML model with probability " . $score . "%");
        $evaluation->setEvaluatorId(0); // 0 corresponds to System Engine ID
        $evaluation->setCreatedAt(new \DateTime());
        $evaluation->setRiskLevel('AI-Assessed');
        $evaluation->setFundingCategory('Auto');
        
        $application->setStatus($decision);

        $em->persist($evaluation);
        $em->flush();

        $this->addFlash('success', 'Application was technically ' . $decision . ' with a ' . $score . '% computed likelihood!');
        return $this->redirectToRoute('app_evaluator_funding_list');
    }
}
