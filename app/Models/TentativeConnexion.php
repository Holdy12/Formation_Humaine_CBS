<?php
// app/Models/TentativeConnexion.php — échecs de connexion récents (table TENTATIVE_CONNEXION).
require_once __DIR__ . '/../../config/database.php';

class TentativeConnexion {
    public const FENETRE_MINUTES = 10;
    public const MAX_PAR_IDENTIFIANT = 5;
    // Généreux : une école entière peut sortir par une seule adresse.
    public const MAX_PAR_ADRESSE = 50;

    private static function adresse(): string {
        return substr($_SERVER['REMOTE_ADDR'] ?? 'inconnue', 0, 50);
    }

    private static function normaliser(string $identifiant): string {
        return mb_strtolower(mb_substr(trim($identifiant), 0, 100));
    }

    // Vrai si l'identifiant ou l'adresse a dépassé sa limite sur la fenêtre.
    public static function bloque(string $identifiant): bool {
        $stmt = Database::getConnection()->prepare("
            SELECT SUM(IDENTIFIANT = :identifiant) AS PAR_IDENTIFIANT, SUM(ADRESSE_IP = :adresse) AS PAR_ADRESSE
            FROM TENTATIVE_CONNEXION
            WHERE DATE_TENTATIVE > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)
              AND (IDENTIFIANT = :identifiant2 OR ADRESSE_IP = :adresse2)
        ");
        $stmt->execute(['identifiant' => self::normaliser($identifiant), 'identifiant2' => self::normaliser($identifiant),
                        'adresse' => self::adresse(), 'adresse2' => self::adresse(), 'minutes' => self::FENETRE_MINUTES]);
        $n = $stmt->fetch();
        return (int)$n['PAR_IDENTIFIANT'] >= self::MAX_PAR_IDENTIFIANT || (int)$n['PAR_ADRESSE'] >= self::MAX_PAR_ADRESSE;
    }

    public static function enregistrerEchec(string $identifiant): void {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO TENTATIVE_CONNEXION (IDENTIFIANT, ADRESSE_IP) VALUES (:identifiant, :adresse)");
        $stmt->execute(['identifiant' => self::normaliser($identifiant), 'adresse' => self::adresse()]);
        $db->exec("DELETE FROM TENTATIVE_CONNEXION WHERE DATE_TENTATIVE < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    // Une connexion réussie efface les échecs de cet identifiant.
    public static function effacer(string $identifiant): void {
        $stmt = Database::getConnection()->prepare("DELETE FROM TENTATIVE_CONNEXION WHERE IDENTIFIANT = :identifiant");
        $stmt->execute(['identifiant' => self::normaliser($identifiant)]);
    }
}
