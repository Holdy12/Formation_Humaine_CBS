<?php
// app/Models/Signalement.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/MouvementPoint.php';

class Signalement {
    private const STATUTS_REPONSE = ['SOUMIS', 'EN_EXAMEN', 'ETUDIANT_ENTENDU'];

    private const SELECT = "
        SELECT s.ID_SIGNALEMENT, s.TITRE_SIGNALEMENT, s.DESCRIPTION, s.DATE_FAITS, s.LIEU_SIGNALEMENT, s.DATE_SIGNALEMENT, s.STATUT,
               s.REPONSE_ETUDIANT, s.DATE_REPONSE, s.DECISION, s.DATE_DECISION, s.ID_PERSONNE_ETUDIANT, s.ID_PERSONNE_AUTEUR,
               s.ID_CRITERE, s.DATE_AUDITION, s.NOTES_AUDITION, s.CONSEIL_DISCIPLINE,
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

    // Dossiers concernant un étudiant ; limités à ceux d'un auteur si $idAuteur est fourni.
    public static function pourEtudiant(int $idPersonne, ?int $idAuteur = null): array {
        $params = ['id' => $idPersonne];
        $ou = "s.ID_PERSONNE_ETUDIANT = :id AND s.STATUT <> 'BROUILLON'";
        if ($idAuteur !== null) {
            $ou .= " AND s.ID_PERSONNE_AUTEUR = :auteur";
            $params['auteur'] = $idAuteur;
        }
        $stmt = Database::getConnection()->prepare(self::SELECT . " WHERE $ou ORDER BY s.DATE_SIGNALEMENT DESC");
        $stmt->execute($params);
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

    private static function filtres(array $f, array &$params, ?int $auteurSeulement): string {
        $ou = ["s.STATUT <> 'BROUILLON'"];
        if ($auteurSeulement !== null) {
            $ou[] = "s.ID_PERSONNE_AUTEUR = :auteur";
            $params['auteur'] = $auteurSeulement;
        }
        if (($f['q'] ?? '') !== '') {
            $ou[] = "CONCAT_WS(' ', et.NOM, et.PRENOM, et.MATRICULE, s.TITRE_SIGNALEMENT) LIKE :q";
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['statut'])) { $ou[] = "s.STATUT = :statut"; $params['statut'] = $f['statut']; }
        if (!empty($f['domaine'])) { $ou[] = "d.ID_DOMAINE = :domaine"; $params['domaine'] = (int)$f['domaine']; }
        if (!empty($f['promo'])) { $ou[] = "e.ID_PROMO = :promo"; $params['promo'] = (int)$f['promo']; }
        if (!empty($f['debut'])) { $ou[] = "s.DATE_FAITS >= :debut"; $params['debut'] = $f['debut'] . ' 00:00:00'; }
        if (!empty($f['fin'])) { $ou[] = "s.DATE_FAITS <= :fin"; $params['fin'] = $f['fin'] . ' 23:59:59'; }
        return implode(' AND ', $ou);
    }

