<?php
// app/Controllers/Admin/CompteController.php — comptes du personnel et espace « Mon compte ».
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Personne.php';
require_once __DIR__ . '/../../Models/Club.php';

class CompteController extends PersonnelController {
    public function liste(): void {
        $q = $this->param('q');
        $this->vue('comptes/liste', 'Comptes du personnel', 'comptes', [
            'comptes'     => Personne::personnel($q),
            'q'           => $q,
            'roles'       => Personne::roles(),
            'permissions' => Permissions::matrice(),
            'libelles'    => Permissions::libelles(),
            'jeton'       => Auth::jeton(),
        ]);
    }

    public function enregistrer(): void {
        $this->exigerPost();
        $id = (int)($_POST['id'] ?? 0);
        $d = [
            'id_role'   => (int)($_POST['role'] ?? 0),
            'nom'       => mb_strtoupper(trim($_POST['nom'] ?? '')),
            'prenom'    => trim($_POST['prenom'] ?? ''),
            'email'     => mb_strtolower(trim($_POST['email'] ?? '')),
            'telephone' => trim($_POST['telephone'] ?? ''),
            'sexe'      => ($_POST['sexe'] ?? 'M') === 'F' ? 'F' : 'M',
        ];
        $rolesValides = array_map(fn($r) => (int)$r['ID_ROLE'], Personne::roles());
        if ($d['nom'] === '' || $d['prenom'] === '' || $d['telephone'] === '' || !filter_var($d['email'], FILTER_VALIDATE_EMAIL) || !in_array($d['id_role'], $rolesValides, true)) {
            $this->retour('admin_comptes', "Nom, prénom, téléphone, email valide et rôle sont obligatoires.", false);
        }
        if (Personne::emailExiste($d['email'], $id ?: null)) {
            $this->retour('admin_comptes', "Cet email est déjà utilisé par un autre compte.", false);
        }
        if ($id > 0) {
            $compte = Personne::trouver($id);
            if (!$compte || $compte['CODE_ROLE'] === 'ETUDIANT') {
                $this->retour('admin_comptes', "Compte introuvable.", false);
            }
            if ($id === $this->id() && $d['id_role'] !== (int)$compte['ID_ROLE']) {
                $this->retour('admin_comptes', "Vous ne pouvez pas changer votre propre rôle.", false);
            }
            Personne::modifierPersonnel($id, $d);
            unset($_SESSION['anime_un_club']);
            Journal::ecrire('Compte', 'Compte modifié : ' . $d['prenom'] . ' ' . $d['nom']);
            $this->retour('admin_comptes', "Compte mis à jour.");
        }
        $resultat = Personne::creerPersonnel($d);
        Journal::ecrire('Compte', 'Compte créé : ' . $d['prenom'] . ' ' . $d['nom']);
        $_SESSION['mot_de_passe_temporaire'] = ['nom' => $d['prenom'] . ' ' . $d['nom'], 'identifiant' => $d['email'], 'mdp' => $resultat['motDePasse']];
        $this->retour('admin_comptes', "Compte créé. Communiquez le mot de passe temporaire affiché ci-dessous : il ne sera plus visible ensuite.");
    }

    public function statut(): void {
        $this->exigerPost();
        $id = (int)($_POST['id'] ?? 0);
        $compte = Personne::trouver($id);
        if (!$compte || $compte['CODE_ROLE'] === 'ETUDIANT') {
            $this->retour('admin_comptes', "Compte introuvable.", false);
        }
        if ($id === $this->id()) {
            $this->retour('admin_comptes', "Vous ne pouvez pas désactiver votre propre compte.", false);
        }
        $nouveau = $compte['STATUT_COMPTE'] === 'ACTIF' ? 'INACTIF' : 'ACTIF';
        if ($nouveau === 'INACTIF' && $compte['CODE_ROLE'] === 'ADMIN' && Personne::compterAdminsActifs() <= 1) {
            $this->retour('admin_comptes', "Impossible de désactiver le dernier administrateur actif.", false);
        }
        Personne::changerStatut($id, $nouveau);
        Journal::ecrire('Compte', 'Compte ' . ($nouveau === 'ACTIF' ? 'réactivé' : 'désactivé') . ' : ' . $compte['PRENOM'] . ' ' . $compte['NOM']);
        $this->retour('admin_comptes', $nouveau === 'ACTIF' ? "Compte réactivé." : "Compte désactivé : la connexion est refusée.");
    }

