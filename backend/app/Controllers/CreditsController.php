<?php

namespace App\Controllers;

use App\Core\Security;
use App\Services\CreditService;
use PDO;

class CreditsController extends Controller
{
    /**
     * Consulter le solde actuel
     */
    public function balance(): array
    {
        // Récupération du token et de l'ID utilisateur
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $token = $_COOKIE['auth_token'] ?? null;
        
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        // Si on a le token, on extrait l'ID utilisateur directement
        if ($token) {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if ($payload && isset($payload['sub'])) {
                    $userId = (int) $payload['sub'];
                }
            }
        }
        
        // Si aucun ID utilisateur valide, on renvoie une erreur
        if ($userId <= 0) {
            return $this->error('Utilisateur non identifié', 401);
        }
        
        $db = $this->app->getDatabase()->getMysqlConnection();
        $service = new CreditService($db);
        $balance = $service->getBalance($userId);
        
        return $this->success(['balance' => $balance]);
    }

    /**
     * Historique des transactions (pagination)
     */
    public function transactions(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        
        // Même logique pour extraire l'ID du token si nécessaire
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if ($token) {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if ($payload && isset($payload['sub'])) {
                    $userId = (int) $payload['sub'];
                }
            }
        }
        
        if ($userId <= 0) {
            return $this->error('Utilisateur non identifié', 401);
        }
        
        $page = max(1, (int) ($this->getParam('page', 1)));
        $perPage = max(1, (int) ($this->getParam('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        $db = $this->app->getDatabase()->getMysqlConnection();
        // Compte total
        $countStmt = $db->prepare('SELECT COUNT(*) FROM CreditTransaction WHERE utilisateur_id = ?');
        $countStmt->execute([$userId]);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = (int) ceil($total / $perPage);

        // Récupère les transactions
        $stmt = $db->prepare(
            'SELECT ct.transaction_id AS id, ct.montant AS amount, tt.libelle AS type, ct.date_transaction AS date, ct.description 
             FROM CreditTransaction ct 
             JOIN TypeTransaction tt ON ct.type_id = tt.type_id 
             WHERE ct.utilisateur_id = ? 
             ORDER BY ct.date_transaction DESC 
             LIMIT ? OFFSET ?'
        );
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        $stmt->bindParam(2, $perPage, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success([
            'transactions' => $transactions,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages
            ]
        ]);
    }

    /**
     * Transfert de crédits (admin uniquement)
     */
    public function transfer(): array
    {
        // Vérification rôle
        $isAdmin = false;
        
        // Extraire le rôle directement du token
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if ($token) {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if ($payload && isset($payload['role'])) {
                    $isAdmin = $payload['role'] === 'admin';
                }
            }
        }
        
        if (!$isAdmin) {
            return $this->error('Accès réservé aux administrateurs', 403);
        }

        $data = sanitize($this->getJsonData());
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $amount = isset($data['amount']) ? (float) $data['amount'] : null;
        $type = $data['type'] ?? 'autre';
        $description = $data['description'] ?? '';

        if (!$userId || $amount === null) {
            return $this->error('user_id et amount sont obligatoires', 400);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $service = new CreditService($db);

        try {
            if ($amount >= 0) {
                $service->creditAccount($userId, $amount, $type, $description);
            } else {
                $service->debitAccount($userId, abs($amount), $type, $description);
            }
            return $this->success(null, 'Transfert effectué avec succès');
        } catch (\Exception $e) {
            return $this->error('Erreur lors du transfert : ' . $e->getMessage(), 500);
        }
    }

    /**
     * Prix estimé d'un trajet
     */
    public function pricing(): array
    {
        $distance = (float) $this->getParam('distance', 0);
        $db = $this->app->getDatabase()->getMysqlConnection();
        $service = new CreditService($db);
        $pricing = $service->calculateTripCost($distance);
        return $this->success($pricing);
    }

    /**
     * Alertes pour le dashboard admin
     */
    public function alerts(): array
    {
        // Vérification du rôle admin comme dans transfer()
        $isAdmin = false;
        
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        
        if ($token) {
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if ($payload && isset($payload['role'])) {
                    $isAdmin = $payload['role'] === 'admin';
                }
            }
        }
        
        if (!$isAdmin) {
            return $this->error('Accès réservé aux administrateurs', 403);
        }
        
        $db = $this->app->getDatabase()->getMysqlConnection();
        $threshold = (float)config('credits.alert_threshold', 0);
        $stmt = $db->prepare('SELECT utilisateur_id AS user_id, solde FROM CreditBalance WHERE solde < ?');
        $stmt->execute([$threshold]);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->success(['alerts' => $alerts]);
    }
} 