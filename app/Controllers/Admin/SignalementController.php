<?php
// app/Controllers/Admin/SignalementController.php — déclaration et instruction des signalements.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Signalement.php';
require_once __DIR__ . '/../../Models/Etudiant.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../Models/MouvementPoint.php';
require_once __DIR__ . '/../../Models/Bareme.php';

class SignalementController extends PersonnelController {
    private const EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'mp4'];

    public function liste(): void {
        $filtres = ['q' => $this->param('q'), 'statut' => $this->param('statut'), 'domaine' => $this->param('domaine'), 'promo' => $this->param('promo'), 'debut' => $this->param('debut'), 'fin' => $this->param('fin')];
        $mesDossiers = $this->param('miens') === '1' || !Auth::peut('signalements.consulter_tous');
        $auteur = $mesDossiers ? $this->id() : null;
        $pagination = $this->pagination(Signalement::compter($filtres, $auteur));
        $this->vue('signalements/liste', $mesDossiers ? 'Mes signalements' : 'Signalements', 'signalements', [
            'dossiers'     => Signalement::filtrer($filtres, $pagination['debut'], $pagination['limite'], $auteur),
            'filtres'      => $filtres,
            'mesDossiers'  => $mesDossiers,
            'peutTousVoir' => Auth::peut('signalements.consulter_tous'),
            'pagination'   => $pagination,
            'domaines'     => Bareme::domaines(),
            'promotions'   => Structure::promotions(),
        ]);
    }

    public function dossier(): void {
        $dossier = $this->charger();
        $auteur = (int)$dossier['ID_PERSONNE_AUTEUR'] === $this->id();
        if (!Auth::peut('signalements.consulter_tous') && !$auteur) {
            Erreur::interdit("Retour à mes signalements", 'index.php?action=admin_signalements');
        }
        $this->vue('signalements/dossier', 'Dossier de signalement', 'signalements', [
            'dossier'     => $dossier,
            'historique'  => Signalement::historique((int)$dossier['ID_SIGNALEMENT']),
            'temoins'     => Signalement::temoins((int)$dossier['ID_SIGNALEMENT']),
            'pieces'      => (Auth::peut('pieces.consulter') || $auteur) ? Signalement::pieces((int)$dossier['ID_SIGNALEMENT']) : [],
            'estAuteur'   => $auteur,
            'peutAgir'    => $this->peutInstruire($dossier),
            'jeton'       => Auth::jeton(),
        ]);
    }

    public function nouveau(): void {
        $promotions = Structure::promotions();
        $idPromo = (int)$this->param('promo', (string)($promotions[0]['ID_PROMO'] ?? 0));
        $preselection = (int)$this->param('etudiant', '0');
        if ($preselection > 0 && ($fiche = Etudiant::fiche($preselection))) {
            $idPromo = (int)$fiche['ID_PROMO'];
        }
        $saisie = ['etudiants' => $preselection > 0 ? [$preselection] : [], 'promo' => $idPromo];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            $idPromo = (int)($_POST['promo'] ?? 0);
            $saisie = [
                'promo'       => $idPromo,
                'etudiants'   => array_map('intval', (array)($_POST['etudiants'] ?? [])),
                'critere'     => (int)($_POST['critere'] ?? 0),
                'titre'       => trim($_POST['titre'] ?? ''),
                'date_faits'  => trim($_POST['date_faits'] ?? ''),
                'lieu'        => trim($_POST['lieu'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
            ];
            $erreur = $this->validerSaisie($saisie, $idPromo);
            $pieces = [];
            if ($erreur === null) {
                try {
                    foreach (Fichier::normaliserMultiple($_FILES['preuves'] ?? []) as $f) {
                        $chemin = Fichier::enregistrer($f, 'preuves', self::EXTENSIONS);
                        $pieces[] = ['nom' => $f['name'], 'chemin' => $chemin, 'type' => strtolower(pathinfo($f['name'], PATHINFO_EXTENSION))];
                    }
                    $temoins = [];
                    foreach ((array)($_POST['temoin_nom'] ?? []) as $i => $nom) {
                        $nom = trim($nom);
                        $prenom = trim($_POST['temoin_prenom'][$i] ?? '');
                        if ($nom !== '' && $prenom !== '') {
                            $temoins[] = ['nom' => $nom, 'prenom' => $prenom, 'contact' => trim($_POST['temoin_contact'][$i] ?? '') ?: null];
                        }
                    }
                    $ids = Signalement::creerPlusieurs($this->id(), $saisie['etudiants'], [
                        'critere' => $saisie['critere'], 'titre' => $saisie['titre'], 'description' => $saisie['description'],
                        'dateFaits' => str_replace('T', ' ', $saisie['date_faits']) . ':00', 'lieu' => $saisie['lieu'],
                    ], $temoins, $pieces);
                    Journal::ecrire('Signalement', count($ids) . ' dossier(s) créé(s) : ' . $saisie['titre']);
                    if (count($ids) === 1) {
                        $this->retour('admin_signalement', "Signalement transmis au chargé de discipline.", true, ['id' => $ids[0]]);
                    }
                    $this->retour('admin_signalements', count($ids) . " signalements transmis, un par étudiant concerné.");
                } catch (Exception $e) {
                    foreach ($pieces as $p) {
                        @unlink(Fichier::racine() . $p['chemin']);
                    }
                    $erreur = $e->getMessage();
                }
            }
            $_SESSION['error_message'] = $erreur;
        }

        $this->vue('signalements/nouveau', 'Nouveau signalement', 'signalement_nouveau', [
            'promotions' => $promotions,
            'etudiants'  => $idPromo > 0 ? Etudiant::camarades($idPromo) : [],
            'criteres'   => Signalement::criteresParDomaine(),
            'saisie'     => $saisie,
            'jeton'      => Auth::jeton(),
        ]);
    }

    public function ouvrir(): void {
        $dossier = $this->chargerPourInstruction();
        if ($dossier['STATUT'] !== 'SOUMIS') {
            $this->retourDossier($dossier, "L'instruction de ce dossier est déjà ouverte.", false);
        }
        Signalement::changerStatut((int)$dossier['ID_SIGNALEMENT'], 'EN_EXAMEN', $this->id(), "Instruction ouverte");
        Journal::ecrire('Signalement', 'Instruction ouverte pour le dossier ' . $dossier['ID_SIGNALEMENT']);
        $this->retourDossier($dossier, "Instruction ouverte. L'étudiant peut répondre depuis son espace.");
    }

    public function audition(): void {
        $dossier = $this->chargerPourInstruction();
        if (!in_array($dossier['STATUT'], ['SOUMIS', 'EN_EXAMEN'], true)) {
            $this->retourDossier($dossier, "Ce dossier n'attend plus d'audition.", false);
        }
        $date = trim($_POST['date_audition'] ?? '');
        $notes = trim($_POST['notes_audition'] ?? '');
        if (!DateTime::createFromFormat('Y-m-d\TH:i', $date)) {
            $this->retourDossier($dossier, "La date de l'audition est invalide.", false);
        }
        if ($notes === '') {
            $this->retourDossier($dossier, "Consignez les explications de l'étudiant.", false);
        }
        Signalement::enregistrerAudition((int)$dossier['ID_SIGNALEMENT'], str_replace('T', ' ', $date) . ':00', $notes, $this->id());
        Journal::ecrire('Signalement', 'Audition enregistrée pour le dossier ' . $dossier['ID_SIGNALEMENT']);
        $this->retourDossier($dossier, "Audition enregistrée. La décision peut être prise.");
    }

    public function decider(): void {
        $dossier = $this->chargerPourInstruction();
        $id = (int)$dossier['ID_SIGNALEMENT'];
        if ($dossier['DATE_DECISION'] !== null) {
            $this->retourDossier($dossier, "Une décision a déjà été rendue sur ce dossier.", false);
        }
        $decision = strtoupper(trim($_POST['decision'] ?? ''));
        $motif = trim($_POST['motif'] ?? '');
        $negatif = (float)$dossier['VALEUR_POINTS'] < 0;
        $conseil = $negatif && !empty($_POST['conseil']);

        if (!in_array($decision, ['VALIDE', 'REJETE', 'ANNULE'], true)) {
            $this->retourDossier($dossier, "Choisissez une décision.", false);
        }
        if ($motif === '') {
            $this->retourDossier($dossier, "Le motif de la décision est obligatoire.", false);
        }
        if ($decision === 'VALIDE' && $negatif && $dossier['STATUT'] !== 'ETUDIANT_ENTENDU') {
            $this->retourDossier($dossier, "L'étudiant doit être entendu avant tout retrait de points.", false);
        }

        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            Signalement::decider($id, $decision, $motif, $conseil, $this->id());
            if ($decision === 'VALIDE') {
                MouvementPoint::appliquer([
                    'idPersonne' => (int)$dossier['ID_PERSONNE_ETUDIANT'], 'idCritere' => (int)$dossier['ID_CRITERE'],
                    'dateMouvement' => $dossier['DATE_FAITS'], 'motif' => $dossier['TITRE_SIGNALEMENT'],
                    'idValidateur' => $this->id(), 'idSignalement' => $id,
                ]);
                if ($conseil) {
                    $critereConseil = MouvementPoint::critereParLibelle('DISCIPLINE', 'Passage au conseil de discipline');
                    if (!$critereConseil) {
                        throw new RuntimeException("Le critère « Passage au conseil de discipline » est introuvable dans le barème.");
                    }
                    MouvementPoint::appliquer([
                        'idPersonne' => (int)$dossier['ID_PERSONNE_ETUDIANT'], 'idCritere' => (int)$critereConseil['ID_CRITERE'],
                        'dateMouvement' => $dossier['DATE_FAITS'], 'motif' => 'Conseil de discipline : ' . $dossier['TITRE_SIGNALEMENT'],
                        'idValidateur' => $this->id(), 'idSignalement' => $id,
                    ]);
                }
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $this->retourDossier($dossier, $e->getMessage(), false);
        }
        Journal::ecrire('Signalement', 'Décision ' . $decision . ' sur le dossier ' . $id . ($conseil ? ' (conseil de discipline)' : ''));
        $this->retourDossier($dossier, $decision === 'VALIDE' ? "Décision enregistrée et points appliqués." : "Décision enregistrée.");
    }

    public function cloturer(): void {
        $dossier = $this->chargerPourInstruction();
        if ($dossier['DATE_DECISION'] === null) {
            $this->retourDossier($dossier, "Rendez d'abord une décision sur ce dossier.", false);
        }
        Signalement::changerStatut((int)$dossier['ID_SIGNALEMENT'], 'CLOTURE', $this->id(), "Dossier archivé");
        Journal::ecrire('Signalement', 'Dossier ' . $dossier['ID_SIGNALEMENT'] . ' clôturé');
        $this->retourDossier($dossier, "Dossier clôturé et archivé.");
    }

    public function piece(): void {
        $piece = Signalement::piece((int)$this->param('id', '0'));
        if (!$piece || (!Auth::peut('pieces.consulter') && (int)$piece['ID_PERSONNE_AUTEUR'] !== $this->id())) {
            Erreur::interdit("Retour aux signalements", 'index.php?action=admin_signalements');
        }
        Fichier::envoyer($piece['CHEMIN_FICHIER'], $piece['NOM_FICHIER']);
    }

    private function charger(): array {
        $dossier = Signalement::dossier((int)$this->param('id', '0'));
        if (!$dossier) {
            Erreur::afficher(404, "Dossier introuvable", "Aucun signalement ne correspond à cet identifiant.", "Retour aux signalements", 'index.php?action=admin_signalements');
        }
        return $dossier;
    }

    // Instruction : permission, dossier ouvert et interdiction de décider sur son propre signalement.
    private function chargerPourInstruction(): array {
        $this->exigerPost();
        Auth::exigerPermission('signalements.instruire');
        $dossier = $this->charger();
        if (!$this->peutInstruire($dossier)) {
            $this->retourDossier($dossier, "Vous ne pouvez pas instruire un dossier que vous avez vous-même signalé.", false);
        }
        return $dossier;
    }

    private function peutInstruire(array $dossier): bool {
        return Auth::peut('signalements.instruire') && (int)$dossier['ID_PERSONNE_AUTEUR'] !== $this->id();
    }

    private function retourDossier(array $dossier, string $message, bool $succes = true): never {
        $this->retour('admin_signalement', $message, $succes, ['id' => (int)$dossier['ID_SIGNALEMENT']]);
    }

    private function validerSaisie(array $s, int $idPromo): ?string {
        $idsPromo = array_map(fn($c) => (int)$c['ID_PERSONNE'], Etudiant::camarades($idPromo));
        if (empty($s['etudiants'])) {
            return "Choisissez au moins un étudiant concerné.";
        }
        foreach ($s['etudiants'] as $id) {
            if (!in_array($id, $idsPromo, true)) {
                return "Un étudiant sélectionné n'appartient pas à la promotion choisie.";
            }
            if ($id === $this->id()) {
                return "Vous ne pouvez pas vous signaler vous-même.";
            }
        }
        $idsCriteres = [];
        foreach (Signalement::criteresParDomaine() as $liste) {
            foreach ($liste as $cr) {
                $idsCriteres[] = (int)$cr['ID_CRITERE'];
            }
        }
        if (!in_array($s['critere'], $idsCriteres, true)) {
            return "Choisissez un critère.";
        }
        if ($s['titre'] === '' || $s['lieu'] === '' || $s['description'] === '' || mb_strlen($s['titre']) > 100 || mb_strlen($s['lieu']) > 255) {
            return "L'objet, le lieu et la description sont obligatoires.";
        }
        $date = DateTime::createFromFormat('Y-m-d\TH:i', $s['date_faits']);
        if (!$date || $date > new DateTime()) {
            return "La date des faits est invalide ou dans le futur.";
        }
        return null;
    }
}
