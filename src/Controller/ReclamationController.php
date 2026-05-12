<?php

namespace App\Controller;

use App\Entity\Reclamations;
use App\Entity\Responses as ReclamationResponses;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ReclamationController extends AbstractController
{
    #[Route('/reclamation/users', name: 'app_reclamation_users', methods: ['GET'])]
    public function getUsers(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(Users::class)->findAll();
        
        $userList = [];
        foreach ($users as $user) {
            $userList[] = [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
            ];
        }

        return new JsonResponse(['users' => $userList]);
    }

    #[Route('/reclamation/submit', name: 'app_reclamation_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, \App\Service\RiskAssessmentService $riskService): JsonResponse
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return new JsonResponse(['status' => 'unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['title']) || !isset($data['description'])) {
            return new JsonResponse(['status' => 'error', 'message' => 'Missing title or description']);
        }

        $reclamation = new Reclamations();
        $reclamation->setTitle($data['title']);
        $reclamation->setDescription($data['description']);
        $reclamation->setStatus('OPEN'); 
        $reclamation->setCreatedAt(new \DateTime());
        $reclamation->setRequestedId($userId);

        $targetUser = null;
        if ($data['title'] === 'User problem' && !empty($data['targetId'])) {
            $reclamation->setTargetId((int) $data['targetId']);
            $targetUser = $em->getRepository(Users::class)->find((int) $data['targetId']);
        }

        $errors = $validator->validate($reclamation);
        if (count($errors) > 0) {
            return new JsonResponse(['status' => 'error', 'message' => $errors[0]->getMessage()]);
        }

        $em->persist($reclamation);
        $em->flush();

        // AI Risk Assessment Engine trigger!
        if ($targetUser) {
            $riskService->assessUserRisk($targetUser);
        }

        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/reclamation/mine', name: 'app_reclamation_mine', methods: ['GET'])]
    public function getMine(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $session = $request->getSession();
        $userId = $session->get('user_id');

        if (!$userId) {
            return new JsonResponse(['status' => 'unauthorized'], 401);
        }

        $reclamations = $em->getRepository(Reclamations::class)->findBy(['requestedId' => $userId], ['createdAt' => 'DESC']);
        $responsesRepo = $em->getRepository(ReclamationResponses::class);
        $usersRepo = $em->getRepository(Users::class);

        $result = [];
        foreach ($reclamations as $rec) {
            // Find responses for this reclamation
            $responses = $responsesRepo->findBy(['reclamationId' => $rec->getId()], ['createdAt' => 'ASC']);
            
            $resList = [];
            foreach ($responses as $res) {
                // Get admin name
                $admin = $usersRepo->find($res->getResponderUserId());
                $adminName = $admin ? $admin->getFullName() : 'Admin';
                
                $resList[] = [
                    'id' => $res->getId(),
                    'content' => $res->getContent(),
                    'date' => $res->getCreatedAt()->format('Y-m-d H:i'),
                    'admin_name' => $adminName
                ];
            }

            $targetName = null;
            if ($rec->getTargetId()) {
                $target = $usersRepo->find($rec->getTargetId());
                if ($target) {
                    $targetName = $target->getFullName();
                }
            }

            $result[] = [
                'id' => $rec->getId(),
                'title' => $rec->getTitle(),
                'description' => $rec->getDescription(),
                'status' => $rec->getStatus(),
                'date' => $rec->getCreatedAt() ? $rec->getCreatedAt()->format('Y-m-d H:i') : null,
                'target_name' => $targetName,
                'responses' => $resList
            ];
        }

        return new JsonResponse(['status' => 'success', 'reclamations' => $result]);
    }
}
