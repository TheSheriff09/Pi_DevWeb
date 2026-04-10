<?php

namespace App\Controller;

use App\Entity\Schedule;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mentorship')]
class ScheduleController extends AbstractController
{
    #[Route('/schedule', name: 'app_mentor_schedule')]
    public function mySchedule(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        
        if (!$userId || $role !== 'MENTOR') {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $date = $request->request->get('date');
            $startTime = $request->request->get('start_time');
            $endTime = $request->request->get('end_time');
            
            if ($date && $startTime && $endTime) {
                $availableDateObj = new \DateTime($date);
                $startTimeObj = new \DateTime($startTime);
                $endTimeObj = new \DateTime($endTime);
                
                $now = new \DateTime();
                if ($availableDateObj->format('Y-m-d') < $now->format('Y-m-d') || 
                   ($availableDateObj->format('Y-m-d') === $now->format('Y-m-d') && $startTimeObj->format('H:i') < $now->format('H:i'))) {
                    $this->addFlash('error', 'Cannot create a schedule slot in the past.');
                    return $this->redirectToRoute('app_mentor_schedule');
                }
                
                if ($endTimeObj <= $startTimeObj) {
                    $this->addFlash('error', 'End time must be after Start time.');
                    return $this->redirectToRoute('app_mentor_schedule');
                }

                $maxId = $em->createQueryBuilder()
                    ->select('MAX(s.scheduleID)')
                    ->from(Schedule::class, 's')
                    ->getQuery()
                    ->getSingleScalarResult();

                $schedule = new Schedule();
                $schedule->setScheduleID(($maxId ?? 0) + 1);
                $schedule->setMentorID($userId);
                $schedule->setAvailableDate($availableDateObj);
                $schedule->setStartTime($startTimeObj);
                $schedule->setEndTime($endTimeObj);
                $schedule->setIsBooked(false);
                
                $em->persist($schedule);
                $em->flush();
                $this->addFlash('success', 'Time slot added successfully!');
                return $this->redirectToRoute('app_mentor_schedule');
            }
        }

        $qb = $em->createQueryBuilder()
            ->select('s')
            ->from(Schedule::class, 's')
            ->where('s.mentorID = :mentorId')
            ->setParameter('mentorId', $userId)
            ->orderBy('s.availableDate', 'DESC')
            ->addOrderBy('s.startTime', 'DESC');
            
        $schedules = $qb->getQuery()->getResult();

        return $this->render('FrontOffice/mentorship/schedule.html.twig', [
            'schedules' => $schedules
        ]);
    }
    
    #[Route('/schedule/delete/{id}', name: 'app_mentor_schedule_delete', methods: ['POST'])]
    public function deleteSchedule(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        
        if (!$userId || $role !== 'MENTOR') {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $schedule = $em->getRepository(Schedule::class)->findOneBy(['scheduleID' => $id, 'mentorID' => $userId]);
        if ($schedule && !$schedule->getIsBooked()) {
            $em->remove($schedule);
            $em->flush();
            return $this->json(['status' => 'success']);
        }
        
        return $this->json(['status' => 'error', 'message' => 'Cannot delete booked or non-existent schedule.'], 400);
    }
}
