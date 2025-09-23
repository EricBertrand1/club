<?php
// src/Form/CoachingSettingsType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class CoachingSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach (['corps','esprit','intellect','habitudes'] as $section) {
            $builder->add($section, CollectionType::class, [
                'entry_type' => CoachingThemeType::class,
                'allow_add' => false,
                'allow_delete' => false,
                'label' => ucfirst($section),
            ]);
        }
    }
}
