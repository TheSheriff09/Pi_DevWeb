<?php

namespace App\Controller;

use App\Entity\Startup;
use App\Entity\Users;
use App\Entity\Businessplan;
use App\Entity\Fundingapplication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class StartupController extends AbstractController
{
    #[Route('/entrepreneur/startups', name: 'app_entrepreneur_startups')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Startup Management.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }
        
        // Get user's startups
        $startups = $em->getRepository(Startup::class)->findBy(['userId' => $userId]);
        
        return $this->render('FrontOffice/startup/index.html.twig', [
            'startups' => $startups,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }
    
    #[Route('/entrepreneur/startups/new', name: 'app_entrepreneur_startup_new')]
    public function new(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Startup Management.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }
        
        if ($request->isMethod('POST')) {
            $startup = new Startup();
            
            // Set basic fields
            $startup->setName($request->request->get('name'));
            $startup->setDescription($request->request->get('description'));
            $startup->setSector($request->request->get('sector'));
            $startup->setImageURL($request->request->get('imageURL'));
            
            // Set dates
            if ($request->request->get('creationDate')) {
                $startup->setCreationDate(new \DateTime($request->request->get('creationDate')));
            }
            
            // Set numeric fields
            if ($request->request->get('kPIscore')) {
                $startup->setKPIscore(floatval($request->request->get('kPIscore')));
            }
            
            if ($request->request->get('fundingAmount')) {
                $startup->setFundingAmount(floatval($request->request->get('fundingAmount')));
            }
            
            // Set other fields
            $startup->setStage($request->request->get('stage'));
            $startup->setStatus($request->request->get('status'));
            $startup->setIncubatorProgram($request->request->get('incubatorProgram'));
            
            // Set evaluation date if provided
            if ($request->request->get('lastEvaluationDate')) {
                $startup->setLastEvaluationDate(new \DateTime($request->request->get('lastEvaluationDate')));
            }
            
            // Set IDs if provided
            if ($request->request->get('mentorID')) {
                $startup->setMentorID(intval($request->request->get('mentorID')));
            }
            
            if ($request->request->get('founderID')) {
                $startup->setFounderID(intval($request->request->get('founderID')));
            }
            
            if ($request->request->get('businessPlanID')) {
                $startup->setBusinessPlanID(intval($request->request->get('businessPlanID')));
            }
            
            // Set the current user as the owner
            $startup->setUserId($userId);
            
            $errors = $validator->validate($startup);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->redirectToRoute('app_entrepreneur_startup_new');
            }
            
            try {
                $em->persist($startup);
                $em->flush();
                
                return $this->redirectToRoute('app_entrepreneur_startups');
            } catch (\Exception $e) {
                return $this->render('FrontOffice/startup/new.html.twig', [
                    'error' => 'Error creating startup: ' . $e->getMessage(),
                    'user' => $em->getRepository(Users::class)->find($userId)
                ]);
            }
        }
        
        return $this->render('FrontOffice/startup/new.html.twig', [
            'error' => null,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }
    
    #[Route('/entrepreneur/startups/{id}', name: 'app_entrepreneur_startup_show', requirements: ['id' => '\d+'])]
    public function show(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Startup Management.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        // Check if startup exists and belongs to current user
        if (!$startup || $startup->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }

        $businessPlan = $em->getRepository(Businessplan::class)->findOneBy(['startupID' => $id]);
        $fundingApplications = $em->getRepository(Fundingapplication::class)->findBy(['projectId' => $id]);

        return $this->render('FrontOffice/startup/show.html.twig', [
            'startup' => $startup,
            'businessplan' => $businessPlan,
            'fundingApplications' => $fundingApplications,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }

    #[Route('/entrepreneur/startups/{id}/edit', name: 'app_entrepreneur_startup_edit')]
    public function edit(Request $request, EntityManagerInterface $em, int $id, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Startup Management.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        // Check if startup exists and belongs to current user
        if (!$startup || $startup->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }
        
        if ($request->isMethod('POST')) {
            // Update all fields
            $startup->setName($request->request->get('name'));
            $startup->setDescription($request->request->get('description'));
            $startup->setSector($request->request->get('sector'));
            $startup->setImageURL($request->request->get('imageURL'));
            
            if ($request->request->get('creationDate')) {
                $startup->setCreationDate(new \DateTime($request->request->get('creationDate')));
            }
            
            if ($request->request->get('kPIscore')) {
                $startup->setKPIscore(floatval($request->request->get('kPIscore')));
            }
            
            if ($request->request->get('fundingAmount')) {
                $startup->setFundingAmount(floatval($request->request->get('fundingAmount')));
            }
            
            $startup->setStage($request->request->get('stage'));
            $startup->setStatus($request->request->get('status'));
            $startup->setIncubatorProgram($request->request->get('incubatorProgram'));
            
            if ($request->request->get('lastEvaluationDate')) {
                $startup->setLastEvaluationDate(new \DateTime($request->request->get('lastEvaluationDate')));
            }
            
            if ($request->request->get('mentorID')) {
                $startup->setMentorID(intval($request->request->get('mentorID')));
            }
            
            if ($request->request->get('founderID')) {
                $startup->setFounderID(intval($request->request->get('founderID')));
            }
            
            if ($request->request->get('businessPlanID')) {
                $startup->setBusinessPlanID(intval($request->request->get('businessPlanID')));
            }
            
            $errors = $validator->validate($startup);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->redirectToRoute('app_entrepreneur_startup_edit', ['id' => $startup->getId()]);
            }
            
            try {
                $em->flush();
                return $this->redirectToRoute('app_entrepreneur_startups');
            } catch (\Exception $e) {
                return $this->render('FrontOffice/startup/edit.html.twig', [
                    'startup' => $startup,
                    'error' => 'Error updating startup: ' . $e->getMessage(),
                    'user' => $em->getRepository(Users::class)->find($userId)
                ]);
            }
        }
        
        return $this->render('FrontOffice/startup/edit.html.twig', [
            'startup' => $startup,
            'error' => null,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }
    
    #[Route('/entrepreneur/startups/{id}/delete', name: 'app_entrepreneur_startup_delete')]
    public function delete(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Startup Management.');
            return $this->redirectToRoute('app_home');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        // Check if startup exists and belongs to current user
        if (!$startup || $startup->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }
        
        try {
            $em->remove($startup);
            $em->flush();
        } catch (\Exception $e) {
            // Handle error silently or add flash message
        }
        
        return $this->redirectToRoute('app_entrepreneur_startups');
    }

    #[Route('/entrepreneur/startups/{id}/swot', name: 'app_entrepreneur_startup_swot', methods: ['POST'])]
    public function swotGenerate(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        set_time_limit(300); // Allow up to 5 minutes for generation
        
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return new JsonResponse(['error' => 'Access Denied: Evaluators are not allowed to access Startup Management.'], Response::HTTP_FORBIDDEN);
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        if (!$startup || $startup->getUserId() !== $userId) {
            return new JsonResponse(['error' => 'Startup not found or unauthorized'], Response::HTTP_NOT_FOUND);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $pythonScript = $projectDir . '/bin/swot_generator.py';

        $name = $startup->getName() ?: '';
        $description = $startup->getDescription() ?: '';
        $sector = $startup->getSector() ?: '';

        // Determine python executable (try 'py', 'python' or 'python3')
        $pythonExe = DIRECTORY_SEPARATOR === '\\' ? 'py' : 'python3';
        $process = new Process([$pythonExe, $pythonScript, $name, $description, $sector]);
        $process->setTimeout(300);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            return new JsonResponse(json_decode(trim($output), true));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/entrepreneur/startups/{id}/forecast', name: 'app_entrepreneur_startup_forecast', methods: ['POST'])]
    public function forecastGenerate(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return new JsonResponse(['error' => 'Access Denied: Evaluators are not allowed to access Startup Management.'], Response::HTTP_FORBIDDEN);
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        if (!$startup || $startup->getUserId() !== $userId) {
            return new JsonResponse(['error' => 'Startup not found or unauthorized'], Response::HTTP_NOT_FOUND);
        }

        $revenue = $request->request->get('revenue');
        $growth = $request->request->get('growth');
        $expenses = $request->request->get('expenses');

        if (!$revenue || !$growth || !$expenses) {
            return new JsonResponse(['error' => 'Missing inputs'], Response::HTTP_BAD_REQUEST);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $pythonScript = $projectDir . '/bin/forecast_generator.py';
        $pythonExe = DIRECTORY_SEPARATOR === '\\' ? 'py' : 'python3';

        $process = new Process([$pythonExe, $pythonScript, $revenue, $growth, $expenses]);
        $process->setTimeout(60);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            return new JsonResponse(json_decode(trim($output), true));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
