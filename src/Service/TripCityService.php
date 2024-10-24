<?php
namespace App\Service;

use App\Entity\Trip;
use App\Repository\CityRepository;

class TripCityService
{
    private $cityRepository;

    public function __construct(CityRepository $cityRepository)
    {
        $this->cityRepository = $cityRepository;
    }

    public function updateCity(Trip $trip, ?int $cityId): void
    {
        if ($cityId) {
            $city = $this->cityRepository->find($cityId);
            if ($city) {
                $trip->getLocation()->setCity($city);
            }
        }
    }
}
