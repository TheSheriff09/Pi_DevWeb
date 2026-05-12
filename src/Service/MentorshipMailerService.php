<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Session;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class MentorshipMailerService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $em;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $em)
    {
        $this->mailer = $mailer;
        $this->em = $em;
    }

    public function sendBookingCreationEmail(Booking $booking, Users $entrepreneur, Users $mentor): void
    {
        $status = $booking->getStatus() ?? 'Requested';
        
        $email = (new TemplatedEmail())
            ->from('no-reply@startupflow.com')
            ->to($mentor->getEmail())
            ->subject('New Mentorship Booking Request: ' . $booking->getTopic())
            ->htmlTemplate('BackOffice/mentorship/emails/booking_created.html.twig')
            ->context([
                'booking' => $booking,
                'entrepreneur' => $entrepreneur,
                'mentor' => $mentor
            ]);

        $this->mailer->send($email);
    }

    public function sendBookingApprovalEmail(Booking $booking, Users $entrepreneur, Users $mentor, Session $session = null): void
    {
        $email = (new TemplatedEmail())
            ->from('no-reply@startupflow.com')
            ->to($entrepreneur->getEmail())
            ->subject('Mentorship Booking Approved: ' . $booking->getTopic())
            ->htmlTemplate('BackOffice/mentorship/emails/booking_approved.html.twig')
            ->context([
                'booking' => $booking,
                'entrepreneur' => $entrepreneur,
                'mentor' => $mentor,
                'session' => $session
            ]);

        $this->mailer->send($email);
    }

    public function sendSessionReminderEmail(Session $session, Users $entrepreneur, Users $mentor): void
    {
        // Send to Entrepreneur
        $emailEnt = (new TemplatedEmail())
            ->from('no-reply@startupflow.com')
            ->to($entrepreneur->getEmail())
            ->subject('Reminder: Your Upcoming Session with ' . $mentor->getFullName())
            ->htmlTemplate('BackOffice/mentorship/emails/session_reminder.html.twig')
            ->context([
                'session' => $session,
                'entrepreneur' => $entrepreneur,
                'mentor' => $mentor,
                'isMentor' => false
            ]);

        // Send to Mentor
        $emailMentor = (new TemplatedEmail())
            ->from('no-reply@startupflow.com')
            ->to($mentor->getEmail())
            ->subject('Reminder: Upcoming Session with ' . $entrepreneur->getFullName())
            ->htmlTemplate('BackOffice/mentorship/emails/session_reminder.html.twig')
            ->context([
                'session' => $session,
                'entrepreneur' => $entrepreneur,
                'mentor' => $mentor,
                'isMentor' => true
            ]);

        $this->mailer->send($emailEnt);
        $this->mailer->send($emailMentor);
    }
}
