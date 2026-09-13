<?php
// app/Controllers/Admin/PresenceController.php — appel, séances, justificatifs et assiduité.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Presence.php';
require_once __DIR__ . '/../../Models/Etudiant.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../Models/Club.php';
require_once __DIR__ . '/../../Models/MouvementPoint.php';

class PresenceController extends PersonnelController {
    private const STATUTS = ['PRESENT', 'RETARD', 'ABSENT'];

    // Un responsable de club ne fait l'appel que pour les clubs qu'il anime.
    private function limiteAuClub(): bool {
        return !Auth::peut('etudiants.gerer') && !Auth::peut('signalements.instruire') && Auth::peut('club.animer');
    }

    private function clubsDisponibles(): array {
        return $this->limiteAuClub() ? Club::animesPar($this->id()) : Club::lister();
    }

    private function promotionsDisponibles(): array {
        return $this->limiteAuClub() ? [] : Structure::promotions();
    }

    public function appel(): void {
        $clubs = $this->clubsDisponibles();
        $promotions = $this->promotionsDisponibles();
        $idClub = (int)$this->param('club', '0');
        $idPromo = (int)$this->param('promo', '0');
        $cible = $this->param('cible');
        if (str_starts_with($cible, 'promo:')) {
            [$idPromo, $idClub] = [(int)substr($cible, 6), 0];
        } elseif (str_starts_with($cible, 'club:')) {
            [$idPromo, $idClub] = [0, (int)substr($cible, 5)];
        }
        $idSeance = (int)$this->param('seance', '0');
        $saisie = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Auth::verifierJeton();
            $idClub = (int)($_POST['club'] ?? 0);
            $idPromo = (int)($_POST['promo'] ?? 0);
            $idSeance = (int)($_POST['seance'] ?? 0);
            $saisie = [
                'titre' => trim($_POST['titre'] ?? ''), 'date' => trim($_POST['date'] ?? ''),
                'debut' => trim($_POST['debut'] ?? ''), 'fin' => trim($_POST['fin'] ?? ''), 'lieu' => trim($_POST['lieu'] ?? ''),
                'statut' => [],
            ];
            $erreur = $this->enregistrerAppel($idSeance, $idClub, $idPromo, $saisie);
            if ($erreur !== null) {
                $_SESSION['error_message'] = $erreur;
            }
        }

        $seance = $idSeance > 0 ? Presence::seance($idSeance) : null;
        if ($seance) {
            $idClub = (int)($seance['ID_CLUB'] ?? 0);
            $idPromo = (int)($seance['ID_PROMO'] ?? 0);
        }
        if ($idClub === 0 && $idPromo === 0) {
            $idClub = $this->limiteAuClub() ? (int)($clubs[0]['ID_CLUB'] ?? 0) : 0;
            $idPromo = $this->limiteAuClub() ? 0 : (int)($promotions[0]['ID_PROMO'] ?? 0);
        }
        if ($idClub > 0 && $this->limiteAuClub() && !Club::estAnimePar($idClub, $this->id())) {
            Erreur::interdit("Retour au tableau de bord", 'index.php?action=admin_dashboard');
        }

