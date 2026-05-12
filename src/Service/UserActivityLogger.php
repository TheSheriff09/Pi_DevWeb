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

        // Ensure Admin users are NEVER tracked
        if ($user !== null && strtoupper($user->getRole()) === 'ADMIN') {
            return;
        }

        $log = new UserActivityLogs();
        $log->setActionType($actionType);
        $log->setDescription($description);
        $log->setStatus($status);

        if ($user !== null) {
            $log->setUser($user);
        }

        $log->setPage($request->getPathInfo());
        $log->setIpAddress($request->getClientIp());
        $log->setUserAgent($request->headers->get('User-Agent'));

        if ($request->hasSession()) {
            $session = $request->getSession();
            if ($session->isStarted()) {
                $log->setSessionId($session->getId());
            }
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function logWithoutRequestContext(
        string $actionType,
        ?string $description = null,
        ?string $status = 'SUCCESS',
        ?Users $user = null
    ): void {
        // Ensure Admin users are NEVER tracked
        if ($user !== null && strtoupper($user->getRole()) === 'ADMIN') {
            return;
        }

        $log = new UserActivityLogs();
        $log->setActionType($actionType);
        $log->setDescription($description);
        $log->setStatus($status);

        if ($user !== null) {
            $log->setUser($user);
        }

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }
}
