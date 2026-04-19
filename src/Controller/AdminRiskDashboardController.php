<?php

namespace App\Controller;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\RiskAssessmentService;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Options\PieChart\PieChartOptions;

#[Route('/admin')]
class AdminRiskDashboardController extends AbstractController
{
    #[Route('/risk-dashboard', name: 'app_admin_risk_dashboard')]
    public function index(EntityManagerInterface $em): Response
    {
        $usersRepo = $em->getRepository(Users::class);
        
        $dangerousUsers = $usersRepo->findBy(['riskLevel' => 'DANGEROUS'], ['riskScore' => 'DESC']);
        $allUsers = $usersRepo->findAll();
        
        $normalCount = 0;
        $suspiciousCount = 0;
        $dangerousCount = count($dangerousUsers);
        
        foreach ($allUsers as $u) {
            if ($u->getRiskLevel() === 'NORMAL') $normalCount++;
            if ($u->getRiskLevel() === 'SUSPICIOUS') $suspiciousCount++;
        }
        
        // Build Pie Chart
        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable([
            ['Risk Level', 'Number of Users'],
            ['Normal', $normalCount],
            ['Suspicious', $suspiciousCount],
            ['Dangerous', $dangerousCount]
        ]);
        
        $pieChart->getOptions()->setTitle('User Risk Distribution');
        $pieChart->getOptions()->setHeight(400);
        $pieChart->getOptions()->setWidth(500);
        $pieChart->getOptions()->setBackgroundColor('transparent');
        // Colors mapping: Normal -> Green, Suspicious -> Orange, Dangerous -> Red
        $pieChart->getOptions()->setColors(['#2ecc71', '#f39c12', '#e74c3c']);
        $pieChart->getOptions()->getLegend()->getTextStyle()->setColor('#888');
        $pieChart->getOptions()->getLegend()->setPosition('bottom');
        $pieChart->getOptions()->getTitleTextStyle()->setColor('#888');
        
        return $this->render('BackOffice/users/risk_dashboard.html.twig', [
            'dangerous_users' => $dangerousUsers,
            'pie_chart' => $pieChart,
            'total_users' => count($allUsers)
        ]);
    }
    
    #[Route('/risk-dashboard/recalculate-all', name: 'app_admin_risk_recalculate')]
    public function recalculateAll(EntityManagerInterface $em, RiskAssessmentService $riskService): Response
    {
        $users = $em->getRepository(Users::class)->findAll();
        
        $assessedCount = 0;
        foreach ($users as $user) {
            // Recalculate risk score purely using ML engine
            $riskService->assessUserRisk($user);
            $assessedCount++;
        }
        
        $this->addFlash('success', 'AI Recalculated Risk Scores for ' . $assessedCount . ' users.');
        
        return $this->redirectToRoute('app_admin_risk_dashboard');
    }
}
