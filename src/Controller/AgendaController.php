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
}
