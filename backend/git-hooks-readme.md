# Hooks Git pour EcoRide

Ce document explique les hooks Git configurés pour ce projet, en particulier pour vérifier la qualité du code PHP.

## Hook Pre-commit

Un hook pre-commit a été ajouté pour vérifier automatiquement la qualité du code PHP avant chaque commit en utilisant PHP_CodeSniffer. Ce hook :

1. Vérifie si des fichiers PHP ont été ajoutés ou modifiés dans le commit
2. Si oui, lance PHP_CodeSniffer pour vérifier la qualité du code
3. Bloque le commit si des erreurs de style sont détectées

## Installation

Les hooks Git sont déjà présents dans le dossier `.git/hooks/` du projet. Aucune action supplémentaire n'est normalement nécessaire.

Si vous rencontrez des problèmes avec les hooks, assurez-vous que les fichiers dans `.git/hooks/` ont les permissions d'exécution :

```bash
# Sur Linux/Mac
chmod +x .git/hooks/pre-commit

# Sur Windows (PowerShell avec privilèges administrateur)
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
```

## En cas d'erreurs

Si le hook pre-commit détecte des erreurs dans votre code :

1. Consultez les messages d'erreur affichés dans le terminal
2. Corrigez les problèmes de style de code identifiés
3. Vous pouvez utiliser la commande suivante pour corriger automatiquement certaines erreurs :
   ```bash
   # Depuis la racine du projet
   cd backend && ./vendor/bin/phpcbf --standard=config/phpcs.xml app
   ```
4. Une fois les corrections faites, essayez à nouveau de committer

## Ignorer temporairement le hook

Dans des cas exceptionnels, si vous devez committer du code sans passer par la vérification (déconseillé), vous pouvez utiliser l'option `--no-verify` :

```bash
git commit -m "Message de commit" --no-verify
```

## Questions fréquentes

### Le hook ne s'exécute pas sous Windows

Si le hook ne semble pas s'exécuter sous Windows, vérifiez que :

1. Vous avez bien PowerShell installé
2. Le script PowerShell peut s'exécuter : `Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass`
3. Les chemins dans les scripts pointent correctement vers votre installation de PHP et Composer 