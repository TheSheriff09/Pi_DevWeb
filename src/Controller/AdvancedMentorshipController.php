<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Schedule;
use App\Entity\Session;
use App\Entity\SessionFeedback;
use App\Entity\SessionNotes;
use App\Entity\SessionTodos;
use App\Entity\Users;
use App\Service\MentorshipMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/mentorship/advanced')]
class AdvancedMentorshipController extends AbstractController
{
    private EntityManagerInterface $em;
    private MentorshipMailerService $mailer;

    public function __construct(EntityManagerInterface $em, MentorshipMailerService $mailer)
    {
        $this->em = $em;
        $this->mailer = $mailer;
    }

    #[Route('/simulate', name: 'app_advanced_simulate')]
    public function simulateTime(Request $request): Response
    {
        // Ensure automatic status updates based on time.
        // Fetch Scheduled sessions
        $conn = $this->em->getConnection();
        $sql = 'SELECT * FROM session WHERE status IN ("Scheduled", "Ongoing", "Requested")';
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $sessionsData = $resultSet->fetchAllAssociative();

        $now = new \DateTime();
        $messages = [];

        foreach ($sessionsData as $data) {
            $session = $this->em->getRepository(Session::class)->find($data['session_id']);
            if (!$session || !$session->getScheduleID()) continue;

            $schedule = $this->em->getRepository(Schedule::class)->find($session->getScheduleID());
            if (!$schedule) continue;

            $availableDate = $schedule->getAvailableDate();
            $startTimeObj = $schedule->getStartTime();
            
            if (!$availableDate || !$startTimeObj) continue;

            $sessionDate = \DateTime::createFromFormat('Y-m-d', $availableDate->format('Y-m-d'));
            $startTime = \DateTime::createFromFormat('H:i:s', $startTimeObj->format('H:i:s'));
            
            // Combine date and time
            $combinedStart = null;
            if ($sessionDate && $startTime) {
                $combinedStart = \DateTime::createFromFormat('Y-m-d H:i:s', $sessionDate->format('Y-m-d') . ' ' . $startTime->format('H:i:s'));
            }
            
            if ($combinedStart && $session->getStatus() === 'Scheduled' && $combinedStart <= $now) {
                // If it is time, set to Ongoing
                $session->setStatus('Ongoing');
                $messages[] = "Session {$session->getSessionID()} marked as Ongoing.";
            } else if ($combinedStart && $session->getStatus() === 'Scheduled' && $combinedStart > $now) {
                // Reminder system: Detect session 1 hour before
                $interval = $combinedStart->getTimestamp() - $now->getTimestamp();
                if ($interval > 0 && $interval <= 3600) {
                    // Send reminder email via Mailer
                    $mentor = $this->em->getRepository(Users::class)->find($session->getMentor()->getId());
                    $entrepreneur = $this->em->getRepository(Users::class)->find($session->getEntrepreneur()->getId());
                    if ($mentor && $entrepreneur) {
                        try {
                           $this->mailer->sendSessionReminderEmail($session, $entrepreneur, $mentor);
                           $messages[] = "Reminder sent for Session {$session->getSessionID()}.";
                        } catch (\Exception $e) { }
                    }
                }
            }
        }

        $this->em->flush();
        $this->addFlash('success', 'Simulation triggered. Logs: ' . implode(' | ', $messages));
        return $this->redirectToRoute('app_advanced_dashboard');
    }

    #[Route('/dashboard', name: 'app_advanced_dashboard')]
    public function dashboard(): Response
    {
        // Dashboard calculating Entrepreneur Progress and Mentor Performance
        
        $conn = $this->em->getConnection();

        // 1. Entrepreneur Progress (Assuming default User mapping: ID 1 for testing)
        // Adjust logic if an entrepreneur logs in. We mock entrepreneurID = 2 for now.
        $entrepreneurID = 2; // Stub
        $sqlTodosTotal = "SELECT COUNT(*) as c FROM session_todos st JOIN session s ON st.session_id = s.session_id WHERE s.entrepreneur_id = :id";
        $sqlTodosDone = "SELECT COUNT(*) as c FROM session_todos st JOIN session s ON st.session_id = s.session_id WHERE s.entrepreneur_id = :id AND st.isDone = 1";
        $sqlSessionsTotal = "SELECT COUNT(*) as c FROM session WHERE entrepreneur_id = :id";
        $sqlSessionsDone = "SELECT COUNT(*) as c FROM session WHERE entrepreneur_id = :id AND status = 'Completed'";

        $todosTotal = $conn->fetchOne($sqlTodosTotal, ['id' => $entrepreneurID]);
        $todosDone = $conn->fetchOne($sqlTodosDone, ['id' => $entrepreneurID]);
        $sessionsTotal = $conn->fetchOne($sqlSessionsTotal, ['id' => $entrepreneurID]);
        $sessionsDone = $conn->fetchOne($sqlSessionsDone, ['id' => $entrepreneurID]);

        $progressPct = ($todosTotal > 0) ? round(($todosDone / $todosTotal) * 100) : 0;
        $progressPctSessions = ($sessionsTotal > 0) ? round(($sessionsDone / $sessionsTotal) * 100) : 0;

        // 2. Mentor Performance calculation & Ranking
        $sqlMentors = "SELECT u.id, u.fullName, u.image_name, AVG(sf.progressScore) as avgScore 
                       FROM users u 
                       LEFT JOIN session_feedback sf ON u.id = sf.mentor_id 
                       WHERE u.role = 'Mentor' 
                       GROUP BY u.id 
                       ORDER BY avgScore DESC";
        $topMentors = $conn->fetchAllAssociative($sqlMentors);

        return $this->render('BackOffice/mentorship/advanced/dashboard.html.twig', [
             'todosTotal' => $todosTotal,
             'todosDone' => $todosDone,
             'sessionsTotal' => $sessionsTotal,
             'sessionsDone' => $sessionsDone,
             'progressTodos' => $progressPct,
             'progressSessions' => $progressPctSessions,
             'topMentors' => $topMentors
        ]);
    }

