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
}
