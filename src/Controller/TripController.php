<?php

namespace App\Controller;

use App\Entity\Trip;
use App\Repository\StateRepository;
use App\Repository\TripRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TripController extends AbstractController
{
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