    #[Route('/workspace/{id}', name: 'app_advanced_workspace')]
    public function sessionWorkspace(int $id, Request $request): Response
    {
        $session = $this->em->getRepository(Session::class)->find($id);
        if (!$session) {
            throw $this->createNotFoundException('Session not found');
        }

        $mentor = $this->em->getRepository(Users::class)->find($session->getMentor()->getId());
        $entrepreneur = $this->em->getRepository(Users::class)->find($session->getEntrepreneur()->getId());

        // Fetch Notes, Todos, Feedback manually since no associations
        $notes = $this->em->getRepository(SessionNotes::class)->findBy(['session' => $id]);
        $todos = $this->em->getRepository(SessionTodos::class)->findBy(['session' => $id]);
        $feedbacks = $this->em->getRepository(SessionFeedback::class)->findBy(['session' => $id]);

        return $this->render('BackOffice/mentorship/advanced/workspace.html.twig', [
            'session' => $session,
            'mentor' => $mentor,
            'entrepreneur' => $entrepreneur,
            'notes' => $notes,
            'todos' => $todos,
            'feedbacks' => $feedbacks
        ]);
    }

    #[Route('/list', name: 'app_advanced_list')]
    public function listSessions(PaginatorInterface $paginator, Request $request): Response
    {
        $conn = $this->em->getConnection();
        
        // Mocking user for now (ID 2 for entrepreneur)
        $userId = 2; // Stub representing Entrepreneur

        $sqlSessions = "SELECT s.*, u.fullName as mentorName, u.image_name 
                        FROM session s 
                        LEFT JOIN users u ON s.mentor_id = u.id 
                        WHERE s.entrepreneur_id = :id OR s.mentor_id = :id
                        ORDER BY s.session_date DESC";
        $stmtSessions = $conn->prepare($sqlSessions);
        /** @phpstan-ignore-next-line */
        $resultSetSessions = $stmtSessions->executeQuery(['id' => $userId]);
        $allSessions = $resultSetSessions->fetchAllAssociative();

        $sqlBookings = "SELECT b.*, u.fullName as mentorName 
                        FROM booking b 
                        LEFT JOIN users u ON b.mentor_id = u.id 
                        WHERE b.entrepreneur_id = :id OR b.mentor_id = :id
                        ORDER BY b.creation_date DESC";
        $stmtBookings = $conn->prepare($sqlBookings);
        /** @phpstan-ignore-next-line */
        $resultSetBookings = $stmtBookings->executeQuery(['id' => $userId]);
        $allBookings = $resultSetBookings->fetchAllAssociative();

        $paginationSessions = $paginator->paginate(
            $allSessions,
            $request->query->getInt('page', 1),
            5
        );

        $paginationBookings = $paginator->paginate(
            $allBookings,
            $request->query->getInt('bpage', 1),
            5
        );

        return $this->render('BackOffice/mentorship/list_sessions.html.twig', [
            'sessions' => $paginationSessions,
            'bookings' => $paginationBookings
        ]);
    }

