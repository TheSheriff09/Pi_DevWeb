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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This area is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }
        
        // Get user's startups with optimized eager loading to prevent N+1 queries
        $startups = $em->getRepository(Startup::class)->createQueryBuilder('s')
            ->leftJoin('s.mentor', 'm')->addSelect('m')
            ->leftJoin('s.founder', 'f')->addSelect('f')
            ->leftJoin('s.businessPlan', 'b')->addSelect('b')
            ->where('s.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
        
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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This area is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }
        
        if ($request->isMethod('POST')) {
            $startup = new Startup();
            
            // Set basic fields
            $startup->setName((string) $request->request->get('name'));
            $startup->setDescription((string) $request->request->get('description'));
            $startup->setSector((string) $request->request->get('sector'));
            $startup->setImageURL((string) $request->request->get('imageURL'));
            
            // Set dates
            if ($request->request->get('creationDate')) {
                $startup->setCreationDate(new \DateTime((string) $request->request->get('creationDate')));
            }
            
            // Set numeric fields
            if ($request->request->get('kPIscore')) {
                $startup->setKPIscore(floatval($request->request->get('kPIscore')));
            }
            
            if ($request->request->get('fundingAmount')) {
                $startup->setFundingAmount(floatval($request->request->get('fundingAmount')));
            }
            
            // Set other fields
            $startup->setStage((string) $request->request->get('stage'));
            $startup->setStatus((string) $request->request->get('status'));
            $startup->setIncubatorProgram((string) $request->request->get('incubatorProgram'));
            
            // Set evaluation date if provided
            if ($request->request->get('lastEvaluationDate')) {
                $startup->setLastEvaluationDate(new \DateTime((string) $request->request->get('lastEvaluationDate')));
            }
            
            // Set IDs if provided
            if ($request->request->get('mentorID')) {
                $mentor = $em->getRepository(Users::class)->find($request->request->get('mentorID'));
                $startup->setMentor($mentor);
            }
            
            if ($request->request->get('founderID')) {
                $founder = $em->getRepository(Users::class)->find($request->request->get('founderID'));
                $startup->setFounder($founder);
            }
            
            if ($request->request->get('businessPlanID')) {
                $bp = $em->getRepository(Businessplan::class)->find($request->request->get('businessPlanID'));
                $startup->setBusinessPlan($bp);
            }
            
            // Set the current user as the owner
            $startup->setUser($em->getRepository(Users::class)->find($userId));
            
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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This area is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        // Check if startup exists and belongs to current user
        if (!$startup || $startup->getUser()->getId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }

        $businessPlan = $em->getRepository(Businessplan::class)->findOneBy(['startup' => $id]);
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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This area is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        // Check if startup exists and belongs to current user
        if (!$startup || $startup->getUser()->getId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }
        
        if ($request->isMethod('POST')) {
            // Update all fields
            $startup->setName((string) $request->request->get('name'));
            $startup->setDescription((string) $request->request->get('description'));
            $startup->setSector((string) $request->request->get('sector'));
            $startup->setImageURL((string) $request->request->get('imageURL'));
            
            if ($request->request->get('creationDate')) {
                $startup->setCreationDate(new \DateTime((string) $request->request->get('creationDate')));
            }
            
            if ($request->request->get('kPIscore')) {
                $startup->setKPIscore(floatval($request->request->get('kPIscore')));
            }
            
            if ($request->request->get('fundingAmount')) {
                $startup->setFundingAmount(floatval($request->request->get('fundingAmount')));
            }
            
            $startup->setStage((string) $request->request->get('stage'));
            $startup->setStatus((string) $request->request->get('status'));
            $startup->setIncubatorProgram((string) $request->request->get('incubatorProgram'));
            
            if ($request->request->get('lastEvaluationDate')) {
                $startup->setLastEvaluationDate(new \DateTime((string) $request->request->get('lastEvaluationDate')));
            }
            
            if ($request->request->get('mentorID')) {
                $startup->setMentor($em->getRepository(Users::class)->find($request->request->get('mentorID')));
            }
            
            if ($request->request->get('founderID')) {
                $startup->setFounder($em->getRepository(Users::class)->find($request->request->get('founderID')));
            }
            
            if ($request->request->get('businessPlanID')) {
                $startup->setBusinessPlan($em->getRepository(Businessplan::class)->find($request->request->get('businessPlanID')));
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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This area is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        // Check if startup exists and belongs to current user
        if (!$startup || $startup->getUser()->getId() !== $userId) {
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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return new JsonResponse(['error' => 'Access Denied: This area is reserved for Entrepreneurs.'], Response::HTTP_FORBIDDEN);
        }
        
        $startup = $em->getRepository(Startup::class)->find($id);
        
        if (!$startup || $startup->getUser()->getId() !== $userId) {
            return new JsonResponse(['error' => 'Startup not found or unauthorized'], Response::HTTP_NOT_FOUND);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $pythonScript = (is_string($projectDir) ? $projectDir : '') . '/bin/swot_generator.py';

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
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return new JsonResponse(['error' => 'Access Denied: This area is reserved for Entrepreneurs.'], Response::HTTP_FORBIDDEN);
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
        $pythonScript = (is_string($projectDir) ? $projectDir : '') . '/bin/forecast_generator.py';
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
