<?php
// app/Controllers/PersonnelController.php — base commune des contrôleurs de l'espace personnel.
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Fichier.php';
require_once __DIR__ . '/../../core/Format.php';
require_once __DIR__ . '/../../core/Icone.php';
require_once __DIR__ . '/../../core/Composant.php';
require_once __DIR__ . '/../../core/Erreur.php';
require_once __DIR__ . '/../../core/Journal.php';
require_once __DIR__ . '/../Models/Parametre.php';
require_once __DIR__ . '/../Models/Personne.php';

abstract class PersonnelController {
    protected array $utilisateur;

    public function __construct() {
        $personne = Personne::trouver(Auth::idPersonne());
        if (!$personne || $personne['STATUT_COMPTE'] !== 'ACTIF') {
            Auth::deconnecter();
        }
        // Un changement de rôle par l'administrateur s'applique dès la requête suivante.
        $_SESSION['user_role'] = strtoupper($personne['CODE_ROLE']);
        $this->utilisateur = $personne;
    }

    protected function id(): int {
        return (int)$this->utilisateur['ID_PERSONNE'];
    }

    protected function vue(string $nom, string $titre, string $actif, array $data = []): void {
        $utilisateur = [
            'nom'       => $this->utilisateur['PRENOM'] . ' ' . $this->utilisateur['NOM'],
            'sousTitre' => Permissions::libelleRole($this->utilisateur['CODE_ROLE']),
            'photo'     => $this->utilisateur['PHOTO'],
        ];
        $menu = $this->menu();
        $personnel = $this->utilisateur;
        extract($data);
        require __DIR__ . '/../Views/partials/entete.php';
        require __DIR__ . '/../Views/admin/' . $nom . '.php';
        require __DIR__ . '/../Views/partials/pied.php';
    }

    protected function menu(): array {
        $sections = [];
        $entree = fn(string $cle, string $action, string $icone, string $libelle) => ['cle' => $cle, 'action' => $action, 'icone' => $icone, 'libelle' => $libelle];

        $sections[] = ['titre' => 'Général', 'entrees' => [$entree('dashboard', 'admin_dashboard', 'tableau', 'Tableau de bord')]];

        $suivi = [$entree('etudiants', 'admin_etudiants', 'diplome', 'Étudiants')];
        $suivi[] = Auth::peut('signalements.consulter_tous')
            ? $entree('signalements', 'admin_signalements', 'drapeau', 'Signalements')
            : $entree('signalements', 'admin_signalements', 'drapeau', 'Mes signalements');
        $suivi[] = $entree('signalement_nouveau', 'admin_signalement_nouveau', 'plus', 'Nouveau signalement');
        $sections[] = ['titre' => 'Suivi', 'entrees' => $suivi];

        $presences = [$entree('appel', 'admin_appel', 'appel', "Faire l'appel"), $entree('seances', 'admin_seances', 'calendrier', 'Séances')];
        if (Auth::peut('justificatifs.valider')) {
            $presences[] = $entree('justificatifs', 'admin_justificatifs', 'document', 'Justificatifs');
        }
        if (Auth::peut('assiduite.penaliser')) {
            $presences[] = $entree('assiduite', 'admin_assiduite', 'horloge', 'Assiduité');
        }
        $sections[] = ['titre' => 'Présences', 'entrees' => $presences];

        $ecole = [];
        if (Auth::peut('points.consulter')) {
            $ecole[] = $entree('points', 'admin_points', 'etoile', 'Points');
        }
        if (Auth::peut('club.animer')) {
            $ecole[] = $entree('clubs', 'admin_clubs', 'groupe', 'Clubs');
        }
        if ($ecole) {
            $sections[] = ['titre' => "Vie de l'école", 'entrees' => $ecole];
        }

        $administration = [];
        if (Auth::peut('structure.gerer')) {
            $administration[] = $entree('structure', 'admin_structure', 'batiment', 'Structure académique');
        }
        if (Auth::peut('bareme.gerer')) {
            $administration[] = $entree('bareme', 'admin_bareme', 'liste', 'Barème et paramètres');
        }
        if (Auth::peut('comptes.gerer')) {
            $administration[] = $entree('comptes', 'admin_comptes', 'carte', 'Comptes');
        }
        if (Auth::peut('rapports.consulter')) {
            $administration[] = $entree('rapports', 'admin_rapports', 'graphique', 'Rapports');
        }
        if (Auth::peut('journal.consulter')) {
            $administration[] = $entree('journal', 'admin_journal', 'oeil', 'Journal');
        }
       
        if ($administration) {
            $sections[] = ['titre' => 'Administration', 'entrees' => $administration];
        }

        $sections[] = ['titre' => 'Compte', 'entrees' => [$entree('mon_compte', 'admin_mon_compte', 'personne', 'Mon compte')]];
        return $sections;
    }

    protected function exigerPost(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Auth::rediriger('admin_dashboard');
        }
        Auth::verifierJeton();
    }

    protected function retour(string $action, string $message, bool $succes = true, array $params = []): never {
        $_SESSION[$succes ? 'success_message' : 'error_message'] = $message;
        Auth::rediriger($action, $params);
    }

    protected function param(string $cle, string $defaut = ''): string {
        return trim((string)($_GET[$cle] ?? $defaut));
    }

    // Calcule la page courante et les bornes ; conserve les filtres dans les liens de pagination.
    protected function pagination(int $total, int $parPage = 25): array {
        $pages = max(1, (int)ceil($total / $parPage));
        $page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
        $params = $_GET;
        unset($params['page']);
        return [
            'page'   => $page,
            'pages'  => $pages,
            'total'  => $total,
            'debut'  => ($page - 1) * $parPage,
            'limite' => $parPage,
            'base'   => 'index.php?' . http_build_query($params),
        ];
    }


    private function envoyerEmailBienvenue(string $destinataire, string $prenom, string $nom, string $matricule, string $mdp): void {
    $sujet = "Vos identifiants d'accès - CBS Formation Humaine";
    
    $message = "Bonjour $prenom $nom,\n\n";
    $message .= "Votre compte étudiant pour la Formation Humaine du CBS a été créé avec succès.\n\n";
    $message .= "Voici vos informations de connexion :\n";
    $message .= "- Matricule : $matricule\n";
    $message .= "- E-mail : $destinataire\n";
    $message .= "- Mot de passe temporaire : $mdp\n\n";
    $message .= "Veuillez vous connecter sur la plateforme pour modifier votre mot de passe.\n\n";
    $message .= "Cordialement,\nL'équipe pédagogique.";

    $headers = "From: no-reply@cbs.local\r\n" .
               "X-Mailer: PHP/" . phpversion();

    @mail($destinataire, $sujet, $message, $headers);
}
}
