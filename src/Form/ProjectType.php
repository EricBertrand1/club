<?php

namespace App\Form;

use App\Entity\Project;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $o): void
    {
        $b->add('name', TextType::class, ['label' => 'Nom du projet']);
        // L’auteur est fixé côté contrôleur = user connecté (non exposé dans le formulaire)
    }
    public function configureOptions(OptionsResolver $r): void
    {
        $r->setDefaults(['data_class' => Project::class]);
    }
}
