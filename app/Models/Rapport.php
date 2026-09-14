<?php
// app/Models/Rapport.php — synthèses par semestre : soldes, mentions, signalements, assiduité.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MouvementPoint.php';
require_once __DIR__ . '/Structure.php';
require_once __DIR__ . '/Parametre.php';

class Rapport {
    // Solde et mention de chaque étudiant actif sur la période, avec la promotion.
    public static function soldes(array $semestre, ?int $idPromo = null): array {
        $params = [];
        $ou = "p.STATUT_COMPTE = 'ACTIF'";
        if ($idPromo) {
            $ou .= " AND e.ID_PROMO = :promo";
            $params['promo'] = $idPromo;
        }
        $stmt = Database::getConnection()->prepare("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE, p.SEXE, pr.CODE_PROMO, pr.ID_PROMO
            FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            WHERE $ou ORDER BY pr.CODE_PROMO, p.NOM, p.PRENOM
        ");
        $stmt->execute($params);
        $soldes = MouvementPoint::soldesParEtudiant($semestre['DATE_DEBUT'], $semestre['DATE_FIN']);
        $lignes = [];
        foreach ($stmt->fetchAll() as $e) {
            $solde = $soldes[(int)$e['ID_PERSONNE']] ?? MouvementPoint::calculer([]);
            $e['PENALITES'] = $solde['penalites'];
            $e['BONUS'] = $solde['bonus'];
            $e['SOLDE'] = $solde['solde'];
            $e['MENTION'] = Structure::mention($solde['solde']);
            $lignes[] = $e;
        }
        return $lignes;
    }

    public static function synthese(array $soldes): array {
        $seuil = Parametre::nombre('SEUIL_CRITIQUE_NOTE', 10);
        $parPromo = [];
        $parMention = [];
        foreach ($soldes as $s) {
            $code = $s['CODE_PROMO'];
            $parPromo[$code] ??= ['CODE_PROMO' => $code, 'EFFECTIF' => 0, 'TOTAL' => 0.0, 'CRITIQUES' => 0, 'MIN' => null, 'MAX' => null];
            $parPromo[$code]['EFFECTIF']++;
            $parPromo[$code]['TOTAL'] += $s['SOLDE'];
            $parPromo[$code]['CRITIQUES'] += $s['SOLDE'] < $seuil ? 1 : 0;
            $parPromo[$code]['MIN'] = $parPromo[$code]['MIN'] === null ? $s['SOLDE'] : min($parPromo[$code]['MIN'], $s['SOLDE']);
            $parPromo[$code]['MAX'] = $parPromo[$code]['MAX'] === null ? $s['SOLDE'] : max($parPromo[$code]['MAX'], $s['SOLDE']);
            $parMention[$s['MENTION']] = ($parMention[$s['MENTION']] ?? 0) + 1;
        }
        foreach ($parPromo as &$p) {
            $p['MOYENNE'] = $p['EFFECTIF'] ? $p['TOTAL'] / $p['EFFECTIF'] : 0;
        }
        return ['promotions' => array_values($parPromo), 'mentions' => $parMention, 'seuil' => $seuil, 'effectif' => count($soldes),
                'moyenne' => count($soldes) ? array_sum(array_column($soldes, 'SOLDE')) / count($soldes) : 0];
    }

    public static function signalementsParDomaine(array $semestre): array {
        $stmt = Database::getConnection()->prepare("
            SELECT d.NOM_DOMAINE, d.CODE_DOMAINE,
                   COUNT(*) AS TOTAL,
                   SUM(COALESCE(h.STATUT, s.STATUT) = 'VALIDE') AS VALIDES,
                   SUM(COALESCE(h.STATUT, s.STATUT) = 'REJETE') AS REJETES,
                   SUM(s.STATUT IN ('SOUMIS', 'EN_EXAMEN', 'ETUDIANT_ENTENDU')) AS EN_COURS,
                   SUM(s.CONSEIL_DISCIPLINE = 1) AS CONSEILS
            FROM SIGNALEMENT s JOIN CRITERE c ON c.ID_CRITERE = s.ID_CRITERE JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            LEFT JOIN SIGNALEMENT_HISTORIQUE h ON h.ID_SIGNALEMENT = s.ID_SIGNALEMENT AND h.STATUT IN ('VALIDE', 'REJETE', 'ANNULE')
            WHERE s.STATUT <> 'BROUILLON' AND s.DATE_FAITS >= :debut AND s.DATE_FAITS < DATE_ADD(:fin, INTERVAL 1 DAY)
            GROUP BY d.ID_DOMAINE ORDER BY d.ID_DOMAINE
        ");
        $stmt->execute(['debut' => $semestre['DATE_DEBUT'], 'fin' => $semestre['DATE_FIN']]);
        return $stmt->fetchAll();
    }

    public static function criteresLesPlusFrequents(array $semestre, int $limite = 8): array {
        $stmt = Database::getConnection()->prepare("
            SELECT c.LIBELLE_CRITERE, d.NOM_DOMAINE, m.TYPE_MOUVEMENT, COUNT(*) AS N, SUM(m.NOMBRE_POINTS) AS POINTS
            FROM MOUVEMENT_POINTS m JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            WHERE m.ID_MOUVEMENT_CORRIGE IS NULL AND m.DATE_MOUVEMENT >= :debut AND m.DATE_MOUVEMENT < DATE_ADD(:fin, INTERVAL 1 DAY)
            GROUP BY c.ID_CRITERE, m.TYPE_MOUVEMENT ORDER BY N DESC, POINTS DESC LIMIT " . (int)$limite);
        $stmt->execute(['debut' => $semestre['DATE_DEBUT'], 'fin' => $semestre['DATE_FIN']]);
        return $stmt->fetchAll();
    }

    public static function assiduiteParPromotion(array $semestre): array {
        $stmt = Database::getConnection()->prepare("
            SELECT pr.CODE_PROMO,
                   COUNT(*) AS RELEVES,
                   SUM(p.STATUT = 'PRESENT') AS PRESENTS,
                   SUM(p.STATUT = 'RETARD') AS RETARDS,
                   SUM(p.STATUT = 'ABSENT') AS ABSENCES,
                   SUM(p.STATUT = 'ABSENT_JUSTIFIE') AS JUSTIFIEES
            FROM PRESENCE p
            JOIN APPEL a ON a.ID_APPEL = p.ID_APPEL
            JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
            JOIN ETUDIANT e ON e.ID_PERSONNE = p.ID_PERSONNE
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            WHERE s.DATE_SEANCE BETWEEN :debut AND :fin
            GROUP BY pr.ID_PROMO ORDER BY pr.CODE_PROMO
        ");
        $stmt->execute(['debut' => $semestre['DATE_DEBUT'], 'fin' => $semestre['DATE_FIN']]);
        return $stmt->fetchAll();
    }
}
