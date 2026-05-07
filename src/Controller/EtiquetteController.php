<?php

namespace App\Controller;

use App\Entity\Etiquette;
use App\Form\EtiquetteType;
use App\Repository\EtiquetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etiquettes')]
#[IsGranted('ROLE_ADMIN')]
class EtiquetteController extends AbstractController
{
    #[Route('', name: 'etiquette_list', methods: ['GET'])]
    public function list(EtiquetteRepository $repository): Response
    {
        $etiquettes = $repository->findAll();

        return $this->render('etiquette/list.html.twig', [
            'etiquettes' => $etiquettes,
        ]);
    }

    #[Route('/nouveau', name: 'etiquette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $etiquette = new Etiquette();
        $form = $this->createForm(EtiquetteType::class, $etiquette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($etiquette);
            $em->flush();

            $this->addFlash('success', 'Étiquette créée avec succès.');

            return $this->redirectToRoute('etiquette_list');
        }

        return $this->render('etiquette/form.html.twig', [
            'form' => $form,
            'etiquette' => $etiquette,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'etiquette_delete', methods: ['POST'])]
    public function delete(Etiquette $etiquette, Request $request, EntityManagerInterface $em): Response
    {
        // CSRF token validation
        if (!$this->isCsrfTokenValid('delete' . $etiquette->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $em->remove($etiquette);
        $em->flush();

        $this->addFlash('success', 'Étiquette supprimée avec succès.');

        return $this->redirectToRoute('etiquette_list');
    }
}
