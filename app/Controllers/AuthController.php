<?php
// app/Controllers/AuthController.php — connexion, déconnexion, première connexion, mot de passe oublié.
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Icone.php';
require_once __DIR__ . '/../../core/Journal.php';
require_once __DIR__ . '/../Models/Personne.php';

class AuthController {
    private const ECHECS_MAX = 5;
    private const BLOCAGE_SECONDES = 30;

    private const MESSAGES = [
        'auth_echouee'   => "Identifiant ou mot de passe incorrect.",
        'champs_vides'   => "Renseignez votre identifiant et votre mot de passe.",
        'compte_inactif' => "Ce compte est désactivé. Rapprochez-vous de l'administration.",
        'blocage'        => "Trop de tentatives. Patientez trente secondes avant de réessayer.",
        'acces_interdit' => "Cette page n'est pas accessible avec votre compte.",
        'role_inconnu'   => "Votre compte n'a pas d'espace associé. Contactez l'administration.",
        'session'        => "La session a expiré, veuillez recommencer.",
        'deconnecte'     => "Vous êtes déconnecté.",
    ];

    public function traiter(string $action): void {
        match ($action) {
            'login'               => $this->connexion(),
            'logout'              => $this->deconnexion(),
            'premiere_connexion'  => $this->premiereConnexion(),
            'mot_de_passe_oublie' => $this->motDePasseOublie(),
            default               => Erreur::introuvable(),
        };
    }

    public function connexion(): void {
        Auth::demarrer();
        if (Auth::idPersonne() > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            Auth::rediriger(!empty($_SESSION['doit_changer_mdp']) ? 'premiere_connexion' : Auth::accueil());
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->traiterConnexion();
        }
        $code = $_GET['erreur'] ?? '';
        $this->vueAuth('login', 'Connexion', [
            'message' => self::MESSAGES[$code] ?? ($code !== '' ? "Une erreur est survenue lors de la connexion." : null),
            'succes'  => $code === 'deconnecte',
            'jeton'   => Auth::jeton(),
        ]);
    }

    private function traiterConnexion(): void {
        if (!Auth::jetonValide()) {
            Auth::rediriger('login', ['erreur' => 'session']);
        }
        if (!empty($_SESSION['blocage_jusqua']) && $_SESSION['blocage_jusqua'] > time()) {
            Auth::rediriger('login', ['erreur' => 'blocage']);
        }
        $identifiant = trim($_POST['identifiant'] ?? '');
        $motDePasse = $_POST['password'] ?? '';
        if ($identifiant === '' || $motDePasse === '') {
            Auth::rediriger('login', ['erreur' => 'champs_vides']);
        }

        $personne = Personne::trouverParIdentifiant($identifiant);
        if (!$personne || !password_verify($motDePasse, $personne['MOT_DE_PASSE'])) {
            $echecs = (int)($_SESSION['echecs_connexion'] ?? 0) + 1;
            $_SESSION['echecs_connexion'] = $echecs;
            if ($echecs >= self::ECHECS_MAX) {
                $_SESSION['blocage_jusqua'] = time() + self::BLOCAGE_SECONDES;
                $_SESSION['echecs_connexion'] = 0;
            }
            Journal::ecrire('Connexion', 'Échec pour l\'identifiant ' . mb_substr($identifiant, 0, 60), 'ECHEC', $personne ? (int)$personne['ID_PERSONNE'] : null);
            Auth::rediriger('login', ['erreur' => 'auth_echouee']);
        }
        if ($personne['STATUT_COMPTE'] !== 'ACTIF') {
            Journal::ecrire('Connexion', 'Compte désactivé', 'ECHEC', (int)$personne['ID_PERSONNE']);
            Auth::rediriger('login', ['erreur' => 'compte_inactif']);
        }

        Auth::connecter($personne);
        Journal::ecrire('Connexion', 'Ouverture de session');
        if (!empty($_SESSION['doit_changer_mdp'])) {
            Auth::rediriger('premiere_connexion');
        }
        $accueil = Auth::accueil();
        if ($accueil === 'login') {
            Auth::deconnecter();
        }
        Auth::rediriger($accueil);
    }

    public function deconnexion(): void {
        Auth::demarrer();
        if (Auth::idPersonne() > 0) {
            Journal::ecrire('Déconnexion', 'Fermeture de session');
        }
        Auth::demarrer();
        $_SESSION = [];
        session_destroy();
        Auth::rediriger('login', ['erreur' => 'deconnecte']);
    }

    public function premiereConnexion(): void {
        Auth::demarrer();
        if (Auth::idPersonne() === 0) {
            Auth::rediriger('login');
        }
        if (empty($_SESSION['doit_changer_mdp'])) {
            Auth::rediriger(Auth::accueil());
        }
        $erreur = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Auth::jetonValide()) {
                $erreur = self::MESSAGES['session'];
            } else {
                $nouveau = $_POST['nouveau'] ?? '';
                $confirmation = $_POST['confirmation'] ?? '';
                $personne = Personne::trouver(Auth::idPersonne());
                if (strlen($nouveau) < 8) {
                    $erreur = "Le mot de passe doit contenir au moins 8 caractères.";
                } elseif ($nouveau !== $confirmation) {
                    $erreur = "La confirmation ne correspond pas au mot de passe.";
                } elseif ($personne && password_verify($nouveau, $personne['MOT_DE_PASSE'])) {
                    $erreur = "Choisissez un mot de passe différent du mot de passe temporaire.";
                } else {
                    Personne::changerMotDePasse(Auth::idPersonne(), $nouveau, false);
                    $_SESSION['doit_changer_mdp'] = false;
                    Journal::ecrire('Mot de passe', 'Mot de passe défini à la première connexion');
                    $_SESSION['success_message'] = "Votre mot de passe est enregistré. Bienvenue.";
                    Auth::rediriger(Auth::accueil());
                }
            }
        }
        $this->vueAuth('premiere_connexion', 'Première connexion', [
            'message' => $erreur,
            'succes'  => false,
            'jeton'   => Auth::jeton(),
            'prenom'  => $_SESSION['prenom'] ?? '',
        ]);
    }

    public function motDePasseOublie(): void {
        $this->vueAuth('mot_de_passe_oublie', 'Mot de passe oublié', ['message' => null, 'succes' => false]);
    }

    private function vueAuth(string $nom, string $titre, array $data): void {
        extract($data);
        require __DIR__ . '/../Views/auth/' . $nom . '.php';
    }
}
