<?php
// app/Models/MouvementPoint.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Parametre.php';
require_once __DIR__ . '/Structure.php';
require_once __DIR__ . '/../../core/Journal.php';

class MouvementPoint {
    private const PLAFONDS = [
        'ECOLOGIE'    => 'PLAFOND_BONUS_ECOLOGIE',
        'CITOYENNETE' => 'PLAFOND_BONUS_CITOYENNETE',
        'CLUB'        => 'PLAFOND_BONUS_CLUB',
    ];

    // Un mouvement corrigé et son écriture inverse s'annulent : aucun des deux n'entre dans les totaux ni dans les plafonds.
    private const HORS_CORRECTIONS = "m.ID_MOUVEMENT_CORRIGE IS NULL AND NOT EXISTS (SELECT 1 FROM MOUVEMENT_POINTS x WHERE x.ID_MOUVEMENT_CORRIGE = m.ID_MOUVEMENT)";

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
                 AND " . self::HORS_CORRECTIONS . "
            GROUP BY d.ID_DOMAINE, d.CODE_DOMAINE, d.NOM_DOMAINE
            ORDER BY d.ID_DOMAINE
        ");
        $stmt->execute(['id' => $idPersonne, 'debut' => $debut, 'fin' => $fin]);
        return $stmt->fetchAll();
    }

    public static function solde(int $idPersonne, string $debut, string $fin): array {
        return self::calculer(self::totauxParDomaine($idPersonne, $debut, $fin));
    }

    // Soldes de tous les étudiants ayant des mouvements sur la période, en une requête, indexés par ID_PERSONNE.
    // Un étudiant absent du tableau est au capital initial : utiliser calculer([]).
    public static function soldesParEtudiant(string $debut, string $fin): array {
        $stmt = Database::getConnection()->prepare("
            SELECT m.ID_PERSONNE, d.CODE_DOMAINE, d.NOM_DOMAINE,
                   COALESCE(SUM(CASE WHEN m.TYPE_MOUVEMENT = 'POSITIF' THEN m.NOMBRE_POINTS END), 0) AS POSITIF,
                   COALESCE(SUM(CASE WHEN m.TYPE_MOUVEMENT = 'NEGATIF' THEN m.NOMBRE_POINTS END), 0) AS NEGATIF
            FROM MOUVEMENT_POINTS m
            JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE
            JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            WHERE m.DATE_MOUVEMENT >= :debut AND m.DATE_MOUVEMENT < DATE_ADD(:fin, INTERVAL 1 DAY)
              AND " . self::HORS_CORRECTIONS . "
            GROUP BY m.ID_PERSONNE, d.ID_DOMAINE, d.CODE_DOMAINE, d.NOM_DOMAINE
            ORDER BY m.ID_PERSONNE, d.ID_DOMAINE
        ");
        $stmt->execute(['debut' => $debut, 'fin' => $fin]);
        $totaux = [];
        foreach ($stmt->fetchAll() as $ligne) {
            $totaux[(int)$ligne['ID_PERSONNE']][] = $ligne;
        }
        return array_map([self::class, 'calculer'], $totaux);
    }

    // Applique les plafonds par domaine et les bornes de la note à des totaux (CODE_DOMAINE, NOM_DOMAINE, POSITIF, NEGATIF).
    public static function calculer(array $totaux): array {
        $capital = Parametre::nombre('CAPITAL_INITIAL_NOTE', 20);
        $minimum = Parametre::nombre('NOTE_MINIMALE_POSSIBLE', 0);
        $maximum = Parametre::nombre('NOTE_MAXIMALE_POSSIBLE', 20);

        $domaines = [];
        $penalites = 0.0;
        $bonus = 0.0;
        foreach ($totaux as $d) {
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

    public static function critere(int $idCritere): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT c.*, d.CODE_DOMAINE, d.NOM_DOMAINE FROM CRITERE c JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE WHERE c.ID_CRITERE = :id
        ");
        $stmt->execute(['id' => $idCritere]);
        return $stmt->fetch() ?: null;
    }

    public static function critereParLibelle(string $codeDomaine, string $libelle): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT c.*, d.CODE_DOMAINE, d.NOM_DOMAINE FROM CRITERE c JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            WHERE d.CODE_DOMAINE = :domaine AND c.LIBELLE_CRITERE = :libelle AND c.ACTIF = 1 LIMIT 1
        ");
        $stmt->execute(['domaine' => $codeDomaine, 'libelle' => $libelle]);
        return $stmt->fetch() ?: null;
    }

    public static function plafondDomaine(string $codeDomaine): ?float {
        return isset(self::PLAFONDS[$codeDomaine]) ? Parametre::nombre(self::PLAFONDS[$codeDomaine], 0) : null;
    }

    // Bonification encore disponible dans ce domaine sur la période ; null si le domaine n'a pas de plafond.
    public static function bonusRestant(int $idPersonne, string $codeDomaine, string $debut, string $fin): ?float {
        $plafond = self::plafondDomaine($codeDomaine);
        if ($plafond === null) {
            return null;
        }
        $stmt = Database::getConnection()->prepare("
            SELECT COALESCE(SUM(m.NOMBRE_POINTS), 0) FROM MOUVEMENT_POINTS m
            JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            WHERE m.ID_PERSONNE = :id AND d.CODE_DOMAINE = :domaine AND m.TYPE_MOUVEMENT = 'POSITIF'
              AND m.DATE_MOUVEMENT >= :debut AND m.DATE_MOUVEMENT < DATE_ADD(:fin, INTERVAL 1 DAY)
              AND " . self::HORS_CORRECTIONS . "
        ");
        $stmt->execute(['id' => $idPersonne, 'domaine' => $codeDomaine, 'debut' => $debut, 'fin' => $fin]);
        return max(0, $plafond - (float)$stmt->fetchColumn());
    }

    public static function penaliteDejaAppliquee(int $idPresence): bool {
        $stmt = Database::getConnection()->prepare("SELECT 1 FROM MOUVEMENT_POINTS WHERE ID_PRESENCE = :id LIMIT 1");
        $stmt->execute(['id' => $idPresence]);
        return (bool)$stmt->fetchColumn();
    }

    public static function dejaCorrige(int $idMouvement): bool {
        $stmt = Database::getConnection()->prepare("SELECT 1 FROM MOUVEMENT_POINTS WHERE ID_MOUVEMENT_CORRIGE = :id LIMIT 1");
        $stmt->execute(['id' => $idMouvement]);
        return (bool)$stmt->fetchColumn();
    }

    public static function trouver(int $idMouvement): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT m.*, c.LIBELLE_CRITERE, d.CODE_DOMAINE, d.NOM_DOMAINE, p.NOM, p.PRENOM, p.MATRICULE
            FROM MOUVEMENT_POINTS m
            JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE
            JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            JOIN PERSONNE p ON p.ID_PERSONNE = m.ID_PERSONNE
            WHERE m.ID_MOUVEMENT = :id
        ");
        $stmt->execute(['id' => $idMouvement]);
        return $stmt->fetch() ?: null;
    }

    // Seul point d'écriture du registre : contrôle le semestre, le plafond, puis journalise.
    // $m : idPersonne, idCritere, dateMouvement, motif, idValidateur, idSignalement?, idPresence?,
    //      sensForce?, idMouvementCorrige?
    public static function appliquer(array $m): int {
        $critere = self::critere((int)$m['idCritere']);
        if (!$critere) {
            throw new RuntimeException("Critère introuvable.");
        }
        $date = $m['dateMouvement'];
        $semestre = Structure::semestrePourDate($date);
        if (!$semestre) {
            throw new RuntimeException("Aucun semestre ne couvre la date du " . date('d/m/Y', strtotime($date)) . " : créez le semestre correspondant.");
        }
        if (Structure::semestreEstClos((int)$semestre['ID_SEMESTRE'])) {
            throw new RuntimeException("Le semestre " . ($semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE']) . " est clôturé : rouvrez-le pour modifier les points.");
        }

        $valeur = (float)$critere['VALEUR_POINTS'];
        $sens = $m['sensForce'] ?? ($valeur < 0 ? 'NEGATIF' : 'POSITIF');
        $points = abs($valeur);
        $correction = !empty($m['idMouvementCorrige']);

        if ($sens === 'POSITIF' && !$correction) {
            $restant = self::bonusRestant((int)$m['idPersonne'], $critere['CODE_DOMAINE'], $semestre['DATE_DEBUT'], $semestre['DATE_FIN']);
            if ($restant !== null && $points > $restant + 0.001) {
                throw new RuntimeException("Plafond du domaine " . $critere['NOM_DOMAINE'] . " atteint : il reste "
                    . number_format($restant, 2, ',', ' ') . " point(s) à attribuer sur ce semestre.");
            }
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO MOUVEMENT_POINTS (ID_CRITERE, ID_PERSONNE, NOMBRE_POINTS, TYPE_MOUVEMENT, DATE_MOUVEMENT, MOTIF_MOUVEMENT, ID_SIGNALEMENT, ID_VALIDATEUR, ID_PRESENCE, ID_MOUVEMENT_CORRIGE)
            VALUES (:critere, :personne, :points, :sens, :date, :motif, :signalement, :validateur, :presence, :corrige)
        ");
        $stmt->execute([
            'critere' => (int)$m['idCritere'], 'personne' => (int)$m['idPersonne'], 'points' => $points, 'sens' => $sens,
            'date' => $date, 'motif' => mb_substr($m['motif'] ?? '', 0, 255),
            'signalement' => $m['idSignalement'] ?? null, 'validateur' => $m['idValidateur'] ?? null,
            'presence' => $m['idPresence'] ?? null, 'corrige' => $m['idMouvementCorrige'] ?? null,
        ]);
        $id = (int)$db->lastInsertId();
        Journal::ecrire('Points', ($sens === 'NEGATIF' ? '−' : '+') . number_format($points, 2, ',', ' ') . ' pour la personne '
            . $m['idPersonne'] . ' (' . $critere['LIBELLE_CRITERE'] . ')');
        return $id;
    }

    // Correction d'un mouvement par une écriture de sens inverse ; l'original n'est jamais modifié.
    public static function corriger(int $idMouvement, int $idAuteur, string $motif): int {
        $origine = self::trouver($idMouvement);
        if (!$origine) {
            throw new RuntimeException("Mouvement introuvable.");
        }
        if ($origine['ID_MOUVEMENT_CORRIGE'] !== null) {
            throw new RuntimeException("Une écriture de correction ne peut pas être corrigée à son tour.");
        }
        if (self::dejaCorrige($idMouvement)) {
            throw new RuntimeException("Ce mouvement a déjà été corrigé.");
        }
        return self::appliquer([
            'idPersonne' => (int)$origine['ID_PERSONNE'],
            'idCritere' => (int)$origine['ID_CRITERE'],
            'dateMouvement' => $origine['DATE_MOUVEMENT'],
            'motif' => $motif,
            'idValidateur' => $idAuteur,
            'idSignalement' => $origine['ID_SIGNALEMENT'],
            'sensForce' => $origine['TYPE_MOUVEMENT'] === 'NEGATIF' ? 'POSITIF' : 'NEGATIF',
            'idMouvementCorrige' => $idMouvement,
        ]);
    }

    private static function filtresRegistre(array $f, array &$params): string {
        $ou = ['1 = 1'];
        if (($f['q'] ?? '') !== '') {
            $ou[] = "CONCAT_WS(' ', p.NOM, p.PRENOM, p.MATRICULE) LIKE :q";
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['promo'])) { $ou[] = "e.ID_PROMO = :promo"; $params['promo'] = (int)$f['promo']; }
        if (!empty($f['domaine'])) { $ou[] = "d.ID_DOMAINE = :domaine"; $params['domaine'] = (int)$f['domaine']; }
        if (!empty($f['sens'])) { $ou[] = "m.TYPE_MOUVEMENT = :sens"; $params['sens'] = $f['sens']; }
        if (!empty($f['debut'])) { $ou[] = "m.DATE_MOUVEMENT >= :debut"; $params['debut'] = $f['debut'] . ' 00:00:00'; }
        if (!empty($f['fin'])) { $ou[] = "m.DATE_MOUVEMENT <= :fin"; $params['fin'] = $f['fin'] . ' 23:59:59'; }
        return implode(' AND ', $ou);
    }

    public static function registre(array $f, int $debut, int $limite): array {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT m.ID_MOUVEMENT, m.DATE_MOUVEMENT, m.NOMBRE_POINTS, m.TYPE_MOUVEMENT, m.MOTIF_MOUVEMENT,
                   m.ID_SIGNALEMENT, m.ID_MOUVEMENT_CORRIGE, m.ID_PERSONNE,
                   c.LIBELLE_CRITERE, d.NOM_DOMAINE, p.NOM, p.PRENOM, p.MATRICULE, pr.CODE_PROMO,
                   v.NOM AS VALIDATEUR_NOM, v.PRENOM AS VALIDATEUR_PRENOM,
                   (SELECT COUNT(*) FROM MOUVEMENT_POINTS x WHERE x.ID_MOUVEMENT_CORRIGE = m.ID_MOUVEMENT) AS CORRIGE
            FROM MOUVEMENT_POINTS m
            JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE
            JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            JOIN PERSONNE p ON p.ID_PERSONNE = m.ID_PERSONNE
            JOIN ETUDIANT e ON e.ID_PERSONNE = m.ID_PERSONNE
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            LEFT JOIN PERSONNE v ON v.ID_PERSONNE = m.ID_VALIDATEUR
            WHERE " . self::filtresRegistre($f, $params) . "
            ORDER BY m.DATE_MOUVEMENT DESC, m.ID_MOUVEMENT DESC
            LIMIT " . (int)$limite . " OFFSET " . (int)$debut . "
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function compterRegistre(array $f): int {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT COUNT(*) FROM MOUVEMENT_POINTS m
            JOIN CRITERE c ON c.ID_CRITERE = m.ID_CRITERE
            JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            JOIN PERSONNE p ON p.ID_PERSONNE = m.ID_PERSONNE
            JOIN ETUDIANT e ON e.ID_PERSONNE = m.ID_PERSONNE
            WHERE " . self::filtresRegistre($f, $params));
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
