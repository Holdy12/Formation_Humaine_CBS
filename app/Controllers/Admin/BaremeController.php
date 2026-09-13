<?php
// app/Controllers/Admin/BaremeController.php — barème des critères et paramètres système.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Bareme.php';

class BaremeController extends PersonnelController {
    public function index(): void {
        $this->vue('bareme/index', 'Barème et paramètres', 'bareme', [
            'domaines'   => Bareme::domaines(),
            'criteres'   => Bareme::criteres(),
            'parametres' => Bareme::parametres(),
            'jeton'      => Auth::jeton(),
        ]);
    }

    public function critere(): void {
        $this->exigerPost();
        $id = (int)($_POST['id'] ?? 0);
        $libelle = trim($_POST['libelle'] ?? '');
        $valeur = str_replace(',', '.', trim($_POST['valeur'] ?? ''));
        $actif = !empty($_POST['actif']);
        if ($libelle === '' || mb_strlen($libelle) > 100 || !is_numeric($valeur) || (float)$valeur == 0) {
            $this->retour('admin_bareme', "Indiquez un libellé et une valeur non nulle en points, négative pour une pénalité.", false);
        }
        if ($id > 0) {
            Bareme::modifierCritere($id, $libelle, (float)$valeur, $actif);
            Journal::ecrire('Barème', 'Critère modifié : ' . $libelle . ' (' . $valeur . ')');
            $this->retour('admin_bareme', "Critère mis à jour. Les mouvements déjà enregistrés conservent leur valeur.");
        }
        $idDomaine = (int)($_POST['domaine'] ?? 0);
        if ($idDomaine === 0) {
            $this->retour('admin_bareme', "Choisissez un domaine.", false);
        }
        Bareme::creerCritere($idDomaine, $libelle, (float)$valeur);
        Journal::ecrire('Barème', 'Critère créé : ' . $libelle . ' (' . $valeur . ')');
        $this->retour('admin_bareme', "Critère ajouté au barème.");
    }

    public function domaine(): void {
        $this->exigerPost();
        $id = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        if ($id === 0 || $nom === '') {
            $this->retour('admin_bareme', "Le nom du domaine est obligatoire.", false);
        }
        Bareme::renommerDomaine($id, $nom);
        Journal::ecrire('Barème', 'Domaine renommé : ' . $nom);
        $this->retour('admin_bareme', "Domaine renommé.");
    }

    public function parametres(): void {
        $this->exigerPost();
        $valeurs = (array)($_POST['valeur'] ?? []);
        $modifies = 0;
        foreach (Bareme::parametres() as $p) {
            $code = $p['CODE_PARAMETRE'];
            if (!array_key_exists($code, $valeurs)) {
                continue;
            }
            $valeur = str_replace(',', '.', trim((string)$valeurs[$code]));
            if ($valeur === '' || !is_numeric($valeur) || (float)$valeur < 0) {
                $this->retour('admin_bareme', "La valeur de « " . $p['DESCRIPTION'] . " » doit être un nombre positif.", false);
            }
            if ((float)$valeur !== (float)$p['VALEUR']) {
                Bareme::modifierParametre($code, $valeur);
                $modifies++;
            }
        }
        if ($modifies > 0) {
            Journal::ecrire('Paramètres', $modifies . ' paramètre(s) modifié(s)');
        }
        $this->retour('admin_bareme', $modifies > 0 ? "Paramètres enregistrés." : "Aucun paramètre n'a changé.");
    }
}
