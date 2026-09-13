<?php
// app/Controllers/Admin/PointController.php — registre global des points et corrections.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/MouvementPoint.php';
require_once __DIR__ . '/../../Models/Structure.php';
require_once __DIR__ . '/../../Models/Bareme.php';
require_once __DIR__ . '/../../../core/Csv.php';

class PointController extends PersonnelController {
    private function filtres(): array {
        return ['q' => $this->param('q'), 'promo' => $this->param('promo'), 'domaine' => $this->param('domaine'),
                'sens' => $this->param('sens'), 'debut' => $this->param('debut'), 'fin' => $this->param('fin')];
    }

    public function registre(): void {
        $filtres = $this->filtres();
        $pagination = $this->pagination(MouvementPoint::compterRegistre($filtres));
        $this->vue('points/registre', 'Registre des points', 'points', [
            'mouvements' => MouvementPoint::registre($filtres, $pagination['debut'], $pagination['limite']),
            'filtres'    => $filtres,
            'pagination' => $pagination,
            'promotions' => Structure::promotions(),
            'domaines'   => Bareme::domaines(),
            'peutCorriger' => Auth::peut('points.corriger'),
            'jeton'      => Auth::jeton(),
        ]);
    }

    public function exporter(): void {
        $lignes = [];
        foreach (MouvementPoint::registre($this->filtres(), 0, 100000) as $m) {
            $valeur = ($m['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$m['NOMBRE_POINTS'];
            $lignes[] = [Format::dateHeure($m['DATE_MOUVEMENT']), $m['MATRICULE'], $m['NOM'] . ' ' . $m['PRENOM'], $m['CODE_PROMO'],
                         $m['NOM_DOMAINE'], $m['LIBELLE_CRITERE'], $m['MOTIF_MOUVEMENT'], number_format($valeur, 2, ',', ''),
                         $m['VALIDATEUR_NOM'] ? $m['VALIDATEUR_PRENOM'] . ' ' . $m['VALIDATEUR_NOM'] : '',
                         $m['ID_MOUVEMENT_CORRIGE'] ? 'correction' : ($m['CORRIGE'] ? 'corrigé' : '')];
        }
        Journal::ecrire('Export', 'Registre des points (' . count($lignes) . ' lignes)');
        Csv::envoyer('registre-points', ['Date', 'Matricule', 'Étudiant', 'Promotion', 'Domaine', 'Critère', 'Motif', 'Points', 'Validé par', 'Observation'], $lignes);
    }

    public function corriger(): void {
        $this->exigerPost();
        Auth::exigerPermission('points.corriger');
        $id = (int)($_POST['mouvement'] ?? 0);
        $motif = trim($_POST['motif'] ?? '');
        if ($motif === '') {
            $this->retour('admin_points', "Indiquez le motif de la correction.", false);
        }
        try {
            MouvementPoint::corriger($id, $this->id(), $motif);
        } catch (Exception $e) {
            $this->retour('admin_points', $e->getMessage(), false);
        }
        Journal::ecrire('Points', 'Correction du mouvement ' . $id . ' : ' . $motif);
        $this->retour('admin_points', "Correction enregistrée : une écriture inverse a été ajoutée au registre.");
    }
}
