<?php

namespace App\Core;

/**
 * Classe Security
 * 
 * Gère les fonctionnalités de sécurité de l'application
 */
class Security
{
    /**
     * Sanitize les données d'entrée
     * 
     * @param mixed $input Données à nettoyer
     * @return mixed Données nettoyées
     */
    public static function sanitize($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
            return $input;
        }
        
        if (is_string($input)) {
            // Supprime les balises HTML
            $input = strip_tags($input);
            
            // Convertit les caractères spéciaux en entités HTML
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            
            return $input;
        }
        
        return $input;
    }
    
    /**
     * Génère un token CSRF
     * 
     * @return string
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Vérifie un token CSRF
     * 
     * @param string $token Token à vérifier
     * @return bool
     */
    public static function verifyCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Limite le taux de requêtes pour prévenir les attaques par force brute
     * 
     * @param string $key Clé unique (ex: IP + action)
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $period Période en secondes
     * @return bool
     */
    public static function rateLimit(string $key, int $maxAttempts = 5, int $period = 60): bool
    {
        $db = app()->getDatabase()->getSqliteConnection();
        
        // Nettoyer les anciennes entrées
        $stmt = $db->prepare('DELETE FROM rate_limits WHERE expires_at < datetime("now")');
        $stmt->execute();
        
        // Vérifier le nombre de tentatives
        $stmt = $db->prepare('SELECT COUNT(*) FROM rate_limits WHERE rate_key = ?');
        $stmt->execute([$key]);
        $attempts = (int) $stmt->fetchColumn();
        
        if ($attempts >= $maxAttempts) {
            return false;
        }
        
        // Enregistrer la tentative
        $stmt = $db->prepare('INSERT INTO rate_limits (rate_key, expires_at) VALUES (?, datetime("now", "+' . $period . ' seconds"))');
        $stmt->execute([$key]);
        
        return true;
    }
    
    /**
     * Valide les données selon des règles spécifiées
     * 
     * @param array $data Données à valider
     * @param array $rules Règles de validation
     * @return array Erreurs de validation ou tableau vide si tout est valide
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $rulesList = explode('|', $rule);
            
            foreach ($rulesList as $ruleItem) {
                // Vérification des règles avec paramètres (ex: max:255)
                if (strpos($ruleItem, ':') !== false) {
                    list($ruleName, $ruleParam) = explode(':', $ruleItem, 2);
                } else {
                    $ruleName = $ruleItem;
                    $ruleParam = null;
                }
                
                // Appliquer la règle
                switch ($ruleName) {
                    case 'required':
                        if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                            $errors[$field][] = "Le champ $field est obligatoire";
                        }
                        break;
                        
                    case 'email':
                        if (isset($data[$field]) && !filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "Le champ $field doit être une adresse email valide";
                        }
                        break;
                        
                    case 'min':
                        if (isset($data[$field]) && strlen((string)$data[$field]) < (int)$ruleParam) {
                            $errors[$field][] = "Le champ $field doit contenir au moins $ruleParam caractères";
                        }
                        break;
                        
                    case 'max':
                        if (isset($data[$field]) && strlen((string)$data[$field]) > (int)$ruleParam) {
                            $errors[$field][] = "Le champ $field ne doit pas dépasser $ruleParam caractères";
                        }
                        break;
                        
                    case 'numeric':
                        if (isset($data[$field]) && !is_numeric($data[$field])) {
                            $errors[$field][] = "Le champ $field doit être numérique";
                        }
                        break;
                        
                    case 'date':
                        if (isset($data[$field])) {
                            $date = \DateTime::createFromFormat('Y-m-d', $data[$field]);
                            if (!$date || $date->format('Y-m-d') !== $data[$field]) {
                                $errors[$field][] = "Le champ $field doit être une date valide (format YYYY-MM-DD)";
                            }
                        }
                        break;
                }
            }
        }
        
        return $errors;
    }
} 