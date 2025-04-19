<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Classe de test d'exemple pour démontrer le fonctionnement des tests unitaires
 */
class ExampleTest extends TestCase
{
    /**
     * Configuration initiale avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Préparation de l'environnement de test
    }

    /**
     * Test basique pour vérifier que l'environnement de test fonctionne
     */
    public function testEnvironmentWorks(): void
    {
        $this->assertTrue(true);
        $this->assertFalse(false);
        $this->assertEquals(1, 1);
    }

    /**
     * Test d'exemple pour démontrer les assertions plus complexes
     */
    public function testArrayOperations(): void
    {
        $array = ['foo' => 'bar'];
        
        // Vérifie que la clé existe
        $this->assertArrayHasKey('foo', $array);
        
        // Vérifie la valeur
        $this->assertEquals('bar', $array['foo']);
        
        // Vérifie la taille
        $this->assertCount(1, $array);
    }
    
    /**
     * Nettoyage après chaque test
     */
    protected function tearDown(): void
    {
        // Nettoyage de l'environnement de test
        parent::tearDown();
    }
} 