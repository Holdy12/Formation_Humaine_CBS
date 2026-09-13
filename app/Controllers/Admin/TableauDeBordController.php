<?php
// app/Controllers/Admin/TableauDeBordController.php
require_once __DIR__ . '/../PersonnelController.php';

class TableauDeBordController extends PersonnelController {
    public function index(): void {
        $this->vue('tableau_de_bord/index', 'Tableau de bord', 'dashboard', [
            'aTraiter' => [],
        ]);
    }
}
