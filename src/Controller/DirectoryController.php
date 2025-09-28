<?php
namespace App\Controller;

use App\Entity\DirectoryEntry;
use App\Form\DirectoryEntryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DirectoryController extends AbstractController
{
    #[Route('/annuaire', name: 'annuaire_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(DirectoryEntry::class);

        // Filtres GET
        $cat = $request->query->get('cat');      // Catégorie exacte
        $typ = $request->query->get('type');     // Web/Localisé
        $q   = $request->query->get('q');        // recherche multi-mots (OR)
        $min = $request->query->get('min', '');  // note min

        $qb = $repo->createQueryBuilder('e');

        if ($cat) {
            $qb->andWhere('e.category = :cat')->setParameter('cat', $cat);
        }
        if ($typ) {
            $qb->andWhere('e.type = :typ')->setParameter('typ', $typ);
        }

        // ----- Recherche multi-mots (OR global) -----
        // Si l’utilisateur tape "mairie paris", on matche une fiche si
        // "mairie" OU "paris" apparaissent dans AU MOINS UN des champs ci-dessous.
        if ($q) {
            $words = preg_split('/\s+/', trim((string) $q)) ?: [];
            $groupOr = [];
            $i = 0;

            foreach ($words as $w) {
                $w = mb_strtolower($w);
                if ($w === '') { continue; }

                $param = 'w' . $i++;
                // Pour CHAQUE mot, on prépare un sous-or sur l’ensemble des colonnes
                $groupOr[] = sprintf(
                    '(LOWER(e.designation) LIKE :%1$s
                       OR LOWER(e.lastName)   LIKE :%1$s
                       OR LOWER(e.firstName)  LIKE :%1$s
                       OR LOWER(e.address)    LIKE :%1$s
                       OR LOWER(e.postalCode) LIKE :%1$s
                       OR LOWER(e.city)       LIKE :%1$s
                       OR LOWER(e.email)      LIKE :%1$s
                       OR LOWER(e.website)    LIKE :%1$s
                       OR LOWER(e.description) LIKE :%1$s)',
                    $param
                );
                $qb->setParameter($param, '%'.$w.'%');
            }

            if (!empty($groupOr)) {
                // On exige : (blocMot1) OR (blocMot2) OR (blocMot3) …
                $qb->andWhere(implode(' OR ', $groupOr));
            }
        }

        if ($min !== '' && is_numeric($min)) {
            $qb->andWhere('e.rating >= :min')->setParameter('min', (int)$min);
        }

        $qb->orderBy('e.designation', 'ASC')->addOrderBy('e.lastName', 'ASC');
        $entries = $qb->getQuery()->getResult();

        // Catégories disponibles (liste + valeurs présentes en base)
        $catsDb = $em->createQuery('SELECT DISTINCT e.category FROM App\Entity\DirectoryEntry e ORDER BY e.category ASC')->getScalarResult();
        $categories = array_values(array_unique(array_filter(array_merge(
            array_map(fn($row) => $row['category'], $catsDb),
            ['Administration','Agricole','Détente','Formation','Information','Politique','Religion','Services','Autre']
        ))));

        // Permissions calculées (édition/suppression) par entrée
        $canEditById = [];
        $canDeleteById = [];
        foreach ($entries as $e) {
            $isOwner = method_exists($e, 'getCreatedBy') ? ($e->getCreatedBy() === $this->getUser()) : false;
            $canEditById[$e->getId()]   = $this->isGranted('ROLE_ADMIN') || $isOwner;
            $canDeleteById[$e->getId()] = $this->isGranted('ROLE_ADMIN') || $isOwner;
        }

        return $this->render('directory/index.html.twig', [
            'entries'        => $entries,
            'categories'     => $categories,
            'types'          => ['Web','Localisé'],
            // plus de 'desc' ici
            'f'              => ['cat' => $cat, 'type' => $typ, 'q' => $q, 'min' => $min],
            'canEditById'    => $canEditById,
            'canDeleteById'  => $canDeleteById,
        ]);
    }

    #[Route('/directory/{id}/edit', name: 'annuaire_edit', methods: ['GET','POST'])]
    public function edit(Request $request, DirectoryEntry $entry, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DirectoryEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Adresse mise à jour.');
            return $this->redirectToRoute('annuaire_index');
        }

        return $this->render('directory/edit.html.twig', [
            'form'  => $form->createView(),
            'entry' => $entry,
        ]);
    }

    #[Route('/directory/new', name: 'annuaire_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } else {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }

        $entry = new DirectoryEntry();

        // valeur par défaut uniquement à la création
        if ($entry->getRating() === null) {
            $entry->setRating(0);
        }

        $form = $this->createForm(DirectoryEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entry);
            $em->flush();

            $this->addFlash('success', 'Adresse enregistrée.');
            return $this->redirectToRoute('annuaire_index');
        }

        return $this->render('directory/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/annuaire/{id}/delete', name: 'annuaire_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $entry = $em->getRepository(DirectoryEntry::class)->find($id);
        if (!$entry) {
            $this->addFlash('danger', 'Adresse introuvable.');
            return $this->redirectToRoute('annuaire_index');
        }

        if (!$this->isCsrfTokenValid('annuaire_delete_'.$entry->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('annuaire_index');
        }

        $isOwner = method_exists($entry, 'getCreatedBy') ? ($entry->getCreatedBy() === $this->getUser()) : false;
        if (!$this->isGranted('ROLE_ADMIN') && !$isOwner) {
            $this->addFlash('danger', 'Vous ne pouvez pas supprimer cette adresse.');
            return $this->redirectToRoute('annuaire_index');
        }

        $em->remove($entry);
        $em->flush();

        $this->addFlash('success', 'Adresse supprimée.');
        return $this->redirectToRoute('annuaire_index');
    }
}
