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
        // Vérification de sécurité avant d'afficher le dashboard
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }

        // Chargement du contrôleur pour injecter les données ($data) dans la vue
        require_once __DIR__ . '/../app/Controllers/AdminDashboardController.php';
        $controller = new AdminDashboardController();
        $data = $controller->getDashboardData();

        require_once __DIR__ . '/../app/Views/admin/dashboard.php';
        break;

    case 'etudiants':
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?action=login');
            exit();
        }
        require_once __DIR__ . '/../app/Views/admin/etudiants.php';
        break;

    default:
        http_response_code(404);
        echo "Page non trouvée.";
        break;
}