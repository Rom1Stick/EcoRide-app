<?php

namespace App\Models\Entities;

/**
 * Entité représentant l'activité d'un utilisateur
 */
class UserActivity
{
    public string $id;
    public int $user_id;
    public string $action;
    public array $data;
    public string $ip_address;
    public string $user_agent;
    public string $created_at;
    
    /**
     * Constructeur
     *
     * @param int $user_id ID de l'utilisateur
     * @param string $action Type d'action réalisée
     * @param array $data Données associées à l'action
     * @param string $ip_address Adresse IP
     * @param string $user_agent Agent utilisateur
     * @param string|null $id ID unique (généré automatiquement si null)
     * @param string|null $created_at Date de création (générée automatiquement si null)
     */
    public function __construct(
        int $user_id,
        string $action,
        array $data = [],
        string $ip_address = '',
        string $user_agent = '',
        ?string $id = null,
        ?string $created_at = null
    ) {
        $this->id = $id ?? uniqid('act_', true);
        $this->user_id = $user_id;
        $this->action = $action;
        $this->data = $data;
        $this->ip_address = $ip_address;
        $this->user_agent = $user_agent;
        $this->created_at = $created_at ?? date('Y-m-d H:i:s');
    }
    
    /**
     * Récupère les données d'une clé spécifique
     *
     * @param string $key Clé des données
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur des données ou valeur par défaut
     */
    public function getData(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Vérifie si l'action est d'un type spécifique
     *
     * @param string $actionType Type d'action à vérifier
     * @return bool Vrai si l'action est du type spécifié
     */
    public function isActionType(string $actionType): bool
    {
        return $this->action === $actionType;
    }
    
    /**
     * Vérifie si l'activité concerne une entité spécifique
     *
     * @param string $entityType Type d'entité
     * @param int|null $entityId ID de l'entité (optionnel)
     * @return bool Vrai si l'activité concerne l'entité spécifiée
     */
    public function concernsEntity(string $entityType, ?int $entityId = null): bool
    {
        if (!isset($this->data['entity_type']) || $this->data['entity_type'] !== $entityType) {
            return false;
        }
        
        if ($entityId !== null && (!isset($this->data['entity_id']) || $this->data['entity_id'] != $entityId)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Génère une description lisible de l'activité
     *
     * @return string Description de l'activité
     */
    public function getDescription(): string
    {
        $description = "Utilisateur {$this->user_id} a ";
        
        switch ($this->action) {
            case 'login':
                $description .= "s'est connecté";
                break;
            case 'logout':
                $description .= "s'est déconnecté";
                break;
            case 'register':
                $description .= "s'est inscrit";
                break;
            case 'view':
                $entityType = $this->data['entity_type'] ?? 'inconnu';
                $entityId = $this->data['entity_id'] ?? '';
                $description .= "consulté $entityType #$entityId";
                break;
            case 'create':
                $entityType = $this->data['entity_type'] ?? 'inconnu';
                $entityId = $this->data['entity_id'] ?? '';
                $description .= "créé $entityType #$entityId";
                break;
            case 'update':
                $entityType = $this->data['entity_type'] ?? 'inconnu';
                $entityId = $this->data['entity_id'] ?? '';
                $description .= "mis à jour $entityType #$entityId";
                break;
            case 'delete':
                $entityType = $this->data['entity_type'] ?? 'inconnu';
                $entityId = $this->data['entity_id'] ?? '';
                $description .= "supprimé $entityType #$entityId";
                break;
            case 'search':
                $query = $this->data['query'] ?? '';
                $description .= "recherché '$query'";
                break;
            default:
                $description .= "effectué l'action '{$this->action}'";
        }
        
        return $description;
    }
    
    /**
     * Vérifie si l'activité s'est produite dans un intervalle de temps
     *
     * @param string $start Date de début (format Y-m-d H:i:s)
     * @param string $end Date de fin (format Y-m-d H:i:s)
     * @return bool Vrai si l'activité s'est produite dans l'intervalle
     */
    public function occurredBetween(string $start, string $end): bool
    {
        return $this->created_at >= $start && $this->created_at <= $end;
    }
    
    /**
     * Vérifie si l'activité est récente
     *
     * @param int $minutes Nombre de minutes
     * @return bool Vrai si l'activité est récente
     */
    public function isRecent(int $minutes = 30): bool
    {
        $timestamp = strtotime($this->created_at);
        $currentTime = time();
        $diffInMinutes = ($currentTime - $timestamp) / 60;
        
        return $diffInMinutes <= $minutes;
    }
    
    /**
     * Récupère les informations sur le navigateur et l'appareil
     *
     * @return array Informations sur le navigateur et l'appareil
     */
    public function getBrowserInfo(): array
    {
        // Analyse simplifiée de l'agent utilisateur
        $browser = 'Inconnu';
        $os = 'Inconnu';
        $device = 'Inconnu';
        
        $userAgent = $this->user_agent;
        
        // Détection du navigateur
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Edge';
        }
        
        // Détection du système d'exploitation
        if (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'MacOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad|iPod/i', $userAgent)) {
            $os = 'iOS';
        }
        
        // Détection de l'appareil
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent)) {
            $device = 'Mobile';
            
            if (preg_match('/iPad|Tablet/i', $userAgent)) {
                $device = 'Tablette';
            } elseif (preg_match('/iPhone|Android/i', $userAgent)) {
                $device = 'Smartphone';
            }
        } else {
            $device = 'Ordinateur';
        }
        
        return [
            'browser' => $browser,
            'os' => $os,
            'device' => $device,
            'raw' => $userAgent
        ];
    }
    
    /**
     * Convertit l'activité en tableau
     *
     * @return array Tableau représentant l'objet
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'data' => $this->data,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at
        ];
    }
    
    /**
     * Crée un objet à partir d'un tableau
     *
     * @param array $data Données
     * @return UserActivity Instance de UserActivity
     */
    public static function fromArray(array $data): UserActivity
    {
        return new self(
            $data['user_id'],
            $data['action'],
            $data['data'] ?? [],
            $data['ip_address'] ?? '',
            $data['user_agent'] ?? '',
            $data['id'] ?? null,
            $data['created_at'] ?? null
        );
    }
} 