<?php

namespace App\DataAccess\NoSql\Service;

use App\DataAccess\DataAccessException;
use App\DataAccess\NoSql\Model\Configuration;
use App\DataAccess\NoSql\MongoServiceInterface;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

/**
 * Service pour gérer les configurations système dans MongoDB
 */
class ConfigurationService extends AbstractMongoService
{
    /**
     * Nom de la collection
     */
    private const COLLECTION_NAME = 'configurations';
    
    /**
     * Collection MongoDB
     *
     * @var Collection
     */
    protected Collection $collection;
    
    /**
     * Constructeur
     *
     * @param MongoServiceInterface $mongoConnection
     */
    public function __construct(MongoServiceInterface $mongoConnection)
    {
        parent::__construct($mongoConnection);
    }
    
    /**
     * Initialiser le service
     */
    protected function initService(): void
    {
        $this->collection = $this->getCollection(self::COLLECTION_NAME);
        $this->ensureIndexes();
    }
    
    /**
     * Assurer la création des index nécessaires
     */
    private function ensureIndexes(): void
    {
        // Index unique sur le code + environnement
        $this->collection->createIndex(
            [
                'code' => 1,
                'environment' => 1
            ],
            ['unique' => true]
        );
        
        // Index sur la catégorie
        $this->collection->createIndex(['category' => 1]);
        
        // Index sur l'état d'activation
        $this->collection->createIndex(['active' => 1]);
    }
    
