<?php
namespace App\Service;

use App\Entity\Trip;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\StateRepository;

class TripCancellationService
{
    private EntityManagerInterface $entityManager;
    private StateRepository $stateRepository;

    public function __construct(EntityManagerInterface $entityManager, StateRepository $stateRepository)
    {
        $this->entityManager = $entityManager;
        $this->stateRepository = $stateRepository;
    }

    public function canCancel(Trip $trip, $user): string
    {
     
        if ($trip->getOrganiser() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
           
            return 'Vous n\'êtes pas autorisé à annuler cette sortie.';
        }
        return '';
    }

    public function cancel(Trip $trip, string $cancellationReason): string
    {
        if (empty($cancellationReason)) {
            return 'Veuillez fournir une raison pour l\'annulation.';
        }

        $cancelledState = $this->stateRepository->findOneBy(['label' => 'cancelled']);
        if (!$cancelledState) {
            return 'L\'état annulé est introuvable.';
        }

        $trip->setState($cancelledState);
        $trip->setReasonCancel($cancellationReason);
        $this->entityManager->persist($trip);
        $this->entityManager->flush();

        return 'La sortie a été annulée avec succès.';
    }
}