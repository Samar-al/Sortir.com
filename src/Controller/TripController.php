<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Form\TripType;
use App\Repository\CityRepository;
use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/trip')]
final class TripController extends AbstractController
{

    #[Route(name: '/', methods: ['GET'])]
    public function indexHome(): Response
    {
        return $this->render('trip/index.html.twig', [
            'controllerName' => 'TripController',
        ]);
    }

    #[Route(name: 'app_trip_index', methods: ['GET'])]
    public function index(TripRepository $tripRepository): Response
    {
        return $this->render('trip/index.html.twig', [
            'trips' => $tripRepository->findAll(),
        ]);
    }

    #[Route('/create', name: 'app_trip_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        $trip = new Trip();
        $formTrip = $this->createForm(TripType::class, $trip);
        $formTrip->handleRequest($request);

        $cities = $cityRepository->findAll();

        if ($formTrip->isSubmitted() && $formTrip->isValid()) {

            $trip->getLocation()->setCity($request->get('city'));

            $entityManager->persist($trip);
            $entityManager->flush();

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
