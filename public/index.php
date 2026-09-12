<?php
// public/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrage unique de la session au tout début
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupération de l'action ou de la route demandée pour assurer la compatibilité
$action = $_GET['action'] ?? $_GET['route'] ?? 'login';

switch ($action) {
    case 'login':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $auth = new AuthController();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $identifiant = trim($_POST['identifiant'] ?? '');
            $password = $_POST['password'] ?? '';
            $auth->login($identifiant, $password);
        }
        
        require_once __DIR__ . '/../app/Views/auth/login.php';
        break;

    case 'logout':
        require_once __DIR__ . '/../app/Controllers/AuthController.php';
        $auth = new AuthController();
        $auth->logout();
        break;

    case 'dashboard':
    case 'admin_dashboard':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }

        require_once __DIR__ . '/../app/Controllers/AdminDashboardController.php';
        $controller = new AdminDashboardController();
        
        $choixAnnee = $_GET['annee'] ?? 'active';
        $choixSemestre = $_GET['semestre'] ?? 'actif';
        $data = $controller->getDashboardData($choixAnnee, $choixSemestre);

        require_once __DIR__ . '/../app/Views/admin/dashboard.php';
        break;

    case 'etudiants':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
        
        require_once __DIR__ . '/../app/Controllers/AdminDashboardController.php';
        $controller = new AdminDashboardController();
        $controller->etudiants();
        break;

    case 'ajouter_etudiant':
    case 'store_etudiant':
    case 'store':
    case 'delete':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
        
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        $controller = new EtudiantController();

        if ($action === 'ajouter_etudiant') {
            if (method_exists($controller, 'createEtudiantForm')) {
                $controller->createEtudiantForm();
            } else {
                require_once __DIR__ . '/../app/Views/admin/ajouter_etudiant.php';
            }
        } elseif ($action === 'store_etudiant' || $action === 'store') {
            if (method_exists($controller, 'storeEtudiant')) {
                $controller->storeEtudiant($_POST, $_FILES);
            }
        } elseif ($action === 'delete') {
            if (method_exists($controller, 'delete')) {
                $controller->delete($_GET['id'] ?? null);
            }
        }
        break;

    // Route pour afficher le formulaire de modification
    case 'modifier_etudiant':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
        
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        $controller = new EtudiantController();
        // S'assure d'appeler la méthode qui charge les données et affiche la vue du formulaire
        if (method_exists($controller, 'editForm')) {
            $controller->editForm($_GET['id'] ?? null);
        } else {
            // Alternative si tu charges directement les données dans une méthode dédiée
            $controller->voirEtudiantPourModification($_GET['id'] ?? null);
        }
        break;

    // Route pour exécuter le traitement de la mise à jour (soumission du formulaire)
    case 'traitement_modifier_etudiant':
    case 'update':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
        
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        $controller = new EtudiantController();
        if (method_exists($controller, 'update')) {
            $controller->update($_GET['id'] ?? null, $_POST, $_FILES);
        }
        break;

    case 'voir_etudiant':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
        
        require_once __DIR__ . '/../app/Controllers/EtudiantController.php';
        $controller = new EtudiantController();
        $controller->voirEtudiant($_GET['id'] ?? null);
        break;

    default:
        http_response_code(404);
        echo "Page non trouvée.";
        break;
}