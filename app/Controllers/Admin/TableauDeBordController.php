<?php
// app/Controllers/Admin/TableauDeBordController.php — tableau de bord selon le rôle.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Signalement.php';
require_once __DIR__ . '/../../Models/Presence.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../Models/Rapport.php';
require_once __DIR__ . '/../../Models/Club.php';

class TableauDeBordController extends PersonnelController {
    public function index(): void {
        $aTraiter = [];
        $chiffres = [];
        $semestre = Structure::semestrePourDate(date('Y-m-d'));

        if (Auth::peut('signalements.instruire')) {
            $statuts = Signalement::compterParStatut();
            $ouverts = ($statuts['SOUMIS'] ?? 0) + ($statuts['EN_EXAMEN'] ?? 0) + ($statuts['ETUDIANT_ENTENDU'] ?? 0);
            $this->tache($aTraiter, $statuts['SOUMIS'] ?? 0, 'drapeau', 'signalement à examiner', 'signalements à examiner', 'admin_signalements&statut=SOUMIS');
            $this->tache($aTraiter, ($statuts['EN_EXAMEN'] ?? 0) + ($statuts['ETUDIANT_ENTENDU'] ?? 0), 'marteau', 'dossier en attente de décision', 'dossiers en attente de décision', 'admin_signalements&statut=EN_EXAMEN');
            $chiffres[] = ['libelle' => 'Dossiers ouverts', 'valeur' => $ouverts, 'lien' => 'admin_signalements'];
        }
        if (Auth::peut('justificatifs.valider')) {
            $this->tache($aTraiter, Presence::compterJustificatifsEnAttente(), 'document', 'justificatif à examiner', 'justificatifs à examiner', 'admin_justificatifs');
        }
        if (Auth::peut('assiduite.penaliser')) {
            $this->tache($aTraiter, count(Presence::assiduiteAPenaliser()), 'horloge', 'absence ou retard à pénaliser', 'absences ou retards à pénaliser', 'admin_assiduite');
        }
        if (Auth::peut('appel.faire')) {
            $clubs = Auth::peut('clubs.gerer') ? null : array_map(fn($c) => (int)$c['ID_CLUB'], Club::animesPar($this->id()));
            $seances = array_filter(Presence::seancesSansAppel(), fn($s) => $s['DATE_SEANCE'] <= date('Y-m-d') && ($clubs === null || in_array((int)($s['ID_CLUB'] ?? 0), $clubs, true)));
            $this->tache($aTraiter, count($seances), 'appel', 'séance passée sans appel', 'séances passées sans appel', 'admin_seances');
        }
        if (Auth::peut('semestre.cloturer')) {
            $this->tache($aTraiter, count(Structure::semestresEchusNonClos()), 'diplome', 'semestre échu à clôturer', 'semestres échus à clôturer', 'admin_structure&entite=semestre');
        }
        if (!Auth::peut('signalements.consulter_tous')) {
            $miens = Signalement::compter([], $this->id());
            $chiffres[] = ['libelle' => 'Mes signalements', 'valeur' => $miens, 'lien' => 'admin_signalements'];
        }

        $synthese = null;
        if ($semestre && Auth::peut('rapports.consulter')) {
            $synthese = Rapport::synthese(Rapport::soldes($semestre));
            $chiffres[] = ['libelle' => 'Étudiants actifs', 'valeur' => $synthese['effectif'], 'lien' => 'admin_etudiants'];
            $chiffres[] = ['libelle' => 'Note moyenne', 'valeur' => Format::points($synthese['moyenne']) . ' / 20', 'lien' => 'admin_rapports'];
            $chiffres[] = ['libelle' => 'Sous le seuil de ' . Format::points($synthese['seuil']), 'valeur' => array_sum(array_column($synthese['promotions'], 'CRITIQUES')), 'lien' => 'admin_rapports', 'alerte' => true];
        } elseif (Auth::peut('etudiants.consulter')) {
            $chiffres[] = ['libelle' => 'Étudiants actifs', 'valeur' => Etudiant::compter(['statut' => 'ACTIF']), 'lien' => 'admin_etudiants'];
        }

        $this->vue('tableau_de_bord/index', 'Tableau de bord', 'dashboard', [
            'aTraiter'  => $aTraiter,
            'chiffres'  => $chiffres,
            'semestre'  => $semestre,
            'synthese'  => $synthese,
            'recents'   => Auth::peut('signalements.consulter_tous') ? Signalement::filtrer([], 0, 6) : Signalement::filtrer([], 0, 6, $this->id()),
            'journal'   => Auth::peut('journal.consulter') ? Journal::recents(6) : [],
            'mesClubs'  => Club::animesPar($this->id()),
        ]);
    }

    private function tache(array &$liste, int $n, string $icone, string $singulier, string $pluriel, string $action): void {
        if ($n > 0) {
            $liste[] = ['n' => $n, 'icone' => $icone, 'texte' => $n . ' ' . ($n > 1 ? $pluriel : $singulier), 'action' => $action];
        }
    }
}
