-- Script d'ajout d'index pour l'optimisation des requêtes de recherche de trajets

-- Index pour la recherche par lieu de départ
CREATE INDEX IF NOT EXISTS idx_lieu_nom ON Lieu(nom);

-- Index pour la recherche combinée par date et disponibilité
CREATE INDEX IF NOT EXISTS idx_covoiturage_date_places ON Covoiturage(date_depart, nb_place);

-- Index pour le tri par prix
CREATE INDEX IF NOT EXISTS idx_covoiturage_prix ON Covoiturage(prix_personne);

-- Index pour la recherche par statut de covoiturage
CREATE INDEX IF NOT EXISTS idx_covoiturage_statut_id ON Covoiturage(statut_id);

-- Index pour la recherche par empreinte carbone (pour des fonctionnalités futures liées à l'éco-responsabilité)
CREATE INDEX IF NOT EXISTS idx_covoiturage_empreinte ON Covoiturage(empreinte_carbone);

-- Index pour la recherche de notes moyennes (pour les filtres potentiels sur la réputation du conducteur)
CREATE INDEX IF NOT EXISTS idx_avis_note_covoiturage ON Avis(covoiturage_id, note);

-- Index pour optimiser les comptages de places réservées
CREATE INDEX IF NOT EXISTS idx_participation_statut_covoiturage ON Participation(covoiturage_id, statut_id);

-- Commentaire explicatif pour les DBA
/*
Ces index ont été conçus pour optimiser les requêtes du moteur de recherche de trajets
qui filtre sur :
- Lieux de départ et d'arrivée
- Date du trajet
- Disponibilité des places
- Prix

Les index composites ont été privilégiés là où des filtres multiples sont appliqués simultanément.
*/ 