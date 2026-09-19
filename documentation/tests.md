# Tests

L'application n'embarque pas de cadre de test. La recette est un script qui rejoue, contre une
instance locale, les cas décrits dans `documentation/espace-etudiant.md` (section 5) et dans
`documentation/espace-personnel.md` (section 6, cas `P1` à `P24`) et dans
`documentation/site-vitrine.md` (section 6, cas `V1` à `V10` pour le site et son formulaire de
contact, `R1` à `R9` pour la reconnexion automatique et la réinitialisation du mot de passe), plus quelques contrôles techniques : syntaxe PHP de tous les fichiers,
absence d'avertissement PHP dans les pages, protection CSRF, contrôle des fichiers envoyés,
respect des permissions par rôle.

## Prérequis

- L'application servie en local, par exemple `php -S localhost:8000 -t public`.
- MySQL ou MariaDB accessible avec un compte autorisé à recréer `formation_humaine_db`.
- `bash`, `curl` et `base64` (présents sur Linux, macOS et Git Bash sous Windows).

## Lancer la recette

```bash
bash tests/recette.sh
```

Le script recharge la base (`schema.sql`, `seed.sql`, `donnees_test.sql`), vide `storage/`,
puis déroule les cas. Chaque ligne affiche `ok` ou `KO` avec la valeur obtenue et la valeur
attendue ; le code de retour est 0 si tout passe.

Variables d'environnement acceptées :

| Variable | Rôle | Défaut |
|---|---|---|
| `URL` | adresse de `index.php` | `http://localhost:8000/index.php` |
| `PHP` | exécutable PHP | `php`, ou celui de XAMPP s'il est présent |
| `MYSQL` | exécutable MySQL | `mysql`, ou celui de XAMPP s'il est présent |
| `DB_USER`, `DB_PASS` | compte MySQL utilisé pour recharger la base | `root`, sans mot de passe |
| `SANS_RESET=1` | ne pas recharger la base avant les tests | recharge |

Exemple sur un poste Linux avec un mot de passe MySQL :

```bash
DB_PASS=secret URL=http://localhost/Formation_Humaine_CBS/public/index.php bash tests/recette.sh
```

## Ce que la recette ne couvre pas

- Le rendu visuel (mise en page, mode sombre, affichage sur téléphone) : à vérifier dans un
  navigateur, en particulier le tiroir de navigation sous 900 px et les tableaux repliés sous 700 px,
  et, pour le site vitrine, le menu sous 880 px et le chargement des photos en WebP.
- L'impression du relevé : ouvrir « Relevé imprimable » puis l'aperçu avant impression du navigateur.
- L'import CSV d'étudiants dans toutes ses variantes : le rapport d'aperçu se vérifie à la main
  avec le modèle téléchargé depuis la page d'import.
- La réinitialisation autonome du mot de passe, qui dépend de l'envoi de courriels
  (`documentation/courriel-resend.md`).

## Ajouter un cas

Chaque cas est une ligne `verif "libellé" "valeur obtenue" "valeur attendue"`. Les fonctions
`connexion`, `connexion_rester` (avec la case « rester connecté »), `page`, `jeton` et `sql`
évitent de répéter les appels `curl` et `mysql`. Les pages du site se demandent par leur chemin
(`$SITE/formations`, où `SITE` est `URL` sans `/index.php`). Les
comptes disponibles sont ceux de `database/donnees_test.sql` (mot de passe `Test1234!`) et
l'administrateur de `seed.sql` (`admin@formation.local` / `Admin123!`). Les cas du personnel
s'exécutent après ceux de l'espace étudiant et réutilisent les données que ceux-ci ont créées.
