<?php

namespace App\Core;

/**
 * Classe Application principale
 *
 * Cette classe initialise et orchestre les différents composants
 * de l'application (routeur, base de données, etc.)
 */
class Application
{
    /**
     * Environnement d'exécution (development, production, testing)
     * @var string
     */
    public static $env = 'development';

    /**
     * Instance du routeur
     *
     * @var Router
     */
    private Router $router;

    /**
     * Instance de connexion à la base de données
     *
     * @var Database
     */
    private Database $database;

    /**
     * Constructeur
     */
    public function __construct()
    {
        // Initialiser le routeur
        $this->router = new Router();

        // Initialiser la connexion à la base de données
        $this->database = new Database();
    }

    /**
     * Obtient l'instance du routeur
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Obtient l'instance de la base de données
     *
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * Exécute l'application, traite la requête et envoie la réponse
     *
     * @return void
     */
    public function run(): void
    {
        // Vérifier le verbe HTTP
        $method = $_SERVER['REQUEST_METHOD'];

        // Obtenir l'URL de la requête
        $uri = $_SERVER['REQUEST_URI'];

        // Traiter la requête avec le routeur
        $response = $this->router->dispatch($method, $uri);

        // Envoyer la réponse
        $this->sendResponse($response);
    }

    /**
     * Envoie la réponse au client
     *
     * @param  mixed $response Données de réponse
     * @return void
     */
    private function sendResponse($response): void
    {
        // Définir l'en-tête Content-Type
        header('Content-Type: application/json');

        // Si la réponse n'est pas déjà une chaîne JSON, la convertir
        if (!is_string($response)) {
            $response = json_encode($response);
        }

        // Envoyer la réponse
        echo $response;
    }
}
