<?php
// app/Controllers/Admin/RapportController.php — rapports par semestre et exports CSV.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Rapport.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../../core/Csv.php';

class RapportController extends PersonnelController {
    private function semestreChoisi(): array {
        $semestres = Structure::lister('semestre');
        if (empty($semestres)) {
            Erreur::afficher(404, "Aucun semestre", "Créez d'abord un semestre dans la structure académique.", "Structure académique", 'index.php?action=admin_structure&entite=semestre');
        }
        $id = (int)$this->param('semestre', '0');
        foreach ($semestres as $s) {
            if ((int)$s['ID_SEMESTRE'] === $id) {
                return [$s, $semestres];
            }
        }
        $courant = Structure::semestrePourDate(date('Y-m-d'));
        return [$courant ?: $semestres[0], $semestres];
    }

    public function index(): void {
        [$semestre, $semestres] = $this->semestreChoisi();
        $idPromo = (int)$this->param('promo', '0') ?: null;
        $soldes = Rapport::soldes($semestre, $idPromo);
        $this->vue('rapports/index', 'Rapports', 'rapports', [
            'semestre'    => $semestre,
            'semestres'   => $semestres,
            'promotions'  => Structure::promotions(),
            'idPromo'     => $idPromo,
            'soldes'      => $soldes,
            'synthese'    => Rapport::synthese($soldes),
            'domaines'    => Rapport::signalementsParDomaine($semestre),
            'criteres'    => Rapport::criteresLesPlusFrequents($semestre),
            'assiduite'   => Rapport::assiduiteParPromotion($semestre),
            'clos'        => Structure::semestreEstClos((int)$semestre['ID_SEMESTRE']),
        ]);
    }

    public function exporter(): void {
        [$semestre] = $this->semestreChoisi();
        $type = $this->param('type', 'soldes');
        $idPromo = (int)$this->param('promo', '0') ?: null;
        $libelle = $semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE'];

        if ($type === 'signalements') {
            $lignes = array_map(fn($d) => [$d['NOM_DOMAINE'], $d['TOTAL'], $d['VALIDES'], $d['REJETES'], $d['EN_COURS'], $d['CONSEILS']], Rapport::signalementsParDomaine($semestre));
            Journal::ecrire('Export', 'Signalements par domaine, ' . $libelle);
            Csv::envoyer('signalements-' . $semestre['CODE_SEMESTRE'], ['Domaine', 'Signalements', 'Validés', 'Rejetés', 'En cours', 'Conseils de discipline'], $lignes);
        }
        if ($type === 'assiduite') {
            $lignes = array_map(fn($a) => [$a['CODE_PROMO'], $a['RELEVES'], $a['PRESENTS'], $a['RETARDS'], $a['ABSENCES'], $a['JUSTIFIEES']], Rapport::assiduiteParPromotion($semestre));
            Journal::ecrire('Export', 'Assiduité par promotion, ' . $libelle);
            Csv::envoyer('assiduite-' . $semestre['CODE_SEMESTRE'], ['Promotion', 'Relevés', 'Présents', 'Retards', 'Absences', 'Absences justifiées'], $lignes);
        }
        $lignes = [];
        foreach (Rapport::soldes($semestre, $idPromo) as $s) {
            $lignes[] = [$s['MATRICULE'], $s['NOM'], $s['PRENOM'], $s['CODE_PROMO'], number_format($s['PENALITES'], 2, ',', ''),
                         number_format($s['BONUS'], 2, ',', ''), number_format($s['SOLDE'], 2, ',', ''), $s['MENTION']];
        }
        Journal::ecrire('Export', 'Soldes, ' . $libelle . ' (' . count($lignes) . ' étudiants)');
        Csv::envoyer('soldes-' . $semestre['CODE_SEMESTRE'], ['Matricule', 'Nom', 'Prénom', 'Promotion', 'Pénalités', 'Bonifications', 'Note', 'Mention'], $lignes);
    }
}
