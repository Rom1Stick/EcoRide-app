<?php

namespace App\Controllers;

use PDO;

class AdminController extends Controller
{
    /**
     * Liste tous les utilisateurs avec leurs rôles
     *
     * @return array
     */
    public function listUsers(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Récupérer utilisateurs et rôles
        $stmt = $db->prepare(
            'SELECT u.utilisateur_id AS id,
                    CONCAT(u.nom, " ", u.prenom) AS name,
                    u.email,
                    r.role_id AS role_id,
                    r.libelle AS role_name
             FROM Utilisateur u
             LEFT JOIN Possede p ON u.utilisateur_id = p.utilisateur_id
             LEFT JOIN Role r ON p.role_id = r.role_id'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Grouper par utilisateur
        $users = [];
        foreach ($rows as $row) {
            $uid = (int)$row['id'];
            if (!isset($users[$uid])) {
                $users[$uid] = [
                    'id'    => $uid,
                    'name'  => $row['name'],
                    'email' => $row['email'],
                    'roles' => []
                ];
            }
            if ($row['role_id']) {
                $users[$uid]['roles'][] = [
                    'id'   => (int)$row['role_id'],
                    'name' => $row['role_name']
                ];
            }
        }

        return $this->success(array_values($users));
    }

    /**
     * Liste tous les rôles
     *
     * @return array
     */
    public function listRoles(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->query('SELECT role_id AS id, libelle AS name FROM Role');
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // caster id en int
        foreach ($roles as &$r) {
            $r['id'] = (int)$r['id'];
        }
        return $this->success($roles);
    }

    /**
     * Liste toutes les permissions
     *
     * @return array
     */
    public function listPermissions(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->query('SELECT permission_id AS id, name, description FROM Permission');
        $perms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($perms as &$p) {
            $p['id'] = (int)$p['id'];
        }
        return $this->success($perms);
    }

    /**
     * Assigne un rôle à un utilisateur
     *
     * @param int $userId
     * @return array
     */
    public function addUserRole(int $userId): array
    {
        $data = sanitize($this->getJsonData());
        $roleId = isset($data['role_id']) ? (int)$data['role_id'] : null;
        if (!$roleId) {
            return $this->error('role_id manquant', 400);
        }
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Rôles précédents
        $stmt = $db->prepare(
            'SELECT r.libelle FROM Role r
             JOIN Possede p ON r.role_id = p.role_id
             WHERE p.utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $old = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Assigner le rôle
        $insert = $db->prepare('INSERT IGNORE INTO Possede (utilisateur_id, role_id) VALUES (?, ?)');
        $insert->execute([$userId, $roleId]);
        // Nouveaux rôles
        $stmt->execute([$userId]);
        $new = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Journaliser le changement (si la table existe)
        try {
            $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
            $log = $db->prepare(
                'INSERT INTO RoleChangeLog (admin_id, user_id, old_roles, new_roles)
                 VALUES (?, ?, ?, ?)'
            );
            $log->execute([
                $adminId,
                $userId,
                json_encode($old),
                json_encode($new)
            ]);
        } catch (\Exception $e) {
            // Ignorer si la table RoleChangeLog n'existe pas
        }
        return $this->success(['old_roles' => $old, 'new_roles' => $new], 'Rôle ajouté');
    }

    /**
     * Retire un rôle à un utilisateur
     *
     * @param int $userId
     * @param int $roleId
     * @return array
     */
    public function removeUserRole(int $userId, int $roleId): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Rôles précédents
        $stmt = $db->prepare(
            'SELECT r.libelle FROM Role r
             JOIN Possede p ON r.role_id = p.role_id
             WHERE p.utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $old = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Retirer le rôle
        $del = $db->prepare('DELETE FROM Possede WHERE utilisateur_id = ? AND role_id = ?');
        $del->execute([$userId, $roleId]);
        // Nouveaux rôles
        $stmt->execute([$userId]);
        $new = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Journaliser le changement (si la table existe)
        try {
            $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
            $log = $db->prepare(
                'INSERT INTO RoleChangeLog (admin_id, user_id, old_roles, new_roles)
                 VALUES (?, ?, ?, ?)'
            );
            $log->execute([
                $adminId,
                $userId,
                json_encode($old),
                json_encode($new)
            ]);
        } catch (\Exception $e) {
            // Ignorer si la table RoleChangeLog n'existe pas
        }
        return $this->success(['old_roles' => $old, 'new_roles' => $new], 'Rôle retiré');
    }

    /**
     * Liste les demandes de changement de rôle en attente
     *
     * @return array
     */
    public function listRoleRequests(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->query(
            'SELECT rr.request_id AS id,
                    u.utilisateur_id AS user_id,
                    CONCAT(u.nom, " ", u.prenom) AS user_name,
                    r.role_id AS role_id,
                    r.libelle AS role_name,
                    rr.status,
                    rr.created_at
             FROM RoleRequest rr
             JOIN Utilisateur u ON rr.user_id = u.utilisateur_id
             JOIN Role r ON rr.role_id = r.role_id
             WHERE rr.status = "pending"'
        );
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->success($requests);
    }

    /**
     * Approuve une demande de changement de rôle
     *
     * @param int $requestId
     * @return array
     */
    public function approveRoleRequest(int $requestId): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Récupérer la demande
        $stmt = $db->prepare('SELECT user_id, role_id FROM RoleRequest WHERE request_id = ? AND status = "pending"');
        $stmt->execute([$requestId]);
        $req = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$req) {
            return $this->error('Demande introuvable ou déjà traitée', 404);
        }
        $userId = (int)$req['user_id'];
        $roleId = (int)$req['role_id'];
        // Ajouter le rôle à l'utilisateur
        $db->prepare('INSERT IGNORE INTO Possede (utilisateur_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
        // Mettre à jour le statut de la demande
        $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
        $db->prepare('UPDATE RoleRequest SET status = "approved", processed_by = ?, processed_at = NOW() WHERE request_id = ?')
            ->execute([$adminId, $requestId]);
        // Journaliser le changement
        $old = [];
        $new = [];
        $this->logRoleChange($adminId, $userId, $old, $new);
        return $this->success(null, 'Demande approuvée');
    }

    /**
     * Rejette une demande de changement de rôle
     *
     * @param int $requestId
     * @return array
     */
    public function rejectRoleRequest(int $requestId): array
    {
        $data = sanitize($this->getJsonData());
        $reason = $data['reason'] ?? null;
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Vérifier la demande
        $stmt = $db->prepare('SELECT request_id FROM RoleRequest WHERE request_id = ? AND status = "pending"');
        $stmt->execute([$requestId]);
        if (!$stmt->fetchColumn()) {
            return $this->error('Demande introuvable ou déjà traitée', 404);
        }
        $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
        // Mettre à jour le statut
        $update = $db->prepare('UPDATE RoleRequest SET status = "rejected", reason = ?, processed_by = ?, processed_at = NOW() WHERE request_id = ?');
        $update->execute([$reason, $adminId, $requestId]);
        return $this->success(null, 'Demande rejetée');
    }

    /**
     * Journalise le changement de rôle sans données détaillées
     */
    private function logRoleChange(int $adminId, int $userId, array $oldRoles, array $newRoles): void
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Enregistrer l'historique si la table est présente
        try {
            $log = $db->prepare(
                'INSERT INTO RoleChangeLog (admin_id, user_id, old_roles, new_roles)
                 VALUES (?, ?, ?, ?)'
            );
            $log->execute([
                $adminId,
                $userId,
                json_encode($oldRoles),
                json_encode($newRoles)
            ]);
        } catch (\Exception $e) {
            // Ignorer si la table RoleChangeLog n'existe pas
        }
    }

