<?php

namespace App\Models\Entities;

/**
 * Entité représentant un log d'activité
 */
class ActivityLog
{
    public string $id;
    public int $user_id;
    public string $action;
    public string $entity_type;
    public ?int $entity_id;
    public ?array $metadata;
    public string $ip_address;
    public string $user_agent;
    public string $created_at;
    
    /**
     * Constructeur
     *
     * @param int $user_id ID de l'utilisateur
     * @param string $action Action réalisée
     * @param string $entity_type Type d'entité concernée
     * @param int|null $entity_id ID de l'entité concernée
     * @param array|null $metadata Métadonnées supplémentaires
     * @param string|null $ip_address Adresse IP
     * @param string|null $user_agent Agent utilisateur
     * @param string|null $id ID unique (généré automatiquement si null)
     * @param string|null $created_at Date de création (générée automatiquement si null)
     */
    public function __construct(
        int $user_id,
        string $action,
        string $entity_type = '',
        ?int $entity_id = null,
        ?array $metadata = null,
        ?string $ip_address = null,
        ?string $user_agent = null,
        ?string $id = null,
        ?string $created_at = null
    ) {
        $this->id = $id ?? uniqid('log_', true);
        $this->user_id = $user_id;
        $this->action = $action;
        $this->entity_type = strtolower($entity_type);
        $this->entity_id = $entity_id;
        $this->metadata = $metadata;
        $this->ip_address = $ip_address ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $this->user_agent = $user_agent ?? $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $this->created_at = $created_at ?? date('Y-m-d H:i:s');
    }
    
    /**
     * Ajoute une information aux métadonnées
     *
     * @param string $key Clé de métadonnée
     * @param mixed $value Valeur de métadonnée
     * @return self Pour chaînage
     */
    public function addMetadata(string $key, $value): self
    {
        if ($this->metadata === null) {
            $this->metadata = [];
        }
        
        $this->metadata[$key] = $value;
        
        return $this;
    }
    
    /**
     * Récupère une métadonnée spécifique
     *
     * @param string $key Clé de métadonnée
     * @param mixed $default Valeur par défaut si la métadonnée n'existe pas
     * @return mixed Valeur de métadonnée ou valeur par défaut
     */
    public function getMetadata(string $key, $default = null)
    {
        if ($this->metadata === null || !isset($this->metadata[$key])) {
            return $default;
        }
        
        return $this->metadata[$key];
    }
    
    /**
     * Vérifie si l'activité concerne un utilisateur spécifique
     *
     * @param int $userId ID de l'utilisateur
     * @return bool Vrai si l'activité concerne l'utilisateur spécifié
     */
    public function isForUser(int $userId): bool
    {
        return $this->user_id === $userId;
    }
    
    /**
     * Vérifie si l'activité correspond à une action spécifique
     *
     * @param string $action Action à vérifier
     * @return bool Vrai si l'activité correspond à l'action spécifiée
     */
    public function isAction(string $action): bool
    {
        return strtolower($this->action) === strtolower($action);
    }
    
    /**
     * Vérifie si l'activité concerne une entité spécifique
     *
     * @param string $entityType Type d'entité
     * @param int|null $entityId ID d'entité (optionnel)
     * @return bool Vrai si l'activité concerne l'entité spécifiée
     */
    public function concernsEntity(string $entityType, ?int $entityId = null): bool
    {
        $typeMatch = strtolower($this->entity_type) === strtolower($entityType);
        
        if ($entityId === null) {
            return $typeMatch;
        }
        
        return $typeMatch && $this->entity_id === $entityId;
    }
    
    /**
     * Récupère l'âge du log en secondes
     *
     * @return int Âge en secondes
     */
    public function getAgeInSeconds(): int
    {
        $createdTimestamp = strtotime($this->created_at);
        $now = time();
        
        return $now - $createdTimestamp;
    }
    
    /**
     * Vérifie si le log est récent (moins d'une heure)
     *
     * @return bool Vrai si le log est récent
     */
    public function isRecent(): bool
    {
        return $this->getAgeInSeconds() < 3600; // 1 heure = 3600 secondes
    }
    
    /**
     * Détermine si le log est critique (actions sensibles)
     *
     * @return bool Vrai si le log est critique
     */
    public function isCritical(): bool
    {
        $criticalActions = [
            'login', 'logout', 'password_change', 'password_reset',
            'account_create', 'account_delete', 'payment_add',
            'admin_access', 'settings_update'
        ];
        
        return in_array(strtolower($this->action), $criticalActions);
    }
    
    /**
     * Détermine si le log concerne une modification de données
     *
     * @return bool Vrai si le log concerne une modification
     */
    public function isModification(): bool
    {
        return preg_match('/^(update|modify|delete|create|add|remove)/', strtolower($this->action)) === 1;
    }
    
    /**
     * Détermine si le log provient d'un appareil mobile
     *
     * @return bool Vrai si le log provient d'un appareil mobile
     */
    public function isFromMobile(): bool
    {
        return preg_match('/(android|iphone|ipad|mobile)/i', $this->user_agent) === 1;
    }
    
    /**
     * Convertit le log en tableau
     *
     * @return array Tableau représentant l'objet
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'metadata' => $this->metadata,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at
        ];
    }
    
    /**
     * Crée un objet à partir d'un tableau
     *
     * @param array $data Données
     * @return ActivityLog Instance d'ActivityLog
     */
    public static function fromArray(array $data): ActivityLog
    {
        return new self(
            $data['user_id'],
            $data['action'],
            $data['entity_type'] ?? '',
            $data['entity_id'] ?? null,
            $data['metadata'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['id'] ?? null,
            $data['created_at'] ?? null
        );
    }
    
    /**
     * Crée une entrée de log pour une connexion
     *
     * @param int $userId ID de l'utilisateur
     * @param string|null $ipAddress Adresse IP
     * @param string|null $userAgent Agent utilisateur
     * @return ActivityLog Instance d'ActivityLog
     */
    public static function login(int $userId, ?string $ipAddress = null, ?string $userAgent = null): ActivityLog
    {
        return new self(
            $userId,
            'login',
            'user',
            $userId,
            ['success' => true],
            $ipAddress,
            $userAgent
        );
    }
    
    /**
     * Crée une entrée de log pour une déconnexion
     *
     * @param int $userId ID de l'utilisateur
     * @param string|null $ipAddress Adresse IP
     * @param string|null $userAgent Agent utilisateur
     * @return ActivityLog Instance d'ActivityLog
     */
    public static function logout(int $userId, ?string $ipAddress = null, ?string $userAgent = null): ActivityLog
    {
        return new self(
            $userId,
            'logout',
            'user',
            $userId,
            null,
            $ipAddress,
            $userAgent
        );
    }
    
    /**
     * Crée une entrée de log pour une action échouée
     *
     * @param int $userId ID de l'utilisateur
     * @param string $action Action tentée
     * @param string $reason Raison de l'échec
     * @param string|null $ipAddress Adresse IP
     * @param string|null $userAgent Agent utilisateur
     * @return ActivityLog Instance d'ActivityLog
     */
    public static function failedAction(
        int $userId,
        string $action,
        string $reason,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ActivityLog {
        return new self(
            $userId,
            'failed_' . $action,
            '',
            null,
            ['reason' => $reason],
            $ipAddress,
            $userAgent
        );
    }
} 