
### **CONTRIBUTING.md**

Le fichier CONTRIBUTING fournit aux développeurs les règles et bonnes pratiques pour contribuer au projet EcoRide. Il détaille la convention de commit, le modèle de branches Git, les exigences pour les pull requests, les hooks automatiques et les critères de qualité à respecter avant de fusionner du code. 

# Guide de Contribution – EcoRide Frontend

Merci de contribuer à **EcoRide** ! Ce guide décrit le processus à suivre pour des contributions de qualité, cohérentes avec les objectifs du projet.

## Branches Git

- **Branche principale** : `main` contient la dernière version stable du front-end. Tout code mergé sur main doit être testé et prêt pour la mise en production.
- **Branches de fonctionnalité** : créez une branche pour chaque nouvelle fonctionnalité ou correction de bug à partir de `main`. Utilisez un nom explicite :
  - `feat/inscription-email` pour une nouvelle fonctionnalité (préfixe *feat/*)
  - `fix/correctif-avatar` pour un bugfix (préfixe *fix/*)
  - D’autres préfixes possibles : *chore/* (tâches diverses), *docs/* (documentation), *refactor/* (remaniement de code sans changement de fonction), etc.
- **Branches de release** : (si applicable) utilisées en fin de sprint pour la préparation d’une version.
- **Branches hotfix** : pour corrections urgentes en production, partant de la version de production.

Essayez de garder une branche de fonctionnalité concentrée sur un sujet (évitez les « méga-branches » multi-sujets). **Astuce** : préfixez éventuellement par un numéro d’issue : ex `feat/42-ajout-filtre-date` si vous utilisez un gestionnaire de tickets.

## Commits

Nous suivons la convention **Conventional Commits** pour les messages de commit. Cela permet un historique clair et éventuellement une génération automatique du changelog. 

**Format de commit** : `type(scope): description` (en anglais de préférence). Par exemple :
- `feat(auth): add OAuth2 login support`
- `fix(route): correct typo in route name causing 404`
- `docs: update README installation steps`

Les types autorisés incluent entre autres :
- **feat** – ajout d’une nouvelle fonctionnalité
- **fix** – correction de bug
- **docs** – changements de documentation seulement
- **style** – formatage, pas de changement de logique (espaces, virgules, etc.)
- **refactor** – refonte de code sans changement de fonctionnalité
- **perf** – amélioration de performance
- **test** – ajout ou correction de tests
- **chore** – maintenance (maj de dépendances, configuration)

Un commit doit être aussi petit que possible tout en restant cohérent. Évitez les commits fourre-tout. N’hésitez pas à faire plusieurs commits et à les *squasher* ensuite si nécessaire lors du merge.

*(Pour rappel, un message de commit doit être impérativement préfixé d’un type valide, par ex. `feat:` ou `fix:` etc., suivi d’une courte description)&#8203;:contentReference[oaicite:10]{index=10}.*

## Pull Requests

Avant d’ouvrir une Pull Request (PR) pour fusionner votre branche sur `main` :
- Assurez-vous que votre branche est à jour avec `main` (rebase si nécessaire pour intégrer les derniers changements).
- Vérifiez que **tous les tests passent** et que **l’application se lance sans erreur**.
- Exécutez `npm run lint` et corrigez les problèmes de formatage ou lint éventuels.
- Remplissez la description de la PR en expliquant :
  - Le **contexte** et le besoin (référence d’une issue éventuelle, « pourquoi » du changement).
  - Un résumé clair du **changement** (le « quoi »). Listez les modifications majeures apportées.
  - Détaillez comment tester votre contribution (captures d’écran bienvenues si UI impactée).
- Marquez les personnes devant relire la PR (reviewers). Au moins un autre développeur doit approuver avant fusion.
- **Taille de PR** : essayez de limiter les PR à un volume raisonnable. Si votre travail est trop gros, envisagez de le découper en plusieurs PR cohérentes.

## Hooks et Tests automatisés

Nous utilisons **Husky** pour automatiser certaines vérifications en local :
- Un *hook* `pre-commit` exécute automatiquement ESLint, Prettier et Stylelint. Si des erreurs de lint ou de formatage sont détectées, le commit est annulé jusqu’à correction (vous pouvez lancer manuellement `npm run lint` et `npm run format` pour corriger avant de re-tenter).
- Un *hook* `commit-msg` utilise **Commitlint** pour valider le format de votre message de commit (selon Conventional Commits). Un commit mal formatté sera refusé.
- (À terme) Un hook `pre-push` ou une étape CI pourrait exécuter la suite de tests pour s’assurer que rien n’est cassé.

Ces garde-fous visent à maintenir une base de code propre et stable. Merci de ne pas les contourner. Si pour une raison exceptionnelle vous devez passer outre un hook (par ex. problème urgent), utilisez l’option `--no-verify` sur le commit, mais justifiez-le dans la PR.

## Qualité du Code & Exigences avant merge

Avant la fusion d’une PR, les critères suivants doivent être remplis :
- **Tests** : les tests automatisés (unitaires, d’intégration) couvrant la fonctionnalité ajoutée/corrigée doivent être présents et passer. S’il n’y a pas de tests (techniquement ou par manque de temps), mentionnez-le dans la PR et créez une tâche technique pour en ajouter plus tard.
- **Linting/formatage** : le code doit respecter nos standards (ESLint/Prettier/Stylelint sans erreur). Le style de code homogène facilite la relecture et évite les diffs inutiles.
- **Performances** : assurez-vous que votre changement n’alourdit pas significativement l’application. Par exemple, éviter d’introduire une librairie volumineuse sans discussion préalable. EcoRide vise la sobriété : tout ajout de dépendance doit être justifié (fonctionnalité critique non réalisable nativement).
- **Accessibilité** : pour toute modification UI, pensez à vérifier les bonnes pratiques d’accessibilité (contrastes, navigation clavier, attributs ARIA si nécessaire). C’est une part des critères d’acceptation.
- **Relecture** : au moins un membre de l’équipe doit approuver la PR. Si des changements sont demandés, intégrez-les puis faites à nouveau valider.
- **Documentation** : si votre contribution modifie le comportement côté utilisateur ou ajoute une config, mettez à jour le README ou la documentation associée.

En respectant ces directives, nous gardons un codebase sain, facile à maintenir et en ligne avec les valeurs d’EcoRide (qualité, efficacité, durabilité). 

Merci pour votre contribution ! Ensemble, rendons la mobilité plus durable 🚗🌱.

