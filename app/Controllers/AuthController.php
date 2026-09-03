<?php
// app/Controllers/AuthController.php
require_once __DIR__ . '/../../config/database.php';

class AuthController {
    
    public function login(string $email, string $password): bool {
        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, r.CODE_ROLE 
            FROM PERSONNE p 
            JOIN ROLE r ON p.ID_ROLE = r.ID_ROLE 
            WHERE p.EMAIL = :email AND p.STATUT_COMPTE = 'ACTIF'
        ");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['MOT_DE_PASSE'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['ID_PERSONNE'];
            $_SESSION['nom'] = $user['NOM'];
            $_SESSION['prenom'] = $user['PRENOM'];
            $_SESSION['role'] = $user['CODE_ROLE'];
            return true;
        }
        return false;
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
    }
}