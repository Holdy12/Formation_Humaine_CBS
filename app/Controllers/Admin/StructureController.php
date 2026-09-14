<?php
// app/Controllers/Admin/StructureController.php — structure académique et clôture des semestres.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../Models/Etudiant.php';
require_once __DIR__ . '/../../Models/MouvementPoint.php';

class StructureController extends PersonnelController {
    private const ENTITES = [
        'annee'       => ['titre' => 'Années académiques', 'champs' => ['LIBELLE_ANNEE', 'DATE_DEBUT', 'DATE_FIN']],
        'semestre'    => ['titre' => 'Semestres',          'champs' => ['ID_ANNEE', 'CODE_SEMESTRE', 'LIBELLE_SEMESTRE', 'DATE_DEBUT', 'DATE_FIN']],
        'departement' => ['titre' => 'Départements',       'champs' => ['CODE_DEPT', 'NOM_DEPT']],
        'filiere'     => ['titre' => 'Filières',           'champs' => ['ID_DEPT', 'CODE_FILIERE', 'NOM_FILIERE']],
        'niveau'      => ['titre' => 'Niveaux',            'champs' => ['CODE_NIVEAU', 'LIBELLE_NIVEAU']],
        'promotion'   => ['titre' => 'Promotions',         'champs' => ['ID_ANNEE', 'ID_NIVEAU', 'ID_FILIERE', 'CODE_PROMO']],
    ];

    public function index(): void {
        $entite = $this->param('entite', 'annee');
        if (!isset(self::ENTITES[$entite])) {
            $entite = 'annee';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->enregistrer($entite);
        }
        $this->vue('structure/index', 'Structure académique', 'structure', [
            'entite'      => $entite,
            'entites'     => self::ENTITES,
            'lignes'      => Structure::lister($entite),
            'annees'      => Structure::lister('annee'),
            'niveaux'     => Structure::lister('niveau'),
            'filieres'    => Structure::lister('filiere'),
            'departements'=> Structure::lister('departement'),
            'semestres'   => Structure::lister('semestre'),
            'promotions'  => Structure::promotions(),
            'jeton'       => Auth::jeton(),
        ]);
    }

