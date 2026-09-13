# Installation et lancement

Guide pas à pas pour faire tourner l'application sur un poste de développement (Windows avec
XAMPP, ou Linux / macOS), puis pour la mettre sur un serveur. Chaque étape se vérifie avant de
passer à la suivante.

## 1. Prérequis

| Composant | Version | Remarques |
|---|---|---|
| PHP | 8.1 ou plus (testé avec 8.2) | extensions `pdo_mysql`, `mbstring`, `fileinfo` activées |
| MySQL ou MariaDB | MySQL 8, ou MariaDB 10.4 ou plus (testé avec 10.4) | |
| Git | récent | pour récupérer le code |
| Navigateur | Chrome, Edge, Firefox ou Safari récents | |
| `bash`, `curl` | facultatif | uniquement pour la recette `tests/recette.sh` (Git Bash sous Windows) |

Sous Windows, XAMPP fournit PHP et MariaDB. Les chemins utilisés ci-dessous sont ceux d'une
installation standard dans `C:\xampp`. Sous Linux, remplacer par `php` et `mysql`.

Vérifier PHP et ses extensions :

```bash
# Windows (Git Bash ou PowerShell)
C:\xampp\php\php.exe -v
C:\xampp\php\php.exe -m | findstr /i "pdo_mysql mbstring fileinfo"

# Linux / macOS
php -v
php -m | grep -iE "pdo_mysql|mbstring|fileinfo"
```

Les trois extensions doivent apparaître. Si l'une manque sous XAMPP, ouvrir
`C:\xampp\php\php.ini` et retirer le `;` devant `extension=pdo_mysql`, `extension=mbstring`
ou `extension=fileinfo`.

## 2. Récupérer le code

```bash
git clone https://github.com/Holdy12/Formation_Humaine_CBS.git
cd Formation_Humaine_CBS
git checkout espaces-etudiant-personnel
```

La branche `espaces-etudiant-personnel` contient l'espace étudiant et l'espace personnel. Les
autres branches n'ont pas ces modules.

## 3. Base de données

### 3.1 Démarrer le serveur MySQL

- XAMPP : panneau de contrôle, bouton *Start* sur la ligne MySQL. En ligne de commande :
  `C:\xampp\mysql\bin\mysqld.exe --defaults-file=C:\xampp\mysql\bin\my.ini --standalone`.
- Linux : `sudo systemctl start mariadb` (ou `mysql`).

### 3.2 Créer l'utilisateur attendu par l'application

`config/database.php` se connecte avec l'utilisateur `Maurer` et le mot de passe `20031975`.
Créer ce compte (ou adapter le fichier de configuration, étape 4) :

```bash
# Windows
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE USER IF NOT EXISTS 'Maurer'@'localhost' IDENTIFIED BY '20031975'; GRANT ALL PRIVILEGES ON formation_humaine_db.* TO 'Maurer'@'localhost'; FLUSH PRIVILEGES;"

# Linux
sudo mysql -e "CREATE USER IF NOT EXISTS 'Maurer'@'localhost' IDENTIFIED BY '20031975'; GRANT ALL PRIVILEGES ON formation_humaine_db.* TO 'Maurer'@'localhost'; FLUSH PRIVILEGES;"
```

Sous XAMPP, `root` n'a pas de mot de passe par défaut. Ailleurs, ajouter `-p` et saisir le
mot de passe.

### 3.3 Charger les scripts, dans l'ordre

Depuis la racine du projet. L'option `--default-character-set=utf8mb4` est indispensable :
sans elle, les accents des libellés arrivent cassés dans la base.

```bash
# Windows (Git Bash)
M="/c/xampp/mysql/bin/mysql.exe"
$M -u root --default-character-set=utf8mb4 < database/schema.sql
$M -u root --default-character-set=utf8mb4 < database/seed.sql
$M -u root --default-character-set=utf8mb4 < database/donnees_test.sql

# Windows (invite de commandes cmd ; depuis PowerShell, préfixer chaque ligne de cmd /c)
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\schema.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\seed.sql
C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\donnees_test.sql

# Linux / macOS
mysql -u root -p --default-character-set=utf8mb4 < database/schema.sql
mysql -u root -p --default-character-set=utf8mb4 < database/seed.sql
mysql -u root -p --default-character-set=utf8mb4 < database/donnees_test.sql
```

- `schema.sql` supprime et recrée la base `formation_humaine_db` (26 tables). Ne jamais le
  rejouer sur une base qui contient des données réelles.
- `seed.sql` charge les rôles, les domaines, le barème, les paramètres et le compte administrateur.
- `donnees_test.sql` est facultatif : trois étudiants, quatre membres du personnel, des séances,
  des signalements et des mouvements de points pour essayer l'application. À ne pas charger sur
  un serveur de production.

### 3.4 Vérifier

```bash
C:\xampp\mysql\bin\mysql.exe -u root formation_humaine_db -e "SELECT COUNT(*) AS tables_creees FROM information_schema.tables WHERE table_schema='formation_humaine_db'; SELECT COUNT(*) AS parametres FROM PARAMETRE_SYSTEME; SELECT NOM_DOMAINE FROM DOMAINE;"
```

