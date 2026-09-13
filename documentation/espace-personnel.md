# Espace personnel

Conception de la partie de l'application réservée au personnel : administrateur, responsable de
la Formation Humaine, chargé de discipline, enseignants, responsables de club et du club
environnement. Un seul espace, dont le menu et les actions dépendent des permissions du rôle.

Cette version remplace les premières pages d'administration (tableau de bord et gestion des
étudiants) par des écrans construits sur les mêmes fondations que l'espace étudiant.

## 1. Rôles et permissions

| Code rôle | Profil |
|---|---|
| `ADMIN` | Administrateur |
| `RESPONSABLE_FH` | Responsable de la Formation Humaine |
| `CHARGE_DISCIPLINE` | Chargé de discipline |
| `ENSEIGNANT` | Enseignant |
| `RESPONSABLE_CLUB` | Responsable de club |
| `RESPONSABLE_ENV` | Responsable du club environnement (mêmes droits qu'un responsable de club, sur son club) |

Les droits sont exprimés par des permissions, définies une seule fois dans `core/Permissions.php`
et vérifiées par `Auth::exigerPermission()` dans les routes et `Auth::peut()` dans les vues.

| Permission | ADMIN | RESP. FH | CHARGÉ DISC. | ENSEIGNANT | RESP. CLUB / ENV |
|---|:-:|:-:|:-:|:-:|:-:|
| `tableau.voir` | x | x | x | x | x |
| `etudiants.consulter` (liste, fiche) | x | x | x | x | x |
| `etudiants.gerer` (créer, modifier, importer, désactiver, réinitialiser) | x | x | | | |
| `signalements.creer` | x | x | x | x | x |
| `signalements.consulter_tous` (sinon : uniquement ceux que l'on a émis) | x | x | x | | |
| `signalements.instruire` (examen, audition, décision) | x | x | x | | |
| `pieces.consulter` (pièces jointes ; l'auteur voit toujours les siennes) | x | x | x | | |
| `appel.faire` | x | x | x | x | x |
| `justificatifs.valider` | x | x | x | | |
| `assiduite.penaliser` | x | x | x | | |
| `points.consulter` (registre global) | x | x | x | | |
| `points.corriger` (écriture inverse) | x | x | | | |
| `clubs.gerer` (créer un club, nommer un responsable) | x | x | | | |
| `club.animer` (membres et séances de son club ; tous les clubs pour ADMIN et RESP. FH) | x | x | | | x |
| `structure.gerer` (années, semestres, filières, niveaux, promotions) | x | | | | |
| `semestre.cloturer` (clôture et réouverture) | x | x | | | |
| `bareme.gerer` (domaines, critères, paramètres système) | x | | | | |
| `comptes.gerer` (comptes du personnel, rôles) | x | | | | |
| `rapports.consulter` | x | x | x | | |
| `journal.consulter` | x | x | | | |

Règles transverses :

- Nul ne décide sur un dossier qu'il a lui-même signalé, quel que soit son rôle.
- Un retrait de points suite à un signalement n'est possible qu'après audition de l'étudiant
  (instruction contradictoire). Une bonification peut être validée sans audition.
- Le chargé de discipline ne modifie pas le barème ; le responsable FH ne modifie pas le journal.
- La permission d'une route est un contrôle d'accès grossier ; la portée (son propre club, ses
  propres signalements) est vérifiée dans le contrôleur, sur chaque enregistrement manipulé.
- Les pénalités d'assiduité ne demandent pas d'audition : la fenêtre de dépôt du justificatif
  est l'étape contradictoire de l'étudiant.

## 2. Architecture

```
core/Routeur.php                          table des routes admin_* → contrôleur, méthode, permission
core/Permissions.php                      matrice rôle → permissions
core/Auth.php                             + exigerPersonnel(), exigerPermission(), peut(), connecter()
core/Journal.php                          écriture du journal d'audit
app/Controllers/AuthController.php        connexion, déconnexion, première connexion, mot de passe oublié
app/Controllers/PersonnelController.php   base commune : gabarit, menu, messages, pagination
app/Controllers/Admin/TableauDeBordController.php
app/Controllers/Admin/EtudiantController.php
app/Controllers/Admin/SignalementController.php
app/Controllers/Admin/PresenceController.php
app/Controllers/Admin/PointController.php
app/Controllers/Admin/ClubController.php
app/Controllers/Admin/StructureController.php
app/Controllers/Admin/BaremeController.php
app/Controllers/Admin/CompteController.php
app/Controllers/Admin/RapportController.php
app/Controllers/Admin/JournalController.php
app/Models/Personne.php                   comptes (personnel et étudiants), mots de passe, statut
app/Models/Structure.php                  années, semestres, filières, niveaux, promotions, clôture
app/Models/Club.php                       clubs, membres, séances
app/Models/Bareme.php                     domaines, critères, paramètres
app/Models/Rapport.php                    requêtes agrégées des rapports
app/Models/Etudiant.php, Signalement.php, Presence.php, MouvementPoint.php   étendus
app/Views/partials/entete.php, pied.php   gabarit commun aux deux espaces (menu et utilisateur fournis par le contrôleur)
app/Views/auth/                           connexion, premiere_connexion, mot_de_passe_oublie
app/Views/admin/<module>/                 une vue par page, un dossier par module
public/assets/css/espace.css              feuille commune (anciennement etudiant.css)
public/assets/css/personnel.css           compléments propres à l'espace personnel
public/assets/js/espace.js                thème, tiroir de navigation, zones de fichier, sélecteurs
public/index.php                          switch réduit : connexion, déconnexion, etudiant_* ; admin_* délégué au Routeur
```

Fichiers retirés : les premiers contrôleurs et vues d'administration, `dashboard.js` et la
dépendance Chart.js (graphiques rendus en CSS et SVG). `dashboard.css` reste la base commune.

### Routage

`public/index.php` conserve son `switch` pour la connexion, la déconnexion et les routes
`etudiant_*`. Toute action commençant par `admin_` est confiée à `Routeur::traiter($action)`,
qui lit une table `action → [contrôleur, méthode, permission]`, vérifie que l'utilisateur est
connecté et fait partie du personnel, vérifie la permission, puis appelle la méthode. Une action
inconnue affiche la page 404 ; une permission manquante la page 403.

Les anciennes actions (`dashboard`, `etudiants`, `voir_etudiant`…) redirigent vers leurs
équivalentes `admin_*` pour ne casser aucun lien.

### Gabarit

`entete.php` reçoit `$titre`, `$actif`, `$utilisateur` (nom, sous-titre, photo) et `$menu`
(sections et entrées). L'espace étudiant construit son menu comme aujourd'hui ; l'espace
personnel construit le sien à partir des permissions du rôle, de sorte qu'aucune entrée
inaccessible n'apparaît.

Menu de l'espace personnel (entrées filtrées par permission) :

- Tableau de bord
- Étudiants
- Signalements : tous les dossiers · mes signalements · nouveau
- Présences : faire l'appel · séances · justificatifs · assiduité
- Points
- Clubs
- Structure académique
- Barème et paramètres
- Comptes
- Rapports
- Journal
- Mon compte

### Connexion

- Identifiant : adresse email ou matricule ; mot de passe vérifié par `password_verify()`
  uniquement. Le mot de passe passe-partout de développement est supprimé.
- Un compte dont `STATUT_COMPTE` n'est pas `ACTIF` ne peut pas se connecter (message dédié).
- À la connexion : régénération de l'identifiant de session, variables `user_id`, `user_role`,
  `nom`, `prenom`, écriture d'une ligne `JOURNAL_CONNEXION` (`SUCCES` ou `ECHEC`).
- Après cinq échecs consécutifs dans la même session, un délai de trente secondes est imposé
  (limitation connue : le compteur est lié à la session ; un blocage par adresse IP pourra
  compléter ce mécanisme).
- Le formulaire de connexion porte lui aussi un jeton CSRF.
- Si `PERSONNE.DOIT_CHANGER_MDP = 1`, l'utilisateur est conduit à la page de première connexion
  et ne peut rien faire d'autre avant d'avoir choisi un nouveau mot de passe (huit caractères
  minimum, différent du temporaire).
- Redirection : `ETUDIANT` vers `etudiant_dashboard`, tout rôle du personnel vers
  `admin_dashboard`, rôle inconnu vers la connexion avec un message.
- « Mot de passe oublié » : page expliquant la procédure assistée (demande à l'administration,
  qui réinitialise le mot de passe depuis la fiche du compte). Voir la section Évolutions.
- La page de connexion reprend l'identité visuelle de l'application (bandeau noir et orange,
  formulaire clair, affichage du mot de passe, messages d'erreur explicites, adaptée au téléphone).

## 3. Modules

### Tableau de bord

Bloc « À traiter » en tête, selon les permissions : signalements à instruire (`SOUMIS`,
`EN_EXAMEN`), auditions à mener (`EN_EXAMEN` avec critère négatif), justificatifs en attente,
présences à pénaliser, semestre échu non clôturé. Chaque carte mène à la liste filtrée.

Puis, selon le rôle :

- Administrateur et responsable FH : effectifs (étudiants actifs, promotions, comptes), points
  du semestre par domaine (barres), évolution mensuelle des signalements (barres), étudiants sous
  le seuil de 10, dernières entrées du journal.
- Chargé de discipline : dossiers par statut, justificatifs et pénalités en attente, derniers
  dossiers décidés.
- Enseignant, responsables de club : mes signalements récents et leur statut, mes derniers appels,
  mon club (membres, prochaines séances).

### Étudiants

- Liste : recherche (nom, prénom, matricule, email), filtres promotion, niveau, filière, statut de
  compte ; tri ; pagination par 25 ; export CSV de la sélection.
- Fiche : identité et photo, cursus, solde du semestre (sélecteur), registre des points,
  présences, signalements, justificatifs ; actions selon permission : modifier, nouveau
  signalement pré-rempli, réinitialiser le mot de passe, désactiver ou réactiver, supprimer
  (uniquement sans historique).
- Ajout et modification : identité, coordonnées, promotion, club, délégué, photo. Le matricule
  est généré (`CBS<année>-<numéro>`, numéro suivant de l'année) ; un mot de passe temporaire est
  généré, affiché une seule fois et impose un changement à la première connexion.
- Import CSV : modèle téléchargeable ; colonnes `nom, prenom, email, telephone, sexe,
  date_naissance, adresse, code_promo` ; contrôle ligne par ligne (email unique, promotion
  existante, date valide) avec rapport des erreurs avant enregistrement ; création en une
  transaction ; résultat avec les mots de passe temporaires, téléchargeable en CSV une seule fois.
- Désactivation plutôt que suppression dès qu'un historique existe (présences, points,
  signalements), pour préserver la traçabilité.

### Signalements

- Liste : filtres statut, domaine, promotion, période, auteur ; les rôles sans
  `signalements.consulter_tous` ne voient que leurs dossiers.
- Nouveau : un ou plusieurs étudiants (sélecteur avec recherche ; plusieurs étudiants pour une
  sanction collective, un dossier par étudiant), critère, objet, date et heure, lieu, description,
  témoins (nom, prénom, contact), preuves. Statut `SOUMIS`.
- Instruction (permission `signalements.instruire`, auteur exclu) :
  1. `SOUMIS` → « Ouvrir l'instruction » → `EN_EXAMEN`.
  2. Audition : date, compte rendu → `ETUDIANT_ENTENDU`. Obligatoire avant toute décision qui
     retire des points.
  3. Décision : `VALIDE`, `REJETE` ou `ANNULE`, avec motif ; case « transmis au conseil de
     discipline », proposée uniquement pour un critère négatif.
  4. À `VALIDE` : écriture dans `MOUVEMENT_POINTS` (critère, valeur absolue, sens, validateur,
     lien vers le dossier) ; si conseil de discipline, écriture supplémentaire du critère
     « Passage au conseil de discipline ». Pour une bonification, le plafond du domaine est
     vérifié sur le semestre : s'il serait dépassé, la validation est refusée avec le montant
     restant disponible.
  5. « Clôturer » archive un dossier décidé (`CLOTURE`).
- Chaque changement de statut est enregistré dans `SIGNALEMENT_HISTORIQUE` (statut, auteur,
  date, commentaire) ; le dossier affiche la réponse de l'étudiant, les témoins, les pièces
  (selon permission) et cet historique.

### Présences

- Faire l'appel : cible = une promotion, ou un club que l'on anime ; soit une séance planifiée
  sans appel, soit une nouvelle séance saisie sur place ; statut par étudiant ; enregistrement
  de `SEANCE` (si nouvelle), `APPEL` et `PRESENCE`. Aucun point n'est écrit à cette étape.
- Séances : liste et planification (club ou promotion, titre, date, heures, lieu) ; une séance
  planifiée peut recevoir son appel plus tard.
- Justificatifs : file d'attente `EN_ATTENTE` ; consultation de la pièce ; « Valider » passe le
  justificatif à `VALIDEE` et la présence à `ABSENT_JUSTIFIE` ; « Rejeter » à `REJETEE` ; un
  commentaire est enregistré et visible par l'étudiant.
- Assiduité à pénaliser : présences `ABSENT` sans justificatif valide ou en attente et dont le
  délai de dépôt est expiré, et présences `RETARD`, sans pénalité déjà écrite. Application
  individuelle ou groupée : mouvement négatif avec le critère Absence ou Retard du domaine de la
  séance (Discipline pour une promotion, Participation aux clubs pour un club), validateur,
  `ID_PRESENCE` renseigné pour empêcher toute double pénalité.

### Points

- Date d'un mouvement : la date des faits (date de la séance pour l'assiduité, `DATE_FAITS`
  pour un signalement), afin qu'il compte dans le bon semestre ; la date d'application est
  celle de l'entrée du journal.
- Aucun mouvement ne peut être écrit sur la période d'un semestre clôturé : la validation, la
  pénalité ou la correction est refusée avec un message invitant à rouvrir le semestre.
- Registre global : filtres étudiant, promotion, domaine, sens, période ; pagination ; export CSV.
- Correction : depuis un mouvement, « écriture inverse » crée le mouvement opposé avec un motif
  obligatoire et référence au mouvement corrigé. Aucun mouvement n'est jamais modifié ni supprimé.

### Clubs et séances

- Clubs : création, description, responsable (personne du rôle `RESPONSABLE_CLUB` ou
  `RESPONSABLE_ENV`), effectif.
- Membres : ajout et retrait d'étudiants (sélecteur avec recherche), par le responsable du club
  ou l'administration.
- Séances du club : planification et appel (voir Présences).

### Structure académique

Années académiques, semestres, départements, filières, niveaux, promotions : listes et
formulaires ; suppression refusée s'il existe des données liées (message explicite).

Clôture d'un semestre (`semestre.cloturer`) : pour chaque étudiant actif inscrit dans une
promotion de l'année, calcul du solde sur la période, écriture ou mise à jour de
`RESULTAT_SEMESTRIEL` (`NOTE_FINALE`, `MENTION`, `STATUT_VALIDATION = 'CLOTURE'`,
`DATE_CLOTURE`). Mentions : 16 et plus Très bien, 14 Bien, 12 Assez bien, 10 Passable, en
dessous Insuffisant (seuils à confirmer par la direction, modifiables dans `PARAMETRE_SYSTEME`).
La réouverture repasse le semestre en `PROVISOIRE`.

### Barème et paramètres

Domaines (libellé), critères (libellé, valeur signée, actif ou inactif — un critère utilisé n'est
jamais supprimé, il est désactivé), paramètres système (valeur numérique contrôlée, description).

### Comptes et rôles

- Comptes du personnel : liste, création (identité, coordonnées, rôle, grade ou poste),
  modification, activation et désactivation, réinitialisation du mot de passe (temporaire affiché
  une seule fois, changement imposé à la première connexion).
- Rôles : liste des rôles et de leurs permissions, en lecture, générée depuis la matrice.
- Mon compte : photo, coordonnées, mot de passe (tout membre du personnel).

### Rapports

Chaque rapport propose un filtre de période ou de semestre, un affichage à l'écran, un export
CSV (encodage UTF-8 avec BOM pour Excel) et une mise en page d'impression.

- Résultats par promotion : étudiant, solde ou note finale, mention.
- Sanctions et bonifications par période : par domaine et critère, nombre et points.
- Assiduité : absences, absences justifiées et retards par promotion.
- Bilan des clubs : membres, séances, taux de présence, bonifications.
- Étudiants sous le seuil critique (10).
- Fiche individuelle : le relevé de l'espace étudiant, ouvert depuis la fiche.

### Journal

Liste de `JOURNAL_CONNEXION` avec filtres (personne, période, action) et pagination. Entrées
écrites automatiquement : connexion réussie ou échouée, création et modification de compte,
réinitialisation de mot de passe, import, décision sur un signalement, validation ou rejet d'un
justificatif, pénalité d'assiduité, correction de points, clôture et réouverture de semestre,
changements de structure et de barème.

## 4. Schéma

Ajouts, tous facultatifs pour les lignes existantes :

- `MOUVEMENT_POINTS.ID_PRESENCE` (référence à `PRESENCE`) : une pénalité d'assiduité par présence.
- `MOUVEMENT_POINTS.ID_MOUVEMENT_CORRIGE` (référence à `MOUVEMENT_POINTS`) : lien d'une écriture inverse vers le mouvement corrigé.
- `SIGNALEMENT.DATE_AUDITION`, `SIGNALEMENT.NOTES_AUDITION`, `SIGNALEMENT.CONSEIL_DISCIPLINE`.
- `PERSONNE.DOIT_CHANGER_MDP` : changement de mot de passe imposé à la prochaine connexion.
- Table `SIGNALEMENT_HISTORIQUE` (`ID_HISTORIQUE`, `ID_SIGNALEMENT`, `STATUT`, `ID_PERSONNE`,
  `DATE_CHANGEMENT`, `COMMENTAIRE`).
- `PARAMETRE_SYSTEME` : seuils de mention (`MENTION_TRES_BIEN`, `MENTION_BIEN`,
  `MENTION_ASSEZ_BIEN`, `MENTION_PASSABLE`) et `SEUIL_CRITIQUE_NOTE`, lu aussi par la jauge
  de l'espace étudiant.
- Fuseau horaire : PHP et la connexion MySQL sont alignés sur `Africa/Ndjamena` dans
  `config/`, car les délais (justificatifs, périodes) sont calculés des deux côtés.

## 5. Interface

Mêmes principes que l'espace étudiant : gabarit commun, icônes SVG, états vides, messages après
chaque action, pages d'erreur, adaptation au téléphone, mode sombre, sélecteurs avec recherche,
zones de dépôt de fichier, listes paginées, filtres conservés dans l'adresse (partageables).
Les graphiques du tableau de bord sont rendus sans bibliothèque externe.

## 6. Cas de test

Comptes de `donnees_test.sql` (mot de passe `Test1234!`) : Enock PANDA (chargé de discipline),
Marie TCHOUA (enseignante), Idriss MAHAMAT (responsable du club environnement), plus
`admin@formation.local` / `Admin123!`. Le jeu de test ajoute un compte responsable FH.

| # | Scénario | Résultat attendu |
|---|---|---|
| 1 | Connexion avec le mot de passe `password` sur n'importe quel compte | refusée |
| 2 | Connexion de chaque rôle du personnel | redirection vers `admin_dashboard`, menu limité aux permissions |
| 3 | Compte désactivé | connexion refusée avec message dédié |
| 4 | Cinq échecs puis un essai | délai imposé |
| 5 | Enseignant ouvre `admin_structure` | page 403 |
| 6 | Enseignant crée un signalement avec deux étudiants et une preuve | deux dossiers `SOUMIS`, pièces attachées, visibles dans « mes signalements » |
| 7 | Enseignant tente d'instruire son propre dossier | refusé |
| 8 | Chargé de discipline ouvre, auditionne, valide avec conseil | `ETUDIANT_ENTENDU` puis `VALIDE`, deux mouvements négatifs (critère + conseil), validateur renseigné |
| 9 | Validation d'un retrait sans audition | refusée |
| 10 | Validation d'une bonification qui dépasserait le plafond | refusée, montant restant indiqué |
| 11 | Justificatif validé | `VALIDEE`, présence `ABSENT_JUSTIFIE`, commentaire visible côté étudiant |
| 12 | Assiduité : appliquer les pénalités puis recharger la page | mouvements créés une fois, plus rien à pénaliser |
| 13 | Écriture inverse d'un mouvement | mouvement opposé lié, solde rétabli |
| 14 | Clôture du semestre | `RESULTAT_SEMESTRIEL` clôturé pour chaque étudiant, note finale et mention visibles côté étudiant ; réouverture possible |
| 15 | Création d'un étudiant | matricule généré, mot de passe temporaire affiché, première connexion impose le changement |
| 16 | Import CSV avec une ligne invalide | rapport d'erreur, aucune création tant que le fichier n'est pas corrigé |
| 17 | Désactivation d'un étudiant | disparaît des appels, connexion refusée, historique conservé |
| 18 | Responsable de club ajoute un membre et fait l'appel d'une séance planifiée | membre visible côté étudiant, présences enregistrées |
| 19 | Modification du barème par le chargé de discipline | 403 |
| 20 | Journal après ces scénarios | une entrée par action sensible, filtrable |
| 21 | Export CSV d'un rapport | fichier UTF-8 avec BOM, une ligne par enregistrement |

## 7. Évolutions prévues

- **Courriel.** L'application ne dispose d'aucun envoi de courriel ; les mots de passe
  temporaires sont remis de la main à la main et la réinitialisation passe par l'administration.
  Une infrastructure d'envoi (serveur SMTP de l'école, paramètres dans `PARAMETRE_SYSTEME` ou
  dans `config/`) permettra : réinitialisation par lien à usage unique, envoi du mot de passe
  temporaire à la création d'un compte, notifications (convocation à une audition, décision
  rendue, justificatif validé ou rejeté, clôture du semestre). Les points d'accroche sont déjà en
  place : chaque événement à notifier est aussi une entrée du journal.
- Notifications par SMS, sur la même base, si l'école le souhaite.
- Génération de PDF côté serveur pour les relevés et rapports, si l'impression depuis le
  navigateur ne suffit plus.
- Réclamation formelle de l'étudiant sur une décision, avec son propre circuit.
