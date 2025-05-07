<?php

namespace App\DataAccess\NoSql\Model;

use DateTime;
use JsonSerializable;

/**
 * Modèle pour les données géospatiales dans MongoDB
 */
class GeoData implements JsonSerializable
{
    /**
     * Identifiant MongoDB
     *
     * @var string|null
     */
    private ?string $id = null;
    
    /**
     * Type de données géo (itineraire, point_interet, zone)
     *
     * @var string
     */
    private string $type = 'itineraire';
    
    /**
     * ID du covoiturage associé
     *
     * @var int|null
     */
    private ?int $covoiturageId = null;
    
    /**
     * Géométrie GeoJSON
     *
     * @var array
     */
    private array $geometry = [];
    
    /**
     * Métadonnées associées
     *
     * @var array
     */
    private array $metadata = [];
    
    /**
     * Points d'intérêt associés
     *
     * @var array
     */
    private array $pointsInteret = [];
    
    /**
     * Date de création
     *
     * @var string
     */
    private string $createdAt;
    
    /**
     * Date de mise à jour
     *
     * @var string
     */
    private string $updatedAt;
    
    /**
     * Constructeur
     *
     * @param string $type Type de données géo
     * @param array $geometry Géométrie GeoJSON
     * @param int|null $covoiturageId ID du covoiturage associé
     */
    public function __construct(string $type = 'itineraire', array $geometry = [], ?int $covoiturageId = null)
    {
        $this->type = $type;
        $this->geometry = $geometry;
        $this->covoiturageId = $covoiturageId;
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }
    
    /**
     * Obtenir l'ID
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }
    
    /**
     * Définir l'ID
     *
     * @param string $id
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Obtenir le type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * Définir le type
     *
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Obtenir l'ID du covoiturage
     *
     * @return int|null
     */
    public function getCovoiturageId(): ?int
    {
        return $this->covoiturageId;
    }
    
    /**
     * Définir l'ID du covoiturage
     *
     * @param int|null $covoiturageId
     * @return self
     */
    public function setCovoiturageId(?int $covoiturageId): self
    {
        $this->covoiturageId = $covoiturageId;
        return $this;
    }
    
    /**
     * Obtenir la géométrie
     *
     * @return array
     */
    public function getGeometry(): array
    {
        return $this->geometry;
    }
    
    /**
     * Définir la géométrie
     *
     * @param array $geometry
     * @return self
     */
    public function setGeometry(array $geometry): self
    {
        $this->geometry = $geometry;
        return $this;
    }
    
    /**
     * Obtenir les métadonnées
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    /**
     * Définir les métadonnées
     *
     * @param array $metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }
    
    /**
     * Ajouter une métadonnée
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function addMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }
    
    /**
     * Obtenir les points d'intérêt
     *
     * @return array
     */
    public function getPointsInteret(): array
    {
        return $this->pointsInteret;
    }
    
    /**
     * Définir les points d'intérêt
     *
     * @param array $pointsInteret
     * @return self
     */
    public function setPointsInteret(array $pointsInteret): self
    {
        $this->pointsInteret = $pointsInteret;
        return $this;
    }
    
    /**
     * Ajouter un point d'intérêt
     *
     * @param array $point
     * @return self
     */
    public function addPointInteret(array $point): self
    {
        $this->pointsInteret[] = $point;
        return $this;
    }
    
    /**
     * Obtenir la date de création
     *
     * @return string
     */
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    
    /**
     * Définir la date de création
     *
     * @param string $createdAt
     * @return self
     */
    public function setCreatedAt(string $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    /**
     * Obtenir la date de mise à jour
     *
     * @return string
     */
    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
    
    /**
     * Définir la date de mise à jour
     *
     * @param string $updatedAt
     * @return self
     */
    public function setUpdatedAt(string $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    /**
     * Toucher l'objet (mettre à jour la date de mise à jour)
     *
     * @return self
     */
    public function touch(): self
    {
        $this->updatedAt = (new DateTime())->format('Y-m-d H:i:s');
        return $this;
    }
    
    /**
     * Sérialiser l'objet pour JSON
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            '_id' => $this->id,
            'type' => $this->type,
            'covoiturageId' => $this->covoiturageId,
            'geometry' => $this->geometry,
            'metadata' => $this->metadata,
            'points_interet' => $this->pointsInteret,
            'created' => $this->createdAt,
            'updated' => $this->updatedAt
        ];
    }
    
    /**
     * Créer une instance à partir d'un tableau
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $geoData = new self();
        
        if (isset($data['_id'])) {
            $geoData->setId((string)$data['_id']);
        }
        
        if (isset($data['type'])) {
            $geoData->setType($data['type']);
        }
        
        if (isset($data['covoiturageId'])) {
            $geoData->setCovoiturageId((int)$data['covoiturageId']);
        }
        
        if (isset($data['geometry'])) {
            $geoData->setGeometry((array)$data['geometry']);
        }
        
        if (isset($data['metadata'])) {
            $geoData->setMetadata((array)$data['metadata']);
        }
        
        if (isset($data['points_interet'])) {
            $geoData->setPointsInteret((array)$data['points_interet']);
        }
        
        if (isset($data['created'])) {
            $geoData->setCreatedAt($data['created']);
        }
        
        if (isset($data['updated'])) {
            $geoData->setUpdatedAt($data['updated']);
        }
        
        return $geoData;
    }
    
    /**
     * Créer une géométrie de type Point
     *
     * @param float $longitude
     * @param float $latitude
     * @return array
     */
    public static function createPoint(float $longitude, float $latitude): array
    {
        return [
            'type' => 'Point',
            'coordinates' => [$longitude, $latitude]
        ];
    }
    
    /**
     * Créer une géométrie de type LineString
     *
     * @param array $points Tableau de points [longitude, latitude]
     * @return array
     */
    public static function createLineString(array $points): array
    {
        return [
            'type' => 'LineString',
            'coordinates' => $points
        ];
    }
    
    /**
     * Créer une géométrie de type Polygon
     *
     * @param array $points Tableau de points [longitude, latitude] formant un polygone fermé
     * @return array
     */
    public static function createPolygon(array $points): array
    {
        // S'assurer que le polygone est fermé (premier point = dernier point)
        if (count($points) > 0 && $points[0] !== end($points)) {
            $points[] = $points[0];
        }
        
        return [
            'type' => 'Polygon',
            'coordinates' => [$points] // Un polygone peut avoir des trous, d'où le tableau imbriqué
        ];
    }
} 