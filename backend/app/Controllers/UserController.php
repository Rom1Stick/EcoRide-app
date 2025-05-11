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
                    pseudo
             FROM Utilisateur
             WHERE utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) {
            return $this->error('Utilisateur introuvable', 404);
        }

        // Nettoyer les données sensibles
        unset($user['mot_passe']);

        return $this->success(
            [
                'id'       => (int)$user['id'],
                'name'     => $user['name'],
                'email'    => $user['email'],
                'username' => $user['pseudo']
            ]
        );
    }
} 