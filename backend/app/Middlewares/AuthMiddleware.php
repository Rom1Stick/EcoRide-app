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
        // Récupérer le token JWT depuis l'en-tête Authorization ou le cookie
        $token = null;
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } elseif (!empty($_COOKIE['auth_token'])) {
            $token = $_COOKIE['auth_token'];
        }
        if (!$token) {
            http_response_code(401);
            return [
                'error' => true,
                'message' => 'Accès non autorisé. Token manquant.'
            ];
        }

        // Limiter les tentatives d'authentification pour prévenir les attaques par force brute
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (!Security::rateLimit("auth_$ip", 10, 60)) {
            http_response_code(429);
            return [
                'error' => true,
                'message' => 'Trop de tentatives d\'authentification. Veuillez réessayer plus tard.'
            ];
        }

        // Vérifier et décoder le token JWT
        $payload = $this->decodeJwt($token);

        if ($payload === false) {
            http_response_code(401);
            return [
                'error' => true,
                'message' => 'Token invalide ou expiré.'
            ];
        }

        // Stocker l'ID de l'utilisateur pour y accéder dans les contrôleurs
        $_SERVER['AUTH_USER_ID'] = $payload['sub'];
        // Stocker le rôle de l'utilisateur si présent dans le payload
        if (isset($payload['role'])) {
            $_SERVER['AUTH_USER_ROLE'] = $payload['role'];
        }

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
