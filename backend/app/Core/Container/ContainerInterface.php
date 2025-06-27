<?php

namespace App\Core\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Interface du Container d'Injection de Dépendances
 * 
 * Conforme à PSR-11 pour la compatibilité avec l'écosystème PHP
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Enregistre un service dans le container
     *
     * @param string $id Identifiant unique du service
     * @param mixed $concrete Implémentation du service (classe, factory, instance)
     * @param bool $singleton Si true, retourne toujours la même instance
     * @return void
     */
    public function bind(string $id, $concrete, bool $singleton = false): void;

    /**
     * Enregistre un service comme singleton
     *
     * @param string $id Identifiant unique du service
     * @param mixed $concrete Implémentation du service
     * @return void
     */
    public function singleton(string $id, $concrete): void;

    /**
     * Enregistre une instance déjà créée
     *
     * @param string $id Identifiant unique du service
     * @param mixed $instance Instance du service
     * @return void
     */
    public function instance(string $id, $instance): void;

    /**
     * Crée une nouvelle instance à chaque appel
     *
     * @param string $id Identifiant du service
     * @param array $parameters Paramètres supplémentaires
     * @return mixed
     */
    public function make(string $id, array $parameters = []);

    /**
     * Résout automatiquement les dépendances d'une classe
     *
     * @param string $className Nom de la classe à instancier
     * @param array $parameters Paramètres supplémentaires
     * @return mixed
     */
    public function resolve(string $className, array $parameters = []);

    /**
     * Enregistre un alias pour un service
     *
     * @param string $alias Nom de l'alias
     * @param string $id Identifiant du service original
     * @return void
     */
    public function alias(string $alias, string $id): void;

    /**
     * Vérifie si un service peut être résolu
     *
     * @param string $id Identifiant du service
     * @return bool
     */
    public function canResolve(string $id): bool;

    /**
     * Supprime un service du container
     *
     * @param string $id Identifiant du service
     * @return void
     */
    public function forget(string $id): void;

    /**
     * Obtient tous les services enregistrés
     *
     * @return array
     */
    public function getBindings(): array;
} 