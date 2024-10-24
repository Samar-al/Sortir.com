<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Location;
use App\Form\CityType;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use App\Repository\TripRepository;
use App\Service\CityLoaderService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lieu')]
class LocationController extends AbstractController
{

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/', name: 'app_location_index', methods: ['GET'])]
    public function index(Request $request, LocationRepository $locationRepository, PaginatorInterface $paginator, TripRepository $tripRepository): Response
    {
      
        $query = $request->query->get('q', '');

        if ($query) {
            // Assuming the `findByName` method in LocationRepository searches by the name field
            $locations = $locationRepository->findByName($query);
        } else {
            // Retrieve all locations if no search query is present
            $locations = $locationRepository->findAll();
        }

        $locationsWithTrips = [];

        foreach ($locations as $location)
        {
            $hasTrip = $tripRepository->count(['location' => $location]) > 0;
            $locationsWithTrips[$location->getId()] = $hasTrip;
        }

         // Paginate the results
        $pagination = $paginator->paginate(
            $locations, // The query or query builder to paginate
            $request->query->getInt('page', 1), // Current page number, defaults to 1
            10 // Limit the number of entries per page to 10
        );
        return $this->render('location/index.html.twig', [
            'pagination' => $pagination,
            'locationsWithTrips' => $locationsWithTrips
        ]);
    }

    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[Route('/ajouter', name: 'app_location_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityLoaderService $cityLoaderService): Response
    {
        $location = new Location();

        if ($this->isGranted('ROLE_ADMIN')) {
            $form = $this->createForm(LocationType::class, $location, ['is_admin' => true]);
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

        $city = new City();

        $departments = $cityLoaderService->loadDepartments();

        $form = $this->createFormBuilder()
            ->add('location', LocationType::class, ['data' => $location, 'is_admin' => false])
            ->add('city', CityType::class, [
                'data' => $city,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $city = $form->get('city')->getData();

            if ($city == null || empty($city->getZipCode())) {
                $this->addFlash('danger', 'Vous devez sélectionner une ville !');
                return $this->redirectToRoute('app_location_new', [], Response::HTTP_SEE_OTHER);
            }

            $entityManager->persist($city);
            $entityManager->flush();

            $location->setCity($city);

            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash("success", "Vous avez ajouté un lieu avec succès !");
            return $this->redirectToRoute('app_trip_new', [], Response::HTTP_SEE_OTHER);

        }

        return $this->render('location/new.html.twig', [
            'departments' => $departments,
            'location' => $location,
            'form' => $form->createView(),

        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'app_location_show', methods: ['GET'])]
    public function show(Location $location, TripRepository $tripRepository): Response
    {
        $hasTrip = $tripRepository->count(['location' => $location]) > 0;

        return $this->render('location/show.html.twig', [
            'location' => $location,
            'hasTrip' => $hasTrip,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/modifier', name: 'app_location_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LocationType::class, $location, ['is_admin' => true]);
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

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}/supprimer', name: 'app_location_delete', methods: ['POST'])]
    public function delete(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$location->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($location);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_location_index', [], Response::HTTP_SEE_OTHER);
    }
    
}
