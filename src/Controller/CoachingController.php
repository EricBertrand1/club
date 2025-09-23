<?php
namespace App\Controller;

use App\Entity\UserCheck;
use App\Entity\UserTheme;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use IntlDateFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CoachingController extends AbstractController
{
    // rubriques en dur (clé technique => libellé d’affichage)
    private const SECTIONS = [
        'corps'     => 'Corps',
        'esprit'    => 'Esprit',
        'intellect' => 'Intellect & Savoir-faire',
        'habitudes' => 'Habitudes perso',
    ];

    private const DEFAULTS = [
        'corps'     => ['Lever prompt', "Coucher à l'heure", 'Sport', null, null],
        'esprit'    => ['Effort spirituel', 'Rangement', 'Objectif du jour atteint', 'Objectif du lendemain planifié', 'Succumber'],
        'intellect' => ["Apprendre quelque chose", "Réviser quelque chose", "Lecture ou exercice mental", null, null],
        'habitudes' => ['Piano', 'Étude du cerveau', 'Informatique/Élec/Radio', null, null],
    ];

    private function todayFr(): string
    {
        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, null, null, 'EEEE d MMMM yyyy');
        return $fmt->format(new DateTimeImmutable());
    }

    private function ensureThemesExist(EntityManagerInterface $em): void
    {
        $user = $this->getUser();
        if (!$user) { return; }

        $repo = $em->getRepository(UserTheme::class);

        foreach (self::SECTIONS as $key => $label) {
            for ($pos = 1; $pos <= 5; $pos++) {
                $existing = $repo->findOneBy(['user' => $user, 'section' => $key, 'position' => $pos]);
                if (!$existing) {
                    $t = (new UserTheme())
                        ->setUser($user)
                        ->setSection($key)
                        ->setPosition($pos)
                        ->setLabel(self::DEFAULTS[$key][$pos-1] ?? null)
                        ->setCoefficient(0);
                    $em->persist($t);
                }
            }
        }
        $em->flush();
    }

    #[Route('/coaching', name: 'coaching_index', methods: ['GET','POST'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } else {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }
        $this->ensureThemesExist($em);

        $user = $this->getUser();
        $themeRepo = $em->getRepository(UserTheme::class);
        $checkRepo = $em->getRepository(UserCheck::class);

        $today = new DateTimeImmutable('today');

        // Récupère tous les thèmes de l’utilisateur, groupés par section/position
        $themes = $themeRepo->findBy(['user' => $user], ['section' => 'ASC', 'position' => 'ASC']);

        // POST : sauvegarde (checkboxes + MAJ des coefficients si modifiés)
        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('coaching_save', $token)) {
                throw $this->createAccessDeniedException('CSRF invalide');
            }

            // Récupère les sous-tableaux
            $checks = $request->request->all('check') ?? [];
            $coefs  = $request->request->all('coef') ?? [];

            foreach ($themes as $t) {
                // MAJ coefficient persistant (-3..3)
                if (isset($coefs[$t->getId()])) {
                    $c = max(-3, min(3, (int) $coefs[$t->getId()]));
                    $t->setCoefficient($c);
                }

                // MAJ du check du jour
                $uc = $checkRepo->findOneBy([
                    'user'  => $user,
                    'day'   => $today,
                    'theme' => $t,
                ]) ?? (new UserCheck())->setUser($user)->setDay($today)->setTheme($t);

                $uc->setChecked(isset($checks[$t->getId()]));
                $em->persist($uc);
            }

            $em->flush();

            // Redirection selon le bouton
            $action = $request->request->get('action');
            if ($action === 'save_and_quit') {
                // route d'accueil (adapte si besoin)
                return $this->redirectToRoute('app_home'); // ou: return $this->redirect('/');
            }
            return $this->redirectToRoute('coaching_index');
        }

        // GET : prépare l’affichage (coches du jour)
        $checksToday = $checkRepo->findBy(['user' => $user, 'day' => $today]);
        $checkedMap = [];
        foreach ($checksToday as $c) {
            $checkedMap[$c->getTheme()->getId()] = $c->isChecked();
        }

        // Grouping pour Twig
        $grouped = [];
        foreach (self::SECTIONS as $key => $display) { $grouped[$key] = []; }

        foreach ($themes as $t) {
            $grouped[$t->getSection()][] = [
                'id'    => $t->getId(),
                'label' => $t->getLabel(),
                'coef'  => $t->getCoefficient(),
                'checked' => $checkedMap[$t->getId()] ?? false,
            ];
        }

        // Score du jour = somme des coef des cases cochées
        $score = 0;
        foreach ($grouped as $items) {
            foreach ($items as $it) {
                if ($it['checked']) { $score += (int)$it['coef']; }
            }
        }

        return $this->render('coaching/index.html.twig', [
            'sections' => self::SECTIONS,
            'grouped'  => $grouped,
            'todayFr'  => $this->todayFr(),
            'score'    => $score,
        ]);
    }

    #[Route('/coaching/settings', name: 'coaching_settings', methods: ['GET','POST'])]
    public function settings(Request $request, EntityManagerInterface $em): Response
    {
        // ⬇️ Garde d'accès adaptée à GET/POST
        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } else {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }

        $this->ensureThemesExist($em);

        $user = $this->getUser();
        $repo = $em->getRepository(UserTheme::class);
        $themes = $repo->findBy(['user' => $user], ['section' => 'ASC', 'position' => 'ASC']);

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('coaching_settings', $token)) {
                throw $this->createAccessDeniedException('CSRF invalide');
            }

            $labels = $request->request->all('label') ?? []; // label[<id>]
            $coefs  = $request->request->all('coef')  ?? []; // coef[<id>]

            foreach ($themes as $t) {
                if (array_key_exists($t->getId(), $labels)) {
                    $val = trim((string)$labels[$t->getId()]);
                    $t->setLabel($val !== '' ? $val : null);
                }
                if (array_key_exists($t->getId(), $coefs)) {
                    $c = max(-3, min(3, (int)$coefs[$t->getId()]));
                    $t->setCoefficient($c);
                }
            }
            $em->flush();

            // Rester sur Paramètres après "Enregistrer"
            return $this->redirectToRoute('coaching_settings');
        }

        $grouped = [];
        foreach (self::SECTIONS as $key => $display) { $grouped[$key] = []; }
        foreach ($themes as $t) {
            $grouped[$t->getSection()][] = $t;
        }

        return $this->render('coaching/settings.html.twig', [
            'sections' => self::SECTIONS,
            'grouped'  => $grouped,
        ]);
    }

}
