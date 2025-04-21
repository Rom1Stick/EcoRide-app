<?php

namespace App\Models\Entities;

/**
 * Entité représentant les préférences d'un utilisateur
 */
class UserPreferences
{
    /**
     * ID de l'utilisateur
     *
     * @var int
     */
    public int $user_id;
    
    /**
     * Préférences de l'utilisateur
     *
     * @var array
     */
    public array $preferences;
    
    /**
     * Date de dernière mise à jour
     *
     * @var string|null
     */
    public ?string $updated_at;
    
    /**
     * Constructeur
     *
     * @param int $user_id ID de l'utilisateur
     * @param array $preferences Tableau de préférences
     * @param string|null $updated_at Date de dernière mise à jour
     */
    public function __construct(
        int $user_id,
        array $preferences = [],
        ?string $updated_at = null
    ) {
        $this->user_id = $user_id;
        $this->preferences = $preferences;
        $this->updated_at = $updated_at ?? date('Y-m-d H:i:s');
    }
    
    /**
     * Récupère une préférence spécifique
     *
     * @param string $key Clé de la préférence
     * @param mixed $default Valeur par défaut si la préférence n'existe pas
     * @return mixed Valeur de la préférence ou valeur par défaut
     */
    public function get(string $key, $default = null)
    {
        return $this->preferences[$key] ?? $default;
    }
    
    /**
     * Définit une préférence
     *
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur de la préférence
     * @return self Pour chaînage
     */
    public function set(string $key, $value): self
    {
        $this->preferences[$key] = $value;
        $this->updated_at = date('Y-m-d H:i:s');
        return $this;
    }
    
    /**
     * Définit plusieurs préférences à la fois
     *
     * @param array $preferences Tableau de préférences à définir
     * @return self Pour chaînage
     */
    public function setMultiple(array $preferences): self
    {
        foreach ($preferences as $key => $value) {
            $this->preferences[$key] = $value;
        }
        
        $this->updated_at = date('Y-m-d H:i:s');
        return $this;
    }
    
    /**
     * Vérifie si une préférence existe
     *
     * @param string $key Clé de la préférence
     * @return bool Vrai si la préférence existe
     */
    public function has(string $key): bool
    {
        return isset($this->preferences[$key]);
    }
    
    /**
     * Supprime une préférence
     *
     * @param string $key Clé de la préférence
     * @return self Pour chaînage
     */
    public function remove(string $key): self
    {
        if (isset($this->preferences[$key])) {
            unset($this->preferences[$key]);
            $this->updated_at = date('Y-m-d H:i:s');
        }
        
        return $this;
    }
    
    /**
     * Récupère toutes les préférences
     *
     * @return array Tableau de préférences
     */
    public function all(): array
    {
        return $this->preferences;
    }
    
    /**
     * Efface toutes les préférences
     *
     * @return self Pour chaînage
     */
    public function clear(): self
    {
        $this->preferences = [];
        $this->updated_at = date('Y-m-d H:i:s');
        
        return $this;
    }
    
    /**
     * Calcule un score de similarité avec d'autres préférences
     *
     * @param UserPreferences $otherPreferences Autres préférences
     * @return float Score de similarité entre 0 et 1
     */
    public function getSimilarityWith(UserPreferences $otherPreferences): float
    {
        if (empty($this->preferences) || empty($otherPreferences->preferences)) {
            return 0;
        }
        
        $allKeys = array_unique(array_merge(array_keys($this->preferences), array_keys($otherPreferences->preferences)));
        $matchCount = 0;
        $totalCount = count($allKeys);
        
        foreach ($allKeys as $key) {
            if ($this->has($key) && $otherPreferences->has($key)) {
                $value1 = $this->get($key);
                $value2 = $otherPreferences->get($key);
                
                // Vérifie si les valeurs sont identiques
                if (is_scalar($value1) && is_scalar($value2)) {
                    if ($value1 === $value2) {
                        $matchCount++;
                    }
                } elseif (is_array($value1) && is_array($value2)) {
                    // Pour les tableaux, calcule l'intersection
                    $intersection = array_intersect($value1, $value2);
                    $union = array_unique(array_merge($value1, $value2));
                    
                    if (!empty($union)) {
                        $matchCount += count($intersection) / count($union);
                    }
                }
            }
        }
        
        // Retourne le score de similarité
        return $totalCount > 0 ? $matchCount / $totalCount : 0;
    }
    
    /**
     * Vérifie si l'utilisateur a une préférence pour une catégorie spécifique
     *
     * @param string $category Catégorie à vérifier
     * @return bool Vrai si l'utilisateur a une préférence pour cette catégorie
     */
    public function hasPreferenceForCategory(string $category): bool
    {
        $categoryKey = 'preferred_categories';
        
        if (!$this->has($categoryKey) || !is_array($this->get($categoryKey))) {
            return false;
        }
        
        return in_array($category, $this->get($categoryKey));
    }
    
    /**
     * Ajoute une préférence de catégorie
     *
     * @param string $category Catégorie à ajouter
     * @return self Pour chaînage
     */
    public function addCategoryPreference(string $category): self
    {
        $categoryKey = 'preferred_categories';
        $categories = $this->get($categoryKey, []);
        
        if (!is_array($categories)) {
            $categories = [];
        }
        
        if (!in_array($category, $categories)) {
            $categories[] = $category;
            $this->set($categoryKey, $categories);
        }
        
        return $this;
    }
    
    /**
     * Supprime une préférence de catégorie
     *
     * @param string $category Catégorie à supprimer
     * @return self Pour chaînage
     */
    public function removeCategoryPreference(string $category): self
    {
        $categoryKey = 'preferred_categories';
        $categories = $this->get($categoryKey, []);
        
        if (!is_array($categories)) {
            return $this;
        }
        
        $index = array_search($category, $categories);
        
        if ($index !== false) {
            unset($categories[$index]);
            $categories = array_values($categories); // Réindexe le tableau
            $this->set($categoryKey, $categories);
        }
        
        return $this;
    }
    
    /**
     * Récupère la liste des catégories préférées
     *
     * @return array Liste des catégories préférées
     */
    public function getPreferredCategories(): array
    {
        $categoryKey = 'preferred_categories';
        $categories = $this->get($categoryKey, []);
        
        return is_array($categories) ? $categories : [];
    }
    
    /**
     * Convertit les préférences en tableau
     *
     * @return array Tableau représentant l'objet
     */
    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'preferences' => $this->preferences,
            'updated_at' => $this->updated_at
        ];
    }
    
    /**
     * Crée un objet à partir d'un tableau
     *
     * @param array $data Données
     * @return UserPreferences Instance de UserPreferences
     */
    public static function fromArray(array $data): UserPreferences
    {
        return new self(
            $data['user_id'],
            $data['preferences'] ?? [],
            $data['updated_at'] ?? null
        );
    }
} 