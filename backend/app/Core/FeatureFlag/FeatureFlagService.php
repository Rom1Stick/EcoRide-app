<?php

namespace App\Core\FeatureFlag;

use App\Core\Logger;

/**
 * Service de gestion des Feature Flags pour la migration progressive
 * 
 * Permet de contrôler finement l'activation des fonctionnalités de la nouvelle
 * architecture orientée objet selon différents critères.
 */
class FeatureFlagService
{
    private array $config;
    private Logger $logger;
    private array $metrics = [];

    public function __construct(array $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->initializeMetrics();
    }

    /**
     * Vérifie si une fonctionnalité OO est activée pour un utilisateur
     */
    public function isFeatureEnabled(string $feature, ?int $userId = null, array $context = []): bool
    {
        try {
            // Vérification des circuits breakers
            if ($this->isCircuitBreakerTripped($feature)) {
                $this->logDecision($feature, false, 'circuit_breaker_tripped', $userId);
                return false;
            }

            // Vérification de l'activation globale de la fonctionnalité
            if (!$this->isFeatureGloballyEnabled($feature)) {
                $this->logDecision($feature, false, 'globally_disabled', $userId);
                return false;
            }

            // Vérification du traffic split global
            if (!$this->shouldUseV2BasedOnTrafficSplit($userId)) {
                $this->logDecision($feature, false, 'traffic_split', $userId);
                return false;
            }

            // Vérification des groupes d'utilisateurs spéciaux
            if ($userId && $this->isSpecialUser($userId)) {
                $this->logDecision($feature, true, 'special_user', $userId);
                return true;
            }

            // Décision finale basée sur les pourcentages et critères
            $enabled = $this->calculateFeatureEligibility($feature, $userId, $context);
            
            $this->logDecision($feature, $enabled, 'calculated', $userId);
            $this->recordMetric($feature, $enabled);
            
            return $enabled;

        } catch (\Exception $e) {
            $this->logger->error('Erreur dans FeatureFlagService', [
                'feature' => $feature,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // En cas d'erreur, on retourne vers V1 (sécurisé)
            return false;
        }
    }

    /**
     * Vérifie si le traffic splitting indique d'utiliser V2
     */
    public function shouldUseV2BasedOnTrafficSplit(?int $userId = null): bool
    {
        if (!$this->config['traffic_split']['enabled']) {
            return false;
        }

        $v2Percentage = $this->config['traffic_split']['v2_percentage'];
        
        if ($v2Percentage >= 100) {
            return true;
        }

        if ($v2Percentage <= 0) {
            return false;
        }

        // Utilisation d'un hash cohérent basé sur l'utilisateur ou l'IP
        $seed = $userId ?? $this->getClientSeed();
        $hash = crc32((string)$seed) % 100;
        
        return $hash < $v2Percentage;
    }

    /**
     * Enregistre une erreur pour le circuit breaker
     */
    public function recordError(string $feature, string $errorType, array $context = []): void
    {
        $this->logger->warning('Erreur feature flag enregistrée', [
            'feature' => $feature,
            'error_type' => $errorType,
            'context' => $context
        ]);

        // Implémentation du circuit breaker
        $this->incrementErrorCount($feature);
        
        if ($this->shouldTripCircuitBreaker($feature)) {
            $this->tripCircuitBreaker($feature);
        }
    }

    /**
     * Enregistre une réussite pour le circuit breaker
     */
    public function recordSuccess(string $feature): void
    {
        $this->resetErrorCount($feature);
    }

    /**
     * Obtient les métriques d'utilisation des features
     */
    public function getMetrics(): array
    {
        return [
            'features' => $this->metrics,
            'circuit_breakers' => $this->getCircuitBreakerStatus(),
            'traffic_split' => $this->config['traffic_split'],
            'timestamp' => time()
        ];
    }

    /**
     * Force l'activation/désactivation d'une feature (pour débogage)
     */
    public function forceFeature(string $feature, bool $enabled): void
    {
        $this->config['features'][$feature . '_forced'] = $enabled;
        
        $this->logger->info('Feature forcée', [
            'feature' => $feature,
            'enabled' => $enabled
        ]);
    }

    // ==========================================
    // MÉTHODES PRIVÉES
    // ==========================================

    private function initializeMetrics(): void
    {
        $this->metrics = [
            'requests' => [],
            'decisions' => [],
            'errors' => []
        ];
    }

    private function isFeatureGloballyEnabled(string $feature): bool
    {
        // Vérification force override
        if (isset($this->config['features'][$feature . '_forced'])) {
            return $this->config['features'][$feature . '_forced'];
        }

        return $this->config['features'][$feature] ?? false;
    }

    private function isSpecialUser(int $userId): bool
    {
        // Utilisateurs beta
        if ($this->config['user_groups']['beta_users']) {
            $betaUsers = $this->config['user_groups']['beta_user_ids'];
            if (in_array($userId, $betaUsers)) {
                return true;
            }
        }

        // Staff interne (nécessiterait une requête DB en réalité)
        // Pour l'instant, on simule avec des IDs < 100
        if ($this->config['user_groups']['staff_users'] && $userId < 100) {
            return true;
        }

        return false;
    }

    private function calculateFeatureEligibility(string $feature, ?int $userId, array $context): bool
    {
        // Pour l'instant, utilisation du traffic split global
        // Dans une version avancée, on pourrait avoir des % par feature
        return $this->shouldUseV2BasedOnTrafficSplit($userId);
    }

    private function isCircuitBreakerTripped(string $feature): bool
    {
        if (!$this->config['safety']['circuit_breaker_enabled']) {
            return false;
        }

        $key = "circuit_breaker_{$feature}";
        // En production, ceci serait stocké en cache/redis
        return false; // Simplifié pour l'exemple
    }

    private function shouldTripCircuitBreaker(string $feature): bool
    {
        $threshold = $this->config['safety']['circuit_breaker_threshold'];
        $errorCount = $this->getErrorCount($feature);
        
        return $errorCount >= $threshold;
    }

    private function tripCircuitBreaker(string $feature): void
    {
        $this->logger->critical('Circuit breaker activé', [
            'feature' => $feature,
            'error_count' => $this->getErrorCount($feature)
        ]);

        // En production, notifier les équipes ops
    }

    private function incrementErrorCount(string $feature): void
    {
        $key = "errors_{$feature}";
        if (!isset($this->metrics['errors'][$key])) {
            $this->metrics['errors'][$key] = 0;
        }
        $this->metrics['errors'][$key]++;
    }

    private function resetErrorCount(string $feature): void
    {
        $key = "errors_{$feature}";
        $this->metrics['errors'][$key] = 0;
    }

    private function getErrorCount(string $feature): int
    {
        $key = "errors_{$feature}";
        return $this->metrics['errors'][$key] ?? 0;
    }

    private function getCircuitBreakerStatus(): array
    {
        $status = [];
        foreach ($this->config['features'] as $feature => $enabled) {
            $status[$feature] = [
                'tripped' => $this->isCircuitBreakerTripped($feature),
                'error_count' => $this->getErrorCount($feature)
            ];
        }
        return $status;
    }

    private function getClientSeed(): string
    {
        // En production, utiliser l'IP client ou session ID
        return $_SERVER['REMOTE_ADDR'] ?? 'default';
    }

    private function logDecision(string $feature, bool $enabled, string $reason, ?int $userId): void
    {
        $this->logger->debug('Décision feature flag', [
            'feature' => $feature,
            'enabled' => $enabled,
            'reason' => $reason,
            'user_id' => $userId
        ]);

        // Enregistrement pour analytics
        $this->metrics['decisions'][] = [
            'feature' => $feature,
            'enabled' => $enabled,
            'reason' => $reason,
            'user_id' => $userId,
            'timestamp' => time()
        ];
    }

    private function recordMetric(string $feature, bool $enabled): void
    {
        $key = $feature . '_' . ($enabled ? 'enabled' : 'disabled');
        if (!isset($this->metrics['requests'][$key])) {
            $this->metrics['requests'][$key] = 0;
        }
        $this->metrics['requests'][$key]++;
    }
} 