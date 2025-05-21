-- Ajouter la colonne suspended et suspended_at à la table Utilisateur
ALTER TABLE Utilisateur
ADD COLUMN suspended TINYINT NOT NULL DEFAULT 0,
ADD COLUMN suspended_at DATETIME NULL;

-- Créer la table de logs pour les actions administratives sur les utilisateurs
CREATE TABLE IF NOT EXISTS UserActionLog (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    details JSON NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES Utilisateur(utilisateur_id) ON DELETE CASCADE
); 