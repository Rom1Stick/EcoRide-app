<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\DataAccessException;
use App\DataAccess\NoSql\Model\DriverPreference;
use MongoDB\Collection;
use MongoDB\BSON\ObjectId;
use DateTime;

/**
 * Service pour gérer les préférences des conducteurs dans MongoDB
 */
class DriverPreferenceService extends AbstractMongoService
{
    /**
     * Nom de la collection MongoDB
     * 
     * @var string
     */
    private const COLLECTION_NAME = 'driver_preferences';
    
    /**
     * Collection MongoDB
     *
     * @var Collection
     */
    protected Collection $collection;
    
    /**
     * Constructeur
     * 
     * @param MongoServiceInterface $mongoConnection Service de connexion
     */
    public function __construct(MongoServiceInterface $mongoConnection)
    {
        parent::__construct($mongoConnection);
    }
    
    /**
     * Initialiser le service
     * 
     * @return void
     */
    protected function initService(): void
    {
        $this->collection = $this->getCollection(self::COLLECTION_NAME);
    }
    
    /**
     * Sauvegarder les préférences d'un conducteur
     * 
     * @param DriverPreference $preference Préférences à sauvegarder
     * @return DriverPreference
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function save(DriverPreference $preference): DriverPreference
    {
        try {
            // Mettre à jour les dates
            if ($preference->getCreatedAt() === null) {
                $preference->setCreatedAt((new DateTime())->format('Y-m-d H:i:s'));
            }
            $preference->setUpdatedAt((new DateTime())->format('Y-m-d H:i:s'));
            
            $data = $preference->toArray();
            
            // Supprimer l'ID pour les nouvelles préférences
            if ($preference->getId() === null) {
                unset($data['_id']);
                $result = $this->collection->insertOne($data);
                
                if ($result->getInsertedCount() > 0) {
                    $preference->setId((string)$result->getInsertedId());
                } else {
                    throw new DataAccessException("Échec de l'insertion des préférences du conducteur");
                }
            } else {
                $id = new ObjectId($preference->getId());
                $result = $this->collection->updateOne(
                    ['_id' => $id],
                    ['$set' => $data]
                );
                
                if ($result->getModifiedCount() === 0 && $result->getMatchedCount() === 0) {
                    throw new DataAccessException("Préférences du conducteur non trouvées pour la mise à jour");
                }
            }
            
            return $preference;
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la sauvegarde des préférences du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver les préférences d'un conducteur par ID MongoDB
     * 
     * @param mixed $id ID MongoDB des préférences
     * @return mixed DriverPreference ou null
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function findById($id)
    {
        try {
            $result = $this->collection->findOne(['_id' => new ObjectId((string)$id)]);
            
            if ($result === null) {
                return null;
            }
            
            return DriverPreference::fromArray((array)$result);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des préférences du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver les préférences d'un conducteur par son ID
     * 
     * @param int $driverId ID du conducteur
     * @return DriverPreference|null
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function findByDriverId(int $driverId): ?DriverPreference
    {
        try {
            $result = $this->collection->findOne(['driver_id' => $driverId]);
            
            if ($result === null) {
                return null;
            }
            
            return DriverPreference::fromArray((array)$result);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des préférences du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver ou créer les préférences d'un conducteur
     * 
     * @param int $driverId ID du conducteur
     * @return DriverPreference
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function findOrCreateByDriverId(int $driverId): DriverPreference
    {
        try {
            $preference = $this->findByDriverId($driverId);
            
            if ($preference === null) {
                $preference = new DriverPreference();
                $preference->setDriverId($driverId);
                $this->save($preference);
            }
            
            return $preference;
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération ou création des préférences du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Mettre à jour une préférence spécifique d'un conducteur
     * 
     * @param int $driverId ID du conducteur
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return DriverPreference
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function updatePreference(int $driverId, string $key, $value): DriverPreference
    {
        try {
            $preference = $this->findOrCreateByDriverId($driverId);
            
            switch ($key) {
                case 'music_preferences':
                    $preference->setMusicPreferences((array)$value);
                    break;
                case 'conversation':
                    $preference->setConversation((string)$value);
                    break;
                case 'pets_allowed':
                    $preference->setPetsAllowed((bool)$value);
                    break;
                case 'smoking_allowed':
                    $preference->setSmokingAllowed((bool)$value);
                    break;
                case 'large_luggage_allowed':
                    $preference->setLargeLuggageAllowed((bool)$value);
                    break;
                case 'air_condition':
                    $preference->setAirCondition((string)$value);
                    break;
                case 'max_stops':
                    $preference->setMaxStops((int)$value);
                    break;
                case 'max_detour_distance':
                    $preference->setMaxDetourDistance((float)$value);
                    break;
                case 'max_pickup_radius':
                    $preference->setMaxPickupRadius((float)$value);
                    break;
                case 'max_trip_duration':
                    $preference->setMaxTripDuration((int)$value);
                    break;
                case 'preferred_passenger_types':
                    $preference->setPreferredPassengerTypes((array)$value);
                    break;
                case 'payment_preferences':
                    $preference->setPaymentPreferences((array)$value);
                    break;
                case 'trip_types':
                    $preference->setTripTypes((array)$value);
                    break;
                default:
                    // Préférence personnalisée
                    $preference->setCustomPreference($key, $value);
                    break;
            }
            
            return $this->save($preference);
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la mise à jour de la préférence du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Rechercher des conducteurs par préférences compatibles
     * 
     * @param array $criteria Critères de recherche
     * @param int $limit Limite de résultats
     * @param int $offset Décalage pour la pagination
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function findCompatibleDrivers(array $criteria, int $limit = 20, int $offset = 0): array
    {
        try {
            $query = [];
            
            // Construire la requête MongoDB en fonction des critères fournis
            if (isset($criteria['pets_needed']) && $criteria['pets_needed'] === true) {
                $query['pets_allowed'] = true;
            }
            
            if (isset($criteria['smoking_needed']) && $criteria['smoking_needed'] === true) {
                $query['smoking_allowed'] = true;
            }
            
            if (isset($criteria['large_luggage_needed']) && $criteria['large_luggage_needed'] === true) {
                $query['large_luggage_allowed'] = true;
            }
            
            if (isset($criteria['conversation_style'])) {
                $query['conversation'] = $criteria['conversation_style'];
            }
            
            if (isset($criteria['air_condition_needed'])) {
                $query['air_condition'] = ['$ne' => 'off'];
            }
            
            if (isset($criteria['min_pickup_radius'])) {
                $query['max_pickup_radius'] = ['$gte' => (float)$criteria['min_pickup_radius']];
            }
            
            if (isset($criteria['trip_type'])) {
                $query['trip_types'] = $criteria['trip_type'];
            }
            
            if (isset($criteria['passenger_type'])) {
                $query['preferred_passenger_types'] = $criteria['passenger_type'];
            }
            
            if (isset($criteria['payment_method'])) {
                $query['payment_preferences'] = $criteria['payment_method'];
            }
            
            $options = [
                'limit' => $limit,
                'skip' => $offset
            ];
            
            $cursor = $this->collection->find($query, $options);
            $preferences = [];
            
            foreach ($cursor as $document) {
                $preferences[] = DriverPreference::fromArray((array)$document);
            }
            
            return $preferences;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche de conducteurs compatibles : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer les préférences d'un conducteur
     * 
     * @param mixed $id ID MongoDB des préférences
     * @return bool
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function delete($id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId((string)$id)]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des préférences du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer les préférences d'un conducteur par son ID
     * 
     * @param int $driverId ID du conducteur
     * @return bool
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function deleteByDriverId(int $driverId): bool
    {
        try {
            $result = $this->collection->deleteOne(['driver_id' => $driverId]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression des préférences du conducteur : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Obtenir les préférences les plus populaires
     * 
     * @param int $limit Limite de résultats
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function getPopularPreferences(int $limit = 5): array
    {
        try {
            $results = [];
            
            // Musique populaire
            $pipeline = [
                ['$unwind' => '$music_preferences'],
                ['$group' => [
                    '_id' => '$music_preferences',
                    'count' => ['$sum' => 1]
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    '_id' => 0,
                    'genre' => '$_id',
                    'count' => 1
                ]]
            ];
            $results['music'] = $this->collection->aggregate($pipeline)->toArray();
            
            // Styles de conversation
            $pipeline = [
                ['$group' => [
                    '_id' => '$conversation',
                    'count' => ['$sum' => 1]
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    '_id' => 0,
                    'style' => '$_id',
                    'count' => 1
                ]]
            ];
            $results['conversation'] = $this->collection->aggregate($pipeline)->toArray();
            
            // Animaux autorisés
            $pipeline = [
                ['$group' => [
                    '_id' => '$pets_allowed',
                    'count' => ['$sum' => 1]
                ]],
                ['$project' => [
                    '_id' => 0,
                    'allowed' => '$_id',
                    'count' => 1
                ]]
            ];
            $results['pets'] = $this->collection->aggregate($pipeline)->toArray();
            
            // Fumeurs autorisés
            $pipeline = [
                ['$group' => [
                    '_id' => '$smoking_allowed',
                    'count' => ['$sum' => 1]
                ]],
                ['$project' => [
                    '_id' => 0,
                    'allowed' => '$_id',
                    'count' => 1
                ]]
            ];
            $results['smoking'] = $this->collection->aggregate($pipeline)->toArray();
            
            // Types de trajets
            $pipeline = [
                ['$unwind' => '$trip_types'],
                ['$group' => [
                    '_id' => '$trip_types',
                    'count' => ['$sum' => 1]
                ]],
                ['$sort' => ['count' => -1]],
                ['$limit' => $limit],
                ['$project' => [
                    '_id' => 0,
                    'type' => '$_id',
                    'count' => 1
                ]]
            ];
            $results['trip_types'] = $this->collection->aggregate($pipeline)->toArray();
            
            return $results;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la récupération des préférences populaires : " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Vérifier la compatibilité entre les préférences d'un conducteur et les critères d'un passager
     * 
     * @param int $driverId ID du conducteur
     * @param array $passengerCriteria Critères du passager
     * @return array
     * @throws DataAccessException En cas d'erreur d'accès aux données
     */
    public function checkCompatibility(int $driverId, array $passengerCriteria): array
    {
        try {
            $preference = $this->findByDriverId($driverId);
            
            if ($preference === null) {
                return [
                    'compatible' => true,
                    'issues' => []
                ];
            }
            
            $issues = [];
            
            // Vérifier les incompatibilités
            if (isset($passengerCriteria['pets_needed']) && 
                $passengerCriteria['pets_needed'] === true && 
                $preference->getPetsAllowed() === false) {
                $issues[] = 'pets_not_allowed';
            }
            
            if (isset($passengerCriteria['smoking_needed']) && 
                $passengerCriteria['smoking_needed'] === true && 
                $preference->getSmokingAllowed() === false) {
                $issues[] = 'smoking_not_allowed';
            }
            
            if (isset($passengerCriteria['large_luggage_needed']) && 
                $passengerCriteria['large_luggage_needed'] === true && 
                $preference->getLargeLuggageAllowed() === false) {
                $issues[] = 'large_luggage_not_allowed';
            }
            
            if (isset($passengerCriteria['air_condition_needed']) && 
                $passengerCriteria['air_condition_needed'] === true && 
                $preference->getAirCondition() === 'off') {
                $issues[] = 'no_air_condition';
            }
            
            if (isset($passengerCriteria['min_pickup_radius']) && 
                $preference->getMaxPickupRadius() !== null && 
                $preference->getMaxPickupRadius() < $passengerCriteria['min_pickup_radius']) {
                $issues[] = 'pickup_radius_too_small';
            }
            
            if (isset($passengerCriteria['payment_method']) && 
                !empty($preference->getPaymentPreferences()) && 
                !in_array($passengerCriteria['payment_method'], $preference->getPaymentPreferences())) {
                $issues[] = 'payment_method_not_accepted';
            }
            
            if (isset($passengerCriteria['passenger_type']) && 
                !empty($preference->getPreferredPassengerTypes()) && 
                !in_array($passengerCriteria['passenger_type'], $preference->getPreferredPassengerTypes())) {
                $issues[] = 'passenger_type_not_preferred';
            }
            
            return [
                'compatible' => empty($issues),
                'issues' => $issues
            ];
        } catch (DataAccessException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la vérification de la compatibilité : " . $e->getMessage(), 0, $e);
        }
    }
} 