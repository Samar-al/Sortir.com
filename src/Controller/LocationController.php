<?php

namespace App\Controller;

use App\Entity\Location;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LocationController extends AbstractController
{
    #[Route('/get-location/{id}', name: 'app_get_location', methods: ['GET'])]
    public function getLocation(Location $location): JsonResponse
    {
        // Return the location data as JSON
        return $this->json([
            'streetName' => $location->getStreetName(),
            'latitude' => $location->getLatitude(),
            'longitude' => $location->getLongitude(),
            'zipCode' => $location->getCity()->getZipCode(),
            'cityId' => $location->getCity()->getId(),
        ]);
    }
    
}
