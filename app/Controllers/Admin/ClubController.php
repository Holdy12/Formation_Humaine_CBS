<?php
// app/Controllers/Admin/ClubController.php — clubs, responsables et membres.
require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Club.php';
require_once __DIR__ . '/../../Models/Presence.php';
require_once __DIR__ . '/../../Models/Etudiant.php';

class ClubController extends PersonnelController {
    public function liste(): void {
        $tous = Auth::peut('clubs.gerer');
        $this->vue('clubs/liste', 'Clubs', 'clubs', [
            'clubs'         => $tous ? Club::lister() : Club::animesPar($this->id()),
            'peutGerer'     => $tous,
            'responsables'  => $tous ? Club::responsablesPossibles() : [],
            'jeton'         => Auth::jeton(),
        ]);
    }

    public function club(): void {
        $club = $this->charger();
        $this->vue('clubs/club', $club['NOM_CLUB'], 'clubs', [
            'club'          => $club,
            'membres'       => Club::membres((int)$club['ID_CLUB']),
            'candidats'     => Club::etudiantsSansClub(),
            'seances'       => Presence::seances(['club' => (int)$club['ID_CLUB']], 0, 10),
            'peutGerer'     => Auth::peut('clubs.gerer'),
            'responsables'  => Auth::peut('clubs.gerer') ? Club::responsablesPossibles() : [],
            'jeton'         => Auth::jeton(),
        ]);
    }

    public function enregistrer(): void {
        $this->exigerPost();
        Auth::exigerPermission('clubs.gerer');
        $id = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $responsable = (int)($_POST['responsable'] ?? 0) ?: null;
        if ($nom === '') {
            $this->retour('admin_clubs', "Le nom du club est obligatoire.", false);
        }
        unset($_SESSION['anime_un_club']);
        if ($id > 0) {
            Club::modifier($id, $nom, $description, $responsable);
            Journal::ecrire('Club', 'Club modifié : ' . $nom);
            $this->retour('admin_club', "Club mis à jour.", true, ['id' => $id]);
        }
        $nouveau = Club::creer($nom, $description, $responsable);
        Journal::ecrire('Club', 'Club créé : ' . $nom);
        $this->retour('admin_club', "Club créé.", true, ['id' => $nouveau]);
    }

    public function membre(): void {
        $this->exigerPost();
        $club = $this->charger((int)($_POST['club'] ?? 0));
        $idPersonne = (int)($_POST['etudiant'] ?? 0);
        $etudiant = Etudiant::fiche($idPersonne);
        if (!$etudiant) {
            $this->retour('admin_club', "Étudiant introuvable.", false, ['id' => (int)$club['ID_CLUB']]);
        }
        if (($_POST['op'] ?? '') === 'retirer') {
            Club::retirerMembre((int)$club['ID_CLUB'], $idPersonne);
            Journal::ecrire('Club', $etudiant['MATRICULE'] . ' retiré du club ' . $club['NOM_CLUB']);
            $this->retour('admin_club', "Membre retiré du club.", true, ['id' => (int)$club['ID_CLUB']]);
        }
        Club::ajouterMembre((int)$club['ID_CLUB'], $idPersonne);
        Journal::ecrire('Club', $etudiant['MATRICULE'] . ' ajouté au club ' . $club['NOM_CLUB']);
        $this->retour('admin_club', "Membre ajouté au club.", true, ['id' => (int)$club['ID_CLUB']]);
    }

    private function charger(?int $id = null): array {
        $id = $id ?? (int)$this->param('id', '0');
        $club = $id > 0 ? Club::trouver($id) : null;
        if (!$club) {
            Erreur::afficher(404, "Club introuvable", "Aucun club ne correspond à cet identifiant.", "Retour aux clubs", 'index.php?action=admin_clubs');
        }
        if (!Auth::peut('clubs.gerer') && !Club::estAnimePar($id, $this->id())) {
            Erreur::interdit("Retour aux clubs", 'index.php?action=admin_clubs');
        }
        return $club;
    }
}
