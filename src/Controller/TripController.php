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

//    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ajouter', name: 'app_trip_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CityRepository $cityRepository, StateRepository $stateRepository): Response
    {

        $trip = new Trip();
        // Set the default state where label is 'created'
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

            // Check which button was clicked
            $action = $request->request->get('action');
            if ($action === 'publish') {
                // Set state to 'open' if "Publier" was clicked
                $openState = $stateRepository->findOneBy(['label' => 'open']);
                if ($openState) {
                    $trip->setState($openState);
                }
            } else {
                // Set state to 'created' if "Enregistrer" was clicked
                $defaultState = $stateRepository->findOneBy(['label' => 'created']);
                if ($defaultState) {
                    $trip->setState($defaultState);
                }
            }
            $trip->setOrganiser($this->getUser());
            $entityManager->persist($trip);
            $entityManager->flush();
            $this->addFlash('success', 'Vous avez ajouté une sortie avec succès !');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
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

    #[Route('/modifier/{id}', name: 'app_trip_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Trip $trip, EntityManagerInterface $entityManager, CityRepository $cityRepository): Response
    {
        if($this->getUser()!==$trip->getOrganiser() ){
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cette sortie, vous n\'en êtes pas l\'auteur!');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
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

            $entityManager->flush();
            $this->addFlash('success', 'Vous avez modifié une sortie avec succès !');
            return $this->redirectToRoute('app_main_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('trip/edit.html.twig', [
            'trip' => $trip,
            'cities' => $cities,
            'formTrip' => $formTrip,
        ]);
    }

    #[Route('/supprimer/{id}', name: 'app_trip_delete', methods: ['POST'])]
    public function delete(Request $request, Trip $trip, EntityManagerInterface $entityManager): Response
    {

        if($this->getUser()!=$trip->getOrganiser() && !$this->isGranted("ROLE_MODERATOR") ){
            return $this->json(sprintf('{"msg":"You can not delete this trip, not yours!","code":false}'),Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenValid('delete'.$trip->getId(), $request->getPayload()->getString('_token'))) {
            return  $this->json(sprintf('{"msg":"CSRF token not valid!","code":false}'),Response::HTTP_FORBIDDEN);
        }
        $entityManager->remove($trip);
        $entityManager->flush();
        return $this->json(sprintf('{"msg":"Trip deleted","code":true}'),Response::HTTP_ACCEPTED);

//        if ($this->isCsrfTokenValid('delete'.$trip->getId(), $request->getPayload()->getString('_token'))) {
//            $entityManager->remove($trip);
//            $entityManager->flush();
//        }
//
//        return $this->redirectToRoute('app_trip_index', [], Response::HTTP_SEE_OTHER);
    }

   

    #[Route('/trip/{id}/inscription', name: 'app_trip_register', methods: ['POST'])]
    public function registerToTrip(Trip $trip, TripRepository $tripRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
    
        // Check if the user is already registered for the trip
        if ($trip->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'You are already registered for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Check if the registration deadline has passed
        if ($trip->getDateRegistrationLimit() < new \DateTimeImmutable()) {
            $this->addFlash('warning', 'The registration deadline has passed.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Check if the number of registrations has reached the maximum
        if ($trip->getParticipants()->count() >= $trip->getNumMaxRegistration()) {
            $this->addFlash('warning', 'The trip has reached its maximum number of participants.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Check if the trip is open (assuming "open" is a state id or label)
        if ($trip->getState()->getLabel() !== 'open') {
            $this->addFlash('warning', 'Registration is not allowed for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
    
        // Register the user for the trip
        $trip->addParticipant($user);
        $entityManager->persist($trip);
        $entityManager->flush();
    
        $this->addFlash('success', 'Vous vous êtes inscrit à la sortie avec succès !');
        return $this->redirectToRoute('app_main_index');
    }

    #[Route('/trip/{id}/desisstement', name: 'app_trip_unregister', methods: ['POST'])]
    public function unregisterToTrip(Trip $trip, TripRepository $tripRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Check if the user is already registered for the trip
        if (!$trip->getParticipants()->contains($user)) {
            $this->addFlash('warning', 'You are not registered for this trip.');
            return $this->redirectToRoute('app_main_index');
        }
       
        if ($trip->getState()->getLabel() !== 'open' && $trip->getState()->getLabel() !== 'closed') {
            $this->addFlash('warning', 'Unregistration is not allowed for this trip.');
            return $this->redirectToRoute('app_main_index');
        }

         // Register the user for the trip
         $trip->removeParticipant($user);
         $entityManager->persist($trip);
         $entityManager->flush();
     
         $this->addFlash('success', 'Vous êtes bien désinscrit de la sortie');
         return $this->redirectToRoute('app_main_index');

    }

    #[Route('/trip/{id}/publier', name: 'app_trip_publish', methods: ['GET'])]
    public function publishTrip(Trip $trip, StateRepository $stateRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if($trip->getOrganiser() !== $user) {
            $this->addFlash('warning', 'Vous n\'êtes pas l\'organisateur de cette sortie');
            return $this->redirectToRoute('app_main_index');
        }

        if ($trip->getState()->getLabel() !== 'created') {
            $this->addFlash('warning', 'Vous ne pouvez pas publier cette sortie');
            return $this->redirectToRoute('app_main_index');
        }
        $openState = $stateRepository->findOneBy(['label' => "open"]);
        $trip->setState($openState);
        $entityManager->flush();

        $this->addFlash('success', 'Votre sortie à été publiée avec succès');
         return $this->redirectToRoute('app_main_index');

    }
    

}
