<?php
// core/Auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Permissions.php';
require_once __DIR__ . '/Erreur.php';

class Auth {
    public static function demarrer(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function idPersonne(): int {
        return (int)($_SESSION['user_id'] ?? 0);
    }

    public static function role(): string {
        return strtoupper($_SESSION['user_role'] ?? '');
    }

    public static function estPersonnel(): bool {
        return in_array(self::role(), Permissions::ROLES_PERSONNEL, true);
    }

    // Animer un club s'acquiert aussi en étant désigné responsable d'un club, quel que soit le rôle.
    public static function peut(string $permission): bool {
        if (Permissions::possede(self::role(), $permission)) {
            return true;
        }
        if ($permission === 'club.animer' && self::estPersonnel()) {
            if (!isset($_SESSION['anime_un_club'])) {
                $stmt = Database::getConnection()->prepare("SELECT 1 FROM CLUB WHERE ID_RESPONSABLE = :id LIMIT 1");
                $stmt->execute(['id' => self::idPersonne()]);
                $_SESSION['anime_un_club'] = (bool)$stmt->fetchColumn();
            }
            return $_SESSION['anime_un_club'];
        }
        return false;
    }

    public static function rediriger(string $action, array $params = []): never {
        $url = 'index.php?action=' . $action;
        foreach ($params as $cle => $valeur) {
            $url .= '&' . rawurlencode($cle) . '=' . rawurlencode((string)$valeur);
        }
        header('Location: ' . $url);
        exit();
    }

    // Page d'accueil de l'utilisateur connecté selon son rôle.
    public static function accueil(): string {
        if (self::role() === 'ETUDIANT') {
            return 'etudiant_dashboard';
        }
        return self::estPersonnel() ? 'admin_dashboard' : 'login';
    }

    public static function exigerRole(string $role): void {
        self::demarrer();
        if (self::idPersonne() === 0) {
            self::rediriger('login');
        }
        if (!empty($_SESSION['doit_changer_mdp'])) {
            self::rediriger('premiere_connexion');
        }
        if (self::role() === strtoupper($role)) {
            return;
        }
        if (self::estPersonnel()) {
            self::rediriger('admin_dashboard');
        }
        self::rediriger('login', ['erreur' => 'acces_interdit']);
    }

    public static function exigerPersonnel(): void {
        self::demarrer();
        if (self::idPersonne() === 0) {
            self::rediriger('login');
        }
        if (!empty($_SESSION['doit_changer_mdp'])) {
            self::rediriger('premiere_connexion');
        }
        if (self::estPersonnel()) {
            return;
        }
        if (self::role() === 'ETUDIANT') {
            self::rediriger('etudiant_dashboard');
        }
        self::rediriger('login', ['erreur' => 'acces_interdit']);
    }

    public static function exigerPermission(string $permission): void {
        if (!self::peut($permission)) {
            Erreur::interdit("Retour au tableau de bord", 'index.php?action=admin_dashboard');
        }
    }

    public static function exigerDelegue(array $etudiant): void {
        if (empty($etudiant['EST_DELEGUE'])) {
            $_SESSION['error_message'] = "Cette page est réservée au délégué de promotion.";
            self::rediriger('etudiant_dashboard');
        }
    }

    public static function connecter(array $personne): void {
        self::demarrer();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$personne['ID_PERSONNE'];
        unset($_SESSION['anime_un_club']);
        $_SESSION['user_role'] = strtoupper($personne['CODE_ROLE']);
        $_SESSION['nom'] = $personne['NOM'];
        $_SESSION['prenom'] = $personne['PRENOM'];
        $_SESSION['doit_changer_mdp'] = !empty($personne['DOIT_CHANGER_MDP']);
        unset($_SESSION['echecs_connexion'], $_SESSION['blocage_jusqua']);
    }

    public static function deconnecter(): never {
        self::demarrer();
        $_SESSION = [];
        session_destroy();
        self::rediriger('login');
    }

    public static function jeton(): string {
        self::demarrer();
        if (empty($_SESSION['jeton_csrf'])) {
            $_SESSION['jeton_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['jeton_csrf'];
    }

    public static function jetonValide(): bool {
        self::demarrer();
        $recu = $_POST['jeton'] ?? '';
        return $recu !== '' && !empty($_SESSION['jeton_csrf']) && hash_equals($_SESSION['jeton_csrf'], $recu);
    }

    public static function verifierJeton(): void {
        if (!self::jetonValide()) {
            $_SESSION['error_message'] = "Formulaire expiré, veuillez réessayer.";
            self::rediriger(self::accueil());
        }
    }
}