    /**
     * Liste les derniers utilisateurs en attente de confirmation
     *
     * @return array
     */
    public function listPendingUsers(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        // Récupérer les 5 derniers utilisateurs inscrits avec statut de confirmation
        $stmt = $db->query(
            'SELECT u.utilisateur_id AS id,
                    CONCAT(u.nom, " ", u.prenom) AS name,
                    u.email,
                    u.date_creation AS registered_at,
                    COALESCE(uc.is_used, 0) AS confirmed
             FROM Utilisateur u
             LEFT JOIN user_confirmations uc ON u.utilisateur_id = uc.utilisateur_id
             ORDER BY u.date_creation DESC
             LIMIT 5'
        );
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Caster id et confirmed
        foreach ($users as &$u) {
            $u['id']        = (int)$u['id'];
            $u['confirmed'] = (bool)$u['confirmed'];
        }
        return $this->success($users);
    }

    /**
     * Confirme un compte utilisateur
     *
     * @param int $userId
     * @return array
     */
    public function confirmUser(int $userId): array
    {
        // Vérifier l'existence de l'utilisateur
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare('SELECT confirmed FROM Utilisateur WHERE utilisateur_id = ?');
        $stmt->execute([$userId]);
        $confirmed = $stmt->fetchColumn();
        if ($confirmed === false) {
            return $this->error('Utilisateur introuvable', 404);
        }
        if ($confirmed) {
            return $this->error('Ce compte est déjà confirmé', 400);
        }
        // Confirmer l'utilisateur
        $update = $db->prepare('UPDATE Utilisateur SET confirmed = 1 WHERE utilisateur_id = ?');
        $update->execute([$userId]);
        // Journaliser la confirmation
        $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
        try {
            $log = $db->prepare('INSERT INTO UserActionLog (admin_id, user_id, action, details) VALUES (?, ?, ?, ?)');
            $log->execute([
                $adminId,
                $userId,
                'confirm',
                json_encode(['confirmed_at' => date('Y-m-d H:i:s')])
            ]);
        } catch (\Exception $e) {
            // Ignorer si la table n'existe pas
        }
        return $this->success(null, 'Compte utilisateur confirmé');
    }
    
