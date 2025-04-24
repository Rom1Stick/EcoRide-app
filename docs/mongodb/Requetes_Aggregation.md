# Requêtes d'Agrégation MongoDB - EcoRide

Ce document présente les requêtes d'agrégation MongoDB complexes utilisées dans l'application EcoRide et explique leur fonctionnement.

## Table des matières

1. [Introduction aux agrégations MongoDB](#introduction-aux-agrégations-mongodb)
2. [TripStatsService](#tripstatsservice)
   - [getTopGlobalDestinations](#gettopglobaldestinations)
   - [getTopGlobalOrigins](#gettopglobalorigins)
   - [getGlobalStats](#getglobalstats)
   - [getMonthlyTripTrend](#getmonthlytriptrend)
   - [analyzeByTimeAndDistance](#analyzebytimeanddistance)
3. [BookingStatsService](#bookingstatsservice)
   - [getTopGlobalDestinations](#gettopglobaldestinations-1)
   - [getGlobalStats](#getglobalstats-1)
   - [getMonthlyBookingTrend](#getmonthlybookingtrend)
4. [Optimisations et bonnes pratiques](#optimisations-et-bonnes-pratiques)
5. [Tests des agrégations](#tests-des-agrégations)

## Introduction aux agrégations MongoDB

Les agrégations MongoDB permettent de traiter les données à travers une série d'opérations (pipeline) afin d'obtenir des résultats transformés. C'est l'équivalent MongoDB des requêtes SQL complexes avec JOIN, GROUP BY et fonctions d'agrégation.

Un pipeline d'agrégation se compose d'étapes (`$match`, `$group`, `$project`, etc.) qui transforment les documents d'une collection en un résultat agrégé.

## TripStatsService

Le service `TripStatsService` gère les statistiques des trajets et propose plusieurs méthodes d'agrégation pour analyser les données.

### getTopGlobalDestinations

Cette méthode retourne les principales destinations de tous les conducteurs.

```php
public function getTopGlobalDestinations(int $limit = 10): array
{
    $pipeline = [
        ['$project' => ['top_destinations' => 1]],
        ['$unwind' => '$top_destinations'],
        ['$group' => [
            '_id' => '$top_destinations.city',
            'count' => ['$sum' => '$top_destinations.count']
        ]],
        ['$sort' => ['count' => -1]],
        ['$limit' => $limit],
        ['$project' => [
            '_id' => 0,
            'city' => '$_id',
            'count' => 1
        ]]
    ];
    
    $result = $this->collection->aggregate($pipeline)->toArray();
    return $result;
}
```

**Explication du pipeline** :
1. `$project` : Sélectionne uniquement le champ `top_destinations`
2. `$unwind` : Décompose le tableau `top_destinations` en documents individuels
3. `$group` : Regroupe par ville et somme les compteurs
4. `$sort` : Trie par nombre décroissant
5. `$limit` : Limite le nombre de résultats
6. `$project` : Réorganise les champs pour une meilleure lisibilité

### getTopGlobalOrigins

Similaire à `getTopGlobalDestinations`, cette méthode retourne les principales villes d'origine.

### getGlobalStats

Cette méthode calcule les statistiques globales pour tous les conducteurs.

```php
public function getGlobalStats(): array
{
    $pipeline = [
        ['$group' => [
            '_id' => null,
            'total_drivers' => ['$sum' => 1],
            'total_trips' => ['$sum' => '$total_trips'],
            'total_seats_offered' => ['$sum' => '$total_seats_offered'],
            'total_seats_booked' => ['$sum' => '$total_seats_booked'],
            'total_earnings' => ['$sum' => '$total_earnings'],
            'total_distance' => ['$sum' => '$total_distance'],
            'total_duration' => ['$sum' => '$total_duration'],
            'total_co2_saved' => ['$sum' => '$total_co2_saved'],
            'avg_occupancy_rate' => ['$avg' => '$average_occupancy_rate'],
            'trips_by_status' => ['$push' => '$trips_by_status'],
            'trips_by_day_of_week' => ['$push' => '$trips_by_day_of_week']
        ]],
        ['$project' => [
            '_id' => 0,
            'total_drivers' => 1,
            'total_trips' => 1,
            'total_seats_offered' => 1,
            'total_seats_booked' => 1,
            'total_earnings' => 1,
            'total_distance' => 1,
            'total_duration' => 1,
            'total_co2_saved' => 1,
            'avg_occupancy_rate' => 1,
            'trips_by_status' => 1,
            'trips_by_day_of_week' => 1
        ]]
    ];
    
    // ... traitement supplémentaire pour calculer les totaux par statut et jour
}
```

**Explication du pipeline** :
1. `$group` : Regroupe tous les documents (`_id: null`) et calcule les totaux pour chaque métrique
2. `$project` : Réorganise les champs pour une meilleure lisibilité

### getMonthlyTripTrend

Cette méthode analyse la tendance des trajets sur une période de plusieurs mois.

```php
public function getMonthlyTripTrend(int $months = 12): array
{
    // Construire la liste des mois récents
    $monthsList = [];
    $currentDate = new DateTime();
    
    for ($i = 0; $i < $months; $i++) {
        $date = clone $currentDate;
        $date->modify("-{$i} month");
        $monthsList[] = $date->format('Y-m');
    }
    
    // Récupérer les données pour ces mois
    $pipeline = [
        ['$project' => ['monthly_trip_history' => 1]],
        ['$project' => ['months' => ['$objectToArray' => '$monthly_trip_history']]],
        ['$unwind' => '$months'],
        ['$match' => ['months.k' => ['$in' => $monthsList]]],
        ['$group' => [
            '_id' => '$months.k',
            'trips' => ['$sum' => '$months.v.count'],
            'earnings' => ['$sum' => '$months.v.earnings']
        ]],
        ['$sort' => ['_id' => 1]],
        ['$project' => [
            '_id' => 0,
            'month' => '$_id',
            'trips' => 1,
            'earnings' => 1
        ]]
    ];
    
    // ... traitement supplémentaire pour compléter les mois manquants
}
```

**Explication du pipeline** :
1. `$project` : Sélectionne l'historique mensuel des trajets
2. `$project` + `$objectToArray` : Convertit l'objet historique en tableau de clé-valeur
3. `$unwind` : Décompose le tableau en documents individuels
4. `$match` : Filtre pour ne garder que les mois demandés
5. `$group` : Regroupe par mois et calcule les totaux
6. `$sort` : Trie par mois croissant
7. `$project` : Réorganise les champs pour une meilleure lisibilité

### analyzeByTimeAndDistance

Cette méthode complexe analyse les trajets par tranches horaires et distances.

```php
public function analyzeByTimeAndDistance(array $options = []): array
{
    // Tranches horaires et distances par défaut...
    
    $pipeline = [];
    
    // Étape 1: Filtre par période si spécifiée
    if ($startDate && $endDate) {
        $pipeline[] = ['$match' => [
            'updatedAt' => ['$gte' => $startDate, '$lte' => $endDate]
        ]];
    }
    
    // Étape 2: Décomposer l'historique mensuel pour analyse
    $pipeline[] = ['$project' => [
        'driver_id' => 1,
        'totalTrips' => 1,
        'totalDistance' => 1,
        'totalDuration' => 1,
        'totalEarnings' => 1,
        'averageOccupancyRate' => 1,
        'tripsByDayOfWeek' => 1,
        'monthlyHistory' => ['$objectToArray' => '$monthlyTripHistory']
    ]];
    
    // Étape 3: Dégrouper l'historique mensuel
    $pipeline[] = ['$unwind' => '$monthlyHistory'];
    
    // Étape 4: Préparer l'analyse par tranches horaires et distances
    $pipeline[] = ['$group' => [
        '_id' => null,
        'totalDrivers' => ['$addToSet' => '$driver_id'],
        'totalTrips' => ['$sum' => '$totalTrips'],
        'totalDistance' => ['$sum' => '$totalDistance'],
        'totalDuration' => ['$sum' => '$totalDuration'],
        'totalEarnings' => ['$sum' => '$totalEarnings'],
        'tripsByDayOfWeek' => ['$mergeObjects' => '$tripsByDayOfWeek'],
        'monthlyStats' => ['$push' => [
            'month' => '$monthlyHistory.k',
            'trips' => '$monthlyHistory.v.count',
            'earnings' => '$monthlyHistory.v.earnings'
        ]],
        'distanceStats' => ['$push' => [
            'distanceRange' => [
                '$switch' => [
                    'branches' => [
                        // Différentes branches pour catégoriser les distances
                        // ...
                    ],
                    'default' => 'very_long'
                ]
            ],
            'distance' => '$totalDistance',
            'trips' => '$totalTrips',
            'earnings' => '$totalEarnings'
        ]]
    ]];
    
    // Étape 5: Formater le résultat final
    $pipeline[] = ['$project' => [
        '_id' => 0,
        'summary' => [
            'totalDrivers' => ['$size' => '$totalDrivers'],
            'totalTrips' => '$totalTrips',
            'totalDistance' => ['$round' => ['$totalDistance', 2]],
            // ...
        ],
        'tripsByDayOfWeek' => '$tripsByDayOfWeek',
        'monthlyStats' => [
            '$sortArray' => [
                'input' => '$monthlyStats',
                'sortBy' => ['month' => 1]
            ]
        ],
        'distanceAnalysis' => [
            // Mappage des tranches de distance
            // ...
        ]
    ]];
    
    // ...
}
```

**Explication du pipeline** :
1. Filtrage optionnel par période
2. Projection et transformation des données pour l'analyse
3. Dégroupement des données mensuelle 
4. Regroupement et classification des données par distance
5. Projection finale avec formatage et tri

## BookingStatsService

Le service `BookingStatsService` gère les statistiques des réservations et propose également des méthodes d'agrégation.

### getTopGlobalDestinations

```php
public function getTopGlobalDestinations(int $limit = 10): array
{
    $pipeline = [
        ['$unwind' => '$top_destinations'],
        ['$group' => [
            '_id' => '$top_destinations.destination',
            'count' => ['$sum' => '$top_destinations.count']
        ]],
        ['$sort' => ['count' => -1]],
        ['$limit' => $limit],
        ['$project' => [
            '_id' => 0,
            'destination' => '$_id',
            'count' => 1
        ]]
    ];
    
    $result = $this->collection->aggregate($pipeline)->toArray();
    return $result;
}
```

### getGlobalStats

Similaire à la méthode du `TripStatsService` mais adaptée aux réservations.

### getMonthlyBookingTrend

Similaire à `getMonthlyTripTrend` mais pour les réservations.

## Optimisations et bonnes pratiques

1. **Indexation adaptée** : 
   - Index sur les champs utilisés dans les `$match` et `$sort`
   - Index composites pour les filtres multiples
   
2. **Projections ciblées** :
   - Utilisation de `$project` pour sélectionner uniquement les champs nécessaires
   - Réduction de la quantité de données traitées à chaque étape

3. **Agrégations incrémentales** :
   - Pour les statistiques récurrentes, stockage des résultats précalculés
   - Mise à jour incrémentale plutôt que recalcul complet

4. **Utilisation de la syntaxe PHP correcte** :
   - Utilisation de tableaux associatifs (`['field' => value]`) plutôt que d'objets JSON
   - Respect de la syntaxe PHP pour les opérateurs d'agrégation

## Tests des agrégations

Les tests unitaires pour les agrégations MongoDB sont désormais en place :

- `TripStatsServiceTest` : Tests des méthodes d'agrégation de statistiques de trajets
- `BookingStatsServiceTest` : Tests des méthodes d'agrégation de statistiques de réservations
- `GeoDataServiceTest` : Tests des méthodes géospatiales

Ces tests vérifient :
1. La structure correcte du pipeline d'agrégation
2. Le traitement adéquat des résultats
3. La gestion des cas limites et des erreurs

Exemple de test d'agrégation :

```php
public function testGetTopGlobalDestinations(): void
{
    // Données de test
    $expectedResult = [
        ['city' => 'Lyon', 'count' => 150],
        ['city' => 'Paris', 'count' => 100],
        ['city' => 'Marseille', 'count' => 75]
    ];
    
    // Configuration du mock pour simuler un résultat d'agrégation
    $cursorMock = $this->createMock(Cursor::class);
    $cursorMock->expects($this->once())
        ->method('toArray')
        ->willReturn($expectedResult);
    
    $this->collectionMock->expects($this->once())
        ->method('aggregate')
        ->willReturn($cursorMock);
    
    // Appel de la méthode testée
    $result = $this->tripStatsService->getTopGlobalDestinations(3);
    
    // Assertions
    $this->assertCount(3, $result);
    $this->assertEquals('Lyon', $result[0]['city']);
    $this->assertEquals(150, $result[0]['count']);
    $this->assertEquals('Paris', $result[1]['city']);
}
``` 