<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Location;
use App\Entity\Trip;
use App\Form\TripType;
use App\Repository\CityRepository;
use App\Repository\StateRepository;
use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
final class TripController extends AbstractController
{

    #[Route('/', name: 'app_trip_index', methods: ['GET'])]
    public function index(TripRepository $tripRepository): Response
    {
        return $this->render('trip/index.html.twig', [
            'trips' => $tripRepository->findAll(),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajouter', name: 'app_trip_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository, StateRepository $stateRepository): Response
    {

        $trip = new Trip();
        // Set the default state where label is 'created'
        $defaultState = $stateRepository->findOneBy(['label' => 'created']);
        if ($defaultState) {
            $trip->setState($defaultState);
        }
        $formTrip = $this->createForm(TripType::class, $trip);
        $formTrip->handleRequest($request);

        $cities = $cityRepository->findAll();

        if ($formTrip->isSubmitted() && $formTrip->isValid()) {

            $cityId = $request->request->get('city');
            $city = $cityRepository->find($cityId);

            if ($city) {
                // Set the city on the location of the trip
                $trip->getLocation()->setCity($city);
            }
            $trip->setOrganiser($this->getUser());
            $entityManager->persist($trip);
            $entityManager->flush();
            $this->addFlash('success', 'Vous avez ajouté une sortie avec succès !');
            return $this->redirectToRoute('app_trip_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trip/new.html.twig', [
            'trip' => $trip,
            'cities' => $cities,
            'formTrip' => $formTrip->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_trip_show', methods: ['GET'])]
    public function show(Trip $trip): Response
    {
        return $this->render('trip/show.html.twig', [
            'trip' => $trip,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_trip_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TripType::class, $trip);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_trip_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trip/edit.html.twig', [
            'trip' => $trip,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_trip_delete', methods: ['POST'])]
    public function delete(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$trip->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trip);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_trip_index', [], Response::HTTP_SEE_OTHER);
    }

   
}
