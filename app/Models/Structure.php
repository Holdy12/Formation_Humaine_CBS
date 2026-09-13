<?php
// app/Models/Structure.php — années, semestres, départements, filières, niveaux, promotions, clôture.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Parametre.php';

class Structure {
    private const ENTITES = [
        'annee'       => ['table' => 'ANNEE_ACADEMIQUE', 'cle' => 'ID_ANNEE',   'colonnes' => ['LIBELLE_ANNEE', 'DATE_DEBUT', 'DATE_FIN'], 'tri' => 'DATE_DEBUT DESC'],
        'semestre'    => ['table' => 'SEMESTRE',         'cle' => 'ID_SEMESTRE', 'colonnes' => ['ID_ANNEE', 'CODE_SEMESTRE', 'LIBELLE_SEMESTRE', 'DATE_DEBUT', 'DATE_FIN'], 'tri' => 'DATE_DEBUT DESC'],
        'departement' => ['table' => 'DEPARTEMENT',      'cle' => 'ID_DEPT',     'colonnes' => ['CODE_DEPT', 'NOM_DEPT'], 'tri' => 'NOM_DEPT'],
        'filiere'     => ['table' => 'FILIERE',          'cle' => 'ID_FILIERE',  'colonnes' => ['ID_DEPT', 'CODE_FILIERE', 'NOM_FILIERE'], 'tri' => 'NOM_FILIERE'],
        'niveau'      => ['table' => 'NIVEAU',           'cle' => 'ID_NIVEAU',   'colonnes' => ['CODE_NIVEAU', 'LIBELLE_NIVEAU'], 'tri' => 'ID_NIVEAU'],
        'promotion'   => ['table' => 'PROMOTION',        'cle' => 'ID_PROMO',    'colonnes' => ['ID_ANNEE', 'ID_NIVEAU', 'ID_FILIERE', 'CODE_PROMO'], 'tri' => 'CODE_PROMO'],
    ];

