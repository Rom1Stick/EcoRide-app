<?php

namespace App\Middlewares;

use App\Core\Security;

/**
 * Middleware d'authentification
 */
class AuthMiddleware
{
    /**
     * Traite la requête
     *
     * @return bool|array
     */
    public function handle()
    {
        // Récupérer le token JWT depuis le cookie ou l'en-tête Authorization
        $token = $_COOKIE['auth_token'] ?? null;
        if (!$token) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
            if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }
        if (!$token) {
            http_response_code(401);
            return [
                'error' => true,
                'message' => 'Accès non autorisé. Token manquant.'
            ];
        }

        // Débogage: Enregistrer dans un fichier que le middleware est exécuté
        $logPath = dirname(__DIR__, 2) . '/logs/auth_debug.log';
        file_put_contents($logPath, "AuthMiddleware exécuté à " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        file_put_contents($logPath, "Token trouvé: " . $token . "\n", FILE_APPEND);

        // Limiter les tentatives d'authentification pour prévenir les attaques par force brute
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!Security::rateLimit("auth_$ip", 20, 60)) {
            http_response_code(429);
            return [
                'error' => true,
                'message' => 'Trop de tentatives d\'authentification. Veuillez réessayer plus tard.'
            ];
        }

        // Vérifier et décoder le token JWT
        $payload = $this->decodeJwt($token);

        if ($payload === false) {
            file_put_contents($logPath, "Échec de décodage du token JWT\n", FILE_APPEND);
            http_response_code(401);
            return [
                'error' => true,
                'message' => 'Token invalide ou expiré.'
            ];
        }

        // Débogage: Enregistrer le payload JWT décodé
        file_put_contents($logPath, "Payload décodé: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        // Stocker l'ID de l'utilisateur pour y accéder dans les contrôleurs
        $_SERVER['AUTH_USER_ID'] = $payload['sub'];
        
        // Stocker les rôles de l'utilisateur si présents dans le payload
        if (isset($payload['roles'])) {
            $_SERVER['AUTH_USER_ROLES'] = $payload['roles'];
            // Pour maintenir la compatibilité avec le code existant, on stocke aussi le premier rôle
            if (!empty($payload['roles'])) {
                $_SERVER['AUTH_USER_ROLE'] = $payload['roles'][0];
            }
            file_put_contents($logPath, "Rôles stockés: " . json_encode($_SERVER['AUTH_USER_ROLES']) . "\n", FILE_APPEND);
        } elseif (isset($payload['role'])) {
            // Rétrocompatibilité avec l'ancien format
            $_SERVER['AUTH_USER_ROLE'] = $payload['role'];
            $_SERVER['AUTH_USER_ROLES'] = [$payload['role']];
            file_put_contents($logPath, "Rôle stocké (ancien format): " . $_SERVER['AUTH_USER_ROLE'] . "\n", FILE_APPEND);
        }

        // Débogage: Enregistrer les variables importantes de $_SERVER
        file_put_contents($logPath, "AUTH_USER_ID: " . ($_SERVER['AUTH_USER_ID'] ?? 'non défini') . "\n", FILE_APPEND);
        file_put_contents($logPath, "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'non défini') . "\n", FILE_APPEND);

        // Contrôle d'accès par rôle pour les routes /api/admin
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (strpos($uri, '/api/admin') === 0) {
            $hasAdminRole = false;
            
            // Vérification du rôle dans le tableau de rôles
            if (isset($_SERVER['AUTH_USER_ROLES']) && is_array($_SERVER['AUTH_USER_ROLES'])) {
                $hasAdminRole = in_array('admin', $_SERVER['AUTH_USER_ROLES']);
            }
            
            // Vérification de l'ancien format par rétrocompatibilité
            if (!$hasAdminRole && ($_SERVER['AUTH_USER_ROLE'] ?? '') === 'admin') {
                $hasAdminRole = true;
            }
            
            if (!$hasAdminRole) {
                file_put_contents($logPath, "Accès refusé à la zone admin\n", FILE_APPEND);
            http_response_code(403);
            return [
                'error'   => true,
                'message' => 'Accès réservé aux administrateurs.'
            ];
        }
        }
        
        file_put_contents($logPath, "Authentification réussie\n\n", FILE_APPEND);
        return true;
    }

    /**
     * Décode un token JWT
     *
     * @param  string $token
     * @return array|false
     */
    private function decodeJwt(string $token)
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        // Décoder le payload
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64Payload)), true);

        if (!$payload) {
            return false;
        }

        // Vérifier l'expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        // Vérifier la signature
        $signature = hash_hmac(
            'sha256',
            $base64Header . '.' . $base64Payload,
            env('JWT_SECRET', ''),
            true
        );

        $base64SignatureCalculated = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if (!hash_equals($base64Signature, $base64SignatureCalculated)) {
            return false;
        }

        return $payload;
    }
}
