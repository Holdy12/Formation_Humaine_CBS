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

L'utilisateur MySQL est celui renseigné dans `.env` (`DB_USER`, `DB_PASS`) ; par exemple :

```sql
CREATE USER 'formation'@'localhost' IDENTIFIED BY 'un-mot-de-passe-solide';
GRANT ALL PRIVILEGES ON formation_humaine_db.* TO 'formation'@'localhost';
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
- `PERSONNE.DATE_NAISSANCE` devient facultative : elle n'a pas de sens pour un compte du personnel (`ALTER TABLE PERSONNE MODIFY DATE_NAISSANCE DATE NULL` sur une base existante)
  (compte créé ou mot de passe réinitialisé par l'administration).
- `SIGNALEMENT.DATE_AUDITION`, `NOTES_AUDITION`, `CONSEIL_DISCIPLINE` : audition et transmission
  au conseil, renseignées pendant l'instruction.
- `SIGNALEMENT_HISTORIQUE` : un enregistrement par changement de statut (statut, auteur, date,
  commentaire).
- `MOUVEMENT_POINTS.ID_PRESENCE` : la présence pénalisée, pour interdire toute double pénalité ;
  `ID_MOUVEMENT_CORRIGE` : le mouvement annulé par une écriture inverse.
- `PARAMETRE_SYSTEME` : `SEUIL_CRITIQUE_NOTE` et les seuils de mention `MENTION_TRES_BIEN`,
  `MENTION_BIEN`, `MENTION_ASSEZ_BIEN`, `MENTION_PASSABLE`.
- `TENTATIVE_CONNEXION` : échecs de connexion récents (identifiant saisi, adresse, date), qui
  limitent les essais de mot de passe indépendamment de la session. Sur une base existante :

  ```sql
  CREATE TABLE TENTATIVE_CONNEXION (
     ID_TENTATIVE INT AUTO_INCREMENT PRIMARY KEY,
     IDENTIFIANT VARCHAR(100) NOT NULL,
     ADRESSE_IP VARCHAR(50) NOT NULL,
     DATE_TENTATIVE DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     INDEX IDX_TENTATIVE_IDENTIFIANT (IDENTIFIANT, DATE_TENTATIVE),
     INDEX IDX_TENTATIVE_ADRESSE (ADRESSE_IP, DATE_TENTATIVE)
  ) ENGINE=InnoDB;
  ```
- `JETON_CONNEXION` : appareils mémorisés par la case « Rester connecté sur cet appareil » de la
  page de connexion. Le cookie porte `sélecteur.validateur` ; seul le haché du validateur est en
  base, et il est remplacé à chaque reprise de session (voir `documentation/site-vitrine.md`,
  section 5). Sur une base existante :

  ```sql
  CREATE TABLE JETON_CONNEXION (
     ID_JETON INT AUTO_INCREMENT PRIMARY KEY,
     ID_PERSONNE INT NOT NULL,
     SELECTEUR CHAR(24) NOT NULL UNIQUE,
     VALIDATEUR_HASH CHAR(64) NOT NULL,
     VALIDATEUR_PRECEDENT CHAR(64) NULL,
     DATE_CREATION DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     DATE_EXPIRATION DATETIME NOT NULL,
     DATE_UTILISATION DATETIME NULL,
     CONSTRAINT FK_JETON_PERSONNE FOREIGN KEY (ID_PERSONNE) REFERENCES PERSONNE (ID_PERSONNE) ON DELETE CASCADE,
     INDEX IDX_JETON_PERSONNE (ID_PERSONNE)
  ) ENGINE=InnoDB;
  ```
- `config/database.php` lit les accès dans `.env` (voir `installation.md`) et aligne le fuseau
  horaire de la connexion MySQL sur celui de PHP (`APP_TIMEZONE`, `Africa/Ndjamena` par défaut),
  afin que les délais soient calculés de la même façon des deux côtés.

## Historique des corrections côté administration

Les anciens contrôleurs et vues d'administration (`AdminDashboardController`, `app/Views/admin/*.php`
de première génération) présentaient des requêtes sur des colonnes inexistantes et un mot de passe
de développement accepté pour tout compte. Ils ont été remplacés par l'espace personnel décrit dans
`espace-personnel.md` ; la base ne conserve aucune trace de ces écarts.
