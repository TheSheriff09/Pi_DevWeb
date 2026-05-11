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
            ->where('(b.entrepreneur = :uid OR b.mentor = :uid) AND b.status = :status')
            ->setParameter('uid', $userId)
            ->setParameter('status', 'approved')
            ->getQuery()
            ->getResult();

        $contactIds = [];
        foreach ($bookings as $b) {
            $otherId = ($b->getEntrepreneur()->getId() == $userId) ? $b->getMentor()->getId() : $b->getEntrepreneur()->getId();
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
                    'sender' => $cid,
                    'receiver' => $userId,
                    'isRead' => false
                ]);

                // Get last message text
                $lastMsg = $em->getRepository(MentorshipMessage::class)->findOneBy(
                    ['sender' => [$cid, $userId], 'receiver' => [$cid, $userId]],
                    ['timestamp' => 'DESC']
                );

                $contacts[] = [
                    'id' => $user->getId(),
                    'name' => $user->getFullName(),
                    'role' => $user->getRole(),
                    'unread' => $unreadCount,
                    'latestPreview' => $lastMsg ? substr((string)$lastMsg->getContent(), 0, 30) . (strlen((string)$lastMsg->getContent()) > 30 ? '...' : '') : ''
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
            ->where('m.sender = :cid')
            ->andWhere('m.receiver = :uid')
            ->andWhere('m.isRead = 0')
            ->setParameter('cid', $contactId)
            ->setParameter('uid', $userId)
            ->getQuery()
            ->execute();

        // Fetch history
        $rawMessages = $em->getRepository(MentorshipMessage::class)->createQueryBuilder('m')
            ->where('(m.sender = :uid AND m.receiver = :cid) OR (m.sender = :cid AND m.receiver = :uid)')
            ->setParameter('uid', $userId)
            ->setParameter('cid', $contactId)
            ->orderBy('m.timestamp', 'ASC')
            ->getQuery()
            ->getResult();

        $messages = [];
        foreach ($rawMessages as $m) {
            $timestamp = $m->getTimestamp();
            $messages[] = [
                'id' => $m->getId(),
                'sender' => $m->getSender()->getId(),
                'content' => $m->getContent(),
                'time' => $timestamp ? $timestamp->format('H:i') : '',
                'date' => $timestamp ? $timestamp->format('M j') : ''
            ];
        }

        return $this->json($messages);
    }

    #[Route('/send/{receiver}', name: 'app_mentorship_chat_send', methods: ['POST'])]
    public function sendMessage(int $receiver, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['error' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }

        $content = trim((string)$request->request->get('message', ''));
        if (empty($content)) return $this->json(['error' => 'Empty message'], 400);

        $msg = new MentorshipMessage();
        $msg->setSender($em->getRepository(Users::class)->find($userId));
        $msg->setReceiver($em->getRepository(Users::class)->find($receiver));
        $msg->setContent($content);
        $msg->setTimestamp(new \DateTime());
        $msg->setIsRead(false);

        $em->persist($msg);
        $em->flush();

            $timestamp = $msg->getTimestamp();
            return $this->json([
                'id' => $msg->getId(),
                'sender' => $msg->getSender()->getId(),
                'content' => $msg->getContent(),
                'time' => $timestamp ? $timestamp->format('H:i') : '',
                'date' => $timestamp ? $timestamp->format('M j') : ''
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

        // Immediately unlock the session to prevent blocking subsequent page navigations
        if ($request->hasSession() && $request->getSession()->isStarted()) {
            $request->getSession()->save();
        }

        $qb = $em->getRepository(MentorshipMessage::class)->createQueryBuilder('m')
            ->leftJoin('m.sender', 's')->addSelect('s')
            ->where('m.receiver = :uid')
            ->andWhere('m.isRead = 0')
            ->setParameter('uid', $userId);
            
        $unreadMessages = $qb->getQuery()->getResult();
        
        $payload = [
            'totalUnread' => count($unreadMessages),
            'contactsUnread' => [],
            'newActiveMessages' => []
        ];

        foreach ($unreadMessages as $m) {
            $sid = $m->getSender()->getId();
            if (!isset($payload['contactsUnread'][$sid])) {
                $payload['contactsUnread'][$sid] = 0;
            }
            $payload['contactsUnread'][$sid]++;

            // If this message belongs to the currently active window, forward it directly!
            if ($activeContactId && $sid == $activeContactId) {
                $timestamp = $m->getTimestamp();
                $payload['newActiveMessages'][] = [
                    'id' => $m->getId(),
                    'senderId' => $sid,
                    'content' => $m->getContent(),
                    'time' => $timestamp ? $timestamp->format('H:i') : ''
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
