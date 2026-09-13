<?php
// core/Routeur.php — routes de l'espace personnel (actions admin_*).
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Erreur.php';

class Routeur {
    // action => [contrôleur (app/Controllers/Admin), méthode, permission requise ou null]
    private const ROUTES = [
        'admin_dashboard' => ['TableauDeBordController', 'index', 'tableau.voir'],
        'admin_etudiants'                => ['EtudiantController', 'liste', 'etudiants.consulter'],
        'admin_etudiants_export'         => ['EtudiantController', 'exporter', 'etudiants.consulter'],
        'admin_etudiant'                 => ['EtudiantController', 'fiche', 'etudiants.consulter'],
        'admin_etudiant_nouveau'         => ['EtudiantController', 'nouveau', 'etudiants.gerer'],
        'admin_etudiant_modifier'        => ['EtudiantController', 'modifier', 'etudiants.gerer'],
        'admin_etudiant_statut'          => ['EtudiantController', 'statut', 'etudiants.gerer'],
        'admin_etudiant_reinitialiser'   => ['EtudiantController', 'reinitialiser', 'etudiants.gerer'],
        'admin_etudiant_supprimer'       => ['EtudiantController', 'supprimer', 'etudiants.gerer'],
        'admin_etudiants_import'         => ['EtudiantController', 'importer', 'etudiants.gerer'],
        'admin_etudiants_import_resultat'=> ['EtudiantController', 'importResultat', 'etudiants.gerer'],
        'admin_signalements'             => ['SignalementController', 'liste', 'signalements.creer'],
        'admin_signalement'              => ['SignalementController', 'dossier', 'signalements.creer'],
        'admin_signalement_nouveau'      => ['SignalementController', 'nouveau', 'signalements.creer'],
        'admin_signalement_ouvrir'       => ['SignalementController', 'ouvrir', 'signalements.instruire'],
        'admin_signalement_audition'     => ['SignalementController', 'audition', 'signalements.instruire'],
        'admin_signalement_decider'      => ['SignalementController', 'decider', 'signalements.instruire'],
        'admin_signalement_cloturer'     => ['SignalementController', 'cloturer', 'signalements.instruire'],
        'admin_piece'                    => ['SignalementController', 'piece', 'signalements.creer'],
        'admin_appel'                    => ['PresenceController', 'appel', 'appel.faire'],
        'admin_seances'                  => ['PresenceController', 'seances', 'appel.faire'],
        'admin_seance_planifier'         => ['PresenceController', 'planifier', 'appel.faire'],
        'admin_justificatifs'            => ['PresenceController', 'justificatifs', 'justificatifs.valider'],
        'admin_justificatif_fichier'     => ['PresenceController', 'fichier', 'justificatifs.valider'],
        'admin_justificatif_decider'     => ['PresenceController', 'deciderJustificatif', 'justificatifs.valider'],
        'admin_assiduite'                => ['PresenceController', 'assiduite', 'assiduite.penaliser'],
        'admin_assiduite_penaliser'      => ['PresenceController', 'penaliser', 'assiduite.penaliser'],
        'admin_points'                   => ['PointController', 'registre', 'points.consulter'],
        'admin_points_export'            => ['PointController', 'exporter', 'points.consulter'],
        'admin_point_corriger'           => ['PointController', 'corriger', 'points.corriger'],
        'admin_structure'                => ['StructureController', 'index', 'structure.gerer'],
        'admin_semestre_cloturer'        => ['StructureController', 'cloturer', 'semestre.cloturer'],
        'admin_semestre_rouvrir'         => ['StructureController', 'rouvrir', 'semestre.cloturer'],
        'admin_clubs'                    => ['ClubController', 'liste', 'club.animer'],
        'admin_club'                     => ['ClubController', 'club', 'club.animer'],
        'admin_club_enregistrer'         => ['ClubController', 'enregistrer', 'clubs.gerer'],
        'admin_club_membre'              => ['ClubController', 'membre', 'club.animer'],
        'admin_bareme'                   => ['BaremeController', 'index', 'bareme.gerer'],
        'admin_bareme_critere'           => ['BaremeController', 'critere', 'bareme.gerer'],
        'admin_bareme_domaine'           => ['BaremeController', 'domaine', 'bareme.gerer'],
        'admin_bareme_parametres'        => ['BaremeController', 'parametres', 'bareme.gerer'],
        'admin_comptes'                  => ['CompteController', 'liste', 'comptes.gerer'],
        'admin_compte_enregistrer'       => ['CompteController', 'enregistrer', 'comptes.gerer'],
        'admin_compte_statut'            => ['CompteController', 'statut', 'comptes.gerer'],
        'admin_compte_reinitialiser'     => ['CompteController', 'reinitialiser', 'comptes.gerer'],
        'admin_mon_compte'               => ['CompteController', 'monCompte', 'tableau.voir'],
        'admin_mon_compte_coordonnees'   => ['CompteController', 'coordonnees', 'tableau.voir'],
        'admin_mon_compte_mot_de_passe'  => ['CompteController', 'motDePasse', 'tableau.voir'],
        'admin_mon_compte_photo'         => ['CompteController', 'photo', 'tableau.voir'],
        'admin_rapports'                 => ['RapportController', 'index', 'rapports.consulter'],
        'admin_rapports_export'          => ['RapportController', 'exporter', 'rapports.consulter'],
        'admin_journal'                  => ['JournalController', 'index', 'journal.consulter'],
    ];

    // Anciennes actions conservées pour ne casser aucun lien.
    private const ALIAS = [
        'dashboard'         => 'admin_dashboard',
        'etudiants'         => 'admin_etudiants',
        'voir_etudiant'     => 'admin_etudiant',
        'ajouter_etudiant'  => 'admin_etudiant_nouveau',
        'modifier_etudiant' => 'admin_etudiant_modifier',
    ];

    public static function estConnue(string $action): bool {
        return isset(self::ROUTES[$action]) || isset(self::ALIAS[$action]);
    }

    public static function traiter(string $action): void {
        if (isset(self::ALIAS[$action])) {
            $params = $_GET;
            unset($params['action'], $params['route']);
            Auth::rediriger(self::ALIAS[$action], $params);
        }
        if (!isset(self::ROUTES[$action])) {
            Erreur::introuvable();
        }
        [$classe, $methode, $permission] = self::ROUTES[$action];
        Auth::exigerPersonnel();
        if ($permission !== null) {
            Auth::exigerPermission($permission);
        }
        require_once __DIR__ . '/../app/Controllers/Admin/' . $classe . '.php';
        $controleur = new $classe();
        try {
            $controleur->$methode();
        } catch (PDOException $e) {
            error_log('Espace personnel : ' . $e->getMessage());
            Erreur::technique();
        }
    }
}
