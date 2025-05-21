# API de recherche de trajets

## Endpoint

`GET /api/rides/search`

Cet endpoint permet de rechercher des trajets en fonction de différents critères comme le lieu de départ, d'arrivée, la date et d'autres options.

## Paramètres de requête

| Paramètre | Type | Obligatoire | Description |
|-----------|------|-------------|-------------|
| `departureLocation` | string | Oui | Lieu de départ du trajet |
| `arrivalLocation` | string | Oui | Lieu d'arrivée du trajet |
| `date` | string | Oui | Date du trajet (format: YYYY-MM-DD) |
| `departureTime` | string | Non | Heure de départ minimale (format: HH:MM) |
| `maxPrice` | number | Non | Prix maximum par personne |
| `sortBy` | string | Non | Critère de tri: `departureTime` (défaut) ou `price` |
| `page` | number | Non | Numéro de page pour la pagination (défaut: 1) |
| `limit` | number | Non | Nombre d'éléments par page (défaut: 10, max: 50) |

## Réponse

```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "departure": {
        "location": "Paris",
        "date": "2023-12-01",
        "time": "08:00:00"
      },
      "arrival": {
        "location": "Lyon",
        "date": "2023-12-01",
        "time": "12:00:00"
      },
      "price": 25.5,
      "seats": {
        "total": 4,
        "available": 2
      },
      "driver": {
        "id": 456,
        "username": "john_doe",
        "profilePicture": "/uploads/profiles/john_doe.jpg",
        "rating": 4.5
      },
      "vehicle": {
        "model": "Model 3",
        "brand": "Tesla",
        "energy": "Électrique"
      },
      "ecologicalImpact": {
        "carbonFootprint": 2.5
      }
    }
  ],
  "pagination": {
    "total": 42,
    "page": 1,
    "limit": 10,
    "pages": 5
  }
}
```

## Codes de statut

| Code | Description |
|------|-------------|
| 200 | Succès |
| 400 | Paramètres de requête invalides |
| 500 | Erreur interne du serveur |

## Exemples

### Requête

```
GET /api/rides/search?departureLocation=Paris&arrivalLocation=Lyon&date=2023-12-01
```

### Réponse

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": 42,
    "page": 1,
    "limit": 10,
    "pages": 5
  }
}
```

## Filtres avancés

### Recherche par plage de prix

Pour rechercher des trajets avec un prix maximum :

```
GET /api/rides/search?departureLocation=Paris&arrivalLocation=Lyon&date=2023-12-01&maxPrice=30
```

### Tri par prix

Pour trier les résultats par prix croissant :

```
GET /api/rides/search?departureLocation=Paris&arrivalLocation=Lyon&date=2023-12-01&sortBy=price
```

### Pagination avancée

Pour accéder à la page 2 avec 20 résultats par page :

```
GET /api/rides/search?departureLocation=Paris&arrivalLocation=Lyon&date=2023-12-01&page=2&limit=20
``` 