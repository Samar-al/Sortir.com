<?php

namespace App\Controller;

use App\Entity\City;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CityController extends AbstractController
{
    #[Route('/get-city/{id}', name: 'app_get_city', methods: ['GET'])]
    public function getCity(City $city): JsonResponse
    {
        // Return the city data as JSON
        return $this->json([
            'zipCode' => $city->getZipCode(),
        ]);
    }
    
}
