-- 11_add_permissions_and_role_change_log.sql
-- Ajout de la colonne description à la table Role
ALTER TABLE Role
ADD COLUMN description VARCHAR(255) NULL AFTER libelle;

-- Table des permissions
CREATE TABLE IF NOT EXISTS Permission (
    permission_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Association rôle ↔ permission (N:N)
CREATE TABLE IF NOT EXISTS RolePermission (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES Role(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES Permission(permission_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table de logs des changements de rôle
CREATE TABLE IF NOT EXISTS RoleChangeLog (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    user_id INT NOT NULL,
    old_roles TEXT NOT NULL,
    new_roles TEXT NOT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES Utilisateur(utilisateur_id),
    FOREIGN KEY (user_id) REFERENCES Utilisateur(utilisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 