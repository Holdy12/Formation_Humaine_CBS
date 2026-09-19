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

Sous Debian ou Ubuntu, installer les paquets génériques : les noms versionnés (`php8.1`,
`php8.2`) n'existent pas dans les dépôts récents, qui ne fournissent que la version de PHP
de la distribution.

```bash
sudo apt install php php-cli php-mysql php-mbstring mariadb-server
sudo systemctl enable --now mariadb
```

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

L'application se connecte avec le compte MySQL renseigné dans `.env` (étape 4). Créer ce
compte, en remplaçant `formation` et `un-mot-de-passe-solide` par les valeurs choisies :

```bash
# Windows
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE USER IF NOT EXISTS 'formation'@'localhost' IDENTIFIED BY 'un-mot-de-passe-solide'; GRANT ALL PRIVILEGES ON formation_humaine_db.* TO 'formation'@'localhost'; FLUSH PRIVILEGES;"

# Linux
sudo mysql -e "CREATE USER IF NOT EXISTS 'formation'@'localhost' IDENTIFIED BY 'un-mot-de-passe-solide'; GRANT ALL PRIVILEGES ON formation_humaine_db.* TO 'formation'@'localhost'; FLUSH PRIVILEGES;"
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

- `schema.sql` supprime et recrée la base `formation_humaine_db` (28 tables). Ne jamais le
  rejouer sur une base qui contient des données réelles.
- `seed.sql` charge les rôles, les domaines, le barème, les paramètres et le compte administrateur.
- `donnees_test.sql` est facultatif : trois étudiants, quatre membres du personnel, des séances,
  des signalements et des mouvements de points pour essayer l'application. À ne pas charger sur
  un serveur de production.

### 3.4 Vérifier

```bash
C:\xampp\mysql\bin\mysql.exe -u root formation_humaine_db -e "SELECT COUNT(*) AS tables_creees FROM information_schema.tables WHERE table_schema='formation_humaine_db'; SELECT COUNT(*) AS parametres FROM PARAMETRE_SYSTEME; SELECT NOM_DOMAINE FROM DOMAINE;"
```

Attendu : 28 tables, 15 paramètres, et les quatre domaines avec leurs accents intacts
(« Comportement écologique », « Action citoyenne »). Des caractères comme `Ã©` à la place de
`é` signalent un import sans `utf8mb4` : recommencer l'étape 3.3.

## 4. Configuration

Les accès et l'adresse de l'application sont lus dans un fichier `.env` à la racine du projet,
jamais versionné. Le créer à partir du modèle fourni :

```bash
cp .env.example .env      # Windows : copy .env.example .env
```

puis renseigner les valeurs :

| Variable | Rôle | Valeur par défaut |
|---|---|---|
| `DB_HOST` | serveur MySQL | `localhost` |
| `DB_NAME` | base | `formation_humaine_db` |
| `DB_USER`, `DB_PASS` | compte MySQL créé à l'étape 3.2 | vide (obligatoire) |
| `BASE_URL` | adresse à laquelle l'application est servie, sans barre finale | `http://localhost:8000` |
| `APP_TIMEZONE` | fuseau horaire de PHP et de la connexion MySQL | `Africa/Ndjamena` |
| `APP_DEBUG` | affichage des erreurs PHP : `true` en développement, `false` ailleurs | `false` |

`BASE_URL` doit correspondre exactement à la façon dont l'application est servie (étape 5) :
elle sert aux redirections après connexion et après chaque formulaire. Les chemins vers les
feuilles de style et les images sont relatifs et n'en dépendent pas.

`APP_TIMEZONE` s'applique aux deux côtés à la fois : `config/database.php` aligne le fuseau de
la connexion MySQL sur celui de PHP, pour que les délais (24 h pour déposer un justificatif)
soient calculés de la même façon.

Une variable d'environnement du système portant le même nom a priorité sur le fichier : sur un
serveur, les accès peuvent être fournis par l'hébergeur sans fichier `.env`. Sans fichier et sans
variables, l'application s'arrête avec un message explicite au lieu de tenter une connexion.

Le mot de passe MySQL et les autres secrets ne figurent dans aucun fichier suivi par Git :
`config/database.php` ne contient que la lecture de `.env` et l'ouverture de la connexion.

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
2. Dans `.env`, mettre `BASE_URL=http://localhost/Formation_Humaine_CBS/public`
3. Démarrer Apache depuis le panneau XAMPP et ouvrir
   `http://localhost/Formation_Humaine_CBS/public/`.

