<?php
namespace App\Controller;

use App\Entity\CastellumSubcategory;
use App\Entity\CastellumQuestion;
use App\Entity\CastellumPreference;
use App\Form\CastellumQuestionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CastellumController extends AbstractController
{
    private const LEVELS = ['base', 'avancé', 'expert'];
    private const QUESTION_OPTIONS = [10,20,30,40,50,60,70,80,90,100];

    // Codes + libellés (en dur)
    private const CATEGORY_LABELS = [
        '000' => 'Généralités : informatique',
        '100' => 'Philosophie et psychologie : philosophie, psychologie',
        '200' => 'Religions : religions',
        '300' => 'Sciences sociales : droit, politique, sujets de société, scolarité',
        '400' => 'Langues : conjugaison, vocabulaire, étymologie, expressions françaises',
        '500' => 'Sciences pures : astronomie, biologie, chimie, physique, mathématiques, anatomie',
        '600' => 'Technologie et sciences appliquées : matériaux, cuisine, boucherie, télécom, jardinage',
        '700' => 'Arts et loisirs : architecture d’une église, musique, contes, chasse, jeu de la belote',
        '800' => 'Littérature : /',
        '900' => 'Histoire et géographie : histoire, géographie',
    ];

    // -----------------------
    //  Helpers de comparaison
    // -----------------------
    private function normalizeAnswer(?string $s): string
    {
        $s = (string) $s;
        $s = trim($s);
        $s = mb_strtolower($s);
        // enlève les accents
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        // supprime tout sauf lettres/chiffres/espaces
        $s = preg_replace('/[^a-z0-9 ]+/i', '', $s);
        // compresse les espaces
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function isCorrect(string $user, string $expected): bool
    {
        // support multi-réponses séparées par ';'
        $userN = $this->normalizeAnswer($user);
        foreach (array_map('trim', explode(';', $expected)) as $opt) {
            if ($userN === $this->normalizeAnswer($opt)) {
                return true;
            }
        }
        return false;
    }    
    private function uploadDir(): string
    {
        // public/uploads/castellum
        return $this->getParameter('kernel.project_dir').'/public/uploads/castellum';
    }
    private function publicUploadPath(string $filename): string
    {
        return 'uploads/castellum/'.$filename; // pour stocker dans la BDD (chemin public)
    }
    #[IsGranted('ROLE_USER')]
    #[Route('/castellum', name: 'castellum_index', methods: ['GET','POST'])]
    public function index(Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        // POST => démarrer un test (comme avant)
        if ($request->isMethod('POST') && $request->request->has('_token')) {
            if (!$this->isCsrfTokenValid('castellum_start', $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide');
            }

            $count = (int) $request->request->get('count', 20);
            if (!in_array($count, self::QUESTION_OPTIONS, true)) $count = 20;

            $level = (string) $request->request->get('level', 'base');
            if (!in_array($level, self::LEVELS, true)) $level = 'base';

            // ✅ utiliser all() pour récupérer des tableaux
            $cats = $request->request->all('cats');   // renvoie toujours un array (vide si absent)
            $subs = $request->request->all('subs');   // idem

            // Reconstitue la sélection
            $selected = [];
            foreach ($cats as $code) {
                if (isset(self::CATEGORY_LABELS[$code])) {
                    $selected[$code] = ['label' => self::CATEGORY_LABELS[$code], 'subs' => []];
                }
            }
            foreach ($subs as $code => $ids) {
                $list = $em->getRepository(CastellumSubcategory::class)
                        ->findBy(['code' => $code, 'id' => $ids]);
                $names = array_map(fn($s) => $s->getName(), $list);
                if (!isset($selected[$code])) {
                    $selected[$code] = ['label' => self::CATEGORY_LABELS[$code] ?? $code, 'subs' => []];
                }
                if ($names) {
                    $selected[$code]['subs'] = $names;
                }
            }

            // Sauvegarde des préférences utilisateur (si connecté)
            if ($this->getUser()) {
                // $cats = $request->request->all('cats'); // déjà lus plus haut
                // $subs = $request->request->all('subs'); // map code => [ids]
                $allSubIds = [];
                foreach ($subs as $ids) {
                    foreach ((array)$ids as $id) {
                        if ($id !== '' && $id !== null) $allSubIds[] = (int)$id;
                    }
                }

                $repoPref = $em->getRepository(CastellumPreference::class);
                $pref = $repoPref->findOneBy(['user' => $this->getUser()]);
                if (!$pref) {
                    $pref = (new CastellumPreference())->setUser($this->getUser());
                    $em->persist($pref);
                }
                $pref->setCategories($cats)
                    ->setSubcategories($allSubIds)
                    ->setLevel($level)
                    ->setCount($count)
                    ->touch();
                // pas besoin de flush ici si tu flush ailleurs, mais on s'assure que c'est sauvé :
                $em->flush();
            }

            $session->set('castellum.config', [
                'count'    => $count,
                'level'    => $level,
                'selected' => $selected,
            ]);

            return $this->redirectToRoute('castellum_test_start');
        }


        // GET => afficher config avec sous-catégories depuis la BDD (tri alpha)
        $subcatsByCode = [];
        foreach (array_keys(self::CATEGORY_LABELS) as $code) {
            $subcatsByCode[$code] = $em->getRepository(CastellumSubcategory::class)
                ->createQueryBuilder('s')
                ->andWhere('s.code = :c')->setParameter('c', $code)
                ->orderBy('s.name', 'ASC')
                ->getQuery()->getResult();
        }

        // Valeurs par défaut (si pas connecté ou pas de prefs en BDD)
        $prefsCats   = [];
        $prefsSubIds = [];
        $prefsLevel  = 'base';
        $prefsCount  = 20;

        if ($this->getUser()) {
            $pref = $em->getRepository(CastellumPreference::class)
                ->findOneBy(['user' => $this->getUser()]);
            if ($pref) {
                $prefsCats   = $pref->getCategories();
                $prefsSubIds = $pref->getSubcategories();
                $prefsLevel  = $pref->getLevel();
                $prefsCount  = $pref->getCount();
            }
        }

        return $this->render('castellum/index.html.twig', [
            'labels'     => self::CATEGORY_LABELS,
            'options'    => self::QUESTION_OPTIONS,
            'levels'     => self::LEVELS,
            'subcats'    => $subcatsByCode,
            'prefsCats'  => $prefsCats,
            'prefsSubIds'=> $prefsSubIds,
            'prefsLevel' => $prefsLevel,
            'prefsCount' => $prefsCount,
        ]);

    }

    #[Route('/castellum/prefs/save', name: 'castellum_prefs_save', methods: ['POST'])]
    public function savePrefsAjax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // CSRF (depuis champ _token ou en-tête X-CSRF-TOKEN)
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid('castellum_prefs', $token)) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], 400);
        }

        // Auth: si non connecté, on ignore (on retourne 401 pour que le JS affiche le message)
        if (!$this->getUser()) {
            return new JsonResponse(['ok' => false, 'error' => 'auth'], 401);
        }

        // Lecture & nettoyage
        $count = (int) $request->request->get('count', 20);
        $level = (string) $request->request->get('level', 'base');

        $allowedCounts = [10,20,30,40,50,60,70,80,90,100];
        $allowedLevels = ['base','avancé','expert'];
        if (!in_array($count, $allowedCounts, true)) $count = 20;
        if (!in_array($level, $allowedLevels, true)) $level = 'base';

        $cats = $request->request->all('cats');          // ex: ['400','600']
        if (!is_array($cats)) $cats = [];
        $cats = array_values(array_unique(array_map('strval', $cats)));

        // on envoie côté client un tableau “plat” d’IDs de sous-cats
        $subsFlat = $request->request->all('subsFlat');  // ex: [12,57,31]
        if (!is_array($subsFlat)) $subsFlat = [];
        $subsFlat = array_values(array_unique(array_map('intval', $subsFlat)));

        // Persist
        /** @var \App\Entity\CastellumPreference|null $pref */
        $pref = $em->getRepository(\App\Entity\CastellumPreference::class)
                ->findOneBy(['user' => $this->getUser()]);
        if (!$pref) {
            $pref = (new \App\Entity\CastellumPreference())->setUser($this->getUser());
            $em->persist($pref);
        }
        $pref->setCategories($cats)
            ->setSubcategories($subsFlat)
            ->setLevel($level)
            ->setCount($count)
            ->touch();

        $em->flush();

        return new JsonResponse(['ok' => true, 'savedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)]);
    }

    #[Route('/castellum/test/start', name: 'castellum_test_start', methods: ['GET'])]
    public function startTest(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $config = $session->get('castellum.config');
        if (!$config) {
            $this->addFlash('warning', 'Configurez d’abord votre test.');
            return $this->redirectToRoute('castellum_index');
        }

        $count = (int)($config['count'] ?? 20);
        $level = (string)($config['level'] ?? 'base');
        $selected = (array)($config['selected'] ?? []);

        // Construit la liste des sous-cat sélectionnées (ids), sinon toutes des catégories cochées
        $subcatIds = [];
        $categoryCodes = array_keys($selected);
        if ($selected) {
            // si des sous-cats sont listées, on les prend, sinon on prend toutes les sous-cats des catégories cochées
            foreach ($selected as $code => $info) {
                if (!empty($info['subs'])) {
                    // on a des noms, on récupère leurs IDs
                    $subs = $em->getRepository(CastellumSubcategory::class)
                        ->createQueryBuilder('s')
                        ->andWhere('s.code = :c')->setParameter('c', $code)
                        ->andWhere('s.name IN (:names)')->setParameter('names', $info['subs'])
                        ->getQuery()->getResult();
                    foreach ($subs as $s) { $subcatIds[] = $s->getId(); }
                } else {
                    // toutes les sous-cats de la catégorie
                    $subs = $em->getRepository(CastellumSubcategory::class)
                        ->findBy(['code' => $code]);
                    foreach ($subs as $s) { $subcatIds[] = $s->getId(); }
                }
            }
        }

        // Récupère les questions candidates
        $qb = $em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
            ->andWhere('q.levelQuestion = :lvl')->setParameter('lvl', $level);

        if ($subcatIds) {
            $qb->andWhere('q.subcategory IN (:subs)')->setParameter('subs', $subcatIds);
        } elseif ($categoryCodes) {
            $qb->andWhere('q.categoryCode IN (:codes)')->setParameter('codes', $categoryCodes);
        } // sinon : tout niveau + toutes catégories (si rien coché)

        $qIds = array_map(
            fn($row) => (int)$row['id'],
            $qb->select('q.id')->getQuery()->getScalarResult()
        );

        if (!$qIds) {
            $this->addFlash('danger', 'Aucune question ne correspond à votre sélection.');
            return $this->redirectToRoute('castellum_index');
        }

        shuffle($qIds);
        $qIds = array_slice($qIds, 0, max(1, $count));

        // État de test en session
        $session->set('castellum.test', [
            'ids'     => $qIds,
            'total'   => count($qIds),
            'answers' => [], // pos(1..n) => ['id'=>, 'user'=>, 'ok'=>bool]
        ]);

        return $this->redirectToRoute('castellum_test_question', ['pos' => 1]);
    }

    #[Route('/castellum/test/q/{pos}', name: 'castellum_test_question', requirements: ['pos' => '\d+'], methods: ['GET','POST'])]
    public function playQuestion(int $pos, Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $state = $session->get('castellum.test');
        if (!$state) {
            $this->addFlash('warning', 'Aucun test en cours.');
            return $this->redirectToRoute('castellum_index');
        }

        $ids = $state['ids'] ?? [];
        $total = (int)($state['total'] ?? 0);
        if ($pos < 1 || $pos > $total) {
            return $this->redirectToRoute('castellum_test_result'); // out of bounds => fin
        }

        $qid = (int)$ids[$pos - 1];
        /** @var CastellumQuestion|null $q */
        $q = $em->getRepository(CastellumQuestion::class)->find($qid);
        if (!$q) {
            // question supprimée ? on saute
            return $this->redirectToRoute('castellum_test_question', ['pos' => $pos + 1]);
        }

        $result = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('castellum_answer_'.$pos, $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide');
            }
            $answer = (string)$request->request->get('answer', '');
            $ok = $this->isCorrect($answer, $q->getAnswerText());

            // Enregistre la réponse dans la session
            $answers = $state['answers'] ?? [];
            $answers[$pos] = ['id' => $qid, 'user' => $answer, 'ok' => $ok];
            $state['answers'] = $answers;
            $session->set('castellum.test', $state);

            $result = [
                'ok' => $ok,
                'expected' => $q->getAnswerText(),
                'explanation' => $q->getExplanation(),
                'user' => $answer,
            ];
            // On rend la même page avec bande verte/rouge + bouton "suivante"
        }

        return $this->render('castellum/test_question.html.twig', [
            'q'      => $q,
            'pos'    => $pos,
            'total'  => $total,
            'result' => $result,
        ]);
    }

    #[Route('/castellum/test/result', name: 'castellum_test_result', methods: ['GET'])]
    public function testResult(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $state = $session->get('castellum.test');
        if (!$state) {
            return $this->redirectToRoute('castellum_index');
        }
        $answers = $state['answers'] ?? [];
        $total   = (int)($state['total'] ?? 0);
        $score   = array_sum(array_map(fn($a) => !empty($a['ok']) ? 1 : 0, $answers));

        // Optionnel : récupérer les objets questions pour un récap détaillé
        $byPos = [];
        foreach ($answers as $pos => $row) {
            $byPos[$pos] = [
                'q' => $em->getRepository(CastellumQuestion::class)->find($row['id']),
                'user' => $row['user'],
                'ok' => (bool)$row['ok'],
            ];
        }

        // On peut nettoyer la session si tu veux repartir de zéro après l’écran
        // $session->remove('castellum.test');

        return $this->render('castellum/test_result.html.twig', [
            'total' => $total,
            'score' => $score,
            'byPos' => $byPos,
        ]);
    }


    #[Route('/castellum/subcategory/new', name: 'castellum_sub_new', methods: ['POST'])]
    public function addSubcategory(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('castellum_sub_new', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }
        $code = (string)$request->request->get('code', '');
        $name = trim((string)$request->request->get('name', ''));
        if ($code === '' || $name === '') {
            $this->addFlash('danger', 'Catégorie ou nom manquant.');
            return $this->redirectToRoute('castellum_index', ['_fragment' => 'cat-'.$code]);
        }
        // éviter les doublons (code + name)
        $exists = $em->getRepository(CastellumSubcategory::class)
            ->findOneBy(['code' => $code, 'name' => $name]);
        if ($exists) {
            $this->addFlash('warning', 'Cette sous-catégorie existe déjà.');
            return $this->redirectToRoute('castellum_index', ['_fragment' => 'cat-'.$code]);
        }

        $s = (new CastellumSubcategory())->setCode($code)->setName($name);
        $em->persist($s);
        $em->flush();
        $this->addFlash('success', 'Sous-catégorie ajoutée.');
        return $this->redirectToRoute('castellum_index', ['_fragment' => 'collapse-'.$code]);
    }

    #[Route('/castellum/questions/{id}', name: 'castellum_questions', methods: ['GET'])]
    public function listQuestions(CastellumSubcategory $subcategory, EntityManagerInterface $em): Response
    {
        $qs = $em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
            ->andWhere('q.subcategory = :s')->setParameter('s', $subcategory)
            ->orderBy('q.updatedAt','DESC')->getQuery()->getResult();

        return $this->render('castellum/questions.html.twig', [
            'subcategory' => $subcategory,
            'questions'   => $qs,
            'labels'      => self::CATEGORY_LABELS,
        ]);
    }

    #[Route('/castellum/questions/{id}/new', name: 'castellum_question_new', methods: ['GET','POST'])]
    public function newQuestion(Request $request, CastellumSubcategory $subcategory, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $q = new CastellumQuestion();
        $q->setSubcategory($subcategory)->setCategoryCode($subcategory->getCode());
        $form = $this->createForm(CastellumQuestionType::class, $q);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('questionImageFile')->getData();
            $remove = (bool)$form->get('removeImage')->getData();

            // Supprimer image si demandé
            if ($remove && $q->getQuestionImage()) {
                @unlink($this->getParameter('kernel.project_dir').'/public/'.$q->getQuestionImage());
                $q->setQuestionImage(null);
            }

            // Upload fichier si fourni
            if ($file) {
                $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original)->lower();
                $newName = $safe.'-'.uniqid().'.'.$file->guessExtension();
                @mkdir($this->uploadDir(), 0777, true);
                $file->move($this->uploadDir(), $newName);
                $q->setQuestionImage($this->publicUploadPath($newName));
            }

            $q->touch();
            $em->persist($q);
            $em->flush();
            $this->addFlash('success', 'Question ajoutée.');
            return $this->redirectToRoute('castellum_questions', ['id' => $subcategory->getId()]);
        }

        return $this->render('castellum/question_form.html.twig', [
            'form' => $form->createView(),
            'mode' => 'new',
            'subcategory' => $subcategory,
        ]);
    }

    #[Route('/castellum/question/{id}/edit', name: 'castellum_question_edit', methods: ['GET','POST'])]
    public function editQuestion(Request $request, CastellumQuestion $question, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CastellumQuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('questionImageFile')->getData();
            $remove = (bool)$form->get('removeImage')->getData();

            if ($remove && $question->getQuestionImage()) {
                @unlink($this->getParameter('kernel.project_dir').'/public/'.$question->getQuestionImage());
                $question->setQuestionImage(null);
            }

            if ($file) {
                $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safe = $slugger->slug($original)->lower();
                $newName = $safe.'-'.uniqid().'.'.$file->guessExtension();
                @mkdir($this->uploadDir(), 0777, true);
                $file->move($this->uploadDir(), $newName);
                // si ancienne image existe, on la supprime
                if ($question->getQuestionImage()) {
                    @unlink($this->getParameter('kernel.project_dir').'/public/'.$question->getQuestionImage());
                }
                $question->setQuestionImage($this->publicUploadPath($newName));
            }

            // garder categoryCode en phase si sous-cat changée
            $question->setCategoryCode($question->getSubcategory()->getCode());
            $question->touch();
            $em->flush();
            $this->addFlash('success', 'Question mise à jour.');
            return $this->redirectToRoute('castellum_questions', ['id' => $question->getSubcategory()->getId()]);
        }

        return $this->render('castellum/question_form.html.twig', [
            'form' => $form->createView(),
            'mode' => 'edit',
            'subcategory' => $question->getSubcategory(),
        ]);
    }

    #[Route('/castellum/question/{id}/delete', name: 'castellum_question_delete', methods: ['POST'])]
    public function deleteQuestion(Request $request, CastellumQuestion $question, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); // adapte selon tes rôles

        if (!$this->isCsrfTokenValid('castellum_question_delete_'.$question->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }

        // Supprimer le fichier image local éventuel
        $img = $question->getQuestionImage();
        if ($img && str_starts_with($img, 'uploads/')) {
            @unlink($this->getParameter('kernel.project_dir').'/public/'.$img);
        }

        $subcatId = $question->getSubcategory()->getId();

        $em->remove($question);
        $em->flush();

        $this->addFlash('success', 'Question supprimée.');
        return $this->redirectToRoute('castellum_questions', ['id' => $subcatId]);
    }


    #[Route('/castellum/subcategory/{id}/delete', name: 'castellum_sub_delete', methods: ['POST'])]
    public function deleteSubcategory(Request $request, CastellumSubcategory $subcategory, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER'); // adapte si besoin
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('castellum_sub_delete_'.$subcategory->getId(), $token)) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }
        $code = $subcategory->getCode();
        $em->remove($subcategory); // onDelete:CASCADE sur questions
        $em->flush();
        $this->addFlash('success', 'Sous-catégorie supprimée.');
        return $this->redirectToRoute('castellum_index', ['_fragment' => 'cat-'.$code]);
    }


    #[Route('/castellum/test', name: 'castellum_test', methods: ['GET'])]
    public function test(SessionInterface $session): Response
    {
        $config = $session->get('castellum.config', null);
        return $this->render('castellum/test.html.twig', ['config' => $config]);
    }
}
