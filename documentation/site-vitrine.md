# Site vitrine et reconnexion automatique

Le site vitrine est la face publique de la Formation Humaine : il présente l'école en quelques
faits, explique la Formation Humaine et mène à l'espace étudiant et personnel. Il ne remplace pas
le site officiel de l'école (cbs-tchad.org), vers lequel il renvoie. Il est servi par la même
application que le portail, à la même adresse : l'en-tête sait si le visiteur est connecté.

## 1. Pages et adresses

| Adresse | Page | Vue |
|---|---|---|
| `/` | Accueil : accroche, repères, Formation Humaine, l'école, formations, vie étudiante, contact | `app/Views/site/accueil.php` |
| `/vie-etudiante` (alias `/formation-humaine`) | Vie étudiante et Formation Humaine : pourquoi, les quatre domaines, comment ça marche, accès à l'espace | `vie_etudiante.php` |
| `/formations` | Licences, masters et MBA, formation continue | `formations.php` |
| `/admission` | Trois étapes, conditions par niveau, candidater | `admission.php` |
| `/contact` | Coordonnées, WhatsApp, Facebook, plan | `contact.php` |
| `/connexion` | La page de connexion de l'application (`index.php?action=login`) | — |

`public/index.php` lit le chemin demandé quand aucun paramètre `action` n'est présent, à la racine
d'un hôte comme dans un sous-dossier, et le fait correspondre à une action via
`SiteController::CHEMINS`. Une barre finale est redirigée (301) vers l'adresse sans barre, pour que
les liens relatifs (`assets/…`, autres pages) restent justes. Une adresse inconnue donne la page
404 de l'application, avec « Retour au site » pour un visiteur.

Le serveur intégré de PHP renvoie de lui-même les chemins inconnus vers `index.php`. Sous Apache,
`public/.htaccess` fait la même chose (`mod_rewrite`, `AllowOverride All`) ; sous Nginx,
`try_files $uri /index.php?$args;`. Sans réécriture, `index.php?action=formations` fonctionne
toujours.

Les pages publiques n'ouvrent pas de session pour un simple visiteur : aucun cookie n'est déposé
tant que la personne ne se connecte pas. Seule exception, la page Contact, dont le formulaire
porte un jeton CSRF. Le lien « Se connecter » de l'en-tête devient « Mon espace » (avec le
prénom) dès qu'une session existe, et mène au tableau de bord du rôle.

D'une page à l'autre, le navigateur enchaîne les pages sans coupure (`@view-transition`, CSS
seul) : l'en-tête reste en place, le contenu glisse et se fond. Les navigateurs qui ne le
gèrent pas chargent la page normalement ; la page de connexion y participe aussi.

## 2. Modifier un texte, un numéro, une photo

Tout le contenu est dans `app/Views/site/contenu.php` : textes de chaque page, repères, listes de
formations, étapes d'admission, coordonnées, photos et leurs légendes. Les vues ne font que la
mise en page. Un texte se change dans ce fichier, sans toucher aux vues ni à la feuille de style.

Les valeurs du barème affichées sur la page Vie étudiante (trois exemples par domaine et les
plafonds) sont des exemples fixes, copiés du barème initial : si la direction modifie le barème
dans l'application (**Barème**), mettre `contenu.php` à jour pour que le site ne contredise pas
le portail. Le site précise qu'il s'agit d'un barème indicatif.

Le titre de l'onglet et la description lue par les moteurs de recherche sont dans
`contenu.php`, clé `pages`. Chaque page a une adresse canonique et des balises Open Graph
(image : le bâtiment du campus) construites à partir de `BASE_URL`.

## 3. Mise en page, police et motif

