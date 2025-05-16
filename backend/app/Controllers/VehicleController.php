<?php

namespace App\Controllers;

use PDO;

class VehicleController extends Controller
{
    /**
     * Liste les véhicules de l'utilisateur (chauffeur)
     */
    public function index(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        // Vérifier rôle
        if (($_SERVER['AUTH_USER_ROLE'] ?? '') !== 'chauffeur') {
            return $this->error('Accès réservé aux chauffeurs', 403);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare(
            'SELECT voiture_id AS id, modele_id, immatriculation, energie_id, couleur, date_premiere_immat AS first_immat 
             FROM Voiture WHERE utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->success(['vehicles' => $vehicles]);
    }

    /**
     * Crée un nouveau véhicule pour le chauffeur
     */
    public function store(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        if (($_SERVER['AUTH_USER_ROLE'] ?? '') !== 'chauffeur') {
            return $this->error('Accès réservé aux chauffeurs', 403);
        }

        $data = sanitize($this->getJsonData());
        $modeleId = isset($data['modele_id']) ? (int) $data['modele_id'] : null;
        $energieId = isset($data['energie_id']) ? (int) $data['energie_id'] : null;
        $immatriculation = $data['immatriculation'] ?? null;
        $couleur = $data['couleur'] ?? null;
        $firstImmat = $data['date_premiere_immat'] ?? null;

        if (!$modeleId || !$energieId || !$immatriculation) {
            return $this->error('modele_id, energie_id et immatriculation sont obligatoires', 400);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'INSERT INTO Voiture (modele_id, immatriculation, energie_id, couleur, date_premiere_immat, utilisateur_id) 
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $modeleId,
                $immatriculation,
                $energieId,
                $couleur,
                $firstImmat,
                $userId
            ]);
            $vehicleId = (int) $db->lastInsertId();
            $db->commit();
            return $this->success(['vehicle_id' => $vehicleId], 'Véhicule créé');
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->error('Erreur lors de la création du véhicule : ' . $e->getMessage(), 500);
        }
    }
} 