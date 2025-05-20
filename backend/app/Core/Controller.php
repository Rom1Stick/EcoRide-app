<?php

namespace App\Core;

/**
 * Classe de base pour tous les contrôleurs
 */
abstract class Controller
{
    /**
     * Instance de l'application
     * @var Application
     */
    protected $app;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Récupérer l'instance globale de l'application
        global $app;
        $this->app = $app;
    }

    /**
     * Récupère les données JSON de la requête
     *
     * @return array
     */
    protected function getJsonData(): array
    {
        $rawData = file_get_contents('php://input');
        $data = json_decode($rawData, true) ?? [];
        return $data;
    }

    /**
     * Retourne une réponse de succès au format JSON
     *
     * @param  mixed  $data    Données à renvoyer
     * @param  string $message Message de succès
     * @param  int    $status  Code de statut HTTP
     * @return array
     */
    protected function success($data = null, string $message = 'Succès', int $status = 200): array
    {
        http_response_code($status);
        return [
            'error'   => false,
            'message' => $message,
            'data'    => $data
        ];
    }

    /**
     * Retourne une réponse d'erreur au format JSON
     *
     * @param  mixed $error  Message d'erreur ou tableau d'erreurs
     * @param  int   $status Code de statut HTTP
     * @return array
     */
    protected function error($error = 'Une erreur est survenue', int $status = 400): array
    {
        http_response_code($status);
        return [
            'error'   => true,
            'message' => $error
        ];
    }
} 