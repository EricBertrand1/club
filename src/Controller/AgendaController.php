<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AgendaController extends AbstractController
{
    #[Route('/agenda', name: 'app_agenda')]
    public function index(): Response
    {
        return $this->render('agenda/index.html.twig', [
            'controller_name' => 'AgendaController',
        ]);
    }
}