    public static function filtrer(array $f, int $debut, int $limite, ?int $auteurSeulement = null): array {
        $params = [];
        $stmt = Database::getConnection()->prepare(self::SELECT . "
            JOIN ETUDIANT e ON e.ID_PERSONNE = s.ID_PERSONNE_ETUDIANT
            WHERE " . self::filtres($f, $params, $auteurSeulement) . "
            ORDER BY s.DATE_SIGNALEMENT DESC
            LIMIT " . (int)$limite . " OFFSET " . (int)$debut);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function compter(array $f, ?int $auteurSeulement = null): int {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT COUNT(*) FROM SIGNALEMENT s
            JOIN CRITERE c ON c.ID_CRITERE = s.ID_CRITERE
            JOIN DOMAINE d ON d.ID_DOMAINE = c.ID_DOMAINE
            JOIN PERSONNE et ON et.ID_PERSONNE = s.ID_PERSONNE_ETUDIANT
            JOIN ETUDIANT e ON e.ID_PERSONNE = s.ID_PERSONNE_ETUDIANT
            WHERE " . self::filtres($f, $params, $auteurSeulement));
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function compterParStatut(): array {
        $lignes = Database::getConnection()->query("SELECT STATUT, COUNT(*) N FROM SIGNALEMENT GROUP BY STATUT")->fetchAll();
        $totaux = [];
        foreach ($lignes as $l) {
            $totaux[$l['STATUT']] = (int)$l['N'];
        }
        return $totaux;
    }

    // Dossier vu par le personnel : sans filtre sur l'étudiant, avec l'auteur nommé.
    public static function dossier(int $id): ?array {
        $sql = str_replace('FROM SIGNALEMENT s', ', au.NOM AS AUTEUR_NOM, au.PRENOM AS AUTEUR_PRENOM, et.MATRICULE AS ETUDIANT_MATRICULE, e.ID_PROMO, pr.CODE_PROMO FROM SIGNALEMENT s', self::SELECT);
        $stmt = Database::getConnection()->prepare($sql . "
            JOIN ETUDIANT e ON e.ID_PERSONNE = s.ID_PERSONNE_ETUDIANT
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            WHERE s.ID_SIGNALEMENT = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function historique(int $id): array {
        $stmt = Database::getConnection()->prepare("
            SELECT h.*, p.NOM, p.PRENOM, r.LIBELLE_ROLE
            FROM SIGNALEMENT_HISTORIQUE h
            LEFT JOIN PERSONNE p ON p.ID_PERSONNE = h.ID_PERSONNE
            LEFT JOIN ROLE r ON r.ID_ROLE = p.ID_ROLE
            WHERE h.ID_SIGNALEMENT = :id ORDER BY h.DATE_CHANGEMENT, h.ID_HISTORIQUE
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public static function temoins(int $id): array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM TEMOIN WHERE ID_SIGNALEMENT = :id ORDER BY NOM_TEMOIN");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public static function pieces(int $id): array {
        $stmt = Database::getConnection()->prepare("SELECT * FROM PIECE_JUSTIFICATIVE WHERE ID_SIGNALEMENT = :id ORDER BY ID_PIECE");
        $stmt->execute(['id' => $id]);
        return $stmt->fetchAll();
    }

    public static function piece(int $idPiece): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT pj.*, s.ID_PERSONNE_AUTEUR FROM PIECE_JUSTIFICATIVE pj
            JOIN SIGNALEMENT s ON s.ID_SIGNALEMENT = pj.ID_SIGNALEMENT WHERE pj.ID_PIECE = :id
        ");
        $stmt->execute(['id' => $idPiece]);
        return $stmt->fetch() ?: null;
    }

    public static function ajouterTemoin(int $idSignalement, string $nom, string $prenom, ?string $contact): void {
        $stmt = Database::getConnection()->prepare("
            INSERT INTO TEMOIN (ID_SIGNALEMENT, NOM_TEMOIN, PRENOM_TEMOIN, CONTACT_TEMOIN) VALUES (:s, :nom, :prenom, :contact)
        ");
        $stmt->execute(['s' => $idSignalement, 'nom' => mb_substr($nom, 0, 50), 'prenom' => mb_substr($prenom, 0, 50), 'contact' => $contact ? mb_substr($contact, 0, 50) : null]);
    }

    public static function historiser(int $idSignalement, string $statut, ?int $idPersonne, ?string $commentaire = null): void {
        $stmt = Database::getConnection()->prepare("
            INSERT INTO SIGNALEMENT_HISTORIQUE (ID_SIGNALEMENT, STATUT, ID_PERSONNE, COMMENTAIRE) VALUES (:s, :statut, :p, :c)
        ");
        $stmt->execute(['s' => $idSignalement, 'statut' => $statut, 'p' => $idPersonne, 'c' => $commentaire ? mb_substr($commentaire, 0, 255) : null]);
    }

    public static function changerStatut(int $id, string $statut, int $idPersonne, ?string $commentaire = null): void {
        $stmt = Database::getConnection()->prepare("UPDATE SIGNALEMENT SET STATUT = :statut WHERE ID_SIGNALEMENT = :id");
        $stmt->execute(['statut' => $statut, 'id' => $id]);
        self::historiser($id, $statut, $idPersonne, $commentaire);
    }

    public static function enregistrerAudition(int $id, string $date, string $notes, int $idPersonne): void {
        $stmt = Database::getConnection()->prepare("
            UPDATE SIGNALEMENT SET DATE_AUDITION = :date, NOTES_AUDITION = :notes, STATUT = 'ETUDIANT_ENTENDU' WHERE ID_SIGNALEMENT = :id
        ");
        $stmt->execute(['date' => $date, 'notes' => $notes, 'id' => $id]);
        self::historiser($id, 'ETUDIANT_ENTENDU', $idPersonne, 'Audition du ' . date('d/m/Y', strtotime($date)));
    }

    public static function decider(int $id, string $statut, string $motif, bool $conseil, int $idValidateur): void {
        $stmt = Database::getConnection()->prepare("
            UPDATE SIGNALEMENT SET STATUT = :statut, DECISION = :motif, DATE_DECISION = NOW(),
                   ID_PERSONNE_VALIDATEUR = :validateur, CONSEIL_DISCIPLINE = :conseil
            WHERE ID_SIGNALEMENT = :id
        ");
        $stmt->execute(['statut' => $statut, 'motif' => $motif, 'validateur' => $idValidateur, 'conseil' => $conseil ? 1 : 0, 'id' => $id]);
        self::historiser($id, $statut, $idValidateur, mb_substr($motif, 0, 255));
    }

    // Crée un dossier par étudiant concerné (sanction collective) et retourne les identifiants.
    public static function creerPlusieurs(int $idAuteur, array $idsEtudiants, array $d, array $temoins = [], array $pieces = []): array {
        $db = Database::getConnection();
        $transaction = !$db->inTransaction();
        if ($transaction) {
            $db->beginTransaction();
        }
        try {
            $ids = [];
            foreach ($idsEtudiants as $idEtudiant) {
                $idSignalement = self::creer($idAuteur, $d + ['etudiant' => (int)$idEtudiant]);
                self::historiser($idSignalement, 'SOUMIS', $idAuteur, 'Signalement transmis');
                foreach ($temoins as $t) {
                    self::ajouterTemoin($idSignalement, $t['nom'], $t['prenom'], $t['contact']);
                }
                foreach ($pieces as $p) {
                    self::ajouterPiece($idSignalement, $p['nom'], $p['chemin'], $p['type']);
                }
                $ids[] = $idSignalement;
            }
            if ($transaction) {
                $db->commit();
            }
            return $ids;
        } catch (Exception $e) {
            if ($transaction && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
