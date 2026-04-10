<?php

namespace App\Controller;

use App\Entity\Users;
use App\Entity\MentorFavorites;
use App\Entity\Schedule;
use App\Entity\MentorEvaluations;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/mentorship')]
class MentorController extends AbstractController
{
    #[Route('/mentors', name: 'app_mentors_list')]
    public function listMentors(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $search = $request->query->get('q');
        $availableOnly = $request->query->get('available_only');
        
        $qb = $em->createQueryBuilder()
            ->select('u')
            ->from(Users::class, 'u')
            ->where('u.role = :role')
            ->setParameter('role', 'MENTOR');

        if ($search) {
            $qb->andWhere('u.fullName LIKE :search OR u.mentorExpertise LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        if ($availableOnly) {
            $qb->andWhere(
                $qb->expr()->exists(
                    $em->createQueryBuilder()
                       ->select('1')
                       ->from(Schedule::class, 's')
                       ->where('s.mentorID = u.id AND s.isBooked = false AND s.availableDate >= :today')
                       ->getDQL()
                )
            )->setParameter('today', new \DateTime('today'));
        }

        $mentors = $qb->getQuery()->getResult();
        
        $favoritesRepo = $em->getRepository(MentorFavorites::class);
        $favorites = $favoritesRepo->findBy(['entrepreneurID' => $userId]);
        $favoriteIds = array_map(fn($f) => $f->getMentorID(), $favorites);

        // Fetch aggregate ratings for fast UI loads
        $evaluations = $em->getRepository(MentorEvaluations::class)->findAll();
        $ratingsMap = [];
        foreach ($evaluations as $ev) {
            $mId = $ev->getMentorID();
            if (!isset($ratingsMap[$mId])) { $ratingsMap[$mId] = ['sum' => 0, 'count' => 0]; }
            $ratingsMap[$mId]['sum'] += $ev->getRating();
            $ratingsMap[$mId]['count']++;
        }
        $mentorRatings = [];
        foreach ($mentors as $m) {
            if (isset($ratingsMap[$m->getId()])) {
                $mentorRatings[$m->getId()] = [
                    'avg' => round($ratingsMap[$m->getId()]['sum'] / $ratingsMap[$m->getId()]['count'], 1),
                    'count' => $ratingsMap[$m->getId()]['count']
                ];
            } else {
                $mentorRatings[$m->getId()] = null;
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('FrontOffice/mentorship/_mentors_list_cards.html.twig', [
                'mentors' => $mentors,
                'favoriteIds' => $favoriteIds,
                'mentorRatings' => $mentorRatings
            ]);
        }

        return $this->render('FrontOffice/mentorship/mentors_list.html.twig', [
            'mentors' => $mentors,
            'favoriteIds' => $favoriteIds,
            'mentorRatings' => $mentorRatings,
            'search' => $search
        ]);
    }

    #[Route('/mentor/{id}', name: 'app_mentor_profile')]
    public function profile(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $mentor = $em->getRepository(Users::class)->findOneBy(['id' => $id, 'role' => 'MENTOR']);
        
        if (!$mentor) {
            throw $this->createNotFoundException('Mentor not found.');
        }

        // Fetch upcoming available schedule slots
        $qb = $em->createQueryBuilder()
            ->select('s')
            ->from(Schedule::class, 's')
            ->where('s.mentorID = :mentorId')
            ->andWhere('s.isBooked = false')
            ->andWhere('s.availableDate >= :today')
            ->setParameter('mentorId', $id)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('s.availableDate', 'ASC')
            ->addOrderBy('s.startTime', 'ASC');
            
        $schedule = $qb->getQuery()->getResult();

        $groupedSchedule = [
            'Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 
            'Thursday' => [], 'Friday' => [], 'Saturday' => [], 'Sunday' => []
        ];
        foreach ($schedule as $slot) {
            $dayName = $slot->getAvailableDate()->format('l');
            $groupedSchedule[$dayName][] = $slot;
        }
        
        $favorite = $em->getRepository(MentorFavorites::class)->findOneBy([
            'entrepreneurID' => $userId,
            'mentorID' => $id
        ]);

        $evaluations = $em->getRepository(MentorEvaluations::class)->findBy(['mentorID' => $id], ['createdAt' => 'DESC']);
        $totalRating = 0;
        foreach ($evaluations as $ev) { $totalRating += $ev->getRating(); }
        $avgRating = count($evaluations) > 0 ? round($totalRating / count($evaluations), 1) : 0;

        return $this->render('FrontOffice/mentorship/mentor_profile.html.twig', [
            'mentor' => $mentor,
            'groupedSchedule' => $groupedSchedule,
            'isFavorite' => $favorite !== null,
            'evaluations' => $evaluations,
            'avgRating' => $avgRating
        ]);
    }

    #[Route('/favorites', name: 'app_my_favorites')]
    public function myFavorites(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $favorites = $em->getRepository(MentorFavorites::class)->findBy(['entrepreneurID' => $userId]);
        $mentorIds = array_map(fn($f) => $f->getMentorID(), $favorites);
        
        $mentors = [];
        if (!empty($mentorIds)) {
            $qb = $em->createQueryBuilder()
                ->select('u')
                ->from(Users::class, 'u')
                ->where('u.id IN (:ids)')
                ->setParameter('ids', $mentorIds);
            $mentors = $qb->getQuery()->getResult();
        }

        // Fetch ratings identically for Favorites view
        $evaluations = $em->getRepository(MentorEvaluations::class)->findBy(['mentorID' => $mentorIds]);
        $ratingsMap = [];
        foreach ($evaluations as $ev) {
            $mId = $ev->getMentorID();
            if (!isset($ratingsMap[$mId])) { $ratingsMap[$mId] = ['sum' => 0, 'count' => 0]; }
            $ratingsMap[$mId]['sum'] += $ev->getRating();
            $ratingsMap[$mId]['count']++;
        }
        $mentorRatings = [];
        foreach ($mentors as $m) {
            if (isset($ratingsMap[$m->getId()])) {
                $mentorRatings[$m->getId()] = [
                    'avg' => round($ratingsMap[$m->getId()]['sum'] / $ratingsMap[$m->getId()]['count'], 1),
                    'count' => $ratingsMap[$m->getId()]['count']
                ];
            } else {
                $mentorRatings[$m->getId()] = null;
            }
        }

        return $this->render('FrontOffice/mentorship/favorites.html.twig', [
            'mentors' => $mentors,
            'favoriteIds' => $mentorIds,
            'mentorRatings' => $mentorRatings
        ]);
    }

    #[Route('/mentor/{id}/toggle-favorite', name: 'app_mentor_toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $favorite = $em->getRepository(MentorFavorites::class)->findOneBy([
            'entrepreneurID' => $userId,
            'mentorID' => $id
        ]);

        if ($favorite) {
            $em->remove($favorite);
            $action = 'removed';
        } else {
            $maxId = $em->createQueryBuilder()
                ->select('MAX(m.id)')
                ->from(MentorFavorites::class, 'm')
                ->getQuery()
                ->getSingleScalarResult();

            $favorite = new MentorFavorites();
            $favorite->setId(($maxId ?? 0) + 1);
            $favorite->setEntrepreneurID($userId);
            $favorite->setMentorID($id);
            $favorite->setCreatedAt(new \DateTime());
            $em->persist($favorite);
            $action = 'added';
        }
        
        $em->flush();

        return $this->json(['status' => 'success', 'action' => $action]);
    }
}
