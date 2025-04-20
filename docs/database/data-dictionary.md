# Dictionnaire de données EcoRide

Ce document présente les tables, colonnes et relations du schéma de données EcoRide optimisé pour l'écoconception et normalisé en 3FN.

## Tables principales

### Utilisateur
| Colonne            | Type          | Description                          | Contraintes       |
|--------------------|---------------|--------------------------------------|-------------------|
| utilisateur_id     | INT           | Identifiant unique                   | PK, Auto_Increment |
| nom                | VARCHAR(50)   | Nom de famille                       | NOT NULL          |
| prenom             | VARCHAR(50)   | Prénom                               | NOT NULL          |
| email              | VARCHAR(100)  | Adresse mail                         | NOT NULL, UNIQUE  |
| mot_passe          | VARCHAR(255)  | Mot de passe haché                   | NOT NULL          |
| telephone          | VARCHAR(20)   | Numéro de téléphone                  |                   |
| adresse_id         | INT           | Référence à l'adresse                | FK → Adresse      |
| date_naissance     | DATE          | Date de naissance                    |                   |
| photo_path         | VARCHAR(255)  | Chemin vers la photo de profil       |                   |
| pseudo             | VARCHAR(50)   | Identifiant public                   | UNIQUE            |
| date_creation      | DATETIME      | Date de création du compte           | DEFAULT CURRENT_TIMESTAMP |
| derniere_connexion | DATETIME      | Date de dernière connexion           |                   |

**Remarques :**
- Optimisation écologique : stockage de chemins vers les photos plutôt que les images elles-mêmes (BLOB)
- Index sur email et pseudo pour optimiser les recherches et connexions

### Adresse
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| adresse_id     | INT           | Identifiant unique                   | PK, Auto_Increment |
| rue            | VARCHAR(100)  | Nom de la rue et numéro              | NOT NULL          |
| ville          | VARCHAR(50)   | Ville                                | NOT NULL          |
| code_postal    | VARCHAR(10)   | Code postal                          | NOT NULL          |
| pays           | VARCHAR(50)   | Pays                                 | DEFAULT 'France'  |
| coordonnees_gps| VARCHAR(50)   | Coordonnées GPS                      |                   |

**Remarques :**
- Normalisation des adresses pour éviter la duplication et faciliter les mises à jour
- Les coordonnées GPS permettent de calculer les distances pour les empreintes carbone

### Lieu
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| lieu_id        | INT           | Identifiant unique                   | PK, Auto_Increment |
| nom            | VARCHAR(100)  | Nom du lieu                          | NOT NULL          |
| adresse_id     | INT           | Référence à l'adresse                | FK → Adresse      |

**Remarques :**
- Normalisation des lieux de départ/arrivée pour faciliter la réutilisation

### Covoiturage
| Colonne             | Type          | Description                          | Contraintes       |
|---------------------|---------------|--------------------------------------|-------------------|
| covoiturage_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| lieu_depart_id      | INT           | Référence au lieu de départ          | FK → Lieu, NOT NULL |
| lieu_arrivee_id     | INT           | Référence au lieu d'arrivée          | FK → Lieu, NOT NULL |
| date_depart         | DATE          | Date de départ                       | NOT NULL          |
| heure_depart        | TIME          | Heure de départ                      | NOT NULL          |
| date_arrivee        | DATE          | Date d'arrivée                       | NOT NULL          |
| heure_arrivee       | TIME          | Heure d'arrivée                      | NOT NULL          |
| statut_id           | INT           | Référence au statut                  | FK → StatutCovoiturage, NOT NULL |
| nb_place            | INT           | Nombre de places initiales           | NOT NULL, > 0     |
| prix_personne       | DECIMAL(6,2)  | Prix par passager                    | NOT NULL, >= 0    |
| voiture_id          | INT           | Référence au véhicule utilisé        | FK → Voiture, NOT NULL |
| date_creation       | DATETIME      | Date de création de l'annonce        | DEFAULT CURRENT_TIMESTAMP |
| empreinte_carbone   | DECIMAL(8,2)  | Empreinte carbone estimée            |                   |

**Remarques :**
- Index composites sur date_depart + heure_depart pour optimiser les recherches chronologiques
- Index sur lieu_depart_id et lieu_arrivee_id pour les recherches géographiques

### Participation
| Colonne             | Type          | Description                          | Contraintes       |
|---------------------|---------------|--------------------------------------|-------------------|
| utilisateur_id      | INT           | Référence à l'utilisateur            | PK, FK → Utilisateur |
| covoiturage_id      | INT           | Référence au covoiturage             | PK, FK → Covoiturage |
| date_reservation    | DATETIME      | Date et heure de réservation         | NOT NULL          |
| statut_id           | INT           | Référence au statut                  | FK → StatutParticipation, NOT NULL |

