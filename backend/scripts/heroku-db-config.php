<?php
/**
 * Script de configuration automatique de la base de données pour Heroku
 * Parse les URLs de bases de données et configure les variables d'environnement
 */

echo "🔧 Configuration automatique de la base de données pour Heroku...\n";

// Parse JAWSDB_URL pour MySQL
$jawsdbUrl = getenv('JAWSDB_URL');
$databaseUrl = getenv('DATABASE_URL');

// Utiliser JAWSDB_URL ou DATABASE_URL
$dbUrl = $jawsdbUrl ?: $databaseUrl;

if ($dbUrl) {
    echo "📊 URL de base de données détectée: " . substr($dbUrl, 0, 30) . "...\n";
    
    // Parser l'URL MySQL
    $urlParts = parse_url($dbUrl);
    
    if ($urlParts) {
        $host = $urlParts['host'];
        $port = $urlParts['port'] ?? 3306;
        $database = ltrim($urlParts['path'], '/');
        $username = $urlParts['user'];
        $password = $urlParts['pass'];
        
        // Définir les variables d'environnement
        putenv("DB_CONNECTION=mysql");
        putenv("DB_HOST=$host");
        putenv("DB_PORT=$port");
        putenv("DB_DATABASE=$database");
        putenv("DB_USERNAME=$username");
        putenv("DB_PASSWORD=$password");
        
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_HOST'] = $host;
        $_ENV['DB_PORT'] = $port;
        $_ENV['DB_DATABASE'] = $database;
        $_ENV['DB_USERNAME'] = $username;
        $_ENV['DB_PASSWORD'] = $password;
        
        echo "✅ Configuration MySQL mise à jour\n";
        echo "   Host: $host:$port\n";
        echo "   Database: $database\n";
        echo "   User: $username\n";
        
        // Tester la connexion
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            echo "✅ Test de connexion MySQL réussi\n";
            
            // Initialiser les tables si nécessaire
            echo "🔧 Initialisation des tables...\n";
            initializeTables($pdo);
            
        } catch (PDOException $e) {
            echo "❌ Erreur de connexion MySQL: " . $e->getMessage() . "\n";
        }
    } else {
        echo "❌ Impossible de parser l'URL de base de données\n";
    }
} else {
    echo "⚠️ Aucune URL de base de données trouvée\n";
    echo "Variables disponibles:\n";
    echo "- JAWSDB_URL: " . (getenv('JAWSDB_URL') ? 'définie' : 'non définie') . "\n";
    echo "- DATABASE_URL: " . (getenv('DATABASE_URL') ? 'définie' : 'non définie') . "\n";
}

/**
 * Initialise les tables de base de données
 */
