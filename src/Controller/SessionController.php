<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\SessionNotes;
use App\Entity\SessionTodos;
use App\Entity\MentorEvaluations;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/mentorship')]
class SessionController extends AbstractController
{
    #[Route('/sessions', name: 'app_my_sessions')]
    public function mySessions(Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Mentorship features.');
            return $this->redirectToRoute('app_home');
        }

        $role = $userRole;

        if ($role === 'MENTOR') {
            $sessionsRaw = $em->getRepository(Session::class)->findBy(['mentorID' => $userId], ['sessionDate' => 'DESC']);
        } else {
            $sessionsRaw = $em->getRepository(Session::class)->findBy(['entrepreneurID' => $userId], ['sessionDate' => 'DESC']);
        }

        $sessions = [];
        foreach ($sessionsRaw as $s) {
            $otherId = $role === 'MENTOR' ? $s->getEntrepreneurID() : $s->getMentorID();
            $otherUser = $em->getRepository(Users::class)->find($otherId);
            $sessions[] = [
                'session' => $s,
                'counterpartName' => $otherUser ? $otherUser->getFullName() : 'Unknown'
            ];
        }

        return $this->render('FrontOffice/mentorship/sessions.html.twig', [
            'sessions' => $sessions,
            'role' => $role
        ]);
    }

    #[Route('/session/{id}', name: 'app_session_details')]
    public function sessionDetails(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Mentorship features.');
            return $this->redirectToRoute('app_home');
        }

        $role = $userRole;

        $session = $em->getRepository(Session::class)->find($id);
        if (!$session || ($session->getMentorID() !== $userId && $session->getEntrepreneurID() !== $userId)) {
            throw $this->createNotFoundException('Session not found or forbidden.');
        }

        // Add note handling
        if ($request->isMethod('POST') && $request->request->has('note_content')) {
            if ($role === 'MENTOR') {
                $maxId = $em->createQueryBuilder()
                    ->select('MAX(sn.noteID)')
                    ->from(SessionNotes::class, 'sn')
                    ->getQuery()
                    ->getSingleScalarResult();

                $note = new SessionNotes();
                $note->setNoteID(($maxId ?? 0) + 1);
                $note->setSessionID($id);
                $note->setEntrepreneurID($session->getEntrepreneurID());
                $note->setNotes($request->request->get('note_content'));
                $note->setNoteDate(new \DateTime());
                
                $errors = $validator->validate($note);
                if (count($errors) > 0) {
                    $this->addFlash('error', 'Note validation failed: ' . $errors[0]->getMessage());
                    return $this->redirectToRoute('app_session_details', ['id' => $id]);
                }
                
                $em->persist($note);
                $em->flush();
            }
            return $this->redirectToRoute('app_session_details', ['id' => $id]);
        }

        // Add todo handling
        if ($request->isMethod('POST') && $request->request->has('todo_content')) {
            if ($role === 'MENTOR') {
                $maxId = $em->createQueryBuilder()
                    ->select('MAX(st.id)')
                    ->from(SessionTodos::class, 'st')
                    ->getQuery()
                    ->getSingleScalarResult();

                $todo = new SessionTodos();
                $todo->setId(($maxId ?? 0) + 1);
                $todo->setSessionID($id);
                $todo->setTaskDescription($request->request->get('todo_content'));
                $todo->setIsDone(false);
                $todo->setCreatedAt(new \DateTime());
                
                $errors = $validator->validate($todo);
                if (count($errors) > 0) {
                    $this->addFlash('error', 'Todo validation failed: ' . $errors[0]->getMessage());
                    return $this->redirectToRoute('app_session_details', ['id' => $id]);
                }
                
                $em->persist($todo);
                $em->flush();
            }
            return $this->redirectToRoute('app_session_details', ['id' => $id]);
        }

        $notes = $em->getRepository(SessionNotes::class)->findBy(['sessionID' => $id], ['noteDate' => 'DESC']);
        $todos = $em->getRepository(SessionTodos::class)->findBy(['sessionID' => $id], ['id' => 'ASC']);

        // Fetch partner details
        $partnerId = $role === 'MENTOR' ? $session->getEntrepreneurID() : $session->getMentorID();
        $partner = $em->getRepository(Users::class)->find($partnerId);

        return $this->render('FrontOffice/mentorship/session_details.html.twig', [
            'sessionObj' => $session,
            'notes' => $notes,
            'todos' => $todos,
            'role' => $role,
            'partner' => $partner
        ]);
    }

    #[Route('/session/todo/{id}/toggle', name: 'app_session_todo_toggle', methods: ['POST'])]
    public function toggleTodo(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            return $this->json(['status' => 'error', 'message' => 'Access Denied: Evaluators are not allowed to access Mentorship features.'], 403);
        }
        
        if ($userRole !== 'ENTREPRENEUR') {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $todo = $em->getRepository(SessionTodos::class)->find($id);
        if (!$todo) return $this->json(['status' => 'error'], 404);

        $session = $em->getRepository(Session::class)->find($todo->getSessionID());
        if ($session->getEntrepreneurID() !== $userId) return $this->json(['status' => 'error'], 403);

        $isDone = filter_var($request->request->get('isDone'), FILTER_VALIDATE_BOOLEAN);
        $todo->setIsDone($isDone);
        $em->flush();

        return $this->json(['status' => 'success']);
    }

    #[Route('/session/todo/{id}/edit', name: 'app_session_todo_edit', methods: ['POST'])]
    public function editTodo(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        if (!$userId || $role !== 'MENTOR') {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $todo = $em->getRepository(SessionTodos::class)->find($id);
        if (!$todo) return $this->json(['status' => 'error'], 404);

        $session = $em->getRepository(Session::class)->find($todo->getSessionID());
        if ($session->getMentorID() !== $userId) return $this->json(['status' => 'error'], 403);

        $newDesc = $request->request->get('description');
        if ($newDesc) {
            $todo->setTaskDescription($newDesc);
            $em->flush();
        }

        return $this->json(['status' => 'success']);
    }

    #[Route('/session/todo/{id}/delete', name: 'app_session_todo_delete', methods: ['POST'])]
    public function deleteTodo(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $userId = $request->getSession()->get('user_id');
        $role = $request->getSession()->get('user_role');
        if (!$userId || $role !== 'MENTOR') {
            return $this->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $todo = $em->getRepository(SessionTodos::class)->find($id);
        if (!$todo) return $this->json(['status' => 'error'], 404);

        $session = $em->getRepository(Session::class)->find($todo->getSessionID());
        if ($session->getMentorID() !== $userId) return $this->json(['status' => 'error'], 403);

        $em->remove($todo);
        $em->flush();

        return $this->json(['status' => 'success']);
    }

    #[Route('/session/{id}/feedback', name: 'app_session_feedback', methods: ['POST'])]
    public function submitFeedback(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $userId = $request->getSession()->get('user_id');
        $userRole = $request->getSession()->get('user_role');
        
        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }
        
        if (strtoupper($userRole) === 'EVALUATOR') {
            $this->addFlash('error', 'Access Denied: Evaluators are not allowed to access Mentorship features.');
            return $this->redirectToRoute('app_home');
        }
        
        if ($userRole !== 'ENTREPRENEUR') {
            return $this->redirectToRoute('app_login');
        }

        $session = $em->getRepository(Session::class)->find($id);
        if (!$session || $session->getEntrepreneurID() !== $userId) {
            return $this->json(['status' => 'error'], 404);
        }

        $rating = (int) $request->request->get('rating');
        $comment = $request->request->get('comment');

        if ($rating > 0 && $rating <= 5) {
            $maxId = $em->createQueryBuilder()
                ->select('MAX(e.id)')
                ->from(MentorEvaluations::class, 'e')
                ->getQuery()
                ->getSingleScalarResult();

            $evaluation = new MentorEvaluations();
            $evaluation->setId(($maxId ?? 0) + 1);
            $evaluation->setEntrepreneurID($userId);
            $evaluation->setMentorID($session->getMentorID());
            $evaluation->setSessionID($session->getSessionID());
            $evaluation->setRating($rating);
            $evaluation->setComment($comment);
            $evaluation->setCreatedAt(new \DateTime());
            
            $errors = $validator->validate($evaluation);
            if (count($errors) > 0) {
                $this->addFlash('error', 'Feedback validation failed: ' . $errors[0]->getMessage());
                return $this->redirectToRoute('app_session_details', ['id' => $id]);
            }
            
            $em->persist($evaluation);
            $em->flush();
            $this->addFlash('success', 'Feedback submitted successfully!');
        }

        return $this->redirectToRoute('app_session_details', ['id' => $id]);
    }
}
