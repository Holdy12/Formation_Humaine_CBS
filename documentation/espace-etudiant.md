# Espace étudiant

Conception de la partie de l'application réservée au rôle `ETUDIANT`, y compris les droits
supplémentaires du délégué.

## 1. Périmètre

L'étudiant consulte sa situation et n'écrit que deux choses : un justificatif d'absence et une
réponse à un signalement qui le concerne. Il ne voit jamais le dossier d'un autre étudiant.

Le délégué est un étudiant dont la fiche porte `ETUDIANT.EST_DELEGUE = 1`. Il dispose en plus de
deux écrans : faire l'appel pour sa promotion et signaler un comportement.

Hors périmètre de cette version : notifications par courriel, réclamation formelle, accès aux
pièces jointes d'un signalement (réservées au chargé de discipline), tableaux de bord des autres
rôles.

## 2. Architecture

```
core/Auth.php                              session, garde par rôle, jeton CSRF
core/Fichier.php                           validation et stockage des fichiers envoyés (storage/ ou public/)
core/Format.php                            dates, points, accord en genre
core/Icone.php                             icônes SVG
core/Composant.php                         états vides, zone de dépôt de fichier
core/Erreur.php                            pages d'erreur
app/Models/Parametre.php                   paramètres système
app/Models/Etudiant.php                    fiche, promotion, club, délégué, photo, mot de passe
app/Models/MouvementPoint.php              registre des points, solde, plafonds
app/Models/Presence.php                    présences, absences, justificatifs, appel
app/Models/Signalement.php                 dossiers concernant un étudiant, réponse, émission
app/Controllers/EspaceEtudiantController.php
app/Views/partials/entete.php              <head>, barre latérale (menu selon le rôle), barre haute
app/Views/partials/pied.php
app/Views/etudiant/*.php                   une vue par page
public/assets/css/etudiant.css             compléments à dashboard.css
storage/justificatifs/                     fichiers hors de public/, ignorés par Git
storage/preuves/
```

Conventions : classes en PascalCase, méthodes et variables en camelCase, requêtes préparées PDO,
`htmlspecialchars()` sur toute donnée affichée. Les chemins vers `assets/` sont relatifs, jamais
absolus, pour fonctionner aussi bien à la racine d'un hôte que dans un sous-dossier.

### Routage

Les routes sont des `case` ajoutés au `switch` de `public/index.php`, préfixés `etudiant_`.
Chaque route appelle `Auth::exigerRole('ETUDIANT')` puis une méthode du contrôleur. Les routes
du délégué appellent en plus `Auth::exigerDelegue()`.

Un visiteur non connecté est renvoyé vers la connexion. Un utilisateur connecté qui n'est pas
étudiant est renvoyé vers son propre tableau de bord
(`ADMIN`) ou vers la connexion avec `erreur=acces_interdit` (autres rôles).

### Garde et session

`Auth` s'appuie sur `$_SESSION['user_id']` et `$_SESSION['user_role']` posés par
`AuthController`. La fiche `ETUDIANT` (promotion, niveau, filière, club, `EST_DELEGUE`) est
chargée une fois par requête et transmise aux vues.

Toutes les soumissions POST portent un jeton CSRF (`Auth::jeton()` / `Auth::verifierJeton()`).

### Gabarit

`entete.php` reçoit `$titre` (titre de page) et `$actif` (entrée de menu courante). Le menu est
un tableau par rôle ; le délégué obtient deux entrées supplémentaires. Le gabarit réutilise les
classes et le mode sombre de `dashboard.css`, complétés par `etudiant.css`.

Menu étudiant : Tableau de bord, Mes points, Mes présences, Mes signalements, Mes résultats,
Mon club, Mon profil, Paramètres. Menu délégué : + Faire l'appel, + Signaler.

Interface :

- adaptée au téléphone (menu en tiroir, tableaux repliés en fiches), à la tablette et à l'ordinateur ;
- icônes Lucide intégrées en SVG par `core/Icone.php`, sans dépendance externe ;
- états vides explicites (`Composant::etatVide`) et messages de retour après chaque action ;
- pages d'erreur (`core/Erreur.php`, `app/Views/erreur.php`) : page introuvable avec retour à
  l'accueil, accès refusé avec retour à la page concernée, incident technique avec nouvel essai ;
- sélection d'un étudiant par recherche (nom, prénom ou matricule) plutôt que par liste déroulante ;
- zone de dépôt de fichier commune (`Composant::champFichier`) avec glisser-déposer et liste des fichiers choisis ;
- libellés accordés en genre à partir de `PERSONNE.SEXE` (« Déléguée », « Étudiante ») ;
- fondu court entre les pages sur les navigateurs qui gèrent les View Transitions, désactivé si l'utilisateur limite les animations.

## 3. Règles métier

### Semestre courant