    private function enregistrer(string $entite): void {
        Auth::verifierJeton();
        $operation = $_POST['op'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $valeurs = [];
        foreach (self::ENTITES[$entite]['champs'] as $champ) {
            $valeur = trim($_POST[$champ] ?? '');
            $valeurs[$champ] = str_starts_with($champ, 'ID_') ? (int)$valeur : $valeur;
        }

        try {
            if ($operation === 'supprimer') {
                Structure::supprimer($entite, $id);
                Journal::ecrire('Structure', ucfirst($entite) . ' supprimé(e) : ' . $id);
                $this->retour('admin_structure', "Enregistrement supprimé.", true, ['entite' => $entite]);
            }
            foreach (self::ENTITES[$entite]['champs'] as $champ) {
                if ($champ !== 'LIBELLE_SEMESTRE' && ($valeurs[$champ] === '' || $valeurs[$champ] === 0)) {
                    $this->retour('admin_structure', "Tous les champs sont obligatoires.", false, ['entite' => $entite]);
                }
                if (str_starts_with($champ, 'DATE_') && !Format::dateValide($valeurs[$champ])) {
                    $this->retour('admin_structure', "Les dates doivent être au format AAAA-MM-JJ.", false, ['entite' => $entite]);
                }
            }
            if (isset($valeurs['DATE_DEBUT'], $valeurs['DATE_FIN']) && $valeurs['DATE_FIN'] <= $valeurs['DATE_DEBUT']) {
                $this->retour('admin_structure', "La date de fin doit être après la date de début.", false, ['entite' => $entite]);
            }
            if ($operation === 'modifier' && $id > 0) {
                Structure::modifier($entite, $id, $valeurs);
                Journal::ecrire('Structure', ucfirst($entite) . ' modifié(e) : ' . $id);
                $this->retour('admin_structure', "Enregistrement mis à jour.", true, ['entite' => $entite]);
            }
            Structure::creer($entite, $valeurs);
            Journal::ecrire('Structure', ucfirst($entite) . ' créé(e)');
            $this->retour('admin_structure', "Enregistrement ajouté.", true, ['entite' => $entite]);
        } catch (PDOException $e) {
            error_log('Structure : ' . $e->getMessage());
            $this->retour('admin_structure', "L'enregistrement a été refusé par la base de données : vérifiez les valeurs saisies.", false, ['entite' => $entite]);
        } catch (RuntimeException $e) {
            $this->retour('admin_structure', $e->getMessage(), false, ['entite' => $entite]);
        }
    }

    public function cloturer(): void {
        $this->exigerPost();
        Auth::exigerPermission('semestre.cloturer');
        $idSemestre = (int)($_POST['semestre'] ?? 0);
        $semestre = Structure::trouver('semestre', $idSemestre);
        if (!$semestre) {
            $this->retour('admin_structure', "Semestre introuvable.", false, ['entite' => 'semestre']);
        }
        if (Structure::semestreEstClos($idSemestre)) {
            $this->retour('admin_structure', "Ce semestre est déjà clôturé.", false, ['entite' => 'semestre']);
        }

        $etudiants = Etudiant::tous(['statut' => 'ACTIF']);
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("
                INSERT INTO RESULTAT_SEMESTRIEL (ID_SEMESTRE, ID_PERSONNE, NOTE_PROVISOIRE, NOTE_FINALE, MENTION, STATUT_VALIDATION, DATE_CLOTURE)
                VALUES (:semestre, :personne, :note1, :note2, :mention1, 'CLOTURE', NOW())
                ON DUPLICATE KEY UPDATE NOTE_PROVISOIRE = :note3, NOTE_FINALE = :note4, MENTION = :mention2, STATUT_VALIDATION = 'CLOTURE', DATE_CLOTURE = NOW()
            ");
            $soldes = MouvementPoint::soldesParEtudiant($semestre['DATE_DEBUT'], $semestre['DATE_FIN']);
            foreach ($etudiants as $e) {
                $solde = $soldes[(int)$e['ID_PERSONNE']] ?? MouvementPoint::calculer([]);
                $note = round($solde['solde'], 2);
                $mention = Structure::mention($note);
                $stmt->execute(['semestre' => $idSemestre, 'personne' => (int)$e['ID_PERSONNE'],
                                'note1' => $note, 'note2' => $note, 'note3' => $note, 'note4' => $note,
                                'mention1' => $mention, 'mention2' => $mention]);
            }
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $this->retour('admin_structure', "La clôture a échoué : " . $e->getMessage(), false, ['entite' => 'semestre']);
        }
        Journal::ecrire('Clôture', 'Semestre ' . ($semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE']) . ' clôturé pour ' . count($etudiants) . ' étudiant(s)');
        $this->retour('admin_structure', count($etudiants) . " résultat(s) figé(s). Les notes finales sont visibles par les étudiants.", true, ['entite' => 'semestre']);
    }

    public function rouvrir(): void {
        $this->exigerPost();
        Auth::exigerPermission('semestre.cloturer');
        $idSemestre = (int)($_POST['semestre'] ?? 0);
        $stmt = Database::getConnection()->prepare("
            UPDATE RESULTAT_SEMESTRIEL SET STATUT_VALIDATION = 'PROVISOIRE', DATE_CLOTURE = NULL WHERE ID_SEMESTRE = :id
        ");
        $stmt->execute(['id' => $idSemestre]);
        Journal::ecrire('Clôture', 'Semestre ' . $idSemestre . ' rouvert');
        $this->retour('admin_structure', "Semestre rouvert : les points peuvent de nouveau être modifiés.", true, ['entite' => 'semestre']);
    }
}
