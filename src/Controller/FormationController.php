<?php

namespace App\Controller;

use App\Entity\CastellumSubcategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
#[Route('/formation')]
class FormationController extends AbstractController
{
    /** Libellés Castellum (identiques) */
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

    /** Fichier de stockage JSON par sous-catégorie */
    private function storageFile(int $subId): string
    {
        $dir = $this->getParameter('kernel.project_dir').'/var/formation';
        @mkdir($dir, 0777, true);
        return $dir.'/sub_'.$subId.'.json';
    }

    /** Chemin dossier upload public */
    private function uploadDirFs(): string
    {
        return $this->getParameter('kernel.project_dir').'/public/uploads/formation';
    }

    /** Chemin public (à stocker et renvoyer au front) */
    private function toPublicPath(string $filename): string
    {
        return 'uploads/formation/'.$filename;
    }

    #[Route('', name: 'formation_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // Récupère toutes les sous-catégories existantes
        $subs = $em->getRepository(CastellumSubcategory::class)
            ->createQueryBuilder('s')
            ->orderBy('s.code', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()->getResult();

        // Regroupe par code, en gardant 000..900 même si vide
        $byCode = [];
        foreach (array_keys(self::CATEGORY_LABELS) as $code) {
            $byCode[$code] = [];
        }
        foreach ($subs as $s) {
            /** @var CastellumSubcategory $s */
            $c = $s->getCode();
            if (!isset($byCode[$c])) $byCode[$c] = [];
            $byCode[$c][] = $s;
        }

        return $this->render('formation/index.html.twig', [
            'labels' => self::CATEGORY_LABELS,
            'byCode' => $byCode,
        ]);
    }

    #[Route('/categorie/{code}', name: 'formation_category', methods: ['GET'])]
    public function category(string $code, EntityManagerInterface $em): Response
    {
        if (!isset(self::CATEGORY_LABELS[$code])) {
            throw $this->createNotFoundException('Catégorie inconnue.');
        }

        $subs = $em->getRepository(CastellumSubcategory::class)
            ->createQueryBuilder('s')
            ->andWhere('s.code = :c')->setParameter('c', $code)
            ->orderBy('s.name', 'ASC')
            ->getQuery()->getResult();

        return $this->render('formation/category.html.twig', [
            'code'  => $code,
            'label' => self::CATEGORY_LABELS[$code],
            'subs'  => $subs,
        ]);
    }

    #[Route('/sous-categorie/{id}', name: 'formation_show', methods: ['GET'])]
    public function show(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $sub = $em->getRepository(CastellumSubcategory::class)->find($id);
        if (!$sub) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        $edit = $request->query->getBoolean('edit', false);
        $canEdit = $this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_TRAINING_EDITOR');

        // Lire le JSON de blocs si présent
        $file = $this->storageFile($sub->getId());
        $blocks = [];
        if (is_file($file)) {
            $json = @file_get_contents($file);
            $data = json_decode($json, true);
            if (is_array($data)) $blocks = $data;
        }

        return $this->render('formation/show.html.twig', [
            'subcategory' => $sub,
            'blocks'      => $blocks,
            'canEdit'     => $canEdit && $edit, // mode édition seulement si paramètre ?edit=1
            'uploadUrl'   => $this->generateUrl('formation_upload', ['id' => $sub->getId()]),
            'saveUrl'     => $this->generateUrl('formation_save',   ['id' => $sub->getId()]),
        ]);
    }

    /** Upload d’image (POST file) */
    #[Route('/sous-categorie/{id}/upload', name: 'formation_upload', methods: ['POST'])]
    public function upload(int $id, Request $request, SluggerInterface $slugger): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_TRAINING_EDITOR')) {
            return new JsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $file = $request->files->get('file');
        if (!$file) {
            return new JsonResponse(['ok' => false, 'error' => 'missing_file'], 400);
        }

        @mkdir($this->uploadDirFs(), 0777, true);
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe     = $slugger->slug((string)$original)->lower();
        $ext      = $file->guessExtension() ?: 'bin';
        $name     = $safe.'-'.uniqid().'.'.$ext;

        try {
            $file->move($this->uploadDirFs(), $name);
        } catch (\Throwable $e) {
            return new JsonResponse(['ok' => false, 'error' => 'move_failed'], 500);
        }

        $publicPath = $this->toPublicPath($name);
        // On renvoie à la fois path (public relative) et url complète
        return new JsonResponse(['ok' => true, 'path' => $publicPath, 'url' => '/'.$publicPath]);
    }

    /** Sauvegarde des blocs */
    #[Route('/sous-categorie/{id}/save', name: 'formation_save', methods: ['POST'])]
    public function save(int $id, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_TRAINING_EDITOR')) {
            return new JsonResponse(['ok' => false, 'error' => 'forbidden'], 403);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['blocks']) || !is_array($payload['blocks'])) {
            return new JsonResponse(['ok' => false, 'error' => 'bad_payload'], 400);
        }

        // Sanitize minimal : on garde uniquement les types/attributs attendus
        $allowedTypes = [
            'chapter', 'subchapter', 'p-full',
            'img-full', 'left-text-right-img', 'left-img-right-text'
        ];
        $clean = [];
        foreach ($payload['blocks'] as $b) {
            if (!is_array($b) || empty($b['type']) || !in_array($b['type'], $allowedTypes, true)) {
                continue;
            }
            $row = ['type' => $b['type']];
            if (isset($b['text']) && is_string($b['text'])) {
                $row['text'] = $b['text'];
            }
            if (isset($b['path']) && is_string($b['path'])) {
                // On stocke une path publique relative (ex: uploads/formation/xxx.jpg)
                $row['path'] = ltrim($b['path'], '/');
            }
            $clean[] = $row;
        }

        $file = $this->storageFile($id);
        try {
            @file_put_contents($file, json_encode($clean, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
            return new JsonResponse(['ok' => false, 'error' => 'write_failed'], 500);
        }

        return new JsonResponse(['ok' => true]);
    }

    /** Suppression du contenu de formation (pas la sous-catégorie elle-même) */
    #[Route('/sous-categorie/{id}/delete', name: 'formation_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_TRAINING_EDITOR')) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('formation_delete_'.$id, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('formation_index');
        }

        $sub = $em->getRepository(CastellumSubcategory::class)->find($id);
        if (!$sub) {
            throw $this->createNotFoundException('Sous-catégorie introuvable.');
        }

        $f = $this->storageFile($id);
        if (is_file($f)) @unlink($f);

        $this->addFlash('success', 'Contenu de formation supprimé.');
        return $this->redirectToRoute('formation_category', ['code' => $sub->getCode()]);
    }
}
