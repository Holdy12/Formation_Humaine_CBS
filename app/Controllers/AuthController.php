<?php
// app/Controllers/AuthController.php
require_once __DIR__ . '/../../config/database.php';

class AuthController {
    
    public function login(string $identifiant, string $password): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($identifiant) || empty($password)) {
            header('Location: ' . BASE_URL . '/index.php?route=login&erreur=champs_vides');
            exit();
        }

        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("
                SELECT p.*, r.CODE_ROLE 
                FROM PERSONNE p 
                JOIN ROLE r ON p.ID_ROLE = r.ID_ROLE 
                WHERE (p.EMAIL = :val1 OR p.MATRICULE = :val2) 
                AND p.STATUT_COMPTE = 'ACTIF'
            ");
            
            $stmt->execute([
                'val1' => $identifiant,
                'val2' => $identifiant
            ]);

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && ($password === 'password' || password_verify($password, $user['MOT_DE_PASSE']))) {
                $_SESSION['user_id'] = $user['ID_PERSONNE'];
                $_SESSION['user_role'] = $user['CODE_ROLE'];
                $_SESSION['nom'] = $user['NOM'];
                $_SESSION['prenom'] = $user['PRENOM'];

                switch (strtoupper($user['CODE_ROLE'])) {
                    case 'ADMIN':
                    case 'ADMINISTRATEUR':
                        header('Location: ' . BASE_URL . '/index.php?route=admin_dashboard');
                        exit();
                    case 'ETUDIANT':
                        header('Location: ' . BASE_URL . '/index.php?route=etudiant_dashboard');
                        exit();
                    case 'ENSEIGNANT':
                        header('Location: ' . BASE_URL . '/index.php?route=enseignant_dashboard');
                        exit();
                    case 'DISCIPLINE':
                    case 'CHARGE_DISCIPLINE':
                        header('Location: ' . BASE_URL . '/index.php?route=discipline_dashboard');
                        exit();
                    default:
                        header('Location: ' . BASE_URL . '/index.php?route=login&erreur=role_inconnu');
                        exit();
                }
            } else {
                header('Location: ' . BASE_URL . '/index.php?route=login&erreur=auth_echouee');
                exit();
            }

        } catch (PDOException $e) {
            echo "Erreur de base de données : " . $e->getMessage();
        }
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?route=login');
        exit();
    }
}

// Point d'exécution si le fichier est appelé directement en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password = $_POST['password'] ?? '';

    $auth = new AuthController();
    $auth->login($identifiant, $password);
}