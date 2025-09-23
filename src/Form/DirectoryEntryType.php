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
                    'Automobile' => 'Automobile',
                    'Alimentaire' => 'Alimentaire',
                    'Matériaux' => 'Matériaux',
                    'Outils' => 'Outils',
                    'Services' => 'Services',
                    'Autre' => 'Autre',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'placeholder' => 'Choisir…',
                'choices' => ['Web' => 'Web', 'Localisé' => 'Localisé'],
            ])
            ->add('designation', TextType::class, ['label' => 'Désignation', 'required' => false])
            ->add('lastName', TextType::class, ['label' => 'Nom', 'required' => false])
            ->add('firstName', TextType::class, ['label' => 'Prénom', 'required' => false])
            ->add('address', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('postalCode', TextType::class, ['label' => 'Code postal', 'required' => false])
            ->add('city', TextType::class, ['label' => 'Commune', 'required' => false])
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
            ->add('rating', IntegerType::class, [
                'label' => 'Note (0–5)',
                'attr' => ['min' => 0, 'max' => 5],
                'empty_data' => '0',   // ✅ évite null si l'input est vide
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DirectoryEntry::class]);
    }
}
