<?php

namespace App\Entity;

use App\Repository\VehiculeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VehiculeRepository::class)]
class Vehicule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $brand = null;

    #[ORM\Column(length: 255)]
    private ?string $model = null;

    #[ORM\Column(length: 255)]
    private ?string $registration = null;

    #[ORM\Column(length: 255)]
    private ?string $vin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $circulation_date = null;

    #[ORM\Column]
    private ?int $mileage = null;

    #[ORM\Column]
    private ?bool $driver = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $driver_last_name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $driver_first_name = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $driver_phone = null;

    #[ORM\ManyToOne(inversedBy: 'vehicules')]
    private ?Client $client = null;

    /**
     * @var Collection<int, Appointement>
     */
    #[ORM\OneToMany(targetEntity: Appointement::class, mappedBy: 'vehicule')]
    private Collection $appointements;

    public function __construct()
    {
        $this->appointements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getRegistration(): ?string
    {
        return $this->registration;
    }

    public function setRegistration(string $registration): static
    {
        $this->registration = $registration;

        return $this;
    }

    public function getVin(): ?string
    {
        return $this->vin;
    }

    public function setVin(string $vin): static
    {
        $this->vin = $vin;

        return $this;
    }

    public function getCirculationDate(): ?\DateTime
    {
        return $this->circulation_date;
    }

    public function setCirculationDate(\DateTime $circulation_date): static
    {
        $this->circulation_date = $circulation_date;

        return $this;
    }

    public function getMileage(): ?int
    {
        return $this->mileage;
    }

    public function setMileage(int $mileage): static
    {
        $this->mileage = $mileage;

        return $this;
    }

    public function isDriver(): ?bool
    {
        return $this->driver;
    }

    public function setDriver(bool $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getDriverLastName(): ?string
    {
        return $this->driver_last_name;
    }

    public function setDriverLastName(string $driver_last_name): static
    {
        $this->driver_last_name = $driver_last_name;

        return $this;
    }

    public function getDriverFirstName(): ?string
    {
        return $this->driver_first_name;
    }

    public function setDriverFirstName(?string $driver_first_name): static
    {
        $this->driver_first_name = $driver_first_name;

        return $this;
    }

    public function getDriverPhone(): ?string
    {
        return $this->driver_phone;
    }

    public function setDriverPhone(?string $driver_phone): static
    {
        $this->driver_phone = $driver_phone;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, Appointement>
     */
    public function getAppointements(): Collection
    {
        return $this->appointements;
    }

    public function addAppointement(Appointement $appointement): static
    {
        if (!$this->appointements->contains($appointement)) {
            $this->appointements->add($appointement);
            $appointement->setVehicule($this);
        }

        return $this;
    }

    public function removeAppointement(Appointement $appointement): static
    {
        if ($this->appointements->removeElement($appointement)) {
            // set the owning side to null (unless already changed)
            if ($appointement->getVehicule() === $this) {
                $appointement->setVehicule(null);
            }
        }

        return $this;
    }
}
