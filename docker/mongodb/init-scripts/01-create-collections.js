// Script d'initialisation de la base MongoDB pour EcoRide
print('Initialisation des collections MongoDB pour EcoRide...');

// Connexion à la base ecoride_nosql
const ecoride = db.getSiblingDB('ecoride_nosql');

// Création de la collection "reviews" avec validation
ecoride.createCollection('reviews', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['user_id', 'trip_id', 'rating', 'created_at'],
            properties: {
                user_id: { bsonType: 'int' },
                trip_id: { bsonType: 'int' },
                rating: {
                    bsonType: 'int',
                    minimum: 1,
                    maximum: 5
                },
                comment: { bsonType: 'string' },
                created_at: { bsonType: 'date' },
                updated_at: { bsonType: 'date' }
            }
        }
    }
});

// Création de la collection "user_preferences" avec validation
ecoride.createCollection('user_preferences', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['user_id'],
            properties: {
                user_id: { bsonType: 'int' },
                preferences: { bsonType: 'object' },
                updated_at: { bsonType: 'date' }
            }
        }
    }
});

// Création de la collection "activity_logs" avec validation
ecoride.createCollection('activity_logs', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['user_id', 'action', 'timestamp'],
            properties: {
                user_id: { bsonType: 'int' },
                action: { bsonType: 'string' },
                details: { bsonType: 'object' },
                timestamp: { bsonType: 'date' }
            }
        }
    }
});

// Création de la collection "rides" avec validation
ecoride.createCollection('rides', {
    validator: {
        $jsonSchema: {
            bsonType: 'object',
            required: ['userId', 'vehicleId', 'startLocation', 'endLocation', 'startTime', 'status'],
            properties: {
                userId: { bsonType: 'int' },
                vehicleId: { bsonType: 'int' },
                startLocation: { bsonType: 'string' },
                endLocation: { bsonType: 'string' },
                startTime: { bsonType: 'date' },
                endTime: { bsonType: 'date' },
                distance: { bsonType: 'double' },
                duration: { bsonType: 'int' },
                cost: { bsonType: 'double' },
                status: { bsonType: 'string' },
                waypoints: { bsonType: 'array' },
                metadata: { bsonType: 'object' }
            }
        }
    }
});

// Créer les index pour améliorer les performances
ecoride.reviews.createIndex({ user_id: 1 });
ecoride.reviews.createIndex({ trip_id: 1 });
ecoride.reviews.createIndex({ rating: -1 });
ecoride.reviews.createIndex({ created_at: -1 });
ecoride.reviews.createIndex({ comment: 'text' });

ecoride.user_preferences.createIndex({ user_id: 1 }, { unique: true });

ecoride.activity_logs.createIndex({ user_id: 1 });
ecoride.activity_logs.createIndex({ timestamp: -1 });

// Créer les index pour la collection rides
ecoride.rides.createIndex({ userId: 1 });
ecoride.rides.createIndex({ startTime: -1 });
ecoride.rides.createIndex({ status: 1 });
ecoride.rides.createIndex({ vehicleId: 1 });

// Insérer quelques données de test dans reviews
ecoride.reviews.insertMany([
    {
        user_id: 1,
        trip_id: 1,
        rating: 5,
        comment: 'Conducteur très sympathique et ponctuel!',
        created_at: new Date()
    },
    {
        user_id: 2,
        trip_id: 1,
        rating: 4,
        comment: 'Bonne expérience, voiture propre.',
        created_at: new Date()
    },
    {
        user_id: 1,
        trip_id: 2,
        rating: 3,
        comment: 'Trajet correct, mais un peu long.',
        created_at: new Date()
    }
]);

// Insérer des préférences utilisateur de test
ecoride.user_preferences.insertMany([
    {
        user_id: 1,
        preferences: {
            music: true,
            temperature: 22,
            conversation: 'occasionnelle',
            smoking: false
        },
        updated_at: new Date()
    },
    {
        user_id: 2,
        preferences: {
            music: false,
            temperature: 20,
            conversation: 'aucune',
            smoking: false
        },
        updated_at: new Date()
    }
]);

// Insérer quelques logs d'activité
ecoride.activity_logs.insertMany([
    {
        user_id: 1,
        action: 'login',
        details: { ip: '192.168.1.1', device: 'mobile' },
        timestamp: new Date()
    },
    {
        user_id: 1,
        action: 'book_trip',
        details: { trip_id: 1, seats: 2 },
        timestamp: new Date()
    },
    {
        user_id: 2,
        action: 'login',
        details: { ip: '192.168.1.2', device: 'desktop' },
        timestamp: new Date()
    }
]);

// Insérer quelques trajets de test
ecoride.rides.insertMany([
    {
        userId: 1,
        vehicleId: 1,
        startLocation: "5 Rue du Commerce, Paris",
        endLocation: "15 Avenue des Champs-Élysées, Paris",
        startTime: new Date(),
        endTime: new Date(new Date().getTime() + 3600000), // +1 heure
        distance: 7.5,
        duration: 1800, // 30 minutes en secondes
        cost: 12.50,
        status: "completed",
        waypoints: [
            "10 Boulevard Haussmann, Paris"
        ],
        metadata: {
            trafficCondition: "fluide",
            weatherCondition: "ensoleillé"
        }
    },
    {
        userId: 1,
        vehicleId: 2,
        startLocation: "2 Rue de Rivoli, Paris",
        endLocation: "Tour Eiffel, Paris",
        startTime: new Date(new Date().getTime() + 86400000), // demain
        distance: 5.2,
        duration: 1200, // 20 minutes en secondes
        status: "planned",
        waypoints: [],
        metadata: {}
    }
]);

print('Initialisation de MongoDB terminée avec succès!');
print('Collections créées: reviews, user_preferences, activity_logs, rides'); 