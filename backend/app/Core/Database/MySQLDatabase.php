<?php

namespace App\Core\Database;

use PDO;
use PDOException;
use App\Core\Logger;

/**
 * Implémentation MySQL de l'interface DatabaseInterface
 */
class MySQLDatabase implements DatabaseInterface
{
    /**
     * @var PDO Instance de PDO
     */
    private $pdo;

    /**
     * @var Logger Instance du logger
     */
    private $logger;

    /**
     * Constructeur de la base de données MySQL
     *
     * @param string $host Hôte de la base de données
     * @param string $database Nom de la base de données
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe
     * @param Logger $logger Instance du logger
     */
    public function __construct(string $host, string $database, string $username, string $password, Logger $logger)
    {
        $this->logger = $logger;
        try {
            $this->pdo = new PDO(
                "mysql:host=$host;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            $this->logger->error('Erreur de connexion à la base de données : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Exécute une requête SQL avec des paramètres
     *
     * @param string $query Requête SQL
     * @param array $params Paramètres pour la requête
     * @return mixed Résultat de la requête
     */
    public function query(string $query, array $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logger->error('Erreur d\'exécution de requête : ' . $e->getMessage(), [
                'query' => $query,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Prépare une requête SQL
     *
     * @param string $query Requête SQL
     * @return mixed Statement préparé
     */
    public function prepare(string $query)
    {
        try {
            return $this->pdo->prepare($query);
        } catch (PDOException $e) {
            $this->logger->error('Erreur de préparation de requête : ' . $e->getMessage(), [
                'query' => $query
            ]);
            throw $e;
        }
    }

    /**
     * Commence une transaction
     *
     * @return bool True si la transaction a été démarrée
     */
    public function beginTransaction(): bool
    {
        try {
            return $this->pdo->beginTransaction();
        } catch (PDOException $e) {
            $this->logger->error('Erreur de démarrage de transaction : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valide une transaction
     *
     * @return bool True si la transaction a été validée
     */
    public function commit(): bool
    {
        try {
            return $this->pdo->commit();
        } catch (PDOException $e) {
            $this->logger->error('Erreur de validation de transaction : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Annule une transaction
     *
     * @return bool True si la transaction a été annulée
     */
    public function rollBack(): bool
    {
        try {
            return $this->pdo->rollBack();
        } catch (PDOException $e) {
            $this->logger->error('Erreur d\'annulation de transaction : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupère l'identifiant de la dernière ligne insérée
     *
     * @return string Identifiant de la dernière ligne insérée
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
} 