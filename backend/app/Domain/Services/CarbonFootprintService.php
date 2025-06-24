<?php

namespace App\Domain\Services;

/**
 * Service pour calculer l'empreinte carbone
 * Centralise la logique de calcul CO2 pour éviter la duplication
 */
final class CarbonFootprintService
{
    /**
     * Émission moyenne d'une voiture en kg CO2/km
     */
    private const DEFAULT_EMISSION_PER_KM = 0.12; // 120g/km = 0.12kg/km

    /**
     * Calcule le CO2 économisé pour un trajet partagé
     *
     * @param float $distance Distance en kilomètres
     * @param int $passengers Nombre de passagers (hors conducteur)
     * @return float CO2 économisé en kg
     */
    public static function calculateTripSavings(float $distance, int $passengers): float
    {
        if ($distance <= 0 || $passengers <= 0) {
            return 0.0;
        }

        // CO2 économisé = distance * émission moyenne * nombre de voitures évitées
        return round($distance * self::DEFAULT_EMISSION_PER_KM * $passengers, 2);
    }

    /**
     * Calcule le CO2 économisé pour une réservation
     *
     * @param float $distance Distance du trajet en kilomètres
     * @param int $seatCount Nombre de places réservées
     * @return float CO2 économisé en kg
     */
    public static function calculateBookingSavings(float $distance, int $seatCount): float
    {
        if ($distance <= 0 || $seatCount <= 0) {
            return 0.0;
        }

        // CO2 économisé = distance * émission moyenne * nombre de places
        return round($distance * self::DEFAULT_EMISSION_PER_KM * $seatCount, 2);
    }

    /**
     * Calcule l'empreinte carbone totale d'un trajet
     *
     * @param float $distance Distance en kilomètres
     * @param int $totalPassengers Nombre total de personnes (conducteur inclus)
     * @return float Empreinte carbone par personne en kg
     */
    public static function calculatePerPersonFootprint(float $distance, int $totalPassengers): float
    {
        if ($distance <= 0 || $totalPassengers <= 0) {
            return 0.0;
        }

        $totalEmission = $distance * self::DEFAULT_EMISSION_PER_KM;
        return round($totalEmission / $totalPassengers, 2);
    }

    /**
     * Formate l'affichage du CO2 économisé
     *
     * @param float $co2Saved CO2 en kg
     * @return string Affichage formaté
     */
    public static function formatSavings(float $co2Saved): string
    {
        if ($co2Saved < 1) {
            return round($co2Saved * 1000) . ' g de CO2 économisés';
        }
        
        return round($co2Saved, 1) . ' kg de CO2 économisés';
    }
} 