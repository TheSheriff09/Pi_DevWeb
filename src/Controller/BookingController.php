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
        if (!$userId) {
            return $this->redirectToRoute('app_login');
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
            $topic = $request->request->get('topic', '');
                
            $startup = $em->getRepository(Startup::class)->findOneBy(['founderID' => $userId]);
            if (!$startup) {
                $startup = $em->getRepository(Startup::class)->findOneBy(['userId' => $userId]);
            }
            $startupId = $startup ? $startup->getStartupID() : 0;

            $maxId = $em->createQueryBuilder()
                ->select('MAX(b.bookingID)')
                ->from(Booking::class, 'b')
                ->getQuery()
                ->getSingleScalarResult();

            $booking = new Booking();
            $booking->setBookingID(($maxId ?? 0) + 1);
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
                $this->addFlash('error', 'Booking validation failed: ' . $errors[0]->getMessage());
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
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        if ($role === 'MENTOR') {
            $bookingsRaw = $em->getRepository(Booking::class)->findBy(['mentorID' => $userId], ['creationDate' => 'DESC']);
        } else {
            $bookingsRaw = $em->getRepository(Booking::class)->findBy(['entrepreneurID' => $userId], ['creationDate' => 'DESC']);
        }

        $bookings = [];
        foreach ($bookingsRaw as $b) {
            $otherId = $role === 'MENTOR' ? $b->getEntrepreneurID() : $b->getMentorID();
            $otherUser = $em->getRepository(\App\Entity\Users::class)->find($otherId);
            $bookings[] = [
                'booking' => $b,
                'counterpartName' => $otherUser ? $otherUser->getFullName() : 'Unknown'
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
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        
        if (!$userId || $role !== 'MENTOR') {
            return $this->json(['status' => 'error'], 401);
        }

        $booking = $em->getRepository(Booking::class)->findOneBy(['bookingID' => $id, 'mentorID' => $userId]);
        if (!$booking || strtoupper($booking->getStatus()) !== 'PENDING') {
            return $this->json(['status' => 'error', 'message' => 'Invalid booking'], 400);
        }

        $action = $request->request->get('action'); // 'accept' or 'reject'
        
        if ($action === 'accept') {
            $booking->setStatus('approved');
            $this->pushNotification($booking->getEntrepreneurID(), 'success', 'Your booking on ' . $booking->getRequestedDate()->format('j M Y') . ' was Approved!');
            
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
                $scheduleId = $schedule->getScheduleID();
            }
            
            $maxSessionId = $em->createQueryBuilder()
                ->select('MAX(s.sessionID)')
                ->from(Session::class, 's')
                ->getQuery()
                ->getSingleScalarResult();

            $session = new Session();
            $session->setSessionID(($maxSessionId ?? 0) + 1);
            $session->setMentorID($userId);
            $session->setEntrepreneurID($booking->getEntrepreneurID());
            $session->setStartupID($booking->getStartupID() ?? 0);
            if ($scheduleId) {
                $session->setScheduleID($scheduleId);
            }
            $session->setSessionDate($booking->getRequestedDate());
            $session->setSessionType('online');
            $session->setStatus('planned');
            
            $em->persist($session);

        } elseif ($action === 'reject') {
            $booking->setStatus('rejected');
            $this->pushNotification($booking->getEntrepreneurID(), 'error', 'Your booking on ' . $booking->getRequestedDate()->format('j M Y') . ' was Rejected.');
        }

        $em->flush();
        return $this->redirectToRoute('app_my_bookings');
    }

    private function pushNotification(int $userId, string $type, string $msg): void
    {
        $file = $this->getParameter('kernel.project_dir') . '/var/notifications.json';
        $data = [];
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?? [];
        }
        $data[] = ['userId' => $userId, 'type' => $type, 'msg' => $msg];
        file_put_contents($file, json_encode($data));
    }

    #[Route('/api/poll', name: 'app_mentorship_poll', methods: ['GET'])]
    public function pollNotifications(Request $request): Response
    {
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        if (!$userId || $role !== 'ENTREPRENEUR') {
            return $this->json([]);
        }

        $file = $this->getParameter('kernel.project_dir') . '/var/notifications.json';
        if (!file_exists($file)) {
            return $this->json([]);
        }

        $data = json_decode(file_get_contents($file), true) ?? [];
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
