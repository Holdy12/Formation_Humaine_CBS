<?php
// app/Controllers/EspaceEtudiantController.php
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Fichier.php';
require_once __DIR__ . '/../../core/Icone.php';
require_once __DIR__ . '/../../core/Composant.php';
require_once __DIR__ . '/../../core/Erreur.php';
require_once __DIR__ . '/../Models/Parametre.php';
require_once __DIR__ . '/../Models/Etudiant.php';
require_once __DIR__ . '/../../core/Format.php';
require_once __DIR__ . '/../Models/MouvementPoint.php';
require_once __DIR__ . '/../Models/Presence.php';
require_once __DIR__ . '/../Models/Signalement.php';

class EspaceEtudiantController {
    private array $etudiant;

    public function __construct() {
        $fiche = Etudiant::fiche(Auth::idPersonne());
        if (!$fiche || $fiche['STATUT_COMPTE'] !== 'ACTIF') {
            session_destroy();
            Auth::rediriger('login', ['erreur' => $fiche ? 'compte_inactif' : 'acces_interdit']);
        }
        $this->etudiant = $fiche;
    }

    public function traiter(string $action): void {
        $methodes = [
            'etudiant_dashboard'    => 'dashboard',
            'etudiant_points'       => 'points',
            'etudiant_presences'    => 'presences',
            'etudiant_justificatif' => 'justificatif',
            'etudiant_fichier'      => 'fichier',
            'etudiant_signalements' => 'signalements',
            'etudiant_repondre'     => 'repondre',
            'etudiant_resultats'    => 'resultats',
            'etudiant_club'         => 'club',
            'etudiant_profil'       => 'profil',
            'etudiant_photo'        => 'photo',
            'etudiant_releve'       => 'releve',
            'etudiant_parametres'   => 'parametres',
            'etudiant_coordonnees'  => 'coordonnees',
            'etudiant_mot_de_passe' => 'motDePasse',
            'etudiant_appel'        => 'appel',
            'etudiant_signaler'     => 'signaler',
        ];
        if (!isset($methodes[$action])) {
            Erreur::introuvable();
        }
        try {
            $this->{$methodes[$action]}();
        } catch (PDOException $e) {
            error_log('Espace étudiant : ' . $e->getMessage());
            Erreur::technique();
        }
    }

