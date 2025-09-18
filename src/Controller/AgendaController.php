<?php
// src/Controller/AgendaController.php

namespace App\Controller;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class AgendaController extends AbstractController
{
    #[Route('/agenda/{weekOffset}', name: 'agenda', defaults: ['weekOffset' => 0])]
    public function index(int $weekOffset, EntityManagerInterface $em): Response
    {
        // Début de la semaine (lundi)
        $startOfWeek = new \DateTime();
        $startOfWeek->modify('Monday this week');
        $startOfWeek->modify("+$weekOffset week");

        // Fin de la semaine (dimanche)
        $endOfWeek = clone $startOfWeek;
        $endOfWeek->modify('+6 days')->setTime(23, 59, 59);

        // Récupérer les événements de la semaine
        $eventsRaw = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->where('e.date BETWEEN :start AND :end')
            ->setParameter('start', $startOfWeek->format('Y-m-d'))
            ->setParameter('end', $endOfWeek->format('Y-m-d'))
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.heure', 'ASC')
            ->getQuery()
            ->getResult();

        // Organiser les événements par jour et heure
        $events = [];
        foreach ($eventsRaw as $event) {
            $dayKey = $event->getDate()->format('Y-m-d');
            $hourKey = $event->getHeure()->format('H');
            $events[$dayKey][$hourKey][] = $event;
        }

        // Générer les jours de la semaine pour l'affichage
        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $day = clone $startOfWeek;
            $day->modify("+$i day");
            $days[] = $day;
        }

        // Heures de 8h à 22h
        $hours = range(8, 22);

        // Calculer quel jour est aujourd'hui
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
}
