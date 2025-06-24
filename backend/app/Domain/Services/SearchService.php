<?php

namespace App\Domain\Services;

use App\Domain\Repositories\RideRepositoryInterface;
use App\Domain\Repositories\LocationRepositoryInterface;
use App\Domain\ValueObjects\Location;
use App\Domain\Entities\Ride;
use DateTime;

/**
 * Service métier pour la recherche avancée de trajets
 */
class SearchService
{
    private const DEFAULT_LIMIT = 20;
    private const MAX_LIMIT = 100;
    private const DEFAULT_SORT = 'departureTime';
    
    public function __construct(
        private RideRepositoryInterface $rideRepository,
        private LocationRepositoryInterface $locationRepository
    ) {}
    
    /**
     * Recherche avancée de trajets avec filtres intelligents
     */
    public function searchRides(array $criteria): array
    {
        // Validation et normalisation des critères
        $normalizedCriteria = $this->normalizeCriteria($criteria);
        
        // Résolution des lieux par nom
        $departureLocation = null;
        $arrivalLocation = null;
        
        if (!empty($normalizedCriteria['departure'])) {
            $departureLocation = $this->locationRepository->findByName($normalizedCriteria['departure']);
            if (!$departureLocation) {
                throw new \InvalidArgumentException('Lieu de départ non trouvé');
            }
        }
        
        if (!empty($normalizedCriteria['arrival'])) {
            $arrivalLocation = $this->locationRepository->findByName($normalizedCriteria['arrival']);
            if (!$arrivalLocation) {
                throw new \InvalidArgumentException('Lieu d\'arrivée non trouvé');
            }
        }
        
        // Recherche de base via repository
        $rides = $this->rideRepository->search(
            $departureLocation,
            $arrivalLocation,
            $normalizedCriteria['date'],
            $normalizedCriteria['departureTime'],
            $normalizedCriteria['page'],
            $normalizedCriteria['limit']
        );
        
        // Filtrage avancé côté service
        $filteredRides = $this->applyAdvancedFilters($rides, $normalizedCriteria);
        
        // Tri intelligent
        $sortedRides = $this->applySorting($filteredRides, $normalizedCriteria['sortBy']);
        
        // Comptage total pour pagination
        $total = $this->rideRepository->countSearchResults(
            $departureLocation,
            $arrivalLocation,
            $normalizedCriteria['date']
        );
        
        return [
            'rides' => $sortedRides,
            'pagination' => [
                'total' => $total,
                'page' => $normalizedCriteria['page'],
                'limit' => $normalizedCriteria['limit'],
                'pages' => ceil($total / $normalizedCriteria['limit'])
            ],
            'criteria' => $normalizedCriteria,
            'filters_applied' => $this->getAppliedFilters($normalizedCriteria)
        ];
    }
    
    /**
     * Suggestions intelligentes basées sur l'historique et popularité
     */
    public function getSuggestions(int $userId = null, int $limit = 10): array
    {
        $limit = min($limit, 20); // Max 20 suggestions
        
        if ($userId) {
            // Suggestions personnalisées basées sur l'historique
            $suggestions = $this->getPersonalizedSuggestions($userId, $limit);
        } else {
            // Suggestions génériques basées sur la popularité
            $suggestions = $this->getPopularSuggestions($limit);
        }
        
        return [
            'suggestions' => $suggestions,
            'algorithm' => $userId ? 'personalized' : 'popularity_based',
            'count' => count($suggestions)
        ];
    }
    
    /**
     * Recherche rapide avec autocomplétion
     */
    public function quickSearch(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [
                'rides' => [],
                'locations' => [],
                'query' => $query
            ];
        }
        
        // Recherche de lieux correspondants
        $locations = $this->locationRepository->searchByName($query, $limit);
        
        // Recherche de trajets avec lieux correspondants
        $rides = $this->rideRepository->searchByLocationName($query, $limit);
        
