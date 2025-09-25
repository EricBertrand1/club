<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Champs "classiques"
            ->add('name', TextType::class, [
                'label' => 'Nom de la tâche',
            ])
            ->add('actors', EntityType::class, [
                'label'        => 'Acteurs',
                'class'        => User::class,
                'choice_label' => 'username',
                'multiple'     => true,
                'required'     => false,
            ])
            ->add('startDate', DateType::class, [
                'label'    => 'Début',
                'widget'   => 'single_text',
                'required' => false,
            ])
            ->add('endDate', DateType::class, [
                'label'    => 'Fin',
                'widget'   => 'single_text',
                'required' => false,
            ])
            ->add('hoursPlanned', IntegerType::class, [
                'label' => 'Heures prév.',
                'attr'  => ['min' => 1],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'Non commencé' => Task::STATUS_NOT_STARTED,
                    'En cours'     => Task::STATUS_IN_PROGRESS,
                    'Terminé'      => Task::STATUS_DONE,
                ],
            ])

            // Avancement (%) — utilisé par le slider dans le template
            ->add('progressPercent', IntegerType::class, [
                'label'      => 'Avancement (%)',
                'required'   => true,
                'empty_data' => '0',
                'attr'       => ['min' => 0, 'max' => 100, 'step' => 10],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}
