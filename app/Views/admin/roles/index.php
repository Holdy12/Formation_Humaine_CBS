<?php 
// app/Views/admin/roles/index.php
?>

<div class="dashboard-header">
    <div>
        <h2>Gestion des Rôles et Permissions</h2>
        <p>Attribuez et configurez les droits d'accès pour chaque profil du personnel.</p>
    </div>
    <div class="dashboard-header-actions">
        <a href="index.php?action=admin_roles_creer" class="btn btn-principal">
            <?= Icone::svg('plus', 16) ?> Nouveau rôle
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div class="alerte alerte-succes" role="status">
        <?= Icone::svg('valide', 16) ?>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<div class="dashboard-card">
    <div class="dashboard-card-header">
        <h3>Liste des rôles</h3>
    </div>
    
    <?php if (empty($roles)): ?>
        <?= Composant::etatVide('cadenas', 'Aucun rôle trouvé', 'Il n\'y a actuellement aucun rôle configuré dans le système.') ?>
    <?php else: ?>
        <div class="tableau-container">
            <table class="tableau" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-gauche" style="width: 25%;">Code</th>
                        <th class="text-gauche" style="width: 55%;">Libellé du rôle</th>
                        <th class="text-droite" style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $r): ?>
                        <tr>
                            <td class="text-gauche"><code><?= htmlspecialchars($r['CODE_ROLE'] ?? '') ?></code></td>
                            <td class="text-gauche"><strong><?= htmlspecialchars($r['LIBELLE_ROLE'] ?? '') ?></strong></td>
                            <td class="text-droite">
                                <a href="index.php?action=admin_roles_modifier&id=<?= (int)($r['ID_ROLE'] ?? 0) ?>" class="btn btn-fantome btn-petit">
                                    <?= Icone::svg('crayon', 14) ?> Modifier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>