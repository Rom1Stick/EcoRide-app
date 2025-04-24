<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\GeoData;
use App\DataAccess\NoSql\MongoServiceInterface;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

/**
 * Service pour gérer les données géospatiales dans MongoDB
 */
class GeoDataService extends AbstractMongoService
{
    /**
     * Nom de la collection
     */
    private const COLLECTION_NAME = 'geo_data';
    
    /**
     * Types de données géo
     */
    public const TYPE_ITINERAIRE = 'itineraire';
    public const TYPE_POINT_INTERET = 'point_interet';
    public const TYPE_ZONE = 'zone';
    
    /**
     * Collection MongoDB
     *
     * @var Collection
     */
    protected Collection $collection;
    
    /**
     * Constructeur
     *
     * @param MongoServiceInterface $mongoConnection
     */
    public function __construct(MongoServiceInterface $mongoConnection)
    {
        parent::__construct($mongoConnection);
    }
    
    /**
     * Initialiser le service
     */
    protected function initService(): void
    {
        $this->collection = $this->getCollection(self::COLLECTION_NAME);
        $this->ensureIndexes();
    }
    
    /**
     * Assurer la création des index nécessaires
     */
    private function ensureIndexes(): void
    {
        // Index 2dsphere pour les requêtes géospatiales
        $this->collection->createIndex(['geometry' => '2dsphere']);
        
        // Index sur le type
        $this->collection->createIndex(['type' => 1]);
        
        // Index sur l'ID de covoiturage
        $this->collection->createIndex(['covoiturageId' => 1]);
    }
    
