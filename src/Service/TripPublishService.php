<?php
namespace App\Service;

use App\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class TripPublishService
{
    private $entityManager;
    private $router;

    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function handleTripPublication(Trip $trip, $user, $stateRepository): array
    {
        // Check if the user is the organizer
        if ($trip->getOrganiser() !== $user) {
            return [
                'type' => 'warning',
                'message' => 'Vous n\'êtes pas l\'organisateur de cette sortie',
                'redirect' => new RedirectResponse($this->router->generate('app_main_index'))
            ];
        }

        // Check if the trip is still in the 'created' state
        if ($trip->getState()->getLabel() !== 'created') {
            return [
                'type' => 'warning',
                'message' => 'Vous ne pouvez pas publier cette sortie',
                'redirect' => new RedirectResponse($this->router->generate('app_main_index'))
            ];
        }

        // Check if the trip's dateHourStart or dateRegistrationLimit is in the past
        $now = new \DateTime();
        if ($trip->getDateHourStart() <= $now || $trip->getDateRegistrationLimit() <= $now) {
            return [
                'type' => 'warning',
                'message' => 'Vous ne pouvez pas publier une sortie dont la date est déjà passée ou pour laquelle la limite d\'inscription est dépassée.',
                'redirect' => new RedirectResponse($this->router->generate('app_main_index'))
            ];
        }

        // Set the trip state to 'open'
        $openState = $stateRepository->findOneBy(['label' => 'open']);
        $trip->setState($openState);

        // Persist changes to the database
        $this->entityManager->flush();

        return [
            'type' => 'success',
            'message' => 'Votre sortie a été publiée avec succès',
            'redirect' => new RedirectResponse($this->router->generate('app_main_index'))
        ];
    }
}