La feuille `public/assets/css/site.css` est écrite à la main, sans cadre CSS ni script tiers.
Les titres sont composés en Oswald, police condensée proche du lettrage du logo et du portail
de l'école, servie depuis `public/assets/fonts/oswald.woff2` (21 Ko, licence SIL OFL jointe dans
le même dossier) ; le texte courant reste en police système. Le motif de blocs ajourés reprend
les claustras de la façade : il sert de fond à la bande d'accueil et au pied de page, et figure
les vingt points du semestre (`app/Views/site/capital.php`, `Composant::blocs()`). Les sections
alternent fond blanc, bande sable et bande sombre (`bande-sable`, `bande-encre`) sans changer
leur structure. Le seul mouvement à l'arrivée est celui de la photo de l'accueil, qui se
découvre depuis le bord de l'écran ; tous les mouvements sont coupés quand le système demande
moins d'animations.

La police est annoncée dès l'en-tête (`<link rel="preload">`) pour arriver avec la feuille de
style : les titres s'affichent directement en Oswald, sans sauter d'une police à l'autre. La
feuille et le script portent leur date de modification en paramètre (`site.css?v=…`) ; le
`.htaccess` peut donc demander un cache d'un an sans jamais servir une version périmée.

**Boutons et liens.** Un seul modèle de bouton (`.bouton`, variantes `.bouton-contour` et
`.bouton-petit`) et un seul lien secondaire (`.lien-second`), tous précédés du bloc de claustra.
Actif, le bloc pivote en losange, le motif des grilles du portail ; sur un bouton, la couleur de
remplissage balaie le bouton en même temps. Chaque page n'a qu'un bouton orange.

