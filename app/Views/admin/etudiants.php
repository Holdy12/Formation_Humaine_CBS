<?php
// app/Views/admin/etudiants.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array(strtoupper($_SESSION['user_role'] ?? ''), ['ADMIN', 'ADMINISTRATEUR'])) {
    header('Location: ../auth/login.php?erreur=acces_interdit');
    exit();
}

$etudiantsList = $data['etudiants_list'] ?? [];
$totalEtudiants = $data['total_etudiants'] ?? count($etudiantsList);
$totalGarcons  = $data['total_garcons'] ?? 0;
$totalFilles   = $data['total_filles'] ?? 0;
$promotions    = $data['promotions'] ?? [];
$filieres      = $data['filieres'] ?? ['Génie Informatique', 'Réseaux et Télécoms'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Étudiants - Formation Humaine CBS</title>
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
            <input type="text" id="searchGlobalEtudiant" class="search-bar" placeholder="🔍 Rechercher un étudiant par nom, prénom, email..." onkeyup="filterEtudiantsTable()">
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
                    <h2>Gestion des Étudiants</h2>
                    <p>Liste complète des étudiants inscrits et classement académique</p>
                </div>
                <div class="filters">
                    <a href="index.php?action=ajouter_etudiant" class="filter-btn btn-refresh" style="text-decoration: none; display: inline-block; background-color: #ff7f00; color: white;">
                        ➕ Nouvel Étudiant
                    </a>
                </div>
            </div>

            <!-- Cartes Statistiques rapides -->
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
                <div class="stat-card">
                    <span class="stat-badge badge-success">Actif</span>
                    <div class="stat-value"><?= number_format($totalEtudiants, 0, ',', ' ') ?></div>
                    <div class="stat-title">🎓 Total Étudiants Inscrits</div>
                </div>
                <div class="stat-card">
                    <span class="stat-badge badge-success">Garçons</span>
                    <div class="stat-value"><?= number_format($totalGarcons, 0, ',', ' ') ?></div>
                    <div class="stat-title">👦 Étudiants de sexe Masculin</div>
                </div>
                <div class="stat-card">
                    <span class="stat-badge badge-danger">Filles</span>
                    <div class="stat-value"><?= number_format($totalFilles, 0, ',', ' ') ?></div>
                    <div class="stat-title">👧 Étudiantes de sexe Féminin</div>
                </div>
            </div>

            <!-- Tableau principal des étudiants -->
            <div class="dashboard-card" style="margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="margin: 0;">📋 Annuaire & Classement</h3>
                    
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <!-- Filtre Sexe intégré proprement dans la barre -->
                        <select id="filterSexe" class="filter-btn" onchange="filterEtudiantsTable()">
                            <option value="">🚻 Tous les Sexes</option>
                            <option value="Masculin">Masculin</option>
                            <option value="Féminin">Féminin</option>
                        </select>

                        <select id="filterNiveau" class="filter-btn" onchange="filterEtudiantsTable()">
                            <option value="">📚 Tous les Niveaux</option>
                            <option value="Licence 1">Licence 1 (L1)</option>
                            <option value="Licence 2">Licence 2 (L2)</option>
                            <option value="Licence 3">Licence 3 (L3)</option>
                        </select>

                        <select id="filterFiliere" class="filter-btn" onchange="filterEtudiantsTable()">
                            <option value="">🎯 Toutes les Filières</option>
                            <?php foreach ($filieres as $filiere): ?>
                                <?php $filiereNom = is_array($filiere) ? ($filiere['NOM_FILIERE'] ?? $filiere['FILIERE'] ?? '') : $filiere; ?>
                                <?php if (!empty($filiereNom)): ?>
                                    <option value="<?= htmlspecialchars($filiereNom) ?>"><?= htmlspecialchars($filiereNom) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>

                        <select id="filterPromo" class="filter-btn" onchange="filterEtudiantsTable()">
                            <option value="">📅 Toutes les Promotions</option>
                            <?php foreach ($promotions as $promo): ?>
                                <?php $promoCode = is_array($promo) ? ($promo['CODE_PROMO'] ?? '') : $promo; ?>
                                <?php if (!empty($promoCode)): ?>
                                    <option value="<?= htmlspecialchars($promoCode) ?>"><?= htmlspecialchars($promoCode) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="activity-table" id="etudiantsTable" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align: left; color: #64748b; font-size: 13px; border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; width: 60px; text-align: center;">PHOTO</th>
                                <th style="padding: 12px;">ÉTUDIANT</th>
                                <th style="padding: 12px;">CONTACT</th>
                                <th style="padding: 12px;">NIVEAU</th>
                                <th style="padding: 12px;">FILIÈRE</th>
                                <th style="padding: 12px;">PROMOTION</th>
                                <th style="padding: 12px; text-align: center;">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($etudiantsList)): ?>
                                <?php foreach ($etudiantsList as $etudiant): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color); font-size: 14px;">
                                        <td style="padding: 12px; text-align: center;">
                                            <?php 
                                                $photoPath = !empty($etudiant['PHOTO']) ? htmlspecialchars($etudiant['PHOTO']) : '/assets/images/default-avatar.png'; 
                                            ?>
                                            <img src="<?= $photoPath ?>" alt="Avatar" width="36" height="36" style="border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); vertical-align: middle;">
                                        </td>

                                        <td style="padding: 12px;">
                                            <strong style="color: var(--text-main); display: block;"><?= htmlspecialchars(($etudiant['NOM'] ?? '') . ' ' . ($etudiant['PRENOM'] ?? '')) ?></strong>
                                            <small style="color: #64748b;"><?= htmlspecialchars(($etudiant['SEXE'] ?? '') === 'M' ? 'Masculin' : 'Féminin') ?></small>
                                        </td>

                                        <td style="padding: 12px; color: var(--text-main);">
                                            <div><?= htmlspecialchars($etudiant['EMAIL'] ?? 'N/A') ?></div>
                                            <small style="color: #64748b;"><?= htmlspecialchars($etudiant['TELEPHONE'] ?? 'Aucun téléphone') ?></small>
                                        </td>

                                        <td style="padding: 12px; color: var(--text-main);">
                                            <span style="font-weight: 500;"><?= htmlspecialchars($etudiant['NIVEAU'] ?? 'Non défini') ?></span>
                                        </td>

                                        <td style="padding: 12px; color: var(--text-main);">
                                            <?= htmlspecialchars($etudiant['FILIERE'] ?? 'Non définie') ?>
                                        </td>

                                        <td style="padding: 12px;">
                                            <span style="padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500; background: rgba(99, 102, 241, 0.1); color: #6366f1; border: 1px solid rgba(99, 102, 241, 0.2);">
                                                <?= htmlspecialchars($etudiant['CODE_PROMO'] ?? 'Non assignée') ?>
                                            </span>
                                        </td>

                                        <td style="padding: 12px; text-align: center; white-space: nowrap;">
                                            <a href="index.php?action=voir_etudiant&id=<?= $etudiant['ID_PERSONNE'] ?? 0 ?>" class="filter-btn" style="text-decoration: none; padding: 5px 10px; font-size: 12px; margin-right: 5px;">👁️ Voir</a>
                                            <a href="index.php?action=modifier_etudiant&id=<?= $etudiant['ID_PERSONNE'] ?? 0 ?>" class="filter-btn" style="text-decoration: none; padding: 5px 10px; font-size: 12px; margin-right: 5px;">✏️ Modifier</a>
                                            <a href="index.php?action=delete&id=<?= $etudiant['ID_PERSONNE'] ?? 0 ?>" class="filter-btn" style="text-decoration: none; padding: 5px 10px; font-size: 12px; color: #ef4444; border-color: rgba(239, 68, 68, 0.3);" onclick="return confirm('Voulez-vous vraiment supprimer cet étudiant ?');">🗑️</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">Aucun étudiant enregistré pour le moment.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    
    <script>
        function filterEtudiantsTable() {
            let inputGlobal = document.getElementById("searchGlobalEtudiant").value.toUpperCase();
            let filterSexe = document.getElementById("filterSexe").value.toUpperCase();
            let filterNiveau = document.getElementById("filterNiveau").value.toUpperCase();
            let filterFiliere = document.getElementById("filterFiliere").value.toUpperCase();
            let filterPromo = document.getElementById("filterPromo").value.toUpperCase();
            
            let table = document.getElementById("etudiantsTable");
            let tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) {
                let tdName = tr[i].getElementsByTagName("td")[1];
                let tdContact = tr[i].getElementsByTagName("td")[2];
                let tdNiveau = tr[i].getElementsByTagName("td")[3];
                let tdFiliere = tr[i].getElementsByTagName("td")[4];
                let tdPromo = tr[i].getElementsByTagName("td")[5];

                if (tdName && tdContact && tdNiveau && tdFiliere && tdPromo) {
                    let textName = tdName.getElementsByTagName("strong")[0] ? tdName.getElementsByTagName("strong")[0].textContent : "";
                    let textSexeNode = tdName.getElementsByTagName("small")[0];
                    let textSexe = textSexeNode ? textSexeNode.textContent.toUpperCase() : "";

                    let textContactVal = tdContact.textContent || tdContact.innerText;
                    let textNiveau = tdNiveau.textContent || tdNiveau.innerText;
                    let textFiliere = tdFiliere.textContent || tdFiliere.innerText;
                    let textPromo = tdPromo.textContent || tdPromo.innerText;

                    let matchGlobal = (textName + " " + textContactVal).toUpperCase().indexOf(inputGlobal) > -1;
                    let matchSexe = filterSexe === "" || textSexe.indexOf(filterSexe) > -1;
                    let matchNiveau = filterNiveau === "" || textNiveau.toUpperCase().indexOf(filterNiveau) > -1;
                    let matchFiliere = filterFiliere === "" || textFiliere.toUpperCase().indexOf(filterFiliere) > -1;
                    let matchPromo = filterPromo === "" || textPromo.toUpperCase().indexOf(filterPromo) > -1;

                    if (matchGlobal && matchSexe && matchNiveau && matchFiliere && matchPromo) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>

    <script src="/assets/js/dashboard.js"></script>
</body>
</html>