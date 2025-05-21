<?php

namespace App\Controllers;

use App\Core\Application;

/**
 * Classe Controller de base
 *
 * Cette classe sert de base pour tous les contrôleurs de l'application
 */
abstract class Controller
{
    /**
     * Instance de l'application
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Constructeur
     */
    public function __construct()
    {
        global $app;
        $this->app = $app;
    }

    /**
     * Renvoie une réponse JSON
     *
     * @param  mixed $data       Données à
     *                           renvoyer
     * @param  int   $statusCode Code de statut HTTP
     * @return array
     */
    protected function json($data, int $statusCode = 200): array
    {
        // S'assurer qu'aucun contenu n'a été envoyé avant
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code($statusCode);
        }
        return $data;
    }

    /**
     * Renvoie une réponse d'erreur
     *
     * @param  string|array $message    Message d'erreur ou tableau de données
     *                                  d'erreur
     * @param  int          $statusCode Code de statut HTTP
     * @return array
     */
    protected function error($message, int $statusCode = 400): array
    {
        if (is_array($message)) {
            $response = ['error' => true] + $message;
            return $this->json($response, $statusCode);
        }

        return $this->json(
            [
            'error' => true,
            'message' => $message
            ],
            $statusCode
        );
    }

    /**
     * Renvoie une réponse de succès
     *
     * @param  mixed  $data    Données
     *                         à
     *                         renvoyer
     * @param  string $message Message de succès
     * @return array
     */
    protected function success($data = null, string $message = 'Opération réussie'): array
    {
        $response = [
            'error' => false,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->json($response);
    }

    /**
     * Obtient les données de la requête JSON
     *
     * @return array
     */
    protected function getJsonData(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

    /**
     * Obtient un paramètre de requête
     *
     * @param  string $name    Nom du
     *                         paramètre
     * @param  mixed  $default Valeur par
     *                         défaut
     * @return mixed
     */
    protected function getParam(string $name, $default = null)
    {
        return $_GET[$name] ?? $default;
    }
}
