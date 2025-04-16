<?php

namespace App\Core;

/**
 * Classe Route
 * 
 * Cette classe représente une route dans l'application
 */
class Route
{
    /**
     * Méthode HTTP de la route
     *
     * @var string
     */
    private string $method;

    /**
     * Chemin de la route
     *
     * @var string
     */
    private string $path;

    /**
     * Handler de la route (Controller@method)
     *
     * @var string
     */
    private string $handler;

    /**
     * Liste des middlewares associés à la route
     *
     * @var array
     */
    private array $middlewares = [];

    /**
     * Constructeur
     *
     * @param string $method Méthode HTTP
     * @param string $path Chemin de la route
     * @param string $handler Handler de la route
     */
    public function __construct(string $method, string $path, string $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Obtient la méthode HTTP de la route
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Obtient le chemin de la route
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Obtient le handler de la route
     *
     * @return string
     */
    public function getHandler(): string
    {
        return $this->handler;
    }

    /**
     * Obtient les middlewares de la route
     *
     * @return array
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Ajoute un middleware à la route
     *
     * @param string $middleware Nom du middleware
     * @return self
     */
    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }
} 