function initializeTables($pdo) {
    try {
        // Table des utilisateurs
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Utilisateur (
                utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(100) NOT NULL,
                prenom VARCHAR(100),
                email VARCHAR(255) UNIQUE NOT NULL,
                pseudo VARCHAR(50) UNIQUE,
                mot_passe VARCHAR(255) NOT NULL,
                photo_path VARCHAR(500),
                date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                confirmed TINYINT(1) DEFAULT 0,
                INDEX (email),
                INDEX (pseudo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des rôles
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Role (
                role_id INT AUTO_INCREMENT PRIMARY KEY,
                libelle VARCHAR(50) UNIQUE NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table de liaison utilisateur-rôle
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Possede (
                utilisateur_id INT,
                role_id INT,
                PRIMARY KEY (utilisateur_id, role_id),
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES Role(role_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des confirmations d'utilisateur
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_confirmations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                utilisateur_id INT NOT NULL,
                token VARCHAR(64) UNIQUE NOT NULL,
                expires_at DATETIME NOT NULL,
                is_used TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
                INDEX (token),
                INDEX (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des types d'énergie
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS TypeEnergie (
                energie_id INT AUTO_INCREMENT PRIMARY KEY,
                libelle VARCHAR(50) UNIQUE NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des véhicules
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Voiture (
                voiture_id INT AUTO_INCREMENT PRIMARY KEY,
                marque VARCHAR(100) NOT NULL,
                modele VARCHAR(100) NOT NULL,
                annee INT,
                immatriculation VARCHAR(20) UNIQUE NOT NULL,
                couleur VARCHAR(50),
                places INT DEFAULT 5,
                energie_id INT,
                utilisateur_id INT NOT NULL,
                FOREIGN KEY (energie_id) REFERENCES TypeEnergie(energie_id),
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
                INDEX (utilisateur_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des covoiturages
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Covoiturage (
                covoiturage_id INT AUTO_INCREMENT PRIMARY KEY,
                ville_depart VARCHAR(255) NOT NULL,
                ville_destination VARCHAR(255) NOT NULL,
                date_depart DATE NOT NULL,
                heure_depart TIME NOT NULL,
                places_disponibles INT NOT NULL,
                prix_personne DECIMAL(8,2) NOT NULL,
                description TEXT,
                utilisateur_id INT NOT NULL,
                voiture_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
                FOREIGN KEY (voiture_id) REFERENCES Voiture(voiture_id),
                INDEX (ville_depart),
                INDEX (ville_destination),
                INDEX (date_depart),
                INDEX (utilisateur_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des statuts de participation
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS StatutParticipation (
                statut_id INT AUTO_INCREMENT PRIMARY KEY,
                libelle VARCHAR(50) UNIQUE NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des participations (réservations)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS Participation (
                participation_id INT AUTO_INCREMENT PRIMARY KEY,
                utilisateur_id INT NOT NULL,
                covoiturage_id INT NOT NULL,
                statut_id INT NOT NULL,
                date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
                FOREIGN KEY (covoiturage_id) REFERENCES Covoiturage(covoiturage_id) ON DELETE CASCADE,
                FOREIGN KEY (statut_id) REFERENCES StatutParticipation(statut_id),
                UNIQUE KEY unique_participation (utilisateur_id, covoiturage_id),
                INDEX (covoiturage_id),
                INDEX (statut_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des types de transaction
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS TypeTransaction (
                type_id INT AUTO_INCREMENT PRIMARY KEY,
                libelle VARCHAR(50) UNIQUE NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des soldes de crédit
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS CreditBalance (
                utilisateur_id INT PRIMARY KEY,
                solde DECIMAL(10,2) DEFAULT 0.00,
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Table des transactions de crédit
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS CreditTransaction (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                utilisateur_id INT NOT NULL,
                montant DECIMAL(10,2) NOT NULL,
                type_id INT NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (utilisateur_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE,
                FOREIGN KEY (type_id) REFERENCES TypeTransaction(type_id),
                INDEX (utilisateur_id),
                INDEX (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        echo "✅ Tables initialisées avec succès\n";

        // Insérer les données de base
        echo "🔧 Insertion des données de base...\n";

        // Rôles de base
        $pdo->exec("INSERT IGNORE INTO Role (libelle) VALUES ('visiteur'), ('passager'), ('chauffeur'), ('admin')");

        // Types d'énergie
        $pdo->exec("INSERT IGNORE INTO TypeEnergie (libelle) VALUES ('Essence'), ('Diesel'), ('Électrique'), ('Hybride'), ('GPL')");

        // Statuts de participation
        $pdo->exec("INSERT IGNORE INTO StatutParticipation (libelle) VALUES ('en_attente'), ('confirmee'), ('annulee')");

        // Types de transaction
        $pdo->exec("INSERT IGNORE INTO TypeTransaction (libelle) VALUES ('initial'), ('achat'), ('vente'), ('transfert'), ('bonus')");

        // Vérifier les tables créées
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Tables créées: " . implode(', ', $tables) . "\n";
        
        // Auto-confirmer tous les utilisateurs existants
        autoConfirmExistingUsers($pdo);
        
        echo "✅ Initialisation des tables terminée\n";
    } catch (PDOException $e) {
        echo "❌ Erreur lors de la création des tables: " . $e->getMessage() . "\n";
    }
}

/**
 * Auto-confirme tous les utilisateurs existants
 */
function autoConfirmExistingUsers($pdo) {
    try {
        echo "🔄 Auto-confirmation des utilisateurs existants...\n";
        
        // Mettre à jour tous les utilisateurs pour les confirmer
        $stmt = $pdo->prepare("UPDATE Utilisateur SET confirmed = 1 WHERE confirmed = 0");
        $stmt->execute();
        $confirmedCount = $stmt->rowCount();
        
        if ($confirmedCount > 0) {
            echo "✅ $confirmedCount utilisateur(s) confirmé(s) automatiquement\n";
        }
        
        // Donner le rôle passager à tous les utilisateurs qui n'ont que le rôle visiteur
        $stmt = $pdo->prepare("
            SELECT u.utilisateur_id 
            FROM Utilisateur u
            WHERE u.utilisateur_id NOT IN (
                SELECT p.utilisateur_id 
                FROM Possede p 
                JOIN Role r ON p.role_id = r.role_id 
                WHERE r.libelle = 'passager'
            )
        ");
        $stmt->execute();
        $usersWithoutPassenger = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($usersWithoutPassenger)) {
            // Récupérer l'ID du rôle passager
            $stmt = $pdo->prepare("SELECT role_id FROM Role WHERE libelle = 'passager'");
            $stmt->execute();
            $passagerRoleId = $stmt->fetchColumn();
            
            if ($passagerRoleId) {
                foreach ($usersWithoutPassenger as $userId) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO Possede (utilisateur_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$userId, $passagerRoleId]);
                }
                $passagerCount = count($usersWithoutPassenger);
                echo "✅ Rôle 'passager' attribué à $passagerCount utilisateur(s)\n";
            }
        }
        
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de l'auto-confirmation: " . $e->getMessage() . "\n";
    }
}

echo "🎉 Configuration de la base de données terminée\n";
?> 