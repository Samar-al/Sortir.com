<?php

namespace App\Controller;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
            'cityName'=> $location->getCity()->getName(),
        ]);
    }

    #[Route('/lieu', name: 'app_location_index', methods: ['GET'])]
    public function index(Request $request, LocationRepository $locationRepository): Response
    {
      
        $query = $request->query->get('q', '');

        if ($query) {
            // Assuming the `findByName` method in LocationRepository searches by the name field
            $locations = $locationRepository->findByName($query);
        } else {
            // Retrieve all locations if no search query is present
            $locations = $locationRepository->findAll();
        }
        return $this->render('location/index.html.twig', [
            'locations' => $locations,
        ]);
    }
   

    #[Route('lieu/ajouter', name: 'app_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($location);
            $entityManager->flush();

            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('location/new.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('lieu/{id}', name: 'app_location_show', methods: ['GET'])]
    public function show(Location $location): Response
    {
        return $this->render('location/show.html.twig', [
            'location' => $location,
        ]);
    }
   

    #[Route('lieu/{id}/modifier', name: 'app_location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('location/edit.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }

    #[Route('lieu/{id}', name: 'app_location_delete', methods: ['POST'])]
    public function delete(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
    }
    
}
