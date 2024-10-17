<?php
namespace App\EventListener;

use App\Event\TripRegistrationEvent;
use App\Event\TripUnregistrationEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\StateRepository;

class TripListener
{
    private $entityManager;
    private $stateRepository;

    public function __construct(EntityManagerInterface $entityManager, StateRepository $stateRepository)
    {
        $this->entityManager = $entityManager;
        $this->stateRepository = $stateRepository;
    }

    public function onTripRegistration(TripRegistrationEvent $event)
    {
        $trip = $event->getTrip();

        // Check if the number of participants has reached the max
        if ($trip->getParticipants()->count() >= $trip->getNumMaxRegistration()) {
            // Change the state of the trip to 'closed'
            $closedState = $this->stateRepository->findOneBy(['label' => 'closed']);
            $trip->setState($closedState);

            // Persist the changes to the database
            $this->entityManager->persist($trip);
            $this->entityManager->flush();
        }
    }

    public function onTripUnregistration(TripUnregistrationEvent $event)
    {
        $trip = $event->getTrip();

        $now = new \DateTimeImmutable();
        $dateHourStart = $trip->getDateHourStart();
        $dateRegistrationLimit = $trip->getDateRegistrationLimit();
        $numMaxRegistration = $trip->getNumMaxRegistration();

        // If the trip still has available spots, dateHourStart is in the future, and registration is still allowed
        if ($trip->getParticipants()->count() < $numMaxRegistration &&
            $dateHourStart > $now &&
            $dateRegistrationLimit > $now) {

            // Find the 'open' state and assign it back to the trip
            $openState = $this->stateRepository->findOneBy(['label' => 'open']);
            if ($openState) {
                $trip->setState($openState);
                $this->entityManager->flush();
            }
        }
    }
}