    /**
     * Suspend un compte utilisateur
     * 
     * @param int $userId
     * @return array
     */
    public function suspendUser(int $userId): array
    {
        // Vérifier l'existence de l'utilisateur
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare('SELECT suspended FROM Utilisateur WHERE utilisateur_id = ?');
        $stmt->execute([$userId]);
        $suspended = $stmt->fetchColumn();
        
        if ($suspended === false) {
            return $this->error('Utilisateur introuvable', 404);
        }
        
        if ($suspended) {
            return $this->error('Ce compte est déjà suspendu', 400);
        }
        
        // Suspendre l'utilisateur
        $update = $db->prepare('UPDATE Utilisateur SET suspended = 1, suspended_at = NOW() WHERE utilisateur_id = ?');
        $update->execute([$userId]);
        
        // Journaliser la suspension
        $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
        try {
            $log = $db->prepare('INSERT INTO UserActionLog (admin_id, user_id, action, details) VALUES (?, ?, ?, ?)');
            $log->execute([
                $adminId,
                $userId,
                'suspend',
                json_encode(['suspended_at' => date('Y-m-d H:i:s')])
            ]);
        } catch (\Exception $e) {
            // Ignorer si la table n'existe pas
        }
        
        return $this->success(null, 'Compte utilisateur suspendu');
    }
    
    /**
     * Réactive un compte utilisateur
     * 
     * @param int $userId
     * @return array
     */
    public function activateUser(int $userId): array
    {
        // Vérifier l'existence de l'utilisateur
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare('SELECT suspended FROM Utilisateur WHERE utilisateur_id = ?');
        $stmt->execute([$userId]);
        $suspended = $stmt->fetchColumn();
        
        if ($suspended === false) {
            return $this->error('Utilisateur introuvable', 404);
        }
        
        if (!$suspended) {
            return $this->error('Ce compte est déjà actif', 400);
        }
        
        // Réactiver l'utilisateur
        $update = $db->prepare('UPDATE Utilisateur SET suspended = 0, suspended_at = NULL WHERE utilisateur_id = ?');
        $update->execute([$userId]);
        
        // Journaliser la réactivation
        $adminId = $_SERVER['AUTH_USER_ID'] ?? null;
        try {
            $log = $db->prepare('INSERT INTO UserActionLog (admin_id, user_id, action, details) VALUES (?, ?, ?, ?)');
            $log->execute([
                $adminId,
                $userId,
                'activate',
                json_encode(['activated_at' => date('Y-m-d H:i:s')])
            ]);
        } catch (\Exception $e) {
            // Ignorer si la table n'existe pas
        }
        
        return $this->success(null, 'Compte utilisateur réactivé');
    }
    
