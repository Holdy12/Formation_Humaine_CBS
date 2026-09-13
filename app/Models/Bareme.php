<?php
// app/Models/Bareme.php — domaines, critères et paramètres système.
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Parametre.php';

class Bareme {
    public static function domaines(): array {
        return Database::getConnection()->query("SELECT * FROM DOMAINE ORDER BY ID_DOMAINE")->fetchAll();
    }

    public static function renommerDomaine(int $id, string $nom): void {
        $stmt = Database::getConnection()->prepare("UPDATE DOMAINE SET NOM_DOMAINE = :nom WHERE ID_DOMAINE = :id");
        $stmt->execute(['nom' => mb_substr($nom, 0, 100), 'id' => $id]);
    }

    public static function criteres(bool $actifsSeulement = false): array {
        $sql = "SELECT c.*, d.NOM_DOMAINE, d.CODE_DOMAINE FROM CRITERE c JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE";
        if ($actifsSeulement) {
            $sql .= " WHERE c.ACTIF = 1";
        }
        return Database::getConnection()->query($sql . " ORDER BY d.ID_DOMAINE, c.LIBELLE_CRITERE")->fetchAll();
    }

    public static function critere(int $id): ?array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM CRITERE WHERE ID_CRITERE = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function creerCritere(int $idDomaine, string $libelle, float $valeur): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO CRITERE (ID_DOMAINE, LIBELLE_CRITERE, VALEUR_POINTS, ACTIF) VALUES (:domaine, :libelle, :valeur, 1)");
        $stmt->execute(['domaine' => $idDomaine, 'libelle' => mb_substr($libelle, 0, 100), 'valeur' => $valeur]);
        return (int)$db->lastInsertId();
    }

    public static function modifierCritere(int $id, string $libelle, float $valeur, bool $actif): void {
        $stmt = Database::getConnection()->prepare("UPDATE CRITERE SET LIBELLE_CRITERE = :libelle, VALEUR_POINTS = :valeur, ACTIF = :actif WHERE ID_CRITERE = :id");
        $stmt->execute(['libelle' => mb_substr($libelle, 0, 100), 'valeur' => $valeur, 'actif' => $actif ? 1 : 0, 'id' => $id]);
    }

    public static function parametres(): array {
        return Database::getConnection()->query("SELECT * FROM PARAMETRE_SYSTEME ORDER BY CODE_PARAMETRE")->fetchAll();
    }

    public static function modifierParametre(string $code, string $valeur): void {
        $stmt = Database::getConnection()->prepare("UPDATE PARAMETRE_SYSTEME SET VALEUR = :valeur WHERE CODE_PARAMETRE = :code");
        $stmt->execute(['valeur' => $valeur, 'code' => $code]);
        Parametre::viderCache();
    }
}
