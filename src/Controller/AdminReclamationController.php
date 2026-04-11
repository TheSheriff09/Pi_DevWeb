<?php

namespace App\Controller;

use App\Entity\Reclamations;
use App\Entity\Responses as ReclamationResponses;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminReclamationController extends AbstractController
{
    private function isAdmin(Request $request): bool
    {
        $role = $request->getSession()->get('user_role');
        return $role && strtoupper($role) === 'ADMIN';
    }

    #[Route('/admin/reclamations', name: 'app_admin_reclamations')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $reclamationsRaw = $em->getRepository(Reclamations::class)->findAll();
        
        $usersResult = $em->getRepository(Users::class)->findAll();
        $usersMap = [];
        $adminsMap = [];
        foreach ($usersResult as $user) {
            $usersMap[$user->getId()] = $user->getFullName();
            if ($user->getRole() === 'ADMIN' || $user->getRole() === 'admin') {
                $adminsMap[$user->getId()] = $user->getFullName();
            }
        }

        $responsesRepo = $em->getRepository(ReclamationResponses::class);
        $reclamations = [];
        
        foreach ($reclamationsRaw as $rec) {
            $responses = $responsesRepo->findBy(['reclamationId' => $rec->getId()], ['createdAt' => 'ASC']);
            
            $resList = [];
            foreach ($responses as $r) {
                // Determine admin name safely, default to 'Admin' if not mapped
                $adminName = $usersMap[$r->getResponderUserId()] ?? 'Admin';
                
                $resList[] = [
                    'id' => $r->getId(),
                    'content' => $r->getContent(),
                    'date' => $r->getCreatedAt()->format('Y-m-d H:i'),
                    'adminName' => $adminName
                ];
            }
            
            $reclamations[] = [
                'entity' => $rec,
                'responsesList' => $resList
            ];
        }

        return $this->render('BackOffice/reclamation/index.html.twig', [
            'reclamations' => $reclamations,
            'usersMap' => $usersMap,
        ]);
    }

    #[Route('/admin/reclamation/delete/{id}', name: 'app_admin_reclamation_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        if ($reclamation) {
            // Also delete related responses implicitly, or let cascade do it if defined. 
            // Better to delete explicitly if cascade isn't defined
            $responses = $em->getRepository(ReclamationResponses::class)->findBy(['reclamationId' => $reclamation->getId()]);
            foreach ($responses as $r) {
                $em->remove($r);
            }
            
            $em->remove($reclamation);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/admin/reclamation/status/{id}', name: 'app_admin_reclamation_status', methods: ['POST'])]
    public function updateStatus(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $status = $request->request->get('status');
        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        
        $validStatuses = ['OPEN', 'IN_PROGRESS', 'RESOLVED', 'REJECTED'];

        if ($reclamation && $status && in_array($status, $validStatuses)) {
            $reclamation->setStatus($status);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }

    #[Route('/admin/reclamation/respond/{id}', name: 'app_admin_reclamation_respond', methods: ['POST'])]
    public function respond(Request $request, int $id, EntityManagerInterface $em): Response
    {
        if (!$this->isAdmin($request)) {
            return $this->redirectToRoute('app_login');
        }

        $content = $request->request->get('response_content');
        $reclamation = $em->getRepository(Reclamations::class)->find($id);
        
        if ($reclamation && $content) {
            $response = new ReclamationResponses();
            $response->setContent($content);
            $response->setCreatedAt(new \DateTime());
            $response->setReclamationId($reclamation->getId());
            $response->setResponderUserId($request->getSession()->get('user_id'));
            
            $em->persist($response);
            
            // Optionally, we could set status to IN_PROGRESS if it's OPEN
            if ($reclamation->getStatus() === 'OPEN') {
                $reclamation->setStatus('IN_PROGRESS');
            }

            $em->flush();
        }

        return $this->redirectToRoute('app_admin_reclamations');
    }
}
