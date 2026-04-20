<?php

namespace App\Controller;

use App\Entity\Fundingapplication;
use App\Entity\Fundingevaluation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CounterfactualSimulatorController extends AbstractController
{
    #[Route('/startup/application/{id}/simulate', name: 'app_counterfactual_simulate')]
    public function simulate(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId || strtoupper($userRole) !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }

        $application = $em->getRepository(Fundingapplication::class)->find($id);

        if (!$application) {
            return $this->redirectToRoute('app_startup_index');
        }

        // Extremely secure boundary: must own the application logically.
        if ((int)$application->getEntrepreneurId() !== (int)$userId) {
            $this->addFlash('error', 'Access denied.');
            return $this->redirectToRoute('app_startup_index');
        }

        if ($application->getStatus() !== 'Rejected') {
            $this->addFlash('error', 'Simulation is exclusively reserved for mapping rejected environments.');
            return $this->redirectToRoute('app_entrepreneur_startup_show', ['id' => $application->getProjectId()]);
        }

        $evaluation = $em->getRepository(Fundingevaluation::class)->findOneBy(['fundingApplicationId' => $id]);

        return $this->render('FrontOffice/simulator/index.html.twig', [
            'application' => $application,
            'evaluation' => $evaluation
        ]);
    }
}
