<?php
// app/Controllers/Admin/JournalController.php — consultation du journal d'audit.
require_once __DIR__ . '/../PersonnelController.php';

class JournalController extends PersonnelController {
    public function index(): void {
        $filtres = ['q' => $this->param('q'), 'action' => $this->param('action_journal'), 'statut' => $this->param('statut'),
                    'debut' => $this->param('debut'), 'fin' => $this->param('fin')];
        $pagination = $this->pagination(Journal::compter($filtres), 40);
        $this->vue('journal/index', 'Journal', 'journal', [
            'entrees'    => Journal::lire($filtres, $pagination['debut'], $pagination['limite']),
            'filtres'    => $filtres,
            'actions'    => Journal::actions(),
            'pagination' => $pagination,
        ]);
    }
}
