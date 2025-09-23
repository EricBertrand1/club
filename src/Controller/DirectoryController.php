<?php
namespace App\Controller;

use App\Entity\DirectoryEntry;
use App\Form\DirectoryEntryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DirectoryController extends AbstractController
{
    #[Route('/directory/{id}/edit', name: 'annuaire_edit', methods: ['GET','POST'])]
    public function edit(Request $request, DirectoryEntry $entry, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DirectoryEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('annuaire_success', 'Adresse mise à jour.');
            return $this->redirectToRoute('annuaire_index');
        }

        return $this->render('directory/edit.html.twig', [
            'form'  => $form->createView(),
            'entry' => $entry,
        ]);
    }

    #[Route('/annuaire', name: 'annuaire_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(DirectoryEntry::class);

        // Filtres GET
        $cat  = $request->query->get('cat');     // Catégorie exacte
        $typ  = $request->query->get('type');    // Web/Localisé
        $q    = $request->query->get('q');       // recherche texte globale (désignation/nom/ville/email/website)
        $min  = $request->query->get('min', ''); // note min
        $desc = $request->query->get('desc');    // ✨ recherche sur description (mots)

        $qb = $repo->createQueryBuilder('e');

        if ($cat) { $qb->andWhere('e.category = :cat')->setParameter('cat', $cat); }
        if ($typ) { $qb->andWhere('e.type = :typ')->setParameter('typ', $typ); }
        if ($q) {
            $qb->andWhere('(LOWER(e.designation) LIKE :q OR LOWER(e.lastName) LIKE :q OR LOWER(e.firstName) LIKE :q OR LOWER(e.city) LIKE :q OR LOWER(e.email) LIKE :q OR LOWER(e.website) LIKE :q)')
            ->setParameter('q', '%'.mb_strtolower($q).'%');
        }
        if ($min !== '' && is_numeric($min)) {
            $qb->andWhere('e.rating >= :min')->setParameter('min', (int)$min);
        }
        // ✨ Filtre description : chaque mot doit apparaître (AND)
        if ($desc) {
            $words = preg_split('/\s+/', trim($desc)) ?: [];
            $i = 0;
            foreach ($words as $w) {
                $w = mb_strtolower($w);
                if ($w === '') continue;
                $param = 'd'.$i++;
                $qb->andWhere("LOWER(e.description) LIKE :$param")->setParameter($param, '%'.$w.'%');
            }
        }

        $qb->orderBy('e.designation', 'ASC')->addOrderBy('e.lastName', 'ASC');
        $entries = $qb->getQuery()->getResult();

        // Catégories disponibles
        $catsDb = $em->createQuery('SELECT DISTINCT e.category FROM App\Entity\DirectoryEntry e ORDER BY e.category ASC')->getScalarResult();
        $categories = array_values(array_unique(array_merge(
            array_map(fn($row) => $row['category'], $catsDb),
            ['Automobile','Alimentaire','Matériaux','Outils','Services','Autre']
        )));

        return $this->render('directory/index.html.twig', [
            'entries'    => $entries,
            'categories' => $categories,
            'types'      => ['Web','Localisé'],
            'f' => ['cat' => $cat, 'type' => $typ, 'q' => $q, 'min' => $min, 'desc' => $desc], // ✨
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
        $form = $this->createForm(DirectoryEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entry);
            $em->flush();

            $this->addFlash('success', 'Adresse enregistrée.');
            return $this->redirectToRoute('directory_index');
        }

        return $this->render('directory/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
