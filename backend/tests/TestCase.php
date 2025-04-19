<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Classe de base pour tous les tests
 */
class TestCase extends BaseTestCase
{
    /**
     * Configuration avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Définir le chemin de base constant s'il n'est pas défini
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', dirname(__DIR__));
        }
        
        // Charger les variables d'environnement de test
        $this->loadTestEnv();
    }
    
    /**
     * Charge les variables d'environnement pour les tests
     */
    protected function loadTestEnv(): void
    {
        // Variables d'environnement pour les tests
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['APP_DEBUG'] = 'true';
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_HOST'] = 'mysql';
        $_ENV['DB_PORT'] = '3306';
        $_ENV['DB_DATABASE'] = 'ecoride_test';
        $_ENV['DB_USERNAME'] = 'ecorider';
        $_ENV['DB_PASSWORD'] = 'securepass';
        $_ENV['JWT_SECRET'] = 'test_secret_key';
        
        // Ajouter ces variables à $_SERVER et à getenv() également
        foreach ($_ENV as $key => $value) {
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    /**
     * Simule une requête HTTP
     */
    protected function mockRequest(string $method, string $uri, array $data = []): void
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;
        
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            // Simuler des données POST ou JSON
            $_POST = $data;
            
            // Pour les API JSON
            $_SERVER['CONTENT_TYPE'] = 'application/json';
            $GLOBALS['HTTP_RAW_POST_DATA'] = json_encode($data);
        } else {
            // Simuler des données GET
            $_GET = $data;
        }
    }
    
    /**
     * Réinitialise l'environnement de requête
     */
    protected function resetRequest(): void
    {
        $_GET = [];
        $_POST = [];
        $_REQUEST = [];
        $_SERVER['REQUEST_METHOD'] = null;
        $_SERVER['REQUEST_URI'] = null;
        $_SERVER['CONTENT_TYPE'] = null;
        
        if (isset($GLOBALS['HTTP_RAW_POST_DATA'])) {
            unset($GLOBALS['HTTP_RAW_POST_DATA']);
        }
    }
    
    /**
     * Assainit l'environnement après chaque test
     */
    protected function tearDown(): void
    {
        $this->resetRequest();
        parent::tearDown();
    }
} 