Attendu : 26 tables, 15 paramètres, et les quatre domaines avec leurs accents intacts
(« Comportement écologique », « Action citoyenne »). Des caractères comme `Ã©` à la place de
`é` signalent un import sans `utf8mb4` : recommencer l'étape 3.3.

## 4. Configuration

Un seul fichier compte : `config/database.php`. Il définit :

| Constante | Rôle | Valeur par défaut |
|---|---|---|
| `DB_HOST` | serveur MySQL | `localhost` |
| `DB_NAME` | base | `formation_humaine_db` |
| `DB_USER`, `DB_PASS` | compte MySQL | `Maurer` / `20031975` |
| `BASE_URL` | adresse à laquelle l'application est servie, sans barre finale | `http://localhost:8000` |

`BASE_URL` doit correspondre exactement à la façon dont l'application est servie (étape 5) :
elle sert aux redirections après connexion et après chaque formulaire. Les chemins vers les
feuilles de style et les images sont relatifs et n'en dépendent pas.

Le fichier fixe aussi le fuseau horaire (`Africa/Ndjamena`, `+01:00`) pour PHP et pour la
connexion MySQL : les délais (24 h pour déposer un justificatif) sont calculés de la même façon
des deux côtés. Ne pas le modifier sans changer les deux valeurs ensemble.

`config/config.php` est une copie ancienne du même fichier ; l'application ne le charge pas.
Il peut être supprimé.

Le fichier `config/database.php` est versionné avec les accès de développement. Sur un serveur,
après l'avoir modifié, empêcher Git de proposer la modification :
`git update-index --assume-unchanged config/database.php`.

## 5. Lancer l'application

### Option A : serveur intégré de PHP (développement)

Le plus simple, sans configuration d'Apache. Depuis la racine du projet :

```bash
# Windows
C:\xampp\php\php.exe -S localhost:8000 -t public

# Linux / macOS
php -S localhost:8000 -t public
```

Laisser la fenêtre ouverte et ouvrir `http://localhost:8000`. `BASE_URL` par défaut correspond
déjà à cette adresse. `Ctrl+C` arrête le serveur.

### Option B : Apache de XAMPP

1. Placer le projet dans `C:\xampp\htdocs\Formation_Humaine_CBS` (ou créer un lien).
2. Dans `config/database.php`, mettre
   `define('BASE_URL', 'http://localhost/Formation_Humaine_CBS/public');`
3. Démarrer Apache depuis le panneau XAMPP et ouvrir
   `http://localhost/Formation_Humaine_CBS/public/`.

Pour une adresse plus propre, déclarer un hôte virtuel dont le `DocumentRoot` est le dossier
`public/` et adapter `BASE_URL` (voir l'étape 8).

### Vérifier

La page de connexion s'affiche avec le panneau de présentation à gauche et le formulaire à
droite. Une page blanche ou un message « Erreur de connexion à la base de données » renvoie à
l'étape 9.

## 6. Première connexion

| Compte | Identifiant | Mot de passe | Espace |
|---|---|---|---|
| Administrateur (créé par `seed.sql`) | `admin@formation.local` ou `ADM-2026-001` | `Admin123!` | personnel, tous les droits |

Se connecter, ouvrir **Mon compte** et changer le mot de passe immédiatement.

Avec `donnees_test.sql`, mot de passe `Test1234!` pour tous :

| Personne | Identifiant | Rôle |
|---|---|---|
| Enock PANDA | `enock.panda@cbs.local` | chargé de discipline |
| Sylvie NDOUBA | `sylvie.ndouba@cbs.local` | responsable de la Formation Humaine |
| Marie TCHOUA | `marie.tchoua@cbs.local` | enseignante |
| Idriss MAHAMAT | `idriss.mahamat@cbs.local` | responsable du club environnement |
| Awa KOUASSI | `CBS2026-0001` | étudiante, déléguée de promotion |
| Moussa DIALLO | `CBS2026-0002` | étudiant |
| Chantal NGUEMA | `CBS2026-0003` | étudiante |

Un étudiant se connecte avec son matricule ou son email ; le menu et les pages accessibles
dépendent du rôle (`documentation/espace-personnel.md`, section 1).

Il n'existe pas d'inscription en ligne : les comptes sont créés par l'administration
(**Étudiants** → *Nouvel étudiant* ou *Importer un CSV* ; **Comptes** pour le personnel). Un mot
de passe temporaire est affiché une seule fois à la création ; la personne doit le changer à sa
première connexion.

## 7. Dossiers d'écriture

L'application écrit dans quatre dossiers, qui existent déjà dans le dépôt :

| Dossier | Contenu |
|---|---|
| `storage/justificatifs/` | justificatifs d'absence (hors racine web, servis par l'application) |
| `storage/preuves/` | pièces jointes des signalements (idem) |
| `public/assets/uploads/` | photos de profil |
| `logs/` | réservé aux journaux applicatifs |

