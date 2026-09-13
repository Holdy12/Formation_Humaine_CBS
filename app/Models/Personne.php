<?php
// app/Models/Personne.php — comptes (personnel et étudiants).
require_once __DIR__ . '/../../config/database.php';

class Personne {
    private const SELECT = "SELECT p.*, r.CODE_ROLE, r.LIBELLE_ROLE FROM PERSONNE p JOIN ROLE r ON r.ID_ROLE = p.ID_ROLE";

    public static function trouver(int $id): ?array {
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE p.ID_PERSONNE = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function trouverParIdentifiant(string $identifiant): ?array {
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE p.EMAIL = :email OR p.MATRICULE = :matricule LIMIT 1");
        $stmt->execute(['email' => $identifiant, 'matricule' => $identifiant]);
        return $stmt->fetch() ?: null;
    }

    // Numéro suivant pour l'année en cours : PREFIXE<année>-<NNNN> (ex. CBS2026-0004).
    public static function genererMatricule(string $prefixe = 'CBS', string $separateur = ''): string {
        $annee = date('Y');
        $base = $prefixe . $separateur . $annee . '-';
        $stmt = Database::getConnection()->prepare("SELECT MAX(CAST(SUBSTRING(MATRICULE, :longueur) AS UNSIGNED)) FROM PERSONNE WHERE MATRICULE LIKE :motif");
        $stmt->execute(['longueur' => strlen($base) + 1, 'motif' => $base . '%']);
        $suivant = (int)$stmt->fetchColumn() + 1;
        return $base . str_pad((string)$suivant, 4, '0', STR_PAD_LEFT);
    }

    public static function genererMotDePasseTemporaire(): string {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $mdp = '';
        for ($i = 0; $i < 10; $i++) {
            $mdp .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $mdp;
    }

    public static function emailExiste(string $email, ?int $sauf = null): bool {
        $stmt = Database::getConnection()->prepare("SELECT COUNT(*) FROM PERSONNE WHERE EMAIL = :email AND ID_PERSONNE <> :sauf");
        $stmt->execute(['email' => $email, 'sauf' => $sauf ?? 0]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private static function idRole(string $code): int {
        $stmt = Database::getConnection()->prepare("SELECT ID_ROLE FROM ROLE WHERE CODE_ROLE = :code");
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();
        if ($id === false) {
            throw new RuntimeException("Le rôle $code n'existe pas.");
        }
        return (int)$id;
    }

    // Crée le compte et l'inscription ; retourne id, matricule et mot de passe temporaire.
    public static function creerEtudiant(array $d): array {
        $db = Database::getConnection();
        // Une transaction est déjà ouverte lors d'un import : ne pas en imbriquer une seconde.
        $transaction = !$db->inTransaction();
        if ($transaction) {
            $db->beginTransaction();
        }
        try {
            $matricule = self::genererMatricule('CBS');
            $motDePasse = self::genererMotDePasseTemporaire();
            $stmt = $db->prepare("
                INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, DATE_NAISSANCE, ADRESSE, PHOTO, STATUT_COMPTE, DOIT_CHANGER_MDP, MATRICULE)
                VALUES (:role, :nom, :prenom, :email, :mdp, :tel, :sexe, :naissance, :adresse, :photo, 'ACTIF', 1, :matricule)
            ");
            $stmt->execute([
                'role' => self::idRole('ETUDIANT'), 'nom' => $d['nom'], 'prenom' => $d['prenom'], 'email' => $d['email'],
                'mdp' => password_hash($motDePasse, PASSWORD_DEFAULT), 'tel' => $d['telephone'], 'sexe' => $d['sexe'],
                'naissance' => $d['date_naissance'], 'adresse' => $d['adresse'] ?: null, 'photo' => $d['photo'] ?? null, 'matricule' => $matricule,
            ]);
            $id = (int)$db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO ETUDIANT (ID_PERSONNE, ID_PROMO, ID_CLUB, EST_DELEGUE) VALUES (:id, :promo, :club, :delegue)");
            $stmt->execute(['id' => $id, 'promo' => $d['id_promo'], 'club' => $d['id_club'] ?: null, 'delegue' => !empty($d['delegue']) ? 1 : 0]);
            if ($transaction) {
                $db->commit();
            }
            return ['id' => $id, 'matricule' => $matricule, 'motDePasse' => $motDePasse];
        } catch (Exception $e) {
            if ($transaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function modifier(int $id, array $d): void {
        $stmt = Database::getConnection()->prepare("
            UPDATE PERSONNE SET NOM = :nom, PRENOM = :prenom, EMAIL = :email, TELEPHONE = :tel, SEXE = :sexe, DATE_NAISSANCE = :naissance, ADRESSE = :adresse
            WHERE ID_PERSONNE = :id
        ");
        $stmt->execute([
            'nom' => $d['nom'], 'prenom' => $d['prenom'], 'email' => $d['email'], 'tel' => $d['telephone'], 'sexe' => $d['sexe'],
            'naissance' => $d['date_naissance'], 'adresse' => $d['adresse'] ?: null, 'id' => $id,
        ]);
    }

    public static function changerStatut(int $id, string $statut): void {
        $stmt = Database::getConnection()->prepare("UPDATE PERSONNE SET STATUT_COMPTE = :statut WHERE ID_PERSONNE = :id");
        $stmt->execute(['statut' => $statut, 'id' => $id]);
    }

    // Nouveau mot de passe temporaire, à changer à la prochaine connexion.
    public static function reinitialiser(int $id): string {
        $motDePasse = self::genererMotDePasseTemporaire();
        self::changerMotDePasse($id, $motDePasse, true);
        return $motDePasse;
    }

    public static function aUnHistorique(int $idPersonne): bool {
        $db = Database::getConnection();
        foreach (['PRESENCE' => 'ID_PERSONNE', 'MOUVEMENT_POINTS' => 'ID_PERSONNE', 'SIGNALEMENT' => 'ID_PERSONNE_ETUDIANT', 'RESULTAT_SEMESTRIEL' => 'ID_PERSONNE'] as $table => $colonne) {
            $stmt = $db->prepare("SELECT 1 FROM $table WHERE $colonne = :id LIMIT 1");
            $stmt->execute(['id' => $idPersonne]);
            if ($stmt->fetchColumn()) {
                return true;
            }
        }
        return false;
    }

    public static function supprimer(int $idPersonne): void {
        $stmt = Database::getConnection()->prepare("DELETE FROM PERSONNE WHERE ID_PERSONNE = :id");
        $stmt->execute(['id' => $idPersonne]);
    }

    public static function changerMotDePasse(int $id, string $nouveau, bool $doitChanger = false): void {
        $stmt = Database::getConnection()->prepare("UPDATE PERSONNE SET MOT_DE_PASSE = :mdp, DOIT_CHANGER_MDP = :changer WHERE ID_PERSONNE = :id");
        $stmt->execute(['mdp' => password_hash($nouveau, PASSWORD_DEFAULT), 'changer' => $doitChanger ? 1 : 0, 'id' => $id]);
    }

    public static function modifierPhoto(int $id, ?string $chemin): void {
        $stmt = Database::getConnection()->prepare("UPDATE PERSONNE SET PHOTO = :photo WHERE ID_PERSONNE = :id");
        $stmt->execute(['photo' => $chemin, 'id' => $id]);
    }

    public static function modifierCoordonnees(int $id, string $telephone, ?string $adresse): void {
        $stmt = Database::getConnection()->prepare("UPDATE PERSONNE SET TELEPHONE = :tel, ADRESSE = :adresse WHERE ID_PERSONNE = :id");
        $stmt->execute(['tel' => $telephone, 'adresse' => $adresse, 'id' => $id]);
    }

    public static function roles(): array {
        return Database::getConnection()->query("SELECT * FROM ROLE WHERE CODE_ROLE <> 'ETUDIANT' ORDER BY ID_ROLE")->fetchAll();
    }

    public static function personnel(string $q = ''): array {
        $params = [];
        $ou = "r.CODE_ROLE <> 'ETUDIANT'";
        if ($q !== '') {
            $ou .= " AND CONCAT_WS(' ', p.NOM, p.PRENOM, p.EMAIL) LIKE :q";
            $params['q'] = '%' . $q . '%';
        }
        $stmt = Database::getConnection()->prepare("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.EMAIL, p.TELEPHONE, p.SEXE, p.STATUT_COMPTE, p.DOIT_CHANGER_MDP, p.ID_ROLE, p.PHOTO,
                   r.CODE_ROLE, r.LIBELLE_ROLE,
                   (SELECT MAX(DATE_CONNEXION) FROM JOURNAL_CONNEXION j WHERE j.ID_PERSONNE = p.ID_PERSONNE AND j.ACTION = 'Connexion' AND j.STATUT = 'SUCCES') AS DERNIERE_CONNEXION
            FROM PERSONNE p JOIN ROLE r ON r.ID_ROLE = p.ID_ROLE
            WHERE $ou ORDER BY r.ID_ROLE, p.NOM, p.PRENOM
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function creerPersonnel(array $d): array {
        $db = Database::getConnection();
        $motDePasse = self::genererMotDePasseTemporaire();
        $stmt = $db->prepare("
            INSERT INTO PERSONNE (ID_ROLE, NOM, PRENOM, EMAIL, MOT_DE_PASSE, TELEPHONE, SEXE, STATUT_COMPTE, DOIT_CHANGER_MDP)
            VALUES (:role, :nom, :prenom, :email, :mdp, :tel, :sexe, 'ACTIF', 1)
        ");
        $stmt->execute([
            'role' => $d['id_role'], 'nom' => $d['nom'], 'prenom' => $d['prenom'], 'email' => $d['email'],
            'mdp' => password_hash($motDePasse, PASSWORD_DEFAULT), 'tel' => $d['telephone'], 'sexe' => $d['sexe'],
        ]);
        return ['id' => (int)$db->lastInsertId(), 'motDePasse' => $motDePasse];
    }

    public static function modifierPersonnel(int $id, array $d): void {
        $stmt = Database::getConnection()->prepare("
            UPDATE PERSONNE SET ID_ROLE = :role, NOM = :nom, PRENOM = :prenom, EMAIL = :email, TELEPHONE = :tel, SEXE = :sexe WHERE ID_PERSONNE = :id
        ");
        $stmt->execute(['role' => $d['id_role'], 'nom' => $d['nom'], 'prenom' => $d['prenom'], 'email' => $d['email'],
                        'tel' => $d['telephone'], 'sexe' => $d['sexe'], 'id' => $id]);
    }

    public static function compterAdminsActifs(): int {
        return (int)Database::getConnection()->query("
            SELECT COUNT(*) FROM PERSONNE p JOIN ROLE r ON r.ID_ROLE = p.ID_ROLE WHERE r.CODE_ROLE = 'ADMIN' AND p.STATUT_COMPTE = 'ACTIF'
        ")->fetchColumn();
    }

    public static function motDePasseHash(int $id): string {
        $stmt = Database::getConnection()->prepare("SELECT MOT_DE_PASSE FROM PERSONNE WHERE ID_PERSONNE = :id");
        $stmt->execute(['id' => $id]);
        return (string)$stmt->fetchColumn();
    }
}
