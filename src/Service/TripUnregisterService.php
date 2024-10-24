<?php
namespace App\Service;

use App\Entity\Trip;
use App\Event\TripUnregistrationEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class TripUnregisterService
{
    private $entityManager;
    private $router;
    private $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function unregister(Trip $trip, $user): array
    {
        // Check if the user is already registered for the trip
        if (!$trip->getParticipants()->contains($user)) {
            return [
                'type' => 'warning',
                'message' => 'You are not registered for this trip.',
                'redirect' => 'app_main_index'
            ];
        }

        // Check if the trip is in a state where unregistration is allowed
        if ($trip->getState()->getLabel() !== 'open' && $trip->getState()->getLabel() !== 'closed') {
            return [
                'type' => 'warning',
                'message' => 'Unregistration is not allowed for this trip.',
                'redirect' => 'app_main_index'
            ];
        }

        // Remove the participant from the trip
        $trip->removeParticipant($user);
        $this->entityManager->persist($trip);
        $this->entityManager->flush();

        // Dispatch the TripUnregistrationEvent
        $this->eventDispatcher->dispatch(new TripUnregistrationEvent($trip), TripUnregistrationEvent::NAME);

        return [
            'type' => 'success',
            'message' => 'Vous êtes bien désinscrit de la sortie',
            'redirect' => 'app_main_index',
        ];
    }
}
