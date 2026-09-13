<?php
// app/Controllers/Admin/EtudiantController.php — gestion des étudiants par le personnel.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Etudiant.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../Models/MouvementPoint.php';
require_once __DIR__ . '/../../Models/Presence.php';
require_once __DIR__ . '/../../Models/Signalement.php';
require_once __DIR__ . '/../../../core/Csv.php';

class EtudiantController extends PersonnelController {
    private const CHAMPS = ['nom', 'prenom', 'email', 'telephone', 'sexe', 'date_naissance', 'adresse'];

    private function filtres(): array {
        return ['q' => $this->param('q'), 'promo' => $this->param('promo'), 'niveau' => $this->param('niveau'), 'filiere' => $this->param('filiere'), 'statut' => $this->param('statut')];
    }

    public function liste(): void {
        $filtres = $this->filtres();
        $pagination = $this->pagination(Etudiant::compter($filtres));
        $this->vue('etudiants/liste', 'Étudiants', 'etudiants', [
            'etudiants'  => Etudiant::rechercher($filtres, $pagination['debut'], $pagination['limite']),
            'filtres'    => $filtres,
            'pagination' => $pagination,
            'promotions' => Structure::promotions(),
            'niveaux'    => Structure::lister('niveau'),
            'filieres'   => Structure::lister('filiere'),
        ]);
    }

