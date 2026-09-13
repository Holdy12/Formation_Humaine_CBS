<div class="dashboard-header">
    <div>
        <h2>Registre des points</h2>
        <p>Chaque ligne est un mouvement validé. Une correction apparaît comme une écriture inverse, jamais comme une suppression.</p>
    </div>
    <div class="actions">
        <?php require __DIR__ . '/../partials/selecteur_semestre.php'; ?>
        <a href="index.php?action=etudiant_releve<?= $semestre ? '&semestre=' . (int)$semestre['ID_SEMESTRE'] : '' ?>" class="btn btn-secondaire"><?= Icone::svg('document', 16) ?> Relevé imprimable</a>
    </div>
</div>

<?php if (!$semestre): ?>
    <div class="alerte alerte-info">Aucun semestre n'est configuré pour le moment.</div>
<?php else: ?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));">
    <div class="stat-card">
        <div class="stat-value"><?= Format::points($solde['solde']) ?> / <?= Format::points($solde['maximum']) ?></div>
        <div class="stat-title">Note provisoire</div>
    </div>
    <?php foreach ($solde['domaines'] as $d): ?>
        <div class="stat-card">
            <div class="stat-value">
                <?php if ($d['POSITIF'] == 0 && $d['NEGATIF'] == 0): ?>
                    <span class="stat-neutre">Aucun mouvement</span>
                <?php else: ?>
                    <span class="points-plus">+<?= Format::points($d['BONUS_RETENU']) ?></span>
                    <span class="points-moins">−<?= Format::points($d['NEGATIF']) ?></span>
                <?php endif; ?>
            </div>
            <div class="stat-title"><?= htmlspecialchars($d['NOM_DOMAINE']) ?><?= $d['PLAFOND'] !== null ? ', plafond +' . Format::points($d['PLAFOND']) : '' ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="dashboard-card">
    <h3>Mouvements du semestre</h3>
    <?php if (empty($mouvements)): ?>
        <?= Composant::etatVide('etoile', 'Aucun mouvement sur ce semestre', 'Le registre se remplit au fil des décisions validées : présences, signalements, bonifications.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date</th><th>Domaine</th><th>Critère</th><th>Motif</th><th>Points</th><th>Validé par</th></tr></thead>
                <tbody>
                <?php foreach ($mouvements as $m): ?>
                    <?php $valeur = ($m['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$m['NOMBRE_POINTS']; ?>
                    <tr>
                        <td data-label="Date"><?= Format::dateHeure($m['DATE_MOUVEMENT']) ?></td>
                        <td data-label="Domaine"><?= htmlspecialchars($m['NOM_DOMAINE']) ?></td>
                        <td data-label="Critère"><?= htmlspecialchars($m['LIBELLE_CRITERE']) ?></td>
                        <td data-label="Motif" class="cellule-large"><?= htmlspecialchars($m['MOTIF_MOUVEMENT'] ?? '') ?><?php if ($m['ID_SIGNALEMENT']): ?> <a href="index.php?action=etudiant_signalements&id=<?= (int)$m['ID_SIGNALEMENT'] ?>">Voir le dossier</a><?php endif; ?></td>
                        <td data-label="Points" class="<?= $valeur < 0 ? 'points-moins' : 'points-plus' ?>"><?= Format::points($valeur, true) ?></td>
                        <td data-label="Validé par"><?= $m['VALIDATEUR_NOM'] ? htmlspecialchars($m['VALIDATEUR_PRENOM'] . ' ' . $m['VALIDATEUR_NOM']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php endif; ?>
