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
     * Teste la sanitization des données
     */
    public function testSanitizeString(): void
    {
        $input = '<script>alert("XSS")</script>Hello';
        $expected = 'Hello';
        
        $sanitized = Security::sanitize($input);
        
        $this->assertEquals($expected, $sanitized);
    }
    
    /**
     * Teste la sanitization d'un tableau
     */
    public function testSanitizeArray(): void
    {
        $input = [
            'name' => '<b>John</b>',
            'email' => 'john@example.com<script>alert(1)</script>',
            'nested' => [
                'data' => '<img src="x" onerror="alert(1)">test'
            ]
        ];
        
        $expected = [
            'name' => 'John',
            'email' => 'john@example.com',
            'nested' => [
                'data' => 'test'
            ]
        ];
        
        $sanitized = Security::sanitize($input);
        
        $this->assertEquals($expected, $sanitized);
    }
    
    /**
     * Teste la validation des données avec des règles simples
     */
    public function testValidateWithSimpleRules(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '30'
        ];
        
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric'
        ];
        
        $errors = Security::validate($data, $rules);
        
        $this->assertEmpty($errors);
    }
    
    /**
     * Teste la validation avec des données invalides
     */
    public function testValidateWithInvalidData(): void
    {
        $data = [
            'name' => '',
            'email' => 'not-an-email',
            'age' => 'thirty'
        ];
        
        $rules = [
            'name' => 'required',
            'email' => 'required|email',
            'age' => 'required|numeric'
        ];
        
        $errors = Security::validate($data, $rules);
        
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('age', $errors);
    }
    
    /**
     * Teste la validation avec des règles min/max
     */
    public function testValidateWithMinMaxRules(): void
    {
        $data = [
            'password' => 'short',
            'bio' => str_repeat('a', 300)
        ];
        
        $rules = [
            'password' => 'required|min:8',
            'bio' => 'max:255'
        ];
        
        $errors = Security::validate($data, $rules);
        
        $this->assertArrayHasKey('password', $errors);
        $this->assertArrayHasKey('bio', $errors);
    }
    
    /**
     * Teste la génération et vérification d'un token CSRF
     */
    public function testCsrfTokenGenerationAndVerification(): void
    {
        // Démarrer une session pour le stockage du token
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Générer un token
        $token = Security::generateCsrfToken();
        
        // Vérifier que le token a été généré
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        
        // Vérifier que le token est valide
        $this->assertTrue(Security::verifyCsrfToken($token));
        
        // Vérifier qu'un token incorrect échoue
        $this->assertFalse(Security::verifyCsrfToken('invalid_token'));
        
        // Nettoyer
        session_destroy();
    }
} 