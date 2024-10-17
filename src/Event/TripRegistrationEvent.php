<?php
namespace App\Event;

use App\Entity\Trip;
use Symfony\Contracts\EventDispatcher\Event;

class TripRegistrationEvent extends Event
{
    public const NAME = 'trip.registration';

    private $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    public function getTrip(): Trip
    {
        return $this->trip;
    }
}