    public function dashboard(): void {
        $semestre = Etudiant::semestreCourant();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $solde = $semestre ? MouvementPoint::solde($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : null;
        $derniers = $semestre ? array_slice(MouvementPoint::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']), 0, 5) : [];
        $alertes = [];
        if ($semestre) {
            $aJustifier = count(array_filter(Presence::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']), [Presence::class, 'peutJustifier']));
            if ($aJustifier > 0) {
                $alertes[] = Icone::svg('liste', 16) . '<span>' . $aJustifier . ' absence' . ($aJustifier > 1 ? 's' : '') . ' à justifier avant expiration du délai. <a href="index.php?action=etudiant_presences">Voir mes présences</a></span>';
            }
        }
        $dossiers = Signalement::pourEtudiant($id);
        $sansReponse = count(array_filter($dossiers, fn($d) => $d['REPONSE_ETUDIANT'] === null && Signalement::peutRepondre($d)));
        if ($sansReponse > 0) {
            $alertes[] = Icone::svg('drapeau', 16) . '<span>' . $sansReponse . ' signalement' . ($sansReponse > 1 ? 's' : '') . ' en attente de votre réponse. <a href="index.php?action=etudiant_signalements">Voir mes signalements</a></span>';
        }
        $recentes = count(array_filter($dossiers, fn($d) => $d['DATE_DECISION'] !== null && strtotime($d['DATE_DECISION']) >= strtotime('-7 days')));
        if ($recentes > 0) {
            $alertes[] = Icone::svg('marteau', 16) . '<span>' . $recentes . ' décision' . ($recentes > 1 ? 's' : '') . ' rendue' . ($recentes > 1 ? 's' : '') . ' ces 7 derniers jours. <a href="index.php?action=etudiant_signalements">Consulter</a></span>';
        }
        $this->vue('dashboard', 'Tableau de bord', 'dashboard', [
            'semestre' => $semestre,
            'solde'    => $solde,
            'derniers' => $derniers,
            'alertes'  => $alertes,
        ]);
    }

    public function points(): void {
        $semestre = $this->semestreChoisi();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $this->vue('points', 'Mes points', 'points', [
            'semestre'   => $semestre,
            'semestres'  => Etudiant::semestres(),
            'solde'      => $semestre ? MouvementPoint::solde($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : null,
            'mouvements' => $semestre ? MouvementPoint::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : [],
        ]);
    }

    public function presences(): void {
        $semestre = $this->semestreChoisi();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $this->vue('presences', 'Mes présences', 'presences', [
            'semestre'  => $semestre,
            'semestres' => Etudiant::semestres(),
            'presences' => $semestre ? Presence::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : [],
            'delai'     => Parametre::nombre('DELAI_DEPOT_JUSTIFICATIF_HEURES', 24),
            'jeton'     => Auth::jeton(),
        ]);
    }

    public function justificatif(): void {
        $this->exigerPost();
        $idPresence = (int)($_POST['presence'] ?? 0);
        $absence = Presence::absence($idPresence, (int)$this->etudiant['ID_PERSONNE']);
        if (!$absence || !Presence::peutJustifier($absence)) {
            $this->retour('etudiant_presences', "Cette absence ne peut pas ou plus être justifiée.", false);
        }
        $motif = trim($_POST['motif'] ?? '');
        if ($motif === '') {
            $this->retour('etudiant_presences', "Le motif est obligatoire.", false);
        }
        try {
            $chemin = null;
            if (Fichier::estPresent($_FILES['fichier'] ?? [])) {
                $chemin = Fichier::enregistrer($_FILES['fichier'], 'justificatifs', ['pdf', 'jpg', 'jpeg', 'png', 'webp']);
            }
            Presence::deposerJustificatif($idPresence, $motif, $chemin);
        } catch (Exception $e) {
            $this->retour('etudiant_presences', $e->getMessage(), false);
        }
        $this->retour('etudiant_presences', "Justificatif envoyé. Il sera examiné par le chargé de discipline.");
    }

    public function fichier(): void {
        $justificatif = Presence::justificatif((int)($_GET['id'] ?? 0), (int)$this->etudiant['ID_PERSONNE']);
        if (!$justificatif || !$justificatif['CHEMIN_FICHIER']) {
            Erreur::interdit("Retour à mes présences", 'index.php?action=etudiant_presences');
        }
        $extension = pathinfo($justificatif['CHEMIN_FICHIER'], PATHINFO_EXTENSION);
        Fichier::envoyer($justificatif['CHEMIN_FICHIER'], 'justificatif-' . $justificatif['DATE_SEANCE'] . '.' . $extension);
    }

    public function signalements(): void {
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $sid = (int)($_GET['id'] ?? 0);
        if ($sid > 0) {
            $dossier = Signalement::detail($sid, $id);
            if (!$dossier) {
                $this->retour('etudiant_signalements', "Ce dossier est introuvable ou ne vous concerne pas.", false);
            }
            $this->vue('signalement', 'Signalement', 'signalements', [
                'dossier'      => $dossier,
                'peutRepondre' => Signalement::peutRepondre($dossier),
                'jeton'        => Auth::jeton(),
            ]);
            return;
        }
        $this->vue('signalements', 'Mes signalements', 'signalements', ['dossiers' => Signalement::pourEtudiant($id)]);
    }

    public function repondre(): void {
        $this->exigerPost();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $sid = (int)($_POST['signalement'] ?? 0);
        $dossier = Signalement::detail($sid, $id);
        if (!$dossier || !Signalement::peutRepondre($dossier)) {
            $this->retour('etudiant_signalements', "Ce dossier n'accepte plus de réponse.", false);
        }
        $reponse = trim($_POST['reponse'] ?? '');
        if ($reponse === '') {
            $this->retour('etudiant_signalements', "La réponse ne peut pas être vide.", false, ['id' => $sid]);
        }
        Signalement::repondre($sid, $id, $reponse);
        $this->retour('etudiant_signalements', "Votre réponse a été enregistrée.", true, ['id' => $sid]);
    }

    public function resultats(): void {
        $semestre = $this->semestreChoisi();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $this->vue('resultats', 'Mes résultats', 'resultats', [
            'semestre'  => $semestre,
            'semestres' => Etudiant::semestres(),
            'solde'     => $semestre ? MouvementPoint::solde($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : null,
            'resultat'  => $semestre ? Etudiant::resultat($id, (int)$semestre['ID_SEMESTRE']) : null,
        ]);
    }

    public function club(): void {
        $clubs = Etudiant::clubs();
        $monClub = null;
        foreach ($clubs as $c) {
            if ($this->etudiant['ID_CLUB'] !== null && (int)$c['ID_CLUB'] === (int)$this->etudiant['ID_CLUB']) {
                $monClub = $c;
            }
        }
        $this->vue('club', 'Mon club', 'club', [
            'clubs'   => $clubs,
            'monClub' => $monClub,
            'seances' => $monClub ? Etudiant::seancesAVenir((int)$monClub['ID_CLUB']) : [],
        ]);
    }

    public function profil(): void {
        $this->vue('profil', 'Mon profil', 'profil', ['jeton' => Auth::jeton()]);
    }

    public function photo(): void {
        $this->exigerPost();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        if (!empty($_POST['retirer'])) {
            Fichier::supprimerPublic($this->etudiant['PHOTO']);
            Personne::modifierPhoto($id, null);
            $this->retour('etudiant_profil', "Photo retirée.");
        }
        if (!Fichier::estPresent($_FILES['photo'] ?? [])) {
            $this->retour('etudiant_profil', "Choisissez une image à envoyer.", false);
        }
        try {
            $chemin = Fichier::enregistrerPublic($_FILES['photo'], 'assets/uploads', ['jpg', 'jpeg', 'png', 'webp']);
        } catch (Exception $e) {
            $this->retour('etudiant_profil', $e->getMessage(), false);
        }
        Fichier::supprimerPublic($this->etudiant['PHOTO']);
        Personne::modifierPhoto($id, $chemin);
        $this->retour('etudiant_profil', "Photo de profil mise à jour.");
    }

    public function releve(): void {
        $semestre = $this->semestreChoisi();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $this->vue('releve', 'Relevé de Formation Humaine', 'points', [
            'semestre'   => $semestre,
            'semestres'  => Etudiant::semestres(),
            'solde'      => $semestre ? MouvementPoint::solde($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : null,
            'mouvements' => $semestre ? MouvementPoint::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : [],
            'presences'  => $semestre ? Presence::liste($id, $semestre['DATE_DEBUT'], $semestre['DATE_FIN']) : [],
            'resultat'   => $semestre ? Etudiant::resultat($id, (int)$semestre['ID_SEMESTRE']) : null,
        ]);
    }

    public function parametres(): void {
        $this->vue('parametres', 'Paramètres', 'parametres', ['jeton' => Auth::jeton()]);
    }

    public function coordonnees(): void {
        $this->exigerPost();
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        if ($telephone === '' || mb_strlen($telephone) > 100 || mb_strlen($adresse) > 100) {
            $this->retour('etudiant_parametres', "Le téléphone est obligatoire et chaque champ est limité à 100 caractères.", false);
        }
        Personne::modifierCoordonnees((int)$this->etudiant['ID_PERSONNE'], $telephone, $adresse === '' ? null : $adresse);
        $this->retour('etudiant_parametres', "Coordonnées enregistrées.");
    }

    public function motDePasse(): void {
        $this->exigerPost();
        $id = (int)$this->etudiant['ID_PERSONNE'];
        $actuel = $_POST['actuel'] ?? '';
        $nouveau = $_POST['nouveau'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';
        if (!password_verify($actuel, Personne::motDePasseHash($id))) {
            $this->retour('etudiant_parametres', "Le mot de passe actuel est incorrect.", false);
        }
        if (strlen($nouveau) < 8) {
            $this->retour('etudiant_parametres', "Le nouveau mot de passe doit contenir au moins 8 caractères.", false);
        }
        if ($nouveau !== $confirmation) {
            $this->retour('etudiant_parametres', "La confirmation ne correspond pas au nouveau mot de passe.", false);
        }
        if ($nouveau === $actuel) {
            $this->retour('etudiant_parametres', "Le nouveau mot de passe doit être différent de l'actuel.", false);
        }
        Personne::changerMotDePasse($id, $nouveau);
        $this->retour('etudiant_parametres', "Mot de passe changé.");
    }

    public function appel(): void {
        Auth::exigerDelegue($this->etudiant);
        $idPromo = (int)$this->etudiant['ID_PROMO'];
        $camarades = Etudiant::camarades($idPromo);
        $saisie = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            $saisie = [
                'titre'  => trim($_POST['titre'] ?? ''),
                'date'   => trim($_POST['date'] ?? ''),
                'debut'  => trim($_POST['debut'] ?? ''),
                'fin'    => trim($_POST['fin'] ?? ''),
                'lieu'   => trim($_POST['lieu'] ?? ''),
                'statut' => [],
            ];
            $erreur = null;
            $date = DateTime::createFromFormat('!Y-m-d', $saisie['date']);
            $debut = DateTime::createFromFormat('H:i', $saisie['debut']);
            $fin = DateTime::createFromFormat('H:i', $saisie['fin']);
            if ($saisie['titre'] === '' || $saisie['lieu'] === '' || mb_strlen($saisie['titre']) > 100 || mb_strlen($saisie['lieu']) > 50) {
                $erreur = "L'intitulé (100 caractères max) et le lieu (50 caractères max) sont obligatoires.";
            } elseif (!$date || !$debut || !$fin) {
                $erreur = "La date ou les heures sont invalides.";
            } elseif ($fin <= $debut) {
                $erreur = "L'heure de fin doit être après l'heure de début.";
            } elseif ($date > new DateTime('today')) {
                $erreur = "La date de la séance ne peut pas être dans le futur.";
            }

            $statuts = [];
            foreach ($camarades as $c) {
                $id = (int)$c['ID_PERSONNE'];
                $statut = strtoupper((string)($_POST['statut'][$id] ?? ''));
                if (!in_array($statut, ['PRESENT', 'RETARD', 'ABSENT'], true)) {
                    $erreur = $erreur ?? "Chaque étudiant doit avoir un statut.";
                    $statut = 'PRESENT';
                }
                $statuts[$id] = $statut;
                $saisie['statut'][$id] = $statut;
            }
            if (empty($statuts)) {
                $erreur = $erreur ?? "Aucun étudiant dans la promotion.";
            }

            if ($erreur === null) {
                try {
                    Presence::enregistrerAppel($idPromo, (int)$this->etudiant['ID_PERSONNE'], [
                        'titre' => $saisie['titre'], 'date' => $saisie['date'],
                        'debut' => $saisie['debut'] . ':00', 'fin' => $saisie['fin'] . ':00', 'lieu' => $saisie['lieu'],
                    ], $statuts);
                    $this->retour('etudiant_appel', "Appel enregistré pour « " . $saisie['titre'] . " ».");
                } catch (Exception $e) {
                    $erreur = "L'appel n'a pas pu être enregistré : " . $e->getMessage();
                }
            }
            $_SESSION['error_message'] = $erreur;
        }

        $this->vue('appel', "Faire l'appel", 'appel', [
            'camarades' => $camarades,
            'appels'    => Presence::appelsPromo($idPromo),
            'saisie'    => $saisie,
            'jeton'     => Auth::jeton(),
        ]);
    }

    public function signaler(): void {
        Auth::exigerDelegue($this->etudiant);
        $moi = (int)$this->etudiant['ID_PERSONNE'];
        $camarades = array_values(array_filter(Etudiant::camarades((int)$this->etudiant['ID_PROMO']), fn($c) => (int)$c['ID_PERSONNE'] !== $moi));
        $criteres = Signalement::criteresParDomaine();
        $saisie = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            $saisie = [
                'etudiant'    => (int)($_POST['etudiant'] ?? 0),
                'critere'     => (int)($_POST['critere'] ?? 0),
                'titre'       => trim($_POST['titre'] ?? ''),
                'date_faits'  => trim($_POST['date_faits'] ?? ''),
                'lieu'        => trim($_POST['lieu'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
            ];
            $idsCamarades = array_map(fn($c) => (int)$c['ID_PERSONNE'], $camarades);
            $idsCriteres = [];
            foreach ($criteres as $liste) {
                foreach ($liste as $cr) {
                    $idsCriteres[] = (int)$cr['ID_CRITERE'];
                }
            }
            $dateFaits = DateTime::createFromFormat('Y-m-d\TH:i', $saisie['date_faits']);
            $erreur = null;
            if (!in_array($saisie['etudiant'], $idsCamarades, true)) {
                $erreur = "Choisissez un étudiant de votre promotion. Vous ne pouvez pas vous signaler vous-même.";
            } elseif (!in_array($saisie['critere'], $idsCriteres, true)) {
                $erreur = "Choisissez un critère.";
            } elseif ($saisie['titre'] === '' || $saisie['lieu'] === '' || $saisie['description'] === '' || mb_strlen($saisie['titre']) > 100 || mb_strlen($saisie['lieu']) > 255) {
                $erreur = "L'objet, le lieu et la description sont obligatoires.";
            } elseif (!$dateFaits || $dateFaits > new DateTime()) {
                $erreur = "La date des faits est invalide ou dans le futur.";
            }

            $pieces = [];
            if ($erreur === null) {
                try {
                    foreach (Fichier::normaliserMultiple($_FILES['preuves'] ?? []) as $f) {
                        $chemin = Fichier::enregistrer($f, 'preuves', ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'mp4']);
                        $pieces[] = ['nom' => $f['name'], 'chemin' => $chemin, 'type' => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION))];
                    }
                    Signalement::creerPlusieurs($moi, [$saisie['etudiant']], [
                        'critere' => $saisie['critere'], 'titre' => $saisie['titre'], 'description' => $saisie['description'],
                        'dateFaits' => $dateFaits->format('Y-m-d H:i:00'), 'lieu' => $saisie['lieu'],
                    ], [], $pieces);
                    $this->retour('etudiant_signaler', "Signalement transmis au chargé de discipline.");
                } catch (Exception $e) {
                    foreach ($pieces as $p) {
                        @unlink(Fichier::racine() . $p['chemin']);
                    }
                    $erreur = $e->getMessage();
                }
            }
            $_SESSION['error_message'] = $erreur;
        }

        $this->vue('signaler', 'Signaler un comportement', 'signaler', [
            'camarades' => $camarades,
            'criteres'  => $criteres,
            'emis'      => Signalement::emisPar($moi),
            'saisie'    => $saisie,
            'jeton'     => Auth::jeton(),
        ]);
    }

    private function vue(string $nom, string $titre, string $actif, array $data = []): void {
        $etudiant = $this->etudiant;
        $utilisateur = [
            'nom'         => $etudiant['PRENOM'] . ' ' . $etudiant['NOM'],
            'sousTitre'   => (!empty($etudiant['EST_DELEGUE']) ? Format::genre($etudiant['SEXE'], 'Délégué', 'Déléguée') : Format::genre($etudiant['SEXE'], 'Étudiant', 'Étudiante')) . ', ' . $etudiant['CODE_PROMO'],
            'photo'       => $etudiant['PHOTO'],
            'identifiant' => $etudiant['MATRICULE'],
        ];
        $menu = $this->menu();
        extract($data);
        require __DIR__ . '/../Views/partials/entete.php';
        require __DIR__ . '/../Views/etudiant/' . $nom . '.php';
        require __DIR__ . '/../Views/partials/pied.php';
    }

    private function menu(): array {
        $entree = fn(string $cle, string $icone, string $libelle) => ['cle' => $cle, 'action' => 'etudiant_' . $cle, 'icone' => $icone, 'libelle' => $libelle];
        $sections = [['titre' => 'Mon espace', 'entrees' => [
            $entree('dashboard', 'tableau', 'Tableau de bord'),
            $entree('points', 'etoile', 'Mes points'),
            $entree('presences', 'liste', 'Mes présences'),
            $entree('signalements', 'drapeau', 'Mes signalements'),
            $entree('resultats', 'diplome', 'Mes résultats'),
            $entree('club', 'groupe', 'Mon club'),
            $entree('profil', 'personne', 'Mon profil'),
            $entree('parametres', 'reglages', 'Paramètres'),
        ]]];
        if (!empty($this->etudiant['EST_DELEGUE'])) {
            $sections[] = ['titre' => 'Délégué de promotion', 'entrees' => [
                $entree('appel', 'appel', "Faire l'appel"),
                $entree('signaler', 'alerte', 'Signaler un comportement'),
            ]];
        }
        return $sections;
    }

    private function semestreChoisi(): ?array {
        $id = (int)($_GET['semestre'] ?? 0);
        $choisi = $id > 0 ? Etudiant::semestre($id) : null;
        return $choisi ?? Etudiant::semestreCourant();
    }

    private function exigerPost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Auth::rediriger('etudiant_dashboard');
        }
        Auth::verifierJeton();
    }

    private function retour(string $action, string $message, bool $succes = true, array $params = []): never {
        $_SESSION[$succes ? 'success_message' : 'error_message'] = $message;
        Auth::rediriger($action, $params);
    }
}