    /**
     * Sauvegarder des données géospatiales
     *
     * @param GeoData $geoData
     * @return GeoData
     * @throws DataAccessException
     */
    public function save(GeoData $geoData): GeoData
    {
        try {
            if ($geoData->getId() === null) {
                // Nouvelle entrée
                $result = $this->collection->insertOne($geoData->jsonSerialize());
                
                if ($result->getInsertedCount() === 0) {
                    throw new DataAccessException("Échec de l'insertion des données géospatiales");
                }
                
                $geoData->setId((string)$result->getInsertedId());
            } else {
                // Mise à jour
                $data = $geoData->jsonSerialize();
                unset($data['_id']); // Ne pas mettre à jour l'ID
                
                $result = $this->collection->updateOne(
                    ['_id' => new ObjectId($geoData->getId())],
                    ['$set' => $data]
                );
                
                if ($result->getModifiedCount() === 0 && $result->getMatchedCount() === 0) {
                    throw new DataAccessException("Données géospatiales non trouvées ou non modifiées");
                }
            }
            
            return $geoData;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la sauvegarde des données géospatiales: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver des données géospatiales par ID
     *
     * @param mixed $id
     * @return GeoData|null
     * @throws DataAccessException
     */
    public function findById($id)
    {
        try {
            $result = $this->collection->findOne(['_id' => new ObjectId($id)]);
            
            if ($result === null) {
                return null;
            }
            
            return GeoData::fromArray((array)$result);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des données géospatiales: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver des données géospatiales par ID de covoiturage
     *
     * @param int $covoiturageId
     * @return array
     * @throws DataAccessException
     */
    public function findByCovoiturageId(int $covoiturageId): array
    {
        try {
            $cursor = $this->collection->find(['covoiturageId' => $covoiturageId]);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = GeoData::fromArray((array)$document);
            }
            
            return $results;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des données géospatiales par ID de covoiturage: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver un itinéraire par ID de covoiturage
     *
     * @param int $covoiturageId
     * @return GeoData|null
     * @throws DataAccessException
     */
    public function findItineraireByCovoiturageId(int $covoiturageId): ?GeoData
    {
        try {
            $result = $this->collection->findOne([
                'covoiturageId' => $covoiturageId,
                'type' => self::TYPE_ITINERAIRE
            ]);
            
            if ($result === null) {
                return null;
            }
            
            return GeoData::fromArray((array)$result);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche de l'itinéraire: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher les points d'intérêt à proximité d'un point
     *
     * @param float $longitude
     * @param float $latitude
     * @param float $distanceKm Distance en kilomètres
     * @param string $type Type de point d'intérêt (optionnel)
     * @return array
     * @throws DataAccessException
     */
    public function findPointsInteretNear(float $longitude, float $latitude, float $distanceKm, ?string $type = null): array
    {
        try {
            $query = [
                'type' => self::TYPE_POINT_INTERET,
                'geometry' => [
                    '$near' => [
                        '$geometry' => [
                            'type' => 'Point',
                            'coordinates' => [$longitude, $latitude]
                        ],
                        '$maxDistance' => $distanceKm * 1000 // Conversion en mètres
                    ]
                ]
            ];
            
            if ($type !== null) {
                $query['metadata.type'] = $type;
            }
            
            $cursor = $this->collection->find($query);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = GeoData::fromArray((array)$document);
            }
            
            return $results;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des points d'intérêt à proximité: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher les trajets qui passent à proximité d'un point
     *
     * @param float $longitude
     * @param float $latitude
     * @param float $distanceKm Distance en kilomètres
     * @return array
     * @throws DataAccessException
     */
    public function findItinerairesNear(float $longitude, float $latitude, float $distanceKm): array
    {
        try {
            $query = [
                'type' => self::TYPE_ITINERAIRE,
                'geometry' => [
                    '$geoIntersects' => [
                        '$geometry' => [
                            'type' => 'Point',
                            'coordinates' => [$longitude, $latitude]
                        ]
                    ]
                ]
            ];
            
            $cursor = $this->collection->find($query);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = GeoData::fromArray((array)$document);
            }
            
            return $results;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des itinéraires à proximité: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher les trajets à l'intérieur d'une zone
     *
     * @param array $polygonCoordinates Tableau de coordonnées formant un polygone
     * @return array
     * @throws DataAccessException
     */
    public function findItinerairesInZone(array $polygonCoordinates): array
    {
        try {
            $query = [
                'type' => self::TYPE_ITINERAIRE,
                'geometry' => [
                    '$geoIntersects' => [
                        '$geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [$polygonCoordinates]
                        ]
                    ]
                ]
            ];
            
            $cursor = $this->collection->find($query);
            
            $results = [];
            foreach ($cursor as $document) {
                $results[] = GeoData::fromArray((array)$document);
            }
            
            return $results;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des itinéraires dans la zone: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Créer ou mettre à jour un itinéraire pour un covoiturage
     *
     * @param int $covoiturageId
     * @param array $coordinates Coordonnées de l'itinéraire
     * @param array $metadata Métadonnées (distance, durée, etc.)
     * @return GeoData
     * @throws DataAccessException
     */
    public function saveItineraire(int $covoiturageId, array $coordinates, array $metadata = []): GeoData
    {
        // Rechercher d'abord un itinéraire existant
        $itineraire = $this->findItineraireByCovoiturageId($covoiturageId);
        
        if ($itineraire === null) {
            // Créer un nouvel itinéraire
            $geometry = GeoData::createLineString($coordinates);
            $itineraire = new GeoData(self::TYPE_ITINERAIRE, $geometry, $covoiturageId);
        } else {
            // Mettre à jour l'itinéraire existant
            $itineraire->setGeometry(GeoData::createLineString($coordinates));
            $itineraire->touch();
        }
        
        // Ajouter les métadonnées
        $itineraire->setMetadata($metadata);
        
        // Sauvegarder
        return $this->save($itineraire);
    }
    
    /**
     * Ajouter un point d'intérêt
     *
     * @param float $longitude
     * @param float $latitude
     * @param string $type Type de point (arrêt, point de rencontre, etc.)
     * @param string $nom Nom du point
     * @param array $metadata Métadonnées supplémentaires
     * @return GeoData
     * @throws DataAccessException
     */
    public function addPointInteret(float $longitude, float $latitude, string $type, string $nom, array $metadata = []): GeoData
    {
        $geometry = GeoData::createPoint($longitude, $latitude);
        $pointInteret = new GeoData(self::TYPE_POINT_INTERET, $geometry);
        
        $metadata = array_merge($metadata, [
            'type' => $type,
            'nom' => $nom
        ]);
        
        $pointInteret->setMetadata($metadata);
        
        return $this->save($pointInteret);
    }
    
    /**
     * Créer une zone géographique
     *
     * @param array $coordinates Coordonnées formant un polygone
     * @param string $nom Nom de la zone
     * @param array $metadata Métadonnées supplémentaires
     * @return GeoData
     * @throws DataAccessException
     */
    public function createZone(array $coordinates, string $nom, array $metadata = []): GeoData
    {
        $geometry = GeoData::createPolygon($coordinates);
        $zone = new GeoData(self::TYPE_ZONE, $geometry);
        
        $metadata = array_merge($metadata, [
            'nom' => $nom
        ]);
        
        $zone->setMetadata($metadata);
        
        return $this->save($zone);
    }
    
    /**
     * Supprimer des données géospatiales par ID
     *
     * @param mixed $id
     * @return bool
     * @throws DataAccessException
     */
    public function delete($id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des données géospatiales: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer toutes les données géospatiales associées à un covoiturage
     *
     * @param int $covoiturageId
     * @return int Nombre d'éléments supprimés
     * @throws DataAccessException
     */
    public function deleteByCovoiturageId(int $covoiturageId): int
    {
        try {
            $result = $this->collection->deleteMany(['covoiturageId' => $covoiturageId]);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des données géospatiales par ID de covoiturage: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Calculer la distance entre deux points (formule de Haversine)
     *
     * @param float $lon1 Longitude du point 1
     * @param float $lat1 Latitude du point 1
     * @param float $lon2 Longitude du point 2
     * @param float $lat2 Latitude du point 2
     * @return float Distance en kilomètres
     */
    public function calculateDistance(float $lon1, float $lat1, float $lon2, float $lat2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
} 