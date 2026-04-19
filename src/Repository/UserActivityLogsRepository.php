<?php

namespace App\Repository;

use App\Entity\UserActivityLogs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserActivityLogs>
 */
class UserActivityLogsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActivityLogs::class);
    }

    public function findRecentActivities(int $limit = 50): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.user', 'usr')
            ->where('u.page NOT LIKE :adminPath OR u.page IS NULL')
            ->andWhere('u.description NOT LIKE :adminDesc OR u.description IS NULL')
            ->andWhere('usr.role != :adminRole OR usr.id IS NULL')
            ->setParameter('adminPath', '%/admin%')
            ->setParameter('adminDesc', '%admin%')
            ->setParameter('adminRole', 'ADMIN')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findActivitiesByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('IDENTITY(u.user) = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findSecurityAlerts(int $limit = 50): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.user', 'usr')
            ->where('u.status = :status')
            ->andWhere('u.page NOT LIKE :adminPath OR u.page IS NULL')
            ->andWhere('usr.role != :adminRole OR usr.id IS NULL')
            ->setParameter('status', 'FAILED')
            ->setParameter('adminPath', '%/admin%')
            ->setParameter('adminRole', 'ADMIN')
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMostActiveUsers(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->select('IDENTITY(u.user) as userId, COUNT(u.id) as activityCount')
            ->leftJoin('u.user', 'usr')
            ->where('u.user IS NOT NULL')
            ->andWhere('usr.role != :adminRole')
            ->setParameter('adminRole', 'ADMIN')
            ->groupBy('u.user')
            ->orderBy('activityCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function getMostVisitedPages(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.page as pageUrl, COUNT(u.id) as visitCount')
            ->where('u.actionType = :action')
            ->andWhere('u.page NOT LIKE :adminPath')
            ->setParameter('action', 'VIEW_PAGE')
            ->setParameter('adminPath', '%/admin%')
            ->groupBy('u.page')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
    public function getLoginsPerDay(int $days = 30): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('u')
            ->select('SUBSTRING(u.createdAt, 1, 10) as logDate, COUNT(u.id) as loginCount')
            ->where('u.actionType LIKE :action')
            ->andWhere('u.createdAt >= :date')
            ->setParameter('action', '%LOGIN%')
            ->setParameter('date', $date)
            ->groupBy('logDate')
            ->orderBy('logDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getActionsDistribution(int $days = 30): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('u')
            ->select('u.actionType, COUNT(u.id) as actionCount')
            ->where('u.createdAt >= :date')
            ->setParameter('date', $date)
            ->groupBy('u.actionType')
            ->orderBy('actionCount', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
