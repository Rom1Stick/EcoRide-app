-- Script de mise en place de la base de données de test
-- Création des tables pour les tests unitaires

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS utilisateur;
SET FOREIGN_KEY_CHECKS = 1;

-- Table des utilisateurs pour les tests
CREATE TABLE utilisateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'ROLE_USER',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des véhicules pour les tests
CREATE TABLE vehicule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    marque VARCHAR(50) NOT NULL,
    modele VARCHAR(50) NOT NULL,
    annee INT,
    immatriculation VARCHAR(20) NOT NULL,
    energy_type VARCHAR(30),
    eco_score INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);

-- Table des trajets pour les tests
CREATE TABLE trip (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id INT NOT NULL,
    departure_location VARCHAR(255) NOT NULL,
    arrival_location VARCHAR(255) NOT NULL,
    departure_datetime DATETIME NOT NULL,
    arrival_datetime DATETIME,
    available_seats INT NOT NULL DEFAULT 1,
    price_per_seat DECIMAL(10,2) NOT NULL,
    vehicle_id INT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES utilisateur(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicule(id) ON DELETE SET NULL
);

-- Table des réservations pour les tests
CREATE TABLE booking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    passenger_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trip(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);

-- Table pour les tests de transactions
CREATE TABLE credit_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES utilisateur(id) ON DELETE CASCADE
);

-- Insertions de données de test
INSERT INTO utilisateur (email, first_name, last_name, password, phone, role) VALUES
('test@example.com', 'Test', 'User', '$2y$10$abcdefghijklmnopqrstuv.wxyz0123456789ABCDEFGHIJKLMNO', '0612345678', 'ROLE_USER'),
('admin@example.com', 'Admin', 'User', '$2y$10$abcdefghijklmnopqrstuv.wxyz0123456789ABCDEFGHIJKLMNO', '0612345679', 'ROLE_ADMIN'); 