<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_MAIL', fields: ['mail'])]
class Participant implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $mail = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\Length(
        min:3,
        max:128,
        minMessage:"Il faut au minimum {{ limit }} caractères",
        maxMessage:"Vous ne pouvez dépasser les {{ limit }} caractères"
    )]
    #[ORM\Column(length: 128)]
    private ?string $firstname = null;

    #[Assert\Length(
        min:3,
        max:128,
        minMessage:"Il faut au minimum {{ limit }} caractères",
        maxMessage:"Vous ne pouvez dépasser les {{ limit }} caractères"
    )]
    #[ORM\Column(length: 128)]
    private ?string $lastname = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column]
    private ?bool $isAdmin = false;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Base $base = null;

    /**
     * @var Collection<int, Trip>
     */
    #[ORM\ManyToMany(targetEntity: Trip::class, inversedBy: 'participants')]
    private Collection $trips;

    /**
     * @var Collection<int, Trip>
     */
    #[ORM\OneToMany(targetEntity: Trip::class, mappedBy: 'organiser')]
    private Collection $organisedTrips;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'owner')]
    private Collection $privateGroups;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'members')]
    private Collection $members;

    public function __construct()
    {
        $this->isAdmin  = false;
        $this->isActive  = true;
        $this->trips = new ArrayCollection();
        $this->organisedTrips = new ArrayCollection();
        $this->privateGroups = new ArrayCollection();
        $this->members = new ArrayCollection();
    }
    public function __toString(): string
    {
         // Ensure both first name and last name are available
        if ($this->firstname && $this->lastname) {
            // Return the first name and the first letter of the last name, followed by a dot
            return ucfirst($this->firstname) . ' ' . strtoupper($this->lastname[0]) . '.';
        }

        // Fallback in case one of the names is null
        return $this->firstname ?: 'Unknown';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->mail;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->isAdmin;
    }

    public function setAdmin(bool $isAdmin): static
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
     * @return Collection<int, Trip>
     */
    public function getTrips(): Collection
    {
        return $this->trips;
    }

    public function addTrip(Trip $trip): static
    {
        if (!$this->trips->contains($trip)) {
            $this->trips->add($trip);
        }

        return $this;
    }

    public function removeTrip(Trip $trip): static
    {
        $this->trips->removeElement($trip);

        return $this;
    }

    /**
     * @return Collection<int, Trip>
     */
    public function getOrganisedTrips(): Collection
    {
        return $this->organisedTrips;
    }

    public function addOrganisedTrip(Trip $organisedTrip): static
    {
        if (!$this->organisedTrips->contains($organisedTrip)) {
            $this->organisedTrips->add($organisedTrip);
            $organisedTrip->setOrganiser($this);
        }

        return $this;
    }

    public function removeOrganisedTrip(Trip $organisedTrip): static
    {
        if ($this->organisedTrips->removeElement($organisedTrip)) {
            // set the owning side to null (unless already changed)
            if ($organisedTrip->getOrganiser() === $this) {
                $organisedTrip->setOrganiser(null);
            }
        }

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getPrivateGroups(): Collection
    {
        return $this->privateGroups;
    }

    public function addPrivateGroup(Group $privateGroup): static
    {
        if (!$this->privateGroups->contains($privateGroup)) {
            $this->privateGroups->add($privateGroup);
            $privateGroup->setOwner($this);
        }

        return $this;
    }

    public function removePrivateGroup(Group $privateGroup): static
    {
        if ($this->privateGroups->removeElement($privateGroup)) {
            // set the owning side to null (unless already changed)
            if ($privateGroup->getOwner() === $this) {
                $privateGroup->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(Group $member): static
    {
        if (!$this->members->contains($member)) {
            $this->members->add($member);
            $member->addMember($this);
        }

        return $this;
    }

    public function removeMember(Group $member): static
    {
        if ($this->members->removeElement($member)) {
            $member->removeMember($this);
        }

        return $this;
    }

   
}
