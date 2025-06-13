<?php

namespace App\Services;

use PDO;
use Exception;

class CreditService
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Récupère le solde de crédits d'un utilisateur
     */
    public function getBalance(int $userId): float
    {
        $stmt = $this->db->prepare('SELECT solde FROM CreditBalance WHERE utilisateur_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['solde'] : 0.0;
    }

    /**
     * Vérifie si l'utilisateur a suffisamment de crédits
     */
    public function validateTransaction(int $userId, float $amount): bool
    {
        return $this->getBalance($userId) >= $amount;
    }

    /**
     * Ajoute des crédits au compte utilisateur et enregistre la transaction
     */
    public function creditAccount(int $userId, float $amount, string $type, string $description): void
    {
        $this->db->beginTransaction();
        try {
            $typeId = $this->getTypeId($type);
            // Met à jour ou insère le solde
            $stmt = $this->db->prepare('INSERT INTO CreditBalance (utilisateur_id, solde) VALUES (?, ?) ON DUPLICATE KEY UPDATE solde = solde + VALUES(solde)');
            $stmt->execute([$userId, $amount]);
            // Enregistre la transaction
            $stmt2 = $this->db->prepare('INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$userId, $amount, $typeId, $description]);
            $this->db->commit();

            // Suppression de la journalisation MongoDB car nous n'utilisons plus MongoDB
            // La journalisation est maintenant gérée via MySQL
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Retire des crédits du compte utilisateur et enregistre la transaction
     */
    public function debitAccount(int $userId, float $amount, string $type, string $description): void
    {
        if (!$this->validateTransaction($userId, $amount)) {
            throw new Exception('Solde insuffisant');
        }
        $this->db->beginTransaction();
        try {
            $typeId = $this->getTypeId($type);
            $debit = -abs($amount);
            // Met à jour le solde
            $stmt = $this->db->prepare('UPDATE CreditBalance SET solde = solde + ? WHERE utilisateur_id = ?');
            $stmt->execute([$debit, $userId]);
            // Enregistre la transaction
            $stmt2 = $this->db->prepare('INSERT INTO CreditTransaction (utilisateur_id, montant, type_id, description) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$userId, $debit, $typeId, $description]);
            $this->db->commit();

            // Suppression de la journalisation MongoDB car nous n'utilisons plus MongoDB
            // La journalisation est maintenant gérée via MySQL
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Calcule le coût estimé d'un trajet selon la configuration
     */
    public function calculateTripCost(float $distance, array $otherFactors = []): array
    {
        $min = (float)config('credits.min_cost', 3);
        $divisor = (float)config('credits.max_distance_divisor', 3);
        $commission = (float)config('credits.commission_fee', 2);
        $max = $distance / $divisor;
        return [
            'min_credits' => $min,
            'max_credits' => round($max, 2),
            'commission_fee' => $commission,
        ];
    }

    /**
     * Récupère l'ID d'un type de transaction à partir de son libellé
     */
    private function getTypeId(string $type): int
    {
        $stmt = $this->db->prepare('SELECT type_id FROM TypeTransaction WHERE libelle = ?');
        $stmt->execute([$type]);
        $typeId = $stmt->fetchColumn();
        if (!$typeId) {
            throw new Exception("Type de transaction inconnu: $type");
        }
        return (int)$typeId;
    }
} 