    public function reinitialiser(): void {
        $this->exigerPost();
        $id = (int)($_POST['id'] ?? 0);
        $compte = Personne::trouver($id);
        if (!$compte || $compte['CODE_ROLE'] === 'ETUDIANT') {
            $this->retour('admin_comptes', "Compte introuvable.", false);
        }
        $mdp = Personne::reinitialiser($id);
        Journal::ecrire('Compte', 'Mot de passe réinitialisé : ' . $compte['PRENOM'] . ' ' . $compte['NOM']);
        $_SESSION['mot_de_passe_temporaire'] = ['nom' => $compte['PRENOM'] . ' ' . $compte['NOM'], 'identifiant' => $compte['EMAIL'], 'mdp' => $mdp];
        $this->retour('admin_comptes', "Mot de passe réinitialisé. Communiquez le mot de passe temporaire ci-dessous.");
    }

    public function monCompte(): void {
        $this->vue('comptes/mon_compte', 'Mon compte', 'mon_compte', [
            'compte'      => Personne::trouver($this->id()),
            'clubs'       => Club::animesPar($this->id()),
            'permissions' => Permissions::permissionsDuRole(Auth::role()),
            'libelles'    => Permissions::libelles(),
            'jeton'       => Auth::jeton(),
        ]);
    }

    public function coordonnees(): void {
        $this->exigerPost();
        $telephone = trim($_POST['telephone'] ?? '');
        if ($telephone === '' || mb_strlen($telephone) > 100) {
            $this->retour('admin_mon_compte', "Le téléphone est obligatoire et limité à 100 caractères.", false);
        }
        Personne::modifierCoordonnees($this->id(), $telephone, null);
        $this->retour('admin_mon_compte', "Coordonnées mises à jour.");
    }

    public function motDePasse(): void {
        $this->exigerPost();
        $actuel = $_POST['actuel'] ?? '';
        $nouveau = $_POST['nouveau'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';
        if (!password_verify($actuel, Personne::motDePasseHash($this->id()))) {
            $this->retour('admin_mon_compte', "Le mot de passe actuel est incorrect.", false);
        }
        if (strlen($nouveau) < 8 || !preg_match('/[A-Z]/', $nouveau) || !preg_match('/[0-9]/', $nouveau)) {
            $this->retour('admin_mon_compte', "Le nouveau mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.", false);
        }
        if ($nouveau !== $confirmation) {
            $this->retour('admin_mon_compte', "La confirmation ne correspond pas au nouveau mot de passe.", false);
        }
        Personne::changerMotDePasse($this->id(), $nouveau);
        Journal::ecrire('Compte', 'Mot de passe modifié');
        $this->retour('admin_mon_compte', "Mot de passe modifié.");
    }

    public function photo(): void {
        $this->exigerPost();
        $compte = Personne::trouver($this->id());
        if (!empty($_POST['supprimer'])) {
            Fichier::supprimerPublic($compte['PHOTO']);
            Personne::modifierPhoto($this->id(), null);
            $this->retour('admin_mon_compte', "Photo supprimée.");
        }
        if (!Fichier::estPresent($_FILES['photo'] ?? [])) {
            $this->retour('admin_mon_compte', "Choisissez une photo.", false);
        }
        try {
            $chemin = Fichier::enregistrerPublic($_FILES['photo'], 'assets/uploads', ['jpg', 'jpeg', 'png', 'webp']);
        } catch (Exception $e) {
            $this->retour('admin_mon_compte', $e->getMessage(), false);
        }
        Fichier::supprimerPublic($compte['PHOTO']);
        Personne::modifierPhoto($this->id(), $chemin);
        $this->retour('admin_mon_compte', "Photo mise à jour.");
    }
}
