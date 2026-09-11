<?php
// app/Views/admin/dashboard.php
// La sécurité et les données ($data) sont transmises par le routeur public/index.php et AdminDashboardController.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Formation Humaine CBS</title>
    <!-- Intégration de Chart.js pour les graphiques professionnels -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/assets/css/dashboard.css">
</head>
<body>
    
    <!-- Barre latérale (Sidebar) avec icônes explicites -->
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
                    <li><a href="index.php?action=dashboard" class="active">📊 Tableau de bord</a></li>
                </ul>
            </div>

            <div class="menu-section">
                <div class="menu-section-title">Gestion Académique</div>
                <ul>
                    <li><a href="index.php?action=etudiants">🎓 Étudiants</a></li>
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
            <input type="text" class="search-bar" placeholder="🔍 Rechercher un étudiant, une classe, un signalement">
            <div style="display: flex; align-items: center; gap: 15px;">
                <!-- Bouton Changement de Thème -->
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
                    <h2>Tableau de bord</h2>
                    <p>Vue d'ensemble de la plateforme CBS - Formation Humaine</p>
                </div>
                <!-- Filtres dynamiques adaptés pour l'année et les semestres vides/actifs -->
                <div class="filters">
                    <form method="GET" action="index.php" style="display: flex; gap: 10px; align-items: center;">
                        <input type="hidden" name="action" value="dashboard">
                        
                        <!-- Sélecteur d'Année Académique -->
                        <select name="annee" class="filter-btn" style="background: var(--bg-card, #fff); color: inherit; border: 1px solid var(--border-color, #ccc); cursor: pointer;" onchange="this.form.submit()">
                            <option value="active" <?= ($data['annee_actuelle'] ?? '') === 'active' ? 'selected' : '' ?>>📅 Année en cours</option>
                            <?php if (!empty($data['liste_annees']) && is_array($data['liste_annees'])): ?>
                                <?php foreach ($data['liste_annees'] as $annee): ?>
                                    <option value="<?= $annee['ID_ANNEE'] ?>" <?= (string)($data['annee_actuelle'] ?? '') === (string)$annee['ID_ANNEE'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($annee['LIBELLE_ANNEE']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Aucune année enregistrée</option>
                            <?php endif; ?>
                        </select>

                        <!-- Sélecteur de Semestre -->
                        <select name="semestre" class="filter-btn" style="background: var(--bg-card, #fff); color: inherit; border: 1px solid var(--border-color, #ccc); cursor: pointer;" onchange="this.form.submit()">
                            <option value="actif" <?= ($data['semestre_actuel'] ?? '') === 'actif' ? 'selected' : '' ?>>⏱️ Semestre Actif</option>
                            <option value="1" <?= ($data['semestre_actuel'] ?? '') === '1' ? 'selected' : '' ?>>⏱️ Semestre 1</option>
                            <option value="2" <?= ($data['semestre_actuel'] ?? '') === '2' ? 'selected' : '' ?>>⏱️ Semestre 2</option>
                        </select>

                        <button type="button" class="filter-btn btn-refresh" onclick="window.location.reload();" title="Actualiser">🔄</button>
                    </form>
                </div>
            </div>

            <!-- Cartes Statistiques dynamiques -->
            <div class="stats-grid">
                <a href="index.php?action=etudiants" class="stat-card">
                    <span class="stat-badge badge-success">+4%</span>
                    <div class="stat-value"><?= number_format($data['etudiants'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="stat-title">🎓 Étudiants inscrits (Voir tous)</div>
                </a>
                <a href="index.php?action=roles" class="stat-card">
                    <span class="stat-badge badge-success">+2</span>
                    <div class="stat-value"><?= number_format($data['utilisateurs'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="stat-title">👥 Comptes utilisateurs (Voir tous)</div>
                </a>
                <a href="index.php?action=roles" class="stat-card">
                    <span class="stat-badge badge-success">Stable</span>
                    <div class="stat-value"><?= number_format($data['roles'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="stat-title">🏛️ Rôles définis</div>
                </a>
                <a href="index.php?action=classes" class="stat-card">
                    <span class="stat-badge badge-success">Actif</span>
                    <div class="stat-value"><?= number_format($data['classes'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="stat-title">🏫 Classes / Promotions</div>
                </a>
                <a href="index.php?action=signalements" class="stat-card">
                    <span class="stat-badge badge-danger">-5%</span>
                    <div class="stat-value" style="color: #ef4444;"><?= number_format($data['signalements'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="stat-title">🚩 Signalements (Voir ensemble)</div>
                </a>
                <a href="index.php?action=decisions" class="stat-card">
                    <span class="stat-badge badge-danger">Urgent</span>
                    <div class="stat-value" style="color: #ef4444;"><?= number_format($data['decisions'] ?? 0, 0, ',', ' ') ?></div>
                    <div class="stat-title">📌 Décisions récentes</div>
                </a>
            </div>

            <!-- Graphiques & Statistiques Avancées -->
            <div class="dashboard-row">
                <!-- Graphique Répartition des points -->
                <div class="dashboard-card">
                    <div class="chart-header-container">
                        <h3 style="margin-bottom:0;">📊 Répartition globale des points</h3>
                        <button class="hamburger-btn" onclick="toggleLinksMenu()" title="Afficher/Masquer les liens rapides">
                            ☰ 
                        </button>
                    </div>

                    <div id="collapsibleMenu" class="collapsible-links">
                        <a href="index.php?action=points&type=positifs" class="chip-link">
                            <div class="chip-icon" style="background-color: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                            </div>
                            <div class="chip-info">
                                <span class="chip-title">Positifs</span>
                                <span class="chip-value" style="color: #10b981;"><?= number_format($data['total_points_positifs'] ?? 0, 0, ',', ' ') ?> pts</span>
                            </div>
                        </a>
                        
                        <a href="index.php?action=points&type=negatifs" class="chip-link">
                            <div class="chip-icon" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                            </div>
                            <div class="chip-info">
                                <span class="chip-title">Négatifs</span>
                                <span class="chip-value" style="color: #ef4444;"><?= number_format($data['total_points_negatifs'] ?? 0, 0, ',', ' ') ?> pts</span>
                            </div>
                        </a>
                        
                        <a href="index.php?action=points&type=total" class="chip-link">
                            <div class="chip-icon" style="background-color: rgba(249, 115, 22, 0.1); color: #f97316;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                            </div>
                            <div class="chip-info">
                                <span class="chip-title">Total Attribués</span>
                                <span class="chip-value" style="color: #f97316;"><?= number_format($data['total_points_attribues'] ?? 0, 0, ',', ' ') ?> pts</span>
                            </div>
                        </a>
                        
                        <a href="index.php?action=etudiants" class="chip-link">
                            <div class="chip-icon" style="background-color: rgba(99, 102, 241, 0.1); color: #6366f1;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                            </div>
                            <div class="chip-info">
                                <span class="chip-title">Étudiants Concernés</span>
                                <span class="chip-value" style="color: #6366f1;"><?= number_format($data['etudiants'] ?? 0, 0, ',', ' ') ?></span>
                            </div>
                        </a>
                    </div>

                    <div class="doughnut-wrapper">
                        <canvas id="repartitionChart"></canvas>
                        <div class="doughnut-center-text">
                            <div class="total-num"><?= number_format($data['total_points_attribues'] ?? 0, 0, ',', ' ') ?> pts</div>
                            <div class="current-month">
                                <?php 
                                    $moisFr = ['01'=>'Janvier', '02'=>'Février', '03'=>'Mars', '04'=>'Avril', '05'=>'Mai', '06'=>'Juin', '07'=>'Juillet', '08'=>'Août', '09'=>'Septembre', '10'=>'Octobre', '11'=>'Novembre', '12'=>'Décembre'];
                                    echo $moisFr[date('m')] . ' ' . date('Y');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Répartition par Domaine -->
                <div class="dashboard-card">
                    <h3>📈 Répartition par Domaine & Solde Net</h3>
                    <div style="overflow-x: auto; height: 250px;">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Positifs</th>
                                    <th>Négatifs</th>
                                    <th>Solde Net</th>
                                    <th>Pourcentage(%)</th>
                                </tr>
                            </thead>
                            <tbody>
                               <?php 
$domainesStats = $data['domaines_stats'] ?? [];
if (!empty($domainesStats)):
    foreach ($domainesStats as $row):
        // Ignore les domaines qui n'ont aucun mouvement
        if (($row['pos'] ?? 0) == 0 && ($row['neg'] ?? 0) == 0) continue;
        
        if (empty($row) || !is_array($row)) continue;
?>
<tr>
    <td><strong><?= htmlspecialchars($row['cat'] ?? 'Inconnu') ?></strong></td>
    <td style="color: #10b981; font-weight: bold;">+<?= htmlspecialchars($row['pos'] ?? 0) ?></td>
    <td style="color: #ef4444; font-weight: bold;">-<?= htmlspecialchars($row['neg'] ?? 0) ?></td>
    <?php 
        $net = $row['net'] ?? 0;
        $netSign = ($net > 0 ? '+' : '');
    ?>
    <td style="color: #6366f1; font-weight: bold;"><?= htmlspecialchars($netSign . $net) ?></td>
    <td><?= htmlspecialchars($row['pct'] ?? '0%') ?></td>
</tr>
<?php 
    endforeach;
else:
?>
<tr>
    <td colspan="5" style="text-align: center; color: #64748b;">Aucune donnée disponible pour cette période.</td>
</tr>
<?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION : Détails supplémentaires -->
            <div class="dashboard-row" style="grid-template-columns: 1fr; margin-top: 20px;">
                <div class="dashboard-card">
                    <h3 style="margin-bottom: 15px;">Détails supplémentaires</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                        
                        <!-- Carte 1 : Catégorie la plus positive -->
                        <div style="background: var(--bg-card, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background-color: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline><polyline points="17 6 23 6 23 12"></polyline></svg>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #64748b;">Catégorie la plus positive</div>
                                <div style="font-size: 16px; font-weight: bold; color: #0f172a; margin-top: 2px;">
                                    <?= htmlspecialchars($data['cat_plus_positive'] ?? 'N/A') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Carte 2 : Catégorie la plus négative -->
                        <div style="background: var(--bg-card, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #64748b;">Catégorie la plus négative</div>
                                <div style="font-size: 16px; font-weight: bold; color: #0f172a; margin-top: 2px;">
                                    <?= htmlspecialchars($data['cat_plus_negative'] ?? 'N/A') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Carte 3 : Étudiant le plus méritant -->
                        <div style="background: var(--bg-card, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background-color: rgba(249, 115, 22, 0.1); color: #f97316; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path><path d="M4 22h16"></path><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"></path></svg>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #64748b;">Étudiant le plus méritant</div>
                                <div style="font-size: 16px; font-weight: bold; color: #0f172a; margin-top: 2px;">
                                    <?= htmlspecialchars($data['etudiant_meritant'] ?? 'Aucun') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Carte 4 : Étudiant le plus sanctionné -->
                        <div style="background: var(--bg-card, #f8fafc); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 15px; display: flex; align-items: center; gap: 12px;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background-color: rgba(239, 68, 68, 0.1); color: #ef4444; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                            </div>
                            <div>
                                <div style="font-size: 12px; color: #64748b;">Étudiant le plus sanctionné</div>
                                <div style="font-size: 16px; font-weight: bold; color: #0f172a; margin-top: 2px;">
                                    <?= htmlspecialchars($data['etudiant_sanctionne'] ?? 'Aucun') ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Évolution par domaine -->
            <div class="dashboard-row" style="grid-template-columns: 1fr; margin-top: 20px;">
                <div class="dashboard-card">
                    <h3>📉 Évolution par domaine des signalements</h3>
                    <!-- Échelle augmentée à 350px pour plus de lisibilité -->
                    <div style="height: 350px; position: relative;">
                        <canvas id="evolutionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Activités récentes avec recherche intégrée -->
            <div class="dashboard-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; margin-top: 20px;">

                <!-- 1. Bloc Activités récentes (Placé à gauche avec un poids de 2) -->
                <div class="dashboard-card" style="margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0;">🕒 Activités récentes</h3>
                        <input type="text" id="searchActivity" placeholder="Rechercher une activité..." onkeyup="filterActivities()" style="padding: 8px 12px; border-radius: 6px; font-size: 14px; width: 250px; border: 1px solid var(--border-color); background: var(--bg-surface); color: var(--text-main);">
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="activity-table" id="activityTable" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="text-align: left; color: #64748b; font-size: 13px; border-bottom: 1px solid var(--border-color);">
                                    <th style="padding: 12px;">ACTIVITÉ</th>
                                    <th style="padding: 12px;">DÉTAILS</th>
                                    <th style="padding: 12px;">PAR</th>
                                    <th style="padding: 12px;">RÔLE</th>
                                    <th style="padding: 12px;">DATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($data['activites_recentes'])): ?>
                                    <?php foreach ($data['activites_recentes'] as $activite): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color); font-size: 14px;">
                                            <!-- Colonne ACTIVITÉ avec icône ronde -->
                                            <td style="padding: 12px; display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 36px; height: 36px; border-radius: 50%; background: <?= match($activite['TYPE_ICONE'] ?? 'default') {
                                                    'signalement' => 'rgba(239, 68, 68, 0.1); color: #ef4444;',
                                                    'validation' => 'rgba(16, 185, 129, 0.1); color: #10b981;',
                                                    'points' => 'rgba(249, 115, 22, 0.1); color: #f97316;',
                                                    default => 'rgba(99, 102, 241, 0.1); color: #6366f1;'
                                                }; ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 16px;">
                                                    <?= $activite['ICONE'] ?? '📌' ?>
                                                </div>
                                                <div>
                                                    <strong style="color: var(--text-main); display: block;"><?= htmlspecialchars($activite['ACTION'] ?? '') ?></strong>
                                                    <small style="color: #ef4444;"><?= htmlspecialchars($activite['SOUS_TITRE'] ?? '') ?></small>
                                                </div>
                                            </td>

                                            <!-- Colonne DÉTAILS -->
                                            <td style="padding: 12px; color: var(--text-main);">
                                                <div><?= htmlspecialchars($activite['DETAILS'] ?? '') ?></div>
                                                <?php if (!empty($activite['IDENTIFIANT'])): ?>
                                                    <small style="color: #64748b; font-family: monospace;"><?= htmlspecialchars($activite['IDENTIFIANT']) ?></small>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Colonne PAR (Avatar + Nom) -->
                                            <td style="padding: 12px;">
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <img src="<?= !empty($activite['PHOTO']) ? htmlspecialchars($activite['PHOTO']) : 'https://via.placeholder.com/32' ?>" alt="Avatar" width="32" height="32" style="border-radius: 50%; object-fit: cover;">
                                                    <span style="font-weight: 500; color: var(--text-main);"><?= htmlspecialchars(($activite['PRENOM'] ?? '') . ' ' . ($activite['NOM'] ?? '')) ?></span>
                                                </div>
                                            </td>

                                            <!-- Colonne RÔLE (Badge coloré) -->
                                            <td style="padding: 12px;">
                                                <?php 
                                                    $role = $activite['LIBELLE_ROLE'] ?? 'Utilisateur';
                                                    $badgeStyle = match(strtolower($role)) {
                                                        'étudiante', 'etudiante' => 'background: rgba(99, 102, 241, 0.1); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.2);',
                                                        'responsable formation' => 'background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2);',
                                                        'chargé de discipline', 'charge de discipline' => 'background: rgba(249, 115, 22, 0.1); color: #f97316; border: 1px solid rgba(249, 115, 22, 0.2);',
                                                        default => 'background: rgba(100, 116, 139, 0.1); color: #64748b;'
                                                    };
                                                ?>
                                                <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; <?= $badgeStyle ?>">
                                                    <?= htmlspecialchars($role) ?>
                                                </span>
                                            </td>

                                            <!-- Colonne DATE -->
                                            <td style="padding: 12px; color: #64748b; font-size: 13px; white-space: nowrap;">
                                                <?= htmlspecialchars($activite['DATE_RELATIVE'] ?? '') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">Aucune activité récente enregistrée.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 2. Bloc Raccourcis rapides (Placé à droite avec un poids de 1) -->
                <div class="dashboard-card" style="margin-bottom: 0;">
                    <h3 style="margin-bottom: 15px;">⚡ Raccourcis rapides</h3>
                    <div class="shortcuts-grid">
                        <a href="index.php?action=signalements&sub=nouveau" class="shortcut-item">
                            <span style="font-size: 18px;">➕</span>
                            <span class="shortcut-title">Nouveau signalement</span>
                            <span class="shortcut-desc">Créer maintenant</span>
                        </a>
                        <a href="index.php?action=signalements&sub=mes_cours" class="shortcut-item">
                            <span style="font-size: 18px;">📋</span>
                            <span class="shortcut-title">Mes signalements</span>
                            <span class="shortcut-desc">Gestion</span>
                        </a>
                        <a href="index.php?action=signalements&sub=valider" class="shortcut-item">
                            <span style="font-size: 18px;">✅</span>
                            <span class="shortcut-title">À valider</span>
                            <span class="shortcut-desc">Validation</span>
                        </a>
                        <a href="index.php?action=etudiants" class="shortcut-item">
                            <span style="font-size: 18px;">🎓</span>
                            <span class="shortcut-title">Étudiants</span>
                            <span class="shortcut-desc"><?= number_format($data['etudiants'] ?? 0, 0, ',', ' ') ?> total</span>
                        </a>
                        <a href="index.php?action=classes" class="shortcut-item">
                            <span style="font-size: 18px;">🏫</span>
                            <span class="shortcut-title">Classes & Promotions</span>
                            <span class="shortcut-desc">Gérer les classes</span>
                        </a>
                        <a href="index.php?action=signalements" class="shortcut-item">
                            <span style="font-size: 18px;">⚠️</span>
                            <span class="shortcut-title">Discipline</span>
                            <span class="shortcut-desc">Gérer sanctions</span>
                        </a>
                        <a href="index.php?action=clubs" class="shortcut-item">
                            <span style="font-size: 18px;">⚽</span>
                            <span class="shortcut-title">Club & Activités</span>
                            <span class="shortcut-desc">Gérer les clubs</span>
                        </a>
                        <a href="index.php?action=annees" class="shortcut-item">
                            <span style="font-size: 18px;">📅</span>
                            <span class="shortcut-title">Année & Semestres</span>
                            <span class="shortcut-desc">Configurer périodes</span>
                        </a>
                        <a href="index.php?action=points" class="shortcut-item">
                            <span style="font-size: 18px;">⭐</span>
                            <span class="shortcut-title">Points et Sanctions</span>
                            <span class="shortcut-desc">Suivi des points</span>
                        </a>
                        <a href="index.php?action=rapports" class="shortcut-item">
                            <span style="font-size: 18px;">📊</span>
                            <span class="shortcut-title">Rapports & Statistiques</span>
                            <span class="shortcut-desc">Consulter rapports</span>
                        </a>
                        <a href="index.php?action=justificatifs" class="shortcut-item">
                            <span style="font-size: 18px;">📄</span>
                            <span class="shortcut-title">Justificatifs</span>
                            <span class="shortcut-desc">Gérer les pièces</span>
                        </a>
                        <a href="index.php?action=parametres" class="shortcut-item">
                            <span style="font-size: 18px;">⚙️</span>
                            <span class="shortcut-title">Paramètres</span>
                            <span class="shortcut-desc">Configuration app</span>
                        </a>
                    </div>
                </div>

            </div>

        </div> <!-- Fin de .content-body -->
    </div> <!-- Fin de .main-content -->
    
    <!-- Injection globale des variables PHP pour Chart.js -->
    <script>
        window.evolutionLabels = <?= json_encode($data['evolution_labels'] ?? []) ?>;
        window.evolutionDatasets = <?= json_encode($data['evolution_datasets'] ?? []) ?>;
        window.phpPositifs = <?= json_encode($data['total_points_positifs'] ?? 2) ?>;
        window.phpNegatifs = <?= json_encode($data['total_points_negatifs'] ?? 1) ?>;
    </script>

    <!-- Script JavaScript principal -->
    <script src="/assets/js/dashboard.js"></script>
</body>
</html>