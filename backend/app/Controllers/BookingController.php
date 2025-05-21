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
            'SELECT p.covoiturage_id AS ride_id, p.date_reservation AS reserved_at, sp.libelle AS status,
                   c.ville_depart AS departure, c.ville_destination AS destination, 
                   c.date_depart, c.heure_depart AS departureTime,
                   c.prix_personne AS price
             FROM Participation p 
             JOIN StatutParticipation sp ON p.statut_id = sp.statut_id 
             JOIN Covoiturage c ON p.covoiturage_id = c.covoiturage_id
             WHERE p.utilisateur_id = ?
             ORDER BY p.date_reservation DESC'
        );
        $stmt->execute([$userId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Retourner les réservations directement dans la réponse
        // pour faciliter la compatibilité avec le frontend
        return $this->success($bookings);
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
            'balance' => $balance,
            'ride_id' => $rideId
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
            
            // Vérifier le solde avant la transaction
            $balanceBefore = $service->getBalance($userId);
            
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

            // Vérifier le solde après la transaction
            $balanceAfter = $service->getBalance($userId);
            
            return $this->success([
                'ride_id' => $rideId,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'new_balance' => $balanceAfter,
                'price' => $price
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
    
    /**
     * Crée une réservation pour un trajet local
     * 
     * @return array
     */
    public function create(): array
    {
        $userId = (int) ($_SERVER['AUTH_USER_ID'] ?? 0);
        
        // Vérifier si l'utilisateur est authentifié
        if (!$userId) {
            return $this->error('Non authentifié', 401);
        }
        
        // Récupérer les données de la requête
        $data = $this->getJsonData();
        
        // Validation des données requises
        $requiredFields = ['ride_id', 'price', 'seats', 'departure', 'destination'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->error("Le champ '{$field}' est requis", 400);
            }
        }
        
        // Récupérer les informations
        $rideId = $data['ride_id'];
        $price = (float)$data['price'];
        $seats = (int)$data['seats'];
        $departure = $data['departure'];
        $destination = $data['destination'];
        $dateDepart = $data['date_depart'] ?? date('Y-m-d');
        $departureTime = $data['departureTime'] ?? '12:00';
        
        $db = $this->app->getDatabase()->getMysqlConnection();
        
        // Vérifier le rôle passager
        $roleStmt = $db->prepare(
            'SELECT COUNT(*) FROM Possede p 
             JOIN Role r ON p.role_id = r.role_id 
             WHERE p.utilisateur_id = ? AND LOWER(r.libelle) = ?'
        );
        $roleStmt->execute([$userId, 'passager']);
        
        if ((int)$roleStmt->fetchColumn() === 0) {
            return $this->error('Seuls les passagers peuvent réserver', 403);
        }
        
        // Vérifier le solde
        $service = new CreditService($db);
        $balance = $service->getBalance($userId);
        
        if ($balance < $price) {
            return $this->error('Crédits insuffisants', 402);
        }
        
        try {
            // ÉTAPE 1 : Déterminer s'il s'agit d'un trajet externe ou d'un trajet existant
            
            // Déterminer le statut de participation (confirmé)
            $statutStmt = $db->prepare('SELECT statut_id FROM StatutParticipation WHERE libelle = ?');
            $statutStmt->execute(['confirmé']);
            $statutParticipationId = (int)$statutStmt->fetchColumn();
            
            if (!$statutParticipationId) {
                // Créer le statut s'il n'existe pas - dans sa propre transaction
                try {
                    $db->beginTransaction();
                    $createStatutStmt = $db->prepare('INSERT INTO StatutParticipation (libelle) VALUES (?)');
                    $createStatutStmt->execute(['confirmé']);
                    $statutParticipationId = (int)$db->lastInsertId();
                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollBack();
                    throw new \Exception('Erreur lors de la création du statut: ' . $e->getMessage());
                }
            }
            
            $covoiturageId = 0;
            
            // Vérifier si c'est un ID numérique (trajet existant) ou une référence externe (nouveau trajet)
            $isExistingRide = is_numeric($rideId) && intval($rideId) > 0;
            
            if ($isExistingRide) {
                // Il s'agit d'un trajet existant, vérifier qu'il existe et qu'il a des places disponibles
                $checkRideStmt = $db->prepare('SELECT covoiturage_id, nb_place FROM Covoiturage WHERE covoiturage_id = ?');
                $checkRideStmt->execute([intval($rideId)]);
                $existingRide = $checkRideStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$existingRide) {
                    throw new \Exception('Le trajet demandé n\'existe pas');
                }
                
                if ($existingRide['nb_place'] <= 0) {
                    throw new \Exception('Aucune place disponible sur ce trajet');
                }
                
                // Utiliser l'ID du trajet existant
                $covoiturageId = intval($rideId);
            } else {
                // Pour les trajets externes (locaux), on crée une nouvelle entrée dans Covoiturage
                // en respectant la structure normalisée (lieu_depart_id, lieu_arrivee_id)
                try {
                    $db->beginTransaction();
                    
                    // Récupérer l'ID d'une voiture par défaut (la première)
                    $vehicleStmt = $db->prepare('SELECT voiture_id FROM Voiture LIMIT 1');
                    $vehicleStmt->execute();
                    $vehicleId = (int)$vehicleStmt->fetchColumn();
                    
                    if (!$vehicleId) {
                        // Si aucune voiture n'existe, utiliser l'ID 1 par défaut
                        $vehicleId = 1;
                    }
                    
                    // 1. Récupérer ou créer l'adresse de départ
                    $adresseDepartId = $this->getOrCreateAdresse($db, $departure);
                    
                    // 2. Récupérer ou créer l'adresse d'arrivée
                    $adresseArriveeId = $this->getOrCreateAdresse($db, $destination);
                    
                    // 3. Récupérer ou créer le lieu de départ
                    $lieuDepartId = $this->getOrCreateLieu($db, "Départ: " . $departure, $adresseDepartId);
                    
                    // 4. Récupérer ou créer le lieu d'arrivée
                    $lieuArriveeId = $this->getOrCreateLieu($db, "Arrivée: " . $destination, $adresseArriveeId);
                    
                    // 5. Récupérer le statut "planifié" (ou le premier disponible)
                    $statutStmt = $db->prepare('SELECT statut_id FROM StatutCovoiturage WHERE libelle = "planifié" LIMIT 1');
                    $statutStmt->execute();
                    $statutCovoiturageId = $statutStmt->fetchColumn();
                    
                    if (!$statutCovoiturageId) {
                        $statutStmt = $db->prepare('SELECT statut_id FROM StatutCovoiturage LIMIT 1');
                        $statutStmt->execute();
                        $statutCovoiturageId = $statutStmt->fetchColumn();
                        
                        if (!$statutCovoiturageId) {
                            // Utiliser 1 par défaut si aucun statut n'existe
                            $statutCovoiturageId = 1;
                        }
                    }
                    
                    // 6. Créer l'entrée Covoiturage pour le trajet local avec les bons IDs de lieu
                    $createRideStmt = $db->prepare(
                        'INSERT INTO Covoiturage 
                        (lieu_depart_id, lieu_arrivee_id, date_depart, heure_depart, 
                         date_arrivee, heure_arrivee, statut_id, nb_place, 
                         prix_personne, voiture_id, date_creation, empreinte_carbone) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)'
                    );
                    
                    // Calcul simple pour date et heure d'arrivée (1h plus tard par défaut)
                    $dateArrivee = $dateDepart;
                    $heureArrivee = date('H:i:s', strtotime($departureTime) + 3600);
                    $empreinteCarboneDefaut = 0.0; // Valeur par défaut
                    
                    $createRideStmt->execute([
                        $lieuDepartId,
                        $lieuArriveeId,
                        $dateDepart,
                        $departureTime,
                        $dateArrivee,
                        $heureArrivee,
                        $statutCovoiturageId,
                        $seats,
                        $price,
                        $vehicleId,
                        $empreinteCarboneDefaut
                    ]);
                    
                    $covoiturageId = (int)$db->lastInsertId();
                    $db->commit();
                } catch (\Exception $e) {
                    $db->rollBack();
                    throw new \Exception('Erreur lors de la création du trajet: ' . $e->getMessage());
                }
            }
            
            if (!$covoiturageId) {
                throw new \Exception('Impossible de créer le trajet correspondant');
            }
            
            // ÉTAPE 2 : Débiter le passager - CreditService gère sa propre transaction
            $service->debitAccount($userId, $price, 'achat_trajet', "Réservation trajet #{$rideId}");
            
            // ÉTAPE 3 : Créer la participation et mettre à jour le nombre de places
            $bookingId = 0;
            try {
                $db->beginTransaction();
                
                // 1. Créer la participation
                $createBookingStmt = $db->prepare(
                    'INSERT INTO Participation 
                    (utilisateur_id, covoiturage_id, date_reservation, statut_id) 
                    VALUES (?, ?, NOW(), ?)'
                );
                $createBookingStmt->execute([$userId, $covoiturageId, $statutParticipationId]);
                $bookingId = (int)$db->lastInsertId();
                
                // 2. Si c'est un trajet existant, décrémenter le nombre de places
                if ($isExistingRide) {
                    $updatePlacesStmt = $db->prepare(
                        'UPDATE Covoiturage 
                         SET nb_place = nb_place - 1 
                         WHERE covoiturage_id = ? AND nb_place > 0'
                    );
                    $updatePlacesStmt->execute([$covoiturageId]);
                    
                    // Si aucune ligne n'a été mise à jour (nb_place déjà à 0)
                    if ($updatePlacesStmt->rowCount() === 0) {
                        // On ne fait pas échouer la transaction, mais on log l'anomalie
                        error_log("Attention: Impossible de réduire le nombre de places pour le covoiturage #$covoiturageId");
                    }
                }
                
                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
                throw new \Exception('Erreur lors de la création de la participation: ' . $e->getMessage());
            }
            
            // Récupérer le nouveau solde
            $newBalance = $service->getBalance($userId);
            
            return $this->success([
                'id' => $bookingId,
                'ride_id' => $rideId,
                'covoiturage_id' => $covoiturageId,
                'departure' => $departure,
                'destination' => $destination,
                'date_depart' => $dateDepart,
                'departureTime' => $departureTime,
                'price' => $price,
                'status' => 'confirmé',
                'balance_before' => $balance,
                'new_balance' => $newBalance,
                'reserved_at' => date('Y-m-d H:i:s')
            ], 'Réservation créée avec succès');
            
        } catch (\Exception $e) {
            return $this->error('Erreur lors de la création de la réservation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Récupère ou crée une adresse et retourne son ID
     * 
     * @param \PDO $db Connexion à la base de données
     * @param string $ville Nom de la ville
     * @return int ID de l'adresse
     */
    private function getOrCreateAdresse(\PDO $db, string $ville): int
    {
        // Chercher si l'adresse existe déjà
        $stmt = $db->prepare('SELECT adresse_id FROM Adresse WHERE ville = ? LIMIT 1');
        $stmt->execute([$ville]);
        $adresseId = $stmt->fetchColumn();
        
        if ($adresseId) {
            return (int)$adresseId;
        }
        
        // Créer l'adresse si elle n'existe pas
        $stmt = $db->prepare('INSERT INTO Adresse (rue, ville, code_postal, pays) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            'N/A', // Rue par défaut
            $ville,
            '00000', // Code postal par défaut
            'France' // Pays par défaut
        ]);
        
        return (int)$db->lastInsertId();
    }
    
    /**
     * Récupère ou crée un lieu et retourne son ID
     * 
     * @param \PDO $db Connexion à la base de données
     * @param string $nom Nom du lieu
     * @param int $adresseId ID de l'adresse associée
     * @return int ID du lieu
     */
    private function getOrCreateLieu(\PDO $db, string $nom, int $adresseId): int
    {
        // Chercher si le lieu existe déjà avec cette adresse
        $stmt = $db->prepare('SELECT lieu_id FROM Lieu WHERE nom = ? AND adresse_id = ? LIMIT 1');
        $stmt->execute([$nom, $adresseId]);
        $lieuId = $stmt->fetchColumn();
        
        if ($lieuId) {
            return (int)$lieuId;
        }
        
        // Créer le lieu s'il n'existe pas
        $stmt = $db->prepare('INSERT INTO Lieu (nom, adresse_id) VALUES (?, ?)');
        $stmt->execute([$nom, $adresseId]);
        
        return (int)$db->lastInsertId();
    }
} 