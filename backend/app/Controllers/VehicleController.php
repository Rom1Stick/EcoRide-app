<?php

namespace App\Controllers;

use PDO;

class VehicleController extends Controller
{
    /**
     * Constructeur avec vérification de la structure de la table
     */
    public function __construct()
    {
        parent::__construct();
        $this->ensureVehicleTableStructure();
    }

    /**
     * Vérifie et met à jour la structure de la table Voiture si nécessaire
     */
    private function ensureVehicleTableStructure()
    {
        try {
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Vérifier si les colonnes existent
            $stmt = $db->prepare("SHOW COLUMNS FROM Voiture LIKE 'marque'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                // Les colonnes n'existent pas, les ajouter
                $db->exec("
                    ALTER TABLE Voiture 
                    ADD COLUMN marque VARCHAR(50) AFTER voiture_id,
                    ADD COLUMN modele VARCHAR(50) AFTER marque,
                    ADD COLUMN annee INT AFTER modele,
                    ADD COLUMN places INT DEFAULT 5 AFTER couleur;
                    
                    ALTER TABLE Voiture 
                    MODIFY COLUMN modele_id INT NULL,
                    MODIFY COLUMN energie_id INT NULL;
                ");
            }
        } catch (\Exception $e) {
            // En cas d'erreur, ne pas bloquer l'exécution
            // Ce sera géré par les méthodes qui accèdent à la table
        }
    }

    /**
     * Liste les véhicules de l'utilisateur (chauffeur)
     */
    public function index(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        // Vérifier rôle
        if (($_SERVER['AUTH_USER_ROLE'] ?? '') !== 'chauffeur') {
            return $this->error('Accès réservé aux chauffeurs', 403);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare(
            'SELECT voiture_id AS id, modele_id, immatriculation, energie_id, couleur, date_premiere_immat AS first_immat 
             FROM Voiture WHERE utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['vehicles' => $vehicles]);
    }

    /**
     * Récupère le véhicule de l'utilisateur connecté
     */
    public function getUserVehicle(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        if (!$userId) {
            return $this->error('Utilisateur non authentifié', 401);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare(
            'SELECT v.voiture_id AS id, v.marque, v.modele, v.annee, v.immatriculation, v.couleur, v.places,
                    v.energie_id, COALESCE(te.libelle, "Non spécifié") AS energie_nom
             FROM Voiture v
             LEFT JOIN TypeEnergie te ON v.energie_id = te.energie_id
             WHERE v.utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            return $this->error('Aucun véhicule trouvé pour cet utilisateur', 404);
        }

        return $this->success(['vehicle' => $vehicle]);
    }

    /**
     * Crée un nouveau véhicule pour l'utilisateur
     */
    public function store(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        if (!$userId) {
            return $this->error('Utilisateur non authentifié', 401);
        }

        $data = sanitize($this->getJsonData());
        
        // Vérifier les données obligatoires
        $required = ['marque', 'modele', 'immatriculation'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            return $this->error('Données manquantes: ' . implode(', ', $missing), 400);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $db->beginTransaction();
        
        try {
            // Vérifier si l'utilisateur a déjà un véhicule
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM Voiture WHERE utilisateur_id = ?');
            $checkStmt->execute([$userId]);
            
            if ((int)$checkStmt->fetchColumn() > 0) {
                return $this->error('Un véhicule est déjà enregistré pour cet utilisateur', 409);
            }
            
            // Insérer le véhicule
            $stmt = $db->prepare(
                'INSERT INTO Voiture (marque, modele, annee, immatriculation, couleur, places, energie_id, utilisateur_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            
            $stmt->execute([
                $data['marque'],
                $data['modele'],
                $data['annee'] ?? null,
                $data['immatriculation'],
                $data['couleur'] ?? null,
                $data['places'] ?? 5,
                $data['energie_id'] ?? null,
                $userId
            ]);
            
            $vehicleId = (int) $db->lastInsertId();
            
            // Attribuer le rôle chauffeur à l'utilisateur s'il ne l'a pas déjà
            $roleStmt = $db->prepare('SELECT role_id FROM Role WHERE libelle = ?');
            $roleStmt->execute(['chauffeur']);
            $roleId = $roleStmt->fetchColumn();
            
            if ($roleId) {
                $hasRoleStmt = $db->prepare('SELECT COUNT(*) FROM Possede WHERE utilisateur_id = ? AND role_id = ?');
                $hasRoleStmt->execute([$userId, $roleId]);
                
                if ((int)$hasRoleStmt->fetchColumn() === 0) {
                    $addRoleStmt = $db->prepare('INSERT INTO Possede (utilisateur_id, role_id) VALUES (?, ?)');
                    $addRoleStmt->execute([$userId, $roleId]);
                }
            }
            
            $db->commit();
            
            // Récupérer le véhicule créé avec le nom du type d'énergie
            $vehicleStmt = $db->prepare(
                'SELECT v.voiture_id AS id, v.marque, v.modele, v.annee, v.immatriculation, v.couleur, v.places, 
                        v.energie_id, COALESCE(te.libelle, "Non spécifié") AS energie_nom
                 FROM Voiture v
                 LEFT JOIN TypeEnergie te ON v.energie_id = te.energie_id
                 WHERE v.voiture_id = ?'
            );
            $vehicleStmt->execute([$vehicleId]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->success(
                ['vehicle' => $vehicle], 
                'Véhicule créé avec succès'
            );
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->error('Erreur lors de la création du véhicule : ' . $e->getMessage(), 500);
        }
    }

    /**
     * Met à jour un véhicule existant
     */
    public function update(int $id): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        if (!$userId) {
            return $this->error('Utilisateur non authentifié', 401);
        }

        $data = sanitize($this->getJsonData());
        
        if (empty($data)) {
            return $this->error('Aucune donnée fournie', 400);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        
        // Vérifier que le véhicule appartient à l'utilisateur
        $checkStmt = $db->prepare('SELECT COUNT(*) FROM Voiture WHERE voiture_id = ? AND utilisateur_id = ?');
        $checkStmt->execute([$id, $userId]);
        
        if ((int)$checkStmt->fetchColumn() === 0) {
            return $this->error('Véhicule introuvable ou non autorisé', 404);
        }
        
        try {
            // Préparer les champs à mettre à jour
            $updateFields = [];
            $params = [];
            
            $availableFields = ['marque', 'modele', 'annee', 'immatriculation', 'couleur', 'places', 'energie_id'];
            
            foreach ($availableFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return $this->error('Aucun champ valide à mettre à jour', 400);
            }
            
            // Ajouter l'ID à la fin des paramètres
            $params[] = $id;
            
            $stmt = $db->prepare(
                'UPDATE Voiture SET ' . implode(', ', $updateFields) . ' WHERE voiture_id = ?'
            );
            
            $stmt->execute($params);
            
            // Récupérer le véhicule mis à jour avec le nom du type d'énergie
            $vehicleStmt = $db->prepare(
                'SELECT v.voiture_id AS id, v.marque, v.modele, v.annee, v.immatriculation, v.couleur, v.places,
                        v.energie_id, COALESCE(te.libelle, "Non spécifié") AS energie_nom
                 FROM Voiture v
                 LEFT JOIN TypeEnergie te ON v.energie_id = te.energie_id
                 WHERE v.voiture_id = ?'
            );
            $vehicleStmt->execute([$id]);
            $vehicle = $vehicleStmt->fetch(PDO::FETCH_ASSOC);
            
            return $this->success(
                ['vehicle' => $vehicle], 
                'Véhicule mis à jour avec succès'
            );
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la mise à jour du véhicule : ' . $e->getMessage(), 500);
        }
    }

    /**
     * Supprime un véhicule
     */
    public function destroy(int $id): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        if (!$userId) {
            return $this->error('Utilisateur non authentifié', 401);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        
        // Vérifier que le véhicule appartient à l'utilisateur
        $checkStmt = $db->prepare('SELECT COUNT(*) FROM Voiture WHERE voiture_id = ? AND utilisateur_id = ?');
        $checkStmt->execute([$id, $userId]);
        
        if ((int)$checkStmt->fetchColumn() === 0) {
            return $this->error('Véhicule introuvable ou non autorisé', 404);
        }
        
        try {
            $stmt = $db->prepare('DELETE FROM Voiture WHERE voiture_id = ?');
            $stmt->execute([$id]);
            
            return $this->success([], 'Véhicule supprimé avec succès');
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la suppression du véhicule : ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Récupère tous les types d'énergie disponibles
     */
    public function getEnergyTypes(): array
    {
        try {
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            $stmt = $db->prepare('SELECT energie_id AS id, libelle AS nom FROM TypeEnergie ORDER BY libelle');
            $stmt->execute();
            $energyTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success(['energyTypes' => $energyTypes]);
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la récupération des types d\'énergie : ' . $e->getMessage(), 500);
        }
    }
} 