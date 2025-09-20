<?php
// src/Controller/AgendaController.php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class AgendaController extends AbstractController
{
    #[Route('/agenda/{weekOffset}', name: 'agenda', defaults: ['weekOffset' => 0])]
    public function index(int $weekOffset, EntityManagerInterface $em): Response
    {
        $startOfWeek = new \DateTime();
        $startOfWeek->modify('Monday this week');
        $startOfWeek->modify("+$weekOffset week");

        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days')->setTime(23, 59, 59);

        $eventsRaw = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.date BETWEEN :start AND :end')
            ->setParameter('start', $startOfWeek->format('Y-m-d'))
            ->setParameter('end', $endOfWeek->format('Y-m-d'))
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.heure', 'ASC')
            ->getQuery()
            ->getResult();

        $events = [];
        foreach ($eventsRaw as $event) {
            $dayKey = $event->getDate()->format('Y-m-d');
            $hourKey = $event->getHeure()->format('H');
            $events[$dayKey][$hourKey][] = $event;
        }

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = clone $startOfWeek;
            $day->modify("+$i day");
            $days[] = $day;
        }

        $hours = range(8, 22);

        $today = new \DateTime();
        $isToday = [];
        foreach ($days as $idx => $day) {
            $isToday[$idx] = $day->format('Y-m-d') === $today->format('Y-m-d');
        }

        return $this->render('agenda/index.html.twig', [
            'days' => $days,
            'hours' => $hours,
            'events' => $events,
            'weekOffset' => $weekOffset,
            'isToday' => $isToday,
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Événement modifié !');

            return $this->redirectToRoute('agenda');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }
    #[Route('/event/{id}/delete', name: 'event_delete', methods: ['POST'])]
        public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
        {
            // Vérification du token CSRF
            if ($this->isCsrfTokenValid('delete' . $event->getId(), $request->request->get('_token'))) {
                $em->remove($event);
                $em->flush();

                $this->addFlash('success', 'Événement supprimé avec succès !');
            } else {
                $this->addFlash('error', 'Échec de la suppression (token CSRF invalide).');
            }

            return $this->redirectToRoute('agenda');
        }

    #[Route('/event/new', name: 'event_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($event);
            $em->flush();
            $this->addFlash('success', 'Événement créé !');

            return $this->redirectToRoute('agenda');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }
   #[Route('/event/{id}/duplicate', name: 'event_duplicate', methods: ['POST'])]
        public function duplicate(Request $request, Event $event, EntityManagerInterface $em): Response
        {
            if ($this->isCsrfTokenValid('duplicate' . $event->getId(), $request->request->get('_token'))) {
                // On clone l'événement
                $newEvent = clone $event;

                // Décaler l'heure de +1h
                if ($event->getHeure() !== null) {
                    $newTime = (clone $event->getHeure())->modify('+1 hour');
                    $newEvent->setHeure($newTime);
                }

                $em->persist($newEvent);
                $em->flush();

                $this->addFlash('success', 'Événement dupliqué avec succès (1h plus tard) !');

                // === Calcul du weekOffset ===
                $today = new \DateTimeImmutable('today');
                $startOfWeekToday = $today->modify('monday this week')->setTime(0, 0);

                $eventDate = \DateTimeImmutable::createFromMutable($event->getDate()); 
                $startOfWeekEvent = $eventDate->modify('monday this week')->setTime(0, 0);

                $diffDays = $startOfWeekToday->diff($startOfWeekEvent)->days;
                $weekOffset = (int) floor($startOfWeekToday->diff($startOfWeekEvent)->days / 7);

                // Corriger le signe (avant/après aujourd'hui)
                if ($startOfWeekEvent < $startOfWeekToday) {
                    $weekOffset = -$weekOffset;
                }

                return $this->redirectToRoute('agenda', ['weekOffset' => $weekOffset]);
            }

            $this->addFlash('error', 'Échec de la duplication (token CSRF invalide).');
            return $this->redirectToRoute('agenda');
        }


}
