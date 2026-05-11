<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/taches')]
class TacheController extends AbstractController
{
    #[Route('/{id}', name: 'tache_detail', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function detail(Tache $tache): Response
    {
        return $this->render('tache/detail.html.twig', [
            'tache' => $tache,
        ]);
    }

    #[Route('/nouveau', name: 'tache_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger): Response
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

            if ($tache->getAssignee() !== null) {
                $mailer->send(
                    (new TemplatedEmail())
                        ->from('noreply@taskflow.com')
                        ->to($tache->getAssignee()->getEmail())
                        ->subject('TaskFlow - Nouvelle tâche assignée')
                        ->htmlTemplate('emails/tache_assignee.html.twig')
                        ->context([
                            'tache' => $tache,
                            'assignee' => $tache->getAssignee(),
                        ])
                );
            }

            $this->addFlash('success', 'Tâche créée avec succès.');

            return $this->redirectToRoute('tache_detail', ['id' => $tache->getId()]);
        }

        // If submitted but invalid, log errors for debugging
        if ($form->isSubmitted() && !$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $origin = $error->getOrigin();
                $field = $origin ? $origin->getName() : 'form';
                $logger->warning(sprintf('Tache form error on field "%s": %s', $field, $error->getMessage()));
            }
        }

        return $this->render('tache/form.html.twig', [
            'form' => $form,
            'tache' => $tache,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/modifier', name: 'tache_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Tache $tache, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $originalAssignee = $tache->getAssignee();
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $newAssignee = $tache->getAssignee();
            if ($newAssignee !== null && $newAssignee !== $originalAssignee) {
                $mailer->send(
                    (new TemplatedEmail())
                        ->from('noreply@taskflow.com')
                        ->to($newAssignee->getEmail())
                        ->subject('TaskFlow - Tâche mise à jour et assignée')
                        ->htmlTemplate('emails/tache_assignee.html.twig')
                        ->context([
                            'tache' => $tache,
                            'assignee' => $newAssignee,
                        ])
                );
            }

            $this->addFlash('success', 'Tâche modifiée avec succès.');

            return $this->redirectToRoute('tache_detail', ['id' => $tache->getId()]);
        }

        return $this->render('tache/form.html.twig', [
            'form' => $form,
            'tache' => $tache,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'tache_delete', methods: ['POST'], requirements: ['id' => '\\d+'])]
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
