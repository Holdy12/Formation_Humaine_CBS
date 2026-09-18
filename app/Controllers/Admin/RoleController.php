<?php
// app/Controllers/Admin/RoleController.php

require_once __DIR__ . '/../PersonnelController.php';
require_once __DIR__ . '/../../Models/Role.php'; // Vérifie bien que ton fichier s'appelle Role.php dans app/Models/

class RoleController extends PersonnelController {

    public function index(): void {
        // 1. Récupération des données métiers
        $roles = Role::tous(); 

        // 2. Utilisation de la méthode vue() héritée de PersonnelController
        $this->vue('roles/index', 'Gestion des rôles', 'admin_roles', [
            'roles' => $roles
        ]);
    }

    public function creer(): void {
    // Vérifie les permissions si nécessaire
    // Auth::exigerPermission('roles.gerer');

    $message = null;
    $succes = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Auth::verifierJeton(); 
        
        $libelle = trim($_POST['libelle'] ?? '');

        if ($libelle === '') {
            $succes = false;
            $message = "Le libellé du rôle ne peut pas être vide.";
        } else {
            try {
                $cree = Role::creer($libelle);
                if ($cree) {
                    $succes = true;
                    $message = "Le rôle a été créé avec succès.";
                } else {
                    $succes = false;
                    $message = "Erreur : l'insertion en base de données a échoué.";
                }
            } catch (\Exception $e) {
                $succes = false;
                // Affiche la vraie erreur technique SQL
                $message = "Erreur SQL : " . $e->getMessage();
            }
        }
    }

   // On passe le jeton à la vue (sans le "admin/" au début si le dossier est déjà géré)
    $this->vue('roles/creer', 'Créer un rôle', 'admin', [
        'message' => $message,
        'succes'  => $succes,
        'jeton'   => Auth::jeton()
    ]);
}
}