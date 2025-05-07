<?php

namespace App\DataAccess\Sql\Entity;

/**
 * Entité représentant une réservation de trajet dans le système EcoRide
 */
class Booking
{
    /**
     * Identifiant unique de la réservation
     *
     * @var int|null
     */
    private ?int $id;

    /**
     * Identifiant de l'utilisateur qui réserve
     *
     * @var int
     */
    private int $passengerId;

    /**
     * Identifiant du trajet réservé
     *
     * @var int
     */
    private int $tripId;

    /**
     * Nombre de places réservées
     *
     * @var int
     */
    private int $seatCount;

    /**
     * Prix total de la réservation
     *
     * @var float
     */
    private float $totalPrice;

    /**
     * Statut de la réservation
     * (pending, confirmed, cancelled, completed, no_show)
     *
     * @var string
     */
    private string $status;

    /**
     * Méthode de paiement
     * (credit, balance, pending)
     *
     * @var string
     */
    private string $paymentMethod;

    /**
     * Identifiant de la transaction de paiement
     *
     * @var string|null
     */
    private ?string $paymentTransactionId;

    /**
     * Date de confirmation du paiement
     *
     * @var \DateTime|null
     */
    private ?\DateTime $paymentConfirmedAt;

    /**
     * Bagages supplémentaires
     *
     * @var bool
     */
    private bool $hasLuggage;

    /**
     * Commentaires du passager
     *
     * @var string|null
     */
    private ?string $passengerNotes;

    /**
     * Point de prise en charge personnalisé
     *
     * @var string|null
     */
    private ?string $pickupLocation;

    /**
     * Point de dépose personnalisé
     *
     * @var string|null
     */
    private ?string $dropoffLocation;

    /**
     * Date et heure de prise en charge personnalisée
     *
     * @var \DateTime|null
     */
    private ?\DateTime $customPickupTime;

    /**
     * CO2 économisé en kg
     *
     * @var float|null
     */
    private ?float $co2Saved;

    /**
     * Date de création
     *
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * Date de mise à jour
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
        $this->seatCount = 1;
        $this->status = 'pending';
        $this->paymentMethod = 'pending';
        $this->paymentTransactionId = null;
        $this->paymentConfirmedAt = null;
        $this->hasLuggage = false;
        $this->passengerNotes = null;
        $this->pickupLocation = null;
        $this->dropoffLocation = null;
        $this->customPickupTime = null;
        $this->co2Saved = null;
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Obtient l'identifiant de la réservation
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Définit l'identifiant de la réservation
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
     * Obtient l'identifiant du passager
     *
     * @return int
     */
    public function getPassengerId(): int
    {
        return $this->passengerId;
    }

    /**
     * Définit l'identifiant du passager
     *
     * @param int $passengerId Identifiant du passager
     * @return self
     */
    public function setPassengerId(int $passengerId): self
    {
        $this->passengerId = $passengerId;
        return $this;
    }

    /**
     * Obtient l'identifiant du trajet
     *
     * @return int
     */
    public function getTripId(): int
    {
        return $this->tripId;
    }

    /**
     * Définit l'identifiant du trajet
     *
     * @param int $tripId Identifiant du trajet
     * @return self
     */
    public function setTripId(int $tripId): self
    {
        $this->tripId = $tripId;
        return $this;
    }

    /**
     * Obtient le nombre de places réservées
     *
     * @return int
     */
    public function getSeatCount(): int
    {
        return $this->seatCount;
    }

    /**
     * Définit le nombre de places réservées
     *
     * @param int $seatCount Nombre de places
     * @return self
     */
    public function setSeatCount(int $seatCount): self
    {
        $this->seatCount = $seatCount;
        return $this;
    }

    /**
     * Obtient le prix total
     *
     * @return float
     */
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    /**
     * Définit le prix total
     *
     * @param float $totalPrice Prix total en euros
     * @return self
     */
    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    /**
     * Obtient le statut de la réservation
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Définit le statut de la réservation
     *
     * @param string $status Statut
     * @return self
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Obtient la méthode de paiement
     *
     * @return string
     */
    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * Définit la méthode de paiement
     *
     * @param string $paymentMethod Méthode de paiement
     * @return self
     */
    public function setPaymentMethod(string $paymentMethod): self
    {
        $this->paymentMethod = $paymentMethod;
        return $this;
    }

    /**
     * Obtient l'identifiant de la transaction de paiement
     *
     * @return string|null
     */
    public function getPaymentTransactionId(): ?string
    {
        return $this->paymentTransactionId;
    }