Sous Windows aucun réglage n'est nécessaire. Sous Linux, donner l'écriture à l'utilisateur
qui exécute PHP (par exemple `www-data` avec Apache) :

```bash
sudo chown -R www-data:www-data storage public/assets/uploads logs
sudo chmod -R u+rwX storage public/assets/uploads logs
```

Les fichiers envoyés sont limités à 10 Mo. Si `php.ini` est plus restrictif, relever
`upload_max_filesize = 10M` et `post_max_size = 32M` (plusieurs preuves peuvent être envoyées
en une fois), puis redémarrer Apache.

## 8. Mise sur un serveur

1. **Racine web** : pointer le `DocumentRoot` (Apache) ou `root` (Nginx) sur le dossier
   `public/`, jamais sur la racine du projet, pour que `config/`, `storage/`, `database/` et
   `documentation/` restent inaccessibles depuis le navigateur.
2. **`BASE_URL`** : l'adresse publique, avec `https://` si un certificat est en place.
3. **Erreurs PHP** : dans `public/index.php`, passer `display_errors` et
   `display_startup_errors` à `0` ; garder `log_errors` actif dans `php.ini` pour retrouver les
   erreurs dans le journal du serveur.
4. **Base** : charger `schema.sql` et `seed.sql` seulement ; ne pas charger
   `donnees_test.sql`. Changer le mot de passe de l'administrateur dès la première connexion.
5. **Sauvegardes** : `mysqldump -u root -p formation_humaine_db > sauvegarde.sql` régulièrement,
   plus une copie des dossiers `storage/` et `public/assets/uploads/`.
6. **Courriel** : l'application n'envoie aucun courriel pour l'instant (mots de passe remis de
   la main à la main). La marche à suivre pour brancher Resend est dans
   `documentation/courriel-resend.md`.
7. **Mise à jour d'une base déjà en place** : la version actuelle rend `PERSONNE.DATE_NAISSANCE`
   facultative. Sur une base créée avec une version antérieure de `schema.sql`, exécuter
   `ALTER TABLE PERSONNE MODIFY DATE_NAISSANCE DATE NULL;`. Les autres évolutions du schéma sont
   listées dans `documentation/base-de-donnees.md`.

## 9. Problèmes fréquents

| Symptôme | Cause probable | Que faire |
|---|---|---|
| « Erreur de connexion à la base de données : Access denied » | utilisateur ou mot de passe MySQL différents de `config/database.php` | recréer l'utilisateur (3.2) ou corriger le fichier (4) |
| « Erreur de connexion … Unknown database » | scripts SQL non chargés | étape 3.3 |
| Page blanche ou erreur 500 | extension PHP manquante, ou erreur dans un fichier | vérifier `php -m` (1) ; regarder le journal d'erreurs de PHP ; `php -l fichier.php` |
| Après connexion, redirection vers une adresse qui n'existe pas | `BASE_URL` ne correspond pas à la façon dont l'application est servie | corriger `BASE_URL` (4 et 5) |
| « Formulaire expiré, veuillez réessayer » | session perdue entre l'affichage et l'envoi du formulaire (cookies bloqués, deux onglets avec des sessions différentes, session expirée) | recharger la page et renvoyer ; vérifier que le navigateur accepte les cookies pour ce site |
| Fichier refusé à l'envoi | taille au-dessus de 10 Mo, extension non prévue, ou contenu qui ne correspond pas à l'extension | envoyer un pdf, jpg, png, webp ou mp4 authentique ; vérifier `upload_max_filesize` (7) |
| Accents cassés dans les listes (`Ã©`) | import SQL sans `utf8mb4` | recharger les trois scripts (3.3) |
| Photos ou pièces jointes introuvables | dossiers d'écriture non accessibles à PHP | droits (7) |
| Heures décalées d'une heure | fuseau horaire modifié d'un seul côté | remettre `Africa/Ndjamena` et `+01:00` ensemble (4) |
| `tests/recette.sh` échoue dès le rechargement | `mysql` introuvable ou compte différent | `MYSQL=/c/xampp/mysql/bin/mysql.exe bash tests/recette.sh`, ou `DB_USER` / `DB_PASS` (`documentation/tests.md`) |

## 10. Vérifier l'ensemble avec la recette

Sur un poste de développement seulement (le script recharge la base et efface `storage/`) :

```bash
bash tests/recette.sh
```

Attendu : `--- résultat : 59 ok, 0 ko ---`. Le script rejoue les 23 cas de l'espace étudiant
puis les 23 cas de l'espace personnel contre l'application en cours d'exécution
(`documentation/tests.md`).

## 11. Pour aller plus loin

- `documentation/base-de-donnees.md` : schéma, historique et évolutions.
- `documentation/espace-etudiant.md` : règles métier et écrans de l'espace étudiant et délégué.
- `documentation/espace-personnel.md` : rôles, permissions, modules et règles de l'espace personnel.
- `documentation/courriel-resend.md` : envoi de courriels.
- `documentation/tests.md` : la recette en détail.
