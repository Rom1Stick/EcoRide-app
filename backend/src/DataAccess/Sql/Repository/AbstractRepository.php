<?php

namespace App\DataAccess\Sql\Repository;

use PDO;
use PDOException;
use App\Core\Database;
use App\DataAccess\Exception\DataAccessException;

/**
 * Classe AbstractRepository
 * 
 * Implémente les fonctionnalités communes à tous les repositories
 */
abstract class AbstractRepository implements RepositoryInterface
{
    /**
     * Instance de PDO pour la connexion à la base de données
     * 
     * @var PDO
     */
    protected PDO $db;

    /**
     * Nom de la table dans la base de données
     * 
     * @var string
     */
    protected string $table;

    /**
     * Nom de classe de l'entité
     * 
     * @var string
     */
    protected string $entityClass;

    /**
     * Liste des colonnes
     * 
     * @var array
     */
    protected array $columns = [];

    /**
     * Constructeur
     * 
     * @param Database $database Instance de la classe Database pour la connexion
     */
    public function __construct(Database $database)
    {
        $this->db = $database->getMysqlConnection();
        $this->initRepository();
    }

    /**
     * Initialise les propriétés du repository
     * Cette méthode doit être implémentée par les classes enfants
     * pour définir le nom de la table, la classe d'entité et les colonnes
     * 
     * @return void
     */
    abstract protected function initRepository(): void;

    /**
     * Construit une entité à partir d'un objet de données
     * 
     * @param array $data Données pour construire l'entité
     * @return mixed Une instance de l'entité
     */
    abstract protected function buildEntity(array $data);

    /**
     * {@inheritDoc}
     */
    public function findById(int $id)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return $this->buildEntity($row);
            }
            
            return null;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la recherche de l'entité {$this->table} avec l'ID $id: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Recherche tous les enregistrements avec pagination
     * 
     * @param int $page Numéro de la page
     * @param int $limit Nombre d'enregistrements par page
     * @return array Liste des entités
     */
    public function findAll(int $page = 1, int $limit = 20): array
    {
        $offset = ($page - 1) * $limit;
        $query = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $this->buildEntity($row);
            }
            
            return $results;
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors de la récupération des entités {$this->table}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Compte le nombre total d'enregistrements dans la table
     * 
     * @return int Nombre total d'enregistrements
     */
    public function count(): int
    {
        $query = "SELECT COUNT(*) FROM {$this->table}";
        
        try {
            $stmt = $this->db->query($query);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new DataAccessException("Erreur lors du comptage des entités {$this->table}: " . $e->getMessage(), 0, $e);
        }
    }
} 