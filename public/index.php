<?php
// public/index.php — point d'entrée unique
require_once __DIR__ . '/../core/Env.php';

// FORCER L'AFFICHAGE DES ERREURS TEMPORAIREMENT
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../core/Routeur.php';
require_once __DIR__ . '/../app/Controllers/SiteController.php';

// Sans paramètre action, l'adresse désigne une page du site vitrine par son chemin (« /formations »),
// que l'application soit à la racine de l'hôte ou dans un sous-dossier. Apache et Nginx renvoient
// ces chemins vers index.php (voir documentation/installation.md) ; le serveur intégré de PHP le fait seul.
$action = $_GET['action'] ?? $_GET['route'] ?? null;
if ($action === null) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
    $chemin = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($base !== '' && str_starts_with($chemin, $base)) {
        $chemin = substr($chemin, strlen($base));
    }
    $slug = trim($chemin, '/');
    if ($slug === 'index.php') {
        $slug = '';
    }
    // Une barre finale ferait pointer les liens relatifs (assets/, autres pages) sous le chemin de la page.
    if ($slug !== '' && str_ends_with($chemin, '/') && SiteController::actionPourChemin($slug) !== null) {
        $suite = ($_SERVER['QUERY_STRING'] ?? '') !== '' ? '?' . $_SERVER['QUERY_STRING'] : '';
        header('Location: ' . $base . '/' . $slug . $suite, true, 301);
        exit();
    }
    $action = SiteController::actionPourChemin($slug) ?? 'introuvable';
}

// Cookie de session : inaccessible au JavaScript, non envoyé depuis un autre site, chiffré si l'application l'est.
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => str_starts_with(Env::lire('BASE_URL', 'http://localhost:8000'), 'https://'),
]);
// Les pages publiques n'ouvrent pas de session pour un simple visiteur : pas de cookie déposé,
// pas de fichier de session par passage de robot. Elle s'ouvre dès qu'un cookie existe.
$pagePublique = SiteController::estPage($action) || $action === 'introuvable';
if (!$pagePublique || isset($_COOKIE[session_name()]) || isset($_COOKIE[Auth::COOKIE_RECONNEXION])) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Appareil mémorisé (« rester connecté ») : rouvre la session si elle est absente ou expirée.
Auth::reprendre();

switch ($action) {
    case 'accueil':
    case 'vie_etudiante':
    case 'formations':
    case 'admission':
    case 'contact':
        (new SiteController())->traiter($action);
        break;

    case 'introuvable':
        Erreur::introuvable();
        break;

    case 'login':
    case 'logout':
    case 'premiere_connexion':
    case 'mot_de_passe_oublie':
    case 'reset_password':
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
    

case 'admin_roles':
    case 'admin_roles_creer':
        require_once __DIR__ . '/../app/Controllers/Admin/RoleController.php';
        $controller = new RoleController();
        if ($action === 'admin_roles') {
            $controller->index();
        } else {
            $controller->creer();
        }
        break;
    default:
        if (str_starts_with($action, 'admin_') || Routeur::estConnue($action)) {
            Routeur::traiter($action);
            break;
        }
        Erreur::introuvable();
}
