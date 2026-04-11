<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Session;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/mentorship')]
class AdminMentorshipController extends AbstractController
{
    #[Route('/dashboard', name: 'app_admin_mentorship')]
    public function dashboard(Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        // Stats
        $usersRepo = $em->getRepository(Users::class);
        $totalMentors = $usersRepo->count(['role' => 'MENTOR']);
        $totalEntrepreneurs = $usersRepo->count(['role' => 'ENTREPRENEUR']);

        $totalSessions = $em->getRepository(Session::class)->count([]);
        $totalBookings = $em->getRepository(Booking::class)->count([]);

        // Latest Bookings query
        $qb = $em->createQueryBuilder()
            ->select('b')
            ->from(Booking::class, 'b')
            ->orderBy('b.creationDate', 'DESC')
            ->setMaxResults(5);
        $latestBookings = $qb->getQuery()->getResult();

        return $this->render('BackOffice/mentorship/dashboard.html.twig', [
            'current_menu' => 'dashboard',
            'totalMentors' => $totalMentors,
            'totalBookings' => $totalBookings,
            'totalSessions' => $totalSessions,
            'latestBookings' => $latestBookings
        ]);
    }

    #[Route('/analytics', name: 'app_admin_mentorship_analytics')]
    public function analytics(Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        // Just basic data rendering for analytics chart/view
        return $this->render('BackOffice/mentorship/analytics.html.twig', [
            
        ]);
    }

    #[Route('/mentors', name: 'app_admin_mentorship_mentors')]
    public function mentors(Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $search = $request->query->get('search', '');
        
        $qb = $em->getRepository(Users::class)->createQueryBuilder('u')
                 ->where('u.role = :role')
                 ->setParameter('role', 'MENTOR')
                 ->orderBy('u.id', 'DESC');

        if ($search) {
            $qb->andWhere('u.fullName LIKE :search OR u.email LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $mentorsRaw = $qb->getQuery()->getResult();
        $mentors = [];

        foreach ($mentorsRaw as $m) {
            $sessionsCount = $em->getRepository(Session::class)->count(['mentorID' => $m->getId(), 'status' => 'completed']);
            $evaluations = $em->getRepository(\App\Entity\MentorEvaluations::class)->findBy(['mentorID' => $m->getId()]);
            
            $totalScore = 0;
            foreach ($evaluations as $e) { 
                $totalScore += $e->getRating(); 
            }
            $avgRating = count($evaluations) > 0 ? round($totalScore / count($evaluations), 1) : 0;

            $mentors[] = [
                'user' => $m,
                'sessionsCount' => $sessionsCount,
                'rating' => $avgRating
            ];
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/mentorship/_mentors_tbody.html.twig', [
                'mentors' => $mentors
            ]);
        }

        return $this->render('BackOffice/mentorship/mentors.html.twig', [
            'current_menu' => 'mentors',
            'mentors' => $mentors
        ]);
    }

    #[Route('/bookings', name: 'app_admin_mentorship_bookings')]
    public function bookings(Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $bookingId = $request->request->get('booking_id');
            $action = $request->request->get('action');

            if ($bookingId && $action) {
                $booking = $em->getRepository(Booking::class)->find($bookingId);
                if ($booking && strtoupper($booking->getStatus()) === 'PENDING') {
                    if ($action === 'accept') {
                        $booking->setStatus('approved');
                    } elseif ($action === 'reject') {
                        $booking->setStatus('rejected');
                    }
                    $em->flush();
                }
            }
            return $this->redirectToRoute('app_admin_mentorship_bookings');
        }

        $bookingsRaw = $em->getRepository(Booking::class)->findBy([], ['creationDate' => 'DESC']);
        $bookings = [];

        foreach ($bookingsRaw as $b) {
            $mentor = $em->getRepository(Users::class)->find($b->getMentorID());
            $entrepreneur = $em->getRepository(Users::class)->find($b->getEntrepreneurID());
            
            $bookings[] = [
                'booking' => $b,
                'mentorName' => $mentor ? $mentor->getFullName() : 'Unknown',
                'entrepreneurName' => $entrepreneur ? $entrepreneur->getFullName() : 'Unknown'
            ];
        }

        return $this->render('BackOffice/mentorship/bookings.html.twig', [
            'current_menu' => 'bookings',
            'bookings' => $bookings
        ]);
    }

    #[Route('/sessions', name: 'app_admin_mentorship_sessions')]
    public function sessions(Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        $sessionsRaw = $em->getRepository(Session::class)->findBy([], ['sessionDate' => 'DESC']);
        $sessionsData = [];

        foreach ($sessionsRaw as $s) {
            $mentor = $em->getRepository(Users::class)->find($s->getMentorID());
            $entrepreneur = $em->getRepository(Users::class)->find($s->getEntrepreneurID());
            $todosCount = $em->getRepository(\App\Entity\SessionTodos::class)->count(['sessionID' => $s->getSessionID()]);
            $notesCount = $em->getRepository(\App\Entity\SessionNotes::class)->count(['sessionID' => $s->getSessionID()]);
            
            $sessionsData[] = [
                'session' => $s,
                'mentorName' => $mentor ? $mentor->getFullName() : 'Unknown',
                'entrepreneurName' => $entrepreneur ? $entrepreneur->getFullName() : 'Unknown',
                'todos' => $todosCount,
                'notes' => $notesCount
            ];
        }

        return $this->render('BackOffice/mentorship/sessions.html.twig', [
            'current_menu' => 'sessions',
            'sessions' => $sessionsData
        ]);
    }

    #[Route('/feedback', name: 'app_admin_mentorship_feedback')]
    public function feedback(Request $request, EntityManagerInterface $em): Response
    {
        $role = $request->getSession()->get('user_role');
        if ($role !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $evalId = $request->request->get('evaluation_id');
            if ($evalId) {
                $eval = $em->getRepository(\App\Entity\MentorEvaluations::class)->find($evalId);
                if ($eval) {
                    $em->remove($eval);
                    $em->flush();
                }
            }
            return $this->redirectToRoute('app_admin_mentorship_feedback');
        }

        $evalsRaw = $em->getRepository(\App\Entity\MentorEvaluations::class)->findBy([], ['createdAt' => 'DESC']);
        $feedbacks = [];

        foreach ($evalsRaw as $e) {
            $mentor = $em->getRepository(Users::class)->find($e->getMentorID());
            $entrepreneur = $em->getRepository(Users::class)->find($e->getEntrepreneurID());
            $session = $em->getRepository(Session::class)->find($e->getSessionID());
            
            $feedbacks[] = [
                'evaluation' => $e,
                'mentorName' => $mentor ? $mentor->getFullName() : 'Unknown',
                'entrepreneurName' => $entrepreneur ? $entrepreneur->getFullName() : 'Unknown',
                'sessionDate' => $session ? $session->getSessionDate() : null
            ];
        }

        return $this->render('BackOffice/mentorship/feedback.html.twig', [
            'current_menu' => 'feedback',
            'feedbacks' => $feedbacks
        ]);
    }
}
