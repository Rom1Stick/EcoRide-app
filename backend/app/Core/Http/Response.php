<?php

namespace App\Core\Http;

/**
 * Classe de gestion des réponses HTTP
 */
class Response
{
    /**
     * @var array En-têtes de la réponse
     */
    private $headers = [];

    /**
     * @var int Code de statut HTTP
     */
    private $statusCode = 200;

    /**
     * @var string Contenu de la réponse
     */
    private $content = '';

    /**
     * Ajoute un en-tête à la réponse
     *
     * @param string $name Nom de l'en-tête
     * @param string $value Valeur de l'en-tête
     * @return self
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Définit le code de statut HTTP
     *
     * @param int $statusCode Code de statut HTTP
     * @return self
     */
    public function status(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Définit le contenu de la réponse
     *
     * @param string $content Contenu de la réponse
     * @return self
     */
    public function content(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Envoi une réponse au format JSON
     *
     * @param mixed $data Données à encoder en JSON
     * @param int $statusCode Code de statut HTTP
     * @return self
     */
    public function json($data, int $statusCode = 200): self
    {
        $this->header('Content-Type', 'application/json');
        $this->status($statusCode);
        $this->content(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $this;
    }

    /**
     * Envoie la réponse au client
     */
    public function send(): void
    {
        // Définition du code de statut HTTP
        http_response_code($this->statusCode);

        // Envoi des en-têtes HTTP
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Envoi du contenu de la réponse
        echo $this->content;
        exit;
    }

    /**
     * Effectue une redirection vers l'URL spécifiée
     *
     * @param string $url URL de redirection
     * @param int $statusCode Code de statut HTTP (301 ou 302)
     * @return self
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->header('Location', $url);
        $this->status($statusCode);
        return $this;
    }
} 