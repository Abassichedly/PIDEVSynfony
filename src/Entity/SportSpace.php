<?php
// src/Entity/SportSpace.php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'sportspace')]
class SportSpace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idSportSpace', type: 'integer')]
    #[Groups(['sportspace', 'reservation'])]
    private ?int $idSportSpace = null;

    #[ORM\Column(name: 'name', type: 'string', length: 30)]
    #[Groups(['sportspace'])]
    #[Assert\NotBlank(message: "Name cannot be blank")]
    #[Assert\Length(
        min: 2,
        max: 30,
        minMessage: "Name must be at least {{ limit }} characters long",
        maxMessage: "Name cannot be longer than {{ limit }} characters"
    )]
    private string $name;

    #[ORM\Column(name: 'location', type: 'string', length: 255, nullable: true)]
    #[Groups(['sportspace'])]
    #[Assert\Length(max: 255, maxMessage: "Location cannot be longer than {{ limit }} characters")]
    private ?string $location = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    #[Groups(['sportspace'])]
    #[Assert\NotBlank(message: "Email cannot be blank")]
    #[Assert\Email(message: "The email '{{ value }}' is not a valid email")]
    #[Assert\Length(max: 255, maxMessage: "Email cannot be longer than {{ limit }} characters")]
    private string $email;

    #[ORM\Column(name: 'phone', type: 'integer')]
    #[Groups(['sportspace'])]
    #[Assert\NotBlank(message: "Phone number cannot be blank")]
    #[Assert\Positive(message: "Phone number must be positive")]
    private int $phone;

    #[ORM\Column(name: 'availability', type: 'boolean')]
    #[Groups(['sportspace'])]
    private bool $availability = true;

    #[ORM\Column(name: 'type', type: 'string', columnDefinition: "ENUM('football','basketball','pilates','handball','swimming','yoga','tennis','box','hockey','gym')")]
    #[Groups(['sportspace'])]
    #[Assert\NotBlank(message: "Please select a sport type")]
    #[Assert\Choice([
        'football', 'basketball', 'pilates', 'handball', 
        'swimming', 'yoga', 'tennis', 'box', 'hockey', 'gym'
    ], message: "Select a valid sport type")]
    private string $type;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'sportSpace')]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getIdSportSpace(): ?int
    {
        return $this->idSportSpace;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function isAvailability(): bool
    {
        return $this->availability;
    }

    public function setAvailability(bool $availability): self
    {
        $this->availability = $availability;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setSportSpace($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getSportSpace() === $this) {
                $reservation->setSportSpace(null);
            }
        }
        return $this;
    }
}