<?php

/**
 * Fichier contenant des fonctions utilitaires pour l'application
 */

/**
 * Récupère la valeur d'une variable d'environnement
 *
 * @param  string $key     Clé de la variable
 *                         d'environnement
 * @param  mixed  $default Valeur par défaut si la variable n'existe
 *                         pas
 * @return mixed
 */
function env(string $key, $default = null)
{
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convertir certaines valeurs textuelles en leurs équivalents PHP
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    // Vérifier si la valeur est entre guillemets et les supprimer
    if (
        strlen($value) > 1
        && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
        || ($value[0] === "'" && $value[strlen($value) - 1] === "'")    )
    ) {
        return substr($value, 1, -1);
    }

    return $value;
}

/**
 * Récupère l'instance de l'application
 *
 * @return \App\Core\Application
 */
function app(): \App\Core\Application
{
    global $app;
    return $app;
}

/**
 * Sanitize les données d'entrée
 *
 * @param  mixed $input Données à nettoyer
 * @return mixed
 */
function sanitize($input)
{
    return \App\Core\Security::sanitize($input);
}

/**
 * Valide les données selon des règles spécifiées
 *
 * @param  array $data  Données à
 *                      valider
 * @param  array $rules Règles de validation
 * @return array
 */
function validate(array $data, array $rules): array
{
    return \App\Core\Security::validate($data, $rules);
}

/**
 * Récupère une valeur de configuration
 *
 * @param  string $key     Clé de configuration (format:
 *                         fichier.section.clé)
 * @param  mixed  $default Valeur par
 *                         défaut
 * @return mixed
 */
function config(string $key, $default = null)
{
    static $config = [];

    // Diviser la clé en parties (fichier.section.clé)
    $parts = explode('.', $key);
    $file = $parts[0];

    // Charger le fichier de configuration s'il n'est pas déjà chargé
    if (!isset($config[$file])) {
        $configFile = BASE_PATH . '/config/' . $file . '.php';

        if (file_exists($configFile)) {
            $config[$file] = include $configFile;
        } else {
            return $default;
        }
    }

    // Récupérer la valeur de configuration
    $value = $config[$file];

    // Parcourir l'arborescence pour trouver la valeur
    for ($i = 1; $i < count($parts); $i++) {
        $part = $parts[$i];

        if (is_array($value) && isset($value[$part])) {
            $value = $value[$part];
        } else {
            return $default;
        }
    }

    return $value;
}
