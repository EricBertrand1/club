<?php
namespace App\Form;

use App\Entity\CastellumQuestion;
use App\Entity\CastellumSubcategory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CastellumQuestionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
        ->add('subcategory', EntityType::class, [
            'class' => CastellumSubcategory::class,
            'choice_label' => fn(CastellumSubcategory $s) => $s->getCode().' — '.$s->getName(),
            'label' => 'Sous-catégorie',
        ])
        ->add('levelQuestion', ChoiceType::class, [
            'label' => 'Niveau',
            'choices' => ['Base'=>'base','Avancé'=>'avancé','Expert'=>'expert'],
        ])
        ->add('subject', TextType::class, ['label' => 'Sujet', 'required' => false])
        ->add('questionType', TextType::class, ['label' => 'Type de question', 'empty_data' => 'QCM'])
        ->add('questionText', TextareaType::class, ['label' => 'Texte de la question','attr'=>['rows'=>5]])
        // URL/chemin texte (optionnel). On le garde pour les images externes.
        ->add('questionImage', TextType::class, ['label' => 'Image (URL/chemin)','required' => false])
        // ✨ Upload fichier (non mappé)
        ->add('questionImageFile', FileType::class, [
            'label' => 'Uploader une image',
            'mapped' => false,
            'required' => false,
            'constraints' => [new File([
                'maxSize' => '4M',
                'mimeTypes' => ['image/*'],
                'mimeTypesMessage' => 'Téléversez une image valide (max 4 Mo).',
            ])],
        ])
        // ✨ Supprimer l’image existante
        ->add('removeImage', CheckboxType::class, [
            'label' => 'Supprimer l’image actuelle',
            'mapped' => false,
            'required' => false,
        ])
        ->add('answerText', TextareaType::class, ['label' => 'Texte de la réponse','attr'=>['rows'=>4]])
        ->add('explanation', TextareaType::class, [
            'label' => 'Explication','required' => false,'attr'=>['rows'=>4],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CastellumQuestion::class]);
    }
}
