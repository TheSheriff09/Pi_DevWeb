<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\SessionTodos;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mentorship')]
class MentorshipDashboardController extends AbstractController
{
    #[Route('', name: 'app_mentorship_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $sessionRepo = $em->getRepository(Session::class);
        $todoRepo = $em->getRepository(SessionTodos::class);
        
        if ($role === 'ENTREPRENEUR') {
            $sessions = $sessionRepo->findBy(['entrepreneurID' => $userId]);
        } elseif ($role === 'MENTOR') {
            $sessions = $sessionRepo->findBy(['mentorID' => $userId]);
        } else {
            return $this->redirectToRoute('app_admin_mentorship');
        }

        $sessionIds = array_map(fn($s) => $s->getSessionID(), $sessions);
        $todos = [];
        if (!empty($sessionIds)) {
            $qb = $em->createQueryBuilder()
                ->select('t')
                ->from(SessionTodos::class, 't')
                ->where('t.sessionID IN (:ids)')
                ->setParameter('ids', $sessionIds);
            $todos = $qb->getQuery()->getResult();
        }

        $totalTodos = count($todos);
        $completedTodos = count(array_filter($todos, fn($t) => $t->getIsDone()));
        $progressPercentage = $totalTodos > 0 ? round(($completedTodos / $totalTodos) * 100) : 0;

        $groupedTodos = [];
        $userRepo = $em->getRepository(\App\Entity\Users::class);
        if ($role === 'MENTOR') {
            foreach ($todos as $t) {
                // Find matching session without losing scope
                $sessArr = array_filter($sessions, fn($s) => $s->getSessionID() === $t->getSessionID());
                $sess = reset($sessArr);
                if ($sess) {
                    $entId = $sess->getEntrepreneurID();
                    if (!isset($groupedTodos[$entId])) {
                        $entUser = $userRepo->find($entId);
                        $groupedTodos[$entId] = [
                            'name' => $entUser ? $entUser->getFullName() : 'Unknown Entrepreneur',
                            'tasks' => []
                        ];
                    }
                    $groupedTodos[$entId]['tasks'][] = [
                        'todo' => $t,
                        'sessionDate' => $sess->getSessionDate()
                    ];
                }
            }
        }

        // --- Upcoming Sessions Logic ---
        $now = new \DateTime();
        $upcomingSessions = array_filter($sessions, function($s) use ($now) {
            return $s->getSessionDate()->format('Y-m-d') >= $now->format('Y-m-d');
        });
        
        // Sort by closest date
        usort($upcomingSessions, fn($a, $b) => $a->getSessionDate() <=> $b->getSessionDate());
        $upcomingSessions = array_slice($upcomingSessions, 0, 4);

        $upcomingSessionsData = [];
        foreach ($upcomingSessions as $s) {
            $otherId = ($role === 'MENTOR') ? $s->getEntrepreneurID() : $s->getMentorID();
            $otherUser = $userRepo->find($otherId);
            $upcomingSessionsData[] = [
                'session' => $s,
                'partnerName' => $otherUser ? $otherUser->getFullName() : 'Unknown',
                'isToday' => $s->getSessionDate()->format('Y-m-d') === $now->format('Y-m-d')
            ];
        }

        return $this->render('FrontOffice/mentorship/index.html.twig', [
            'totalSessions' => count($sessions),
            'totalTodos' => $totalTodos,
            'completedTodos' => $completedTodos,
            'progressPercentage' => $progressPercentage,
            'recentTodos' => array_slice(array_reverse($todos), 0, 5),
            'groupedTodos' => $groupedTodos,
            'upcomingSessions' => $upcomingSessionsData,
            'role' => $role
        ]);
    }
}
