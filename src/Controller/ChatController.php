<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/contacts', name: 'app_chat_contacts', methods: ['GET'])]
    public function contacts(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');

        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $users = $em->getRepository(Users::class)->findAll();
        
        $contacts = [];
        foreach ($users as $u) {
            if ($u->getId() !== $userId) {
                $contacts[] = [
                    'id' => $u->getId(),
                    'name' => $u->getFullName(),
                    'role' => $u->getRole()
                ];
            }
        }

        return $this->json($contacts);
    }

    #[Route('/history/{contactId}', name: 'app_chat_history', methods: ['GET'])]
    public function history(int $contactId, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');

        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        // Fetch messages between userId and contactId
        $qb = $em->createQueryBuilder()
            ->select('m')
            ->from(Message::class, 'm')
            ->where('(m.senderId = :userId AND m.receiverId = :contactId) OR (m.senderId = :contactId AND m.receiverId = :userId)')
            ->setParameter('userId', $userId)
            ->setParameter('contactId', $contactId)
            ->orderBy('m.createdAt', 'ASC');

        $messages = $qb->getQuery()->getResult();
        
        $history = [];
        foreach ($messages as $msg) {
            $history[] = [
                'id' => $msg->getId(),
                'senderId' => $msg->getSenderId(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('H:i')
            ];

            // Mark as read if user is the receiver
            if ($msg->getReceiverId() === $userId && !$msg->isRead()) {
                $msg->setRead(true);
            }
        }
        
        $em->flush(); // Commit read statuses

        return $this->json($history);
    }

    #[Route('/send', name: 'app_chat_send', methods: ['POST'])]
    public function send(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->getSession()->get('user_id');

        if (!$userId) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $receiverId = $data['receiverId'] ?? null;
        $content = $data['content'] ?? null;

        if (!$receiverId || !$content) {
            return $this->json(['error' => 'Invalid data payload'], 400);
        }

        $msg = new Message();
        $msg->setSenderId($userId);
        $msg->setReceiverId($receiverId);
        $msg->setContent($content);
        // IsRead and CreatedAt handled securely in constructor

        $em->persist($msg);
        $em->flush();

        return $this->json([
            'status' => 'success',
            'message' => [
                'id' => $msg->getId(),
                'senderId' => $msg->getSenderId(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('H:i')
            ]
        ]);
    }
}