    /**
     * Définit l'identifiant de la transaction de paiement
     *
     * @param string|null $paymentTransactionId Identifiant de transaction
     * @return self
     */
    public function setPaymentTransactionId(?string $paymentTransactionId): self
    {
        $this->paymentTransactionId = $paymentTransactionId;
        return $this;
    }

    /**
     * Obtient la date de confirmation du paiement
     *
     * @return \DateTime|null
     */
    public function getPaymentConfirmedAt(): ?\DateTime
    {
        return $this->paymentConfirmedAt;
    }

    /**
     * Définit la date de confirmation du paiement
     *
     * @param \DateTime|null $paymentConfirmedAt Date de confirmation
     * @return self
     */
    public function setPaymentConfirmedAt(?\DateTime $paymentConfirmedAt): self
    {
        $this->paymentConfirmedAt = $paymentConfirmedAt;
        return $this;
    }

    /**
     * Vérifie si le passager a des bagages supplémentaires
     *
     * @return bool
     */
    public function hasLuggage(): bool
    {
        return $this->hasLuggage;
    }

    /**
     * Définit si le passager a des bagages supplémentaires
     *
     * @param bool $hasLuggage Présence de bagages
     * @return self
     */
    public function setHasLuggage(bool $hasLuggage): self
    {
        $this->hasLuggage = $hasLuggage;
        return $this;
    }

    /**
     * Obtient les commentaires du passager
     *
     * @return string|null
     */
    public function getPassengerNotes(): ?string
    {
        return $this->passengerNotes;
    }

    /**
     * Définit les commentaires du passager
     *
     * @param string|null $passengerNotes Commentaires
     * @return self
     */
    public function setPassengerNotes(?string $passengerNotes): self
    {
        $this->passengerNotes = $passengerNotes;
        return $this;
    }

    /**
     * Obtient le point de prise en charge personnalisé
     *
     * @return string|null
     */
    public function getPickupLocation(): ?string
    {
        return $this->pickupLocation;
    }

    /**
     * Définit le point de prise en charge personnalisé
     *
     * @param string|null $pickupLocation Point de prise en charge
     * @return self
     */
    public function setPickupLocation(?string $pickupLocation): self
    {
        $this->pickupLocation = $pickupLocation;
        return $this;
    }

    /**
     * Obtient le point de dépose personnalisé
     *
     * @return string|null
     */
    public function getDropoffLocation(): ?string
    {
        return $this->dropoffLocation;
    }

    /**
     * Définit le point de dépose personnalisé
     *
     * @param string|null $dropoffLocation Point de dépose
     * @return self
     */
    public function setDropoffLocation(?string $dropoffLocation): self
    {
        $this->dropoffLocation = $dropoffLocation;
        return $this;
    }

    /**
     * Obtient l'heure de prise en charge personnalisée
     *
     * @return \DateTime|null
     */
    public function getCustomPickupTime(): ?\DateTime
    {
        return $this->customPickupTime;
    }

    /**
     * Définit l'heure de prise en charge personnalisée
     *
     * @param \DateTime|null $customPickupTime Heure de prise en charge
     * @return self
     */
    public function setCustomPickupTime(?\DateTime $customPickupTime): self
    {
        $this->customPickupTime = $customPickupTime;
        return $this;
    }

    /**
     * Obtient la quantité de CO2 économisée
     *
     * @return float|null
     */
    public function getCo2Saved(): ?float
    {
        return $this->co2Saved;
    }

