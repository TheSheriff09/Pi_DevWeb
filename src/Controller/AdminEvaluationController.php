<?php

namespace App\Controller;

use App\Entity\Fundingapplication;
use App\Entity\Fundingevaluation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

        $avgScoreResult = $em->createQuery('SELECT AVG(e.score) FROM App\Entity\Fundingevaluation e')->getSingleScalarResult();
        $avgScore = $avgScoreResult ? (float) $avgScoreResult : 0.0;

        $topEvaluations = $em->getRepository(Fundingevaluation::class)->findBy([], ['score' => 'DESC'], 5);

        $decisionCountsRaw = $em->createQuery('SELECT e.decision, COUNT(e.id) as cnt FROM App\Entity\Fundingevaluation e GROUP BY e.decision')->getResult();
        $decisionCounts = ['Approved' => 0, 'Rejected' => 0, 'Pending' => 0];
        foreach ($decisionCountsRaw as $row) {
            $dec = (string)($row['decision'] ?: 'Pending');
            $decisionCounts[$dec] = (int)$row['cnt'];
        }

        return $this->render('BackOffice/evaluation/dashboard.html.twig', [
            'applicationsCount' => $applicationsCount,
            'evaluationsCount' => $evaluationsCount,
            'latestApplications' => $latestApplications,
            'avgScore' => $avgScore,
            'decisionCounts' => $decisionCounts,
            'topEvaluations' => $topEvaluations,
            'current_module' => 'evaluation',
            'current_menu' => 'dashboard'
        ]);
    }

    #[Route('/applications', name: 'app_admin_evaluation_applications')]
    public function applications(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $search = (string)$request->query->get('search', '');
        $sortBy = (string)$request->query->get('sortBy', 'submissionDate');
        $sortDir = strtoupper((string)$request->query->get('sortDir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

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
            'current_module' => 'evaluation',
            'current_menu' => 'applications'
        ]);
    }

    #[Route('/applications/{id}/edit', name: 'app_admin_evaluation_application_edit', methods: ['GET', 'POST'])]
    public function editApplication(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $application = $em->getRepository(Fundingapplication::class)->find($id);
        if (!$application) {
            return $this->redirectToRoute('app_admin_evaluation_applications');
        }

        if ($request->isMethod('POST')) {
            $application->setAmount((float) $request->request->get('amount'));
            $application->setApplicationReason((string)$request->request->get('applicationReason'));
            $application->setPaymentSchedule((string)$request->request->get('paymentSchedule'));
            $application->setStatus((string)$request->request->get('status'));
            
            $errors = $validator->validate($application);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst((string)$error->getPropertyPath()) . ': ' . (string)$error->getMessage());
                }
                return $this->redirectToRoute('app_admin_evaluation_application_edit', ['id' => $id]);
            }
            
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

        $search = (string)$request->query->get('search', '');
        $sortBy = (string)$request->query->get('sortBy', 'createdAt');
        $sortDir = strtoupper((string)$request->query->get('sortDir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

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
            'current_module' => 'evaluation',
            'current_menu' => 'evaluations'
        ]);
    }

    #[Route('/evaluations/{id}/edit', name: 'app_admin_evaluation_evaluation_edit', methods: ['GET', 'POST'])]
    public function editEvaluation(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $evaluation = $em->getRepository(Fundingevaluation::class)->find($id);
        if (!$evaluation) {
            return $this->redirectToRoute('app_admin_evaluation_evaluations');
        }

        if ($request->isMethod('POST')) {
            $evaluation->setScore((int) $request->request->get('score'));
            $evaluation->setDecision((string)$request->request->get('decision'));
            $evaluation->setRiskLevel((string)$request->request->get('riskLevel'));
            $evaluation->setFundingCategory((string)$request->request->get('fundingCategory'));
            $evaluation->setEvaluationComments((string)$request->request->get('evaluationComments'));
            
            $errors = $validator->validate($evaluation);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', ucfirst((string)$error->getPropertyPath()) . ': ' . (string)$error->getMessage());
                }
                return $this->redirectToRoute('app_admin_evaluation_evaluation_edit', ['id' => $id]);
            }
            
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

    #[Route('/pdf/report', name: 'app_admin_evaluation_pdf')]
    public function pdfReport(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $applications = $em->getRepository(Fundingapplication::class)->createQueryBuilder('a')
            ->orderBy('a.submissionDate', 'DESC')->setMaxResults(500)->getQuery()->getResult();
        $evaluations = $em->getRepository(Fundingevaluation::class)->createQueryBuilder('e')
            ->orderBy('e.createdAt', 'DESC')->setMaxResults(500)->getQuery()->getResult();

        $projectDir = $this->getParameter('kernel.project_dir');
        $logoPath = (is_string($projectDir) ? $projectDir : '') . '/public/Front/images/email/logo.png';
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $content = file_get_contents($logoPath);
            $logoBase64 = base64_encode($content ?: '');
        }

        $totalRequested = 0;
        $statusCounts = ['Pending' => 0, 'Approved' => 0, 'Rejected' => 0];
        foreach ($applications as $app) {
            $totalRequested += (float) $app->getAmount();
            $status = (string)$app->getStatus();
            if (isset($statusCounts[$status])) $statusCounts[$status]++;
            else $statusCounts[$status] = 1;
        }

        $avgScore = 0;
        $riskCounts = [];
        if (count($evaluations) > 0) {
            $sum = 0;
            foreach ($evaluations as $ev) {
                $sum += (int) $ev->getScore();
                $r = (string)$ev->getRiskLevel();
                if(!isset($riskCounts[$r])) $riskCounts[$r] = 0;
                $riskCounts[$r]++;
            }
            $avgScore = $sum / count($evaluations);
        }

        $html = $this->renderView('BackOffice/evaluation/pdf_report.html.twig', [
            'applications' => $applications,
            'evaluations' => $evaluations,
            'totalRequested' => $totalRequested,
            'appCount' => count($applications),
            'evalCount' => count($evaluations),
            'statusCounts' => $statusCounts,
            'avgScore' => $avgScore,
            'riskCounts' => $riskCounts,
            'logoBase64' => $logoBase64,
            'date' => new \DateTime()
        ]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="StartupFlow_Evaluation_Report.pdf"'
            ]
        );
    }
}