        $this->vue('presences/appel', "Faire l'appel", 'appel', [
            'clubs'        => $clubs,
            'promotions'   => $promotions,
            'idClub'       => $idClub,
            'idPromo'      => $idPromo,
            'seance'       => $seance,
            'seancesPretes'=> Presence::seancesSansAppel($idPromo ?: null, $idClub ?: null),
            'participants' => ($idClub || $idPromo) ? Presence::participants($idPromo ?: null, $idClub ?: null) : [],
            'saisie'       => $saisie,
            'jeton'        => Auth::jeton(),
        ]);
    }

    private function enregistrerAppel(int $idSeance, int $idClub, int $idPromo, array &$saisie): ?string {
        if ($idClub > 0 && $this->limiteAuClub() && !Club::estAnimePar($idClub, $this->id())) {
            return "Vous ne pouvez faire l'appel que pour les clubs que vous animez.";
        }
        if ($this->limiteAuClub() && $idPromo > 0) {
            return "Vous ne pouvez faire l'appel que pour les clubs que vous animez.";
        }

        $seance = $idSeance > 0 ? Presence::seance($idSeance) : null;
        if ($seance && $seance['DATE_SEANCE'] > date('Y-m-d')) {
            return "Cette séance n'a pas encore eu lieu : l'appel se fera le jour venu.";
        }
        if (!$seance) {
            $date = DateTime::createFromFormat('Y-m-d', $saisie['date']);
            $debut = DateTime::createFromFormat('H:i', $saisie['debut']);
            $fin = DateTime::createFromFormat('H:i', $saisie['fin']);
            if ($saisie['titre'] === '' || $saisie['lieu'] === '' || mb_strlen($saisie['titre']) > 100 || mb_strlen($saisie['lieu']) > 50) {
                return "L'intitulé (100 caractères max) et le lieu (50 caractères max) sont obligatoires.";
            }
            if (!$date || !$debut || !$fin) {
                return "La date ou les heures sont invalides.";
            }
            if ($fin <= $debut) {
                return "L'heure de fin doit être après l'heure de début.";
            }
            if ($date > new DateTime('today')) {
                return "La date de la séance ne peut pas être dans le futur.";
            }
        }

        $participants = Presence::participants($idPromo ?: null, $idClub ?: null);
        if (empty($participants)) {
            return "Aucun étudiant actif pour cette cible.";
        }
        $statuts = [];
        foreach ($participants as $p) {
            $id = (int)$p['ID_PERSONNE'];
            $statut = strtoupper((string)($_POST['statut'][$id] ?? ''));
            if (!in_array($statut, self::STATUTS, true)) {
                $statut = 'PRESENT';
            }
            $statuts[$id] = $statut;
            $saisie['statut'][$id] = $statut;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();
            if (!$seance) {
                $idSeance = Presence::planifierSeance([
                    'club' => $idClub ?: null, 'promo' => $idPromo ?: null, 'titre' => $saisie['titre'],
                    'date' => $saisie['date'], 'debut' => $saisie['debut'] . ':00', 'fin' => $saisie['fin'] . ':00', 'lieu' => $saisie['lieu'],
                ]);
                $titre = $saisie['titre'];
            } else {
                $titre = $seance['TITRE_SEANCE'];
            }
            Presence::enregistrerAppelSeance($idSeance, $this->id(), $statuts);
            $db->commit();
            Journal::ecrire('Appel', 'Appel enregistré pour « ' . $titre . ' » (' . count($statuts) . ' étudiants)');
            $this->retour('admin_seances', "Appel enregistré pour « " . $titre . " ».");
        } catch (Exception $e) {
            if (Database::getConnection()->inTransaction()) {
                Database::getConnection()->rollBack();
            }
            return "L'appel n'a pas pu être enregistré : " . $e->getMessage();
        }
        return null;
    }

    public function seances(): void {
        $filtres = ['club' => $this->param('club'), 'promo' => $this->param('promo')];
        if ($this->limiteAuClub()) {
            $filtres['clubs_animes'] = array_map(fn($c) => (int)$c['ID_CLUB'], Club::animesPar($this->id())) ?: [0];
        }
        $pagination = $this->pagination(Presence::compterSeances($filtres));
        $this->vue('presences/seances', 'Séances', 'seances', [
            'seances'    => Presence::seances($filtres, $pagination['debut'], $pagination['limite']),
            'filtres'    => $filtres,
            'pagination' => $pagination,
            'clubs'      => $this->clubsDisponibles(),
            'promotions' => $this->promotionsDisponibles(),
            'jeton'      => Auth::jeton(),
        ]);
    }

    public function planifier(): void {
        $this->exigerPost();
        $cible = (string)($_POST['cible'] ?? '');
        $idPromo = str_starts_with($cible, 'promo:') ? (int)substr($cible, 6) : 0;
        $idClub = str_starts_with($cible, 'club:') ? (int)substr($cible, 5) : 0;
        if ($idClub === 0 && $idPromo === 0) {
            $this->retour('admin_seances', "Choisissez une promotion ou un club.", false);
        }
        if ($idPromo > 0 && $this->limiteAuClub()) {
            $this->retour('admin_seances', "Vous ne pouvez planifier que pour les clubs que vous animez.", false);
        }
        if ($idClub > 0 && $this->limiteAuClub() && !Club::estAnimePar($idClub, $this->id())) {
            $this->retour('admin_seances', "Vous ne pouvez planifier que pour les clubs que vous animez.", false);
        }
        $titre = trim($_POST['titre'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $debut = trim($_POST['debut'] ?? '');
        $fin = trim($_POST['fin'] ?? '');
        $lieu = trim($_POST['lieu'] ?? '');
        if ($titre === '' || $lieu === '' || !DateTime::createFromFormat('Y-m-d', $date) || !DateTime::createFromFormat('H:i', $debut) || !DateTime::createFromFormat('H:i', $fin)) {
            $this->retour('admin_seances', "Renseignez l'intitulé, le lieu, la date et les heures.", false);
        }
        if ($fin <= $debut) {
            $this->retour('admin_seances', "L'heure de fin doit être après l'heure de début.", false);
        }
        Presence::planifierSeance(['club' => $idClub ?: null, 'promo' => $idPromo ?: null, 'titre' => $titre, 'date' => $date,
                                   'debut' => $debut . ':00', 'fin' => $fin . ':00', 'lieu' => $lieu]);
        Journal::ecrire('Séance', 'Séance planifiée : ' . $titre);
        $this->retour('admin_seances', "Séance planifiée. L'appel pourra être fait le moment venu.");
    }

    public function justificatifs(): void {
        $pagination = $this->pagination(Presence::compterJustificatifsEnAttente());
        $this->vue('presences/justificatifs', "Justificatifs d'absence", 'justificatifs', [
            'justificatifs' => Presence::justificatifsEnAttente($pagination['debut'], $pagination['limite']),
            'pagination'    => $pagination,
            'jeton'         => Auth::jeton(),
        ]);
    }

    public function fichier(): void {
        $justificatif = Presence::justificatifPersonnel((int)$this->param('id', '0'));
        if (!$justificatif || !$justificatif['CHEMIN_FICHIER']) {
            Erreur::interdit("Retour aux justificatifs", 'index.php?action=admin_justificatifs');
        }
        $extension = pathinfo($justificatif['CHEMIN_FICHIER'], PATHINFO_EXTENSION);
        Fichier::envoyer($justificatif['CHEMIN_FICHIER'], 'justificatif-' . $justificatif['DATE_SEANCE'] . '.' . $extension);
    }

    public function deciderJustificatif(): void {
        $this->exigerPost();
        $id = (int)($_POST['id'] ?? 0);
        $justificatif = Presence::justificatifPersonnel($id);
        if (!$justificatif || $justificatif['STATUT_VALIDATION'] !== 'EN_ATTENTE') {
            $this->retour('admin_justificatifs', "Ce justificatif a déjà été traité.", false);
        }
        $valide = ($_POST['decision'] ?? '') === 'valider';
        $commentaire = trim($_POST['commentaire'] ?? '');
        if (!$valide && $commentaire === '') {
            $this->retour('admin_justificatifs', "Indiquez à l'étudiant pourquoi le justificatif est rejeté.", false);
        }
        Presence::deciderJustificatif($id, $valide, $this->id(), $commentaire);
        Journal::ecrire('Justificatif', ($valide ? 'Validé' : 'Rejeté') . ' pour la personne ' . $justificatif['ID_PERSONNE']);
        $this->retour('admin_justificatifs', $valide ? "Justificatif validé : l'absence est désormais justifiée." : "Justificatif rejeté. L'étudiant en est informé dans son espace.");
    }

    public function assiduite(): void {
        $filtres = ['promo' => $this->param('promo')];
        $this->vue('presences/assiduite', 'Assiduité à pénaliser', 'assiduite', [
            'lignes'     => Presence::assiduiteAPenaliser($filtres),
            'filtres'    => $filtres,
            'promotions' => Structure::promotions(),
            'delai'      => Parametre::nombre('DELAI_DEPOT_JUSTIFICATIF_HEURES', 24),
            'jeton'      => Auth::jeton(),
        ]);
    }

    public function penaliser(): void {
        $this->exigerPost();
        $ids = array_map('intval', (array)($_POST['presences'] ?? []));
        if (empty($ids)) {
            $this->retour('admin_assiduite', "Sélectionnez au moins une ligne à pénaliser.", false);
        }
        $appliquees = 0;
        $refus = [];
        foreach ($ids as $idPresence) {
            $ligne = Presence::presencePourPenalite($idPresence);
            if (!$ligne) {
                continue;
            }
            $critere = Presence::criterePourAssiduite($ligne['STATUT'], !empty($ligne['ID_CLUB']));
            if (!$critere) {
                $refus[] = "critère d'assiduité introuvable dans le barème";
                continue;
            }
            try {
                MouvementPoint::appliquer([
                    'idPersonne' => (int)$ligne['ID_PERSONNE'], 'idCritere' => (int)$critere['ID_CRITERE'],
                    'dateMouvement' => $ligne['DATE_SEANCE'] . ' ' . $ligne['HEURE_DEBUT'],
                    'motif' => ($ligne['STATUT'] === 'RETARD' ? 'Retard' : 'Absence non justifiée') . ' : ' . $ligne['TITRE_SEANCE'],
                    'idValidateur' => $this->id(), 'idPresence' => $idPresence,
                ]);
                $appliquees++;
            } catch (Exception $e) {
                $refus[] = $ligne['NOM'] . ' : ' . $e->getMessage();
            }
        }
        if ($appliquees > 0) {
            Journal::ecrire('Assiduité', $appliquees . ' pénalité(s) appliquée(s)');
        }
        if ($refus) {
            $this->retour('admin_assiduite', $appliquees . " pénalité(s) appliquée(s). Non appliquées : " . implode(' ; ', array_slice($refus, 0, 3)) . ".", $appliquees > 0);
        }
        $this->retour('admin_assiduite', $appliquees . " pénalité(s) appliquée(s) au registre des points.");
    }
}