Pour une adresse plus propre, déclarer un hôte virtuel dont le `DocumentRoot` est le dossier
`public/` et adapter `BASE_URL` (voir l'étape 8).

Les pages du site vitrine ont des adresses courtes (`/formations`, `/vie-etudiante`, `/contact`,
`/connexion`). Sous Apache, le fichier `public/.htaccess` les renvoie vers `index.php` : il faut
`mod_rewrite` et `AllowOverride All` sur le dossier (c'est le cas par défaut avec XAMPP). Sans
réécriture, les adresses en `index.php?action=…` restent toutes valables.

### Vérifier

La page d'accueil du site s'affiche, avec le bouton « Se connecter » dans l'en-tête ; il mène à la
page de connexion, avec le panneau de présentation à gauche et le formulaire à droite. Une page
blanche ou un message « Erreur de connexion à la base de données » renvoie à l'étape 9.

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
   `documentation/` restent inaccessibles depuis le navigateur. Pour les adresses courtes du site
   vitrine : sous Apache, `AllowOverride All` sur `public/` (le `.htaccess` fait le reste) ; sous
   Nginx, dans le bloc `server`, `location / { try_files $uri /index.php?$args; }`. Si
   `mod_expires` est actif, le même `.htaccess` fait garder feuilles de style, script, police
   et photos en cache par le navigateur (sous Nginx : `expires 1y;` sur `/assets/`).
2. **`.env`** : créer le fichier sur le serveur (ou fournir les variables par l'hébergeur) avec
   un compte MySQL dédié et `BASE_URL` à l'adresse publique, avec `https://` si un certificat
   est en place. Ne jamais copier le `.env` d'un poste de développement.
3. **Erreurs PHP** : laisser `APP_DEBUG=false` dans le `.env` du serveur (valeur par défaut).
   Les erreurs restent écrites dans le journal du serveur (`log_errors`), mais ne sont jamais
   envoyées au navigateur : affichées, elles exposeraient les chemins du serveur et
   corrompraient les exports CSV et les fichiers téléchargés.
4. **Base** : charger `schema.sql` et `seed.sql` seulement ; ne pas charger
   `donnees_test.sql`. Changer le mot de passe de l'administrateur dès la première connexion.
5. **Sauvegardes** : `mysqldump -u root -p formation_humaine_db > sauvegarde.sql` régulièrement,
   plus une copie des dossiers `storage/` et `public/assets/uploads/`.
6. **Courriel** : l'application n'envoie aucun courriel pour l'instant (mots de passe remis de
   la main à la main). La marche à suivre pour brancher Resend est dans
   `documentation/courriel-resend.md`.
7. **Mise à jour d'une base déjà en place** : la version actuelle rend `PERSONNE.DATE_NAISSANCE`
   facultative, lui ajoute `RESET_TOKEN` et `RESET_EXPIRES_AT` (mot de passe oublié) et ajoute
   les tables `TENTATIVE_CONNEXION` et `JETON_CONNEXION` (appareils mémorisés, « rester
   connecté »). Sur une base créée avec une version antérieure de `schema.sql`, exécuter
   `ALTER TABLE PERSONNE MODIFY DATE_NAISSANCE DATE NULL;`, puis l'`ALTER TABLE` et les deux
   `CREATE TABLE` donnés dans `documentation/base-de-donnees.md`, qui liste les autres
   évolutions du schéma.
8. **Mise à jour d'un poste existant** : les accès ont quitté `config/database.php` ; après
   `git pull`, créer `.env` (étape 4) avant de relancer l'application. Le mot de passe MySQL qui
   figurait dans les anciennes versions du fichier reste dans l'historique Git : le changer
   (`ALTER USER ... IDENTIFIED BY ...`) et reporter la nouvelle valeur dans `.env`.

## 9. Problèmes fréquents

| Symptôme | Cause probable | Que faire |
|---|---|---|
| « Erreur de connexion à la base de données : Access denied » | utilisateur ou mot de passe MySQL différents de `.env` | recréer l'utilisateur (3.2) ou corriger `.env` (4) |
| « Fichier .env introuvable » | `.env` absent à la racine du projet | le créer à partir de `.env.example` (4) |
| « Erreur de connexion … Unknown database » | scripts SQL non chargés | étape 3.3 |
| Page blanche ou erreur 500 | extension PHP manquante, ou erreur dans un fichier | vérifier `php -m` (1) ; regarder le journal d'erreurs de PHP ; `php -l fichier.php` |
| Après connexion, redirection vers une adresse qui n'existe pas | `BASE_URL` ne correspond pas à la façon dont l'application est servie | corriger `BASE_URL` (4 et 5) |
| « Formulaire expiré, veuillez réessayer » | session perdue entre l'affichage et l'envoi du formulaire (cookies bloqués, deux onglets avec des sessions différentes, session expirée) | recharger la page et renvoyer ; vérifier que le navigateur accepte les cookies pour ce site |
| Fichier refusé à l'envoi | taille au-dessus de 10 Mo, extension non prévue, ou contenu qui ne correspond pas à l'extension | envoyer un pdf, jpg, png, webp ou mp4 authentique ; vérifier `upload_max_filesize` (7) |
| Accents cassés dans les listes (`Ã©`) | import SQL sans `utf8mb4` | recharger les trois scripts (3.3) |
| Photos ou pièces jointes introuvables | dossiers d'écriture non accessibles à PHP | droits (7) |
| Heures décalées d'une heure | `APP_TIMEZONE` ne correspond pas au fuseau réel | corriger `APP_TIMEZONE` dans `.env` (4) |
| `tests/recette.sh` échoue dès le rechargement | `mysql` introuvable ou compte différent | `MYSQL=/c/xampp/mysql/bin/mysql.exe bash tests/recette.sh`, ou `DB_USER` / `DB_PASS` (`documentation/tests.md`) |

## 10. Vérifier l'ensemble avec la recette

Sur un poste de développement seulement (le script recharge la base et efface `storage/`) :

```bash
bash tests/recette.sh
```

Attendu : `--- résultat : 61 ok, 0 ko ---`. Le script rejoue les cas de l'espace étudiant
puis ceux de l'espace personnel contre l'application en cours d'exécution
(`documentation/tests.md`).

## 11. Pour aller plus loin

- `documentation/base-de-donnees.md` : schéma, historique et évolutions.
- `documentation/espace-etudiant.md` : règles métier et écrans de l'espace étudiant et délégué.
- `documentation/espace-personnel.md` : rôles, permissions, modules et règles de l'espace personnel.
- `documentation/courriel-resend.md` : envoi de courriels.
- `documentation/tests.md` : la recette en détail.
