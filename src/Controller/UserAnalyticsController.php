<?php

namespace App\Controller;

use App\Repository\UserActivityLogsRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\BarChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserAnalyticsController extends AbstractController
{
    private function isAdmin(Request $request): bool
    {
        $role = $request->getSession()->get('user_role');
        return $role && strtoupper($role) === 'ADMIN';
    }

    #[Route('/admin/users/charts', name: 'app_admin_users_charts')]
    public function index(Request $request, UserActivityLogsRepository $repo): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $days = $request->query->getInt('days', 30);
        $fontColor = '#a09cbb';
        $gridLineColor = 'rgba(255,255,255,0.1)';

        // 1. Logins per Day (Line Chart)
        $loginsData = [['Date', 'Logins']];
        foreach ($repo->getLoginsPerDay($days) as $row) {
            $loginsData[] = [$row['logDate'], (int)$row['loginCount']];
        }
        if (count($loginsData) === 1) {
            $loginsData[] = [date('Y-m-d'), 0]; // fallback if no data
        }
        
        $lineChart = new LineChart();
        $lineChart->getData()->setArrayToDataTable($loginsData);
        $lineChart->getOptions()->setTitle('Logins per Day');
        $lineChart->getOptions()->getTitleTextStyle()->setColor($fontColor);
        $lineChart->getOptions()->setBackgroundColor('transparent');
        $lineChart->getOptions()->getLegend()->getTextStyle()->setColor($fontColor);
        $lineChart->getOptions()->getHAxis()->getTextStyle()->setColor($fontColor);
        $lineChart->getOptions()->getVAxis()->getTextStyle()->setColor($fontColor);
        $lineChart->getOptions()->getVAxis()->getGridlines()->setColor($gridLineColor);
        $lineChart->getOptions()->setColors(['#4facfe']);
        $lineChart->getOptions()->setCurveType('function'); // Smooth lines

        $lineChart->getOptions()->getChartArea()->setWidth('85%');
        $lineChart->getOptions()->getChartArea()->setHeight('75%');

        // 2. Most Visited Pages (Bar Chart)
        $pagesData = [['Page', 'Visits']];
        foreach ($repo->getMostVisitedPages(10) as $row) {
            $pagesData[] = [$row['pageUrl'], (int)$row['visitCount']];
        }
        if (count($pagesData) === 1) {
            $pagesData[] = ['No Data', 0];
        }

        $barChart = new BarChart();
        $barChart->getData()->setArrayToDataTable($pagesData);
        $barChart->getOptions()->setTitle('Top 10 Most Visited Pages');
        $barChart->getOptions()->getTitleTextStyle()->setColor($fontColor);
        $barChart->getOptions()->setBackgroundColor('transparent');
        $barChart->getOptions()->getLegend()->getTextStyle()->setColor($fontColor);
        $barChart->getOptions()->getHAxis()->getTextStyle()->setColor($fontColor);
        $barChart->getOptions()->getVAxis()->getTextStyle()->setColor($fontColor);
        $barChart->getOptions()->getHAxis()->getGridlines()->setColor($gridLineColor);
        $barChart->getOptions()->setColors(['#00f2fe']);

        $barChart->getOptions()->getChartArea()->setWidth('85%');
        $barChart->getOptions()->getChartArea()->setHeight('75%');

        // 3. Actions Distribution (Pie Chart)
        $actionsData = [['Action', 'Count']];
        foreach ($repo->getActionsDistribution($days) as $row) {
            $actionsData[] = [$row['actionType'], (int)$row['actionCount']];
        }
        if (count($actionsData) === 1) {
            $actionsData[] = ['No Data', 1];
        }

        $pieChart = new PieChart();
        $pieChart->getData()->setArrayToDataTable($actionsData);
        $pieChart->getOptions()->setTitle('Distribution of Actions (' . $days . ' days)');
        $pieChart->getOptions()->getTitleTextStyle()->setColor($fontColor);
        $pieChart->getOptions()->setBackgroundColor('transparent');
        $pieChart->getOptions()->getLegend()->getTextStyle()->setColor($fontColor);
        $pieChart->getOptions()->getLegend()->setPosition('right');
        $pieChart->getOptions()->setColors(['#4facfe', '#00f2fe', '#f77062', '#b58bff', '#00ff87']);
        $pieChart->getOptions()->setPieHole(0.4);

        $pieChart->getOptions()->getChartArea()->setWidth('85%');
        $pieChart->getOptions()->getChartArea()->setHeight('75%');

        // 4. Top Active Users (Bar Chart)
        $usersData = [['User ID', 'Actions']];
        foreach ($repo->getMostActiveUsers(10) as $row) {
            $usersData[] = ['User #'.$row['userId'], (int)$row['activityCount']];
        }
        if (count($usersData) === 1) {
            $usersData[] = ['No Data', 0];
        }

        $usersChart = new BarChart();
        $usersChart->getData()->setArrayToDataTable($usersData);
        $usersChart->getOptions()->setTitle('Top 10 Active Users');
        $usersChart->getOptions()->getTitleTextStyle()->setColor($fontColor);
        $usersChart->getOptions()->setBackgroundColor('transparent');
        $usersChart->getOptions()->getLegend()->getTextStyle()->setColor($fontColor);
        $usersChart->getOptions()->getHAxis()->getTextStyle()->setColor($fontColor);
        $usersChart->getOptions()->getVAxis()->getTextStyle()->setColor($fontColor);
        $usersChart->getOptions()->getHAxis()->getGridlines()->setColor($gridLineColor);
        $usersChart->getOptions()->setColors(['#b58bff']);

        $usersChart->getOptions()->getChartArea()->setWidth('85%');
        $usersChart->getOptions()->getChartArea()->setHeight('75%');

        return $this->render('BackOffice/users/charts.html.twig', [
            'days' => $days,
            'lineChart' => $lineChart,
            'barChart' => $barChart,
            'pieChart' => $pieChart,
            'usersChart' => $usersChart,
        ]);
    }
}
