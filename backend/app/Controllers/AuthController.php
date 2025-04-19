<?php

namespace App\Controllers;

use App\Core\Security;

/**
 * Contrôleur d'authentification
 *
 * Gère les fonctionnalités d'authentification (inscription, connexion, déconnexion)
 */
class AuthController extends Controller
{
    /**
     * Inscrit un nouvel utilisateur
     *
     * @return array
     */
    public function register(): array
    {
        // Récupérer et nettoyer les données de la requête
        $data = sanitize($this->getJsonData());

        // Valider les données
        $errors = validate(
            $data,
            [
                'email' => 'required|email|max:255',
                'password' => 'required|min:8|max:255',
                'name' => 'required|max:255'
            ]
        );

        if (!empty($errors)) {
            return $this->error(
                [
                    'message' => 'Données invalides',
                    'errors' => $errors
                ],
                422
            );
        }

        // Vérifier si l'email existe déjà
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);

        if ($stmt->fetchColumn()) {
            return $this->error('Cette adresse email est déjà utilisée', 400);
        }

        // Hasher le mot de passe avec un coût plus élevé pour une meilleure sécurité
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        // Insérer l'utilisateur en base de données
        $stmt = $db->prepare('INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute(
            [
                $data['name'],
                $data['email'],
                $hashedPassword
            ]
        );

        // Génération d'un token JWT
        $userId = $db->lastInsertId();
        $token = $this->generateJwtToken($userId);

        // Enregistrement de l'IP et de l'agent utilisateur pour suivi de sécurité
        $this->logAuthActivity($userId, 'register', true);

        // Retourner les données de l'utilisateur et le token
        return $this->success(
            [
                'user' => [
                    'id' => $userId,
                    'name' => $data['name'],
                    'email' => $data['email']
                ],
                'token' => $token
            ],
            'Inscription réussie'
        );
    }

    /**
     * Connecte un utilisateur
     *
     * @return array
     */
    public function login(): array
    {
        // Limiter les tentatives de connexion pour prévenir les attaques par force brute
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!Security::rateLimit("login_$ip", 5, 300)) {
            return $this->error('Trop de tentatives de connexion. Veuillez réessayer plus tard.', 429);
        }

        // Récupérer les données de la requête et les nettoyer
        $data = sanitize($this->getJsonData());

        // Valider les données
        $errors = validate(
            $data,
            [
                'email' => 'required|email',
                'password' => 'required'
            ]
        );

        if (!empty($errors)) {
            return $this->error(
                [
                    'message' => 'Données invalides',
                    'errors' => $errors
                ],
                422
            );
        }

        // Récupérer l'utilisateur
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare('SELECT id, name, email, password FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);

        $user = $stmt->fetch();

        // Vérifier si l'utilisateur existe
        if (!$user) {
            // Enregistrer la tentative échouée
            $this->logAuthActivity(0, 'login', false, $data['email']);
            return $this->error('Email ou mot de passe incorrect', 401);
        }

        // Vérifier le mot de passe
        if (!password_verify($data['password'], $user['password'])) {
            // Enregistrer la tentative échouée
            $this->logAuthActivity($user['id'], 'login', false);
            return $this->error('Email ou mot de passe incorrect', 401);
        }

        // Vérifier si le hash du mot de passe doit être mis à jour (changement des paramètres de hachage)
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $stmt->execute([$newHash, $user['id']]);
        }

        // Générer un token JWT
        $token = $this->generateJwtToken($user['id']);

        // Enregistrer la tentative réussie
        $this->logAuthActivity($user['id'], 'login', true);

        // Retourner les données de l'utilisateur et le token
        return $this->success(
            [
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email']
                ],
                'token' => $token
            ],
            'Connexion réussie'
        );
    }

    /**
     * Déconnecte un utilisateur
     *
     * @return array
     */
    public function logout(): array
    {
        $userId = $_SERVER['AUTH_USER_ID'] ?? null;

        if ($userId) {
            // Enregistrer la déconnexion
            $this->logAuthActivity($userId, 'logout', true);
        }

        return $this->success(null, 'Déconnexion réussie');
    }

    /**
     * Rafraîchit le token d'un utilisateur
     *
     * @return array
     */
    public function refresh(): array
    {
        // Récupérer l'utilisateur connecté à partir du middleware d'authentification
        $userId = $_SERVER['AUTH_USER_ID'] ?? null;

        if (!$userId) {
            return $this->error('Non authentifié', 401);
        }

        // Générer un nouveau token
        $token = $this->generateJwtToken($userId);

        return $this->success(
            [
                'token' => $token
            ],
            'Token rafraîchi avec succès'
        );
    }

    /**
     * Génère un token JWT
     *
     * @param  int $userId ID de l'utilisateur
     * @return string
     */
    private function generateJwtToken(int $userId): string
    {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $issuedAt = time();
        $expiresAt = $issuedAt + (int) env('JWT_EXPIRATION', 3600);

        $payload = [
            'sub' => $userId,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'jti' => bin2hex(random_bytes(16)) // ID unique pour le token
        ];

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, env('JWT_SECRET', ''), true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    /**
     * Enregistre une activité d'authentification
     *
     * @param  int         $userId  ID de l'utilisateur
     * @param  string      $action  Action effectuée (login, register, logout)
     * @param  bool        $success Indique si l'action a réussi
     * @param  string|null $email   Email utilisé (pour les tentatives échouées)
     * @return void
     */
    private function logAuthActivity(int $userId, string $action, bool $success, ?string $email = null): void
    {
        $db = $this->app->getDatabase()->getSqliteConnection();

        // Créer la table si elle n'existe pas
        $db->exec(
            '
            CREATE TABLE IF NOT EXISTS auth_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                action TEXT NOT NULL,
                success INTEGER NOT NULL,
                email TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        '
        );

        $stmt = $db->prepare(
            '
            INSERT INTO auth_logs (user_id, action, success, email, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        '
        );

        $stmt->execute(
            [
                $userId,
                $action,
                $success ? 1 : 0,
                $email,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]
        );
    }
}
