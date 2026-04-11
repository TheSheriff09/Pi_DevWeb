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

class FundingApplicationController extends AbstractController
{
    #[Route('/entrepreneur/startups/{startupId}/funding/new', name: 'app_entrepreneur_funding_new')]
    public function new(Request $request, EntityManagerInterface $em, int $startupId): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        // Block access to non-entrepreneurs seamlessly
        if (!$userId || strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
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
            $application->setAttachment($request->request->get('attachment') ?: 'No Attachment');

            // Hardcode automated relations without touching the template:
            $application->setEntrepreneurId($userId);
            $application->setProjectId($startupId);
            $application->setStatus('Pending');
            $application->setSubmissionDate(new \DateTime());
            
            $em->persist($application);
            $em->flush();

            // Return cleanly to the Startup portal to watch it reflect
            return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $startupId]);
        }

        return $this->render('FrontOffice/fundingapplication/new.html.twig', [
            'startup' => $startup,
            'user' => $em->getRepository(Users::class)->find($userId)
        ]);
    }
}
