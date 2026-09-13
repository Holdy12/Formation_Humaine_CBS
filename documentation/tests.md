# Tests

L'application n'embarque pas de cadre de test. La recette est un script qui rejoue, contre une
instance locale, les cas décrits dans `documentation/espace-etudiant.md` (section 5), plus
quelques contrôles techniques : syntaxe PHP de tous les fichiers, absence d'avertissement PHP
dans les pages, protection CSRF, contrôle des fichiers envoyés.

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
  navigateur, en particulier le tiroir de navigation sous 900 px et les tableaux repliés sous 700 px.
- L'impression du relevé : ouvrir « Relevé imprimable » puis l'aperçu avant impression du navigateur.
- Les scénarios qui dépendent de l'administration : validation d'un justificatif, décision sur un
  signalement, clôture d'un semestre.

## Ajouter un cas

Chaque cas est une ligne `verif "libellé" "valeur obtenue" "valeur attendue"`. Les fonctions
`connexion`, `page`, `jeton` et `sql` évitent de répéter les appels `curl` et `mysql`. Les
comptes disponibles sont ceux de `database/donnees_test.sql` (mot de passe `Test1234!`).
