<?php

namespace App\Infrastructure\Examples;

use App\Domain\Services\RideManagementService;
use App\Infrastructure\Factories\RepositoryFactory;
use App\Core\Application;
use App\Core\Logger;

/**
 * Exemple d'utilisation des repositories MySQL
 * Ce fichier montre comment utiliser la nouvelle architecture orientée objet
 */
class RepositoryUsageExample
{
    /**
     * Exemple d'utilisation des repositories avec l'architecture existante
     */
    public static function example(Application $app): void
    {
        // Créer le logger (adapté selon votre implémentation existante)
        $logger = new Logger();

        // Créer la factory en utilisant la classe Database existante
        $repositoryFactory = RepositoryFactory::createFromLegacyDatabase(
            $app->getDatabase(),
            $logger
        );

        // Créer les repositories
        $rideRepository = $repositoryFactory->createRideRepository();
        $locationRepository = $repositoryFactory->createLocationRepository();

        // Créer le service de gestion des trajets
        $rideManagementService = new RideManagementService($rideRepository);

        // Exemples d'utilisation
        
        // 1. Rechercher un trajet par ID
        $ride = $rideRepository->findById(1);
        if ($ride) {
            echo "Trajet trouvé : " . $ride->getDeparture()->getName() . " → " . $ride->getArrival()->getName() . "\n";
        }

        // 2. Rechercher des trajets disponibles
        $availableRides = $rideRepository->findAvailableRides(5);
        echo "Trajets disponibles : " . count($availableRides) . "\n";

        // 3. Rechercher des lieux
        $locations = $locationRepository->searchByName("Paris", 5);
        echo "Lieux trouvés pour 'Paris' : " . count($locations) . "\n";

        // 4. Utiliser le service de gestion (avec logique métier)
        $searchResults = $rideManagementService->searchRides(
            null, // departure
            null, // arrival
            new \DateTime('2024-02-01'), // date
            'price' // sortBy
        );
        echo "Résultats de recherche : " . count($searchResults) . "\n";
    }

    /**
     * Exemple d'intégration dans un contrôleur refactorisé
     */
    public static function controllerIntegrationExample(): string
    {
        return '
        // Dans votre contrôleur refactorisé :
        
        public function __construct(Application $app)
        {
            parent::__construct();
            
            $logger = new Logger();
            $repositoryFactory = RepositoryFactory::createFromLegacyDatabase(
                $app->getDatabase(),
                $logger
            );
            
            $this->rideRepository = $repositoryFactory->createRideRepository();
            $this->rideManagementService = new RideManagementService($this->rideRepository);
        }
        
        public function index(): array
        {
            try {
                // Utilisation de la logique métier encapsulée
                $rides = $this->rideManagementService->getAvailableRides(limit: 20);
                
                return $this->success([
                    "rides" => array_map(function($ride) {
                        return [
                            "id" => $ride->getId(),
                            "departure" => $ride->getDeparture()->getName(),
                            "arrival" => $ride->getArrival()->getName(),
                            "price" => $ride->getPricePerPerson()->getAmount(),
                            "availableSeats" => $ride->getAvailableSeats(),
                            "carbonFootprint" => $ride->getCarbonFootprint()
                        ];
                    }, $rides)
                ]);
                
            } catch (Exception $e) {
                return $this->error("Erreur lors de la récupération des trajets", 500);
            }
        }
        ';
    }
} 