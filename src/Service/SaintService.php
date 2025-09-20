<?php
namespace App\Service;

final class SaintService
{
    /** 
     * Tableau statique "mois" => [jour => libellé du saint/fête]
     * Clé mois sur 2 chiffres, clé jour en int.
     * ➜ Complète/édite librement selon ton calendrier.
     */
    private const SAINTS = [
        '01' => [
            1  => "Sainte Marie, Mère de Dieu",
            2  => "Saint Basile le Grand et saint Grégoire de Nazianze",
            3  => "Sainte Geneviève",
            4  => "Saint Odilon",
            5  => "Saint Édouard",
            6  => "Épiphanie du Seigneur",
            7  => "Saint Raymond de Peñafort",
            8  => "Saint Lucien",
            9  => "Sainte Alix",
            10 => "Saint Guillaume",
            // ...
        ],
        '02' => [
            2  => "Présentation du Seigneur (Chandeleur)",
            3  => "Saint Blaise",
            14 => "Saint Valentin",
            // 29 => "—" // année bissextile : à définir si besoin
        ],
        '03' => [
            17 => "Saint Patrice",
            19 => "Saint Joseph",
            25 => "Annonciation du Seigneur",
            // ...
        ],
        '04' => [
            23 => "Saint Georges",
            29 => "Sainte Catherine de Sienne",
            // ...
        ],
        '05' => [
            1  => "Saint Joseph, travailleur",
            26 => "Saint Philippe et saint Jacques",
            // ...
        ],
        '06' => [
            13 => "Saint Antoine de Padoue",
            24 => "Nativité de saint Jean-Baptiste",
            29 => "Saint Pierre et saint Paul",
            // ...
        ],
        '07' => [
            22 => "Sainte Marie-Madeleine",
            25 => "Saint Jacques",
            // ...
        ],
        '08' => [
            10 => "Saint Laurent",
            15 => "Assomption de la Vierge Marie",
            // ...
        ],
        '09' => [
            21 => "Saint Matthieu",
            29 => "Saint Michel, saint Gabriel et saint Raphaël (archanges)",
            // ...
        ],
        '10' => [
            4  => "Saint François d’Assise",
            15 => "Sainte Thérèse d’Avila",
            // ...
        ],
        '11' => [
            1  => "Toussaint",
            2  => "Commémoration des fidèles défunts",
            11 => "Saint Martin",
            30 => "Saint André",
            // ...
        ],
        '12' => [
            6  => "Saint Nicolas",
            8  => "Immaculée Conception",
            13 => "Sainte Lucie",
            25 => "Nativité du Seigneur (Noël)",
            26 => "Saint Étienne",
            27 => "Saint Jean, apôtre",
            28 => "Saints Innocents",
            31 => "Saint Sylvestre",
        ],
    ];

    public function getSaintForDate(\DateTimeInterface $date): ?string
    {
        $month = $date->format('m');
        $day   = (int) $date->format('j');

        return self::SAINTS[$month][$day] ?? null;
    }
}
