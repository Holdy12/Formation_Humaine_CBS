<?php
// app/Models/Presence.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Parametre.php';

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
}
