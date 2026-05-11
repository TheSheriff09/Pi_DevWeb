<?php

namespace App\Service;

use App\Entity\UserActivityLogs;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class UserActivityLogger
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
    }

    public function log(
        string $actionType,
        ?string $description = null,
        ?string $status = 'SUCCESS',
        ?Users $user = null
    ): void {
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            $this->logFromRequest($request, $actionType, $description, $status, $user);
            return;
        }

        $this->logWithoutRequestContext($actionType, $description, $status, $user);
    }

    public function logFromRequest(
        Request $request,
        string $actionType,
        ?string $description = null,
        ?string $status = 'SUCCESS',
        ?Users $user = null
    ): void {
        // Prevent logging any activity on the BackOffice (Admin Panel)
        if (str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        if ($user === null && $request->hasSession()) {
            $session = $request->getSession();
            if ($session->isStarted() && $session->has('user_id')) {
                $userId = $session->get('user_id');
                $currentUser = $this->entityManager->getRepository(Users::class)->find($userId);
                if ($currentUser instanceof Users) {
                    $user = $currentUser;
                }
            }
        }

        if ($user !== null && strtoupper($user->getRole() ?? '') === 'ADMIN') {
            return;
        }

        $conn = $this->entityManager->getConnection();
        $sql = 'INSERT INTO user_activity_logs (user_id, action_type, description, status, page, ip_address, user_agent, session_id, created_at) VALUES (:u, :a, :d, :st, :p, :ip, :ua, :sess, :ct)';
        
        $sessId = null;
        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $sessId = $request->getSession()->getId();
        }

        try {
            $conn->executeStatement($sql, [
                'u' => $user ? $user->getId() : null,
                'a' => $actionType,
                'd' => $description,
                'st' => $status ?? 'SUCCESS',
                'p' => $request->getPathInfo(),
                'ip' => $request->getClientIp(),
                'ua' => $request->headers->get('User-Agent'),
                'sess' => $sessId,
                'ct' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silently ignore log insertion failures
        }
    }

    private function logWithoutRequestContext(
        string $actionType,
        ?string $description = null,
        ?string $status = 'SUCCESS',
        ?Users $user = null
    ): void {
        if ($user !== null && strtoupper($user->getRole() ?? '') === 'ADMIN') {
            return;
        }

        $conn = $this->entityManager->getConnection();
        $sql = 'INSERT INTO user_activity_logs (user_id, action_type, description, status, created_at) VALUES (:u, :a, :d, :st, :ct)';
        
        try {
            $conn->executeStatement($sql, [
                'u' => $user ? $user->getId() : null,
                'a' => $actionType,
                'd' => $description,
                'st' => $status ?? 'SUCCESS',
                'ct' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silently ignore log insertion failures
        }
    }
}
