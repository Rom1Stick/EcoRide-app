<?php

namespace App\DataAccess\Sql\Entity;

/**
 * Entité représentant un véhicule dans le système EcoRide
 */
class Vehicle
{
    /**
     * Identifiant unique du véhicule
     *
     * @var int|null
     */
    private ?int $id;

    /**
     * Identifiant du propriétaire (utilisateur)
     *
     * @var int
     */
    private int $ownerId;

    /**
     * Marque du véhicule
     *
     * @var string
     */
    private string $brand;

    /**
     * Modèle du véhicule
     *
     * @var string
     */
    private string $model;

    /**
     * Année de fabrication
     *
     * @var int
     */
    private int $year;

    /**
     * Type de véhicule (citadine, SUV, berline, etc.)
     *
     * @var string
     */
    private string $type;

    /**
     * Immatriculation du véhicule
     *
     * @var string
     */
    private string $licensePlate;

    /**
     * Nombre de places disponibles
     *
     * @var int
     */
    private int $seats;

    /**
     * Type de carburant (essence, diesel, électrique, hybride)
     *
     * @var string
     */
    private string $fuelType;

    /**
     * Consommation moyenne en L/100km ou kWh/100km
     *
     * @var float
     */
    private float $consumption;

    /**
     * Émissions CO2 en g/km
     *
     * @var float|null
     */
    private ?float $co2Emission;

    /**
     * URL de la photo principale du véhicule
     *
     * @var string|null
     */
    private ?string $photoUrl;

    /**
     * Si le véhicule est actif ou non
     *
     * @var bool
     */
    private bool $isActive;

    /**
     * Date de création de l'entrée
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date de dernière mise à jour
     *
     * @var \DateTime
     */
    private \DateTime $updatedAt;

    /**
     * Constructeur
     */
    public function __construct()
    {
        $this->id = null;
        $this->co2Emission = null;
        $this->photoUrl = null;
        $this->isActive = true;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Obtient l'identifiant du véhicule
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant du véhicule
     *
     * @param int $id Identifiant
     * @return self
     */
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Obtient l'identifiant du propriétaire
     *
     * @return int
     */
    public function getOwnerId(): int
    {
        return $this->ownerId;
    }

    /**
     * Définit l'identifiant du propriétaire
     *
     * @param int $ownerId Identifiant du propriétaire
     * @return self
     */
    public function setOwnerId(int $ownerId): self
    {
        $this->ownerId = $ownerId;
        return $this;
    }

    /**
     * Obtient la marque du véhicule
     *
     * @return string
     */
    public function getBrand(): string
    {
        return $this->brand;
    }

    /**
     * Définit la marque du véhicule
     *
     * @param string $brand Marque
     * @return self
     */
    public function setBrand(string $brand): self
    {
        $this->brand = $brand;
        return $this;
    }

    /**
     * Obtient le modèle du véhicule
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Définit le modèle du véhicule
     *
     * @param string $model Modèle
     * @return self
     */
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Obtient l'année de fabrication
     *
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    /**
     * Définit l'année de fabrication
     *
     * @param int $year Année
     * @return self
     */
    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }

    /**
     * Obtient le type de véhicule
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Définit le type de véhicule
     *
     * @param string $type Type de véhicule
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Obtient l'immatriculation
     *
     * @return string
     */
    public function getLicensePlate(): string
    {
        return $this->licensePlate;
    }

    /**
     * Définit l'immatriculation
     *
     * @param string $licensePlate Immatriculation
     * @return self
     */
    public function setLicensePlate(string $licensePlate): self
    {
        $this->licensePlate = $licensePlate;
        return $this;
    }

    /**
     * Obtient le nombre de places
     *
     * @return int
     */
    public function getSeats(): int
    {
        return $this->seats;
    }

    /**
     * Définit le nombre de places
     *
     * @param int $seats Nombre de places
     * @return self
     */
    public function setSeats(int $seats): self
    {
        $this->seats = $seats;
        return $this;
    }

    /**
     * Obtient le type de carburant
     *
     * @return string
     */
    public function getFuelType(): string
    {
        return $this->fuelType;
    }