    public function exporter(): void {
        $lignes = [];
        foreach (Etudiant::tous($this->filtres()) as $e) {
            $lignes[] = [$e['MATRICULE'], $e['NOM'], $e['PRENOM'], $e['EMAIL'], $e['TELEPHONE'], $e['SEXE'], $e['CODE_PROMO'], $e['LIBELLE_NIVEAU'], $e['NOM_FILIERE'], $e['NOM_CLUB'] ?? '', $e['EST_DELEGUE'] ? 'oui' : 'non', $e['STATUT_COMPTE']];
        }
        Journal::ecrire('Export', 'Liste des étudiants (' . count($lignes) . ' lignes)');
        Csv::envoyer('etudiants', ['Matricule', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Sexe', 'Promotion', 'Niveau', 'Filière', 'Club', 'Délégué', 'Statut'], $lignes);
    }

    public function fiche(): void {
        $etudiant = $this->charger();
        $id = (int)$etudiant['ID_PERSONNE'];
        $semestre = null;
        $idSemestre = (int)$this->param('semestre', '0');
        $semestre = $idSemestre > 0 ? Etudiant::semestre($idSemestre) : Etudiant::semestreCourant();
        $this->vue('etudiants/fiche', 'Fiche étudiant', 'etudiants', [
            'etudiant'     => $etudiant,
            'semestre'     => $semestre,
            'semestres'    => Etudiant::semestres(),
            'solde'        => $semestre ? MouvementPoint::solde($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : null,
            'mouvements'   => $semestre ? MouvementPoint::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : [],
            'presences'    => $semestre ? Presence::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : [],
            'dossiers'     => Signalement::pourEtudiant($id),
            'resultat'     => $semestre ? Etudiant::resultat($id, (int)$semestre['ID_SEMESTRE']) : null,
            'aHistorique'  => Personne::aUnHistorique($id),
            'motDePasse'   => $this->recupererMotDePasse($id),
            'jeton'        => Auth::jeton(),
        ]);
    }

    public function nouveau(): void {
        Auth::exigerPermission('etudiants.gerer');
        $saisie = ['sexe' => 'M', 'delegue' => 0];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            $saisie = $this->lireFormulaire();
            $erreur = $this->validerFormulaire($saisie, null);
            if ($erreur === null) {
                try {
                    if (Fichier::estPresent($_FILES['photo'] ?? [])) {
                        $saisie['photo'] = Fichier::enregistrerPublic($_FILES['photo'], 'assets/uploads', ['jpg', 'jpeg', 'png', 'webp']);
                    }
                    $resultat = Personne::creerEtudiant($saisie);
                    Journal::ecrire('Étudiant', 'Création de ' . $saisie['nom'] . ' ' . $saisie['prenom'] . ' (' . $resultat['matricule'] . ')');
                    $_SESSION['mot_de_passe_temporaire'][$resultat['id']] = $resultat['motDePasse'];
                    $this->retour('admin_etudiant', "Étudiant créé. Le mot de passe temporaire est affiché ci-dessous, une seule fois.", true, ['id' => $resultat['id']]);
                } catch (Exception $e) {
                    $erreur = $e->getMessage();
                }
            }
            $_SESSION['error_message'] = $erreur;
        }
        $this->vue('etudiants/formulaire', 'Nouvel étudiant', 'etudiants', $this->donneesFormulaire($saisie, null));
    }

    public function modifier(): void {
        Auth::exigerPermission('etudiants.gerer');
        $etudiant = $this->charger();
        $id = (int)$etudiant['ID_PERSONNE'];
        $saisie = [
            'nom' => $etudiant['NOM'], 'prenom' => $etudiant['PRENOM'], 'email' => $etudiant['EMAIL'], 'telephone' => $etudiant['TELEPHONE'],
            'sexe' => $etudiant['SEXE'], 'date_naissance' => $etudiant['DATE_NAISSANCE'], 'adresse' => $etudiant['ADRESSE'] ?? '',
            'id_promo' => $etudiant['ID_PROMO'], 'id_club' => $etudiant['ID_CLUB'], 'delegue' => $etudiant['EST_DELEGUE'],
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            $saisie = $this->lireFormulaire();
            $erreur = $this->validerFormulaire($saisie, $id);
            if ($erreur === null) {
                try {
                    if (Fichier::estPresent($_FILES['photo'] ?? [])) {
                        $chemin = Fichier::enregistrerPublic($_FILES['photo'], 'assets/uploads', ['jpg', 'jpeg', 'png', 'webp']);
                        Fichier::supprimerPublic($etudiant['PHOTO']);
                        Personne::modifierPhoto($id, $chemin);
                    }
                    Personne::modifier($id, $saisie);
                    Etudiant::modifierInscription($id, (int)$saisie['id_promo'], $saisie['id_club'] ? (int)$saisie['id_club'] : null, !empty($saisie['delegue']));
                    Journal::ecrire('Étudiant', 'Modification de ' . $saisie['nom'] . ' ' . $saisie['prenom'] . ' (' . $etudiant['MATRICULE'] . ')');
                    $this->retour('admin_etudiant', "Fiche mise à jour.", true, ['id' => $id]);
                } catch (Exception $e) {
                    $erreur = $e->getMessage();
                }
            }
            $_SESSION['error_message'] = $erreur;
        }
        $this->vue('etudiants/formulaire', 'Modifier un étudiant', 'etudiants', $this->donneesFormulaire($saisie, $etudiant));
    }

    public function statut(): void {
        Auth::exigerPermission('etudiants.gerer');
        $this->exigerPost();
        $etudiant = $this->charger((int)($_POST['id'] ?? 0));
        $nouveau = $etudiant['STATUT_COMPTE'] === 'ACTIF' ? 'INACTIF' : 'ACTIF';
        Personne::changerStatut((int)$etudiant['ID_PERSONNE'], $nouveau);
        Journal::ecrire('Étudiant', ($nouveau === 'ACTIF' ? 'Réactivation' : 'Désactivation') . ' de ' . $etudiant['MATRICULE']);
        $this->retour('admin_etudiant', $nouveau === 'ACTIF' ? "Compte réactivé." : "Compte désactivé : l'étudiant ne peut plus se connecter et n'apparaît plus dans les appels.", true, ['id' => $etudiant['ID_PERSONNE']]);
    }

    public function reinitialiser(): void {
        Auth::exigerPermission('etudiants.gerer');
        $this->exigerPost();
        $etudiant = $this->charger((int)($_POST['id'] ?? 0));
        $motDePasse = Personne::reinitialiser((int)$etudiant['ID_PERSONNE']);
        Journal::ecrire('Mot de passe', 'Réinitialisation pour ' . $etudiant['MATRICULE']);
        $_SESSION['mot_de_passe_temporaire'][(int)$etudiant['ID_PERSONNE']] = $motDePasse;
        $this->retour('admin_etudiant', "Mot de passe réinitialisé. Le mot de passe temporaire est affiché ci-dessous, une seule fois.", true, ['id' => $etudiant['ID_PERSONNE']]);
    }

    public function supprimer(): void {
        Auth::exigerPermission('etudiants.gerer');
        $this->exigerPost();
        $etudiant = $this->charger((int)($_POST['id'] ?? 0));
        $id = (int)$etudiant['ID_PERSONNE'];
        if (Personne::aUnHistorique($id)) {
            $this->retour('admin_etudiant', "Cet étudiant a un historique (présences, points ou signalements) : désactivez le compte plutôt que de le supprimer.", false, ['id' => $id]);
        }
        Fichier::supprimerPublic($etudiant['PHOTO']);
        Personne::supprimer($id);
        Journal::ecrire('Étudiant', 'Suppression de ' . $etudiant['MATRICULE'] . ' (sans historique)');
        $this->retour('admin_etudiants', "Étudiant supprimé.");
    }

    public function importer(): void {
        Auth::exigerPermission('etudiants.gerer');
        if ($this->param('modele') === '1') {
            Csv::envoyer('modele-import-etudiants', ['nom', 'prenom', 'email', 'telephone', 'sexe', 'date_naissance', 'adresse', 'code_promo'],
                [['DUPONT', 'Jean', 'jean.dupont@exemple.com', '+235 60 00 00 00', 'M', '2005-01-31', 'Quartier, ville', 'L1-GI-2026']]);
        }
        $apercu = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            if (!empty($_POST['confirmer'])) {
                $lignes = $_SESSION['import_apercu'] ?? null;
                if (!$lignes) {
                    $this->retour('admin_etudiants_import', "L'aperçu a expiré, recommencez l'import.", false);
                }
                try {
                    $crees = Etudiant::creerEnMasse($lignes);
                    unset($_SESSION['import_apercu']);
                    $_SESSION['import_resultat'] = $crees;
                    Journal::ecrire('Import', count($crees) . ' étudiant(s) créé(s) par import CSV');
                    Auth::rediriger('admin_etudiants_import_resultat');
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Import annulé : " . $e->getMessage();
                }
            } elseif (Fichier::estPresent($_FILES['fichier'] ?? []) && ($_FILES['fichier']['error'] ?? 1) === UPLOAD_ERR_OK) {
                try {
                    $apercu = Etudiant::analyserCsv($_FILES['fichier']['tmp_name']);
                    $_SESSION['import_apercu'] = empty($apercu['erreurs']) ? $apercu['lignes'] : null;
                } catch (Exception $e) {
                    $_SESSION['error_message'] = $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = "Choisissez un fichier CSV.";
            }
        }
        $this->vue('etudiants/import', 'Importer des étudiants', 'etudiants', ['apercu' => $apercu, 'jeton' => Auth::jeton()]);
    }

    public function importResultat(): void {
        Auth::exigerPermission('etudiants.gerer');
        $crees = $_SESSION['import_resultat'] ?? null;
        if (!$crees) {
            $this->retour('admin_etudiants', "Aucun résultat d'import à afficher.", false);
        }
        if ($this->param('csv') === '1') {
            unset($_SESSION['import_resultat']);
            Csv::envoyer('comptes-crees', ['Nom', 'Prénom', 'Email', 'Matricule', 'Mot de passe temporaire'],
                array_map(fn($c) => [$c['nom'], $c['prenom'], $c['email'], $c['matricule'], $c['motDePasse']], $crees));
        }
        $this->vue('etudiants/import_resultat', 'Comptes créés', 'etudiants', ['crees' => $crees]);
    }

    private function charger(?int $id = null): array {
        $id = $id ?? (int)$this->param('id', '0');
        $etudiant = $id > 0 ? Etudiant::fiche($id) : null;
        if (!$etudiant) {
            Erreur::afficher(404, "Étudiant introuvable", "Aucun étudiant ne correspond à cet identifiant.", "Retour à la liste", 'index.php?action=admin_etudiants');
        }
        return $etudiant;
    }

    private function recupererMotDePasse(int $id): ?string {
        $mdp = $_SESSION['mot_de_passe_temporaire'][$id] ?? null;
        unset($_SESSION['mot_de_passe_temporaire'][$id]);
        return $mdp;
    }

    private function lireFormulaire(): array {
        $s = [];
        foreach (self::CHAMPS as $c) {
            $s[$c] = trim($_POST[$c] ?? '');
        }
        $s['sexe'] = strtoupper($s['sexe']);
        $s['id_promo'] = (int)($_POST['id_promo'] ?? 0);
        $s['id_club'] = (int)($_POST['id_club'] ?? 0) ?: null;
        $s['delegue'] = !empty($_POST['delegue']) ? 1 : 0;
        return $s;
    }

    private function validerFormulaire(array $s, ?int $id): ?string {
        if ($s['nom'] === '' || $s['prenom'] === '' || $s['email'] === '' || $s['telephone'] === '' || $s['date_naissance'] === '') {
            return "Nom, prénom, email, téléphone et date de naissance sont obligatoires.";
        }
        if (!filter_var($s['email'], FILTER_VALIDATE_EMAIL)) {
            return "L'adresse email est invalide.";
        }
        if (Personne::emailExiste($s['email'], $id)) {
            return "Cette adresse email est déjà utilisée par un autre compte.";
        }
        if (!in_array($s['sexe'], ['M', 'F'], true)) {
            return "Le sexe doit être M ou F.";
        }
        if (!DateTime::createFromFormat('Y-m-d', $s['date_naissance'])) {
            return "La date de naissance est invalide.";
        }
        if (!Structure::promotion($s['id_promo'])) {
            return "Choisissez une promotion.";
        }
        return null;
    }

    private function donneesFormulaire(array $saisie, ?array $etudiant): array {
        return [
            'saisie'     => $saisie,
            'etudiant'   => $etudiant,
            'promotions' => Structure::promotions(),
            'clubs'      => Etudiant::clubs(),
            'jeton'      => Auth::jeton(),
        ];
    }
}
