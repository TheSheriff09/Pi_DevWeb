<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Schedule;
use App\Entity\Session;
use App\Entity\Startup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/mentorship')]
class BookingController extends AbstractController
{
    #[Route('/book/{mentorId}/{scheduleId}', name: 'app_mentorship_book')]
    public function bookSession(int $mentorId, int $scheduleId, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper((string) $userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Mentorship features.');
            return $this->redirectToRoute('app_home');
        }

        $schedule = $em->getRepository(Schedule::class)->find($scheduleId);
        if (!$schedule || $schedule->getMentorID() !== $mentorId || $schedule->getIsBooked()) {
            // Already booked or invalid
            return $this->redirectToRoute('app_mentor_profile', ['id' => $mentorId]);
        }

        // Check double booking
        $existing = $em->getRepository(Booking::class)->findOneBy([
            'entrepreneurID' => $userId,
            'mentorID' => $mentorId,
            'requestedDate' => $schedule->getAvailableDate(),
            'requestedTime' => $schedule->getStartTime(),
            'status' => 'PENDING'
        ]);

        if ($existing) {
            return $this->render('FrontOffice/mentorship/book_error.html.twig', [
                'error' => 'You already requested a booking for this specific time slot.'
            ]);
        }

        if ($request->isMethod('POST')) {
            $topic = (string) $request->request->get('topic', '');
                
            $startup = $em->getRepository(Startup::class)->findOneBy(['founderID' => $userId]);
            if (!$startup) {
                $startup = $em->getRepository(Startup::class)->findOneBy(['userId' => $userId]);
            }
            $startupId = $startup ? (int) $startup->getStartupID() : 0;

            $maxId = $em->createQueryBuilder()
                ->select('MAX(b.bookingID)')
                ->from(Booking::class, 'b')
                ->getQuery()
                ->getSingleScalarResult();

            $booking = new Booking();
            $booking->setBookingID((int) ($maxId ?? 0) + 1);
            $booking->setEntrepreneurID($userId);
            $booking->setMentorID($mentorId);
            $booking->setStartupID($startupId);
            $booking->setRequestedDate($schedule->getAvailableDate());
            $booking->setRequestedTime($schedule->getStartTime());
            $booking->setTopic($topic);
            $booking->setStatus('PENDING');
            $booking->setCreationDate(new \DateTime());
                
            $errors = $validator->validate($booking);
            if (count($errors) > 0) {
                $errorMessage = (string) $errors->get(0)->getMessage();
                $this->addFlash('error', 'Booking validation failed: ' . $errorMessage);
                return $this->redirectToRoute('app_mentorship_book', ['mentorId' => $mentorId, 'scheduleId' => $scheduleId]);
            }
                
            try {
                $em->persist($booking);
                $em->flush();
                return $this->redirectToRoute('app_my_bookings');
            } catch (\Exception $e) {
                dd('Booking creation failed: ' . $e->getMessage() . ' - StackTrace: ' . $e->getTraceAsString());
            }
        }

        return $this->render('FrontOffice/mentorship/book.html.twig', [
            'schedule' => $schedule,
            'mentorId' => $mentorId
        ]);
    }

    #[Route('/bookings', name: 'app_my_bookings')]
    public function myBookings(Request $request, EntityManagerInterface $em): Response
    {
        $userId = (int) $request->getSession()->get('user_id');
        $userRole = (string) $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Mentorship features.');
            return $this->redirectToRoute('app_home');
        }

        $role = $userRole;

        if ($role === 'MENTOR') {
            $bookingsRaw = $em->getRepository(Booking::class)->findBy(['mentorID' => $userId], ['creationDate' => 'DESC']);
        } else {
            $bookingsRaw = $em->getRepository(Booking::class)->findBy(['entrepreneurID' => $userId], ['creationDate' => 'DESC']);
        }

        $bookings = [];
        foreach ($bookingsRaw as $b) {
            $otherId = $role === 'MENTOR' ? (int) $b->getEntrepreneurID() : (int) $b->getMentorID();
            $otherUser = $em->getRepository(\App\Entity\Users::class)->find($otherId);
            $bookings[] = [
                'booking' => $b,
                'counterpartName' => $otherUser ? (string) $otherUser->getFullName() : 'Unknown'
            ];
        }