    /**
     * Sauvegarder une configuration
     *
     * @param Configuration $config
     * @return Configuration
     * @throws DataAccessException
     */
    public function save(Configuration $config): Configuration
    {
        try {
            if ($config->getId() === null) {
                // Nouvelle configuration
                $result = $this->collection->insertOne($config->jsonSerialize());
                if ($result->getInsertedCount() === 0) {
                    throw new DataAccessException("Échec de l'insertion de la configuration");
                }
                $config->setId((string)$result->getInsertedId());
            } else {
                // Mise à jour d'une configuration existante
                $data = $config->jsonSerialize();
                unset($data['_id']); // Pas de mise à jour de l'ID
                
                $result = $this->collection->updateOne(
                    ['_id' => new ObjectId($config->getId())],
                    ['$set' => $data]
                );
                
                if ($result->getModifiedCount() === 0 && $result->getMatchedCount() === 0) {
                    throw new DataAccessException("Configuration non trouvée ou non modifiée");
                }
            }
            
            return $config;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la sauvegarde de la configuration: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver une configuration par son ID
     *
     * @param mixed $id
     * @return Configuration|null
     * @throws DataAccessException
     */
    public function findById($id)
    {
        try {
            $result = $this->collection->findOne(['_id' => new ObjectId($id)]);
            
            if ($result === null) {
                return null;
            }
            
            return Configuration::fromArray((array)$result);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche de la configuration: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver une configuration par son code
     *
     * @param string $code
     * @param string $environment Environnement (prod, dev, test...)
     * @return Configuration|null
     * @throws DataAccessException
     */
    public function findByCode(string $code, string $environment = 'prod'): ?Configuration
    {
        try {
            $result = $this->collection->findOne([
                'code' => $code,
                'environment' => $environment
            ]);
            
            if ($result === null) {
                return null;
            }
            
            return Configuration::fromArray((array)$result);
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche de la configuration par code: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver ou créer une configuration
     *
     * @param string $code
     * @param mixed $defaultValue
     * @param string $description
     * @param string $environment
     * @return Configuration
     * @throws DataAccessException
     */
    public function findOrCreate(string $code, $defaultValue = null, string $description = '', string $environment = 'prod'): Configuration
    {
        $config = $this->findByCode($code, $environment);
        
        if ($config === null) {
            $config = new Configuration($code, $defaultValue, $description);
            $config->setEnvironment($environment);
            return $this->save($config);
        }
        
        return $config;
    }
    
    /**
     * Obtenir la valeur d'une configuration
     *
     * @param string $code
     * @param mixed $defaultValue
     * @param string $environment
     * @return mixed
     * @throws DataAccessException
     */
    public function getValue(string $code, $defaultValue = null, string $environment = 'prod')
    {
        $config = $this->findByCode($code, $environment);
        
        if ($config === null || !$config->isActive()) {
            return $defaultValue;
        }
        
        return $config->getValue();
    }
    
    /**
     * Mettre à jour la valeur d'une configuration
     *
     * @param string $code
     * @param mixed $value
     * @param string $environment
     * @param string $modifiedBy
     * @return Configuration
     * @throws DataAccessException
     */
    public function updateValue(string $code, $value, string $environment = 'prod', string $modifiedBy = 'system'): Configuration
    {
        $config = $this->findByCode($code, $environment);
        
        if ($config === null) {
            throw new DataAccessException("Configuration non trouvée: $code");
        }
        
        $config->setValue($value);
        $config->touch($modifiedBy);
        
        return $this->save($config);
    }
    
    /**
     * Activer ou désactiver une configuration
     *
     * @param string $code
     * @param bool $active
     * @param string $environment
     * @param string $modifiedBy
     * @return Configuration
     * @throws DataAccessException
     */
    public function setActive(string $code, bool $active, string $environment = 'prod', string $modifiedBy = 'system'): Configuration
    {
        $config = $this->findByCode($code, $environment);
        
        if ($config === null) {
            throw new DataAccessException("Configuration non trouvée: $code");
        }
        
        $config->setActive($active);
        $config->touch($modifiedBy);
        
        return $this->save($config);
    }
    
    /**
     * Trouver toutes les configurations par catégorie
     *
     * @param string $category
     * @param string $environment
     * @return Configuration[]
     * @throws DataAccessException
     */
    public function findByCategory(string $category, string $environment = 'prod'): array
    {
        try {
            $cursor = $this->collection->find([
                'category' => $category,
                'environment' => $environment
            ]);
            
            $result = [];
            foreach ($cursor as $document) {
                $result[] = Configuration::fromArray((array)$document);
            }
            
            return $result;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des configurations par catégorie: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Trouver toutes les configurations actives
     *
     * @param string $environment
     * @return Configuration[]
     * @throws DataAccessException
     */
    public function findActiveConfigurations(string $environment = 'prod'): array
    {
        try {
            $cursor = $this->collection->find([
                'active' => true,
                'environment' => $environment
            ]);
            
            $result = [];
            foreach ($cursor as $document) {
                $result[] = Configuration::fromArray((array)$document);
            }
            
            return $result;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche des configurations actives: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer une configuration
     *
     * @param mixed $id
     * @return bool
     * @throws DataAccessException
     */
    public function delete($id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression de la configuration: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Supprimer une configuration par son code
     *
     * @param string $code
     * @param string $environment
     * @return bool
     * @throws DataAccessException
     */
    public function deleteByCode(string $code, string $environment = 'prod'): bool
    {
        try {
            $result = $this->collection->deleteOne([
                'code' => $code,
                'environment' => $environment
            ]);
            
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression de la configuration par code: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Charger un ensemble de configurations depuis un tableau
     *
     * @param array $configs
     * @param string $environment
     * @param string $modifiedBy
     * @param bool $overwrite
     * @return int Nombre de configurations chargées
     * @throws DataAccessException
     */
    public function bulkLoad(array $configs, string $environment = 'prod', string $modifiedBy = 'system', bool $overwrite = false): int
    {
        $count = 0;
        
        foreach ($configs as $configData) {
            if (!isset($configData['code']) || !isset($configData['value'])) {
                continue;
            }
            
            $code = $configData['code'];
            $value = $configData['value'];
            $description = $configData['description'] ?? '';
            $category = $configData['category'] ?? 'general';
            $active = $configData['active'] ?? true;
            
            $existingConfig = $this->findByCode($code, $environment);
            
            if ($existingConfig === null) {
                // Création d'une nouvelle configuration
                $config = new Configuration($code, $value, $description);
                $config->setCategory($category);
                $config->setActive($active);
                $config->setEnvironment($environment);
                $config->setModifiedBy($modifiedBy);
                $this->save($config);
                $count++;
            } elseif ($overwrite) {
                // Mise à jour d'une configuration existante
                $existingConfig->setValue($value);
                $existingConfig->setDescription($description);
                $existingConfig->setCategory($category);
                $existingConfig->setActive($active);
                $existingConfig->touch($modifiedBy);
                $this->save($existingConfig);
                $count++;
            }
        }
        
        return $count;
    }
} 