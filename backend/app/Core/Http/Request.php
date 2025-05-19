<?php

namespace App\Core\Http;

/**
 * Classe de gestion des requêtes HTTP
 */
class Request
{
    /**
     * @var array Paramètres de la requête GET
     */
    private $queryParams;

    /**
     * @var array Paramètres de la requête POST
     */
    private $postParams;

    /**
     * @var array Paramètres des fichiers uploadés
     */
    private $files;

    /**
     * @var array En-têtes de la requête
     */
    private $headers;

    /**
     * @var string Méthode HTTP
     */
    private $method;

    /**
     * @var string URI de la requête
     */
    private $uri;

    /**
     * @var array|null Corps de la requête JSON
     */
    private $jsonBody;

    /**
     * Constructeur de la requête
     */
    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->postParams = $_POST;
        $this->files = $_FILES;
        $this->headers = $this->getRequestHeaders();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->uri = $_SERVER['REQUEST_URI'] ?? '/';
        $this->parseJsonBody();
    }

    /**
     * Récupère les en-têtes de la requête
     *
     * @return array En-têtes de la requête
     */
    private function getRequestHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headerKey = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerKey] = $value;
            }
        }
        return $headers;
    }

    /**
     * Parse le corps de la requête au format JSON
     */
    private function parseJsonBody(): void
    {
        if ($this->getHeader('Content-Type') === 'application/json') {
            $body = file_get_contents('php://input');
            $this->jsonBody = json_decode($body, true);
        }
    }

    /**
     * Récupère un paramètre de la requête GET
     *
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed Valeur du paramètre ou valeur par défaut
     */
    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }

    /**
     * Récupère un paramètre de la requête POST
     *
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed Valeur du paramètre ou valeur par défaut
     */
    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->postParams;
        }
        return $this->postParams[$key] ?? $default;
    }

    /**
     * Récupère un paramètre du corps JSON
     *
     * @param string $key Clé du paramètre
     * @param mixed $default Valeur par défaut si le paramètre n'existe pas
     * @return mixed Valeur du paramètre ou valeur par défaut
     */
    public function json(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->jsonBody;
        }
        return $this->jsonBody[$key] ?? $default;
    }

    /**
     * Récupère un fichier uploadé
     *
     * @param string $key Nom du fichier
     * @return array|null Informations sur le fichier
     */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Récupère un en-tête de la requête
     *
     * @param string $key Nom de l'en-tête
     * @return string|null Valeur de l'en-tête
     */
    public function getHeader(string $key)
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Vérifie si la requête est une requête AJAX
     *
     * @return bool True si c'est une requête AJAX
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Récupère la méthode HTTP
     *
     * @return string Méthode HTTP
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Récupère l'URI de la requête
     *
     * @return string URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Récupère l'adresse IP du client
     *
     * @return string Adresse IP
     */
    public function getIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * Récupère l'utilisateur authentifié
     *
     * @return mixed Utilisateur authentifié
     */
    public function user()
    {
        return $_SESSION['user'] ?? null;
    }
} 