        return $this->render('FrontOffice/mentorship/bookings.html.twig', [
            'bookings' => $bookings,
            'role' => $role
        ]);
    }

    #[Route('/booking/{id}/handle', name: 'app_handle_booking', methods: ['POST'])]
    public function handleBooking(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = (int) $request->getSession()->get('user_id');
        $userRole = (string) $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['status' => 'error', 'message' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }
        
        if ($userRole !== 'MENTOR') {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $booking = $em->getRepository(Booking::class)->findOneBy(['bookingID' => $id, 'mentorID' => $userId]);
        $status = $booking ? (string) $booking->getStatus() : null;
        if (!$booking || ($status !== null && strtoupper($status) !== 'PENDING')) {
            return $this->json(['status' => 'error', 'message' => 'Invalid booking'], 400);
        }

        $action = (string) $request->request->get('action');
        
        if ($action === 'accept') {
            $booking->setStatus('approved');
            $bookingDate = $booking->getRequestedDate();
            $this->pushNotification((int) $booking->getEntrepreneurID(), 'success', 'Your booking on ' . ($bookingDate ? $bookingDate->format('j M Y') : '') . ' was Approved!');
            
            // Mark corresponding schedule as booked
            $schedule = $em->getRepository(Schedule::class)->findOneBy([
                'mentorID' => $userId,
                'availableDate' => $booking->getRequestedDate(),
                'startTime' => $booking->getRequestedTime(),
                'isBooked' => false
            ]);
            
            $scheduleId = null;
            if ($schedule) {
                $schedule->setIsBooked(true);
                $scheduleId = (int) $schedule->getScheduleID();
            }
            
            $maxSessionId = $em->createQueryBuilder()
                ->select('MAX(s.sessionID)')
                ->from(Session::class, 's')
                ->getQuery()
                ->getSingleScalarResult();

            $session = new Session();
            $session->setSessionID((int) ($maxSessionId ?? 0) + 1);
            $session->setMentorID($userId);
            $session->setEntrepreneurID((int) $booking->getEntrepreneurID());
            $session->setStartupID((int) ($booking->getStartupID() ?? 0));
            if ($scheduleId) {
                $session->setScheduleID($scheduleId);
            }
            $session->setSessionDate($booking->getRequestedDate());
            $session->setSessionType('online');
            $session->setStatus('planned');
            
            $em->persist($session);

        } elseif ($action === 'reject') {
            $booking->setStatus('rejected');
            $bookingDate = $booking->getRequestedDate();
            $this->pushNotification((int) $booking->getEntrepreneurID(), 'error', 'Your booking on ' . ($bookingDate ? $bookingDate->format('j M Y') : '') . ' was Rejected.');
        }

        $em->flush();
        return $this->redirectToRoute('app_my_bookings');
    }

    private function pushNotification(int $userId, string $type, string $msg): void
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $file = (is_string($projectDir) ? $projectDir : '') . '/var/notifications.json';
        $data = [];
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = $content ? (json_decode($content, true) ?? []) : [];
        }
        $data[] = ['userId' => $userId, 'type' => $type, 'msg' => $msg];
        file_put_contents($file, json_encode($data));
    }

    #[Route('/api/poll', name: 'app_mentorship_poll', methods: ['GET'])]
    public function pollNotifications(Request $request): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId || strtoupper((string) $userRole) === 'EVALUATOR' || $userRole !== 'ENTREPRENEUR') {
            return $this->json([]);
        }

        $projectDir = $this->getParameter('kernel.project_dir');
        $file = (is_string($projectDir) ? $projectDir : '') . '/var/notifications.json';
        if (!file_exists($file)) {
            return $this->json([]);
        }

        $content = file_get_contents($file);
        $data = $content ? (json_decode($content, true) ?? []) : [];
        $userNotifs = [];
        $remaining = [];
        
        foreach ($data as $n) {
            if (isset($n['userId']) && $n['userId'] == $userId) {
                $userNotifs[] = ['type' => $n['type'], 'msg' => $n['msg']];
            } else {
                $remaining[] = $n;
            }
        }
        
        if (count($userNotifs) > 0) {
            file_put_contents($file, json_encode($remaining));
        }

        return $this->json($userNotifs);
    }
}
