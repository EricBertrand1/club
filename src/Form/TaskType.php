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
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b
          ->add('name', TextType::class, ['label' => 'Nom de la tâche'])
          ->add('actors', EntityType::class, [
              'class' => User::class,
              'choice_label' => 'username', // adapte si tu préfères username
              'multiple' => true, 'expanded' => false,
              'label' => 'Acteurs'
          ])
          ->add('startDate', DateType::class, [
              'widget' => 'single_text', 'required' => false, 'label' => 'Début'
          ])
          ->add('endDate', DateType::class, [
              'widget' => 'single_text', 'required' => false, 'label' => 'Fin'
          ])
          ->add('hoursPlanned', IntegerType::class, ['label' => 'Heures prév.'])
          ->add('status', ChoiceType::class, [
              'label' => 'Statut',
              'choices' => [
                  'Non commencé' => Task::STATUS_NOT_STARTED,
                  'En cours'     => Task::STATUS_IN_PROGRESS,
                  'Terminé'      => Task::STATUS_DONE,
              ],
          ]);
    }

    public function configureOptions(OptionsResolver $r): void
    {
        $r->setDefaults(['data_class' => Task::class]);
    }
}
