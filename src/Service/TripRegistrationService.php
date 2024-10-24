<?php
namespace App\Service;

use App\Entity\Trip;
use App\Entity\Participant;
use Doctrine\ORM\EntityManagerInterface;

class TripRegistrationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function canRegister(Trip $trip, Participant $user): bool
    {
        if ($trip->getParticipants()->contains($user)) {
            return false;
        }

        if ($trip->getDateRegistrationLimit() < new \DateTimeImmutable()) {
            return false;
        }

        if ($trip->getParticipants()->count() >= $trip->getNumMaxRegistration()) {
            return false;
        }

        if ($trip->getState()->getLabel() !== 'open') {
            return false;
        }

        return true;
    }

    public function register(Trip $trip, Participant $user): void
    {
        $trip->addParticipant($user);
        $this->entityManager->persist($trip);
        $this->entityManager->flush();
    }
}


