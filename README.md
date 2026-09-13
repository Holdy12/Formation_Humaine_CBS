# Formation Humaine CBS

Application web de gestion et d'évaluation de la Formation Humaine des étudiants du CBS :
présences, signalements, registre des points et résultats semestriels, avec un espace par rôle.

## Prérequis

- PHP 8.1 ou plus, avec les extensions `pdo_mysql` et `fileinfo`
- MySQL 8 ou MariaDB 10.4 ou plus
- Un serveur web dont la racine est `public/`, ou le serveur intégré de PHP pour le développement

## Installation

1. Cloner le dépôt.
2. Créer la base et charger les scripts dans l'ordre (détails dans `documentation/base-de-donnees.md`) :
   `database/schema.sql`, `database/seed.sql`, puis `database/donnees_test.sql` (facultatif, jeu d'essai).
3. Renseigner les accès MySQL (`DB_USER`, `DB_PASS`) et `BASE_URL` dans `config/database.php`.
4. Lancer `php -S localhost:8000 -t public` et ouvrir `http://localhost:8000`.

Compte administrateur initial : `admin@formation.local` / `Admin123!` (à changer après la première connexion).

## Structure

```
app/Controllers/   traitement des requêtes
app/Models/        accès aux données (PDO, requêtes préparées)
app/Views/         pages HTML ; partials/ pour le gabarit commun ; erreur.php pour les pages d'erreur
core/              session et garde par rôle, fichiers envoyés, icônes, composants partagés
config/            connexion à la base
database/          schéma, données de référence, données de test
documentation/     documentation fonctionnelle et technique
public/            racine web : index.php, assets/
storage/           fichiers envoyés par les utilisateurs, hors racine web, ignorés par Git
tests/             recette automatisée (bash + curl)
```

## Documentation

- `documentation/base-de-donnees.md` : installation de la base, historique du schéma, points à corriger
- `documentation/espace-etudiant.md` : conception de l'espace étudiant et délégué, règles métier, cas de test
- `documentation/espace-personnel.md` : conception de l'espace personnel (rôles, permissions, modules)
- `documentation/courriel-resend.md` : brancher l'envoi de courriels avec Resend
- `documentation/tests.md` : lancer la recette `tests/recette.sh`

## Tests

```bash
bash tests/recette.sh
```

Recharge la base de test et rejoue les cas de l'espace étudiant contre l'instance locale (voir `documentation/tests.md`).

## Conventions

- Commits : `ajout :`, `correction :`, `doc :` ou `style :` suivi d'une description courte en français.
- Classes en PascalCase, méthodes et variables en camelCase, tables et colonnes en majuscules.
- Requêtes préparées PDO systématiques ; `htmlspecialchars()` sur toute donnée affichée ; jeton CSRF sur les formulaires.
- Chemins vers `assets/` relatifs, jamais absolus, pour fonctionner à la racine d'un hôte comme dans un sous-dossier.
