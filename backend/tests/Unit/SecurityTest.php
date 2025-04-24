<?php

namespace Tests\Unit;

use App\Core\Security;
use Tests\TestCase;

/**
 * Test unitaire pour la classe Security
 */
class SecurityTest extends TestCase
{
    /**
     * Teste la fonction sanitize sur une chaîne simple
     */
    public function testSanitizeString(): void
    {
        $input = 'alert("XSS")Hello';
        $expected = 'Hello';
        
        $this->assertEquals($expected, Security::sanitize($input));
    }
    
    /**
     * Teste la fonction sanitize sur une chaîne avec des balises HTML
     */
    public function testSanitizeHtml(): void
    {
        $input = '<script>alert("XSS")</script><p>Hello</p>';
        $expected = 'Hello';
        
        $this->assertEquals($expected, Security::sanitize($input));
    }
    
    /**
     * Teste la fonction sanitize sur un tableau simple
     */
    public function testSanitizeArray(): void
    {
        $input = [
            'name' => 'John',
            'email' => 'john@example.comalert(1)',
            'nested' => [
                'key' => '<script>document.cookie</script>',
                'value' => '<p>Test</p>'
            ]
        ];
        
        $expected = [
            'name' => 'John',
            'email' => 'john@example.com',
            'nested' => [
                'key' => 'document.cookie',
                'value' => 'Test'
            ]
        ];
        
        $this->assertEquals($expected, Security::sanitize($input));
    }
    
    /**
     * Teste la génération et la vérification de tokens CSRF
     */
    public function testCsrfTokenGeneration(): void
    {
        // Démarrer la session si ce n'est pas déjà fait
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Générer un token
        $token = Security::generateCsrfToken();
        
        // Vérifier que le token a été généré
        $this->assertNotEmpty($token);
        
        // Vérifier que le token est valide
        $this->assertTrue(Security::verifyCsrfToken($token));
        
        // Vérifier qu'un token invalide est rejeté
        $this->assertFalse(Security::verifyCsrfToken('invalid_token'));
    }
    
    /**
     * Teste la validation des données
     */
    public function testValidation(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'age' => '25'
        ];
        
        $rules = [
            'name' => 'required|max:50',
            'email' => 'required|email',
            'age' => 'required|numeric'
        ];
        
        $errors = Security::validate($data, $rules);
        
        // Vérifier qu'il y a une erreur pour l'email
        $this->assertArrayHasKey('email', $errors);
        
        // Vérifier qu'il n'y a pas d'erreur pour le nom et l'âge
        $this->assertArrayNotHasKey('name', $errors);
        $this->assertArrayNotHasKey('age', $errors);
    }
} 