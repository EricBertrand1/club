<?php
namespace App\Controller;

use App\Entity\Objet;
use App\Form\ObjetType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;




#[Route('/boutique')]
class BoutiqueController extends AbstractController
{
    #[Route('', name: 'boutique_index')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        // Filtres GET
        $filters = [
            'q'         => trim((string) $request->query->get('q', '')),
            'categorie' => (string) $request->query->get('categorie', ''),
            'pmin'      => $request->query->get('pmin'),
            'pmax'      => $request->query->get('pmax'),
            'order'     => (string) $request->query->get('order', 'date_desc'),
        ];

        $qb = $em->getRepository(Objet::class)->createQueryBuilder('o');

        if ($filters['q'] !== '') {
            $q = mb_strtolower($filters['q']);
            $qb->andWhere('LOWER(o.titreObjet) LIKE :q OR LOWER(o.description) LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($filters['categorie'] !== '') {
            $qb->andWhere('o.categorie = :cat')->setParameter('cat', $filters['categorie']);
        }

        if ($filters['pmin'] !== null && $filters['pmin'] !== '') {
            $qb->andWhere('o.prix >= :pmin')->setParameter('pmin', (int) $filters['pmin']);
        }

        if ($filters['pmax'] !== null && $filters['pmax'] !== '') {
            $qb->andWhere('o.prix <= :pmax')->setParameter('pmax', (int) $filters['pmax']);
        }

        // Tri
        switch ($filters['order']) {
            case 'prix_asc':
                $qb->orderBy('o.prix', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('o.prix', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('o.date', 'ASC')->addOrderBy('o.idObjet', 'ASC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('o.date', 'DESC')->addOrderBy('o.idObjet', 'DESC');
                break;
        }

        $objets = $qb->getQuery()->getResult();

        return $this->render('boutique/index.html.twig', [
            'objets'     => $objets,
            'filters'    => $filters,
            // On réutilise la liste des catégories du formulaire
            'categories' => \App\Form\ObjetType::CATEGORIES,
        ]);
    }

    #[Route('/new', name: 'boutique_new')]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $objet = new Objet();

        $form = $this->createForm(ObjetType::class, $objet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleUploads($form, $objet, $slugger);
            $em->persist($objet);
            $em->flush();

            $this->addFlash('success', 'Objet ajouté à la boutique !');
            return $this->redirectToRoute('boutique_index');
        }

        return $this->render('boutique/form.html.twig', [
            'form'    => $form->createView(),
            'objet'   => $objet,
            'is_edit' => false,
        ]);
    }

    #[Route('/{idObjet}', name: 'boutique_show', requirements: ['idObjet' => '\d+'])]
    public function show(#[MapEntity(id: 'idObjet')] Objet $objet): Response
    {
        return $this->render('boutique/show.html.twig', ['objet' => $objet]);
    }

    #[Route('/{idObjet}/edit', name: 'boutique_edit', requirements: ['idObjet' => '\d+'])]
    public function edit(#[MapEntity(id: 'idObjet')] Objet $objet, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ObjetType::class, $objet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleUploads($form, $objet, $slugger);
            $em->flush();

            $this->addFlash('success', 'Objet mis à jour !');
            return $this->redirectToRoute('boutique_show', ['idObjet' => $objet->getIdObjet()]);
        }

        return $this->render('boutique/form.html.twig', [
            'form'    => $form->createView(),
            'objet'   => $objet,
            'is_edit' => true,
        ]);
    }

    private function archiveImagesOnDelete(Objet $objet): void
    {
        $srcDir = rtrim($this->getParameter('uploads_boutique_dir'), DIRECTORY_SEPARATOR);
        $dstDir = rtrim($this->getParameter('uploads_boutique_archive_dir'), DIRECTORY_SEPARATOR);

        $fs = new Filesystem();
        $fs->mkdir($dstDir);

        $files = array_filter([
            $objet->getImageObjet1(),
            $objet->getImageObjet2(),
            $objet->getImageObjet3(),
        ]);

        if (!$files) {
            return;
        }

        $stamp = (new \DateTimeImmutable())->format('Ymd_His');

        foreach ($files as $file) {
            $src = $srcDir . DIRECTORY_SEPARATOR . $file;
            if (!$fs->exists($src)) {
                continue; // fichier déjà manquant : on ignore
            }

            $base = pathinfo($file, PATHINFO_FILENAME);
            $ext  = pathinfo($file, PATHINFO_EXTENSION);
            $dest = $dstDir . DIRECTORY_SEPARATOR . $file;

            // Si un fichier du même nom existe déjà dans l’archive, on suffixe avec un timestamp
            if ($fs->exists($dest)) {
                $dest = $dstDir . DIRECTORY_SEPARATOR . sprintf('%s__%s%s',
                    $base,
                    $stamp,
                    $ext ? '.'.$ext : ''
                );
            }

            // Déplacement (rename = move)
            $fs->rename($src, $dest, true);
        }
    }

    #[Route('/{idObjet}/delete', name: 'boutique_delete', methods: ['POST'], requirements: ['idObjet' => '\d+'])]
    public function delete(Request $request, #[MapEntity(id: 'idObjet')] Objet $objet, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$objet->getIdObjet(), $request->request->get('_token'))) {

            try {
                $this->archiveImagesOnDelete($objet); // 👈 déplace les fichiers
            } catch (\Throwable $e) {
                // On n’empêche pas la suppression de l’annonce si l’archive échoue
                $this->addFlash('warning', "Annonce supprimée, mais l’archivage des images a échoué.");
            }

            $em->remove($objet);
            $em->flush();

            $this->addFlash('success', 'Objet supprimé (images archivées).');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('boutique_index');
    }


    private function handleUploads($form, Objet $objet, SluggerInterface $slugger): void
    {
        /** @var UploadedFile|null $f1 */
        $f1 = $form->get('image1')->getData();
        /** @var UploadedFile|null $f2 */
        $f2 = $form->get('image2')->getData();
        /** @var UploadedFile|null $f3 */
        $f3 = $form->get('image3')->getData();

        $uploadDir = $this->getParameter('uploads_boutique_dir');
        (new Filesystem())->mkdir($uploadDir);

        $save = function (?UploadedFile $file) use ($slugger, $uploadDir): ?string {
            if (!$file) return null;
            $safeName = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $newName  = $safeName.'-'.uniqid().'.'.$file->guessExtension();
            $file->move($uploadDir, $newName);
            return $newName;
        };

        if ($name = $save($f1)) $objet->setImageObjet1($name);
        if ($name = $save($f2)) $objet->setImageObjet2($name);
        if ($name = $save($f3)) $objet->setImageObjet3($name);
    }
}
