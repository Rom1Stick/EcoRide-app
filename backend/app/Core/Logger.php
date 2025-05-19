<?php

namespace App\Core;

/**
 * Classe de gestion des journaux
 */
class Logger
{
    /**
     * @var string Chemin du fichier de journal
     */
    private $logFile;

    /**
     * @var string Niveau minimum de log
     */
    private $minLevel;

    /**
     * @var array Niveaux de log disponibles
     */
    private $levels = [
        'debug' => 100,
        'info' => 200,
        'notice' => 300,
        'warning' => 400,
        'error' => 500,
        'critical' => 600,
        'alert' => 700,
        'emergency' => 800
    ];

    /**
     * Constructeur du logger
     *
     * @param string $logFile Chemin du fichier de journal
     * @param string $minLevel Niveau minimum de log
     */
    public function __construct(string $logFile, string $minLevel = 'debug')
    {
        $this->logFile = $logFile;
        $this->minLevel = $minLevel;
        
        // Créer le répertoire de logs si nécessaire
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
    }

    /**
     * Ajoute un message de journal
     *
     * @param string $level Niveau de log
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function log(string $level, string $message, array $context = []): void
    {
        // Vérifier si le niveau est valide
        if (!isset($this->levels[$level])) {
            throw new \InvalidArgumentException("Niveau de log invalide: $level");
        }

        // Vérifier si le niveau est supérieur au niveau minimum
        if ($this->levels[$level] < $this->levels[$this->minLevel]) {
            return;
        }

        // Formatage du message
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [$level] $message";
        
        // Ajouter le contexte si présent
        if (!empty($context)) {
            $formattedMessage .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        // Écrire le message dans le fichier de journal
        file_put_contents(
            $this->logFile,
            $formattedMessage . PHP_EOL,
            FILE_APPEND
        );
    }

    /**
     * Ajoute un message de journal de niveau debug
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau info
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau notice
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function notice(string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau warning
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau error
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau critical
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau alert
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function alert(string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Ajoute un message de journal de niveau emergency
     *
     * @param string $message Message à journaliser
     * @param array $context Contexte du message
     * @return void
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }
} 