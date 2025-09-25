<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use App\Form\ProjectType;
use App\Form\TaskType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[IsGranted('ROLE_USER')]
#[Route('/projets')]
class ProjetsController extends AbstractController
{
    #[Route('', name: 'projets_index', methods: ['GET'])]
    public function index(ProjectRepository $repo): Response
    {
        $all = $repo->findAllWithTasks();
        [$mine, $others] = $repo->splitMineFirst($all, $this->getUser());

        return $this->render('projets/index.html.twig', [
            'projectsMine' => $mine,
            'projectsOthers' => $others,
        ]);
    }

    #[Route('/new', name: 'projets_new', methods: ['GET','POST'])]
    public function new(Request $req, EntityManagerInterface $em): Response
    {
        $p = new Project();
        $p->setAuthor($this->getUser());
        $form = $this->createForm(ProjectType::class, $p);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($p); $em->flush();
            $this->addFlash('success','Projet créé.');
            return $this->redirectToRoute('projets_index');
        }

        return $this->render('projets/form_project.html.twig', ['form'=>$form->createView(), 'mode'=>'new']);
    }

    #[Route('/{id}/edit', name: 'projets_edit', methods: ['GET','POST'])]
    public function edit(Project $p, Request $req, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProjectType::class, $p);
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success','Projet mis à jour.');
            return $this->redirectToRoute('projets_index');
        }
        return $this->render('projets/form_project.html.twig', ['form'=>$form->createView(), 'mode'=>'edit', 'project'=>$p]);
    }

    #[Route('/{id}/delete', name: 'projets_delete', methods: ['POST'])]
    public function delete(Project $p, EntityManagerInterface $em, Request $req): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($this->isCsrfTokenValid('delete_project_'.$p->getId(), $req->request->get('_token'))) {
            $em->remove($p); $em->flush();
            $this->addFlash('success','Projet supprimé.');
        }
        return $this->redirectToRoute('projets_index');
    }

    #[Route('/{id}/task/new', name: 'tasks_new', methods: ['GET','POST'])]
    public function taskNew(Project $p, Request $req, EntityManagerInterface $em): Response
    {
        $t = new Task();
        $t->setProject($p);
        // position = fin de ligne
        $t->setPosition(max(1, $p->getTasks()->count()+1));
        $form = $this->createForm(TaskType::class, $t);
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($t); $em->flush();
            $this->addFlash('success','Tâche ajoutée.');
            return $this->redirectToRoute('projets_index');
        }
        return $this->render('projets/form_task.html.twig', ['form'=>$form->createView(), 'mode'=>'new', 'project'=>$p]);
    }

    #[Route('/task/{id}/edit', name: 'tasks_edit', methods: ['GET','POST'])]
    public function taskEdit(Task $t, Request $req, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $t);
        $form->handleRequest($req);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success','Tâche mise à jour.');
            return $this->redirectToRoute('projets_index');
        }
        return $this->render('projets/form_task.html.twig', ['form'=>$form->createView(), 'mode'=>'edit', 'task'=>$t]);
    }

    #[Route('/task/{id}/delete', name: 'tasks_delete', methods: ['POST'])]
    public function taskDelete(Task $t, EntityManagerInterface $em, Request $req): Response
    {
        if ($this->isCsrfTokenValid('delete_task_'.$t->getId(), $req->request->get('_token'))) {
            $em->remove($t); $em->flush();
            $this->addFlash('success','Tâche supprimée.');
        }
        return $this->redirectToRoute('projets_index');
    }

    /** Drag&Drop: nouvel ordre des tâches d’un projet */
    #[Route('/reorder', name: 'tasks_reorder', methods: ['POST'])]
    public function reorder(Request $req, TaskRepository $taskRepo, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($req->getContent(), true);
        $projectId = (int)($data['projectId'] ?? 0);
        $order = $data['order'] ?? []; // array de taskId dans le nouvel ordre

        if (!$projectId || !is_array($order)) return new JsonResponse(['ok'=>false], 400);

        $pos = 1;
        foreach ($order as $taskId) {
            $task = $taskRepo->find((int)$taskId);
            if ($task && $task->getProject() && $task->getProject()->getId() === $projectId) {
                $task->setPosition($pos++);
            }
        }
        $em->flush();
        return new JsonResponse(['ok'=>true]);
    }
}
