<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projets')]
class ProjetController extends AbstractController
{
    #[Route('', name: 'projet_list', methods: ['GET'])]
    public function list(ProjetRepository $repository, Request $request): Response
    {
        // Fetch all projects (pagination will be added later with KnpPaginator)
        $projets = $repository->findAll();

        return $this->render('projet/list.html.twig', [
            'projets' => $projets,
        ]);
    }

    #[Route('/{id}', name: 'projet_detail', methods: ['GET'])]
    public function detail(Projet $projet, Request $request): Response
    {
        // Store recently viewed project in session (will be enhanced later)
        $session = $request->getSession();
        $recentProjects = $session->get('recent_projects', []);
        
        // Add current project to recent (max 5, FIFO, no duplicates)
        if (($key = array_search($projet->getId(), $recentProjects)) !== false) {
            unset($recentProjects[$key]);
        }
        array_unshift($recentProjects, $projet->getId());
        $recentProjects = array_slice($recentProjects, 0, 5);
        $session->set('recent_projects', $recentProjects);

        $taches = $projet->getTaches();

        return $this->render('projet/detail.html.twig', [
            'projet' => $projet,
            'taches' => $taches,
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

    #[Route('/{id}/modifier', name: 'projet_edit', methods: ['GET', 'POST'])]
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

    #[Route('/{id}/supprimer', name: 'projet_delete', methods: ['POST'])]
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