    /**
     * Définit la quantité de CO2 économisée
     *
     * @param float|null $co2Saved CO2 économisé en kg
     * @return self
     */
    public function setCo2Saved(?float $co2Saved): self
    {
        $this->co2Saved = $co2Saved;
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
     * Calcule la quantité de CO2 économisée pour cette réservation
     * basé sur la distance du trajet et le nombre de sièges réservés
     *
     * @param Trip $trip Le trajet associé
     * @return void
     */
    public function calculateCO2Savings(Trip $trip): void
    {
        // Moyenne d'émission d'une voiture standard: 120g CO2/km
        $averageEmission = 0.12; // en kg/km
        
        // CO2 économisé = distance * émission moyenne * nombre de places
        $this->co2Saved = $trip->getDistance() * $averageEmission * $this->seatCount;
    }

    /**
     * Confirme le paiement de la réservation
     *
     * @param string $transactionId Identifiant de la transaction
     * @param string $method Méthode de paiement
     * @return self
     */
    public function confirmPayment(string $transactionId, string $method = 'credit'): self
    {
        $this->paymentTransactionId = $transactionId;
        $this->paymentMethod = $method;
        $this->paymentConfirmedAt = new \DateTime();
        $this->status = 'confirmed';
        $this->updateTimestamp();
        
        return $this;
    }

    /**
     * Vérifie si la réservation est annulable
     * Une réservation est annulable si elle n'est pas déjà annulée, terminée, 
     * ou marquée comme non présentée
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        $nonCancellableStatuses = ['cancelled', 'completed', 'no_show'];
        return !in_array($this->status, $nonCancellableStatuses);
    }

    /**
     * Calcule le montant à rembourser en cas d'annulation
     * Basé sur la politique d'annulation d'EcoRide
     *
     * @param Trip $trip Le trajet associé
     * @return float Montant à rembourser
     */
    public function calculateRefundAmount(Trip $trip): float
    {
        if (!$this->isCancellable()) {
            return 0.0;
        }

        $now = new \DateTime();
        $departureTime = $trip->getDepartureTime();
        $interval = $now->diff($departureTime);
        
        // Convertir en heures
        $hoursBeforeDeparture = $interval->h + ($interval->days * 24);
        
        // Politique de remboursement:
        // - Plus de 24h avant le départ: 100%
        // - Entre 12h et 24h avant le départ: 50%
        // - Moins de 12h avant le départ: 0%
        if ($hoursBeforeDeparture >= 24) {
            return $this->totalPrice;
        } elseif ($hoursBeforeDeparture >= 12) {
            return $this->totalPrice * 0.5;
        } else {
            return 0.0;
        }
    }

    /**
     * Vérifie si la réservation est modifiable
     * Une réservation est modifiable si elle n'est pas déjà complétée, 
     * annulée ou marquée comme non présentée
     *
     * @return bool
     */
    public function isModifiable(): bool
    {
        $nonModifiableStatuses = ['cancelled', 'completed', 'no_show'];
        return !in_array($this->status, $nonModifiableStatuses);
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
            'passengerId' => $this->passengerId,
            'tripId' => $this->tripId,
            'seatCount' => $this->seatCount,
            'totalPrice' => $this->totalPrice,
            'status' => $this->status,
            'paymentMethod' => $this->paymentMethod,
            'paymentTransactionId' => $this->paymentTransactionId,
            'paymentConfirmedAt' => $this->paymentConfirmedAt ? 
                $this->paymentConfirmedAt->format('Y-m-d H:i:s') : null,
            'hasLuggage' => $this->hasLuggage,
            'passengerNotes' => $this->passengerNotes,
            'pickupLocation' => $this->pickupLocation,
            'dropoffLocation' => $this->dropoffLocation,
            'customPickupTime' => $this->customPickupTime ?
                $this->customPickupTime->format('Y-m-d H:i:s') : null,
            'co2Saved' => $this->co2Saved,
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
        $booking = new self();

        if (isset($data['id'])) {
            $booking->setId($data['id']);
        }

        if (isset($data['passengerId'])) {
            $booking->setPassengerId($data['passengerId']);
        }

        if (isset($data['tripId'])) {
            $booking->setTripId($data['tripId']);
        }

        if (isset($data['seatCount'])) {
            $booking->setSeatCount($data['seatCount']);
        }

        if (isset($data['totalPrice'])) {
            $booking->setTotalPrice($data['totalPrice']);
        }

        if (isset($data['status'])) {
            $booking->setStatus($data['status']);
        }

        if (isset($data['paymentMethod'])) {
            $booking->setPaymentMethod($data['paymentMethod']);
        }

        if (isset($data['paymentTransactionId'])) {
            $booking->setPaymentTransactionId($data['paymentTransactionId']);
        }

        if (isset($data['paymentConfirmedAt']) && $data['paymentConfirmedAt']) {
            $booking->setPaymentConfirmedAt(new \DateTime($data['paymentConfirmedAt']));
        }

        if (isset($data['hasLuggage'])) {
            $booking->setHasLuggage((bool)$data['hasLuggage']);
        }

        if (isset($data['passengerNotes'])) {
            $booking->setPassengerNotes($data['passengerNotes']);
        }

        if (isset($data['pickupLocation'])) {
            $booking->setPickupLocation($data['pickupLocation']);
        }

        if (isset($data['dropoffLocation'])) {
            $booking->setDropoffLocation($data['dropoffLocation']);
        }

        if (isset($data['customPickupTime']) && $data['customPickupTime']) {
            $booking->setCustomPickupTime(new \DateTime($data['customPickupTime']));
        }

        if (isset($data['co2Saved'])) {
            $booking->setCo2Saved((float)$data['co2Saved']);
        }

        if (isset($data['createdAt'])) {
            $booking->setCreatedAt(new \DateTime($data['createdAt']));
        }

        if (isset($data['updatedAt'])) {
            $booking->setUpdatedAt(new \DateTime($data['updatedAt']));
        }

        return $booking;
    }
} 