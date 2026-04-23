<?php

namespace App\Controller;

use App\Repository\UserActivityLogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_panel')]
    public function index(Request $request): Response
    {
        // Simple security check based on manual session
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('BackOffice/admin/panel.html.twig');
    }

    #[Route('/admin/users/activity', name: 'app_admin_activity')]
    public function activity(Request $request, UserActivityLogsRepository $activityRepo): Response
    {
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $recentActivities = $activityRepo->findRecentActivities(100);
        $mostActiveUsers = $activityRepo->getMostActiveUsers(5);
        $mostVisitedPages = $activityRepo->getMostVisitedPages(5);
        $securityAlerts = $activityRepo->findSecurityAlerts(20);

        return $this->render('BackOffice/admin/activity.html.twig', [
            'recentActivities' => $recentActivities,
            'mostActiveUsers' => $mostActiveUsers,
            'mostVisitedPages' => $mostVisitedPages,
            'securityAlerts' => $securityAlerts,
            'current_module' => 'users',
            'current_menu' => 'activity'
        ]);
    }
    #[Route('/admin/users/activity/export/pdf', name: 'app_admin_activity_export_pdf')]
    public function exportPdf(Request $request, UserActivityLogsRepository $activityRepo): Response
    {
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $activities = $activityRepo->findRecentActivities(500);

        $html = $this->renderView('BackOffice/admin/activity_pdf.html.twig', [
            'logs' => $activities
        ]);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="activity_report.pdf"',
            ]
        );
    }

    #[Route('/admin/users/activity/export/excel', name: 'app_admin_activity_export_excel')]
    public function exportCSV(Request $request, UserActivityLogsRepository $activityRepo): Response
    {
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $activities = $activityRepo->findRecentActivities(1000);

        $fp = fopen('php://temp', 'w');
        fputcsv($fp, ['Date & Time', 'User ID', 'Action', 'Page', 'Description', 'Status']);

        foreach ($activities as $log) {
            fputcsv($fp, [
                $log->getCreatedAt()->format('Y-m-d H:i:s'),
                $log->getUser() ? $log->getUser()->getId() : 'Guest',
                $log->getActionType(),
                $log->getPage() ?? '',
                $log->getDescription() ?? '',
                $log->getStatus()
            ]);
        }

        rewind($fp);
        $csvContent = stream_get_contents($fp);
        fclose($fp);

        return new Response(
            $csvContent,
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="activity_report.csv"',
            ]
        );
    }
}
