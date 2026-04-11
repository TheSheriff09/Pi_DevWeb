<?php

namespace App\Controller;

use App\Entity\Fundingapplication;
use App\Entity\Fundingevaluation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/evaluation')]
class AdminEvaluationController extends AbstractController
{
    private function ensureAdmin(Request $request): ?Response
    {
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }
        return null;
    }

    #[Route('/', name: 'app_admin_evaluation_dashboard')]
    public function dashboard(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $applicationsCount = $em->getRepository(Fundingapplication::class)->count([]);
        $evaluationsCount = $em->getRepository(Fundingevaluation::class)->count([]);

        $latestApplications = $em->getRepository(Fundingapplication::class)->findBy([], ['submissionDate' => 'DESC'], 5);

        return $this->render('BackOffice/evaluation/dashboard.html.twig', [
            'applicationsCount' => $applicationsCount,
            'evaluationsCount' => $evaluationsCount,
            'latestApplications' => $latestApplications,
            'current_menu' => 'dashboard'
        ]);
    }

    #[Route('/applications', name: 'app_admin_evaluation_applications')]
    public function applications(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'submissionDate');
        $sortDir = strtoupper($request->query->get('sortDir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $em->getRepository(Fundingapplication::class)->createQueryBuilder('a');

        if ($search && is_numeric($search)) {
            $qb->where('a.id = :search')
               ->setParameter('search', $search);
        }

        $allowedSorts = ['id', 'amount', 'submissionDate', 'status'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('a.' . $sortBy, $sortDir);
        }

        $applications = $qb->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/evaluation/_applications_tbody.html.twig', [
                'applications' => $applications
            ]);
        }

        return $this->render('BackOffice/evaluation/applications.html.twig', [
            'applications' => $applications,
            'current_menu' => 'applications'
        ]);
    }

    #[Route('/applications/{id}/edit', name: 'app_admin_evaluation_application_edit', methods: ['GET', 'POST'])]
    public function editApplication(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $application = $em->getRepository(Fundingapplication::class)->find($id);
        if (!$application) {
            return $this->redirectToRoute('app_admin_evaluation_applications');
        }

        if ($request->isMethod('POST')) {
            $application->setAmount((float) $request->request->get('amount'));
            $application->setApplicationReason($request->request->get('applicationReason'));
            $application->setPaymentSchedule($request->request->get('paymentSchedule'));
            $application->setStatus($request->request->get('status'));
            
            $em->flush();
            return $this->redirectToRoute('app_admin_evaluation_applications');
        }

        return $this->render('BackOffice/evaluation/edit_application.html.twig', [
            'application' => $application,
            'current_menu' => 'applications'
        ]);
    }

    #[Route('/applications/{id}/delete', name: 'app_admin_evaluation_application_delete', methods: ['POST'])]
    public function deleteApplication(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $application = $em->getRepository(Fundingapplication::class)->find($id);
        if ($application) {
            $em->remove($application);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_evaluation_applications');
    }

    #[Route('/evaluations', name: 'app_admin_evaluation_evaluations')]
    public function evaluations(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'createdAt');
        $sortDir = strtoupper($request->query->get('sortDir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $em->getRepository(Fundingevaluation::class)->createQueryBuilder('e');

        if ($search && is_numeric($search)) {
            $qb->where('e.id = :search')
               ->setParameter('search', $search);
        }

        $allowedSorts = ['id', 'fundingApplicationId', 'score', 'decision', 'riskLevel', 'createdAt'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('e.' . $sortBy, $sortDir);
        } else if ($sortBy === 'createdAt') {
            $qb->orderBy('e.createdAt', $sortDir);
        }

        $evaluations = $qb->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/evaluation/_evaluations_tbody.html.twig', [
                'evaluations' => $evaluations
            ]);
        }

        return $this->render('BackOffice/evaluation/evaluations.html.twig', [
            'evaluations' => $evaluations,
            'current_menu' => 'evaluations'
        ]);
    }

    #[Route('/evaluations/{id}/edit', name: 'app_admin_evaluation_evaluation_edit', methods: ['GET', 'POST'])]
    public function editEvaluation(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $evaluation = $em->getRepository(Fundingevaluation::class)->find($id);
        if (!$evaluation) {
            return $this->redirectToRoute('app_admin_evaluation_evaluations');
        }

        if ($request->isMethod('POST')) {
            $evaluation->setScore((int) $request->request->get('score'));
            $evaluation->setDecision($request->request->get('decision'));
            $evaluation->setRiskLevel($request->request->get('riskLevel'));
            $evaluation->setFundingCategory($request->request->get('fundingCategory'));
            $evaluation->setEvaluationComments($request->request->get('evaluationComments'));
            
            $em->flush();
            return $this->redirectToRoute('app_admin_evaluation_evaluations');
        }

        return $this->render('BackOffice/evaluation/edit_evaluation.html.twig', [
            'evaluation' => $evaluation,
            'current_menu' => 'evaluations'
        ]);
    }

    #[Route('/evaluations/{id}/delete', name: 'app_admin_evaluation_evaluation_delete', methods: ['POST'])]
    public function deleteEvaluation(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $evaluation = $em->getRepository(Fundingevaluation::class)->find($id);
        if ($evaluation) {
            $em->remove($evaluation);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_evaluation_evaluations');
    }
}
