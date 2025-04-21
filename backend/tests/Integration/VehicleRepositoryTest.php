<?php

namespace Tests\Integration;

use App\Models\Repositories\VehicleRepository;
use App\Models\Entities\Vehicle;

/**
 * Tests d'intégration pour VehicleRepository
 */
class VehicleRepositoryTest extends DatabaseTestCase
{
    private VehicleRepository $repository;
    
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialiser le repository avec la connexion PDO
        $this->repository = new VehicleRepository($this->pdo);
        
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
        $this->pdo->exec("INSERT INTO Marque (marque_id, nom) VALUES (1, 'Renault'), (2, 'Peugeot'), (3, 'Tesla')");
        
        // Insérer des modèles
        $this->pdo->exec("INSERT INTO Modele (modele_id, marque_id, nom) VALUES 
            (1, 1, 'Clio'), 
            (2, 1, 'Megane'), 
            (3, 2, '208'),
            (4, 3, 'Model 3')");
        
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
            (2, 3, 'EF-456-GH', 2, 'Bleu', '2019-05-20', 1),
            (3, 4, 'IJ-789-KL', 3, 'Blanc', '2022-03-10', 2)");
    }
    
    /**
     * Test de la récupération de tous les véhicules
     */
    public function testFindAll(): void
    {
        // Exécuter la méthode findAll
        $vehicles = $this->repository->findAll();
        
        // Vérifier qu'on a bien récupéré 3 véhicules
        $this->assertCount(3, $vehicles);
        
        // Vérifier que les véhicules sont dans l'ordre décroissant par ID
        $this->assertEquals(3, $vehicles[0]->voiture_id);
        $this->assertEquals(2, $vehicles[1]->voiture_id);
        $this->assertEquals(1, $vehicles[2]->voiture_id);
        
        // Vérifier les propriétés du premier véhicule (qui est le véhicule avec l'ID le plus élevé)
        $this->assertEquals('IJ-789-KL', $vehicles[0]->immatriculation);
        $this->assertEquals('Blanc', $vehicles[0]->couleur);
    }
    
    /**
     * Test de récupération d'un véhicule par son ID
     */
    public function testFindById(): void
    {
        // Récupérer un véhicule existant par son ID
        $vehicle = $this->repository->findVehicleById(2);
        
        // Vérifier qu'on a récupéré le bon véhicule
        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertEquals('EF-456-GH', $vehicle->immatriculation);
        $this->assertEquals('Bleu', $vehicle->couleur);
        
        // Tester avec un ID inexistant
        $nonExistentVehicle = $this->repository->findVehicleById(999);
        $this->assertNull($nonExistentVehicle);
    }
    
    /**
     * Test de récupération des véhicules par ID utilisateur
     */
    public function testFindByUserId(): void
    {
        // Trouver les véhicules de l'utilisateur 1
        $vehicles = $this->repository->findByUserId(1);
        
        // L'utilisateur 1 possède 2 véhicules
        $this->assertCount(2, $vehicles);
        
        // Vérifier les ids des véhicules
        $this->assertEquals(2, $vehicles[0]->voiture_id);
        $this->assertEquals(1, $vehicles[1]->voiture_id);
        
        // Vérifier qu'on trouve également le bon nombre pour l'utilisateur 2
        $user2Vehicles = $this->repository->findByUserId(2);
        $this->assertCount(1, $user2Vehicles);
        $this->assertEquals(3, $user2Vehicles[0]->voiture_id);
    }
    
    /**
     * Test de recherche de véhicule par immatriculation
     */
    public function testFindByImmatriculation(): void
    {
        // Appeler la méthode à tester
        $vehicle = $this->repository->findByImmatriculation('EF-456-GH');
        
        // Vérifier que le bon véhicule est trouvé
        $this->assertInstanceOf(Vehicle::class, $vehicle);
        $this->assertEquals(2, $vehicle->voiture_id);
        $this->assertEquals('Bleu', $vehicle->couleur);
        
        // Tester avec une immatriculation inexistante
        $nonExistentVehicle = $this->repository->findByImmatriculation('XX-999-XX');
        $this->assertNull($nonExistentVehicle);
    }
    
    /**
     * Test de création d'un véhicule
     */
    public function testCreate(): void
    {
        // Créer un nouveau véhicule
        $newVehicle = new Vehicle();
        $newVehicle->modele_id = 2;
        $newVehicle->immatriculation = 'MN-012-OP';
        $newVehicle->energie_id = 1;
        $newVehicle->couleur = 'Noir';
        $newVehicle->date_premiere_immat = '2021-07-01';
        $newVehicle->utilisateur_id = 2;
        
        // Appeler la méthode create
        $createdId = $this->repository->create($newVehicle);
        
        // Vérifier que l'insertion a fonctionné
        $this->assertIsInt($createdId);
        $this->assertGreaterThan(0, $createdId);
        
        // Récupérer le véhicule créé pour vérifier
        $createdVehicle = $this->repository->findVehicleById($createdId);
        $this->assertInstanceOf(Vehicle::class, $createdVehicle);
        $this->assertEquals('MN-012-OP', $createdVehicle->immatriculation);
        $this->assertEquals('Noir', $createdVehicle->couleur);
    }
    
    /**
     * Test de mise à jour d'un véhicule
     */
    public function testUpdate(): void
    {
        // Récupérer un véhicule existant
        $vehicle = $this->repository->findVehicleById(1);
        $this->assertInstanceOf(Vehicle::class, $vehicle);
        
        // Modifier quelques propriétés
        $vehicle->couleur = 'Vert';
        $vehicle->energie_id = 2;
        
        // Appeler la méthode update
        $result = $this->repository->update($vehicle);
        
        // Vérifier que la mise à jour a fonctionné
        $this->assertTrue($result);
        
        // Récupérer le véhicule mis à jour pour vérifier
        $updatedVehicle = $this->repository->findVehicleById(1);
        $this->assertEquals('Vert', $updatedVehicle->couleur);
        $this->assertEquals(2, $updatedVehicle->energie_id);
    }
    
    /**
     * Test de suppression d'un véhicule
     */
    public function testDelete(): void
    {
        // Vérifier que le véhicule existe avant suppression
        $vehicle = $this->repository->findVehicleById(3);
        $this->assertInstanceOf(Vehicle::class, $vehicle);
        
        // Appeler la méthode delete
        $result = $this->repository->delete(3);
        
        // Vérifier que la suppression a fonctionné
        $this->assertTrue($result);
        
        // Vérifier que le véhicule n'existe plus
        $deletedVehicle = $this->repository->findVehicleById(3);
        $this->assertNull($deletedVehicle);
    }
    
    /**
     * Test de vérification d'existence d'une immatriculation
     */
    public function testRegistrationExists(): void
    {
        // Vérifier une immatriculation existante
        $exists = $this->repository->registrationExists('AB-123-CD');
        $this->assertTrue($exists);
        
        // Vérifier une immatriculation inexistante
        $notExists = $this->repository->registrationExists('XX-999-XX');
        $this->assertFalse($notExists);
        
        // Vérifier avec exclusion d'ID
        $excludingId = $this->repository->registrationExists('AB-123-CD', 1);
        $this->assertFalse($excludingId);
    }
} 