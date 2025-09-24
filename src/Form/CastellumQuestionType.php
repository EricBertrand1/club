<?php
namespace App\Form;

use App\Entity\CastellumQuestion;
use App\Entity\CastellumSubcategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CastellumQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Champs existants / principaux
        $builder
            ->add('subcategory', EntityType::class, [
                'class' => CastellumSubcategory::class,
                'choice_label' => function (CastellumSubcategory $s) {
                    return sprintf('%s — %s', $s->getCode(), $s->getName());
                },
                'placeholder' => 'Sélectionner…',
                'label' => 'Sous-catégorie',
                'required' => true,
            ])
            ->add('levelQuestion', ChoiceType::class, [
                'label' => 'Niveau',
                'choices' => [
                    'Base'   => 'base',
                    'Avancé' => 'avancé',
                    'Expert' => 'expert',
                ],
                'placeholder' => '—',
                'required'   => true,
            ])
            ->add('questionType', TextType::class, [
                'required' => false,
                'label' => 'Type de question',
            ])
            ->add('subject', TextType::class, [
                'required' => false,
                'label' => 'Sujet',
            ])
            ->add('questionText', TextareaType::class, [
                'required' => false,
                'label' => 'Énoncé de la question',
                'attr' => ['rows' => 6],
            ])
            ->add('answerText', TextareaType::class, [
                'required' => false,
                'label' => 'Réponse attendue',
                'attr' => ['rows' => 6],
            ])
            ->add('explanation', TextareaType::class, [
                'required' => false,
                'label' => 'Explication',
                'attr' => ['rows' => 4],
            ])
            // Image principale (chemin texte + upload + suppression)
            ->add('questionImage', TextType::class, [
                'required' => false,
                'label' => 'Image (chemin public ou URL)',
            ])
            ->add('questionImageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Téléverser une image (remplace l’existante)',
            ])
            ->add('removeImage', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Supprimer l’image existante',
            ]);

        // Nouveaux champs — Paramètres & formation
        $builder
            ->add('durationSeconds', IntegerType::class, [
                'required' => false,
                'label' => 'Durée max. (secondes)',
            ])
            ->add('formationChapter', TextType::class, [
                'required' => false,
                'label' => 'Formation — Chapitre',
            ])
            ->add('formationParagraph', TextType::class, [
                'required' => false,
                'label' => 'Formation — Paragraphe',
            ]);

        // Coordonnées (X/Y)
        $builder
            ->add('coordX', IntegerType::class, [
                'required' => false,
                'label' => 'Coordonnée X',
            ])
            ->add('coordY', IntegerType::class, [
                'required' => false,
                'label' => 'Coordonnée Y',
            ]);

        // Audio (upload uniquement)
        $builder->add('questionAudioFile', FileType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Fichier son (question)',
        ]);

        // QCM — Réponses texte 1..10
        for ($i = 1; $i <= 10; $i++) {
            $builder->add('qcmText'.$i, TextType::class, [
                'required' => false,
                'label' => 'Réponse texte QCM '.$i,
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            $builder->add('qcmImage'.$i, TextType::class, [
                'required' => false,
                'label' => 'Réponse image QCM '.$i.' (chemin public ou URL)',
            ]);
        }

        // QCM — Réponses image 1..10 (uploads, non mappés)
        for ($i = 1; $i <= 10; $i++) {
            $builder->add('qcmImageFile'.$i, FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Réponse image QCM '.$i,
            ]);
        }

        // Validations
        $builder
            ->add('validationSignataire1', TextType::class, [
                'required' => false,
                'label' => 'Validation — Signataire 1',
            ])
            ->add('validationSignataire2', TextType::class, [
                'required' => false,
                'label' => 'Validation — Signataire 2',
            ])
            ->add('validationSignataire3', TextType::class, [
                'required' => false,
                'label' => 'Validation — Signataire 3',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CastellumQuestion::class,
        ]);
    }
}
