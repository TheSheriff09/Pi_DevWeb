<?php

namespace App\Controller;

use App\Repository\UserActivityLogsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ActivityApiController extends AbstractController
{
    public function __construct(
        private UserActivityLogsRepository $activityRepo
    ) {
    }

    #[Route('/user-activity/{user_id}', name: 'api_user_activity', methods: ['GET'])]
    public function getUserActivity(int $user_id): JsonResponse
    {
        $activities = $this->activityRepo->findActivitiesByUser($user_id);
        
        $data = array_map(function($log) {
            return [
                'id' => $log->getId(),
                'action_type' => $log->getActionType(),
                'page' => $log->getPage(),
                'description' => $log->getDescription(),
                'status' => $log->getStatus(),
                'ip_address' => $log->getIpAddress(),
                'created_at' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ];
        }, $activities);

        return $this->json($data);
    }

    #[Route('/activity/recent', name: 'api_recent_activity', methods: ['GET'])]
    public function getRecentActivity(): JsonResponse
    {
        $activities = $this->activityRepo->findRecentActivities();

        $data = array_map(function($log) {
            return [
                'id' => $log->getId(),
                'user_id' => $log->getUser()?->getId(),
                'action_type' => $log->getActionType(),
                'page' => $log->getPage(),
                'status' => $log->getStatus(),
                'created_at' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ];
        }, $activities);

        return $this->json($data);
    }

    #[Route('/activity/security-alerts', name: 'api_security_alerts', methods: ['GET'])]
    public function getSecurityAlerts(): JsonResponse
    {
        $alerts = $this->activityRepo->findSecurityAlerts();

        $data = array_map(function($log) {
            return [
                'id' => $log->getId(),
                'user_id' => $log->getUser()?->getId(),
                'action_type' => $log->getActionType(),
                'description' => $log->getDescription(),
                'ip_address' => $log->getIpAddress(),
                'status' => $log->getStatus(),
                'created_at' => $log->getCreatedAt()->format(\DateTimeInterface::ATOM)
            ];
        }, $alerts);

        return $this->json($data);
    }

    #[Route('/activity/analytics', name: 'api_activity_analytics', methods: ['GET'])]
    public function getAnalytics(): JsonResponse
    {
        $mostActiveUsers = $this->activityRepo->getMostActiveUsers(5);
        $mostVisitedPages = $this->activityRepo->getMostVisitedPages(5);

        return $this->json([
            'most_active_users' => $mostActiveUsers,
            'most_visited_pages' => $mostVisitedPages
        ]);
    }
}
