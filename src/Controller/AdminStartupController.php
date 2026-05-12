<?php

namespace App\Controller;

use App\Entity\Startup;
use App\Entity\Businessplan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/startup')]
class AdminStartupController extends AbstractController
{
    private function ensureAdmin(Request $request): ?Response
    {
        $role = $request->getSession()->get('user_role');
        if (!$role || strtoupper($role) !== 'ADMIN') {
            return $this->redirectToRoute('app_login');
        }
        return null;
    }

    #[Route('/', name: 'app_admin_startup_dashboard')]
    public function dashboard(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $startupsCount = $em->getRepository(Startup::class)->count([]);
        $businessPlansCount = $em->getRepository(Businessplan::class)->count([]);

        $latestStartups = $em->getRepository(Startup::class)->findBy([], ['creationDate' => 'DESC'], 5);

        return $this->render('BackOffice/startup/dashboard.html.twig', [
            'startupsCount' => $startupsCount,
            'businessPlansCount' => $businessPlansCount,
            'latestStartups' => $latestStartups,
            'current_module' => 'startup',
            'current_menu' => 'dashboard'
        ]);
    }

    #[Route('/startups', name: 'app_admin_startup_startups')]
    public function startups(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'creationDate');
        $sortDir = strtoupper($request->query->get('sortDir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $em->getRepository(Startup::class)->createQueryBuilder('s');

        if ($search && is_numeric($search)) {
            $qb->where('s.startupID = :search')
               ->setParameter('search', $search);
        }

        $allowedSorts = ['startupID', 'name', 'sector', 'creationDate', 'kPIscore', 'stage', 'status'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('s.' . $sortBy, $sortDir);
        }

        $startups = $qb->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/startup/_startups_tbody.html.twig', [
                'startups' => $startups
            ]);
        }

        return $this->render('BackOffice/startup/startups.html.twig', [
            'startups' => $startups,
            'current_module' => 'startup',
            'current_menu' => 'startups'
        ]);
    }

    #[Route('/startups/{id}/edit', name: 'app_admin_startup_startup_edit', methods: ['GET', 'POST'])]
    public function editStartup(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $startup = $em->getRepository(Startup::class)->find($id);
        if (!$startup) {
            return $this->redirectToRoute('app_admin_startup_startups');
        }

        if ($request->isMethod('POST')) {
            $startup->setName($request->request->get('name'));
            $startup->setSector($request->request->get('sector'));
            $startup->setStage($request->request->get('stage'));
            $startup->setStatus($request->request->get('status'));
            $startup->setKPIscore((float) $request->request->get('kPIscore'));
            
            $em->flush();
            return $this->redirectToRoute('app_admin_startup_startups');
        }

        return $this->render('BackOffice/startup/edit_startup.html.twig', [
            'startup' => $startup,
            'current_module' => 'startup',
            'current_menu' => 'startups'
        ]);
    }

    #[Route('/startups/{id}/delete', name: 'app_admin_startup_startup_delete', methods: ['POST'])]
    public function deleteStartup(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $startup = $em->getRepository(Startup::class)->find($id);
        if ($startup) {
            $em->remove($startup);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_startup_startups');
    }

    #[Route('/businessplans', name: 'app_admin_startup_businessplans')]
    public function businessplans(EntityManagerInterface $em, Request $request): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $search = $request->query->get('search', '');
        $sortBy = $request->query->get('sortBy', 'creationDate');
        $sortDir = strtoupper($request->query->get('sortDir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $em->getRepository(Businessplan::class)->createQueryBuilder('b');

        if ($search && is_numeric($search)) {
            $qb->where('b.businessPlanID = :search')
               ->setParameter('search', $search);
        }

        $allowedSorts = ['businessPlanID', 'title', 'fundingRequired', 'status', 'creationDate'];
        if (in_array($sortBy, $allowedSorts)) {
            $qb->orderBy('b.' . $sortBy, $sortDir);
        }

        $businessPlans = $qb->getQuery()->getResult();

        if ($request->isXmlHttpRequest()) {
            return $this->render('BackOffice/startup/_businessplans_tbody.html.twig', [
                'businessPlans' => $businessPlans
            ]);
        }

        return $this->render('BackOffice/startup/businessplans.html.twig', [
            'businessPlans' => $businessPlans,
            'current_module' => 'startup',
            'current_menu' => 'businessplans'
        ]);
    }

    #[Route('/businessplans/{id}/edit', name: 'app_admin_startup_businessplan_edit', methods: ['GET', 'POST'])]
    public function editBusinessplan(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $businessPlan = $em->getRepository(Businessplan::class)->find($id);
        if (!$businessPlan) {
            return $this->redirectToRoute('app_admin_startup_businessplans');
        }

        if ($request->isMethod('POST')) {
            $businessPlan->setTitle($request->request->get('title'));
            $businessPlan->setFundingRequired((float) $request->request->get('fundingRequired'));
            $businessPlan->setTimeline($request->request->get('timeline'));
            $businessPlan->setStatus($request->request->get('status'));
            
            $em->flush();
            return $this->redirectToRoute('app_admin_startup_businessplans');
        }

        return $this->render('BackOffice/startup/edit_businessplan.html.twig', [
            'businessPlan' => $businessPlan,
            'current_module' => 'startup',
            'current_menu' => 'businessplans'
        ]);
    }

    #[Route('/businessplans/{id}/delete', name: 'app_admin_startup_businessplan_delete', methods: ['POST'])]
    public function deleteBusinessplan(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if ($redirect = $this->ensureAdmin($request)) return $redirect;

        $businessPlan = $em->getRepository(Businessplan::class)->find($id);
        if ($businessPlan) {
            $em->remove($businessPlan);
            $em->flush();
        }

        return $this->redirectToRoute('app_admin_startup_businessplans');
    }
}
