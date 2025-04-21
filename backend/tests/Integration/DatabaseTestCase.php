<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use PDO;
use App\Services\Database;

/**
 * Classe de base pour les tests d'intégration avec une base de données réelle
 */
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $pdo;
    protected array $fixtures = [];
    
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration de la connexion à la base de données de test
        $host = 'mysql';
        $dbname = 'ecoride_test';
        $username = 'ecorider';
        $password = 'securepass';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        
        try {
            $this->pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (\PDOException $e) {
            $this->markTestSkipped('Impossible de se connecter à la base de données de test: ' . $e->getMessage());
        }
        
        // Nettoyer et charger les fixtures avant chaque test
        $this->cleanDatabase();
        $this->loadFixtures();
    }
    
    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        // Désactiver temporairement les contraintes de clé étrangère
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        // Vider les tables de test (dans l'ordre inverse des dépendances)
        $this->pdo->exec('TRUNCATE TABLE covoiturage');
        $this->pdo->exec('TRUNCATE TABLE Voiture');
        $this->pdo->exec('TRUNCATE TABLE Utilisateur');
        $this->pdo->exec('TRUNCATE TABLE statut');
        $this->pdo->exec('TRUNCATE TABLE lieu');
        $this->pdo->exec('TRUNCATE TABLE Energie');
        $this->pdo->exec('TRUNCATE TABLE Modele');
        $this->pdo->exec('TRUNCATE TABLE Marque');
        
        // Réactiver les contraintes de clé étrangère
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        
        parent::tearDown();
    }
    
    /**
     * Nettoie la base de données en vidant les tables utilisées dans les tests
     */
    protected function cleanDatabase(): void
    {
        // Désactiver les contraintes de clés étrangères temporairement
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        
        // Vider toutes les tables utilisées dans les tests
        foreach (array_keys($this->fixtures) as $table) {
            $this->pdo->exec("TRUNCATE TABLE `$table`");
        }
        
        // Réactiver les contraintes de clés étrangères
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
    
    /**
     * Charge les données de test dans la base de données
     */
    protected function loadFixtures(): void
    {
        foreach ($this->fixtures as $table => $records) {
            foreach ($records as $record) {
                $columns = implode('`, `', array_keys($record));
                $placeholders = implode(', ', array_fill(0, count($record), '?'));
                
                $stmt = $this->pdo->prepare("INSERT INTO `$table` (`$columns`) VALUES ($placeholders)");
                $stmt->execute(array_values($record));
            }
        }
    }
} 