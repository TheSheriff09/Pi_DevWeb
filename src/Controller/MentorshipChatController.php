<?php

namespace App\Controller;

use App\Entity\MentorshipMessage;
use App\Entity\Users;
use App\Entity\Booking;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mentorship/chat/api')]
class MentorshipChatController extends AbstractController
{
    #[Route('/contacts', name: 'app_mentorship_chat_contacts', methods: ['GET'])]
    public function getContacts(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['error' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }

        $bookings = $em->getRepository(Booking::class)->createQueryBuilder('b')
            ->where('(b.entrepreneurID = :uid OR b.mentorID = :uid) AND b.status = :status')
            ->setParameter('uid', $userId)
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getResult();

        $contactIds = [];
        foreach ($bookings as $b) {
            $otherId = ($b->getEntrepreneurID() == $userId) ? $b->getMentorID() : $b->getEntrepreneurID();
            if (!in_array($otherId, $contactIds)) {
                $contactIds[] = $otherId;
            }
        }

        $contacts = [];
        foreach ($contactIds as $cid) {
            $user = $em->getRepository(Users::class)->find($cid);
            if ($user) {
                // Get unread count specifically for this contact
                $unreadCount = $em->getRepository(MentorshipMessage::class)->count([
                    'senderId' => $cid,
                    'receiverId' => $userId,
                    'isRead' => false
                ]);

                // Get last message text
                $lastMsg = $em->getRepository(MentorshipMessage::class)->findOneBy(
                    ['senderId' => [$cid, $userId], 'receiverId' => [$cid, $userId]],
                    ['timestamp' => 'DESC']
                );

                $contacts[] = [
                    'id' => $user->getId(),
                    'name' => $user->getFullName(),
                    'role' => $user->getRole(),
                    'unread' => $unreadCount,
                    'latestPreview' => $lastMsg ? substr($lastMsg->getContent(), 0, 30) . (strlen($lastMsg->getContent()) > 30 ? '...' : '') : ''
                ];
            }
        }

        return $this->json($contacts);
    }

    #[Route('/messages/{contactId}', name: 'app_mentorship_chat_history', methods: ['GET'])]
    public function getMessages(int $contactId, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['error' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }

        // Mark all incoming from this contact as read
        $em->createQueryBuilder()
            ->update(MentorshipMessage::class, 'm')
            ->set('m.isRead', '1')
            ->where('m.senderId = :cid')
            ->andWhere('m.receiverId = :uid')
            ->andWhere('m.isRead = 0')
            ->setParameter('cid', $contactId)
            ->setParameter('uid', $userId)
            ->getQuery()
            ->execute();

        // Fetch history
        $rawMessages = $em->getRepository(MentorshipMessage::class)->createQueryBuilder('m')
            ->where('(m.senderId = :uid AND m.receiverId = :cid) OR (m.senderId = :cid AND m.receiverId = :uid)')
            ->setParameter('uid', $userId)
            ->setParameter('cid', $contactId)
            ->orderBy('m.timestamp', 'ASC')
            ->getQuery()
            ->getResult();

        $messages = [];
        foreach ($rawMessages as $m) {
            $messages[] = [
                'id' => $m->getId(),
                'senderId' => $m->getSenderId(),
                'content' => $m->getContent(),
                'time' => $m->getTimestamp()->format('H:i'),
                'date' => $m->getTimestamp()->format('M j')
            ];
        }

        return $this->json($messages);
    }

    #[Route('/send/{receiverId}', name: 'app_mentorship_chat_send', methods: ['POST'])]
    public function sendMessage(int $receiverId, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['error' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }

        $content = trim($request->request->get('message', ''));
        if (empty($content)) return $this->json(['error' => 'Empty message'], 400);

        $msg = new MentorshipMessage();
        $msg->setSenderId($userId);
        $msg->setReceiverId($receiverId);
        $msg->setContent($content);
        $msg->setTimestamp(new \DateTime());
        $msg->setIsRead(false);

        $em->persist($msg);
        $em->flush();

        return $this->json([
            'id' => $msg->getId(),
            'senderId' => $msg->getSenderId(),
            'content' => $msg->getContent(),
            'time' => $msg->getTimestamp()->format('H:i'),
            'date' => $msg->getTimestamp()->format('M j')
        ]);
    }

    #[Route('/poll', name: 'app_mentorship_chat_poll', methods: ['GET'])]
    public function poll(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['error' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }

        $activeContactId = $request->query->get('activeContact'); // ID of currently open chat

        $qb = $em->getRepository(MentorshipMessage::class)->createQueryBuilder('m')
            ->where('m.receiverId = :uid')
            ->andWhere('m.isRead = 0')
            ->setParameter('uid', $userId);
            
        $unreadMessages = $qb->getQuery()->getResult();
        
        $payload = [
            'totalUnread' => count($unreadMessages),
            'contactsUnread' => [],
            'newActiveMessages' => []
        ];

        foreach ($unreadMessages as $m) {
            $sid = $m->getSenderId();
            if (!isset($payload['contactsUnread'][$sid])) {
                $payload['contactsUnread'][$sid] = 0;
            }
            $payload['contactsUnread'][$sid]++;

            // If this message belongs to the currently active window, forward it directly!
            if ($activeContactId && $sid == $activeContactId) {
                $payload['newActiveMessages'][] = [
                    'id' => $m->getId(),
                    'senderId' => $sid,
                    'content' => $m->getContent(),
                    'time' => $m->getTimestamp()->format('H:i')
                ];
                $m->setIsRead(true);
            }
        }
        
        if (count($payload['newActiveMessages']) > 0) {
            $em->flush();
        }

        return $this->json($payload);
    }
}
