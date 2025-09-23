<?php
namespace App\Form;

use App\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ReportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'Que voulez-vous faire ?',
                'placeholder' => 'Choisir…',
                'choices' => [
                    'Signaler un contenu inaproprié' => 'Signaler un contenu inaproprié',
                    'Signaler un bug' => 'Signaler un bug',
                    'Suggérer une amélioration du site' => 'Suggérer une amélioration du site',
                ],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('context', ChoiceType::class, [
                'label' => 'Contexte',
                'placeholder' => 'Choisir…',
                'choices' => [
                    'Général' => 'Général',
                    'Agenda' => 'Agenda',
                    'Annuaire' => 'Annuaire',
                    'Astuces' => 'Astuces',
                    'Boutique' => 'Boutique',
                    'Castellum' => 'Castellum',
                    'Chants' => 'Chants',
                    'Coaching' => 'Coaching',
                    'Formation' => 'Formation',
                    'Projets' => 'Projets',
                    'Production' => 'Production',
                    'Annonces' => 'Annonces',
                    'Services' => 'Services',
                    'Autre' => 'Autre',
                ],
                'constraints' => [new Assert\NotBlank()],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 6, 'placeholder' => 'Décrivez le problème, le bug ou votre suggestion…'],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 5]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
        ]);
    }
}
