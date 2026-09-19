<?php
// app/Controllers/AuthController.php — connexion, déconnexion, première connexion, mot de passe oublié et réinitialisation.
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Icone.php';
require_once __DIR__ . '/../../core/Journal.php';
require_once __DIR__ . '/../Models/Personne.php';
require_once __DIR__ . '/../Models/TentativeConnexion.php';

class AuthController {
    private const MESSAGES = [
        'auth_echouee'   => "Identifiant ou mot de passe incorrect.",
        'champs_vides'   => "Renseignez votre identifiant et votre mot de passe.",
        'compte_inactif' => "Ce compte est désactivé. Rapprochez-vous de l'administration.",
        'blocage'        => "Trop de tentatives. Patientez dix minutes avant de réessayer.",
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
            'reset_password'      => $this->resetPassword(),
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
        $identifiant = $_SESSION['identifiant_saisi'] ?? '';
        unset($_SESSION['identifiant_saisi']);
        $this->vueAuth('login', 'Connexion', [
            'message'     => self::MESSAGES[$code] ?? ($code !== '' ? "Une erreur est survenue lors de la connexion." : null),
            'succes'      => $code === 'deconnecte',
            'identifiant' => $identifiant,
            'jeton'       => Auth::jeton(),
        ]);
    }

    private function traiterConnexion(): void {
        if (!Auth::jetonValide()) {
            Auth::rediriger('login', ['erreur' => 'session']);
        }
        
        $identifiant = trim($_POST['identifiant'] ?? '');
        $motDePasse = $_POST['password'] ?? '';
        $_SESSION['identifiant_saisi'] = mb_substr($identifiant, 0, 100);
        if ($identifiant === '' || $motDePasse === '') {
            Auth::rediriger('login', ['erreur' => 'champs_vides']);
        }
        if (TentativeConnexion::bloque($identifiant)) {
            Auth::rediriger('login', ['erreur' => 'blocage']);
        }

        $personne = Personne::trouverParIdentifiant($identifiant);
        
        if (!$personne || !password_verify($motDePasse, $personne['MOT_DE_PASSE'])) {
            TentativeConnexion::enregistrerEchec($identifiant);
            Journal::ecrire('Connexion', 'Échec pour l\'identifiant ' . mb_substr($identifiant, 0, 60), 'ECHEC', $personne ? (int)$personne['ID_PERSONNE'] : null);
            Auth::rediriger('login', ['erreur' => 'auth_echouee']);
        }
        if ($personne['STATUT_COMPTE'] !== 'ACTIF') {
            Journal::ecrire('Connexion', 'Compte désactivé', 'ECHEC', (int)$personne['ID_PERSONNE']);
            Auth::rediriger('login', ['erreur' => 'compte_inactif']);
        }

        TentativeConnexion::effacer($identifiant);
        Auth::connecter($personne);
        if (!empty($_POST['rester'])) {
            Auth::memoriser((int)$personne['ID_PERSONNE']);
        }
        Journal::ecrire('Connexion', 'Ouverture de session' . (!empty($_POST['rester']) ? ' (appareil mémorisé)' : ''));
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
        Auth::oublier();
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
        Auth::demarrer();
        $message = null;
        $succes = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            if ($email === '') {
                $message = "Veuillez renseigner votre adresse e-mail.";
            } else {
                $token = Personne::enregistrerTokenReset($email);
                if ($token) {
                    $resetLink = self::lienReinitialisation($token);
                    
                    $sujet = "Réinitialisation de votre mot de passe - CBS";
                    $contenu = "Bonjour,\n\nCliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :\n" . $resetLink . "\n\nCe lien expire dans 1 heure.";
                    $headers = "From: no-reply@cbs.local";
                    
                    @mail($email, $sujet, $contenu, $headers);
                    Journal::ecrire('Mot de passe', 'Demande de réinitialisation pour l\'e-mail ' . $email);
                }
                $succes = true;
                $message = "Si un compte est associé à cet e-mail, un lien de réinitialisation y a été envoyé.";
            }
        }

        $this->vueAuth('mot_de_passe_oublie', 'Mot de passe oublié', [
            'message' => $message,
            'succes'  => $succes
        ]);
    }

    public function resetPassword(): void {
        Auth::demarrer();
        $token = $_GET['token'] ?? $_POST['token'] ?? '';
        $message = null;
        $succes = false;
        $invalide = false;

        $personne = Personne::trouverParTokenReset($token);
        if (!$token || !$personne) {
            $this->vueAuth('reset_password', 'Réinitialisation', [
                'message' => "Le lien de réinitialisation est invalide ou a expiré.",
                'succes' => false,
                'invalide' => true
            ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nouveau = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (strlen($nouveau) < 8) {
                $message = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif ($nouveau !== $confirmation) {
                $message = "La confirmation ne correspond pas au mot de passe.";
            } else {
                Personne::reinitialiserAvecToken($token, $nouveau);
                Journal::ecrire('Mot de passe', 'Mot de passe réinitialisé avec succès via token');
                $_SESSION['success_message'] = "Votre mot de passe a été modifié avec succès. Vous pouvez vous connecter.";
                Auth::rediriger('login');
            }
        }

        $this->vueAuth('reset_password', 'Réinitialisation', [
            'message' => $message,
            'succes' => $succes,
            'token' => $token,
            'invalide' => $invalide
        ]);
    }

    // Toujours sur BASE_URL : construit à partir de l'en-tête Host de la requête, le lien pourrait
    // pointer vers un autre site et y livrer un jeton valide (empoisonnement du lien).
    public static function lienReinitialisation(string $token): string {
        return BASE_URL . '/index.php?action=reset_password&token=' . rawurlencode($token);
    }

    private function vueAuth(string $nom, string $titre, array $data): void {
        extract($data);
        $filePath = __DIR__ . '/../Views/auth/' . $nom . '.php';
        if (!file_exists($filePath)) {
            if ($nom === 'mot_de_passe_oublie') {
                $filePath = __DIR__ . '/../Views/auth/oubli_password.php';
            }
        }
        require $filePath;
    }
}