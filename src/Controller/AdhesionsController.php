<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdhesionsController extends AbstractController
{
    #[Route('/adhesions', name: 'app_adhesions')]
    public function index(): Response
    {
        return $this->render('adhesions/index.html.twig', [
            'controller_name' => 'AdhesionsController',
        ]);
    }
}
