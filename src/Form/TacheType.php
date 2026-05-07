<?php

namespace App\Form;

use App\Entity\Etiquette;
use App\Entity\Tache;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class TacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la tâche',
                'attr' => ['placeholder' => 'Entrez le titre de la tâche'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['placeholder' => 'Décrivez la tâche...', 'rows' => 5],
            ])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 'basse',
                    'Moyenne' => 'moyenne',
                    'Haute' => 'haute',
                    'Urgente' => 'urgente',
                ],
                'placeholder' => 'Sélectionnez une priorité',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'À faire' => 'a_faire',
                    'En cours' => 'en_cours',
                    'Terminée' => 'terminee',
                ],
                'placeholder' => 'Sélectionnez un statut',
            ])
            ->add('dateEcheance', DateType::class, [
                'label' => 'Date d\'échéance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('assignee', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'pseudo',
                'label' => 'Assigner à',
                'required' => false,
                'placeholder' => 'Sélectionnez un utilisateur',
            ])
            ->add('etiquettes', EntityType::class, [
                'class' => Etiquette::class,
                'choice_label' => 'nom',
                'label' => 'Étiquettes',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('pieceJointeName', FileType::class, [
                'label' => 'Pièce jointe',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/pdf',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier valide (PDF, DOCX ou image).',
                    ]),
                ],
                'attr' => ['accept' => '.pdf,.docx,image/*'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tache::class,
        ]);
    }
}
