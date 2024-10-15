<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TripController extends AbstractController
{
    #[Route('/', name: 'app_trip')]
    public function index(): Response
    {
        return $this->render('trip/index.html.twig', [
            'controller_name' => 'TripController',
        ]);
    }
}
