<?php
// app/Models/Etudiant.php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Personne.php';
require_once __DIR__ . '/Structure.php';

class Etudiant {
    private const SEMESTRE = "SELECT s.ID_SEMESTRE, s.CODE_SEMESTRE, s.LIBELLE_SEMESTRE, s.DATE_DEBUT, s.DATE_FIN, a.LIBELLE_ANNEE
                              FROM SEMESTRE s JOIN ANNEE_ACADEMIQUE a ON a.ID_ANNEE = s.ID_ANNEE";

    public static function fiche(int $idPersonne): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT p.*, e.ID_ETUDIANT, e.ID_PROMO, e.ID_CLUB, e.EST_DELEGUE,
                   pr.CODE_PROMO, n.LIBELLE_NIVEAU, f.NOM_FILIERE, c.NOM_CLUB, a.LIBELLE_ANNEE
            FROM ETUDIANT e
            JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            JOIN NIVEAU n ON n.ID_NIVEAU = pr.ID_NIVEAU
            JOIN FILIERE f ON f.ID_FILIERE = pr.ID_FILIERE
            JOIN ANNEE_ACADEMIQUE a ON a.ID_ANNEE = pr.ID_ANNEE
            LEFT JOIN CLUB c ON c.ID_CLUB = e.ID_CLUB
            WHERE e.ID_PERSONNE = :id
        ");
        $stmt->execute(['id' => $idPersonne]);
        return $stmt->fetch() ?: null;
    }

    public static function semestreCourant(): ?array {
        $db = Database::getConnection();
        $courant = $db->query(self::SEMESTRE . " WHERE CURRENT_DATE() BETWEEN s.DATE_DEBUT AND s.DATE_FIN ORDER BY s.DATE_DEBUT DESC LIMIT 1")->fetch();
        if ($courant) {
            return $courant;
        }
        return $db->query(self::SEMESTRE . " ORDER BY s.DATE_DEBUT DESC LIMIT 1")->fetch() ?: null;
    }

    public static function semestres(): array {
        return Database::getConnection()->query(self::SEMESTRE . " ORDER BY s.DATE_DEBUT DESC")->fetchAll();
    }

    public static function semestre(int $id): ?array {
        $stmt = Database::getConnection()->prepare(self::SEMESTRE . " WHERE s.ID_SEMESTRE = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function resultat(int $idPersonne, int $idSemestre): ?array {
        $stmt = Database::getConnection()->prepare("
            SELECT NOTE_PROVISOIRE, NOTE_FINALE, MENTION, STATUT_VALIDATION, DATE_CLOTURE
            FROM RESULTAT_SEMESTRIEL WHERE ID_PERSONNE = :id AND ID_SEMESTRE = :semestre
        ");
        $stmt->execute(['id' => $idPersonne, 'semestre' => $idSemestre]);
        return $stmt->fetch() ?: null;
    }

    public static function camarades(int $idPromo): array {
        $stmt = Database::getConnection()->prepare("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.MATRICULE
            FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE
            WHERE e.ID_PROMO = :promo AND p.STATUT_COMPTE = 'ACTIF'
            ORDER BY p.NOM, p.PRENOM
        ");
        $stmt->execute(['promo' => $idPromo]);
        return $stmt->fetchAll();
    }

    public static function clubs(): array {
        return Database::getConnection()->query("
            SELECT c.ID_CLUB, c.NOM_CLUB, c.DESCRIPTION, p.NOM AS RESP_NOM, p.PRENOM AS RESP_PRENOM,
                   (SELECT COUNT(*) FROM ETUDIANT e WHERE e.ID_CLUB = c.ID_CLUB) AS NB_MEMBRES
            FROM CLUB c LEFT JOIN PERSONNE p ON p.ID_PERSONNE = c.ID_RESPONSABLE
            ORDER BY c.NOM_CLUB
        ")->fetchAll();
    }

    public static function seancesAVenir(int $idClub): array {
        $stmt = Database::getConnection()->prepare("
            SELECT TITRE_SEANCE, DATE_SEANCE, HEURE_DEBUT, HEURE_FIN, LIEU
            FROM SEANCE WHERE ID_CLUB = :club AND DATE_SEANCE >= CURRENT_DATE()
            ORDER BY DATE_SEANCE, HEURE_DEBUT
        ");
        $stmt->execute(['club' => $idClub]);
        return $stmt->fetchAll();
    }

    private static function filtres(array $f, array &$params): string {
        $ou = ["p.ID_ROLE = (SELECT ID_ROLE FROM ROLE WHERE CODE_ROLE = 'ETUDIANT')"];
        if (($f['q'] ?? '') !== '') {
            $ou[] = "CONCAT_WS(' ', p.NOM, p.PRENOM, p.MATRICULE, p.EMAIL) LIKE :q";
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['promo'])) { $ou[] = "e.ID_PROMO = :promo"; $params['promo'] = (int)$f['promo']; }
        if (!empty($f['niveau'])) { $ou[] = "pr.ID_NIVEAU = :niveau"; $params['niveau'] = (int)$f['niveau']; }
        if (!empty($f['filiere'])) { $ou[] = "pr.ID_FILIERE = :filiere"; $params['filiere'] = (int)$f['filiere']; }
        if (!empty($f['statut'])) { $ou[] = "p.STATUT_COMPTE = :statut"; $params['statut'] = $f['statut']; }
        return implode(' AND ', $ou);
    }

    public static function rechercher(array $f, int $debut, int $limite): array {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT p.ID_PERSONNE, p.NOM, p.PRENOM, p.EMAIL, p.TELEPHONE, p.SEXE, p.PHOTO, p.MATRICULE, p.STATUT_COMPTE,
                   e.ID_ETUDIANT, e.EST_DELEGUE, pr.CODE_PROMO, n.LIBELLE_NIVEAU, f.NOM_FILIERE, c.NOM_CLUB
            FROM ETUDIANT e
            JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE
            JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            JOIN NIVEAU n ON n.ID_NIVEAU = pr.ID_NIVEAU
            JOIN FILIERE f ON f.ID_FILIERE = pr.ID_FILIERE
            LEFT JOIN CLUB c ON c.ID_CLUB = e.ID_CLUB
            WHERE " . self::filtres($f, $params) . "
            ORDER BY p.NOM, p.PRENOM
            LIMIT " . (int)$limite . " OFFSET " . (int)$debut . "
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function compter(array $f): int {
        $params = [];
        $stmt = Database::getConnection()->prepare("
            SELECT COUNT(*) FROM ETUDIANT e JOIN PERSONNE p ON p.ID_PERSONNE = e.ID_PERSONNE JOIN PROMOTION pr ON pr.ID_PROMO = e.ID_PROMO
            WHERE " . self::filtres($f, $params)
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function tous(array $f): array {
        return self::rechercher($f, 0, 100000);
    }

    public static function modifierInscription(int $idPersonne, int $idPromo, ?int $idClub, bool $delegue): void {
        $stmt = Database::getConnection()->prepare("UPDATE ETUDIANT SET ID_PROMO = :promo, ID_CLUB = :club, EST_DELEGUE = :delegue WHERE ID_PERSONNE = :id");
        $stmt->execute(['promo' => $idPromo, 'club' => $idClub, 'delegue' => $delegue ? 1 : 0, 'id' => $idPersonne]);
    }

    // Contrôle un fichier CSV (nom, prenom, email, telephone, sexe, date_naissance, adresse, code_promo) sans rien créer.
    public static function analyserCsv(string $chemin): array {
        $contenu = file_get_contents($chemin);
        if ($contenu === false) {
            throw new RuntimeException("Fichier illisible.");
        }
        if (str_starts_with($contenu, "\xEF\xBB\xBF")) {
            $contenu = substr($contenu, 3);
        }
        if (!mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }
        $lignes = preg_split('/\r\n|\r|\n/', trim($contenu));
        if (count($lignes) < 2) {
            throw new RuntimeException("Le fichier ne contient aucune ligne de données.");
        }
        $separateur = substr_count($lignes[0], ';') >= substr_count($lignes[0], ',') ? ';' : ',';
        $entetes = array_map(fn($h) => strtolower(trim($h)), str_getcsv($lignes[0], $separateur));
        $attendues = ['nom', 'prenom', 'email', 'telephone', 'sexe', 'date_naissance', 'adresse', 'code_promo'];
        $manquantes = array_diff($attendues, $entetes);
        if ($manquantes) {
            throw new RuntimeException("Colonnes manquantes : " . implode(', ', $manquantes) . ".");
        }
        $resultat = ['lignes' => [], 'erreurs' => []];
        $emailsVus = [];
        foreach (array_slice($lignes, 1) as $i => $brut) {
            $numero = $i + 2;
            if (trim($brut) === '') {
                continue;
            }
            $valeurs = str_getcsv($brut, $separateur);
            $ligne = [];
            foreach ($entetes as $k => $nom) {
                $ligne[$nom] = trim($valeurs[$k] ?? '');
            }
            $erreurs = [];
            foreach (['nom', 'prenom', 'email', 'telephone', 'sexe', 'date_naissance', 'code_promo'] as $champ) {
                if ($ligne[$champ] === '') { $erreurs[] = "$champ manquant"; }
            }
            if ($ligne['email'] !== '' && !filter_var($ligne['email'], FILTER_VALIDATE_EMAIL)) { $erreurs[] = "email invalide"; }
            if ($ligne['email'] !== '' && (Personne::emailExiste($ligne['email']) || isset($emailsVus[strtolower($ligne['email'])]))) { $erreurs[] = "email déjà utilisé"; }
            $emailsVus[strtolower($ligne['email'])] = true;
            $ligne['sexe'] = strtoupper(substr($ligne['sexe'], 0, 1));
            if (!in_array($ligne['sexe'], ['M', 'F'], true)) { $erreurs[] = "sexe attendu : M ou F"; }
            $date = DateTime::createFromFormat('Y-m-d', $ligne['date_naissance']) ?: DateTime::createFromFormat('d/m/Y', $ligne['date_naissance']);
            if (!$date) { $erreurs[] = "date de naissance invalide (AAAA-MM-JJ ou JJ/MM/AAAA)"; } else { $ligne['date_naissance'] = $date->format('Y-m-d'); }
            $promo = $ligne['code_promo'] !== '' ? Structure::promotionParCode($ligne['code_promo']) : null;
            if (!$promo) { $erreurs[] = "promotion inconnue"; } else { $ligne['id_promo'] = (int)$promo['ID_PROMO']; }
            $ligne['numero'] = $numero;
            $resultat['lignes'][] = $ligne;
            if ($erreurs) {
                $resultat['erreurs'][$numero] = implode(', ', $erreurs);
            }
        }
        return $resultat;
    }

    // Crée tous les étudiants d'un CSV déjà contrôlé ; tout ou rien.
    public static function creerEnMasse(array $lignes): array {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $crees = [];
            foreach ($lignes as $l) {
                $r = Personne::creerEtudiant(['nom' => $l['nom'], 'prenom' => $l['prenom'], 'email' => $l['email'], 'telephone' => $l['telephone'],
                    'sexe' => $l['sexe'], 'date_naissance' => $l['date_naissance'], 'adresse' => $l['adresse'], 'id_promo' => $l['id_promo'], 'id_club' => null]);
                $crees[] = ['nom' => $l['nom'], 'prenom' => $l['prenom'], 'email' => $l['email'], 'matricule' => $r['matricule'], 'motDePasse' => $r['motDePasse']];
            }
            $db->commit();
            return $crees;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
