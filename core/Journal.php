<?php
// core/Journal.php — journal d'audit (table JOURNAL_CONNEXION).
require_once __DIR__ . '/../config/database.php';

class Journal {
    public static function ecrire(string $action, string $details = '', string $statut = 'SUCCES', ?int $idPersonne = null): void {
        $idPersonne = $idPersonne ?? (int)($_SESSION['user_id'] ?? 0);
        if ($idPersonne <= 0) {
            return;
        }
        $stmt = Database::getConnection()->prepare("
            INSERT INTO JOURNAL_CONNEXION (ID_PERSONNE, ADRESSE_IP, STATUT, ACTION, DETAILS)
            VALUES (:id, :ip, :statut, :action, :details)
        ");
        $stmt->execute([
            'id'      => $idPersonne,
            'ip'      => substr($_SERVER['REMOTE_ADDR'] ?? 'inconnue', 0, 50),
            'statut'  => $statut,
            'action'  => mb_substr($action, 0, 100),
            'details' => mb_substr($details, 0, 255),
        ]);
    }

    public static function actions(): array {
        return array_column(Database::getConnection()->query("SELECT DISTINCT ACTION FROM JOURNAL_CONNEXION ORDER BY ACTION")->fetchAll(), 'ACTION');
    }

    private static function filtres(array $f, array &$params): string {
        $ou = ['1 = 1'];
        if (($f['q'] ?? '') !== '') {
            $ou[] = "CONCAT_WS(' ', p.NOM, p.PRENOM, j.DETAILS) LIKE :q";
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['action'])) { $ou[] = "j.ACTION = :action"; $params['action'] = $f['action']; }
        if (!empty($f['statut'])) { $ou[] = "j.STATUT = :statut"; $params['statut'] = $f['statut']; }
        if (!empty($f['debut'])) { $ou[] = "j.DATE_CONNEXION >= :debut"; $params['debut'] = $f['debut'] . ' 00:00:00'; }
        if (!empty($f['fin'])) { $ou[] = "j.DATE_CONNEXION <= :fin"; $params['fin'] = $f['fin'] . ' 23:59:59'; }
        return implode(' AND ', $ou);
    }

    public static function lire(array $f, int $debut, int $limite): array {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT j.*, p.NOM, p.PRENOM, r.LIBELLE_ROLE
            FROM JOURNAL_CONNEXION j
            LEFT JOIN PERSONNE p ON p.ID_PERSONNE = j.ID_PERSONNE
            LEFT JOIN ROLE r ON r.ID_ROLE = p.ID_ROLE
            WHERE " . self::filtres($f, $params) . "
            ORDER BY j.DATE_CONNEXION DESC, j.ID_JOURNAL DESC
            LIMIT " . (int)$limite . " OFFSET " . (int)$debut);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function compter(array $f): int {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT COUNT(*) FROM JOURNAL_CONNEXION j LEFT JOIN PERSONNE p ON p.ID_PERSONNE = j.ID_PERSONNE
            WHERE " . self::filtres($f, $params));
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function recents(int $limite = 8): array {
        return self::lire([], 0, $limite);
    }
}
