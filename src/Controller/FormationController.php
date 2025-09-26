<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\CastellumSubcategory;
use App\Entity\FormationContent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[IsGranted('ROLE_USER')]
#[Route('/formation')]
class FormationController extends AbstractController
{
    /** Libellés des catégories (mêmes codes que Castellum) */
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

    // ----------------- Helpers stockage -----------------

    private function loadBlocksForPdf(CastellumSubcategory $sub, EntityManagerInterface $em): array
    {
        // Variante A : stockage dédié en table FormationContent (recommandé)
        if (class_exists(FormationContent::class)) {
            $content = $em->getRepository(FormationContent::class)
                        ->findOneBy(['subcategory' => $sub]);
            if ($content === null) return [];
            $blocks = $content->getBlocks();
            if (is_string($blocks)) {
                $decoded = json_decode($blocks, true);
                return is_array($decoded) ? $decoded : [];
            }
            return is_array($blocks) ? $blocks : [];
        }

        // Variante B : si tu avais un champ JSON directement sur la sous-catégorie
        if (method_exists($sub, 'getFormationBlocks')) {
            $blocks = $sub->getFormationBlocks();
            if (is_string($blocks)) {
                $decoded = json_decode($blocks, true);
                return is_array($decoded) ? $decoded : [];
            }
            return is_array($blocks) ? $blocks : [];
        }

        return [];
    }


    private function dataDir(): string
    {
        return $this->getParameter('kernel.project_dir').'/var/formation';
    }

    private function jsonPathFor(int $subId): string
    {
        return rtrim($this->dataDir(), '/').'/'.$subId.'.json';
    }

    /** @return array<int, array<string,mixed>> */
    private function readBlocks(int $subId): array
    {
        $file = $this->jsonPathFor($subId);
        if (!is_file($file)) {
            return [];
        }
        $raw = @file_get_contents($file);
        if ($raw === false) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function writeBlocks(int $subId, array $blocks): void
    {
        @mkdir($this->dataDir(), 0777, true);
        $file = $this->jsonPathFor($subId);
        @file_put_contents($file, json_encode($blocks, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    }

    private function uploadDir(): string
    {
        return $this->getParameter('kernel.project_dir').'/public/uploads/formation';
    }

    // ----------------- Pages -----------------

    #[Route('', name: 'formation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // regroupe les sous-catégories par code (mêmes codes que Castellum)
        $all = $em->getRepository(CastellumSubcategory::class)->createQueryBuilder('s')
            ->orderBy('s.code', 'ASC')->addOrderBy('s.name', 'ASC')
            ->getQuery()->getResult();

        $byCode = [];
        /** @var CastellumSubcategory $s */
        foreach ($all as $s) {
            $byCode[$s->getCode()][] = $s;
        }

        return $this->render('formation/index.html.twig', [
            'labels' => self::CATEGORY_LABELS,
            'byCode' => $byCode,
        ]);
    }

    #[Route('/categorie/{code}', name: 'formation_category', methods: ['GET'])]
    public function category(string $code, EntityManagerInterface $em): Response
    {
        $subs = $em->getRepository(CastellumSubcategory::class)->findBy(
            ['code' => $code],
            ['name' => 'ASC']
        );

        $label = self::CATEGORY_LABELS[$code] ?? $code;

        return $this->render('formation/category.html.twig', [
            'code'  => $code,
            'label' => $label,
            'subs'  => $subs,
        ]);
    }

    #[Route('/sous-categorie/{id}/pdf', name: 'formation_pdf', methods: ['GET'])]
    public function pdf(int $id, EntityManagerInterface $em): Response
    {
        /** @var CastellumSubcategory|null $sub */
        $sub = $em->getRepository(CastellumSubcategory::class)->find($id);
        if (!$sub) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        // ✅ Lire les blocs comme la page show() (var/formation/{id}.json)
        $blocks = $this->readBlocks($sub->getId());

        // (Facultatif) fallback si tu utilises aussi FormationContent un jour
        if (!$blocks && class_exists(FormationContent::class)) {
            $content = $em->getRepository(FormationContent::class)->findOneBy(['subcategory' => $sub]);
            if ($content) {
                $raw = $content->getBlocks();
                if (is_string($raw))      $blocks = json_decode($raw, true) ?: [];
                elseif (is_array($raw))   $blocks = $raw;
            }
        }

        $html = $this->renderView('formation/pdf.html.twig', [
            'subcategory' => $sub,
            'blocks'      => $blocks,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="formation-'.$sub->getId().'.pdf"',
            ]
        );
    }


    #[Route('/formation/sous-categorie/new', name: 'formation_sub_new', methods: ['POST'])]
    #[IsGranted('ROLE_TRAINING_EDITOR')]
    public function subNew(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $code  = (string) $request->request->get('code', '');
        $name  = trim((string) $request->request->get('name', ''));
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('formation_sub_new_'.$code, $token)) {
            return new JsonResponse(['ok' => false, 'error' => 'csrf'], 400);
        }
        if ($code === '' || $name === '') {
            return new JsonResponse(['ok' => false, 'error' => 'missing'], 422);
        }

        $repo   = $em->getRepository(CastellumSubcategory::class);
        $exists = $repo->findOneBy(['code' => $code, 'name' => $name]);
        if ($exists) {
            return new JsonResponse(['ok' => false, 'error' => 'exists'], 409);
        }

        $sub = (new CastellumSubcategory())->setCode($code)->setName($name);
        $em->persist($sub);
        $em->flush();

        return new JsonResponse([
            'ok'   => true,
            'id'   => $sub->getId(),
            'name' => $sub->getName(),
            'code' => $sub->getCode(),
        ]);
    }

    #[Route('/sous-categorie/{id}', name: 'formation_show', methods: ['GET'])]
    public function show(int $id, Request $request, EntityManagerInterface $em): Response
    {
        /** @var CastellumSubcategory|null $sub */
        $sub = $em->getRepository(CastellumSubcategory::class)->find($id);
        if (!$sub) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        // Lecture seule par défaut ; édition seulement si ?edit=1 ET rôle
        $wantsEdit = $request->query->getBoolean('edit', false);
        $canEdit   = $wantsEdit && ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_TRAINING_EDITOR'));

        // charge les blocs depuis var/formation/{id}.json
        $blocks = $this->readBlocks($sub->getId());

        // endpoints pour upload/save
        $uploadUrl = $this->generateUrl('formation_upload', ['id' => $sub->getId()]);
        $saveUrl   = $this->generateUrl('formation_save',   ['id' => $sub->getId()]);

        return $this->render('formation/show.html.twig', [
            'subcategory' => $sub,
            'blocks'      => $blocks,
            'canEdit'     => $canEdit,
            'uploadUrl'   => $uploadUrl,
            'saveUrl'     => $saveUrl,
        ]);
    }

    // --------------- Actions (upload / save / delete) ---------------

    #[Route('/sous-categorie/{id}/upload', name: 'formation_upload', methods: ['POST'])]
    public function upload(int $id, Request $request): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            return new JsonResponse(['ok' => false, 'error' => 'nofile'], 400);
        }

