<?php

namespace App\Controllers;

use App\Core\Security;

class UserController extends Controller
{
    /**
     * Renvoie les informations de l'utilisateur connecté
     *
     * @return array
     */
    public function me(): array
    {
        // Récupérer le token depuis le cookie ou l'en-tête Authorization
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        if (!$token) {
            return $this->error('Non authentifié', 401);
        }
        // Décoder le payload du JWT (sans vérification de signature pour l'affichage)
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return $this->error('Token invalide', 401);
        }
        $payloadB64 = $parts[1];
        $payloadJson = base64_decode(str_replace(['-', '_'], ['+', '/'], $payloadB64));
        $payload = json_decode($payloadJson, true);
        if (!$payload || !isset($payload['sub'])) {
            return $this->error('Token invalide', 401);
        }
        $userId = (int)$payload['sub'];

        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare(
            'SELECT utilisateur_id AS id,
                    CONCAT(nom, " ", prenom) AS name,
                    email,
                    pseudo,
                    photo_path
             FROM Utilisateur
             WHERE utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->error('Utilisateur introuvable', 404);
        }

        // Définir l'image par défaut si nécessaire
        if (empty($user['photo_path'])) {
            $user['photo_path'] = '/assets/images/Logo_EcoRide.svg';
        }

        // Récupérer les rôles de l'utilisateur
        $rolesStmt = $db->prepare(
            'SELECT r.role_id AS id, r.libelle AS name
             FROM Role r
             JOIN Possede p ON r.role_id = p.role_id
             WHERE p.utilisateur_id = ?'
        );
        $rolesStmt->execute([$userId]);
        $roles = $rolesStmt->fetchAll(\PDO::FETCH_ASSOC);

        // Nettoyer les données sensibles
        unset($user['mot_passe']);

        // Créer une réponse structurée
        $userData = [
            'id'        => (int)$user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'username'  => $user['pseudo'],
            'photoPath' => $user['photo_path'],
            'roles'     => $roles
        ];

        // Marquer l'utilisateur comme admin s'il a un rôle d'administrateur
        $isAdmin = false;
        foreach ($roles as $role) {
            if (stripos($role['name'], 'admin') !== false) {
                $isAdmin = true;
                break;
            }
        }
        $userData['isAdmin'] = $isAdmin;

        return $this->success($userData);
    }

    /**
     * Soumet une demande de changement de rôle pour l'utilisateur connecté
     *
     * @return array
     */
    public function requestRole(): array
    {
        $userId = $_SERVER['AUTH_USER_ID'] ?? null;
        if (!$userId) {
            return $this->error('Non authentifié', 401);
        }
        $data = sanitize($this->getJsonData());
        $roleId = isset($data['role_id']) ? (int)$data['role_id'] : null;
        if (!$roleId) {
            return $this->error('role_id manquant', 400);
        }
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Vérifier s'il y a déjà une demande en attente
        $check = $db->prepare("SELECT COUNT(*) FROM RoleRequest WHERE user_id = ? AND status = 'pending'");
        $check->execute([$userId]);
        if ((int)$check->fetchColumn() > 0) {
            return $this->error('Une demande est déjà en cours', 400);
        }
        // Insérer la demande
        $stmt = $db->prepare('INSERT INTO RoleRequest (user_id, role_id) VALUES (?, ?)');
        $stmt->execute([$userId, $roleId]);
        return $this->success(null, 'Demande de changement de rôle soumise');
    }
    
    /**
     * Ajoute le rôle spécifié à l'utilisateur connecté
     * 
     * @return array
     */
    public function addRole(): array 
    {
        // Récupérer l'ID de l'utilisateur authentifié
        $userId = $_SERVER['AUTH_USER_ID'] ?? null;
        if (!$userId) {
            return $this->error('Non authentifié', 401);
        }
        
        // Récupérer les données de la requête
        $data = $this->getJsonData();
        $roleName = $data['role'] ?? null;
        
        if (!$roleName) {
            return $this->error('Nom du rôle manquant', 400);
        }
        
        // Normaliser le nom du rôle (le mettre en minuscule)
        $roleName = strtolower($roleName);
        
        // Vérifier si le rôle demandé est "passager" (seul rôle autorisé pour l'auto-attribution)
        if ($roleName !== 'passager') {
            return $this->error('Seul le rôle "passager" peut être ajouté automatiquement', 403);
        }
        
        $db = $this->app->getDatabase()->getMysqlConnection();
        
        // Vérifier si l'utilisateur a déjà ce rôle
        $checkStmt = $db->prepare(
            'SELECT COUNT(*) 
             FROM Possede p
             JOIN Role r ON p.role_id = r.role_id
             WHERE p.utilisateur_id = ? AND LOWER(r.libelle) = ?'
        );
        $checkStmt->execute([$userId, $roleName]);
        
        if ((int)$checkStmt->fetchColumn() > 0) {
            return $this->success(null, 'Vous avez déjà ce rôle');
        }
        
        // Récupérer l'ID du rôle
        $roleStmt = $db->prepare('SELECT role_id FROM Role WHERE LOWER(libelle) = ?');
        $roleStmt->execute([$roleName]);
        $roleId = $roleStmt->fetchColumn();
        
        if (!$roleId) {
            // Si le rôle n'existe pas dans la base de données, le créer
            try {
                $db->beginTransaction();
                
                $createRoleStmt = $db->prepare('INSERT INTO Role (libelle) VALUES (?)');
                $createRoleStmt->execute(['Passager']);
                $roleId = $db->lastInsertId();
                
                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
                return $this->error('Erreur lors de la création du rôle: ' . $e->getMessage(), 500);
            }
        }
        
        // Ajouter le rôle à l'utilisateur
        try {
            $db->beginTransaction();
            
            $addRoleStmt = $db->prepare('INSERT INTO Possede (utilisateur_id, role_id) VALUES (?, ?)');
            $addRoleStmt->execute([$userId, $roleId]);
            
            $db->commit();
            
            return $this->success(null, 'Rôle ajouté avec succès');
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->error('Erreur lors de l\'ajout du rôle: ' . $e->getMessage(), 500);
        }
    }
} 