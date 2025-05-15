<?php

namespace App\DataAccess\Sql\Repository;

use PDO;
use PDOException;
use App\DataAccess\Sql\Entity\User;
use App\DataAccess\Exception\DataAccessException;

/**
 * Repository pour les utilisateurs
 * 
 * Gère l'accès et la manipulation des données utilisateurs dans la base de données MySQL
 */
class UserRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected function initRepository(): void
    {
        $this->table = 'utilisateur';
        $this->entityClass = User::class;
        $this->columns = [
            'id',
            'email',
            'password',
            'first_name',
            'last_name',
            'phone',
            'photo_path',
            'role',
            'created_at',
            'updated_at'
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function buildEntity(array $data): User
    {
        $user = new User();
        $user->setId((int)$data['id'])
             ->setEmail($data['email'])
             ->setPassword($data['password'])
             ->setFirstName($data['first_name'])
             ->setLastName($data['last_name'])
             ->setPhone($data['phone'] ?? null)
             ->setPhotoPath($data['photo_path'] ?? '/assets/images/Logo_EcoRide.svg')
             ->setRole($data['role']);

        // Conversion des dates
        $user->setCreatedAt(new \DateTime($data['created_at']));
        if (!empty($data['updated_at'])) {
            $user->setUpdatedAt(new \DateTime($data['updated_at']));
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function create($entity): int
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException("L'entité doit être une instance de User");
        }

        // Validation des données de base
        if (!$entity->isEmailValid()) {
            throw new \InvalidArgumentException("L'email n'est pas valide");
        }

        if (!$entity->isPhoneValid()) {
            throw new \InvalidArgumentException("Le numéro de téléphone n'est pas valide");
        }

        // Mise à jour de la date de création/mise à jour
        if ($entity->getCreatedAt() === null) {
            $entity->setCreatedAt(new \DateTime());
        }

        // S'assurer que l'image de profil par défaut est définie si aucune n'est spécifiée
        if ($entity->getPhotoPath() === null) {
            $entity->setPhotoPath('/assets/images/Logo_EcoRide.svg');
        }

        // Préparation des données
        $query = "INSERT INTO {$this->table} (
                    email, 
                    password, 
                    first_name, 
                    last_name, 
                    phone, 
                    photo_path,
                    role, 
                    created_at, 
                    updated_at
                ) VALUES (
                    :email, 
                    :password, 
                    :firstName, 
                    :lastName, 
                    :phone, 
                    :photoPath,
                    :role, 
                    :createdAt, 
                    :updatedAt
                )";

        try {
            $stmt = $this->db->prepare($query);
            
            $email = $entity->getEmail();
            $password = $entity->getPassword();
            $firstName = $entity->getFirstName();
            $lastName = $entity->getLastName();
            $phone = $entity->getPhone();
            $photoPath = $entity->getPhotoPath();
            $role = $entity->getRole();
            $createdAt = $entity->getCreatedAt()->format('Y-m-d H:i:s');
            $updatedAt = $entity->getUpdatedAt() ? $entity->getUpdatedAt()->format('Y-m-d H:i:s') : null;
            
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':firstName', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':lastName', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, $phone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':photoPath', $photoPath, $photoPath === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->bindParam(':createdAt', $createdAt, PDO::PARAM_STR);
            $stmt->bindParam(':updatedAt', $updatedAt, $updatedAt === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            
            $stmt->execute();
            
            $id = (int)$this->db->lastInsertId();
            $entity->setId($id);
            
            return $id;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la création de l'utilisateur : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function update($entity): bool
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException("L'entité doit être une instance de User");
        }

        // Validation des données de base
        if (!$entity->isEmailValid()) {
            throw new \InvalidArgumentException("L'email n'est pas valide");
        }

        if (!$entity->isPhoneValid()) {
            throw new \InvalidArgumentException("Le numéro de téléphone n'est pas valide");
        }

        if ($entity->getId() === null) {
            throw new \InvalidArgumentException("Impossible de mettre à jour un utilisateur sans ID");
        }

        // S'assurer que l'image de profil par défaut est définie si aucune n'est spécifiée
        if ($entity->getPhotoPath() === null) {
            $entity->setPhotoPath('/assets/images/Logo_EcoRide.svg');
        }

        // Mise à jour de la date de modification
        $entity->touch();

        // Préparation de la requête
        $query = "UPDATE {$this->table} SET 
                  email = :email,
                  password = :password,
                  first_name = :firstName,
                  last_name = :lastName,
                  phone = :phone,
                  photo_path = :photoPath,
                  role = :role,
                  updated_at = :updatedAt
                WHERE id = :id";

        try {
            $stmt = $this->db->prepare($query);
            
            $id = $entity->getId();
            $email = $entity->getEmail();
            $password = $entity->getPassword();
            $firstName = $entity->getFirstName();
            $lastName = $entity->getLastName();
            $phone = $entity->getPhone();
            $photoPath = $entity->getPhotoPath();
            $role = $entity->getRole();
            $updatedAt = $entity->getUpdatedAt()->format('Y-m-d H:i:s');
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':firstName', $firstName, PDO::PARAM_STR);
            $stmt->bindParam(':lastName', $lastName, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, $phone === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':photoPath', $photoPath, $photoPath === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            $stmt->bindParam(':updatedAt', $updatedAt, PDO::PARAM_STR);
            
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la mise à jour de l'utilisateur : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(int $id): bool
    {
        $query = "DELETE FROM {$this->table} WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la suppression de l'utilisateur : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Recherche un utilisateur par son email
     *
     * @param string $email Email de l'utilisateur à rechercher
     * @return User|null L'utilisateur trouvé ou null
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function findByEmail(string $email): ?User
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $this->buildEntity($row);
            }
            
            return null;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche de l'utilisateur par email : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Recherche des utilisateurs par nom ou prénom
     *
     * @param string $term Terme de recherche
     * @param int $page Page de résultats
     * @param int $limit Nombre de résultats par page
     * @return array Liste des utilisateurs correspondants
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function searchByName(string $term, int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $searchTerm = '%' . $term . '%';
        
        $query = "SELECT * FROM {$this->table} 
                 WHERE first_name LIKE :term 
                 OR last_name LIKE :term 
                 ORDER BY last_name, first_name 
                 LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $this->buildEntity($row);
            }
            
            return $results;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche d'utilisateurs par nom : " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Vérifie si un email est déjà utilisé par un autre utilisateur
     *
     * @param string $email Email à vérifier
     * @param int|null $excludeUserId ID de l'utilisateur à exclure (pour les mises à jour)
     * @return bool True si l'email est déjà utilisé, False sinon
     * @throws DataAccessException En cas d'erreur d'accès à la base de données
     */
    public function isEmailTaken(string $email, ?int $excludeUserId = null): bool
    {
        $query = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        
        if ($excludeUserId !== null) {
            $query .= " AND id != :excludeId";
        }
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            
            if ($excludeUserId !== null) {
                $stmt->bindParam(':excludeId', $excludeUserId, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la vérification de l'email : " . $e->getMessage(), 0, $e);
        }
    }
} 