Semestre dont `DATE_DEBUT <= aujourd'hui <= DATE_FIN` ; à défaut, le plus récent. Les pages
Points, Présences et Résultats proposent un sélecteur de semestre.

### Solde

Calculé à chaque affichage à partir de `MOUVEMENT_POINTS` (source de vérité), sur la période du
semestre :

```
solde = CAPITAL_INITIAL_NOTE
      - somme des mouvements NEGATIF
      + somme par domaine de min(mouvements POSITIF du domaine, plafond du domaine)
borné à [NOTE_MINIMALE_POSSIBLE ; NOTE_MAXIMALE_POSSIBLE]
```

Un mouvement corrigé et son écriture inverse s'annulent : aucun des deux n'entre dans les sommes
ni dans le calcul des plafonds.

Plafonds lus dans `PARAMETRE_SYSTEME` : `PLAFOND_BONUS_ECOLOGIE`, `PLAFOND_BONUS_CITOYENNETE`,
`PLAFOND_BONUS_CLUB`, associés aux domaines `ECOLOGIE`, `CITOYENNETE`, `CLUB`. Le domaine
`DISCIPLINE` n'a pas de bonification.

### Résultats

La note provisoire est le solde calculé. La note finale et la mention proviennent de
`RESULTAT_SEMESTRIEL` et ne sont affichées que lorsque `STATUT_VALIDATION = 'CLOTURE'`.

### Justificatif d'absence

- Possible uniquement sur une présence de statut `ABSENT`.
- Date limite : `DATE_SEANCE + HEURE_DEBUT + DELAI_DEPOT_JUSTIFICATIF_HEURES`. Passé ce délai,
  le formulaire est remplacé par un message indiquant la date limite dépassée.
- Un seul justificatif par absence ; un nouveau dépôt n'est possible que si le précédent est
  `REJETEE`.
- Champs : motif (obligatoire), fichier (facultatif : pdf, jpg, jpeg, png, webp, 10 Mo max).
- Le dépôt crée une ligne `JUSTIFICATION_ABSENCE` en `EN_ATTENTE`. Le statut de la présence
  n'est modifié que par la validation côté administration.

### Réponse à un signalement

- L'étudiant voit les signalements dont il est `ID_PERSONNE_ETUDIANT`, sauf ceux en `BROUILLON`.
- Il peut répondre tant que le statut est `SOUMIS`, `EN_EXAMEN` ou `ETUDIANT_ENTENDU` et
  qu'aucune décision n'est enregistrée. La réponse est modifiable dans les mêmes conditions.
- La réponse remplit `REPONSE_ETUDIANT` et `DATE_REPONSE` ; elle ne change pas le statut.
- Les pièces jointes du dossier ne sont pas visibles par l'étudiant.

### Appel (délégué)

- Le délégué renseigne la séance de sa promotion (titre, date, heure de début et de fin, lieu)
  et coche le statut de chaque étudiant de la promotion : `PRESENT`, `RETARD`, `ABSENT`.
- L'enregistrement crée `SEANCE` (avec `ID_PROMO`), `APPEL` (avec `ID_PERSONNE` du délégué) et
  une ligne `PRESENCE` par étudiant.
- Aucun point n'est retiré à cette étape : les pénalités sont appliquées après validation par
  le chargé de discipline.

### Signalement (délégué)

- Étudiant concerné : uniquement un membre de sa promotion, jamais lui-même.
- Champs : critère (regroupé par domaine), titre, description, date et heure des faits, lieu,
  preuves (facultatif, plusieurs fichiers : pdf, jpg, jpeg, png, webp, mp4, 10 Mo chacun).
- Crée un `SIGNALEMENT` en `SOUMIS` avec `ID_PERSONNE_AUTEUR` = délégué, et une
  `PIECE_JUSTIFICATIVE` par fichier.
- La page liste aussi les signalements émis par le délégué avec leur statut.

### Photo de profil

L'étudiant peut envoyer ou retirer sa photo (jpg, png, webp, 10 Mo max). Elle est rangée sous
`public/assets/uploads/`, au même endroit et au même format de chemin que les photos gérées par
l'administration, de sorte que les deux espaces affichent la même image. L'ancienne photo est
supprimée du disque lors d'un remplacement ou d'un retrait.

### Fichiers

`Fichier::enregistrer()` vérifie l'extension, le type MIME réel (`finfo`) et la taille, renomme
le fichier aléatoirement et le range sous `storage/`. Les fichiers ne sont jamais servis
directement : la route `etudiant_fichier&id=` vérifie que le justificatif appartient à
l'étudiant connecté avant de l'envoyer.

## 4. Pages

