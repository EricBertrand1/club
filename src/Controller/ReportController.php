<?php
namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\Mailer\MailerInterface;
// use Symfony\Component\Mime\Email;

class ReportController extends AbstractController
{
    #[Route('/report', name: 'report_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em/*, MailerInterface $mailer*/): Response
    {
        // Lecture accessible aux remembered, POST réservé aux fully (optionnel)
        if ($request->isMethod('POST')) {
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }

        $report = new Report();
        if ($this->getUser()) {
            $report->setUser($this->getUser());
        }

        $form = $this->createForm(ReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($report);
            $em->flush();

            // (Optionnel) Envoyer un email aux admins :
            /*
            $email = (new Email())
                ->from('no-reply@ton-domaine.tld')
                ->to('admins@ton-domaine.tld')
                ->subject('[Club] Nouveau signalement')
                ->text(sprintf(
                    "Type: %s\nContexte: %s\nPar: %s\nLe: %s\n\n%s",
                    $report->getType(),
                    $report->getContext(),
                    $this->getUser()?->getUserIdentifier() ?? 'Anonyme',
                    $report->getCreatedAt()->format('d/m/Y H:i'),
                    $report->getMessage()
                ));
            $mailer->send($email);
            */

            $this->addFlash('report_success', 'Merci, votre signalement a bien été enregistré.');

            // Retour au menu principal
            return $this->redirectToRoute('app_home'); // adapte si ta route d’accueil a un autre nom
        }

        return $this->render('report/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
