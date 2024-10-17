<?php
namespace App\Event;

use App\Entity\Trip;
use Symfony\Contracts\EventDispatcher\Event;

class TripUnregistrationEvent extends Event
{
    public const NAME = 'trip.unregistration';

    private Trip $trip;

    public function __construct(Trip $trip)
    {
        $this->trip = $trip;
    }

    public function getTrip(): Trip
    {
        return $this->trip;
    }
}