**Remarques :**
- Table associative avec clé primaire composite
- La cascade de suppression (ON DELETE CASCADE) permet de nettoyer automatiquement les participations

### Avis
| Colonne             | Type          | Description                          | Contraintes       |
|---------------------|---------------|--------------------------------------|-------------------|
| avis_id             | INT           | Identifiant unique                   | PK, Auto_Increment |
| utilisateur_id      | INT           | Référence à l'auteur                 | FK → Utilisateur, NOT NULL |
| covoiturage_id      | INT           | Référence au trajet évalué           | FK → Covoiturage, NOT NULL |
| commentaire         | TEXT          | Texte de l'avis                      |                   |
| note                | TINYINT       | Note (1-5)                           | NOT NULL, BETWEEN 1 AND 5 |
| statut_id           | INT           | Référence au statut                  | FK → StatutAvis, NOT NULL |
| date_creation       | DATETIME      | Date de création de l'avis           | DEFAULT CURRENT_TIMESTAMP |

**Remarques :**
- Index sur note pour le tri par évaluation
- Index sur statut_id pour filtrer les avis publiés/en attente

## Tables de gestion des crédits

### CreditBalance
| Colonne             | Type          | Description                          | Contraintes       |
|---------------------|---------------|--------------------------------------|-------------------|
| utilisateur_id      | INT           | Référence à l'utilisateur            | PK, FK → Utilisateur |
| solde               | DECIMAL(8,2)  | Solde actuel                         | NOT NULL, DEFAULT 0, >= 0 |

**Remarques :**
- La contrainte CHECK garantit un solde non négatif
- La suppression en cascade nettoie automatiquement les soldes des utilisateurs supprimés

### CreditTransaction
| Colonne             | Type          | Description                          | Contraintes       |
|---------------------|---------------|--------------------------------------|-------------------|
| transaction_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| utilisateur_id      | INT           | Référence à l'utilisateur            | FK → Utilisateur, NOT NULL |
| montant             | DECIMAL(8,2)  | Montant (positif=crédit, négatif=débit) | NOT NULL      |
| type_id             | INT           | Référence au type de transaction     | FK → TypeTransaction, NOT NULL |
| date_transaction    | DATETIME      | Date et heure de la transaction      | DEFAULT CURRENT_TIMESTAMP |
| description         | VARCHAR(255)  | Description supplémentaire           |                   |

**Remarques :**
- Index sur date_transaction pour les recherches chronologiques
- Index sur type_id pour filtrer par type de transaction

## Tables de normalisation (référentielles)

### Role
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| role_id        | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(50)   | Nom du rôle                          | NOT NULL, UNIQUE  |

**Valeurs prédéfinies :** 'visiteur', 'passager', 'chauffeur', 'admin'

### Marque
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| marque_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(50)   | Nom de la marque                     | NOT NULL, UNIQUE  |

### Modele
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| modele_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| nom            | VARCHAR(50)   | Nom du modèle                        | NOT NULL          |
| marque_id      | INT           | Référence à la marque                | FK → Marque, NOT NULL |

### TypeEnergie
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| energie_id     | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(30)   | Type d'énergie                       | NOT NULL, UNIQUE  |

**Valeurs prédéfinies :** 'Électrique', 'Essence', 'Diesel', 'Hybride', 'GPL'

### StatutCovoiturage
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| statut_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(20)   | Nom du statut                        | NOT NULL, UNIQUE  |

**Valeurs prédéfinies :** 'planifié', 'en_cours', 'terminé', 'annulé'

### StatutParticipation
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| statut_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(20)   | Nom du statut                        | NOT NULL, UNIQUE  |

**Valeurs prédéfinies :** 'en_attente', 'confirmé', 'annulé'

### StatutAvis
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| statut_id      | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(20)   | Nom du statut                        | NOT NULL, UNIQUE  |

**Valeurs prédéfinies :** 'en_attente', 'publié', 'rejeté'

### TypeTransaction
| Colonne        | Type          | Description                          | Contraintes       |
|----------------|---------------|--------------------------------------|-------------------|
| type_id        | INT           | Identifiant unique                   | PK, Auto_Increment |
| libelle        | VARCHAR(30)   | Type de transaction                  | NOT NULL, UNIQUE  |

**Valeurs prédéfinies :** 'initial', 'achat_trajet', 'bonus', 'annulation', 'autre' 