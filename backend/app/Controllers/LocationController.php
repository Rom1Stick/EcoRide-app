<?php

namespace App\Controllers;

use PDO;
use Exception;

/**
 * Contrôleur pour la gestion des lieux
 */
class LocationController extends Controller
{
    /**
     * Recherche de lieux
     */
    public function search(): array
    {
        try {
            // Récupérer le terme de recherche
            $query = $_GET['q'] ?? '';
            
            if (empty($query) || strlen($query) < 2) {
                return $this->success([
                    'locations' => $this->getPopularLocationsData()
                ]);
            }
            
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Requête pour rechercher les lieux correspondants
            $stmt = $db->prepare(
                'SELECT lieu_id as id, nom 
                FROM Lieu 
                WHERE nom LIKE ? 
                ORDER BY 
                    CASE WHEN nom = ? THEN 1
                         WHEN nom LIKE ? THEN 2
                         ELSE 3
                    END,
                    nom ASC
                LIMIT 10'
            );
            
            $likeQuery = '%' . $query . '%';
            $startsWithQuery = $query . '%';
            
            $stmt->execute([$likeQuery, $query, $startsWithQuery]);
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $this->success([
                'locations' => $locations
            ]);
            
        } catch (Exception $e) {
            return $this->error('Une erreur est survenue lors de la recherche des lieux', 500);
        }
    }
    
    /**
     * Récupération des lieux populaires
     */
    public function getPopular(): array
    {
        try {
            return $this->success([
                'locations' => $this->getPopularLocationsData()
            ]);
        } catch (Exception $e) {
            return $this->error('Une erreur est survenue lors de la récupération des lieux populaires', 500);
        }
    }
    
    /**
     * Données des lieux populaires
     */
    private function getPopularLocationsData(): array
    {
        try {
            $db = $this->app->getDatabase()->getMysqlConnection();
            
            // Requête pour récupérer les lieux les plus utilisés dans les covoiturages
            $stmt = $db->prepare(
                'SELECT l.lieu_id as id, l.nom
                FROM Lieu l
                JOIN (
                    SELECT lieu_depart_id as lieu_id, COUNT(*) as count
                    FROM Covoiturage
                    GROUP BY lieu_depart_id
                    UNION ALL
                    SELECT lieu_arrivee_id as lieu_id, COUNT(*) as count
                    FROM Covoiturage
                    GROUP BY lieu_arrivee_id
                ) as usage ON l.lieu_id = usage.lieu_id
                GROUP BY l.lieu_id
                ORDER BY SUM(usage.count) DESC
                LIMIT 8'
            );
            
            $stmt->execute();
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Si aucun lieu n'est trouvé (ou pas assez), on complète avec des lieux par défaut
            if (count($locations) < 8) {
                $defaultLocations = $this->getDefaultLocations();
                
                // Filtrer les lieux par défaut pour ne pas avoir de doublons
                $existingIds = array_map(function($location) {
                    return $location['id'];
                }, $locations);
                
                foreach ($defaultLocations as $location) {
                    if (!in_array($location['id'], $existingIds) && count($locations) < 8) {
                        $locations[] = $location;
                    }
                }
            }
            
            return $locations;
            
        } catch (Exception $e) {
            // En cas d'erreur, on retourne des lieux par défaut
            return $this->getDefaultLocations();
        }
    }
    
    /**
     * Lieux par défaut (si aucun n'est trouvé en base)
     */
    private function getDefaultLocations(): array
    {
        return [
            ['id' => 1, 'nom' => 'Paris'],
            ['id' => 2, 'nom' => 'Lyon'],
            ['id' => 3, 'nom' => 'Marseille'],
            ['id' => 4, 'nom' => 'Bordeaux'],
            ['id' => 5, 'nom' => 'Lille'],
            ['id' => 6, 'nom' => 'Strasbourg'],
            ['id' => 7, 'nom' => 'Nantes'],
            ['id' => 8, 'nom' => 'Toulouse']
        ];
    }
} 