    /**
     * Définit le type de carburant
     *
     * @param string $fuelType Type de carburant
     * @return self
     */
    public function setFuelType(string $fuelType): self
    {
        $this->fuelType = $fuelType;
        return $this;
    }

    /**
     * Obtient la consommation moyenne
     *
     * @return float
     */
    public function getConsumption(): float
    {
        return $this->consumption;
    }

    /**
     * Définit la consommation moyenne
     *
     * @param float $consumption Consommation
     * @return self
     */
    public function setConsumption(float $consumption): self
    {
        $this->consumption = $consumption;
        return $this;
    }

    /**
     * Obtient les émissions CO2
     *
     * @return float|null
     */
    public function getCo2Emission(): ?float
    {
        return $this->co2Emission;
    }

    /**
     * Définit les émissions CO2
     *
     * @param float|null $co2Emission Émissions CO2
     * @return self
     */
    public function setCo2Emission(?float $co2Emission): self
    {
        $this->co2Emission = $co2Emission;
        return $this;
    }

    /**
     * Obtient l'URL de la photo
     *
     * @return string|null
     */
    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    /**
     * Définit l'URL de la photo
     *
     * @param string|null $photoUrl URL de la photo
     * @return self
     */
    public function setPhotoUrl(?string $photoUrl): self
    {
        $this->photoUrl = $photoUrl;
        return $this;
    }

    /**
     * Vérifie si le véhicule est actif
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Définit si le véhicule est actif
     *
     * @param bool $isActive Statut d'activité
     * @return self
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    /**
     * Obtient la date de création
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * Définit la date de création
     *
     * @param \DateTime $createdAt Date de création
     * @return self
     */
    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Obtient la date de mise à jour
     *
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Définit la date de mise à jour
     *
     * @param \DateTime $updatedAt Date de mise à jour
     * @return self
     */
    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Met à jour la date de modification
     *
     * @return void
     */
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * Convertit l'objet en tableau
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'ownerId' => $this->ownerId,
            'brand' => $this->brand,
            'model' => $this->model,
            'year' => $this->year,
            'type' => $this->type,
            'licensePlate' => $this->licensePlate,
            'seats' => $this->seats,
            'fuelType' => $this->fuelType,
            'consumption' => $this->consumption,
            'co2Emission' => $this->co2Emission,
            'photoUrl' => $this->photoUrl,
            'isActive' => $this->isActive,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Crée un objet à partir d'un tableau
     *
     * @param array $data Données
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $vehicle = new self();

        if (isset($data['id'])) {
            $vehicle->setId($data['id']);
        }

        if (isset($data['ownerId'])) {
            $vehicle->setOwnerId($data['ownerId']);
        }

        if (isset($data['brand'])) {
            $vehicle->setBrand($data['brand']);
        }

        if (isset($data['model'])) {
            $vehicle->setModel($data['model']);
        }

        if (isset($data['year'])) {
            $vehicle->setYear((int)$data['year']);
        }

        if (isset($data['type'])) {
            $vehicle->setType($data['type']);
        }

        if (isset($data['licensePlate'])) {
            $vehicle->setLicensePlate($data['licensePlate']);
        }

        if (isset($data['seats'])) {
            $vehicle->setSeats((int)$data['seats']);
        }

        if (isset($data['fuelType'])) {
            $vehicle->setFuelType($data['fuelType']);
        }

        if (isset($data['consumption'])) {
            $vehicle->setConsumption((float)$data['consumption']);
        }

        if (isset($data['co2Emission'])) {
            $vehicle->setCo2Emission((float)$data['co2Emission']);
        }

        if (isset($data['photoUrl'])) {
            $vehicle->setPhotoUrl($data['photoUrl']);
        }

        if (isset($data['isActive'])) {
            $vehicle->setIsActive((bool)$data['isActive']);
        }

        if (isset($data['createdAt'])) {
            $vehicle->setCreatedAt(new \DateTime($data['createdAt']));
        }

        if (isset($data['updatedAt'])) {
            $vehicle->setUpdatedAt(new \DateTime($data['updatedAt']));
        }

        return $vehicle;
    }
} 