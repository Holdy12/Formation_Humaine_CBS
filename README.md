# Formation Humaine CBS

Application web de gestion et d'évaluation de la Formation Humaine des étudiants du CBS :
présences, signalements, registre des points et résultats semestriels, avec un espace par rôle,
et un site vitrine public qui présente l'école et la Formation Humaine et mène aux espaces.

- **Espace étudiant et délégué** : solde de points et détail par domaine, présences et dépôt de
  justificatifs, réponse aux signalements, résultats, club, profil, relevé imprimable ; le délégué
  fait l'appel de sa promotion et signale un comportement.
- **Espace personnel** (administrateur, responsable FH, chargé de discipline, enseignant,
  responsables de club) : étudiants et import CSV, signalements et instruction, appel, séances,
  justificatifs, assiduité, registre des points et corrections, clubs, structure académique et
  clôture des semestres, barème et paramètres, comptes, rapports et exports, journal.
- **Site vitrine** (`/`, `/vie-etudiante`, `/formations`, `/admission`, `/contact`) : accueil,
  Formation Humaine expliquée, formations, admission, contact avec formulaire (envoi à brancher,
  voir `documentation/site-vitrine.md`) ; l'en-tête mène à l'espace de la personne connectée.
  Case « Rester connecté sur cet appareil » (dix jours) à la connexion.

## Prérequis

- PHP 8.1 ou plus, avec les extensions `pdo_mysql` et `fileinfo`
- MySQL 8 ou MariaDB 10.4 ou plus
- Un serveur web dont la racine est `public/`, ou le serveur intégré de PHP pour le développement

## Installation

Guide détaillé, étape par étape, avec vérifications et dépannage : `documentation/installation.md`.
En résumé :

1. Cloner le dépôt.
2. Créer la base et charger les scripts dans l'ordre (détails dans `documentation/base-de-donnees.md`) :
   `database/schema.sql`, `database/seed.sql`, puis `database/donnees_test.sql` (facultatif, jeu d'essai).
3. Copier `.env.example` en `.env` et y renseigner les accès MySQL (`DB_USER`, `DB_PASS`) et `BASE_URL`.
4. Lancer `php -S localhost:8000 -t public` et ouvrir `http://localhost:8000` (le site vitrine ;
   « Se connecter » mène à l'application).

Compte administrateur initial : `admin@formation.local` / `Admin123!` (à changer après la première connexion).

## Structure

```
app/Controllers/   traitement des requêtes ; Admin/ pour l'espace personnel ; SiteController pour le site vitrine
app/Models/        accès aux données (PDO, requêtes préparées)
app/Views/         pages HTML : etudiant/, admin/, auth/, site/ (vitrine, textes dans contenu.php) ; partials/ ; erreur.php
core/              session, permissions et routage, journal, fichiers envoyés, icônes, composants
config/            connexion à la base (accès lus dans `.env`)
database/          schéma, données de référence, données de test
documentation/     documentation fonctionnelle et technique
public/            racine web : index.php, .htaccess (adresses du site), assets/ (site.css, fonts/ et images/site/ pour la vitrine)
storage/           fichiers envoyés par les utilisateurs, hors racine web, ignorés par Git
tests/             recette automatisée (bash + curl)
```

## Documentation

- `documentation/installation.md` : installation pas à pas, lancement, mise sur un serveur, dépannage
- `documentation/base-de-donnees.md` : scripts de la base, historique du schéma
- `documentation/espace-etudiant.md` : conception de l'espace étudiant et délégué, règles métier, cas de test
- `documentation/espace-personnel.md` : espace personnel (rôles, permissions, modules, règles, cas de test)
- `documentation/site-vitrine.md` : site vitrine (pages, contenu, photos), reconnexion automatique, points à valider
- `documentation/courriel-resend.md` : brancher l'envoi de courriels avec Resend
- `documentation/tests.md` : lancer la recette `tests/recette.sh`

## Tests

```bash
bash tests/recette.sh
```

Recharge la base de test et rejoue les cas des deux espaces contre l'instance locale (voir `documentation/tests.md`).

## Conventions

- Commits : `ajout :`, `correction :`, `doc :` ou `style :` suivi d'une description courte en français.
- Classes en PascalCase, méthodes et variables en camelCase, tables et colonnes en majuscules.
- Requêtes préparées PDO systématiques ; `htmlspecialchars()` sur toute donnée affichée ; jeton CSRF sur les formulaires.
- Chemins vers `assets/` relatifs, jamais absolus, pour fonctionner à la racine d'un hôte comme dans un sous-dossier.
