<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/taches')]
class TacheController extends AbstractController
{
    #[Route('/{id}', name: 'tache_detail', methods: ['GET'])]
    public function detail(Tache $tache): Response
    {
        return $this->render('tache/detail.html.twig', [
            'tache' => $tache,
        ]);
    }

    #[Route('/nouveau', name: 'tache_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $tache = new Tache();
        
        // If projet_id is in query, set the projet
        if ($request->query->has('projet_id')) {
            $projet = $em->getRepository(Projet::class)->find($request->query->get('projet_id'));
            if ($projet) {
                $tache->setProjet($projet);
            }
        }

        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tache);
            $em->flush();

            $this->addFlash('success', 'Tâche créée avec succès.');

            return $this->redirectToRoute('tache_detail', ['id' => $tache->getId()]);
        }

        return $this->render('tache/form.html.twig', [
            'form' => $form,
            'tache' => $tache,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/modifier', name: 'tache_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Tache $tache, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Tâche modifiée avec succès.');

            return $this->redirectToRoute('tache_detail', ['id' => $tache->getId()]);
        }

        return $this->render('tache/form.html.twig', [
            'form' => $form,
            'tache' => $tache,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'tache_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Tache $tache, Request $request, EntityManagerInterface $em): Response
    {
        // CSRF token validation
        if (!$this->isCsrfTokenValid('delete' . $tache->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $projet = $tache->getProjet();
        $em->remove($tache);
        $em->flush();

        $this->addFlash('success', 'Tâche supprimée avec succès.');

        return $this->redirectToRoute('projet_detail', ['id' => $projet->getId()]);
    }
}
