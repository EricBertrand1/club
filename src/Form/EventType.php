<?php
// src/Form/EventType.php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('date', DateType::class, ['widget' => 'single_text'])
            ->add('heure', TimeType::class, ['widget' => 'single_text'])
            ->add('texteCourt', TextType::class)
            ->add('texteLong', TextareaType::class, ['required' => false])
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Réunion film' => 'reunion-film',
                    'Réunion jeu' => 'reunion-jeu',
                    'Réunion conférence' => 'reunion-conference',
                    'Réunion débat' => 'reunion-debat',
                    'Réunion organisation' => 'reunion-organisation',
                    'Réunion atelier' => 'reunion-atelier',
                    'Réunion divers' => 'reunion-divers',
                    'Chantier participatif' => 'chantier-participatif',
                    'Repas' => 'repas',
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
