<?php

namespace App\Service;

final class DirectoryCategories
{
    /**
     * Liste canonique des catégories de l’annuaire.
     *
     * @return string[]
     */
    public function all(): array
    {
        return [
            'Administration',
            'Agricole',
            'Détente',
            'Formation',
            'Information',
            'Politique',
            'Religion',
            'Services',
            'Autre',
        ];
    }

    /**
     * Format prêt pour ChoiceType (label => value).
     *
     * @return array<string,string>
     */
    public function asChoices(): array
    {
        $all = $this->all();

        return array_combine($all, $all);
    }
}
