<?php
// app/Models/Signalement.php
require_once __DIR__ . '/../../config/database.php';

class Signalement {
    private const STATUTS_REPONSE = ['SOUMIS', 'EN_EXAMEN', 'ETUDIANT_ENTENDU'];

    private const SELECT = "
        SELECT s.ID_SIGNALEMENT, s.TITRE_SIGNALEMENT, s.DESCRIPTION, s.DATE_FAITS, s.LIEU_SIGNALEMENT, s.DATE_SIGNALEMENT, s.STATUT,
               s.REPONSE_ETUDIANT, s.DATE_REPONSE, s.DECISION, s.DATE_DECISION, s.ID_PERSONNE_ETUDIANT, s.ID_PERSONNE_AUTEUR,
               c.LIBELLE_CRITERE, c.VALEUR_POINTS, d.NOM_DOMAINE, d.CODE_DOMAINE,
               r.LIBELLE_ROLE AS AUTEUR_ROLE,
               va.NOM AS VALIDATEUR_NOM, va.PRENOM AS VALIDATEUR_PRENOM,
               et.NOM AS ETUDIANT_NOM, et.PRENOM AS ETUDIANT_PRENOM
        FROM SIGNALEMENT s
        JOIN CRITERE c ON c.ID_CRITERE = s.ID_CRITERE
        JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
        JOIN PERSONNE au ON au.ID_PERSONNE = s.ID_PERSONNE_AUTEUR
        LEFT JOIN ROLE r ON r.ID_ROLE = au.ID_ROLE
        LEFT JOIN PERSONNE va ON va.ID_PERSONNE = s.ID_PERSONNE_VALIDATEUR
        JOIN PERSONNE et ON et.ID_PERSONNE = s.ID_PERSONNE_ETUDIANT";

    public static function pourEtudiant(int $idPersonne): array {
        $stmt = Database::getConnection()->prepare(self::SELECT . "
            WHERE s.ID_PERSONNE_ETUDIANT = :id AND s.STATUT <> 'BROUILLON'
            ORDER BY s.DATE_SIGNALEMENT DESC
        ");
        $stmt->execute(['id' => $idPersonne]);
        return $stmt->fetchAll();
    }

    public static function detail(int $id, int $idPersonne): ?array {
        $stmt = Database::getConnection()->prepare(self::SELECT . "
            WHERE s.ID_SIGNALEMENT = :sid AND s.ID_PERSONNE_ETUDIANT = :id AND s.STATUT <> 'BROUILLON'
        ");
        $stmt->execute(['sid' => $id, 'id' => $idPersonne]);
        return $stmt->fetch() ?: null;
    }

    public static function peutRepondre(array $dossier): bool {
        return in_array($dossier['STATUT'], self::STATUTS_REPONSE, true) && $dossier['DATE_DECISION'] === null;
    }

    public static function repondre(int $id, int $idPersonne, string $reponse): void {
        $stmt = Database::getConnection()->prepare("
            UPDATE SIGNALEMENT SET REPONSE_ETUDIANT = :reponse, DATE_REPONSE = NOW()
            WHERE ID_SIGNALEMENT = :sid AND ID_PERSONNE_ETUDIANT = :id
        ");
        $stmt->execute(['reponse' => $reponse, 'sid' => $id, 'id' => $idPersonne]);
    }

    public static function libelleStatut(string $statut): array {
        return match ($statut) {
            'BROUILLON'        => ['badge-gris',   'Brouillon'],
            'SOUMIS'           => ['badge-bleu',   'Soumis'],
            'EN_EXAMEN'        => ['badge-orange', 'En examen'],
            'ETUDIANT_ENTENDU' => ['badge-orange', 'Audition effectuée'],
            'VALIDE'           => ['badge-vert',   'Validé'],
            'REJETE'           => ['badge-rouge',  'Rejeté'],
            'ANNULE'           => ['badge-gris',   'Annulé'],
            'CLOTURE'          => ['badge-gris',   'Clôturé'],
            default            => ['badge-gris',   $statut],
        };
    }

    public static function criteresParDomaine(): array {
        $rows = Database::getConnection()->query("
            SELECT c.ID_CRITERE, c.LIBELLE_CRITERE, c.VALEUR_POINTS, d.NOM_DOMAINE
            FROM CRITERE c JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            WHERE c.ACTIF = 1 ORDER BY d.ID_DOMAINE, c.ID_CRITERE
        ")->fetchAll();
        $groupes = [];
        foreach ($rows as $r) {
            $groupes[$r['NOM_DOMAINE']][] = $r;
        }
        return $groupes;
    }

    public static function creer(int $idAuteur, array $d): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO SIGNALEMENT (ID_PERSONNE_ETUDIANT, ID_PERSONNE_AUTEUR, ID_CRITERE, TITRE_SIGNALEMENT, DESCRIPTION, DATE_FAITS, LIEU_SIGNALEMENT, STATUT)
            VALUES (:etudiant, :auteur, :critere, :titre, :description, :dateFaits, :lieu, 'SOUMIS')
        ");
        $stmt->execute([
            'etudiant' => $d['etudiant'], 'auteur' => $idAuteur, 'critere' => $d['critere'], 'titre' => $d['titre'],
            'description' => $d['description'], 'dateFaits' => $d['dateFaits'], 'lieu' => $d['lieu'],
        ]);
        return (int)$db->lastInsertId();
    }

    public static function ajouterPiece(int $idSignalement, string $nom, string $chemin, string $type): void {
        $stmt = Database::getConnection()->prepare("
            INSERT INTO PIECE_JUSTIFICATIVE (ID_SIGNALEMENT, NOM_FICHIER, CHEMIN_FICHIER, TYPE_FICHIER)
            VALUES (:signalement, :nom, :chemin, :type)
        ");
        $stmt->execute(['signalement' => $idSignalement, 'nom' => mb_substr($nom, 0, 100), 'chemin' => $chemin, 'type' => $type]);
    }

    public static function emisPar(int $idAuteur): array {
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE s.ID_PERSONNE_AUTEUR = :id ORDER BY s.DATE_SIGNALEMENT DESC");
        $stmt->execute(['id' => $idAuteur]);
        return $stmt->fetchAll();
    }
}