**Souris et toucher.** Les effets de survol ne s'appliquent qu'aux appareils avec souris ou pavé
tactile (`@media (hover: hover) and (pointer: fine)`) : sur un téléphone ou une tablette, un
survol resterait figé après le toucher. Le toucher a son propre retour, le temps de l'appui
(`:active`) : le bouton se remplit et s'enfonce, le lien trace son soulignement, la carte et la
photo s'enfoncent légèrement. Les lignes du tableau de cours et les numéros de la frise ne sont
pas des liens : ils ne réagissent qu'à la souris, en repère de lecture, sans changer le curseur.
Les cibles de toucher font au moins 44 px (zone agrandie sans changer l'apparence).

**Menu sur petit écran.** Sous 880 px, le menu s'ouvre en plein écran sous l'en-tête, fond encre
à motif, liens en grand qui montent l'un après l'autre, page courante en orange ; le bouton
« Se connecter » et les contacts rapides sont en bas. La page derrière ne défile plus ; Échap ou
le bouton (devenu une croix) le referme.

**Cartes des domaines (accueil).** Chaque domaine est une carte à deux faces : au recto ce
qu'est le domaine et son plafond de bonus, au verso trois exemples du barème. Elle se retourne
en 3D au survol avec une souris, par le bouton « Voir le barème » au clavier, et d'un toucher
sur téléphone et tablette. Les deux faces restent lues par les lecteurs d'écran.

Chaque page a son propre dispositif, tiré de son contenu : l'accueil, les vingt points en blocs ;
la vie étudiante, le barème en grand livre sur fond sombre ; les formations, un tableau
d'affichage des programmes avec la durée en blocs ; l'admission, une frise des trois étapes et le
calendrier des douze mois, juillet à septembre en orange ; le contact, l'adresse et les numéros
composés comme une carte de visite.

## 4. Photos

Les photos sont traitées comme des tirages qu'on prend en main. Avec une souris, elles attendent
en tons sable et encre ; sous le pointeur elles s'inclinent vers lui en 3D, reprennent leurs
couleurs, accrochent un reflet qui suit le pointeur et se soulèvent de la page (`site.js`). Les
photos pleine largeur (accueil, bandeau de la vie étudiante) ne s'inclinent pas, leurs bords
sortant de l'écran. Sur téléphone et tablette, elles sont toujours en couleur.

Un clic, un toucher ou Entrée (chaque photo est un bouton) ouvre la photo en grand dans une
boîte de dialogue native (`<dialog>`, sans bibliothèque) : Échap, un clic à côté ou, au toucher,
un toucher sur la photo la referme. Sur l'accueil, les trois photos de la vie étudiante
s'élargissent sous le pointeur.

Les photos sont dans `public/assets/images/site/`, chacune en quatre fichiers : `nom.jpg`,
`nom.webp`, `nom-720.jpg`, `nom-720.webp`. Le navigateur choisit WebP quand il le peut et la
largeur 720 px sur téléphone (`Composant::photo()`, balise `<picture>` avec `srcset`). Toutes
sont chargées à la demande sauf celle de l'accueil, affichée en premier.

Pour ajouter une photo : produire les quatre fichiers (Pillow, ImageMagick ou un service en
ligne ; JPEG qualité 78, WebP qualité 74, largeur au plus 1280 px), puis la déclarer dans
`contenu.php`, clé `photos`, avec sa largeur, sa hauteur, un texte alternatif et une légende.

### Photos publiées et consentement

Sept photos sont publiées : le bâtiment du campus, une promotion devant l'entrée, un groupe
devant le portail, trois scènes de la Semaine de l'Étudiant et de la vie sur le campus, une salle
de cours. Elles ne montrent que des groupes. Cinq portraits individuels (remise de diplôme,
portraits en tenue) ont été volontairement écartés : ils ne doivent être publiés qu'avec l'accord
écrit des personnes. Les photos portent la signature du photographe (« D1S ») : le créditer ou
obtenir son accord avant la mise en ligne.

## 5. Reconnexion automatique (« Rester connecté sur cet appareil »)

La session PHP est courte : elle disparaît à la fermeture du navigateur. La case « Rester
connecté sur cet appareil » de la page de connexion ajoute un second cookie, `reconnexion`, valable
dix jours, qui permet de rouvrir une session sans ressaisir le mot de passe. C'est l'équivalent,
pour une application rendue par le serveur, d'un jeton de rafraîchissement.

Fonctionnement (`app/Models/JetonConnexion.php`, `core/Auth.php`) :

- À la connexion avec la case cochée, un jeton est créé dans `JETON_CONNEXION` : un *sélecteur*
  aléatoire (en clair, pour retrouver la ligne) et un *validateur* aléatoire dont seul le haché
  SHA-256 est stocké. Le cookie porte `sélecteur.validateur`, avec `HttpOnly`, `SameSite=Lax`,
  `Secure` dès que `BASE_URL` est en `https://`, et le chemin de l'application.
- À chaque requête sans session, `Auth::reprendre()` vérifie le cookie : sélecteur connu,
  validateur conforme (`hash_equals`), jeton non expiré, compte toujours actif. La session est
  alors rouverte comme après une connexion, et le validateur est **remplacé** (rotation) : la
  durée de dix jours repart de la dernière utilisation, et un cookie copié ne sert qu'une fois.
- Un sélecteur connu présenté avec un mauvais validateur est le signe d'un cookie volé puis
  rejoué après son renouvellement : tous les jetons de la personne sont supprimés et
  l'événement est journalisé (action « Connexion », statut `ECHEC`).
- Deux requêtes parties en même temps avec le même cookie (deux onglets rouverts ensemble, par
  exemple) ne sont pas prises pour un vol : le validateur remplacé reste accepté pendant
  30 secondes après le renouvellement (`VALIDATEUR_PRECEDENT`), sans nouveau renouvellement. Au-delà,
  il est traité comme un cookie volé.
- Le jeton est supprimé à la déconnexion (bouton « Se déconnecter »), à tout changement de mot
  de passe (première connexion, changement volontaire, réinitialisation par l'administration ou
  par le lien « mot de passe oublié »),
  à la désactivation du compte, et avec le compte lui-même. Les jetons expirés sont purgés à
  chaque création.

La reconnexion est journalisée (« Reprise de session sur un appareil mémorisé »). Sur un
ordinateur partagé, ne pas cocher la case : le libellé le rappelle.

## 6. Cas de test

Rejoués par `tests/recette.sh` (voir `documentation/tests.md`).

| Cas | Vérification | Attendu |
|---|---|---|
| V1 | Accueil public | 200, devise affichée, aucun `Set-Cookie` |
| V2 | Chaque page par son chemin, alias `formation-humaine` | 200, titre et contenu propres à la page |
| V3 | Adresse inconnue ; barre finale | 404 ; 301 vers l'adresse sans barre |
| V4 | En-tête | « Se connecter » pour un visiteur ; « Mon espace » vers le tableau de bord pour Moussa |
| V5 | Pages du site | aucun avertissement PHP |
| V6 | Formulaire de contact | jeton, champ piège, quatre objets |
| V7 | Envoi avec champs manquants ou invalides | une erreur sous chaque champ concerné |
| V8 | Envoi sans jeton | refusé, message « Le formulaire a expiré », texte conservé |
| V9 | Envoi valide, courriel pas encore branché | avis « pas encore en service », texte conservé |
| V10 | Champ piège rempli | message écarté, aucune erreur affichée |
| R1 | Connexion avec la case cochée | cookie `reconnexion` déposé, un jeton en base ; aucun cookie sans la case |
| R2 | Requête avec le seul cookie de reconnexion | tableau de bord servi (200), cookie renouvelé, date d'utilisation renseignée |
| R3 | Déconnexion | jeton supprimé, cookie effacé |
| R4 | Ancien cookie rejoué après renouvellement | redirection vers la connexion, tous les jetons révoqués |
| R5 | Jeton expiré | redirection vers la connexion, jeton purgé |
| R6 | Changement de mot de passe | jetons de la personne supprimés |
| R7 | Même cookie présenté deux fois de suite | les deux requêtes servies, l'appareil reste mémorisé |
| R8 | Mot de passe réinitialisé par le lien « mot de passe oublié » | appareils mémorisés déconnectés, nouveau mot de passe actif |
| R9 | Lien de réinitialisation, requête avec un autre en-tête `Host` | lien construit sur `BASE_URL` |

## 7. À valider par l'école avant la mise en ligne

La liste est aussi dans `contenu.php`, clé `a_valider`.

- Nombre d'étudiants ou de diplômés, si l'école souhaite l'afficher dans les repères.
- Liste des masters (source : cefod-tchad.org uniquement).
- Dates du concours (juillet – septembre), pièces du dossier, frais de dossier de 5 000 FCFA.
- Numéro WhatsApp (65 77 23 10), ligne fixe (22 51 54 32), horaires d'ouverture.
- Adresse exacte : le CBS est-il dans l'enceinte du CEFOD ?
- Liste des clubs.
- Valeurs du barème affichées (exemples), à mettre à jour si le barème change dans l'application.
- Consentement des personnes reconnaissables sur les photos ; crédit du photographe.

## 8. Évolutions prévues

- **Envoi du formulaire de contact** : le formulaire est en place (nom, e-mail, téléphone
  facultatif, objet, message ; jeton CSRF, champ piège, erreurs par champ, texte conservé).
  L'envoi passe par `SiteController::transmettre()`, qui appelle `Courriel::envoyer()` dès que
  `core/Courriel.php` existe et que `COURRIEL_ACTIF` vaut 1 (`documentation/courriel-resend.md`,
  sections 2 et 3) : rien d'autre à modifier. D'ici là, un message valide n'est pas envoyé et le
  visiteur est invité à écrire par e-mail ou WhatsApp, son texte conservé. Les messages vont à
  `contenu.php`, clé `contact.destinataire`. À ajouter en même temps que l'envoi : une limite du
  nombre de messages par adresse, sur le modèle de `TENTATIVE_CONNEXION`, et l'adresse du
  visiteur en « répondre à » (paramètre à ajouter à `Courriel::envoyer()`).
- **Actualités** : le contenu est fixe dans `contenu.php`. Si l'école veut publier elle-même des
  nouvelles, prévoir une table `ACTUALITE` et un module dans l'espace personnel.