    public static function promotions(): array {
        return Database::getConnection()->query("
            SELECT pr.ID_PROMO, pr.CODE_PROMO, pr.ID_ANNEE, pr.ID_NIVEAU, pr.ID_FILIERE,
                   n.LIBELLE_NIVEAU, f.NOM_FILIERE, a.LIBELLE_ANNEE,
                   (SELECT COUNT(*) FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE WHERE e.ID_PROMO = pr.ID_PROMO AND p.STATUT_COMPTE = 'ACTIF') AS EFFECTIF
            FROM PROMOTION pr
            JOIN NIVEAU n ON n.ID_NIVEAU = pr.ID_NIVEAU
            JOIN FILIERE f ON f.ID_FILIERE = pr.ID_FILIERE
            JOIN ANNEE_ACADEMIQUE a ON a.ID_ANNEE = pr.ID_ANNEE
            ORDER BY a.DATE_DEBUT DESC, pr.CODE_PROMO
        ")->fetchAll();
    }

    public static function promotion(int $id): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT pr.*, n.LIBELLE_NIVEAU, f.NOM_FILIERE, a.LIBELLE_ANNEE
            FROM PROMOTION pr JOIN NIVEAU n ON n.ID_NIVEAU = pr.ID_NIVEAU JOIN FILIERE f ON f.ID_FILIERE = pr.ID_FILIERE JOIN ANNEE_ACADEMIQUE a ON a.ID_ANNEE = pr.ID_ANNEE
            WHERE pr.ID_PROMO = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function promotionParCode(string $code): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM PROMOTION WHERE CODE_PROMO = :code");
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public static function lister(string $entite): array {
        $e = self::ENTITES[$entite];
        $sql = "SELECT * FROM {$e['table']} ORDER BY {$e['tri']}";
        if ($entite === 'semestre') {
            $sql = "SELECT s.*, a.LIBELLE_ANNEE FROM SEMESTRE s JOIN ANNEE_ACADEMIQUE a ON a.ID_ANNEE = s.ID_ANNEE ORDER BY s.DATE_DEBUT DESC";
        } elseif ($entite === 'filiere') {
            $sql = "SELECT f.*, d.NOM_DEPT FROM FILIERE f JOIN DEPARTEMENT d ON d.ID_DEPT = f.ID_DEPT ORDER BY f.NOM_FILIERE";
        }
        return Database::getConnection()->query($sql)->fetchAll();
    }

    public static function trouver(string $entite, int $id): ?array {
        $e = self::ENTITES[$entite];
        $stmt = Database::getConnection()->prepare("SELECT * FROM {$e['table']} WHERE {$e['cle']} = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function creer(string $entite, array $valeurs): int {
        $e = self::ENTITES[$entite];
        $colonnes = array_values(array_intersect($e['colonnes'], array_keys($valeurs)));
        $sql = "INSERT INTO {$e['table']} (" . implode(', ', $colonnes) . ") VALUES (" . implode(', ', array_map(fn($c) => ':' . $c, $colonnes)) . ")";
        $db = Database::getConnection();
        $stmt = $db->prepare($sql);
        $stmt->execute(array_intersect_key($valeurs, array_flip($colonnes)));
        return (int)$db->lastInsertId();
    }

    public static function modifier(string $entite, int $id, array $valeurs): void {
        $e = self::ENTITES[$entite];
        $colonnes = array_values(array_intersect($e['colonnes'], array_keys($valeurs)));
        $sql = "UPDATE {$e['table']} SET " . implode(', ', array_map(fn($c) => "$c = :$c", $colonnes)) . " WHERE {$e['cle']} = :id";
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute(array_intersect_key($valeurs, array_flip($colonnes)) + ['id' => $id]);
    }

    // Refuse la suppression s'il existe des données liées (contrainte de clé étrangère).
    public static function supprimer(string $entite, int $id): void {
        $e = self::ENTITES[$entite];
        try {
            $stmt = Database::getConnection()->prepare("DELETE FROM {$e['table']} WHERE {$e['cle']} = :id");
            $stmt->execute(['id' => $id]);
        } catch (PDOException $ex) {
            if ((int)$ex->errorInfo[1] === 1451) {
                throw new RuntimeException("Suppression impossible : des données y sont rattachées.");
            }
            throw $ex;
        }
    }

    public static function semestrePourDate(string $date): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT s.*, a.LIBELLE_ANNEE FROM SEMESTRE s JOIN ANNEE_ACADEMIQUE a ON a.ID_ANNEE = s.ID_ANNEE
            WHERE :date BETWEEN s.DATE_DEBUT AND s.DATE_FIN ORDER BY s.DATE_DEBUT DESC LIMIT 1
        ");
        $stmt->execute(['date' => substr($date, 0, 10)]);
        return $stmt->fetch() ?: null;
    }

    // Un semestre est clos dès qu'un résultat clôturé existe pour lui.
    public static function semestreEstClos(int $idSemestre): bool {
        $stmt = Database::getConnection()->prepare("SELECT 1 FROM RESULTAT_SEMESTRIEL WHERE ID_SEMESTRE = :id AND STATUT_VALIDATION = 'CLOTURE' LIMIT 1");
        $stmt->execute(['id' => $idSemestre]);
        return (bool)$stmt->fetchColumn();
    }

    public static function semestresEchusNonClos(): array {
        return array_values(array_filter(self::lister('semestre'), fn($s) => $s['DATE_FIN'] < date('Y-m-d') && !self::semestreEstClos((int)$s['ID_SEMESTRE'])));
    }

    public static function mention(float $note): string {
        if ($note >= Parametre::nombre('MENTION_TRES_BIEN', 16)) return 'Très bien';
        if ($note >= Parametre::nombre('MENTION_BIEN', 14)) return 'Bien';
        if ($note >= Parametre::nombre('MENTION_ASSEZ_BIEN', 12)) return 'Assez bien';
        if ($note >= Parametre::nombre('MENTION_PASSABLE', 10)) return 'Passable';
        return 'Insuffisant';
    }
}