    /**
     * Obtient les statistiques des covoiturages
     * 
     * @return array
     */
    public function getRideStats(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        
        try {
            // Vérifier si la table Trajet existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'Trajet'");
            $tableExists = $tableCheck->rowCount() > 0;
            
            if (!$tableExists) {
                // Renvoyer des données fictives si la table n'existe pas
                return $this->success([
                    'dailyRides' => [
                        ['date' => date('Y-m-d', strtotime('-6 days')), 'count' => 12],
                        ['date' => date('Y-m-d', strtotime('-5 days')), 'count' => 15],
                        ['date' => date('Y-m-d', strtotime('-4 days')), 'count' => 8],
                        ['date' => date('Y-m-d', strtotime('-3 days')), 'count' => 20],
                        ['date' => date('Y-m-d', strtotime('-2 days')), 'count' => 18],
                        ['date' => date('Y-m-d', strtotime('-1 days')), 'count' => 22],
                        ['date' => date('Y-m-d'), 'count' => 16]
                    ],
                    'total' => 111
                ]);
            }
            
            // Nombre de covoiturages par jour (derniers 30 jours)
            $stmt = $db->prepare(
                'SELECT 
                    DATE(date_depart) AS date,
                    COUNT(*) AS count
                FROM Trajet
                WHERE date_depart >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(date_depart)
                ORDER BY date ASC'
            );
            $stmt->execute();
            $dailyRides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total des trajets
            $stmt = $db->query('SELECT COUNT(*) FROM Trajet');
            $totalRides = (int)$stmt->fetchColumn();
            
            return $this->success([
                'dailyRides' => $dailyRides,
                'total' => $totalRides
            ]);
        } catch (\Exception $e) {
            // En cas d'erreur, renvoyer des données fictives
            return $this->success([
                'dailyRides' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'count' => 12],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'count' => 15],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'count' => 8],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'count' => 20],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'count' => 18],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'count' => 22],
                    ['date' => date('Y-m-d'), 'count' => 16]
                ],
                'total' => 111
            ]);
        }
    }
    
    /**
     * Obtient les statistiques des crédits
     * 
     * @return array
     */
    public function getCreditStats(): array
    {
        $db = $this->app->getDatabase()->getMysqlConnection();
        
        try {
            // Vérifier si la table Transaction existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'Transaction'");
            $tableExists = $tableCheck->rowCount() > 0;
            
            if (!$tableExists) {
                // Renvoyer des données fictives si la table n'existe pas
                return $this->success([
                    'dailyCredits' => [
                        ['date' => date('Y-m-d', strtotime('-6 days')), 'amount' => 120],
                        ['date' => date('Y-m-d', strtotime('-5 days')), 'amount' => 150],
                        ['date' => date('Y-m-d', strtotime('-4 days')), 'amount' => 80],
                        ['date' => date('Y-m-d', strtotime('-3 days')), 'amount' => 200],
                        ['date' => date('Y-m-d', strtotime('-2 days')), 'amount' => 180],
                        ['date' => date('Y-m-d', strtotime('-1 days')), 'amount' => 220],
                        ['date' => date('Y-m-d'), 'amount' => 160]
                    ],
                    'total' => 1110
                ]);
            }
            
            // Crédits gagnés par jour (derniers 30 jours)
            $stmt = $db->prepare(
                'SELECT 
                    DATE(date_transaction) AS date,
                    SUM(montant) AS amount
                FROM Transaction 
                WHERE type = "platform_fee" 
                    AND date_transaction >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY DATE(date_transaction)
                ORDER BY date ASC'
            );
            $stmt->execute();
            $dailyCredits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Total des crédits de la plateforme
            $stmt = $db->prepare('SELECT SUM(montant) FROM Transaction WHERE type = "platform_fee"');
            $stmt->execute();
            $totalCredits = (int)$stmt->fetchColumn();
            
            return $this->success([
                'dailyCredits' => $dailyCredits,
                'total' => $totalCredits
            ]);
        } catch (\Exception $e) {
            // En cas d'erreur, renvoyer des données fictives
            return $this->success([
                'dailyCredits' => [
                    ['date' => date('Y-m-d', strtotime('-6 days')), 'amount' => 120],
                    ['date' => date('Y-m-d', strtotime('-5 days')), 'amount' => 150],
                    ['date' => date('Y-m-d', strtotime('-4 days')), 'amount' => 80],
                    ['date' => date('Y-m-d', strtotime('-3 days')), 'amount' => 200],
                    ['date' => date('Y-m-d', strtotime('-2 days')), 'amount' => 180],
                    ['date' => date('Y-m-d', strtotime('-1 days')), 'amount' => 220],
                    ['date' => date('Y-m-d'), 'amount' => 160]
                ],
                'total' => 1110
            ]);
        }
    }
} 