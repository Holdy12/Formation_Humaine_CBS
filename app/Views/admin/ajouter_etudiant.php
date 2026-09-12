<?php
// app/Views/admin/ajouter_etudiant.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'ADMINISTRATEUR'])) {
    header('Location: ../auth/login.php?erreur=acces_interdit');
    exit();
}

$promotions = $data['promotions'] ?? [];
$clubs = $data['clubs'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Étudiant - Formation Humaine CBS</title>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    
    <!-- Barre latérale (Sidebar) -->
    <div class="sidebar">
        <div>
            <div class="logo-box">
                <img src="/assets/images/images.jpeg" alt="Logo CBS">
            </div>
            <div class="user-card">
                <img src="/assets/images/images.jpeg" alt="Avatar">
                <div class="info">
                    <h4><?= htmlspecialchars($_SESSION['nom'] ?? 'Admin') ?> <?= htmlspecialchars($_SESSION['prenom'] ?? '') ?></h4>
                    <p><span class="online-dot"></span> Compte Admin • En ligne</p>
                </div>
            </div>

            <div class="menu-section">
                <ul>
                    <li><a href="index.php?action=dashboard">📊 Tableau de bord</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Gestion Académique</div>
                <ul>
                    <li><a href="index.php?action=etudiants" class="active">🎓 Étudiants</a></li>
                    <li><a href="index.php?action=classes">🏫 Classes & Promotion</a></li>
                    <li><a href="index.php?action=annees">📅 Année & Semestres</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Présences</div>
                <ul>
                    <li><a href="index.php?action=appel">📋 Faire l'appel</a></li>
                    <li><a href="index.php?action=justificatifs">📄 Justificatifs d'absence</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Discipline</div>
                <ul>
                    <li><a href="index.php?action=signalements">🚩 Signalement</a></li>
                    <li><a href="index.php?action=decisions">📌 Décisions</a></li>
                    <li><a href="index.php?action=points">⭐ Mouvement des points</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Activités & Clubs</div>
                <ul>
                    <li><a href="index.php?action=clubs">⚽ Clubs</a></li>
                    <li><a href="index.php?action=seances">🕒 Séances / Activités</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Rapport</div>
                <ul>
                    <li><a href="index.php?action=rapports">📈 Rapports & Statistiques</a></li>
                    <li><a href="index.php?action=export">📥 Exportation</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Utilisateurs</div>
                <ul>
                    <li><a href="index.php?action=roles">🔐 Rôles & Permissions</a></li>
                    <li><a href="index.php?action=parametres">⚙️ Paramètres système</a></li>
                    <li><a href="index.php?action=journal">📜 Journal des activités</a></li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <a href="index.php?action=logout" style="color: #ff6b6b; text-decoration: none; font-weight: bold;">🚪 Déconnexion</a>
        </div>
    </div>
    
    <!-- Contenu Principal -->
    <div class="main-content">
        <div class="topbar-custom">
            <div style="font-weight: 600; color: var(--text-main);">Enregistrement d'un nouvel étudiant</div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <button onclick="toggleTheme()" class="filter-btn" id="themeToggleBtn" title="Changer de thème">🌙 Mode Sombre</button>
                <div class="topbar-admin">
                    <img src="/assets/images/images.jpeg" alt="Admin">
                    <span><?= htmlspecialchars($_SESSION['nom'] ?? 'Administrateur') ?></span>
                </div>
            </div>
        </div>
        
        <div class="content-body">
            <div class="dashboard-header">
                <div>
                    <h2>Ajouter un Étudiant</h2>
                    <p>Inscrire un nouvel étudiant et générer ses accès automatiques</p>
                </div>
                <div class="filters">
                    <a href="index.php?action=etudiants" class="filter-btn" style="text-decoration: none; display: inline-block; background-color: #64748b; color: white;">
                        ⬅️ Retour
                    </a>
                </div>
            </div>

            <?php if (!empty($_SESSION['error_message'])): ?>
                <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.3);">
                    <?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout -->
            <div class="dashboard-card" style="margin-top: 20px; max-width: 900px; margin-left: auto; margin-right: auto;">
                <form action="index.php?action=store" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Nom</label>
                            <input type="text" name="nom" required placeholder="Ex: Dupont" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Prénom</label>
                            <input type="text" name="prenom" required placeholder="Ex: Jean" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Email</label>
                            <input type="email" name="email" required placeholder="jean.dupont@cbs.edu" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Téléphone</label>
                            <input type="text" name="telephone" placeholder="Ex: +235 60..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Sexe</label>
                            <select name="sexe" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                                <option value="M">Masculin</option>
                                <option value="F">Féminin</option>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Date de Naissance</label>
                            <input type="date" name="date_naissance" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Promotion</label>
                            <select name="id_promo" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                                <option value="">-- Choisir une promotion --</option>
                                <?php foreach ($promotions as $promo): ?>
                                    <option value="<?= $promo['ID_PROMO'] ?>"><?= htmlspecialchars($promo['CODE_PROMO']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Club</label>
                            <select name="id_club" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                                <option value="">-- Aucun club --</option>
                                <?php foreach ($clubs as $club): ?>
                                    <option value="<?= $club['ID_CLUB'] ?>"><?= htmlspecialchars($club['NOM_CLUB']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Adresse</label>
                            <input type="text" name="adresse" placeholder="Quartier, Ville..." style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                        </div>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 500; margin-bottom: 8px; color: var(--text-main);">Photo de profil</label>
                        <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); color: var(--text-main);">
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                        <a href="index.php?action=etudiants" class="filter-btn" style="text-decoration: none; background: #e2e8f0; color: #1e293b; padding: 10px 20px; border-radius: 8px;">Annuler</a>
                        <button type="submit" class="filter-btn" style="background-color: #ff7f00; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">Enregistrer l'étudiant</button>
                    </div>

                </form>
            </div>

        </div>
    </div>

    <script src="/assets/js/dashboard.js"></script>
</body>
</html>