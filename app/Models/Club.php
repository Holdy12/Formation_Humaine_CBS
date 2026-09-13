<?php
// app/Models/Club.php — clubs, responsables et membres.
require_once __DIR__ . '/../../config/database.php';

class Club {
    private const SELECT = "
        SELECT c.*, p.NOM AS RESP_NOM, p.PRENOM AS RESP_PRENOM,
               (SELECT COUNT(*) FROM ETUDIANT e JOIN PERSONNE pe ON pe.ID_PERSONNE = e.ID_PERSONNE WHERE e.ID_CLUB = c.ID_CLUB AND pe.STATUT_COMPTE = 'ACTIF') AS NB_MEMBRES
        FROM CLUB c LEFT JOIN PERSONNE p ON p.ID_PERSONNE = c.ID_RESPONSABLE";

    public static function lister(): array {
        return Database::getConnection()->query(self::SELECT . " ORDER BY c.NOM_CLUB")->fetchAll();
    }

    public static function trouver(int $id): ?array {
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE c.ID_CLUB = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function animesPar(int $idPersonne): array {
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE c.ID_RESPONSABLE = :id ORDER BY c.NOM_CLUB");
        $stmt->execute(['id' => $idPersonne]);
        return $stmt->fetchAll();
    }

    public static function estAnimePar(int $idClub, int $idPersonne): bool {
        $stmt = Database::getConnection()->prepare("SELECT 1 FROM CLUB WHERE ID_CLUB = :club AND ID_RESPONSABLE = :personne");
        $stmt->execute(['club' => $idClub, 'personne' => $idPersonne]);
        return (bool)$stmt->fetchColumn();
    }

    public static function creer(string $nom, ?string $description, ?int $idResponsable): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("INSERT INTO CLUB (NOM_CLUB, DESCRIPTION, ID_RESPONSABLE) VALUES (:nom, :description, :resp)");
        $stmt->execute(['nom' => mb_substr($nom, 0, 100), 'description' => $description ?: null, 'resp' => $idResponsable]);
        return (int)$db->lastInsertId();
    }

    public static function modifier(int $id, string $nom, ?string $description, ?int $idResponsable): void {
        $stmt = Database::getConnection()->prepare("UPDATE CLUB SET NOM_CLUB = :nom, DESCRIPTION = :description, ID_RESPONSABLE = :resp WHERE ID_CLUB = :id");
        $stmt->execute(['nom' => mb_substr($nom, 0, 100), 'description' => $description ?: null, 'resp' => $idResponsable, 'id' => $id]);
    }

    public static function membres(int $idClub): array {
        $stmt = Database::getConnection()->prepare("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE, p.STATUT_COMPTE, pr.CODE_PROMO
            FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            WHERE e.ID_CLUB = :club ORDER BY p.NOM, p.PRENOM
        ");
        $stmt->execute(['club' => $idClub]);
        return $stmt->fetchAll();
    }

    // Un étudiant n'appartient qu'à un club à la fois : l'ajout remplace l'adhésion précédente.
    public static function ajouterMembre(int $idClub, int $idPersonne): void {
        $stmt = Database::getConnection()->prepare("UPDATE ETUDIANT SET ID_CLUB = :club WHERE ID_PERSONNE = :personne");
        $stmt->execute(['club' => $idClub, 'personne' => $idPersonne]);
    }

    public static function retirerMembre(int $idClub, int $idPersonne): void {
        $stmt = Database::getConnection()->prepare("UPDATE ETUDIANT SET ID_CLUB = NULL WHERE ID_PERSONNE = :personne AND ID_CLUB = :club");
        $stmt->execute(['club' => $idClub, 'personne' => $idPersonne]);
    }

    public static function responsablesPossibles(): array {
        return Database::getConnection()->query("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, r.LIBELLE_ROLE FROM PERSONNE p JOIN ROLE r ON r.ID_ROLE = p.ID_ROLE
            WHERE r.CODE_ROLE IN ('RESPONSABLE_CLUB', 'RESPONSABLE_ENV', 'ENSEIGNANT', 'RESPONSABLE_FH') AND p.STATUT_COMPTE = 'ACTIF'
            ORDER BY p.NOM, p.PRENOM
        ")->fetchAll();
    }

    public static function etudiantsSansClub(): array {
        return Database::getConnection()->query("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE, pr.CODE_PROMO
            FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            WHERE e.ID_CLUB IS NULL AND p.STATUT_COMPTE = 'ACTIF' ORDER BY p.NOM, p.PRENOM
        ")->fetchAll();
    }
}
