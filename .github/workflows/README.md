# Pipeline CI/CD EcoRide

Ce dossier contient les workflows GitHub Actions pour l'intégration continue et le déploiement continu du projet EcoRide.

## Avertissements dans l'éditeur VS Code

Si vous rencontrez des avertissements dans VS Code concernant les références aux secrets GitHub (`${{ secrets.X }}`), ces avertissements peuvent être **ignorés en toute sécurité**. Ces secrets sont définis dans les paramètres du repository GitHub et fonctionneront correctement lors de l'exécution du workflow.

## Configuration des secrets

Pour que le workflow de déploiement fonctionne correctement, les secrets suivants doivent être configurés dans les paramètres du repository GitHub (Settings > Secrets and variables > Actions) :

- `AWS_ACCESS_KEY_ID` : L'identifiant de la clé d'accès AWS
- `AWS_SECRET_ACCESS_KEY` : La clé d'accès secrète AWS
- `CLOUDFRONT_DISTRIBUTION_ID` : L'ID de la distribution CloudFront (si applicable)

## Structure du workflow

Le workflow CI/CD est composé de plusieurs jobs séquentiels :

1. **lint** : Vérification des règles de codage
2. **test** : Exécution des tests unitaires et e2e
3. **build** : Compilation et vérification des bundles
4. **audit** : Analyse de performance et de sécurité
5. **deploy** : Déploiement vers AWS S3 et invalidation CloudFront

## Écoconception

Notre pipeline CI/CD est conçu selon les principes d'écoconception :

- Utilisation de caches pour les dépendances
- Exécution conditionnelle des étapes
- Optimisation des ressources utilisées
- Vérification des tailles de bundles
- Audit Lighthouse pour la performance

## Dépannage

Si vous rencontrez des problèmes avec le workflow :

1. Vérifiez que les secrets sont correctement configurés
2. Consultez les logs d'exécution dans l'onglet Actions
3. Assurez-vous que les tests passent localement avant de pousser vos modifications 