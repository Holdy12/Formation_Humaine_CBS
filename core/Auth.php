<?php
// core/Auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Permissions.php';
require_once __DIR__ . '/Erreur.php';

class Auth {
    // Cookie « rester connecté » : voir JetonConnexion. Durée glissante, renouvelée à chaque reprise.
    public const COOKIE_RECONNEXION = 'reconnexion';
    public const DUREE_RECONNEXION = 10 * 24 * 3600;

    private static ?bool $animeUnClub = null;

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
            if (self::$animeUnClub === null) {
                $stmt = Database::getConnection()->prepare("SELECT 1 FROM CLUB WHERE ID_RESPONSABLE = :id LIMIT 1");
                $stmt->execute(['id' => self::idPersonne()]);
                self::$animeUnClub = (bool)$stmt->fetchColumn();
            }
            return self::$animeUnClub;
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
        $_SESSION['user_role'] = strtoupper($personne['CODE_ROLE']);
        $_SESSION['nom'] = $personne['NOM'];
        $_SESSION['prenom'] = $personne['PRENOM'];
        $_SESSION['doit_changer_mdp'] = !empty($personne['DOIT_CHANGER_MDP']);
    }

    public static function deconnecter(): never {
        self::demarrer();
        self::oublier();
        $_SESSION = [];
        session_destroy();
        self::rediriger('login');
    }

    // Mémorise l'appareil : jeton en base et cookie de reconnexion.
    public static function memoriser(int $idPersonne): void {
        require_once __DIR__ . '/../app/Models/JetonConnexion.php';
        self::deposerCookie(JetonConnexion::emettre($idPersonne), self::DUREE_RECONNEXION);
    }

    // Sans session ouverte mais avec un cookie de reconnexion valide, rouvre la session de la
    // personne et renouvelle le jeton. Appelée à chaque requête depuis public/index.php.
    public static function reprendre(): void {
        $cookie = $_COOKIE[self::COOKIE_RECONNEXION] ?? '';
        if ($cookie === '') {
            return;
        }
        self::demarrer();
        if (self::idPersonne() > 0) {
            return;
        }
        require_once __DIR__ . '/../app/Models/JetonConnexion.php';
        require_once __DIR__ . '/../app/Models/Personne.php';
        require_once __DIR__ . '/Journal.php';
        $jeton = JetonConnexion::verifier($cookie);
        $personne = $jeton ? Personne::trouver((int)$jeton['ID_PERSONNE']) : null;
        if (!$personne || $personne['STATUT_COMPTE'] !== 'ACTIF') {
            if ($jeton) {
                JetonConnexion::revoquer($jeton['SELECTEUR']);
            }
            self::deposerCookie('', 0);
            return;
        }
        self::connecter($personne);
        // Requête partie en même temps qu'une autre qui a déjà renouvelé le jeton : le navigateur
        // garde le cookie déposé par celle-ci, on ne renouvelle pas une seconde fois.
        if (!$jeton['CONCURRENT']) {
            self::deposerCookie(JetonConnexion::renouveler($jeton), self::DUREE_RECONNEXION);
        }
        Journal::ecrire('Connexion', 'Reprise de session sur un appareil mémorisé');
    }

    // Révoque le jeton de cet appareil et efface le cookie (déconnexion).
    public static function oublier(): void {
        $cookie = $_COOKIE[self::COOKIE_RECONNEXION] ?? '';
        if ($cookie === '') {
            return;
        }
        if (preg_match('/^([a-f0-9]{24})\./', $cookie, $m)) {
            require_once __DIR__ . '/../app/Models/JetonConnexion.php';
            JetonConnexion::revoquer($m[1]);
        }
        self::deposerCookie('', 0);
        unset($_COOKIE[self::COOKIE_RECONNEXION]);
    }

    // Une durée nulle efface le cookie. Le chemin suit le dossier de l'application (racine ou sous-dossier).
    private static function deposerCookie(string $valeur, int $duree): void {
        $chemin = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
        setcookie(self::COOKIE_RECONNEXION, $valeur, [
            'expires'  => $duree > 0 ? time() + $duree : 1,
            'path'     => $chemin === '' ? '/' : $chemin,
            'secure'   => str_starts_with(BASE_URL, 'https://'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
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
