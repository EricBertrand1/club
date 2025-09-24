<?php
namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Rubrique;
use App\Entity\CoachingTheme;
use App\Entity\CastellumSubcategory;
use App\Entity\CastellumQuestion;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $em): void
    {
        // 1) Admin
        $admin = (new User())
            ->setUsername('admin')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $em->persist($admin);

        // 2) Rubriques + 5 thèmes vides par rubrique (Corps/Esprit/Intellect/Habitudes)
        $rubriques = [
            ['Corps', 'images/menu/formation.jpg'],
            ['Esprit', null],
            ['Intellect & Savoir faire', null],
            ['Habitudes perso', null],
        ];

        foreach ($rubriques as [$label, $img]) {
            $r = (new Rubrique())
                ->setLabel($label)
                ->setImg($img)
                ->setUser($admin);
            $em->persist($r);

            for ($i=1; $i<=5; $i++) {
                $t = (new CoachingTheme())
                    ->setUser($admin)
                    ->setRubrique($r)
                    ->setLabel($label.' — Thème '.$i)   // modifiable plus tard par l’utilisateur
                    ->setCoefficient(0)                 // -3..+3
                    ->setPosition($i);                  // pour l’ordre affiché
                $em->persist($t);
            }
        }

        // 3) Castellum : quelques sous-catégories d’exemple
        foreach ([
            ['600', 'Les bases de la radio'],
            ['600', 'Les bases de l\'informatique'],
            ['400', 'Conjugaison'],
            ['400', 'Expressions françaises'],
        ] as [$code, $name]) {
            $s = (new CastellumSubcategory())
                ->setCode($code)->setName($name);
            $em->persist($s);
        }

        // (optionnel) 1 question d’exemple
        $sampleSub = $em->getRepository(CastellumSubcategory::class)->findOneBy(['name'=>'Les bases de la radio']);
        if ($sampleSub) {
            $q = (new CastellumQuestion())
                ->setSubcategory($sampleSub)
                ->setCategoryCode($sampleSub->getCode())
                ->setLevelQuestion('base')
                ->setSubject('Ondes')
                ->setQuestionType('texte')
                ->setQuestionText('Quelle est l’unité de fréquence ?')
                ->setAnswerText('hertz;Hz')
                ->setExplanation('On utilise le hertz, noté Hz.')
                ->touch();
            $em->persist($q);
        }

        $em->flush();
    }
}
