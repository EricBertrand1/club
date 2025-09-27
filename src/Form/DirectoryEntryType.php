<?php

namespace App\Form;

use App\Entity\DirectoryEntry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DirectoryEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'placeholder' => 'Choisir…',
                'choices' => [
                    'Administration' => 'Administration',
                    'Agricole'       => 'Agricole',
                    'Détente'        => 'Détente',
                    'Formation'      => 'Formation',
                    'Information'    => 'Information',
                    'Politique'      => 'Politique',
                    'Religion'       => 'Religion',
                    'Services'       => 'Services',
                    'Autre'          => 'Autre',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'placeholder' => 'Choisir…',
                'choices' => ['Web' => 'Web', 'Localisé' => 'Localisé'],
            ])
            ->add('designation', TextType::class, ['label' => 'Désignation', 'required' => false])
            ->add('lastName',   TextType::class, ['label' => 'Nom',        'required' => false])
            ->add('firstName',  TextType::class, ['label' => 'Prénom',     'required' => false])
            ->add('address',    TextType::class, ['label' => 'Adresse',    'required' => false])
            ->add('postalCode', TextType::class, ['label' => 'Code postal','required' => false])
            ->add('city',       TextType::class, ['label' => 'Commune',    'required' => false])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site web',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4, 'placeholder' => 'Mots clés, horaires, prestations, notes…'],
            ])
            // On garde IntegerType pour rester simple côté entité,
            // et on gère les étoiles dans le template (pré-remplissage compris).
            ->add('rating', IntegerType::class, [
                'label' => 'Note (0–5)',
                'attr' => ['min' => 0, 'max' => 5, 'inputmode' => 'numeric'],
                'required' => false,
                // Surtout pas de empty_data ici, sinon la valeur est forcée à 0 en édition.
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DirectoryEntry::class]);
    }
}
