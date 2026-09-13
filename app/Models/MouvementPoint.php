<?php
// app/Models/MouvementPoint.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Parametre.php';

class MouvementPoint {
    private const PLAFONDS = [
        'ECOLOGIE'    => 'PLAFOND_BONUS_ECOLOGIE',
        'CITOYENNETE' => 'PLAFOND_BONUS_CITOYENNETE',
        'CLUB'        => 'PLAFOND_BONUS_CLUB',
    ];

    public static function liste(int $idPersonne, string $debut, string $fin): array {
        $stmt = Database::getConnection()->prepare("
            SELECT m.ID_MOUVEMENT, m.DATE_MOUVEMENT, m.NOMBRE_POINTS, m.TYPE_MOUVEMENT, m.MOTIF_MOUVEMENT, m.ID_SIGNALEMENT,
                   d.CODE_DOMAINE, d.NOM_DOMAINE, c.LIBELLE_CRITERE,
                   v.NOM AS VALIDATEUR_NOM, v.PRENOM AS VALIDATEUR_PRENOM
            FROM MOUVEMENT_POINTS m
            JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE
            JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            LEFT JOIN PERSONNE v ON v.ID_PERSONNE = m.ID_VALIDATEUR
            WHERE m.ID_PERSONNE = :id
              AND m.DATE_MOUVEMENT >= :debut AND m.DATE_MOUVEMENT < DATE_ADD(:fin, INTERVAL 1 DAY)
            ORDER BY m.DATE_MOUVEMENT DESC, m.ID_MOUVEMENT DESC
        ");
        $stmt->execute(['id' => $idPersonne, 'debut' => $debut, 'fin' => $fin]);
        return $stmt->fetchAll();
    }

    public static function totauxParDomaine(int $idPersonne, string $debut, string $fin): array {
        $stmt = Database::getConnection()->prepare("
            SELECT d.CODE_DOMAINE, d.NOM_DOMAINE,
                   COALESCE(SUM(CASE WHEN m.TYPE_MOUVEMENT = 'POSITIF' THEN m.NOMBRE_POINTS END), 0) AS POSITIF,
                   COALESCE(SUM(CASE WHEN m.TYPE_MOUVEMENT = 'NEGATIF' THEN m.NOMBRE_POINTS END), 0) AS NEGATIF
            FROM DOMAINE d
            LEFT JOIN CRITERE c ON c.ID_DOMAINE = d.ID_DOMAINE
            LEFT JOIN MOUVEMENT_POINTS m ON m.ID_CRITERE = c.ID_CRITERE
                 AND m.ID_PERSONNE = :id
                 AND m.DATE_MOUVEMENT >= :debut AND m.DATE_MOUVEMENT < DATE_ADD(:fin, INTERVAL 1 DAY)
            GROUP BY d.ID_DOMAINE, d.CODE_DOMAINE, d.NOM_DOMAINE
            ORDER BY d.ID_DOMAINE
        ");
        $stmt->execute(['id' => $idPersonne, 'debut' => $debut, 'fin' => $fin]);
        return $stmt->fetchAll();
    }

    public static function solde(int $idPersonne, string $debut, string $fin): array {
        $capital = Parametre::nombre('CAPITAL_INITIAL_NOTE', 20);
        $minimum = Parametre::nombre('NOTE_MINIMALE_POSSIBLE', 0);
        $maximum = Parametre::nombre('NOTE_MAXIMALE_POSSIBLE', 20);

        $domaines = [];
        $penalites = 0.0;
        $bonus = 0.0;
        foreach (self::totauxParDomaine($idPersonne, $debut, $fin) as $d) {
            $plafond = isset(self::PLAFONDS[$d['CODE_DOMAINE']]) ? Parametre::nombre(self::PLAFONDS[$d['CODE_DOMAINE']], 0) : null;
            $positif = (float)$d['POSITIF'];
            $d['POSITIF'] = $positif;
            $d['NEGATIF'] = (float)$d['NEGATIF'];
            $d['PLAFOND'] = $plafond;
            $d['BONUS_RETENU'] = $plafond === null ? $positif : min($positif, $plafond);
            $penalites += $d['NEGATIF'];
            $bonus += $d['BONUS_RETENU'];
            $domaines[] = $d;
        }
        $brut = $capital - $penalites + $bonus;

        return [
            'capital'   => $capital,
            'penalites' => $penalites,
            'bonus'     => $bonus,
            'brut'      => $brut,
            'solde'     => max($minimum, min($maximum, $brut)),
            'maximum'   => $maximum,
            'domaines'  => $domaines,
        ];
    }
}
