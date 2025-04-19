<?php

namespace App\Core;

/**
 * Classe utilitaire pour charger les variables d'environnement
 * à partir d'un fichier .env
 */
class DotEnv
{
    /**
     * Le chemin vers le fichier .env
     *
     * @var string
     */
    protected $path;

    /**
     * Constructeur
     *
     * @param string $path Chemin vers le fichier .env
     */
    public function __construct(string $path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s n\'existe pas', $path));
        }
        $this->path = $path;
    }

    /**
     * Charge les variables d'environnement à partir du fichier .env
     *
     * @return void
     */
    public function load(): void
    {
        if (!is_readable($this->path)) {
            throw new \RuntimeException(sprintf('%s n\'est pas lisible', $this->path));
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Analyser la ligne
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Supprimer les guillemets éventuels
            if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            }
            if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                $value = substr($value, 1, -1);
            }

            // Résoudre les variables dans la valeur
            $value = $this->resolveNestedVariables($value);

            // Définir la variable d'environnement
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    /**
     * Résout les variables imbriquées comme ${APP_NAME}
     *
     * @param  string $value La valeur à résoudre
     * @return string
     */
    private function resolveNestedVariables(string $value): string
    {
        if (str_contains($value, '$')) {
            $value = preg_replace_callback(
                '/\${([a-zA-Z0-9_]+)}/',
                function ($matches) {
                    $nestedVariable = $matches[1];
                    return getenv($nestedVariable) ?: '';
                },
                $value
            );
        }

        return $value;
    }
}
