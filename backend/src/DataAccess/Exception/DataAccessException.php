<?php

namespace App\DataAccess\Exception;

use Exception;

/**
 * Exception spécifique pour la couche d'accès aux données
 *
 * Cette exception encapsule les erreurs provenant de la couche d'accès aux données,
 * comme les erreurs PDO ou MongoDB, et peut être utilisée pour une gestion centralisée
 * des erreurs dans l'application.
 */
class DataAccessException extends Exception
{
    /**
     * Type de base de données (SQL ou NoSQL)
     *
     * @var string
     */
    private string $dbType;

    /**
     * Constructeur
     *
     * @param string $message Message d'erreur
     * @param int $code Code d'erreur
     * @param \Throwable|null $previous Exception précédente
     * @param string $dbType Type de base de données (SQL ou NoSQL)
     */
    public function __construct(
        string $message,
        int $code = 0,
        \Throwable $previous = null,
        string $dbType = "SQL"
    ) {
        parent::__construct($message, $code, $previous);
        $this->dbType = $dbType;
    }

    /**
     * Récupère le type de base de données
     *
     * @return string Type de base de données
     */
    public function getDbType(): string
    {
        return $this->dbType;
    }

    /**
     * Indique si l'exception est liée à un problème de connexion
     *
     * @return bool True si c'est une erreur de connexion, False sinon
     */
    public function isConnectionError(): bool
    {
        $previous = $this->getPrevious();
        
        if ($previous instanceof \PDOException) {
            // Codes d'erreur PDO liés aux problèmes de connexion
            $connectionErrorCodes = [
                2002, // Connection refused
                2003, // Can't connect to MySQL server
                2006, // MySQL server has gone away
                2013  // Lost connection to MySQL server during query
            ];
            
            $code = $previous->getCode();
            return in_array($code, $connectionErrorCodes);
        } elseif (
            $previous instanceof \MongoDB\Driver\Exception\ConnectionTimeoutException ||
            $previous instanceof \MongoDB\Driver\Exception\ConnectionException
        ) {
            return true;
        }
        
        return false;
    }
} 