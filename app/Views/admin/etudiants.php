<?php
// app/Views/admin/etudiants.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'ADMINISTRATEUR'])) {
    header('Location: ../auth/login.php?erreur=acces_interdit');
    exit();
}

//require_once __DIR__ . '/../../app/Controllers/EtudiantController.php';
//$controller = new EtudiantController();
//$etudiants = $controller->listerEtudiants();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Formation Humaine CBS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { display: flex; height: 100vh; background-color: #f8f9fa; overflow: hidden; }
        
        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: #1a1a1a;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px;
            overflow-y: auto;
            border-right: 4px solid #ff7f00;
        }
        .logo-box {
            background: white;
            padding: 10px;
            text-align: center;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .logo-box img { max-width: 100%; height: 50px; object-fit: contain; }
        
        .user-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
            margin-bottom: 20px;
        }
        .user-card img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .user-card .info h4 { font-size: 13px; color: #ff7f00; }
        .user-card .info p { font-size: 11px; color: #aaa; }

        .menu-section { margin-bottom: 15px; }
        .menu-section-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #777;
            letter-spacing: 1px;
            margin-bottom: 8px;
            padding-left: 8px;
        }
        .sidebar ul { list-style: none; }
        .sidebar ul li { margin-bottom: 4px; }
        .sidebar ul li a {
            color: #ccc;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 13px;
            transition: 0.2s;
        }
        .sidebar ul li a:hover, .sidebar ul li a.active {
            background-color: #ff7f00;
            color: white;
        }
        .sidebar-footer {
            font-size: 12px;
            color: #888;
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #333;
        }

        /* Contenu principal */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding: 30px;
        }
        
        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        h2 { color: #333; font-size: 20px; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eaeaea; font-size: 13px; vertical-align: middle; }
        th { background-color: #1a1a1a; color: white; }
        tr:hover { background-color: #f1f1f1; }
        
        .avatar-etudiant { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc; }
        
        /* Statuts dynamiques */
        .badge-statut {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: bold;
        }
        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot-online { background-color: #10b981; box-shadow: 0 0 4px #10b981; }
        .dot-offline { background-color: #9ca3af; }
        
        .btn-add { background: #ff7f00; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold; display: inline-block; }
        
        .alert-success { background: #def7ec; color: #03543f; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; border: 1px solid #b4f5d6; }
        .alert-error { background: #fde8e8; color: #c81e1e; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 13px; border: 1px solid #fbd5d5; }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div>
            <div class="logo-box">
                <img src="../../assets/images/images.jpeg" alt="Logo CBS">
            </div>
            <div class="user-card">
                <img src="https://via.placeholder.com/40" alt="Avatar">
                <div class="info">
                    <h4>Administrateur Principal</h4>
                    <p>Compte Admin • En ligne</p>
                </div>
            </div>

            <div class="menu-section">
                <ul>
                    <li><a href="dashboard.php">Tableau de bord</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Gestion Académique</div>
                <ul>
                    <li><a href="etudiants.php" class="active">Étudiants</a></li>
                    <li><a href="classes.php">Classes & Promotion</a></li>
                    <li><a href="annees.php">Année & Semestres</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Présences</div>
                <ul>
                    <li><a href="appel.php">Faire l'appel</a></li>
                    <li><a href="justificatifs.php">Justificatifs d'absence</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Discipline</div>
                <ul>
                    <li><a href="signalements.php">Signalement</a></li>
                    <li><a href="decisions.php">Décisions</a></li>
                    <li><a href="points.php">Mouvement des points</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Activités & Clubs</div>
                <ul>
                    <li><a href="clubs.php">Clubs</a></li>
                    <li><a href="seances.php">Séances / Activités</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Rapport</div>
                <ul>
                    <li><a href="rapports.php">Rapports & Statistiques</a></li>
                    <li><a href="export.php">Exportation</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Utilisateurs</div>
                <ul>
                    <li><a href="roles.php">Rôles & Permissions</a></li>
                    <li><a href="parametres.php">Paramètres système</a></li>
                    <li><a href="journal.php">Journal des activités</a></li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="../Controllers/AuthController.php?action=logout" style="color: #ff6b6b; text-decoration: none;">Déconnexion</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header-title">
            <h2>Gestion des Étudiants</h2>
            <a href="ajouter_etudiant.php" class="btn-add">+ Ajouter un étudiant</a>
        </div>
        
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert-success">
                <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert-error">
                <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Matricule</th>
                    <th>Nom & Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Promotion</th>
                    <th>Connexion</th>
                    <th>Statut Compte</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($etudiants)): ?>
                    <tr><td colspan="8" style="text-align: center; color: #777;">Aucun étudiant enregistré pour le moment.</td></tr>
                <?php else: ?>
                    <?php foreach ($etudiants as $etu): ?>
                        <?php 
                            // Gestion de l'image de profil (fallback si vide)
                            $photoPath = !empty($etu['PHOTO']) ? '../../' . ltrim($etu['PHOTO'], '/') : 'https://via.placeholder.com/35';
                            
                            // Logique de statut dynamique basée sur le journal de connexion (actif il y a moins de 15 minutes / 900 secondes)
                            $derniereConnexion = $etu['DERNIERE_CONNEXION'] ?? null;
                            $isOnline = (!empty($derniereConnexion) && (strtotime($derniereConnexion) > (time() - 900)));
                        ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($photoPath) ?>" alt="Photo" class="avatar-etudiant">
                            </td>
                            <td><?= htmlspecialchars($etu['MATRICULE']) ?></td>
                            <td><?= htmlspecialchars($etu['NOM'] . ' ' . $etu['PRENOM']) ?></td>
                            <td><?= htmlspecialchars($etu['EMAIL']) ?></td>
                            <td><?= htmlspecialchars($etu['TELEPHONE']) ?></td>
                            <td><?= htmlspecialchars($etu['CODE_PROMO']) ?></td>
                            <td>
                                <span class="badge-statut">
                                    <span class="dot <?= $isOnline ? 'dot-online' : 'dot-offline' ?>"></span>
                                    <?= $isOnline ? 'En ligne' : 'Hors ligne' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($etu['STATUT_COMPTE']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>