<?php

namespace App\Controllers;

class HomeController extends Controller
{
    /**
     * Point d'entrée de l'API
     * @return array
     */
    public function index(): array
    {
        return $this->success([ 'message' => 'Bienvenue dans l\'API EcoRide' ]);
    }

    /**
     * Vérifie l'état de santé de l'application
     * @return array
     */
    public function health(): array
    {
        return $this->success([ 'status' => 'ok' ]);
    }

    /**
     * Test de connexion à la base de données
     * @return array
     */
    public function testDatabase(): array
    {
        try {
            // Test des variables d'environnement
            $envInfo = [
                'JAWSDB_URL' => getenv('JAWSDB_URL') ? 'définie' : 'non définie',
                'DATABASE_URL' => getenv('DATABASE_URL') ? 'définie' : 'non définie',
                'DB_HOST' => getenv('DB_HOST') ?: 'non définie',
                'DB_DATABASE' => getenv('DB_DATABASE') ?: 'non définie',
                'DB_USERNAME' => getenv('DB_USERNAME') ?: 'non définie'
            ];

            // Test de connexion
            $database = new \App\Core\Database();
            $connection = $database->getMysqlConnection();
            
            // Test d'une requête simple
            $stmt = $connection->query("SELECT COUNT(*) as count FROM Utilisateur");
            $result = $stmt->fetch();
            
            return $this->success([
                'database_connection' => 'OK',
                'environment_variables' => $envInfo,
                'users_count' => $result['count']
            ]);
            
        } catch (\Exception $e) {
            return $this->error([
                'database_connection' => 'FAILED',
                'error_message' => $e->getMessage(),
                'environment_variables' => $envInfo ?? []
            ]);
        }
    }

    /**
     * Affiche toutes les tables de la base de données (debug)
     * @return array
     */
    public function debugTables(): array
    {
        try {
            $database = new \App\Core\Database();
            $connection = $database->getMysqlConnection();
            
            // Lister toutes les tables
            $stmt = $connection->query("SHOW TABLES");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            
            return $this->success([
                'tables' => $tables,
                'count' => count($tables)
            ]);
            
        } catch (\Exception $e) {
            return $this->error([
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Affiche les utilisateurs (debug)
     * @return array
     */
    public function debugUsers(): array
    {
        try {
            $database = new \App\Core\Database();
            $connection = $database->getMysqlConnection();
            
            // Récupérer les utilisateurs avec leurs rôles
            $stmt = $connection->query("
                SELECT 
                    u.utilisateur_id,
                    u.nom,
                    u.prenom,
                    u.email,
                    u.pseudo,
                    u.date_creation,
                    u.confirmed,
                    GROUP_CONCAT(r.libelle) as roles,
                    cb.solde as credits
                FROM Utilisateur u
                LEFT JOIN Possede p ON u.utilisateur_id = p.utilisateur_id
                LEFT JOIN Role r ON p.role_id = r.role_id
                LEFT JOIN CreditBalance cb ON u.utilisateur_id = cb.utilisateur_id
                GROUP BY u.utilisateur_id
                ORDER BY u.date_creation DESC
                LIMIT 10
            ");
            $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            return $this->success([
                'users' => $users,
                'count' => count($users)
            ]);
            
        } catch (\Exception $e) {
            return $this->error([
                'error_message' => $e->getMessage()
            ]);
        }
    }
} 