    // SMART SCHEDULING API
    #[Route('/api/book', name: 'app_advanced_book', methods: ['POST'])]
    public function bookAction(Request $request): Response
    {
        $mentorId = $request->request->getInt('mentorID');
        $dateStr = (string)$request->request->get('date');
        $timeStr = (string)$request->request->get('time', '10:00:00');
        $topic = (string)$request->request->get('topic', 'General Mentorship');
        $entrepreneurId = 2; // Mock current user

        $conn = $this->em->getConnection();
        
        // 1. Prevent double booking for this exact date and time
        $sqlDouble = "SELECT COUNT(*) FROM booking WHERE mentor_id = :mentorID AND requested_date = :date AND requested_time = :time AND status != 'Rejected'";
        $doubleBookingCount = $conn->fetchOne($sqlDouble, ['mentorID' => $mentorId, 'date' => $dateStr, 'time' => $timeStr]);
        if ($doubleBookingCount > 0) {
             return $this->json(['status' => 'error', 'message' => 'This slot is already booked. Please choose another time.']);
        }

        // 2. Limit mentor to max 5 sessions per day
        $sqlCheck = "SELECT COUNT(*) as c FROM session s JOIN schedule sc ON s.schedule_id_int = sc.schedule_id 
                     WHERE sc.mentor_id = :mentorID AND sc.available_date = :date";
        $count = $conn->fetchOne($sqlCheck, ['mentorID' => $mentorId, 'date' => $dateStr]);

        if ($count >= 5) {
            return $this->json(['status' => 'error', 'message' => 'Mentor has reached maximum daily capacity (5 sessions).']);
        }

        try {
            // 3. Create Booking
            $booking = new Booking();
            $booking->setBookingID(random_int(1000, 9999));
            $booking->setEntrepreneur($em->getRepository(Users::class)->find($entrepreneurId));
            $booking->setMentor($em->getRepository(Users::class)->find($mentorId));
            $booking->setRequestedDate(\DateTime::createFromFormat('Y-m-d', $dateStr) ?: null);
            $booking->setRequestedTime(\DateTime::createFromFormat('H:i:s', $timeStr) ?: (\DateTime::createFromFormat('H:i', $timeStr) ?: null));
            $booking->setTopic($topic);
            $booking->setStatus('Requested');
            $booking->setCreationDate(new \DateTime());
            
            $this->em->persist($booking);
            $this->em->flush();

            // 4. Send Email Notification
            $mentor = $this->em->getRepository(Users::class)->find($mentorId);
            $entrepreneur = $this->em->getRepository(Users::class)->find($entrepreneurId);
            if ($mentor && $entrepreneur) {
                $this->mailer->sendBookingCreationEmail($booking, $entrepreneur, $mentor);
            }

            return $this->json(['status' => 'success', 'message' => 'Booking requested successfully. Pending approval.']);
        } catch (\Exception $e) {
            return $this->json(['status' => 'error', 'message' => 'Failed to create booking: ' . $e->getMessage()]);
        }
    }

    #[Route('/api/approve/{id}', name: 'app_advanced_approve', methods: ['POST'])]
    public function approveBookingAction(int $id, Request $request): Response
    {
        $booking = $this->em->getRepository(Booking::class)->find($id);
        if (!$booking) {
            return $this->json(['status' => 'error', 'message' => 'Booking not found.']);
        }

        $booking->setStatus('Approved');
        $this->em->persist($booking);

        // Create Schedule
        $schedule = new Schedule();
        $schedule->setScheduleID(random_int(1000, 9999));
        $schedule->setMentor($booking->getMentor());
        $schedule->setAvailableDate($booking->getRequestedDate());
        $requestedTime = $booking->getRequestedTime();
        if ($requestedTime instanceof \DateTimeInterface) {
            $schedule->setStartTime(new \DateTime($requestedTime->format('H:i:s')));
        }
        
        $startTimeObj = $schedule->getStartTime();
        if ($startTimeObj) {
            $endTime = clone $startTimeObj;
            $endTime->modify('+1 hour');
            $schedule->setEndTime($endTime);
        }
        $schedule->setIsBooked(true);

        $this->em->persist($schedule);
        $this->em->flush();

        // Create Session
        $session = new Session();
        $session->setSessionID(random_int(1000, 9999));
        $session->setMentor($booking->getMentor());
        $session->setEntrepreneur($booking->getEntrepreneur());
        $session->setScheduleID($schedule->getScheduleID());
        $session->setSessionDate($schedule->getAvailableDate());
        $session->setSessionType('Video');
        $session->setStatus('Scheduled');
        
        $session->setMeetingLink('https://meet.jit.si/Mentorship_' . bin2hex(random_bytes(8)));

        $this->em->persist($session);
        $this->em->flush();

        // Send Email
        $mentor = $this->em->getRepository(Users::class)->find($booking->getMentor()->getId());
        $entrepreneur = $this->em->getRepository(Users::class)->find($booking->getEntrepreneur()->getId());
        if ($mentor && $entrepreneur) {
             $this->mailer->sendBookingApprovalEmail($booking, $entrepreneur, $mentor, $session);
        }

        return $this->json(['status' => 'success', 'message' => 'Booking approved. Session generated.']);
    }
    
    #[Route('/api/complete/{id}', name: 'app_advanced_complete', methods: ['POST'])]
    public function completeSessionAction(int $id): Response
    {
        $session = $this->em->getRepository(Session::class)->find($id);
        if (!$session) {
            return $this->json(['status' => 'error', 'message' => 'Session not found']);
        }

        $session->setStatus('Completed');
        $this->em->flush();

        return $this->json(['status' => 'success', 'message' => 'Session marked as completed.']);
    }
}
