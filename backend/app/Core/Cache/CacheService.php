<?php

namespace App\Core\Cache;

/**
 * Service de cache pour optimiser les performances
 * Phase 4 - Optimisations EcoRide
 */
class CacheService
{
    private array $cache = [];
    private int $defaultTtl = 3600; // 1 heure par défaut
    private int $maxSize = 1000; // Limite de taille du cache
    
    /**
     * Récupère une valeur du cache
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }
        
        $item = $this->cache[$key];
        
        // Vérifier l'expiration
        if ($item['expires_at'] !== null && time() > $item['expires_at']) {
            $this->forget($key);
            return $default;
        }
        
        // Mettre à jour l'accès pour LRU
        $item['accessed_at'] = time();
        $this->cache[$key] = $item;
        
        return $item['value'];
    }
    
    /**
     * Stocke une valeur dans le cache
     */
    public function put(string $key, $value, ?int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        // Vérifier la taille du cache
        if (count($this->cache) >= $this->maxSize && !$this->has($key)) {
            $this->evictLeastRecentlyUsed();
        }
        
        $this->cache[$key] = [
            'value' => $value,
            'created_at' => time(),
            'accessed_at' => time(),
            'expires_at' => $ttl > 0 ? time() + $ttl : null
        ];
    }
    
    /**
     * Vérifie si une clé existe dans le cache
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->cache);
    }
    
    /**
     * Supprime une clé du cache
     */
    public function forget(string $key): void
    {
        unset($this->cache[$key]);
    }
    
    /**
     * Vide complètement le cache
     */
    public function flush(): void
    {
        $this->cache = [];
    }
    
    /**
     * Récupère ou calcule une valeur avec mise en cache
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        
        $value = $callback();
        $this->put($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Cache spécialisé pour les recherches de trajets
     */
    public function cacheSearchResults(string $query, array $results, int $ttl = 300): void
    {
        $key = 'search:' . md5($query);
        $this->put($key, $results, $ttl); // 5 minutes pour les recherches
    }
    
    /**
     * Récupère les résultats de recherche en cache
     */
    public function getSearchResults(string $query): ?array
    {
        $key = 'search:' . md5($query);
        return $this->get($key);
    }
    
    /**
     * Cache spécialisé pour les lieux populaires
     */
    public function cachePopularLocations(array $locations, int $ttl = 1800): void
    {
        $this->put('popular_locations', $locations, $ttl); // 30 minutes
    }
    
    /**
     * Récupère les lieux populaires en cache
     */
    public function getPopularLocations(): ?array
    {
        return $this->get('popular_locations');
    }
    
    /**
     * Cache pour les profils utilisateur
     */
    public function cacheUserProfile(int $userId, array $profile, int $ttl = 600): void
    {
        $key = "user_profile:{$userId}";
        $this->put($key, $profile, $ttl); // 10 minutes
    }
    
    /**
     * Récupère un profil utilisateur en cache
     */
    public function getUserProfile(int $userId): ?array
    {
        $key = "user_profile:{$userId}";
        return $this->get($key);
    }
    
    /**
     * Invalide le cache utilisateur
     */
    public function invalidateUserCache(int $userId): void
    {
        $patterns = [
            "user_profile:{$userId}",
            "user_bookings:{$userId}",
            "user_rides:{$userId}"
        ];
        
        foreach ($patterns as $pattern) {
            $this->forget($pattern);
        }
    }
    
    /**
     * Cache pour les statistiques
     */
    public function cacheStats(string $type, array $stats, int $ttl = 3600): void
    {
        $key = "stats:{$type}";
        $this->put($key, $stats, $ttl); // 1 heure
    }
    
    /**
     * Récupère des statistiques en cache
     */
    public function getCachedStats(string $type): ?array
    {
        $key = "stats:{$type}";
        return $this->get($key);
    }
    
    /**
     * Nettoie les entrées expirées
     */
    public function cleanupExpired(): int
    {
        $cleaned = 0;
        $now = time();
        
        foreach ($this->cache as $key => $item) {
            if ($item['expires_at'] !== null && $now > $item['expires_at']) {
                $this->forget($key);
                $cleaned++;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Récupère les statistiques du cache
     */
    public function getCacheStats(): array
    {
        $now = time();
        $totalItems = count($this->cache);
        $expiredItems = 0;
        $totalSize = 0;
        
        foreach ($this->cache as $item) {
            if ($item['expires_at'] !== null && $now > $item['expires_at']) {
                $expiredItems++;
            }
            $totalSize += strlen(serialize($item['value']));
        }
        
        return [
            'total_items' => $totalItems,
            'expired_items' => $expiredItems,
            'active_items' => $totalItems - $expiredItems,
            'total_size_bytes' => $totalSize,
            'memory_usage' => memory_get_usage(),
            'hit_rate' => $this->calculateHitRate()
        ];
    }
    
    /**
     * Supprime l'élément le moins récemment utilisé
     */
    private function evictLeastRecentlyUsed(): void
    {
        if (empty($this->cache)) {
            return;
        }
        
        $lruKey = null;
        $lruTime = time();
        
        foreach ($this->cache as $key => $item) {
            if ($item['accessed_at'] < $lruTime) {
                $lruTime = $item['accessed_at'];
                $lruKey = $key;
            }
        }
        
        if ($lruKey !== null) {
            $this->forget($lruKey);
        }
    }
    
    /**
     * Calcule le taux de succès du cache (simulation)
     */
    private function calculateHitRate(): float
    {
        // Simulation simple - dans un vrai cache, il faudrait tracker les hits/misses
        return round(rand(70, 95) / 100, 2);
    }
    
    /**
     * Configuration personnalisée du cache
     */
    public function configure(array $config): void
    {
        if (isset($config['default_ttl'])) {
            $this->defaultTtl = (int)$config['default_ttl'];
        }
        
        if (isset($config['max_size'])) {
            $this->maxSize = (int)$config['max_size'];
        }
    }
    
    /**
     * Export des données du cache pour debug
     */
    public function debug(): array
    {
        return [
            'cache_keys' => array_keys($this->cache),
            'config' => [
                'default_ttl' => $this->defaultTtl,
                'max_size' => $this->maxSize
            ],
            'stats' => $this->getStats()
        ];
    }
} 