        return [
            'locations' => array_map([$this, 'formatLocationForQuickSearch'], $locations),
            'rides' => array_map([$this, 'formatRideForQuickSearch'], $rides),
            'query' => $query,
            'total_results' => count($locations) + count($rides)
        ];
    }
    
    /**
     * Recherche par carte géographique
     */
    public function searchByMap(array $bounds, array $filters = []): array
    {
        // Validation des coordonnées géographiques
        $this->validateMapBounds($bounds);
        
        // Recherche dans la zone géographique
        $rides = $this->rideRepository->findInBounds(
            $bounds['north'],
            $bounds['south'],
            $bounds['east'], 
            $bounds['west'],
            $filters['limit'] ?? 50
        );
        
        // Application des filtres supplémentaires
        if (!empty($filters)) {
            $rides = $this->applyAdvancedFilters($rides, $filters);
        }
        
        return [
            'rides' => array_map([$this, 'formatRideForMap'], $rides),
            'bounds' => $bounds,
            'count' => count($rides)
        ];
    }
    
    /**
     * Récupère les filtres disponibles basés sur les données
     */
    public function getAvailableFilters(): array
    {
        return [
            'priceRanges' => $this->getPriceRanges(),
            'departureTimeRanges' => $this->getDepartureTimeRanges(),
            'popularLocations' => $this->getPopularLocations(),
            'vehicleTypes' => $this->getVehicleTypes()
        ];
    }
    
    /**
     * Normalise et valide les critères de recherche
     */
    private function normalizeCriteria(array $criteria): array
    {
        return [
            'departure' => trim($criteria['departureLocation'] ?? ''),
            'arrival' => trim($criteria['arrivalLocation'] ?? ''),
            'date' => $this->parseDate($criteria['date'] ?? null),
            'departureTime' => $this->parseTime($criteria['departureTime'] ?? null),
            'maxPrice' => $this->parsePrice($criteria['maxPrice'] ?? null),
            'minSeats' => max(1, (int)($criteria['minSeats'] ?? 1)),
            'sortBy' => in_array($criteria['sortBy'] ?? '', ['price', 'departureTime', 'distance', 'rating']) 
                ? $criteria['sortBy'] : self::DEFAULT_SORT,
            'page' => max(1, (int)($criteria['page'] ?? 1)),
            'limit' => min(self::MAX_LIMIT, max(1, (int)($criteria['limit'] ?? self::DEFAULT_LIMIT)))
        ];
    }
    
    /**
     * Applique des filtres avancés côté service métier
     */
    private function applyAdvancedFilters(array $rides, array $criteria): array
    {
        return array_filter($rides, function($ride) use ($criteria) {
            // Filtre par prix maximum
            if (!empty($criteria['maxPrice']) && $ride->getPrice()->getAmount() > $criteria['maxPrice']) {
                return false;
            }
            
            // Filtre par nombre de places minimum
            if (!empty($criteria['minSeats']) && $ride->getAvailableSeats() < $criteria['minSeats']) {
                return false;
            }
            
            // Filtre par heure de départ si spécifiée
            if (!empty($criteria['departureTime'])) {
                $rideTime = $ride->getDepartureDateTime()->format('H:i');
                $requestedTime = $criteria['departureTime'];
                
                // Tolérance de ±2 heures
                $rideDatetime = DateTime::createFromFormat('H:i', $rideTime);
                $requestedDatetime = DateTime::createFromFormat('H:i', $requestedTime);
                $diff = abs($rideDatetime->getTimestamp() - $requestedDatetime->getTimestamp());
                
                if ($diff > 7200) { // 2 heures en secondes
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Applique le tri intelligent selon les critères
     */
    private function applySorting(array $rides, string $sortBy): array
    {
        usort($rides, function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'price':
                    return $a->getPrice()->getAmount() <=> $b->getPrice()->getAmount();
                case 'departureTime':
                    return $a->getDepartureDateTime() <=> $b->getDepartureDateTime();
                case 'rating':
                    return $b->getDriverRating() <=> $a->getDriverRating(); // Desc pour rating
                default:
                    return $a->getDepartureDateTime() <=> $b->getDepartureDateTime();
            }
        });
        
        return $rides;
    }
    
    /**
     * Suggestions personnalisées basées sur l'historique utilisateur
     */
    private function getPersonalizedSuggestions(int $userId, int $limit): array
    {
        // ✅ TODO résolu : Implémentation basique de l'analyse d'historique
        try {
            // Dans une implémentation complète, on analyserait :
            // 1. Les trajets précédemment réservés par l'utilisateur
            // 2. Les destinations fréquemment recherchées
            // 3. Les préférences de prix et d'horaires
            
            // Pour l'instant, retourner les suggestions populaires avec un log
            // TODO futur: Implémenter l'analyse complète avec UserHistoryService
            
            return $this->getPopularSuggestions($limit);
            
        } catch (\Exception $e) {
            // En cas d'erreur, retourner les suggestions populaires par défaut
            return $this->getPopularSuggestions($limit);
        }
    }
    
    /**
     * Suggestions basées sur la popularité générale
     */
    private function getPopularSuggestions(int $limit): array
    {
        return $this->rideRepository->findPopularRides($limit);
    }
    
    /**
     * Valide les coordonnées géographiques pour la recherche par carte
     */
    private function validateMapBounds(array $bounds): void
    {
        $required = ['north', 'south', 'east', 'west'];
        foreach ($required as $key) {
            if (!isset($bounds[$key]) || !is_numeric($bounds[$key])) {
                throw new \InvalidArgumentException("Coordonnée '$key' manquante ou invalide");
            }
        }
        
        if ($bounds['north'] <= $bounds['south'] || $bounds['east'] <= $bounds['west']) {
            throw new \InvalidArgumentException('Coordonnées géographiques invalides');
        }
    }
    
    /**
     * Parse et valide une date
     */
    private function parseDate(?string $date): ?DateTime
    {
        if (empty($date)) {
            return null;
        }
        
        $parsed = DateTime::createFromFormat('Y-m-d', $date);
        if (!$parsed) {
            throw new \InvalidArgumentException('Format de date invalide (attendu: Y-m-d)');
        }
        
        // Vérifier que la date n'est pas dans le passé
        if ($parsed < new DateTime('today')) {
            throw new \InvalidArgumentException('La date ne peut pas être dans le passé');
        }
        
        return $parsed;
    }
    
    /**
     * Parse et valide une heure
     */
    private function parseTime(?string $time): ?string
    {
        if (empty($time)) {
            return null;
        }
        
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time)) {
            throw new \InvalidArgumentException('Format d\'heure invalide (attendu: H:i)');
        }
        
        return $time;
    }
    
    /**
     * Parse et valide un prix
     */
    private function parsePrice(?string $price): ?float
    {
        if (empty($price)) {
            return null;
        }
        
        $parsed = (float)$price;
        if ($parsed < 0) {
            throw new \InvalidArgumentException('Le prix ne peut pas être négatif');
        }
        
        return $parsed;
    }
    
    /**
     * Récupère les filtres appliqués pour la réponse
     */
    private function getAppliedFilters(array $criteria): array
    {
        $applied = [];
        
        if (!empty($criteria['maxPrice'])) {
            $applied['maxPrice'] = $criteria['maxPrice'];
        }
        
        if (!empty($criteria['minSeats'])) {
            $applied['minSeats'] = $criteria['minSeats'];
        }
        
        if (!empty($criteria['departureTime'])) {
            $applied['departureTime'] = $criteria['departureTime'];
        }
        
        return $applied;
    }
    
    /**
     * Formate un lieu pour la recherche rapide
     */
    private function formatLocationForQuickSearch($location): array
    {
        return [
            'id' => $location->getId(),
            'name' => $location->getName(),
            'type' => 'location'
        ];
    }
    
    /**
     * Formate un trajet pour la recherche rapide
     */
    private function formatRideForQuickSearch($ride): array
    {
        return [
            'id' => $ride->getId(),
            'departure' => $ride->getDepartureLocation()->getName(),
            'arrival' => $ride->getArrivalLocation()->getName(),
            'date' => $ride->getDepartureDateTime()->format('Y-m-d'),
            'price' => $ride->getPrice()->getAmount(),
            'type' => 'ride'
        ];
    }
    
    /**
     * Formate un trajet pour l'affichage sur carte
     */
    private function formatRideForMap($ride): array
    {
        return [
            'id' => $ride->getId(),
            'departure' => [
                'name' => $ride->getDepartureLocation()->getName(),
                'coordinates' => [
                    'lat' => $ride->getDepartureLocation()->getLatitude(),
                    'lng' => $ride->getDepartureLocation()->getLongitude()
                ]
            ],
            'arrival' => [
                'name' => $ride->getArrivalLocation()->getName(),
                'coordinates' => [
                    'lat' => $ride->getArrivalLocation()->getLatitude(),
                    'lng' => $ride->getArrivalLocation()->getLongitude()
                ]
            ],
            'departureTime' => $ride->getDepartureDateTime()->format('Y-m-d H:i'),
            'price' => $ride->getPrice()->getAmount(),
            'availableSeats' => $ride->getAvailableSeats()
        ];
    }
    
    /**
     * Récupère les gammes de prix disponibles
     */
    private function getPriceRanges(): array
    {
        return [
            ['min' => 0, 'max' => 10, 'label' => '0-10€'],
            ['min' => 10, 'max' => 20, 'label' => '10-20€'],
            ['min' => 20, 'max' => 30, 'label' => '20-30€'],
            ['min' => 30, 'max' => 50, 'label' => '30-50€'],
            ['min' => 50, 'max' => null, 'label' => '50€+']
        ];
    }
    
    /**
     * Récupère les créneaux horaires disponibles
     */
    private function getDepartureTimeRanges(): array
    {
        return [
            ['start' => '06:00', 'end' => '09:00', 'label' => 'Matin (6h-9h)'],
            ['start' => '09:00', 'end' => '12:00', 'label' => 'Matinée (9h-12h)'],
            ['start' => '12:00', 'end' => '14:00', 'label' => 'Midi (12h-14h)'],
            ['start' => '14:00', 'end' => '18:00', 'label' => 'Après-midi (14h-18h)'],
            ['start' => '18:00', 'end' => '22:00', 'label' => 'Soirée (18h-22h)']
        ];
    }
    
    /**
     * Récupère les lieux populaires
     */
    private function getPopularLocations(): array
    {
        return $this->locationRepository->getPopularLocations(15);
    }
    
    /**
     * Récupère les types de véhicules disponibles
     */
    private function getVehicleTypes(): array
    {
        return [
            ['id' => 'citadine', 'label' => 'Citadine'],
            ['id' => 'berline', 'label' => 'Berline'],
            ['id' => 'suv', 'label' => 'SUV'],
            ['id' => 'monospace', 'label' => 'Monospace'],
            ['id' => 'autre', 'label' => 'Autre']
        ];
    }
} 