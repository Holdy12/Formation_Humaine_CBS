<div class="dashboard-header">
    <div>
        <h2>Bonjour <?= htmlspecialchars($personnel['PRENOM']) ?></h2>
        <p><?= htmlspecialchars(Permissions::libelleRole($personnel['CODE_ROLE'])) ?></p>
    </div>
</div>

<div class="dashboard-card">
    <h3>À traiter</h3>
    <?php if (empty($aTraiter)): ?>
        <?= Composant::etatVide('valide', 'Rien en attente', "Aucune tâche ne vous attend pour le moment.") ?>
    <?php endif; ?>
</div>