        @mkdir($this->uploadDir(), 0777, true);
        $ext = $file->guessExtension() ?: 'bin';
        $name = 'img-'.$id.'-'.uniqid().'.'.$ext;
        $file->move($this->uploadDir(), $name);

        $publicPath = 'uploads/formation/'.$name;
        return new JsonResponse([
            'ok'   => true,
            'path' => $publicPath,
            'url'  => '/'.$publicPath,
        ]);
    }

    #[Route('/formation/sous-categorie/{id}/report', name: 'formation_report', methods: ['POST'])]
    public function report(int $id, Request $request, EntityManagerInterface $em): Response
    {
        // CSRF
        if (!$this->isCsrfTokenValid('formation_report_'.$id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('formation_show', ['id' => $id]);
        }

        /** @var CastellumSubcategory|null $sub */
        $sub = $em->getRepository(CastellumSubcategory::class)->find($id);
        if (!$sub) {
            $this->addFlash('danger', 'Sous-catégorie introuvable.');
            return $this->redirectToRoute('formation_index');
        }

        // Ici tu pourrais: enregistrer un signalement en base, notifier, etc.
        // Pour l’instant on log/flash simplement.
        $this->addFlash('success', 'Merci, votre signalement a été transmis.');

        // Retour sur la page de lecture
        return $this->redirectToRoute('formation_show', ['id' => $id]);
    }

    #[Route('/sous-categorie/{id}/save', name: 'formation_save', methods: ['POST'])]
    public function save(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_TRAINING_EDITOR')) {
            return new JsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $blocks = is_array($data['blocks'] ?? null) ? $data['blocks'] : null;
        if ($blocks === null) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_request'], 400);
        }

        // Optionnel: mini validation des types
        $clean = [];
        foreach ($blocks as $b) {
            if (!is_array($b) || empty($b['type'])) continue;
            $row = ['type' => (string)$b['type']];
            if (isset($b['text'])) $row['text'] = (string)$b['text'];
            if (isset($b['path'])) $row['path'] = (string)$b['path'];
            $clean[] = $row;
        }

        $this->writeBlocks($id, $clean);
        return new JsonResponse(['ok' => true]);
    }

    /**
     * Supprime le contenu de formation de la sous-catégorie (les blocs JSON),
     * sans supprimer la sous-catégorie elle-même.
     */
    #[Route('/sous-categorie/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('formation_delete_'.$id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide');
            return $this->redirectToRoute('formation_index');
        }

        $sub = $em->getRepository(CastellumSubcategory::class)->find($id);
        if (!$sub) {
            $this->addFlash('danger', 'Sous-catégorie introuvable.');
            return $this->redirectToRoute('formation_index');
        }

        // Efface le JSON
        $file = $this->jsonPathFor($id);
        if (is_file($file)) @unlink($file);

        $this->addFlash('success', 'Contenu de formation supprimé.');
        return $this->redirectToRoute('formation_category', ['code' => $sub->getCode()]);
    }
}
