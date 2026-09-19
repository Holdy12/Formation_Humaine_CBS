<?php
// app/Controllers/SiteController.php — site vitrine public : pages servies par leur chemin (« /formations »).
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Icone.php';
require_once __DIR__ . '/../../core/Composant.php';
require_once __DIR__ . '/../../core/Format.php';

class SiteController {
    // Chemin public => action. « formation-humaine » et « connexion » sont des alias.
    public const CHEMINS = [
        ''                  => 'accueil',
        'vie-etudiante'     => 'vie_etudiante',
        'formation-humaine' => 'vie_etudiante',
        'formations'        => 'formations',
        'admission'         => 'admission',
        'contact'           => 'contact',
        'connexion'         => 'login',
    ];

    // Action => [vue, titre court, chemin public]. Le titre complet et la description viennent de contenu.php.
    public const PAGES = [
        'accueil'       => ['accueil',       'Accueil',       ''],
        'vie_etudiante' => ['vie_etudiante', 'Vie étudiante', 'vie-etudiante'],
        'formations'    => ['formations',    'Formations',    'formations'],
        'admission'     => ['admission',     'Admission',     'admission'],
        'contact'       => ['contact',       'Contact',       'contact'],
    ];

    public static function actionPourChemin(string $chemin): ?string {
        return self::CHEMINS[$chemin] ?? null;
    }

    public static function estPage(string $action): bool {
        return isset(self::PAGES[$action]);
    }

    public function traiter(string $action): void {
        if (!self::estPage($action)) {
            Erreur::introuvable();
        }
        [$vue, $titreCourt, $chemin] = self::PAGES[$action];
        $contenu = require __DIR__ . '/../Views/site/contenu.php';
        if ($action === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->recevoirMessage($contenu['contact']);
        }
        $formulaire = $action === 'contact' ? $this->etatFormulaire() : null;
        $page = $contenu['pages'][$action];
        $titre = $page['titre'];
        $description = $page['description'];
        $connecte = Auth::idPersonne() > 0;
        $prenom = $connecte ? (string)($_SESSION['prenom'] ?? '') : '';
        // Le lien vers l'application passe toujours par index.php : il fonctionne même sans réécriture d'adresse.
        $lienEspace = 'index.php?action=' . ($connecte ? Auth::accueil() : 'login');
        $pageCourante = $action;
        require __DIR__ . '/../Views/site/cadre_debut.php';
        require __DIR__ . '/../Views/site/' . $vue . '.php';
        require __DIR__ . '/../Views/site/cadre_fin.php';
    }

    // Formulaire de contact : contrôle, puis transmission ; le résultat passe par la session et la page
    // est rechargée (pas de renvoi du formulaire en actualisant). Le champ « site_web », invisible,
    // n'est rempli que par les robots : le message est alors écarté sans le dire.
    private function recevoirMessage(array $contact): never {
        $valeurs = [
            'nom'       => trim(mb_substr((string)($_POST['nom'] ?? ''), 0, 100)),
            'email'     => trim(mb_substr((string)($_POST['email'] ?? ''), 0, 150)),
            'telephone' => trim(mb_substr((string)($_POST['telephone'] ?? ''), 0, 30)),
            'objet'     => (string)($_POST['objet'] ?? ''),
            'message'   => trim(mb_substr((string)($_POST['message'] ?? ''), 0, 2000)),
        ];
        $erreurs = [];
        if (!Auth::jetonValide()) {
            $etat = 'expire';
        } elseif (($_POST['site_web'] ?? '') !== '') {
            $etat = 'recu';
            $valeurs = [];
        } else {
            if ($valeurs['nom'] === '') {
                $erreurs['nom'] = "Indiquez votre nom.";
            }
            if (!filter_var($valeurs['email'], FILTER_VALIDATE_EMAIL)) {
                $erreurs['email'] = "Indiquez une adresse e-mail valide, pour que nous puissions vous répondre.";
            }
            if (!isset($contact['objets'][$valeurs['objet']])) {
                $erreurs['objet'] = "Choisissez l'objet de votre message.";
            }
            if (mb_strlen($valeurs['message']) < 10) {
                $erreurs['message'] = "Écrivez votre message (10 caractères au moins).";
            }
            if ($erreurs) {
                $etat = 'erreurs';
            } elseif (self::transmettre($valeurs, $contact)) {
                $etat = 'recu';
                $valeurs = [];
            } else {
                $etat = 'indisponible';
            }
        }
        $_SESSION['contact_retour'] = ['etat' => $etat, 'erreurs' => $erreurs, 'valeurs' => $valeurs];
        header('Location: contact#formulaire', true, 303);
        exit();
    }

    // Point d'envoi du formulaire. Il fonctionne dès que core/Courriel.php existe et que COURRIEL_ACTIF
    // vaut 1 (documentation/courriel-resend.md) ; d'ici là, rien n'est envoyé et le visiteur en est averti.
    private static function transmettre(array $v, array $contact): bool {
        $classe = __DIR__ . '/../../core/Courriel.php';
        if (!is_file($classe)) {
            return false;
        }
        require_once $classe;
        $h = fn(string $t) => nl2br(htmlspecialchars($t));
        return Courriel::envoyer(
            $contact['destinataire'],
            'Site : ' . $contact['objets'][$v['objet']] . ' — ' . $v['nom'],
            'Message reçu depuis le site',
            '<p><strong>' . $h($v['nom']) . '</strong><br>' . $h($v['email']) . ($v['telephone'] !== '' ? '<br>' . $h($v['telephone']) : '') . '</p>'
            . '<p>Objet : ' . $h($contact['objets'][$v['objet']]) . '</p><p>' . $h($v['message']) . '</p>'
        );
    }

    // Résultat du dernier envoi (lu une seule fois) et jeton du formulaire.
    private function etatFormulaire(): array {
        $retour = $_SESSION['contact_retour'] ?? ['etat' => null, 'erreurs' => [], 'valeurs' => []];
        unset($_SESSION['contact_retour']);
        $retour['jeton'] = Auth::jeton();
        return $retour;
    }
}
