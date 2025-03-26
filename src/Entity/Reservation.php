<?php
// src/Entity/Reservation.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'reservation')]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'idReservation', type: 'integer')]
    #[Groups(['reservation'])]
    private ?int $idReservation = null;

    #[ORM\Column(name: 'date', type: 'date')]
    #[Groups(['reservation'])]
    #[Assert\NotBlank(message: "Date cannot be blank")]
    #[Assert\Type("\DateTimeInterface", message: "Value must be a valid date")]
    #[Assert\GreaterThanOrEqual(
        "today",
        message: "Date must be today or in the future"
    )]
    private \DateTimeInterface $date;

    #[ORM\Column(name: 'duration', type: 'float')]
    #[Groups(['reservation'])]
    #[Assert\NotBlank(message: "Duration cannot be blank")]
    #[Assert\Positive(message: "Duration must be positive")]
    #[Assert\LessThanOrEqual(
        value: 24,
        message: "Duration cannot exceed 24 hours"
    )]
    private float $duration;

    #[ORM\Column(name: 'time', type: 'time')]
    #[Groups(['reservation'])]
    #[Assert\NotBlank(message: "Time cannot be blank")]
    private \DateTimeInterface $time;


    #[ORM\ManyToOne(targetEntity: SportSpace::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(name: 'idSportSpace', referencedColumnName: 'idSportSpace')]
    #[Groups(['reservation'])]
    #[Assert\NotBlank(message: "Sport space must be selected")]
    private ?SportSpace $sportSpace = null;

    public function getIdReservation(): ?int
    {
        return $this->idReservation;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;
        return $this;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function setTime(\DateTimeInterface $time): self
    {
        $this->time = $time;
        return $this;
    }

    public function getSportSpace(): ?SportSpace
    {
        return $this->sportSpace;
    }

    public function setSportSpace(?SportSpace $sportSpace): self
    {
        $this->sportSpace = $sportSpace;
        return $this;
    }
}