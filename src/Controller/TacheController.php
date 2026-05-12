<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\Tache;
use App\Form\TacheType;
use App\Repository\TacheRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
    public function new(Request $request, EntityManagerInterface $em, MailerInterface $mailer, LoggerInterface $logger, FileUploader $fileUploader): Response
    {
        $tache = new Tache();
        
        // If projet_id is in query, set the projet
        if ($request->query->has('projet_id')) {
            $projet = $em->getRepository(Projet::class)->find($request->query->get('projet_id'));
            if ($projet) {
                $tache->setProjet($projet);
            }
        }

        $form = $this->createForm(TacheType::class, $tache, [
            'has_attachment' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $attachment */
            $attachment = $form->get('pieceJointeName')->getData();
            if ($attachment instanceof UploadedFile) {
                $tache->setPieceJointeName($fileUploader->upload($attachment));
            }

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
    public function edit(Tache $tache, Request $request, EntityManagerInterface $em, MailerInterface $mailer, FileUploader $fileUploader): Response
    {
        $originalAssignee = $tache->getAssignee();
        $originalAttachment = $tache->getPieceJointeName();
        $form = $this->createForm(TacheType::class, $tache, [
            'has_attachment' => $originalAttachment !== null,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $removeAttachment = $form->has('removeAttachment') && (bool) $form->get('removeAttachment')->getData();

            /** @var UploadedFile|null $attachment */
            $attachment = $form->get('pieceJointeName')->getData();

            if ($removeAttachment && $originalAttachment !== null) {
                $fileUploader->remove($originalAttachment);
                $tache->setPieceJointeName(null);
                $originalAttachment = null;
            }

            if ($attachment instanceof UploadedFile) {
                $newAttachment = $fileUploader->upload($attachment);
                $tache->setPieceJointeName($newAttachment);

                if ($originalAttachment && $originalAttachment !== $newAttachment) {
                    $fileUploader->remove($originalAttachment);
                }
            }

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
    public function delete(Tache $tache, Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        // CSRF token validation
        if (!$this->isCsrfTokenValid('delete' . $tache->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $fileUploader->remove($tache->getPieceJointeName());

        $projet = $tache->getProjet();
        $em->remove($tache);
        $em->flush();

        $this->addFlash('success', 'Tâche supprimée avec succès.');

        return $this->redirectToRoute('projet_detail', ['id' => $projet->getId()]);
    }
}
