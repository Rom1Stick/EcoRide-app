<?php

namespace Tests\Integration;

use App\Models\Repositories\TripRepository;
use App\Models\Entities\Trip;
use PDO;
use Tests\Integration\DatabaseTestCase;

/**
 * Tests d'intégration pour TripRepository
 */
class TripRepositoryTest extends DatabaseTestCase
{
    private TripRepository $tripRepository;
    
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialiser le repository avec la connexion PDO
        $this->tripRepository = new TripRepository($this->pdo);
        
        // Insérer des données de test
        $this->setupTestData();
    }
    
    private function setupTestData(): void
    {
        // Vérifier et nettoyer les tables avant insertion
        // Pour éviter les erreurs de duplication de clés primaires
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $this->pdo->exec('TRUNCATE TABLE covoiturage');
        $this->pdo->exec('TRUNCATE TABLE Voiture');
        $this->pdo->exec('TRUNCATE TABLE Utilisateur');
        $this->pdo->exec('TRUNCATE TABLE statut');
        $this->pdo->exec('TRUNCATE TABLE lieu');
        $this->pdo->exec('TRUNCATE TABLE Energie');
        $this->pdo->exec('TRUNCATE TABLE Modele');
        $this->pdo->exec('TRUNCATE TABLE Marque');
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        // Insérer des marques
        $this->pdo->exec("INSERT INTO Marque (marque_id, nom) VALUES (1, 'Renault'), (2, 'Peugeot')");
        
        // Insérer des modèles
        $this->pdo->exec("INSERT INTO Modele (modele_id, marque_id, nom) VALUES 
            (1, 1, 'Clio'), 
            (2, 2, '208')");
        
        // Insérer des types d'énergie
        $this->pdo->exec("INSERT INTO Energie (energie_id, nom) VALUES 
            (1, 'Essence'), 
            (2, 'Diesel'), 
            (3, 'Électrique')");
        
        // Insérer des utilisateurs
        $this->pdo->exec("INSERT INTO Utilisateur (utilisateur_id, nom, prenom, email, password, role_id) VALUES 
            (1, 'Dupont', 'Jean', 'jean.dupont@example.com', 'motdepasse123', 1),
            (2, 'Martin', 'Sophie', 'sophie.martin@example.com', 'pass456', 2)");
        
        // Insérer des véhicules
        $this->pdo->exec("INSERT INTO Voiture (voiture_id, modele_id, immatriculation, energie_id, couleur, date_premiere_immat, utilisateur_id) VALUES 
            (1, 1, 'AB-123-CD', 1, 'Rouge', '2020-01-15', 1),
            (2, 2, 'EF-456-GH', 2, 'Bleu', '2019-05-20', 1),
            (3, 1, 'IJ-789-KL', 3, 'Blanc', '2021-03-10', 2)");
        
        // Insérer des lieux
        $this->pdo->exec("INSERT INTO lieu (lieu_id, nom, code_postal) VALUES
            (1, 'Gare de Lyon', '75012'),
            (2, 'Gare du Nord', '75010'),
            (3, 'Gare de Marseille Saint-Charles', '13001')
        ");
        
        // Insérer des statuts de covoiturage
        $this->pdo->exec("INSERT INTO statut (statut_id, libelle) VALUES
            (1, 'En attente'),
            (2, 'Confirmé'),
            (3, 'Terminé'),
            (4, 'Annulé')");
        
        // Insérer des covoiturages
        $this->pdo->exec("INSERT INTO covoiturage (covoiturage_id, voiture_id, lieu_depart_id, lieu_arrivee_id, date_depart, heure_depart, nb_place, prix_personne, statut_id, date_creation) VALUES
            (1, 1, 1, 3, '2023-12-15', '08:00:00', 3, 45.50, 2, NOW()),
            (2, 1, 3, 1, '2023-12-20', '18:30:00', 2, 48.75, 1, NOW()),
            (3, 2, 2, 3, '2023-12-18', '10:15:00', 4, 39.99, 2, NOW())
        ");
    }
    
    /**
     * Test de la récupération de tous les covoiturages
     */
    public function testFindAll(): void
    {
        // Exécuter la méthode findAll
        $trips = $this->tripRepository->findAll();
        
        // Vérifier qu'on a bien récupéré 3 covoiturages
        $this->assertCount(3, $trips);
        
        // Vérifier que les covoiturages sont dans l'ordre décroissant par ID
        $this->assertEquals(3, $trips[0]->covoiturage_id);
        $this->assertEquals(2, $trips[1]->covoiturage_id);
        $this->assertEquals(1, $trips[2]->covoiturage_id);
    }
    
    /**
     * Test de récupération d'un covoiturage par son ID
     */
    public function testFindById(): void
    {
        // Test avec un ID existant
        $trip = $this->tripRepository->findTripById(2);
        
        $this->assertNotNull($trip);
        $this->assertEquals(2, $trip->covoiturage_id);
        $this->assertEquals(1, $trip->voiture_id);
        $this->assertEquals(3, $trip->lieu_depart_id);
        $this->assertEquals(1, $trip->lieu_arrivee_id);
        
        // Test avec un ID inexistant
        $trip = $this->tripRepository->findTripById(999);
        $this->assertNull($trip);
    }
    
    /**
     * Test de création d'un covoiturage
     */
    public function testCreate(): void
    {
        // Création d'un nouveau covoiturage
        $newTrip = new Trip();
        $newTrip->voiture_id = 1;
        $newTrip->lieu_depart_id = 2;
        $newTrip->lieu_arrivee_id = 1;
        $newTrip->date_depart = date('Y-m-d');
        $newTrip->heure_depart = '14:30:00';
        $newTrip->nb_place = 2;
        $newTrip->prix_personne = 22.50;
        $newTrip->statut_id = 1;
        $newTrip->date_creation = date('Y-m-d H:i:s');
        
        $id = $this->tripRepository->create($newTrip);
        
        // Vérification que l'ID est généré
        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);
        
        // Vérification que le covoiturage peut être récupéré
        $createdTrip = $this->tripRepository->findTripById($id);
        $this->assertNotNull($createdTrip);
        $this->assertEquals(1, $createdTrip->voiture_id);
        $this->assertEquals(2, $createdTrip->lieu_depart_id);
        $this->assertEquals(1, $createdTrip->lieu_arrivee_id);
        $this->assertEquals(22.50, $createdTrip->prix_personne);
    }
    
    /**
     * Test de mise à jour d'un covoiturage
     */
    public function testUpdate(): void
    {
        // Récupération d'un covoiturage existant
        $trip = $this->tripRepository->findTripById(3);
        $this->assertNotNull($trip);
        
        // Modification du covoiturage
        $trip->nb_place = 5;
        $trip->prix_personne = 35.00;
        $trip->statut_id = 2; // Statut "Complet"
        
        $result = $this->tripRepository->update($trip);
        $this->assertTrue($result);
        
        // Vérification des modifications
        $updatedTrip = $this->tripRepository->findTripById(3);
        $this->assertEquals(5, $updatedTrip->nb_place);
        $this->assertEquals(35.00, $updatedTrip->prix_personne);
        $this->assertEquals(2, $updatedTrip->statut_id);
    }
    
    /**
     * Test de suppression d'un covoiturage
     */
    public function testDelete(): void
    {
        // Vérification que le covoiturage existe
        $trip = $this->tripRepository->findTripById(2);
        $this->assertNotNull($trip);
        
        // Suppression du covoiturage
        $result = $this->tripRepository->delete(2);
        $this->assertTrue($result);
        
        // Vérification que le covoiturage n'existe plus
        $deletedTrip = $this->tripRepository->findTripById(2);
        $this->assertNull($deletedTrip);
    }
    
    /**
     * Test de recherche de covoiturages par critères
     */
    public function testSearch(): void
    {
        // Test de recherche par lieu de départ
        $tripsByDeparture = $this->tripRepository->search(['departure_id' => 1]);
        $this->assertCount(1, $tripsByDeparture);
        $this->assertEquals(1, $tripsByDeparture[0]->covoiturage_id);
        
        // Test de recherche par lieu d'arrivée
        $tripsByArrival = $this->tripRepository->search(['arrival_id' => 1]);
        $this->assertCount(1, $tripsByArrival);
        $this->assertEquals(2, $tripsByArrival[0]->covoiturage_id);
        
        // Test de recherche avec multiple critères
        $tripsByMultiCriteria = $this->tripRepository->search([
            'departure_id' => 2,
            'arrival_id' => 3
        ]);
        $this->assertCount(1, $tripsByMultiCriteria);
        $this->assertEquals(3, $tripsByMultiCriteria[0]->covoiturage_id);
        
        // Test de recherche qui ne retourne aucun résultat
        $noResults = $this->tripRepository->search([
            'departure_id' => 1,
            'arrival_id' => 2
        ]);
        $this->assertEmpty($noResults);
    }
    
    /**
     * Test de récupération des covoiturages par conducteur
     */
    public function testFindByDriverId(): void
    {
        // Les données ont déjà été insérées par setupTestData
        // Trouver les covoiturages du conducteur 1
        $trips = $this->tripRepository->findByDriverId(1);
        
        // Assurons-nous que nous avons le bon nombre de covoiturages pour l'utilisateur 1
        // Vérifions d'abord ceux qui sont créés dans setupTestData
        $vehiclesForUser1 = $this->pdo->query("SELECT COUNT(*) FROM Voiture WHERE utilisateur_id = 1")->fetchColumn();
        $tripsCountForUser1Vehicles = $this->pdo->query("
            SELECT COUNT(*) FROM covoiturage c 
            JOIN Voiture v ON c.voiture_id = v.voiture_id 
            WHERE v.utilisateur_id = 1
        ")->fetchColumn();
        
        $this->assertCount((int)$tripsCountForUser1Vehicles, $trips);
        
        // Vérifier pour l'utilisateur 2
        $user2Trips = $this->tripRepository->findByDriverId(2);
        $tripsCountForUser2Vehicles = $this->pdo->query("
            SELECT COUNT(*) FROM covoiturage c 
            JOIN Voiture v ON c.voiture_id = v.voiture_id 
            WHERE v.utilisateur_id = 2
        ")->fetchColumn();
        
        $this->assertCount((int)$tripsCountForUser2Vehicles, $user2Trips);
    }
    
    /**
     * Test de récupération des covoiturages par date
     */
    public function testFindByDateRange(): void
    {
        // Trouver les covoiturages dans une plage de dates
        $trips = $this->tripRepository->findByDateRange('2023-12-15', '2023-12-19');
        
        // Il y a 2 covoiturages dans cette plage
        $this->assertCount(2, $trips);
        
        // Vérifier un covoiturage spécifique
        $found = false;
        foreach ($trips as $trip) {
            if ($trip->covoiturage_id === 1) {
                $found = true;
                $this->assertEquals('2023-12-15', $trip->date_depart);
            }
        }
        $this->assertTrue($found, "Le covoiturage attendu n'a pas été trouvé dans les résultats");
        
        // Vérifier une plage sans covoiturage
        $emptyResults = $this->tripRepository->findByDateRange('2024-01-01', '2024-01-10');
        $this->assertEmpty($emptyResults);
    }
    
    /**
     * Test de récupération des covoiturages par trajet
     */
    public function testFindByRoute(): void
    {
        // Trouver les covoiturages pour un trajet spécifique
        $trips = $this->tripRepository->findByRoute(1, 3);
        
        // Il y a 1 covoiturage pour ce trajet
        $this->assertCount(1, $trips);
        $this->assertEquals(1, $trips[0]->covoiturage_id);
        
        // Vérifier pour un autre trajet
        $otherRouteTrips = $this->tripRepository->findByRoute(3, 1);
        $this->assertCount(1, $otherRouteTrips);
        $this->assertEquals(2, $otherRouteTrips[0]->covoiturage_id);
    }
} 