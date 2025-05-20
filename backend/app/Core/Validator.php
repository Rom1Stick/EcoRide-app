<?php

namespace App\Core;

use DateTime;

/**
 * Classe de validation des données
 */
class Validator
{
    /**
     * @var array Données à valider
     */
    private $data;

    /**
     * @var array Erreurs de validation
     */
    private $errors = [];

    /**
     * Constructeur du validateur
     *
     * @param array $data Données à valider
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Vérifie si un champ est présent et non vide
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function required(string $field, string $message): self
    {
        if (!isset($this->data[$field]) || empty($this->data[$field])) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ a une longueur minimale
     *
     * @param string $field Nom du champ
     * @param int $length Longueur minimale
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function minLength(string $field, int $length, string $message): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $length) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ a une longueur maximale
     *
     * @param string $field Nom du champ
     * @param int $length Longueur maximale
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function maxLength(string $field, int $length, string $message): self
    {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $length) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ est une adresse email valide
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function email(string $field, string $message): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ est une date au format spécifié
     *
     * @param string $field Nom du champ
     * @param string $format Format de date (ex: Y-m-d)
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function date(string $field, string $format, string $message): self
    {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->addError($field, $message);
            }
        }
        return $this;
    }

    /**
     * Vérifie si un champ est une heure au format spécifié
     *
     * @param string $field Nom du champ
     * @param string $format Format d'heure (ex: H:i)
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function time(string $field, string $format, string $message): self
    {
        if (isset($this->data[$field])) {
            $time = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$time || $time->format($format) !== $this->data[$field]) {
                $this->addError($field, $message);
            }
        }
        return $this;
    }

    /**
     * Vérifie si un champ est un nombre
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function numeric(string $field, string $message): self
    {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ est un nombre entier
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function integer(string $field, string $message): self
    {
        if (isset($this->data[$field]) && (!is_numeric($this->data[$field]) || floor((float)$this->data[$field]) != $this->data[$field])) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ a une valeur minimale
     *
     * @param string $field Nom du champ
     * @param float $min Valeur minimale
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function min(string $field, float $min, string $message): self
    {
        if (isset($this->data[$field]) && $this->data[$field] < $min) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ a une valeur maximale
     *
     * @param string $field Nom du champ
     * @param float $max Valeur maximale
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function max(string $field, float $max, string $message): self
    {
        if (isset($this->data[$field]) && $this->data[$field] > $max) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ est dans une liste de valeurs autorisées
     *
     * @param string $field Nom du champ
     * @param array $values Valeurs autorisées
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function in(string $field, array $values, string $message): self
    {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->addError($field, $message);
        }
        return $this;
    }

    /**
     * Vérifie si un champ correspond à une date dans le futur ou aujourd'hui
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur en cas d'échec
     * @return self
     */
    public function dateInFutureOrToday(string $field, string $message): self
    {
        if (isset($this->data[$field])) {
            $inputDate = new DateTime($this->data[$field]);
            $today = new DateTime('today');
            
            if ($inputDate < $today) {
                $this->addError($field, $message);
            }
        }
        return $this;
    }

    /**
     * Ajoute une erreur de validation
     *
     * @param string $field Nom du champ
     * @param string $message Message d'erreur
     * @return self
     */
    private function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Vérifie si la validation est passée
     *
     * @return bool True si la validation est passée, false sinon
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Récupère les erreurs de validation
     *
     * @return array Erreurs de validation
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
} 