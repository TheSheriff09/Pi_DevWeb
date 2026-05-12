<?php

namespace App\Controller;

use App\Entity\Fundingapplication;
use App\Entity\Startup;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FundingApplicationController extends AbstractController
{
    #[Route('/entrepreneur/startups/{startupId}/funding/new', name: 'app_entrepreneur_funding_new')]
    public function new(Request $request, EntityManagerInterface $em, int $startupId, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        // Block access to non-entrepreneurs seamlessly
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            $this->addFlash('error', 'Access Denied: This area is reserved for Entrepreneurs.');
            return $this->redirectToRoute('app_home');
        }

        $startup = $em->getRepository(Startup::class)->find($startupId);
        
        // Strict ownership check
        if (!$startup || $startup->getUserId() !== $userId) {
            return $this->redirectToRoute('app_entrepreneur_startups');
        }

        if ($request->isMethod('POST')) {
            $application = new Fundingapplication();
            
            // Core constraints manually extracted from UI
            $application->setAmount((float) $request->request->get('amount'));
            $application->setApplicationReason($request->request->get('applicationReason'));
            $application->setPaymentSchedule($request->request->get('paymentSchedule'));
            
            // Handle File Upload
            $file = $request->files->get('attachment');
            if ($file) {
                $fileName = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
                try {
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/attachments';
                    // Ensure directory exists
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $file->move($uploadDir, $fileName);
                    $application->setAttachment('/uploads/attachments/' . $fileName);
                } catch (\Exception $e) {
                    $application->setAttachment('No Attachment'); 
                }
            } else {
                $previous = $request->request->get('previousAttachment');
                if ($previous) {
                    $application->setAttachment($previous);
                } else {
                    $application->setAttachment($request->request->get('attachment') ?: 'No Attachment');
                }
            }

            // Hardcode automated relations without touching the template:
            $application->setEntrepreneurId($userId);
            $application->setProjectId($startupId);
            $application->setStatus('Pending');
            $application->setSubmissionDate(new \DateTime());
            
            $errors = $validator->validate($application);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst($error->getPropertyPath()) . ': ' . $error->getMessage());
                }
                return $this->redirectToRoute('app_entrepreneur_funding_new', ['startupId' => $startupId]);
            }
            
            $em->persist($application);
            $em->flush();

            // Return cleanly to the Startup portal to watch it reflect
            return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $startupId]);
        }

        // Extract the most historical application mapped specifically to the user for auto-filling logic
        $latestApplication = $em->getRepository(Fundingapplication::class)->findOneBy(
            ['entrepreneurId' => $userId],
            ['id' => 'DESC']
        );

        return $this->render('FrontOffice/fundingapplication/new.html.twig', [
            'startup' => $startup,
            'user' => $em->getRepository(Users::class)->find($userId),
            'latestApplication' => $latestApplication
        ]);
    }

    #[Route('/entrepreneur/funding/{id}/predict', name: 'app_entrepreneur_funding_predict', methods: ['POST'])]
    public function predict(Request $request, EntityManagerInterface $em, int $id): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        
        if (strtoupper($userRole) !== 'ENTREPRENEUR') {
            return new JsonResponse(['error' => 'Access Denied: This area is reserved for Entrepreneurs.'], Response::HTTP_FORBIDDEN);
        }
        
        $application = $em->getRepository(Fundingapplication::class)->find($id);
        
        if (!$application) {
            return new JsonResponse(['error' => 'Funding application not found'], Response::HTTP_NOT_FOUND);
        }

        $startup = $em->getRepository(Startup::class)->find($application->getProjectId());
        
        if (!$startup || $startup->getUserId() !== $userId) {
            return new JsonResponse(['error' => 'Unauthorized or startup not found'], Response::HTTP_UNAUTHORIZED);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $pythonScript = $projectDir . '/train_funding_model.py';

        $schedule = $application->getPaymentSchedule() ?: '';
        $amount = $application->getAmount() ?: 0;

        $pythonExe = DIRECTORY_SEPARATOR === '\\' ? $projectDir . '\.venv\Scripts\python.exe' : $projectDir . '/.venv/bin/python';
        if (!file_exists($pythonExe)) {
            $pythonExe = DIRECTORY_SEPARATOR === '\\' ? 'python' : 'python3';
        }
        $process = new Process([$pythonExe, $pythonScript, 'predict', $schedule, $amount]);
        $process->setTimeout(60);

        try {
            $process->mustRun();
            $output = $process->getOutput();
            $data = json_decode(trim($output), true);
            
            if (!$data && trim($output) !== '') {
                return new JsonResponse(['error' => 'Invalid JSON from prediction script.', 'raw' => $output], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            if (isset($data['error'])) {
                return new JsonResponse($data, Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            return new JsonResponse($data);
        } catch (ProcessFailedException $exception) {
            $errorOutput = $exception->getProcess()->getErrorOutput();
            return new JsonResponse(['error' => 'Error running prediction script.', 'raw' => $errorOutput], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
