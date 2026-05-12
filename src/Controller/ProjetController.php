<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use App\Service\SearchService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projets')]
class ProjetController extends AbstractController
{
    #[Route('', name: 'projet_list', methods: ['GET'])]
    public function list(ProjetRepository $repository, SearchService $searchService, Request $request, PaginatorInterface $paginator): Response
    {
        $query = $request->query->get('q', '');
        $status = $request->query->get('statut');
        $sortBy = $request->query->get('sort', 'nom');
        $sortOrder = $request->query->get('order', 'asc');
        $recentProjectIds = $request->getSession()->get('recent_projects', []);
        $recentProjects = [];

        foreach ($recentProjectIds as $projectId) {
            $recentProject = $repository->find($projectId);
            if ($recentProject !== null) {
                $recentProjects[] = $recentProject;
            }
        }

        // Validate sort parameters to prevent injection
        $validSort = ['nom', 'dateCreation', 'dateLimite'];
        $sortBy = in_array($sortBy, $validSort) ? $sortBy : 'nom';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';

        // Search and sort projects
        $projets = $searchService->advancedProjetsSearch($query, $status, $sortBy, $sortOrder);
        $projetsPagination = $paginator->paginate(
            $projets,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('projet/list.html.twig', [
            'projets' => $projetsPagination,
            'query' => $query,
            'status' => $status,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'recentProjects' => $recentProjects,
        ]);
    }

    #[Route('/{id}', name: 'projet_detail', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function detail(Projet $projet, ProjetRepository $repository, Request $request, SearchService $searchService): Response
    {
        // Store recently viewed project in session (will be enhanced later)
        $session = $request->getSession();
        $recentProjectIds = $session->get('recent_projects', []);
        
        // Add current project to recent (max 5, FIFO, no duplicates)
        if (($key = array_search($projet->getId(), $recentProjectIds, true)) !== false) {
            unset($recentProjectIds[$key]);
        }
        array_unshift($recentProjectIds, $projet->getId());
        $recentProjectIds = array_slice($recentProjectIds, 0, 5);
        $session->set('recent_projects', $recentProjectIds);

        // Filter tasks based on query parameters
        $query = $request->query->get('q', '');
        $status = $request->query->get('statut');
        $priority = $request->query->get('priorite');
        $recentProjects = [];

        foreach ($recentProjectIds as $projectId) {
            $recentProject = $repository->find($projectId);
            if ($recentProject !== null) {
                $recentProjects[] = $recentProject;
            }
        }

        $taches = $searchService->searchTaches($query, $status, $priority, $projet->getId());

        return $this->render('projet/detail.html.twig', [
            'projet' => $projet,
            'taches' => $taches,
            'recentProjects' => $recentProjects,
        ]);
    }

    #[Route('/nouveau', name: 'projet_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CHEF_PROJET')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $projet = new Projet();
        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Auto-set creator
            $projet->setCreateur($this->getUser());
            
            $em->persist($projet);
            $em->flush();

            $this->addFlash('success', 'Projet créé avec succès.');

            return $this->redirectToRoute('projet_detail', ['id' => $projet->getId()]);
        }

        return $this->render('projet/form.html.twig', [
            'form' => $form,
            'projet' => $projet,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/modifier', name: 'projet_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Projet $projet, Request $request, EntityManagerInterface $em): Response
    {
        // Authorization check: only creator or admin can edit
        if ($projet->getCreateur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce projet.');
        }

        $form = $this->createForm(ProjetType::class, $projet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Projet modifié avec succès.');

            return $this->redirectToRoute('projet_detail', ['id' => $projet->getId()]);
        }

        return $this->render('projet/form.html.twig', [
            'form' => $form,
            'projet' => $projet,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'projet_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Projet $projet, Request $request, EntityManagerInterface $em): Response
    {
        // Authorization check: only creator or admin can delete
        if ($projet->getCreateur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce projet.');
        }

        // CSRF token validation
        if (!$this->isCsrfTokenValid('delete' . $projet->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $em->remove($projet);
        $em->flush();

        $this->addFlash('success', 'Projet supprimé avec succès.');

        return $this->redirectToRoute('projet_list');
    }
}
