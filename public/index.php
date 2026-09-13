<?php
// public/index.php — point d'entrée unique
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../core/Routeur.php';

$action = $_GET['action'] ?? $_GET['route'] ?? 'login';

switch ($action) {
    case 'login':
    case 'logout':
    case 'premiere_connexion':
    case 'mot_de_passe_oublie':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        (new AuthController())->traiter($action);
        break;

    case 'etudiant_dashboard':
    case 'etudiant_points':
    case 'etudiant_presences':
    case 'etudiant_justificatif':
    case 'etudiant_fichier':
    case 'etudiant_signalements':
    case 'etudiant_repondre':
    case 'etudiant_resultats':
    case 'etudiant_club':
    case 'etudiant_profil':
    case 'etudiant_photo':
    case 'etudiant_parametres':
    case 'etudiant_coordonnees':
    case 'etudiant_mot_de_passe':
    case 'etudiant_releve':
    case 'etudiant_appel':
    case 'etudiant_signaler':
        Auth::exigerRole('ETUDIANT');
        require_once __DIR__ . '/../app/Controllers/EspaceEtudiantController.php';
        (new EspaceEtudiantController())->traiter($action);
        break;

    default:
        if (str_starts_with($action, 'admin_') || Routeur::estConnue($action)) {
            Routeur::traiter($action);
            break;
        }
        Erreur::introuvable();
}
