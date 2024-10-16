<?php

namespace App\Entity;

use App\Repository\TripRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TripRepository::class)]
class Trip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateHourStart = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateRegistrationLimit = null;

    #[ORM\Column]
    private ?int $numMaxRegistration = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tripDetails = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Location $location = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?State $state = null;

    #[ORM\ManyToOne(inversedBy: 'trips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Base $base = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, mappedBy: 'trips')]
    private Collection $participants;

    #[ORM\ManyToOne(inversedBy: 'organisedTrips')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organiser = null;

    #[ORM\Column]
    private ?bool $isArchived = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reasonCancel = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDateHourStart(): ?\DateTimeImmutable
    {
        return $this->dateHourStart;
    }

    public function setDateHourStart(\DateTimeImmutable $dateHourStart): static
    {
        $this->dateHourStart = $dateHourStart;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDateRegistrationLimit(): ?\DateTimeImmutable
    {
        return $this->dateRegistrationLimit;
    }

    public function setDateRegistrationLimit(\DateTimeImmutable $dateRegistrationLimit): static
    {
        $this->dateRegistrationLimit = $dateRegistrationLimit;

        return $this;
    }

    public function getNumMaxRegistration(): ?int
    {
        return $this->numMaxRegistration;
    }

    public function setNumMaxRegistration(int $numMaxRegistration): static
    {
        $this->numMaxRegistration = $numMaxRegistration;

        return $this;
    }

    public function getTripDetails(): ?string
    {
        return $this->tripDetails;
    }

    public function setTripDetails(?string $tripDetails): static
    {
        $this->tripDetails = $tripDetails;

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getBase(): ?Base
    {
        return $this->base;
    }

    public function setBase(?Base $base): static
    {
        $this->base = $base;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->addTrip($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        if ($this->participants->removeElement($participant)) {
            $participant->removeTrip($this);
        }

        return $this;
    }

    public function getOrganiser(): ?Participant
    {
        return $this->organiser;
    }

    public function setOrganiser(?Participant $organiser): static
    {
        $this->organiser = $organiser;

        return $this;
    }

    public function isArchived(): ?bool
    {
        return $this->isArchived;
    }

    public function setArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;

        return $this;
    }

    public function getReasonCancel(): ?string
    {
        return $this->reasonCancel;
    }

    public function setReasonCancel(?string $reasonCancel): static
    {
        $this->reasonCancel = $reasonCancel;

        return $this;
    }


}
