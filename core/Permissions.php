<?php
// core/Permissions.php — matrice des permissions par rôle (voir documentation/espace-personnel.md).

class Permissions {
    public const ROLES_PERSONNEL = ['ADMIN', 'RESPONSABLE_FH', 'CHARGE_DISCIPLINE', 'ENSEIGNANT', 'RESPONSABLE_CLUB', 'RESPONSABLE_ENV'];

    private const TOUS = ['ADMIN', 'RESPONSABLE_FH', 'CHARGE_DISCIPLINE', 'ENSEIGNANT', 'RESPONSABLE_CLUB', 'RESPONSABLE_ENV'];
    private const INSTRUCTION = ['ADMIN', 'RESPONSABLE_FH', 'CHARGE_DISCIPLINE'];
    private const DIRECTION = ['ADMIN', 'RESPONSABLE_FH'];

    private const MATRICE = [
        'tableau.voir'                => self::TOUS,
        'etudiants.consulter'         => self::TOUS,
        'etudiants.gerer'             => self::DIRECTION,
        'signalements.creer'          => self::TOUS,
        'signalements.consulter_tous' => self::INSTRUCTION,
        'signalements.instruire'      => self::INSTRUCTION,
        'pieces.consulter'            => self::INSTRUCTION,
        'appel.faire'                 => self::TOUS,
        'justificatifs.valider'       => self::INSTRUCTION,
        'assiduite.penaliser'         => self::INSTRUCTION,
        'points.consulter'            => self::INSTRUCTION,
        'points.corriger'             => self::DIRECTION,
        'clubs.gerer'                 => self::DIRECTION,
        'club.animer'                 => ['ADMIN', 'RESPONSABLE_FH', 'RESPONSABLE_CLUB', 'RESPONSABLE_ENV'],
        'structure.gerer'             => ['ADMIN'],
        'semestre.cloturer'           => self::DIRECTION,
        'bareme.gerer'                => ['ADMIN'],
        'comptes.gerer'               => ['ADMIN'],
        'rapports.consulter'          => self::INSTRUCTION,
        'journal.consulter'           => self::DIRECTION,
    ];

    private const LIBELLES = [
        'tableau.voir'                => 'Voir le tableau de bord',
        'etudiants.consulter'         => 'Consulter les étudiants',
        'etudiants.gerer'             => 'Gérer les étudiants (création, import, désactivation)',
        'signalements.creer'          => 'Créer un signalement',
        'signalements.consulter_tous' => 'Consulter tous les signalements',
        'signalements.instruire'      => 'Instruire et décider',
        'pieces.consulter'            => 'Consulter les pièces jointes',
        'appel.faire'                 => "Faire l'appel",
        'justificatifs.valider'       => 'Valider les justificatifs',
        'assiduite.penaliser'         => "Appliquer les pénalités d'assiduité",
        'points.consulter'            => 'Consulter le registre des points',
        'points.corriger'             => 'Corriger des points (écriture inverse)',
        'clubs.gerer'                 => 'Créer les clubs et nommer les responsables',
        'club.animer'                 => 'Animer son club (membres, séances)',
        'structure.gerer'             => 'Gérer la structure académique',
        'semestre.cloturer'           => 'Clôturer et rouvrir un semestre',
        'bareme.gerer'                => 'Gérer le barème et les paramètres',
        'comptes.gerer'               => 'Gérer les comptes du personnel',
        'rapports.consulter'          => 'Consulter les rapports',
        'journal.consulter'           => 'Consulter le journal',
    ];

    public static function possede(string $role, string $permission): bool {
        return in_array(strtoupper($role), self::MATRICE[$permission] ?? [], true);
    }

    public static function matrice(): array {
        return self::MATRICE;
    }

    public static function libelles(): array {
        return self::LIBELLES;
    }

    public static function permissionsDuRole(string $role): array {
        return array_keys(array_filter(self::MATRICE, fn($roles) => in_array($role, $roles, true)));
    }

    public static function liste(): array {
        return self::MATRICE;
    }

    public static function libelle(string $permission): string {
        return self::LIBELLES[$permission] ?? $permission;
    }

    public static function libelleRole(string $role): string {
        return match (strtoupper($role)) {
            'ADMIN'             => 'Administrateur',
            'RESPONSABLE_FH'    => 'Responsable de la Formation Humaine',
            'CHARGE_DISCIPLINE' => 'Chargé de discipline',
            'ENSEIGNANT'        => 'Enseignant',
            'RESPONSABLE_CLUB'  => 'Responsable de club',
            'RESPONSABLE_ENV'   => 'Responsable du club environnement',
            'ETUDIANT'          => 'Étudiant',
            default             => $role,
        };
    }
}
