<?php

namespace Tests\Mocks;

use App\DataAccess\Exception\DataAccessException;
use App\DataAccess\NoSql\Model\Configuration;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;

/**
 * Mock du service de configuration pour les tests
 */
class ConfigurationServiceMock extends MockMongoService
{
    /**
     * Trouve une configuration par son ID
     * 
     * @param string $id
     * @return Configuration|null
     * @throws DataAccessException
     */
    public function findById(string $id): ?Configuration
    {
        try {
            $document = $this->collection->findOne(['_id' => $this->toObjectId($id)]);

            if (!$document) {
                return null;
            }

            return $this->documentToConfiguration($document);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche: " . $e->getMessage());
        }
    }

    /**
     * Trouve une configuration par son code et son environnement
     * 
     * @param string $code
     * @param string $environment
     * @return Configuration|null
     * @throws DataAccessException
     */
    public function findByCode(string $code, string $environment): ?Configuration
    {
        try {
            $document = $this->collection->findOne([
                'code' => $code,
                'environment' => $environment
            ]);

            if (!$document) {
                return null;
            }

            return $this->documentToConfiguration($document);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche par code: " . $e->getMessage());
        }
    }

    /**
     * Sauvegarde une configuration
     * 
     * @param Configuration $config
     * @return Configuration
     * @throws DataAccessException
     */
    public function save(Configuration $config): Configuration
    {
        try {
            $data = [
                'code' => $config->getCode(),
                'value' => $config->getValue(),
                'description' => $config->getDescription(),
                'category' => $config->getCategory(),
                'environment' => $config->getEnvironment(),
                'active' => $config->isActive(),
                'createdAt' => new \MongoDB\BSON\UTCDateTime($config->getCreatedAt()->getTimestamp() * 1000)
            ];

            if ($config->getUpdatedAt()) {
                $data['updatedAt'] = new \MongoDB\BSON\UTCDateTime($config->getUpdatedAt()->getTimestamp() * 1000);
            }

            $result = $this->collection->insertOne($data);

            if ($result->getInsertedCount() > 0) {
                $config->setId((string)$result->getInsertedId());
                return $config;
            }

            throw new DataAccessException("Erreur lors de l'insertion de la configuration");
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la sauvegarde: " . $e->getMessage());
        }
    }

    /**
     * Supprime une configuration
     * 
     * @param string $id
     * @return bool
     * @throws DataAccessException
     */
    public function delete(string $id): bool
    {
        try {
            $result = $this->collection->deleteOne(['_id' => $this->toObjectId($id)]);
            return $result->getDeletedCount() > 0;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la suppression: " . $e->getMessage());
        }
    }

    /**
     * Met à jour la valeur d'une configuration
     * 
     * @param string $code
     * @param string $value
     * @param string $environment
     * @return Configuration
     * @throws DataAccessException
     */
    public function updateValue(string $code, string $value, string $environment): Configuration
    {
        try {
            $config = $this->findByCode($code, $environment);

            if (!$config) {
                throw new DataAccessException("Configuration non trouvée: $code");
            }

            $config->setValue($value);
            $config->updateTimestamp();

            $updatedAt = new \MongoDB\BSON\UTCDateTime($config->getUpdatedAt()->getTimestamp() * 1000);

            $result = $this->collection->updateOne(
                ['code' => $code, 'environment' => $environment],
                [
                    '$set' => [
                        'value' => $value,
                        'updatedAt' => $updatedAt
                    ]
                ]
            );

            if ($result->getModifiedCount() === 0) {
                throw new DataAccessException("Erreur lors de la mise à jour de la configuration");
            }

            return $config;
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la mise à jour: " . $e->getMessage());
        }
    }

    /**
     * Trouve ou crée une configuration
     * 
     * @param string $code
     * @param string $defaultValue
     * @param string $description
     * @param string $environment
     * @param string $category
     * @return Configuration
     * @throws DataAccessException
     */
    public function findOrCreate(
        string $code,
        string $defaultValue,
        string $description,
        string $environment,
        string $category = 'general'
    ): Configuration {
        try {
            $config = $this->findByCode($code, $environment);

            if ($config) {
                return $config;
            }

            $config = new Configuration();
            $config->setCode($code)
                ->setValue($defaultValue)
                ->setDescription($description)
                ->setCategory($category)
                ->setEnvironment($environment);

            return $this->save($config);
        } catch (\Exception $e) {
            throw new DataAccessException("Erreur lors de la recherche ou création: " . $e->getMessage());
        }
    }

    /**
     * Convertit un document MongoDB en objet Configuration
     * 
     * @param object $document
     * @return Configuration
     */
    protected function documentToConfiguration(object $document): Configuration
    {
        $config = new Configuration();
        $config->setId((string)$document->_id)
            ->setCode($document->code)
            ->setValue($document->value)
            ->setDescription($document->description)
            ->setCategory($document->category)
            ->setEnvironment($document->environment)
            ->setActive($document->active);

        if (isset($document->createdAt)) {
            $createdAt = new \DateTime();
            $createdAt->setTimestamp($document->createdAt->toDateTime()->getTimestamp());
            $config->setCreatedAt($createdAt);
        }

        if (isset($document->updatedAt)) {
            $updatedAt = new \DateTime();
            $updatedAt->setTimestamp($document->updatedAt->toDateTime()->getTimestamp());
            $config->setUpdatedAt($updatedAt);
        }

        return $config;
    }
} 