| Route | Contenu | Actions |
|---|---|---|
| `etudiant_dashboard` | solde du semestre, barre par domaine, derniers mouvements, alertes (absence à justifier, signalement sans réponse, décision récente) | — |
| `etudiant_points` | registre complet : date, domaine, critère, motif, points, validé par ; totaux par domaine avec plafond | sélecteur de semestre |
| `etudiant_presences` | séances, statut, justificatif et son statut | déposer un justificatif |
| `etudiant_signalements` | liste des dossiers, détail d'un dossier (faits, réponse, décision) | répondre |
| `etudiant_resultats` | note provisoire ; note finale et mention si clôturé | sélecteur de semestre |
| `etudiant_club` | mon club, séances à venir, liste des autres clubs | — |
| `etudiant_profil` | identité, cursus, coordonnées en lecture seule ; photo de profil | changer ou retirer la photo |
| `etudiant_parametres` | téléphone et adresse modifiables | changer le mot de passe |
| `etudiant_releve` | relevé du semestre mis en page pour l'impression : identité, notes, domaines, registre, assiduité | sélecteur de semestre, imprimer |
| `etudiant_appel` | formulaire séance + liste de la promotion | enregistrer l'appel |
| `etudiant_signaler` | formulaire de signalement ; dossiers émis | soumettre |
| `etudiant_fichier` | envoi d'un justificatif à son propriétaire | — |

Chaque action POST redirige vers la page d'origine avec un message de succès ou d'erreur en
session (`success_message` / `error_message`, comme le reste de l'application).

## 5. Cas de test

À dérouler dans le navigateur avec `database/donnees_test.sql` chargé. Comptes : Awa KOUASSI
(`CBS2026-0001`, déléguée), Moussa DIALLO (`CBS2026-0002`), mot de passe `Test1234!`.

| # | Scénario | Résultat attendu |
|---|---|---|
| 1 | Connexion Moussa | redirection vers `etudiant_dashboard`, solde 19,75 / 20 |
| 2 | Connexion Awa | menu avec *Faire l'appel* et *Signaler* ; solde 20,00 (bonus +0,50, pénalité −0,25, plafonné à 20) |
| 3 | Admin ouvre `etudiant_dashboard` | renvoyé vers le tableau de bord administrateur |
| 4 | Moussa ouvre `etudiant_points` | 1 ligne : Absence −0,25, validé par Enock PANDA |
| 5 | Moussa, présences | séance du 08/09 en `ABSENT` avec justificatif `EN_ATTENTE` ; pas de nouveau dépôt possible |
| 6 | Moussa, absence du 01/09 (sans justificatif) | message de délai dépassé, pas de formulaire |
| 7 | Moussa, absence du jour (sans justificatif), dépôt motif + PDF | ligne `JUSTIFICATION_ABSENCE` créée, fichier sous `storage/justificatifs/`, statut `EN_ATTENTE` |
| 8 | Moussa télécharge son justificatif | fichier reçu ; Awa sur la même URL → refus |
| 9 | Moussa, signalement « Perturbation en cours » | visible, formulaire de réponse ; après envoi, réponse affichée, statut inchangé `SOUMIS` |
| 10 | Awa, signalement validé | réponse impossible, décision affichée |
| 11 | Moussa, résultats | provisoire 19,75 ; pas de note finale |
| 12 | Awa, paramètres, mot de passe actuel faux | erreur, mot de passe inchangé |
| 13 | Awa, paramètres, nouveau mot de passe valide | reconnexion possible avec le nouveau |
| 14 | Awa, appel : séance + Moussa `ABSENT`, elle-même `PRESENT` | `SEANCE`, `APPEL`, 2 `PRESENCE` créées ; aucun `MOUVEMENT_POINTS` |
| 15 | Awa, signaler Moussa avec une photo | `SIGNALEMENT` en `SOUMIS`, `PIECE_JUSTIFICATIVE` créée ; Moussa le voit dans ses signalements |
| 16 | Awa, signaler elle-même | refusé |
| 17 | Moussa ouvre `etudiant_appel` | refusé (non délégué) |
| 18 | Soumission d'un formulaire sans jeton CSRF | refusée |
| 19 | Fichier `.exe` renommé en `.pdf` | refusé (type MIME) |
| 20 | Mode sombre | conservé d'une page à l'autre |
| 21 | Awa envoie puis retire une photo de profil | `PERSONNE.PHOTO` renseigné puis remis à `NULL`, fichier supprimé du disque |
| 22 | Awa ouvre le relevé imprimable | en-tête, notes, domaines, registre et assiduité du semestre ; aperçu d'impression sans barre latérale |

## 6. À prévoir côté administration

- Case à cocher *Délégué* (`EST_DELEGUE`) dans les formulaires d'ajout et de modification
  d'un étudiant.
- Validation des justificatifs (`STATUT_VALIDATION`, `ID_VALIDATEUR`) et mise à jour du statut
  de la présence en `ABSENT_JUSTIFIE`.
- Consultation des réponses des étudiants dans l'instruction des signalements.
- Réutilisation de `core/Fichier.php` pour l'envoi des photos.
