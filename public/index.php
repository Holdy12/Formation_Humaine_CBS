<?php
// public/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// public/index.php
require_once __DIR__ . '/../app/Controllers/AuthController.php';

session_start();

$auth = new AuthController();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($email, $password)) {
        echo "<h1>Bienvenue " . htmlspecialchars($_SESSION['prenom']) . " ! Connexion réussie.</h1>";
        exit;
    } else {
        $error = "Identifiants incorrects ou compte inactif.";
    }
}

require_once __DIR__ . '/../app/Views/login.php';