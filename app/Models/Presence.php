<?php
// app/Models/Presence.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Parametre.php';
require_once __DIR__ . '/MouvementPoint.php';
require_once __DIR__ . '/Etudiant.php';

class Presence {
    private const SELECT = "
        SELECT pr.ID_PRESENCE, pr.STATUT, s.TITRE_SEANCE, s.DATE_SEANCE, s.HEURE_DEBUT, s.HEURE_FIN, s.LIEU, c.NOM_CLUB,
               j.ID_JUSTIFICATION, j.STATUT_VALIDATION, j.CHEMIN_FICHIER, j.DATE_DEPOT, j.MOTIF, j.COMMENTAIRE_VALIDATION
        FROM PRESENCE pr
        JOIN APPEL a ON a.ID_APPEL = pr.ID_APPEL
        JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
        LEFT JOIN CLUB c ON c.ID_CLUB = s.ID_CLUB
        LEFT JOIN JUSTIFICATION_ABSENCE j ON j.ID_JUSTIFICATION = (
            SELECT MAX(j2.ID_JUSTIFICATION) FROM JUSTIFICATION_ABSENCE j2 WHERE j2.ID_PRESENCE = pr.ID_PRESENCE
        )";

    public static function liste(int $idPersonne, string $debut, string $fin): array {
        $stmt = Database::getConnection()->prepare(self::SELECT . "
            WHERE pr.ID_PERSONNE = :id AND s.DATE_SEANCE BETWEEN :debut AND :fin
            ORDER BY s.DATE_SEANCE DESC, s.HEURE_DEBUT DESC
        ");
        $stmt->execute(['id' => $idPersonne, 'debut' => $debut, 'fin' => $fin]);
        return $stmt->fetchAll();
    }

    public static function absence(int $idPresence, int $idPersonne): ?array {
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE pr.ID_PRESENCE = :presence AND pr.ID_PERSONNE = :id");
        $stmt->execute(['presence' => $idPresence, 'id' => $idPersonne]);
        return $stmt->fetch() ?: null;
    }

    public static function dateLimite(array $presence): DateTime {
        $delai = (int)Parametre::nombre('DELAI_DEPOT_JUSTIFICATIF_HEURES', 24);
        return (new DateTime($presence['DATE_SEANCE'] . ' ' . $presence['HEURE_DEBUT']))->modify('+' . $delai . ' hours');
    }

    public static function peutJustifier(array $presence): bool {
        if ($presence['STATUT'] !== 'ABSENT') {
            return false;
        }
        if ($presence['ID_JUSTIFICATION'] !== null && $presence['STATUT_VALIDATION'] !== 'REJETEE') {
            return false;
        }
        return new DateTime() <= self::dateLimite($presence);
    }

    public static function deposerJustificatif(int $idPresence, string $motif, ?string $chemin): void {
        $stmt = Database::getConnection()->prepare("
            INSERT INTO JUSTIFICATION_ABSENCE (ID_PRESENCE, MOTIF, CHEMIN_FICHIER) VALUES (:presence, :motif, :chemin)
        ");
        $stmt->execute(['presence' => $idPresence, 'motif' => $motif, 'chemin' => $chemin]);
    }

    public static function justificatif(int $idJustification, int $idPersonne): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT j.CHEMIN_FICHIER, s.DATE_SEANCE
            FROM JUSTIFICATION_ABSENCE j
            JOIN PRESENCE pr ON pr.ID_PRESENCE = j.ID_PRESENCE
            JOIN APPEL a ON a.ID_APPEL = pr.ID_APPEL
            JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
            WHERE j.ID_JUSTIFICATION = :justification AND pr.ID_PERSONNE = :id
        ");
        $stmt->execute(['justification' => $idJustification, 'id' => $idPersonne]);
        return $stmt->fetch() ?: null;
    }

    public static function enregistrerAppel(int $idPromo, int $idDelegue, array $seance, array $statuts): int {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO SEANCE (ID_CLUB, ID_PROMO, TITRE_SEANCE, DATE_SEANCE, HEURE_DEBUT, HEURE_FIN, LIEU)
                VALUES (NULL, :promo, :titre, :date, :debut, :fin, :lieu)
            ");
            $stmt->execute([
                'promo' => $idPromo, 'titre' => $seance['titre'], 'date' => $seance['date'],
                'debut' => $seance['debut'], 'fin' => $seance['fin'], 'lieu' => $seance['lieu'],
            ]);
            $idSeance = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO APPEL (ID_SEANCE, ID_PERSONNE) VALUES (:seance, :delegue)");
            $stmt->execute(['seance' => $idSeance, 'delegue' => $idDelegue]);
            $idAppel = (int)$db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO PRESENCE (ID_APPEL, ID_PERSONNE, STATUT) VALUES (:appel, :personne, :statut)");
            foreach ($statuts as $idPersonne => $statut) {
                $stmt->execute(['appel' => $idAppel, 'personne' => $idPersonne, 'statut' => $statut]);
            }
            $db->commit();
            return $idAppel;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function appelsPromo(int $idPromo, int $limite = 10): array {
        $stmt = Database::getConnection()->prepare("
            SELECT s.TITRE_SEANCE, s.DATE_SEANCE, s.HEURE_DEBUT, a.DATE_APPEL, p.NOM AS AUTEUR_NOM, p.PRENOM AS AUTEUR_PRENOM,
                   SUM(pr.STATUT = 'PRESENT') AS PRESENTS,
                   SUM(pr.STATUT = 'RETARD') AS RETARDS,
                   SUM(pr.STATUT IN ('ABSENT', 'ABSENT_JUSTIFIE')) AS ABSENTS
            FROM APPEL a
            JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
            LEFT JOIN PERSONNE p ON p.ID_PERSONNE = a.ID_PERSONNE
            LEFT JOIN PRESENCE pr ON pr.ID_APPEL = a.ID_APPEL
            WHERE s.ID_PROMO = :promo
            GROUP BY a.ID_APPEL, s.TITRE_SEANCE, s.DATE_SEANCE, s.HEURE_DEBUT, a.DATE_APPEL, p.NOM, p.PRENOM
            ORDER BY s.DATE_SEANCE DESC, a.DATE_APPEL DESC
            LIMIT " . (int)$limite . "
        ");
        $stmt->execute(['promo' => $idPromo]);
        return $stmt->fetchAll();
    }

    // Séances planifiées qui n'ont pas encore reçu d'appel.
    public static function seancesSansAppel(?int $idPromo = null, ?int $idClub = null): array {
        $ou = ["NOT EXISTS (SELECT 1 FROM APPEL a WHERE a.ID_SEANCE = s.ID_SEANCE)"];
        $params = [];
        if ($idPromo !== null) { $ou[] = "s.ID_PROMO = :promo"; $params['promo'] = $idPromo; }
        if ($idClub !== null) { $ou[] = "s.ID_CLUB = :club"; $params['club'] = $idClub; }
        $stmt = Database::getConnection()->prepare("
            SELECT s.*, c.NOM_CLUB, pr.CODE_PROMO FROM SEANCE s
            LEFT JOIN CLUB c ON c.ID_CLUB = s.ID_CLUB
            LEFT JOIN PROMOTION pr ON pr.ID_PROMO = s.ID_PROMO
            WHERE " . implode(' AND ', $ou) . " ORDER BY s.DATE_SEANCE DESC, s.HEURE_DEBUT DESC LIMIT 50
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function seance(int $idSeance): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT s.*, c.NOM_CLUB, c.ID_RESPONSABLE, pr.CODE_PROMO FROM SEANCE s
            LEFT JOIN CLUB c ON c.ID_CLUB = s.ID_CLUB
            LEFT JOIN PROMOTION pr ON pr.ID_PROMO = s.ID_PROMO
            WHERE s.ID_SEANCE = :id
        ");
        $stmt->execute(['id' => $idSeance]);
        return $stmt->fetch() ?: null;
    }

    public static function appelFait(int $idSeance): bool {
        $stmt = Database::getConnection()->prepare("SELECT 1 FROM APPEL WHERE ID_SEANCE = :id LIMIT 1");
        $stmt->execute(['id' => $idSeance]);
        return (bool)$stmt->fetchColumn();
    }

    public static function seances(array $f, int $debut, int $limite): array {
        $ou = ['1 = 1'];
        $params = [];
        if (!empty($f['club'])) { $ou[] = "s.ID_CLUB = :club"; $params['club'] = (int)$f['club']; }
        if (!empty($f['promo'])) { $ou[] = "s.ID_PROMO = :promo"; $params['promo'] = (int)$f['promo']; }
        if (!empty($f['clubs_animes'])) { $ou[] = "s.ID_CLUB IN (" . implode(',', array_map('intval', $f['clubs_animes'])) . ")"; }
        $stmt = Database::getConnection()->prepare("
            SELECT s.*, c.NOM_CLUB, pr.CODE_PROMO,
                   (SELECT COUNT(*) FROM APPEL a WHERE a.ID_SEANCE = s.ID_SEANCE) AS APPEL_FAIT,
                   (SELECT COUNT(*) FROM PRESENCE p JOIN APPEL a ON a.ID_APPEL = p.ID_APPEL WHERE a.ID_SEANCE = s.ID_SEANCE) AS PRESENTS
            FROM SEANCE s
            LEFT JOIN CLUB c ON c.ID_CLUB = s.ID_CLUB
            LEFT JOIN PROMOTION pr ON pr.ID_PROMO = s.ID_PROMO
            WHERE " . implode(' AND ', $ou) . "
            ORDER BY s.DATE_SEANCE DESC, s.HEURE_DEBUT DESC
            LIMIT " . (int)$limite . " OFFSET " . (int)$debut
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function compterSeances(array $f): int {
        $ou = ['1 = 1'];
        $params = [];
        if (!empty($f['club'])) { $ou[] = "ID_CLUB = :club"; $params['club'] = (int)$f['club']; }
        if (!empty($f['promo'])) { $ou[] = "ID_PROMO = :promo"; $params['promo'] = (int)$f['promo']; }
        if (!empty($f['clubs_animes'])) { $ou[] = "ID_CLUB IN (" . implode(',', array_map('intval', $f['clubs_animes'])) . ")"; }
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM SEANCE WHERE " . implode(' AND ', $ou));
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function planifierSeance(array $s): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO SEANCE (ID_CLUB, ID_PROMO, TITRE_SEANCE, DATE_SEANCE, HEURE_DEBUT, HEURE_FIN, LIEU)
            VALUES (:club, :promo, :titre, :date, :debut, :fin, :lieu)
        ");
        $stmt->execute(['club' => $s['club'], 'promo' => $s['promo'], 'titre' => $s['titre'], 'date' => $s['date'],
                        'debut' => $s['debut'], 'fin' => $s['fin'], 'lieu' => $s['lieu']]);
        return (int)$db->lastInsertId();
    }

    // Participants attendus : les étudiants actifs de la promotion ou les membres du club.
    public static function participants(?int $idPromo, ?int $idClub): array {
        $db = Database::getConnection();
        if ($idClub) {
            $stmt = $db->prepare("
                SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE FROM ETUDIANT e
                JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE
                WHERE e.ID_CLUB = :club AND p.STATUT_COMPTE = 'ACTIF' ORDER BY p.NOM, p.PRENOM
            ");
            $stmt->execute(['club' => $idClub]);
            return $stmt->fetchAll();
        }
        return Etudiant::camarades((int)$idPromo);
    }

    public static function enregistrerAppelSeance(int $idSeance, int $idAuteur, array $statuts): int {
        $db = Database::getConnection();
        $transaction = !$db->inTransaction();
        if ($transaction) {
            $db->beginTransaction();
        }
        try {
            $stmt = $db->prepare("INSERT INTO APPEL (ID_SEANCE, ID_PERSONNE) VALUES (:seance, :auteur)");
            $stmt->execute(['seance' => $idSeance, 'auteur' => $idAuteur]);
            $idAppel = (int)$db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO PRESENCE (ID_APPEL, ID_PERSONNE, STATUT) VALUES (:appel, :personne, :statut)");
            foreach ($statuts as $idPersonne => $statut) {
                $stmt->execute(['appel' => $idAppel, 'personne' => $idPersonne, 'statut' => $statut]);
            }
            if ($transaction) {
                $db->commit();
            }
            return $idAppel;
        } catch (Exception $e) {
            if ($transaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function justificatifsEnAttente(int $debut = 0, int $limite = 25): array {
        $stmt = Database::getConnection()->prepare("
            SELECT j.*, pr.STATUT AS STATUT_PRESENCE, s.TITRE_SEANCE, s.DATE_SEANCE, s.HEURE_DEBUT, c.NOM_CLUB, promo.CODE_PROMO,
                   p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE
            FROM JUSTIFICATION_ABSENCE j
            JOIN PRESENCE pr ON pr.ID_PRESENCE = j.ID_PRESENCE
            JOIN APPEL a ON a.ID_APPEL = pr.ID_APPEL
            JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
            LEFT JOIN CLUB c ON c.ID_CLUB = s.ID_CLUB
            LEFT JOIN PROMOTION promo ON promo.ID_PROMO = s.ID_PROMO
            JOIN PERSONNE p ON p.ID_PERSONNE = pr.ID_PERSONNE
            WHERE j.STATUT_VALIDATION = 'EN_ATTENTE'
            ORDER BY j.DATE_DEPOT
            LIMIT " . (int)$limite . " OFFSET " . (int)$debut);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function compterJustificatifsEnAttente(): int {
        return (int)Database::getConnection()->query("SELECT COUNT(*) FROM JUSTIFICATION_ABSENCE WHERE STATUT_VALIDATION = 'EN_ATTENTE'")->fetchColumn();
    }

    public static function justificatifPersonnel(int $idJustification): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT j.*, pr.ID_PERSONNE, s.DATE_SEANCE FROM JUSTIFICATION_ABSENCE j
            JOIN PRESENCE pr ON pr.ID_PRESENCE = j.ID_PRESENCE
            JOIN APPEL a ON a.ID_APPEL = pr.ID_APPEL
            JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
            WHERE j.ID_JUSTIFICATION = :id
        ");
        $stmt->execute(['id' => $idJustification]);
        return $stmt->fetch() ?: null;
    }

    // Validation : la présence devient une absence justifiée. Rejet : elle reste une absence.
    public static function deciderJustificatif(int $idJustification, bool $valide, int $idValidateur, string $commentaire): void {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                UPDATE JUSTIFICATION_ABSENCE SET STATUT_VALIDATION = :statut, ID_VALIDATEUR = :validateur,
                       DATE_VALIDATION = NOW(), COMMENTAIRE_VALIDATION = :commentaire
                WHERE ID_JUSTIFICATION = :id
            ");
            $stmt->execute(['statut' => $valide ? 'VALIDEE' : 'REJETEE', 'validateur' => $idValidateur,
                            'commentaire' => mb_substr($commentaire, 0, 255) ?: null, 'id' => $idJustification]);
            if ($valide) {
                $stmt = $db->prepare("
                    UPDATE PRESENCE SET STATUT = 'ABSENT_JUSTIFIE'
                    WHERE ID_PRESENCE = (SELECT ID_PRESENCE FROM JUSTIFICATION_ABSENCE WHERE ID_JUSTIFICATION = :id)
                ");
                $stmt->execute(['id' => $idJustification]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // Absences non justifiées dont le délai est expiré, et retards, sans pénalité déjà écrite.
    public static function assiduiteAPenaliser(array $f = []): array {
        $delai = (int)Parametre::nombre('DELAI_DEPOT_JUSTIFICATIF_HEURES', 24);
        $params = ['delai' => $delai];
        $ou = ["pr.STATUT IN ('ABSENT', 'RETARD')",
               "NOT EXISTS (SELECT 1 FROM MOUVEMENT_POINTS m WHERE m.ID_PRESENCE = pr.ID_PRESENCE)",
               "NOT EXISTS (SELECT 1 FROM JUSTIFICATION_ABSENCE j WHERE j.ID_PRESENCE = pr.ID_PRESENCE AND j.STATUT_VALIDATION IN ('EN_ATTENTE', 'VALIDEE'))",
               "(pr.STATUT = 'RETARD' OR TIMESTAMPADD(HOUR, :delai, TIMESTAMP(s.DATE_SEANCE, s.HEURE_DEBUT)) < NOW())"];
        if (!empty($f['promo'])) { $ou[] = "e.ID_PROMO = :promo"; $params['promo'] = (int)$f['promo']; }
        $stmt = Database::getConnection()->prepare("
            SELECT pr.ID_PRESENCE, pr.STATUT, s.ID_SEANCE, s.TITRE_SEANCE, s.DATE_SEANCE, s.HEURE_DEBUT, s.ID_CLUB, c.NOM_CLUB,
                   promo.CODE_PROMO, p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE
            FROM PRESENCE pr
            JOIN APPEL a ON a.ID_APPEL = pr.ID_APPEL
            JOIN SEANCE s ON s.ID_SEANCE = a.ID_SEANCE
            LEFT JOIN CLUB c ON c.ID_CLUB = s.ID_CLUB
            JOIN PERSONNE p ON p.ID_PERSONNE = pr.ID_PERSONNE
            JOIN ETUDIANT e ON e.ID_PERSONNE = pr.ID_PERSONNE
            LEFT JOIN PROMOTION promo ON promo.ID_PROMO = e.ID_PROMO
            WHERE " . implode(' AND ', $ou) . "
            ORDER BY s.DATE_SEANCE DESC, p.NOM
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Critère d'assiduité : Discipline pour une séance de promotion, Participation aux clubs sinon.
    public static function criterePourAssiduite(string $statut, bool $club): ?array {
        return MouvementPoint::critereParLibelle($club ? 'CLUB' : 'DISCIPLINE', $statut === 'RETARD' ? 'Retard' : 'Absence');
    }
}
