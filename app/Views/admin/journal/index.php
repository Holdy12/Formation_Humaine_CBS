<div class="dashboard-header">
    <div>
        <h2>Journal</h2>
        <p>Connexions et actions sensibles, horodatées et attribuées. <?= (int)$pagination['total'] ?> entrée<?= $pagination['total'] > 1 ? 's' : '' ?> pour cette sélection.</p>
    </div>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_journal">
    <input type="search" name="q" aria-label="Rechercher" value="<?= htmlspecialchars($filtres['q']) ?>" placeholder="Personne ou détail" class="filtre-recherche">
    <select name="action_journal" aria-label="Type d'action">
        <option value="">Toutes les actions</option>
        <?php foreach ($actions as $a): ?><option value="<?= htmlspecialchars($a) ?>"<?= $filtres['action'] === $a ? ' selected' : '' ?>><?= htmlspecialchars($a) ?></option><?php endforeach; ?>
    </select>
    <select name="statut" aria-label="Résultat">
        <option value="">Succès et échecs</option>
        <option value="SUCCES"<?= $filtres['statut'] === 'SUCCES' ? ' selected' : '' ?>>Succès</option>
        <option value="ECHEC"<?= $filtres['statut'] === 'ECHEC' ? ' selected' : '' ?>>Échecs</option>
    </select>
    <label class="filtre-groupe"><span>Du</span><input type="date" name="debut" value="<?= htmlspecialchars($filtres['debut']) ?>"></label>
    <label class="filtre-groupe"><span>au</span><input type="date" name="fin" value="<?= htmlspecialchars($filtres['fin']) ?>"></label>
    <div class="barre-filtres-actions"><button type="submit" class="btn btn-sombre"><?= Icone::svg('filtre', 15) ?> Filtrer</button></div>
</form>

<div class="dashboard-card">
    <?php if (empty($entrees)): ?>
        <?= Composant::etatVide('oeil', 'Aucune entrée', 'Aucune action ne correspond à ces critères.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date</th><th>Personne</th><th>Action</th><th>Détail</th><th>Résultat</th><th>Adresse IP</th></tr></thead>
                <tbody>
                <?php foreach ($entrees as $e): ?>
                    <tr>
                        <td data-label="Date"><?= Format::dateHeure($e['DATE_CONNEXION']) ?></td>
                        <td data-label="Personne" class="cellule-large"><?= $e['NOM'] ? '<strong>' . htmlspecialchars($e['NOM'] . ' ' . $e['PRENOM']) . '</strong><br><small>' . htmlspecialchars($e['LIBELLE_ROLE'] ?? '') . '</small>' : '<span class="aide">Inconnu</span>' ?></td>
                        <td data-label="Action"><span class="badge badge-bleu"><?= htmlspecialchars($e['ACTION']) ?></span></td>
                        <td data-label="Détail" class="cellule-large"><?= htmlspecialchars($e['DETAILS'] ?? '') ?></td>
                        <td data-label="Résultat"><span class="badge <?= $e['STATUT'] === 'SUCCES' ? 'badge-vert' : 'badge-rouge' ?>"><?= $e['STATUT'] === 'SUCCES' ? 'Succès' : 'Échec' ?></span></td>
                        <td data-label="Adresse IP"><small><?= htmlspecialchars($e['ADRESSE_IP'] ?? '') ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../../partials/pagination.php'; ?>
    <?php endif; ?>
</div>
