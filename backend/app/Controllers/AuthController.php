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

        // Récupérer la connexion à la base de données
        $db = $this->app->getDatabase()->getMysqlConnection();

        // Démarrer la transaction
        try {
            $db->beginTransaction();

            // Vérifier si l'email existe déjà dans la table Utilisateur
            $stmt = $db->prepare('SELECT utilisateur_id FROM Utilisateur WHERE email = ?');
            $stmt->execute([$data['email']]);
            if ($stmt->fetchColumn()) {
                return $this->error('Cette adresse email est déjà utilisée', 400);
            }

            // Hasher le mot de passe
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            // Insérer l'utilisateur dans la table Utilisateur
            // On stocke le nom complet dans le champ nom et on laisse prenom vide
            $stmt = $db->prepare(
                'INSERT INTO Utilisateur (nom, prenom, email, mot_passe, date_creation)
                 VALUES (?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$data['name'], '', $data['email'], $hashedPassword]);

            // Récupérer l'ID utilisateur
            $userId = $db->lastInsertId();

            // Attribution du crédit de bienvenue
            $stmt = $db->prepare('INSERT INTO CreditBalance (utilisateur_id, solde) VALUES (?, ?)');
            $stmt->execute([$userId, 20]);

            // Enregistrer la transaction initiale
            $stmt = $db->prepare('SELECT type_id FROM TypeTransaction WHERE libelle = ?');
            $stmt->execute(['initial']);
            $typeId = $stmt->fetchColumn();
            $stmt = $db->prepare('INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, 20, $typeId, 'Crédit de bienvenue']);

            // Génération du token de confirmation
            $confirmationToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 24 * 3600);
            $stmt = $db->prepare('INSERT INTO user_confirmations (utilisateur_id, token, expires_at) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $confirmationToken, $expiresAt]);

            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->error('Erreur interne lors de l\'inscription', 500);
        }

        // Génération d'un token JWT
        $token = $this->generateJwtToken($userId);

        // Journalisation de l'inscription réussie
        $this->logAuthActivity($userId, 'register', true);

        // Retour des données, y compris le token de confirmation
        return $this->success(
            [
                'user' => [
                    'id' => $userId,
                    'name' => $data['name'],
                    'email' => $data['email']
                ],
                'token' => $token,
                'confirmation_token' => $confirmationToken
            ],
            'Inscription réussie, veuillez confirmer votre compte'
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

        // Vérifier le statut de confirmation du compte
        $confirmStmt = $db->prepare(
            'SELECT is_used, expires_at
             FROM user_confirmations
             WHERE utilisateur_id = ?
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $confirmStmt->execute([$user['id']]);
        $confirmation = $confirmStmt->fetch();
        if ($confirmation && !$confirmation['is_used']) {
            if ($confirmation['expires_at'] < date('Y-m-d H:i:s')) {
                return $this->error('Le token de confirmation a expiré', 403);
            }
            return $this->error('Compte non confirmé', 403);
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
        $db = $this->app->getDatabase()->getMysqlConnection();

        // Créer la table si elle n'existe pas
        $db->exec(
            '
            CREATE TABLE IF NOT EXISTS auth_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                success TINYINT(1) NOT NULL,
                email VARCHAR(255),
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
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

    /**
     * Confirme un compte utilisateur via un jeton
     *
     * @return array
     */
    public function confirm(): array
    {
        // Récupérer le jeton depuis les paramètres GET
        $token = $_GET['token'] ?? null;
        if (!$token) {
            return $this->error('Jeton manquant', 400);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        // Rechercher le jeton de confirmation
        $stmt = $db->prepare('SELECT id, utilisateur_id, expires_at, is_used FROM user_confirmations WHERE token = ?');
        $stmt->execute([$token]);
        $confirmation = $stmt->fetch();

        if (!$confirmation) {
            return $this->error('Jeton invalide', 404);
        }
        if ($confirmation['is_used']) {
            return $this->error('Jeton déjà utilisé', 400);
        }
        if ($confirmation['expires_at'] < date('Y-m-d H:i:s')) {
            return $this->error('Jeton expiré', 410);
        }

        // Marquer le jeton comme utilisé
        $update = $db->prepare('UPDATE user_confirmations SET is_used = 1 WHERE id = ?');
        $update->execute([$confirmation['id']]);

        return $this->success(null, 'Compte confirmé');
    }
}
