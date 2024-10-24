<?php

namespace App\Service;

use App\Entity\Trip;
use App\Repository\StateRepository;

class TripStateService
{
    private $stateRepository;

    public function __construct(StateRepository $stateRepository)
    {
        $this->stateRepository = $stateRepository;
    }
    
    public function cannotEdit(Trip $trip): bool
    {
        return $trip->getState()->getLabel() !== 'created';
    }


    public function updateStateBasedOnAction(Trip $trip, string $action): void
    {
        if ($action === 'publish') {
            $openState = $this->stateRepository->findOneBy(['label' => 'open']);
            if ($openState) {
                $trip->setState($openState);
            }
        } else {
            $defaultState = $this->stateRepository->findOneBy(['label' => 'created']);
            if ($defaultState) {
                $trip->setState($defaultState);
            }
        }
    }
}
