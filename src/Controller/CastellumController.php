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
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class CastellumController extends AbstractController
{
    private const LEVELS = ['base', 'avancé', 'expert'];
    private const QUESTION_OPTIONS = [10,20,30,40,50,60,70,80,90,100];

    // Codes + libellés (en dur)
    private const CATEGORY_LABELS = [
        '000' => 'Généralités',
        '100' => 'Philosophie et psychologie',
        '200' => 'Religions',
        '300' => 'Sciences sociales',
        '400' => 'Langues',
        '500' => 'Sciences pures',
        '600' => 'Technologie et sciences appliquées',
        '700' => 'Arts et loisirs',
        '800' => 'Littérature',
        '900' => 'Histoire et géographie',
    ];

    // --------- Upload helpers ---------
    private function uploadDir(): string
    {
        return $this->getParameter('kernel.project_dir').'/public/uploads/castellum';
    }
    private function audioUploadDir(): string { return $this->uploadDir().'/audio'; }
    private function qcmUploadDir(): string { return $this->uploadDir().'/qcm'; }

    private function publicUploadPath(string $filename): string { return 'uploads/castellum/'.$filename; }
    private function publicAudioPath(string $filename): string { return 'uploads/castellum/audio/'.$filename; }
    private function publicQcmPath(string $filename): string { return 'uploads/castellum/qcm/'.$filename; }

    private function filesystemPublic(string $publicPath): string
    {
        return $this->getParameter('kernel.project_dir').'/public/'.$publicPath;
    }
    private function isLocalPublic(?string $path): bool
    {
        return $path && str_starts_with($path, 'uploads/');
    }
    private function duplicatePublicFile(string $publicPath, string $destFsDir, string $publicBase, ?string $prefix = null): ?string
    {
        if (!$this->isLocalPublic($publicPath)) {
            return $publicPath ?: null;
        }
        $src = $this->filesystemPublic($publicPath);
        if (!is_file($src)) {
            return null;
        }
        @mkdir($destFsDir, 0777, true);
        $ext  = pathinfo($src, PATHINFO_EXTENSION);
        $base = ($prefix ?: 'copy').'-'.uniqid();
        $new  = $base.($ext ? '.'.$ext : '');
        $dst  = rtrim($destFsDir, '/').'/'.$new;
        if (@copy($src, $dst)) {
            return rtrim($publicBase, '/').'/'.$new;
        }
        return null;
    }

    private function summarizeText(?string $html, int $max = 120): string
    {
        $txt = (string) $html;
        $txt = strip_tags($txt);
        $txt = preg_replace('/\s+/u', ' ', $txt);
        $txt = trim($txt);
        if ($txt === '') return '';
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($txt, 0, $max, '…', 'UTF-8');
        }
        return strlen($txt) > $max ? substr($txt, 0, $max - 2).'…' : $txt;
    }

    /** Safe getter */
    private function formFile($form, string $name): ?UploadedFile
    {
        return $form->has($name) ? $form->get($name)->getData() : null;
    }

    // -----------------------
    //  Helpers de comparaison
    // -----------------------
    private function normalizeAnswer(?string $s): string
    {
        $s = (string) $s;
        $s = trim($s);
        $s = mb_strtolower($s);
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9 ]+/i', '', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function isCorrect(string $user, string $expected): bool
    {
        $userN = $this->normalizeAnswer($user);
        foreach (array_map('trim', explode(';', $expected)) as $opt) {
            if ($userN === $this->normalizeAnswer($opt)) return true;
        }
        return false;
    }

    // =========================
    //  API : liste des questions
    // =========================
    #[Route('/castellum/api/questions', name: 'castellum_api_questions', methods: ['GET'])]
    public function apiQuestions(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $subId   = (int) $request->query->get('subId', 0);
        $chapter = $request->query->getInt('chapter', -1);
        if ($subId <= 0) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_request'], 400);
        }

        $qb = $em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
            ->andWhere('q.subcategory = :s')->setParameter('s', $subId);

        if ($chapter >= 0) {
            $qb->andWhere('q.formationChapter = :c')->setParameter('c', $chapter);
        }

        $qs = $qb->orderBy('q.updatedAt', 'DESC')->getQuery()->getResult();

        $items = [];
        foreach ($qs as $q) {
            $label = $this->summarizeText($q->getQuestionText(), 140);
            if ($label === '') {
                $label = $q->getSubject() ?: ('Question #'.$q->getId());
            }
            $items[] = ['id' => $q->getId(), 'label' => $label];
        }

        return new JsonResponse(['ok' => true, 'items' => $items]);
    }

    // =========================
    //  Page catégorie (liste sous-cats)
    // =========================
    #[IsGranted('ROLE_USER')]
    #[Route('/castellum/categorie/{code}', name: 'castellum_category', methods: ['GET'])]
    public function categoryList(string $code, EntityManagerInterface $em): Response
    {
        $labels = [
            '000'=>'Généralités',
            '100'=>'Philosophie et psychologie',
            '200'=>'Religions',
            '300'=>'Sciences sociales',
            '400'=>'Langues',
            '500'=>'Sciences pures',
            '600'=>'Technologie et sciences appliquées',
            '700'=>'Arts et loisirs',
            '800'=>'Littérature',
            '900'=>'Histoire et géographie',
        ];

        $subs = $em->getRepository(CastellumSubcategory::class)
            ->findBy(['code' => $code], ['name' => 'ASC']);

        // ✅ Compter le nombre de questions par sous-catégorie affichée
        $countsBySubId = [];
        if (!empty($subs)) {
            $ids = array_map(fn(CastellumSubcategory $s) => $s->getId(), $subs);
            $rows = $em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
                ->select('IDENTITY(q.subcategory) AS sid, COUNT(q.id) AS cnt')
                ->andWhere('q.subcategory IN (:ids)')->setParameter('ids', $ids)
                ->groupBy('q.subcategory')
                ->getQuery()->getScalarResult();
            foreach ($rows as $r) {
                $sid = (int) $r['sid'];
                $countsBySubId[$sid] = (int) $r['cnt'];
            }
        }

        // Pré-cocher selon les préférences de l’utilisateur
        $prefsSubIds = [];
        if ($this->getUser()) {
            $pref = $em->getRepository(CastellumPreference::class)
                ->findOneBy(['user'=>$this->getUser()]);
            if ($pref) {
                $prefsSubIds = $pref->getSubcategories() ?? [];
            }
        }

        return $this->render('castellum/category.html.twig', [
            'code'           => $code,
            'label'          => $labels[$code] ?? $code,
            'subs'           => $subs,
            'prefsSubIds'    => $prefsSubIds,
            // Bandeau :
            'catCode'        => $code,
            'catLabel'       => $labels[$code] ?? $code,
            // ✅ nouveaux compteurs pour le template :
            'countsBySubId'  => $countsBySubId,
        ]);
    }

    // -------------------------------------------------------------------
    //  Config test + préférences
    // -------------------------------------------------------------------
    #[IsGranted('ROLE_USER')]
    #[Route('/castellum', name: 'castellum_index', methods: ['GET','POST'])]
    public function index(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrf
    ): Response {
        if ($request->isMethod('GET') && $request->hasSession() && !$request->getSession()->isStarted()) {
            $request->getSession()->start();
        }

        if ($request->isMethod('POST')) {
            $postedToken = (string) $request->request->get('_token_castellum_start', '');
            if ($postedToken === '') {
                $postedToken = (string) $request->request->get('_token', '');
            }
            $expectedToken = $csrf->getToken('castellum_start')->getValue();
            if (!$this->isCsrfTokenValid('castellum_start', $postedToken)) {
                $this->addFlash('danger', 'CSRF invalide, merci de réessayer.');
                return $this->redirectToRoute('castellum_index');
            }

            $count = (int) $request->request->get('count', 20);
            if (!in_array($count, self::QUESTION_OPTIONS, true)) $count = 20;

            $level = (string) $request->request->get('level', 'base');
            if (!in_array($level, self::LEVELS, true)) $level = 'base';

            $cats = $request->request->all('cats');
            $subs = $request->request->all('subs');

            $selected = [];
            foreach ($cats as $c) {
                if (isset(self::CATEGORY_LABELS[$c])) {
                    $selected[$c] = ['label' => self::CATEGORY_LABELS[$c], 'subs' => []];
                }
            }
            foreach ($subs as $c => $ids) {
                $list  = $em->getRepository(CastellumSubcategory::class)->findBy(['code' => $c, 'id' => $ids]);
                $names = array_map(fn($s) => $s->getName(), $list);
                if (!isset($selected[$c])) {
                    $selected[$c] = ['label' => self::CATEGORY_LABELS[$c] ?? $c, 'subs' => []];
                }
                if ($names) $selected[$c]['subs'] = $names;
            }

            if ($this->getUser()) {
                $allSubIds = [];
                foreach ($subs as $ids) {
                    foreach ((array)$ids as $id) {
                        if ($id !== '' && $id !== null) $allSubIds[] = (int)$id;
                    }
                }
                $pref = $em->getRepository(CastellumPreference::class)->findOneBy(['user' => $this->getUser()]);
                if (!$pref) {
                    $pref = (new \App\Entity\CastellumPreference())->setUser($this->getUser());
                    $em->persist($pref);
                }
                $pref->setCategories($cats)->setSubcategories($allSubIds)->setLevel($level)->setCount($count)->touch();
                $em->flush();
            }

            $session->set('castellum.config', ['count'=>$count,'level'=>$level,'selected'=>$selected]);
            return $this->redirectToRoute('castellum_test_start');
        }

        $subcatsByCode = [];
        foreach (array_keys(self::CATEGORY_LABELS) as $c) {
            $subcatsByCode[$c] = $em->getRepository(CastellumSubcategory::class)
                ->createQueryBuilder('s')
                ->andWhere('s.code = :c')->setParameter('c',$c)
                ->orderBy('s.name','ASC')
                ->getQuery()->getResult();
        }

        // ✅ compteurs par catégorie (pour l’écran d’accueil)
        $rows = $em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
            ->select('q.categoryCode AS code, COUNT(q.id) AS cnt')
            ->groupBy('q.categoryCode')
            ->getQuery()->getScalarResult();
        $countsByCode = [];
        foreach ($rows as $r) {
            $code = $r['code'] ?? null;
            if ($code !== null && $code !== '') {
                $countsByCode[$code] = (int)$r['cnt'];
            }
        }

        $prefsCats=[]; $prefsSubIds=[]; $prefsLevel='base'; $prefsCount=20;
        if ($this->getUser()) {
            $pref = $em->getRepository(CastellumPreference::class)->findOneBy(['user'=>$this->getUser()]);
            if ($pref) {
                $prefsCats   = $pref->getCategories() ?? [];
                $prefsSubIds = $pref->getSubcategories() ?? [];
                $prefsLevel  = $pref->getLevel() ?? 'base';
                $prefsCount  = $pref->getCount() ?? 20;
            }
        }

        return $this->render('castellum/index.html.twig', [
            'labels'        => self::CATEGORY_LABELS,
            'options'       => self::QUESTION_OPTIONS,
            'levels'        => self::LEVELS,
            'subcats'       => $subcatsByCode,
            'prefsCats'     => $prefsCats,
            'prefsSubIds'   => $prefsSubIds,
            'prefsLevel'    => $prefsLevel,
            'prefsCount'    => $prefsCount,
            'countsByCode'  => $countsByCode, // pour la ligne catégorie
        ]);
    }

    // ---------------- Duplication question ----------------
    #[Route('/castellum/question/{id}/duplicate', name: 'castellum_question_duplicate', methods: ['POST'])]
    public function duplicateQuestion(Request $request, CastellumQuestion $question, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if (!$this->isCsrfTokenValid('castellum_question_duplicate_'.$question->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }

        $copy = new CastellumQuestion();
        $copy
            ->setSubcategory($question->getSubcategory())
            ->setCategoryCode($question->getCategoryCode())
            ->setLevelQuestion($question->getLevelQuestion())
            ->setSubject($question->getSubject())
            ->setQuestionType($question->getQuestionType())
            ->setQuestionText($question->getQuestionText())
            ->setAnswerText($question->getAnswerText())
            ->setExplanation($question->getExplanation())
            ->setDurationSeconds($question->getDurationSeconds())
            ->setFormationChapter($question->getFormationChapter())
            ->setFormationParagraph($question->getFormationParagraph())
            ->setCoordX($question->getCoordX())
            ->setCoordY($question->getCoordY())
            ->setValidationSignataire1($question->getValidationSignataire1())
            ->setValidationSignataire2($question->getValidationSignataire2())
            ->setValidationSignataire3($question->getValidationSignataire3())
            ->setQcmText1($question->getQcmText1())->setQcmText2($question->getQcmText2())->setQcmText3($question->getQcmText3())
            ->setQcmText4($question->getQcmText4())->setQcmText5($question->getQcmText5())->setQcmText6($question->getQcmText6())
            ->setQcmText7($question->getQcmText7())->setQcmText8($question->getQcmText8())->setQcmText9($question->getQcmText9())
            ->setQcmText10($question->getQcmText10());

        $img = $question->getQuestionImage();
        $copy->setQuestionImage($img && $this->isLocalPublic($img)
            ? $this->duplicatePublicFile($img, $this->uploadDir(), 'uploads/castellum', 'q-img-copy')
            : $img
        );

        $audio = $question->getQuestionAudio();
        $copy->setQuestionAudio($audio && $this->isLocalPublic($audio)
            ? $this->duplicatePublicFile($audio, $this->audioUploadDir(), 'uploads/castellum/audio', 'q-audio-copy')
            : $audio
        );

        for ($i=1;$i<=9;$i++){
            $getter='getQcmImage'.$i; $setter='setQcmImage'.$i; if(!method_exists($question,$getter)) continue;
            $val=$question->$getter();
            $copy->$setter($val && $this->isLocalPublic($val)
                ? $this->duplicatePublicFile($val,$this->qcmUploadDir(),'uploads/castellum/qcm','qcm'.$i.'-copy')
                : $val
            );
        }

        if (method_exists($copy,'touch')) $copy->touch();

        $em->persist($copy);
        $em->flush();

        $this->addFlash('success','Question dupliquée.');
        return $this->redirectToRoute('castellum_question_edit', ['id'=>$copy->getId()]);
    }

    // ---------------- Sauvegarde préférences (AJAX) ----------------
    #[Route('/castellum/prefs/save', name: 'castellum_prefs_save', methods: ['POST'])]
    public function savePrefsAjax(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid('castellum_prefs', $token)) return new JsonResponse(['ok'=>false,'error'=>'csrf'],400);
        if (!$this->getUser()) return new JsonResponse(['ok'=>false,'error'=>'auth'],401);

        $count = (int)$request->request->get('count',20);
        $level = (string)$request->request->get('level','base');
        $allowedCounts = [10,20,30,40,50,60,70,80,90,100];
        $allowedLevels = ['base','avancé','expert'];
        if(!in_array($count,$allowedCounts,true)) $count=20;
        if(!in_array($level,$allowedLevels,true)) $level='base';

        $cats      = (array)$request->request->all('cats');       // optionnel
        $subsFlat  = (array)$request->request->all('subsFlat');   // optionnel
        $merge     = (bool)$request->request->get('merge', false);
        $catCode   = (string)$request->request->get('cat', '');

        $pref = $em->getRepository(CastellumPreference::class)->findOneBy(['user'=>$this->getUser()]);
        if(!$pref){ $pref=(new CastellumPreference())->setUser($this->getUser()); $em->persist($pref); }

        $pref->setCount($count)->setLevel($level);

        if ($merge && $catCode !== '') {
            $existingSubs = $pref->getSubcategories() ?? [];
            $idsInCat = array_map(
                fn($row)=>(int)$row['id'],
                $em->getRepository(CastellumSubcategory::class)->createQueryBuilder('s')
                    ->select('s.id')->andWhere('s.code = :c')->setParameter('c',$catCode)
                    ->getQuery()->getScalarResult()
            );
            $filtered = array_values(array_diff($existingSubs, $idsInCat));
            $newInCat = array_values(array_unique(array_map('intval', $subsFlat)));
            $pref->setSubcategories(array_values(array_unique(array_merge($filtered, $newInCat))));

            if (!empty($cats)) {
                $pref->setCategories(array_values(array_unique(array_map('strval',$cats))));
            }
        } else {
            if (!empty($cats)) {
                $pref->setCategories(array_values(array_unique(array_map('strval',$cats))));
            }
            if (!empty($subsFlat)) {
                $pref->setSubcategories(array_values(array_unique(array_map('intval',$subsFlat))));
            }
        }

        $pref->touch();
        $em->flush();

        return new JsonResponse(['ok'=>true,'savedAt'=>(new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)]);
    }

    // ---------------- Lancement du test ----------------
    #[Route('/castellum/test/start', name: 'castellum_test_start', methods: ['GET'])]
    public function startTest(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $config = $session->get('castellum.config');
        if (!$config) { $this->addFlash('warning','Configurez d’abord votre test.'); return $this->redirectToRoute('castellum_index'); }

        $count=(int)($config['count']??20); $level=(string)($config['level']??'base'); $selected=(array)($config['selected']??[]);
        $subcatIds=[]; $categoryCodes=array_keys($selected);

        if ($selected) {
            foreach ($selected as $code=>$info) {
                if (!empty($info['subs'])) {
                    $subs=$em->getRepository(CastellumSubcategory::class)->createQueryBuilder('s')
                        ->andWhere('s.code=:c')->setParameter('c',$code)
                        ->andWhere('s.name IN (:n)')->setParameter('n',$info['subs'])
                        ->getQuery()->getResult();
                    foreach($subs as $s){ $subcatIds[]=$s->getId(); }
                } else {
                    $subs=$em->getRepository(CastellumSubcategory::class)->findBy(['code'=>$code]);
                    foreach($subs as $s){ $subcatIds[]=$s->getId(); }
                }
            }
        }

        $qb=$em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
            ->andWhere('q.levelQuestion = :lvl')->setParameter('lvl',$level);
        if ($subcatIds)         $qb->andWhere('q.subcategory IN (:subs)')->setParameter('subs',$subcatIds);
        elseif ($categoryCodes) $qb->andWhere('q.categoryCode IN (:codes)')->setParameter('codes',$categoryCodes);

        $qIds=array_map(fn($row) => (int)$row['id'], $qb->select('q.id')->getQuery()->getScalarResult());
        if(!$qIds){ $this->addFlash('danger','Aucune question ne correspond à votre sélection.'); return $this->redirectToRoute('castellum_index'); }

        shuffle($qIds);
        $qIds=array_slice($qIds,0,max(1,$count));

        $session->set('castellum.test', ['ids'=>$qIds,'total'=>count($qIds),'answers'=>[]]);
        return $this->redirectToRoute('castellum_test_question',['pos'=>1]);
    }

    #[Route('/castellum/test/q/{pos}', name: 'castellum_test_question', requirements: ['pos' => '\d+'], methods: ['GET','POST'])]
    public function playQuestion(int $pos, Request $request, SessionInterface $session, EntityManagerInterface $em): Response
    {
        $state=$session->get('castellum.test');
        if(!$state){ $this->addFlash('warning','Aucun test en cours.'); return $this->redirectToRoute('castellum_index'); }

        $ids=$state['ids']??[]; $total=(int)($state['total']??0);
        if($pos<1 || $pos>$total) return $this->redirectToRoute('castellum_test_result');

        $qid=(int)$ids[$pos-1];
        /** @var CastellumQuestion|null $q */
        $q=$em->getRepository(CastellumQuestion::class)->find($qid);
        if(!$q) return $this->redirectToRoute('castellum_test_question',['pos'=>$pos+1]);

        $result=null;
        if ($request->isMethod('POST')) {
            if(!$this->isCsrfTokenValid('castellum_answer_'.$pos, $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('CSRF invalide');
            }
            $answer=(string)$request->request->get('answer','');
            $ok=$this->isCorrect($answer,$q->getAnswerText());

            $answers=$state['answers']??[];
            $answers[$pos]=['id'=>$qid,'user'=>$answer,'ok'=>$ok];
            $state['answers']=$answers;
            $session->set('castellum.test',$state);

            $result=['ok'=>$ok,'expected'=>$q->getAnswerText(),'explanation'=>$q->getExplanation(),'user'=>$answer];
        }

        return $this->render('castellum/test_question.html.twig', [
            'q'       => $q,
            'pos'     => $pos,
            'total'   => $total,
            'result'  => $result,
            'catCode'  => $q->getCategoryCode(),
            'catLabel' => self::CATEGORY_LABELS[$q->getCategoryCode()] ?? $q->getCategoryCode(),
        ]);
    }

    #[Route('/castellum/test/result', name: 'castellum_test_result', methods: ['GET'])]
    public function testResult(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $state=$session->get('castellum.test'); if(!$state) return $this->redirectToRoute('castellum_index');
        $answers=$state['answers']??[]; $total=(int)($state['total']??0);
        $score=array_sum(array_map(fn($a)=>!empty($a['ok'])?1:0,$answers));

        $byPos=[];
        foreach($answers as $p=>$row){
            $byPos[$p]=['q'=>$em->getRepository(CastellumQuestion::class)->find($row['id']),'user'=>$row['user'],'ok'=>(bool)$row['ok']];
        }

        return $this->render('castellum/test_result.html.twig', ['total'=>$total,'score'=>$score,'byPos'=>$byPos]);
    }

    // ---------------- Sous-catégories : add/list/delete ----------------
    #[Route('/castellum/subcategory/new', name: 'castellum_sub_new', methods: ['POST'])]
    public function addSubcategory(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('castellum_sub_new', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }
        $code=(string)$request->request->get('code','');
        $name=trim((string)$request->request->get('name',''));
        if($code===''||$name===''){ $this->addFlash('danger','Catégorie ou nom manquant.'); return $this->redirectToRoute('castellum_index',['_fragment'=>'cat-'.$code]); }

        $exists=$em->getRepository(CastellumSubcategory::class)->findOneBy(['code'=>$code,'name'=>$name]);
        if($exists){ $this->addFlash('warning','Cette sous-catégorie existe déjà.'); return $this->redirectToRoute('castellum_index',['_fragment'=>'cat-'.$code]); }

        $s=(new CastellumSubcategory())->setCode($code)->setName($name);
        $em->persist($s); $em->flush();
        $this->addFlash('success','Sous-catégorie ajoutée.');
        return $this->redirectToRoute('castellum_index',['_fragment'=>'collapse-'.$code]);
    }

    #[Route('/castellum/questions/{id}', name: 'castellum_questions', methods: ['GET'])]
    public function listQuestions(CastellumSubcategory $subcategory, EntityManagerInterface $em): Response
    {
        $qs=$em->getRepository(CastellumQuestion::class)->createQueryBuilder('q')
            ->andWhere('q.subcategory=:s')->setParameter('s',$subcategory)
            ->orderBy('q.updatedAt','DESC')->getQuery()->getResult();

        return $this->render('castellum/questions.html.twig', [
            'subcategory' => $subcategory,
            'questions'   => $qs,
            'labels'      => self::CATEGORY_LABELS,
            // Bandeau :
            'catCode'     => $subcategory->getCode(),
            'catLabel'    => self::CATEGORY_LABELS[$subcategory->getCode()] ?? $subcategory->getCode(),
        ]);
    }

    // ---------------- Création / Édition des questions ----------------
    #[Route('/castellum/questions/{id}/new', name: 'castellum_question_new', methods: ['GET','POST'])]
    public function newQuestion(Request $request, CastellumSubcategory $subcategory, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $q=new CastellumQuestion();
        $q->setSubcategory($subcategory)->setCategoryCode($subcategory->getCode());

        // Pré-remplir le chapitre si fourni par Formation
        $q->setFormationChapter($request->query->getInt('chapter', $q->getFormationChapter() ?? 0));
        $returnTo = (string) $request->query->get('return', '');

        $form=$this->createForm(CastellumQuestionType::class,$q);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Image principale (safe)
            $imgFile = $this->formFile($form, 'questionImageFile');
            $rmImage = ($request->request->get('removeImage') === '1');

            if ($imgFile) {
                @mkdir($this->uploadDir(),0777,true);
                $original=pathinfo($imgFile->getClientOriginalName(),PATHINFO_FILENAME);
                $safe=$slugger->slug($original)->lower(); $newName=$safe.'-'.uniqid().'.'.$imgFile->guessExtension();
                $imgFile->move($this->uploadDir(),$newName);
                $q->setQuestionImage($this->publicUploadPath($newName));
            } elseif ($rmImage && $q->getQuestionImage()) {
                $old = $q->getQuestionImage();
                if ($old && $this->isLocalPublic($old)) {
                    @unlink($this->filesystemPublic($old));
                }
                $q->setQuestionImage(null);
            }

            // Audio (safe)
            $audio=$this->formFile($form, 'questionAudioFile');
            $rmAudio = ($request->request->get('removeAudio') === '1');

            if($audio){
                @mkdir($this->audioUploadDir(),0777,true);
                $ext=$audio->guessExtension()?:'bin'; $new='q-audio-'.uniqid().'.'.$ext;
                $audio->move($this->audioUploadDir(),$new);
                $q->setQuestionAudio($this->publicAudioPath($new));
            } elseif ($rmAudio && $q->getQuestionAudio()) {
                $old = $q->getQuestionAudio();
                if ($old && $this->isLocalPublic($old)) {
                    @unlink($this->filesystemPublic($old));
                }
                $q->setQuestionAudio(null);
            }

            // QCM images 1..9 (safe)
            $rmMap = $request->request->all('removeQcmImage') ?? [];
            for($i=1;$i<=9;$i++){
                $f = $this->formFile($form, 'qcmImageFile'.$i);
                $setter='setQcmImage'.$i; $getter='getQcmImage'.$i;

                if($f){
                    @mkdir($this->qcmUploadDir(),0777,true);
                    $ext=$f->guessExtension()?:'jpg'; $new='qcm-'.$i.'-'.uniqid().'.'.$ext;
                    $f->move($this->qcmUploadDir(),$new);
                    if (method_exists($q,$setter)) $q->$setter($this->publicQcmPath($new));
                } else {
                    $askedRemove = isset($rmMap[(string)$i]) && $rmMap[(string)$i] === '1';
                    if ($askedRemove && method_exists($q,$getter) && method_exists($q,$setter)) {
                        $old = $q->$getter();
                        if ($old && $this->isLocalPublic($old)) {
                            @unlink($this->filesystemPublic($old));
                        }
                        $q->$setter(null);
                    }
                }
            }

            $q->touch();
            $em->persist($q); $em->flush();
            $this->addFlash('success','Question ajoutée.');

            if ($returnTo !== '') { return $this->redirect($returnTo); }
            return $this->redirectToRoute('castellum_questions',['id'=>$subcategory->getId()]);
        }

        return $this->render('castellum/question_form.html.twig', [
            'form'        => $form->createView(),
            'mode'        => 'new',
            'subcategory' => $subcategory,
            'returnTo'    => $returnTo,
            'catCode'     => $subcategory->getCode(),
            'catLabel'    => self::CATEGORY_LABELS[$subcategory->getCode()] ?? $subcategory->getCode(),
        ]);
    }

    #[Route('/castellum/question/{id}/edit', name: 'castellum_question_edit', methods: ['GET','POST'])]
    public function editQuestion(Request $request, CastellumQuestion $question, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $returnTo = (string) $request->query->get('return', '');

        $form = $this->createForm(CastellumQuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Image principale
            $imgFile  = $this->formFile($form, 'questionImageFile');
            $rmImage  = ($request->request->get('removeImage') === '1');

            if ($imgFile) {
                @mkdir($this->uploadDir(), 0777, true);
                $original = pathinfo($imgFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safe     = $slugger->slug($original)->lower();
                $newName  = $safe.'-'.uniqid().'.'.$imgFile->guessExtension();
                $imgFile->move($this->uploadDir(), $newName);

                $old = $question->getQuestionImage();
                if ($old && $this->isLocalPublic($old)) {
                    @unlink($this->filesystemPublic($old));
                }
                $question->setQuestionImage($this->publicUploadPath($newName));
            } elseif ($rmImage) {
                $old = $question->getQuestionImage();
                if ($old && $this->isLocalPublic($old)) {
                    @unlink($this->filesystemPublic($old));
                }
                $question->setQuestionImage(null);
            }

            // Audio
            $audFile  = $this->formFile($form, 'questionAudioFile');
            $rmAudio  = ($request->request->get('removeAudio') === '1');

            if ($audFile) {
                @mkdir($this->audioUploadDir(), 0777, true);
                $ext = $audFile->guessExtension() ?: 'bin';
                $new = 'q-audio-'.uniqid().'.'.$ext;
                $audFile->move($this->audioUploadDir(), $new);

                $old = $question->getQuestionAudio();
                if ($old && $this->isLocalPublic($old)) {
                    @unlink($this->filesystemPublic($old));
                }
                $question->setQuestionAudio($this->publicAudioPath($new));
            } elseif ($rmAudio) {
                $old = $question->getQuestionAudio();
                if ($old && $this->isLocalPublic($old)) {
                    @unlink($this->filesystemPublic($old));
                }
                $question->setQuestionAudio(null);
            }

            // QCM images 1..9
            $rmMap = $request->request->all('removeQcmImage') ?? [];
            for ($i = 1; $i <= 9; $i++) {
                $f = $this->formFile($form, 'qcmImageFile'.$i);
                $getter = 'getQcmImage'.$i;
                $setter = 'setQcmImage'.$i;

                if ($f) {
                    @mkdir($this->qcmUploadDir(), 0777, true);
                    $ext = $f->guessExtension() ?: 'jpg';
                    $new = 'qcm-'.$i.'-'.uniqid().'.'.$ext;
                    $f->move($this->qcmUploadDir(), $new);

                    if (method_exists($question, $getter)) {
                        $old = $question->$getter();
                        if ($old && $this->isLocalPublic($old)) {
                            @unlink($this->filesystemPublic($old));
                        }
                    }
                    if (method_exists($question, $setter)) {
                        $question->$setter($this->publicQcmPath($new));
                    }
                } else {
                    $askedRemove = isset($rmMap[(string)$i]) && $rmMap[(string)$i] === '1';
                    if ($askedRemove && method_exists($question, $getter) && method_exists($question, $setter)) {
                        $old = $question->$getter();
                        if ($old && $this->isLocalPublic($old)) {
                            @unlink($this->filesystemPublic($old));
                        }
                        $question->$setter(null);
                    }
                }
            }

            $question->setCategoryCode($question->getSubcategory()->getCode());
            $question->touch();
            $em->flush();

            $this->addFlash('success', 'Question mise à jour.');

            if ($returnTo !== '') { return $this->redirect($returnTo); }
            return $this->redirectToRoute('castellum_questions', ['id' => $question->getSubcategory()->getId()]);
        }

        return $this->render('castellum/question_form.html.twig', [
            'form'        => $form->createView(),
            'mode'        => 'edit',
            'subcategory' => $question->getSubcategory(),
            'returnTo'    => $returnTo,
            'catCode'     => $question->getSubcategory()->getCode(),
            'catLabel'    => self::CATEGORY_LABELS[$question->getSubcategory()->getCode()] ?? $question->getSubcategory()->getCode(),
        ]);
    }

    #[Route('/castellum/question/{id}/delete', name: 'castellum_question_delete', methods: ['POST'])]
    public function deleteQuestion(Request $request, CastellumQuestion $question, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if(!$this->isCsrfTokenValid('castellum_question_delete_'.$question->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }

        $img=$question->getQuestionImage(); if($img && $this->isLocalPublic($img)) @unlink($this->filesystemPublic($img));
        $aud=$question->getQuestionAudio(); if($aud && $this->isLocalPublic($aud)) @unlink($this->filesystemPublic($aud));
        for($i=1;$i<=9;$i++){ $g='getQcmImage'.$i; if(method_exists($question,$g)){ $p=$question->$g(); if($p && $this->isLocalPublic($p)) @unlink($this->filesystemPublic($p)); } }

        $subcatId=$question->getSubcategory()->getId();
        $em->remove($question); $em->flush();

        $this->addFlash('success','Question supprimée.');
        return $this->redirectToRoute('castellum_questions',['id'=>$subcatId]);
    }

    // ---------------- Suppression sous-catégorie ----------------
    #[Route('/castellum/subcategory/{id}/delete', name: 'castellum_sub_delete', methods: ['POST'])]
    public function deleteSubcategory(Request $request, CastellumSubcategory $subcategory, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $token=$request->request->get('_token');
        if(!$this->isCsrfTokenValid('castellum_sub_delete_'.$subcategory->getId(), $token)) {
            throw $this->createAccessDeniedException('CSRF invalide');
        }
        $code=$subcategory->getCode();
        $em->remove($subcategory); $em->flush();
        $this->addFlash('success','Sous-catégorie supprimée.');
        return $this->redirectToRoute('castellum_index',['_fragment'=>'cat-'.$code]);
    }

    // ---------------- Page "test" simple ----------------
    #[Route('/castellum/test', name: 'castellum_test', methods: ['GET'])]
    public function test(SessionInterface $session): Response
    {
        $config=$session->get('castellum.config', null);
        return $this->render('castellum/test.html.twig', ['config'=>$config]);
    }
}
