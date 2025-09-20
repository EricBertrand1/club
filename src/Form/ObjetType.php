<?php
namespace App\Form;

use App\Entity\Objet;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ObjetType extends AbstractType
{
    public const CATEGORIES = [
        'Véhicules' => 'Véhicules',
        'Emploi' => 'Emploi',
        'Vacances' => 'Vacances',
        'Multimédia' => 'Multimédia',
        'Maison' => 'Maison',
        'Mode' => 'Mode',
        'Loisirs' => 'Loisirs',
        'Animaux' => 'Animaux',
        'Matériel professionnel' => 'Matériel professionnel',
        'Services' => 'Services',
        'Autres' => 'Autres',
        // Immobilier volontairement exclu
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titreObjet', TextType::class, ['label' => 'Titre'])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => self::CATEGORIES,
                'placeholder' => '— Choisir —'
            ])
            ->add('auteur', TextType::class, ['label' => 'Auteur'])
            ->add('prix', IntegerType::class, ['label' => 'Prix (écus)'])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 5]
            ])
            // uploads non mappés directement (on stocke le nom de fichier)
            ->add('image1', FileType::class, [
                'label' => 'Image 1',
                'mapped' => false, 'required' => false,
                'constraints' => [ new File(maxSize: '6M', mimeTypes: ['image/*']) ],
            ])
            ->add('image2', FileType::class, [
                'label' => 'Image 2',
                'mapped' => false, 'required' => false,
                'constraints' => [ new File(maxSize: '6M', mimeTypes: ['image/*']) ],
            ])
            ->add('image3', FileType::class, [
                'label' => 'Image 3',
                'mapped' => false, 'required' => false,
                'constraints' => [ new File(maxSize: '6M', mimeTypes: ['image/*']) ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Objet::class]);
    }
}
