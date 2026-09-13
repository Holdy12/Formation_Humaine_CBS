# Base de données

## Installation

Dans l'ordre, depuis la racine du projet :

```bash
mysql -u root -p < database/schema.sql        # structure (supprime et recrée les tables)
mysql -u root -p < database/seed.sql          # rôles, domaines, barème, paramètres, compte admin
mysql -u root -p < database/donnees_test.sql  # facultatif : jeu de données pour tester
```

Comptes :

| Compte | Identifiant | Mot de passe |
|---|---|---|
| Administrateur | `admin@formation.local` ou `ADM-2026-001` | `Admin123!` |
| Tous les comptes de `donnees_test.sql` | email ou matricule | `Test1234!` |

L'utilisateur MySQL attendu par `config/database.php` est `Maurer` / `20031975` :

```sql
CREATE USER 'Maurer'@'localhost' IDENTIFIED BY '20031975';
GRANT ALL PRIVILEGES ON formation_humaine_db.* TO 'Maurer'@'localhost';
```

## Historique du schéma

La première version de `schema.sql` ne correspondait pas au code : les contrôleurs interrogeaient des colonnes
(`DATE_DEBUT`, `LIBELLE_NIVEAU`, `LIBELLE_ROLE`, `ACTION`, `ID_FILIERE` sur `PROMOTION`…)
qui n'existaient pas dans le script. Sur une installation neuve, le tableau de bord s'affichait
vide parce que chaque requête échouait silencieusement dans un `catch`.

La version actuelle reprend la structure `PERSONNE` utilisée par le code et la met en cohérence
avec le dictionnaire des données et le document préparatoire.

### Corrections

- `ANNEE_ACADEMIQUE`, `SEMESTRE` : `DATE_DEBUT` / `DATE_FIN` (au lieu de `DATE_SEANCE` / `HEURE_DEBUT`).
- `NIVEAU.LIBELLE_NIVEAU`, `ROLE.LIBELLE_ROLE` (au lieu de `LIBELLE_CRITERE`).
- `PROMOTION` porte `ID_FILIERE` ; `FILIERE` ne porte plus `ID_PROMO` (le sens était inversé).
- `JOURNAL_CONNEXION` : `ADRESSE_IP` (faute corrigée) + colonnes `ACTION`, `DETAILS`.
- `SEANCE.HEURE_FIN` (au lieu de `DATE_FIN` de type TIME).
- `CRITERE.VALEUR_POINTS` en `DECIMAL(4,2)` signé : le barème contient des −0,25 et des +0,50.

### Ajouts (document préparatoire / spécifications)

- `ETUDIANT.ID_ETUDIANT` (clé auto) + `EST_DELEGUE`. `ID_PERSONNE` reste la clé référencée par
  toutes les tables liées à l'étudiant.
- `TEMOIN`, `PARAMETRE_SYSTEME` (capital 20/20, plafonds, délais).
- `SIGNALEMENT` : auteur, validateur, critère, date des faits, réponse de l'étudiant, décision,
  statut sur 8 valeurs (`BROUILLON` … `CLOTURE`).
- `JUSTIFICATION_ABSENCE` : `CHEMIN_FICHIER`, validateur, date et commentaire de validation.
- `MOUVEMENT_POINTS` : lien vers le signalement et le validateur.
- `RESULTAT_SEMESTRIEL` : `NOTE_PROVISOIRE` / `NOTE_FINALE` bornées [0 ; 20], unicité
  (semestre, étudiant).
- `SEANCE` peut appartenir à un club **ou** à une promotion (appel par classe).
- `CLUB.ID_RESPONSABLE`, `APPEL.ID_PERSONNE` (qui a fait l'appel), `DOMAINE.CODE_DOMAINE`.

### Ajouts pour l'espace personnel

- `PERSONNE.DOIT_CHANGER_MDP` : changement de mot de passe imposé à la prochaine connexion
  (compte créé ou mot de passe réinitialisé par l'administration).
- `SIGNALEMENT.DATE_AUDITION`, `NOTES_AUDITION`, `CONSEIL_DISCIPLINE` : audition et transmission
  au conseil, renseignées pendant l'instruction.
- `SIGNALEMENT_HISTORIQUE` : un enregistrement par changement de statut (statut, auteur, date,
  commentaire).
- `MOUVEMENT_POINTS.ID_PRESENCE` : la présence pénalisée, pour interdire toute double pénalité ;
  `ID_MOUVEMENT_CORRIGE` : le mouvement annulé par une écriture inverse.
- `PARAMETRE_SYSTEME` : `SEUIL_CRITIQUE_NOTE` et les seuils de mention `MENTION_TRES_BIEN`,
  `MENTION_BIEN`, `MENTION_ASSEZ_BIEN`, `MENTION_PASSABLE`.
- `config/database.php` fixe le fuseau horaire de PHP (`Africa/Ndjamena`) et de la connexion
  MySQL (`+01:00`), afin que les délais soient calculés de la même façon des deux côtés.

## Corrections restant à faire côté administration

1. `AdminDashboardController::getDashboardData` : `j.ADESSE_IP` → `j.ADRESSE_IP`.
2. `AdminDashboardController::etudiants` : retirer `pr.NIVEAU` et `pr.FILIERE` des deux
   `COALESCE` (ces colonnes n'existent pas, la requête bascule sur le secours qui affiche
   « Licence 1 » pour tout le monde).
3. `AdminDashboardController::getDashboardData` : les points sont des décimaux, les `(int)`
   sur `pos` / `neg` transforment 0,25 en 0 → utiliser `(float)`. Et ajouter `unset($row);`
   après la boucle `foreach ($domainesStats as &$row)` : sans cela, la boucle suivante
   `foreach ($resultEvo as $row)` écrase le dernier domaine du tableau.
4. `app/Views/admin/etudiants.php` : les liens *Modifier* et *Supprimer* passent
   `ID_PERSONNE` alors que `update()` et `delete()` cherchent par `ID_ETUDIANT`. Sélectionner
   `e.ID_ETUDIANT` dans la requête de la liste et l'utiliser dans ces deux liens.

`AuthController` accepte encore le mot de passe `password` pour n'importe quel compte
(raccourci de développement à retirer avant la démonstration).
