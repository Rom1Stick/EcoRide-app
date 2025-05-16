<?php

namespace App\Controllers;

use App\Services\CreditService;
use PDO;

class BookingController extends Controller
{
    /**
     * Liste des réservations de l'utilisateur
     */
    public function index(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare(
            'SELECT p.covoiturage_id AS ride_id, p.date_reservation AS reserved_at, sp.libelle AS status 
             FROM Participation p 
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id 
             WHERE p.utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->success(['bookings' => $bookings]);
    }

    /**
     * Première étape de réservation : vérifie places et crédits
     */
    public function store(int $rideId): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        // Vérifier le rôle passager
        if (($_SERVER['AUTH_USER_ROLE'] ?? '') !== 'passager') {
            return $this->error('Seuls les passagers peuvent réserver', 403);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $stmt = $db->prepare('SELECT nb_place AS seats, prix_personne AS price FROM Covoiturage WHERE covoiturage_id = ?');
        $stmt->execute([$rideId]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trip) {
            return $this->error('Trajet introuvable', 404);
        }
        if ((int)$trip['seats'] <= 0) {
            return $this->error('Aucune place disponible', 409);
        }

        $service = new CreditService($db);
        $balance = $service->getBalance($userId);
        $price = (float)$trip['price'];
        if ($balance < $price) {
            return $this->error('Crédits insuffisants', 402);
        }

        return $this->success([
            'needConfirmation' => true,
            'price' => $price,
            'balance' => $balance
        ]);
    }

    /**
     * Confirmation finale de réservation : débite, crédite, met à jour le trajet
     */
    public function confirm(int $rideId): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        if (($_SERVER['AUTH_USER_ROLE'] ?? '') !== 'passager') {
            return $this->error('Seuls les passagers peuvent confirmer une réservation', 403);
        }

        $db = $this->app->getDatabase()->getMysqlConnection();
        $db->beginTransaction();
        try {
            // Vérifier existance et places
            $stmt = $db->prepare(
                'SELECT c.nb_place AS seats, c.prix_personne AS price, v.utilisateur_id AS driver_id 
                 FROM Covoiturage c 
                 JOIN Voiture v ON c.voiture_id = v.voiture_id 
                 WHERE c.covoiturage_id = ?'
            );
            $stmt->execute([$rideId]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$info) {
                return $this->error('Trajet introuvable', 404);
            }
            if ((int)$info['seats'] <= 0) {
                return $this->error('Aucune place disponible', 409);
            }

            $price = (float)$info['price'];
            $driverId = (int)$info['driver_id'];

            $service = new CreditService($db);
            // Débit du passager
            $service->debitAccount($userId, $price, 'achat_trajet', "Réservation trajet #$rideId");

            // Crédit du conducteur (net de commission)
            $commission = (float)config('credits.commission_fee', 2);
            $net = $price - $commission;
            if ($net > 0) {
                $service->creditAccount($driverId, $net, 'achat_trajet', "Gain trajet #$rideId");
            }

            // Met à jour le nombre de places
            $update = $db->prepare('UPDATE Covoiturage SET nb_place = nb_place - 1 WHERE covoiturage_id = ?');
            $update->execute([$rideId]);

            // Enregistre la participation confirmée
            $stmt2 = $db->prepare('SELECT statut_id FROM StatutParticipation WHERE libelle = ?');
            $stmt2->execute(['confirmé']);
            $statutId = (int)$stmt2->fetchColumn();
            $stmt3 = $db->prepare('INSERT INTO Participation (utilisateur_id, covoiturage_id, date_reservation, statut_id) VALUES (?, ?, NOW(), ?)');
            $stmt3->execute([$userId, $rideId, $statutId]);

            $db->commit();

            $newBalance = $service->getBalance($userId);
            return $this->success([
                'ride_id' => $rideId,
                'new_balance' => $newBalance
            ], 'Réservation confirmée');
        } catch (\Exception $e) {
            $db->rollBack();
            return $this->error('Erreur lors de la confirmation : ' . $e->getMessage(), 500);
        }
    }

    /**
     * Annulation de réservation (future implémentation)
     */
    public function destroy(int $bookingId): array
    {
        return $this->error('Annulation de réservation non implémentée', 501);
    }
} 