<div class="dashboard-header">
    <div>
        <h2>Mes résultats</h2>
        <p>La note provisoire évolue avec le registre des points. La note finale est fixée à la clôture du semestre par le responsable de la Formation Humaine.</p>
    </div>
    <div class="actions">
        <?php require __DIR__ . '/../partials/selecteur_semestre.php'; ?>
        <a href="index.php?action=etudiant_releve<?= $semestre ? '&semestre=' . (int)$semestre['ID_SEMESTRE'] : '' ?>" class="btn btn-secondaire"><?= Icone::svg('document', 16) ?> Relevé imprimable</a>
    </div>
</div>

<?php if (!$semestre): ?>
    <div class="alerte alerte-info"><?= Icone::svg('info', 16) ?><span>Aucun semestre n'est configuré pour le moment.</span></div>
<?php else: ?>
<?php $cloture = $resultat && $resultat['STATUT_VALIDATION'] === 'CLOTURE'; ?>
<div class="grille-2">
    <div class="dashboard-card">
        <h3>Note provisoire</h3>
        <div class="resultat">
            <div class="resultat-valeur"><?= Format::points($solde['solde']) ?><small>/ <?= Format::points($solde['maximum']) ?></small></div>
            <div class="solde-decomposition">
                <div><span>Capital de départ</span><b><?= Format::points($solde['capital']) ?></b></div>
                <div><span>Pénalités</span><b class="points-moins">−<?= Format::points($solde['penalites']) ?></b></div>
                <div><span>Bonifications retenues</span><b class="points-plus">+<?= Format::points($solde['bonus']) ?></b></div>
            </div>
        </div>
    </div>

    <div class="dashboard-card">
        <h3>Note finale</h3>
        <?php if ($cloture): ?>
            <div class="resultat">
                <div class="resultat-valeur"><?= Format::points((float)$resultat['NOTE_FINALE']) ?><small>/ <?= Format::points($solde['maximum']) ?></small></div>
                <div class="solde-decomposition">
                    <div><span>Mention</span><b><?= htmlspecialchars($resultat['MENTION'] ?? '—') ?></b></div>
                    <div><span>Semestre clôturé le</span><b><?= Format::date($resultat['DATE_CLOTURE']) ?></b></div>
                </div>
            </div>
        <?php else: ?>
            <?= Composant::etatVide('horloge', 'Semestre en cours', "La note finale s'affichera ici une fois le semestre clôturé par le responsable de la Formation Humaine.") ?>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-card">
    <h3>Détail par domaine</h3>
    <div class="defilement">
        <table class="activity-table">
            <thead><tr><th>Domaine</th><th>Bonifications</th><th>Plafond</th><th>Retenu</th><th>Pénalités</th></tr></thead>
            <tbody>
            <?php foreach ($solde['domaines'] as $d): ?>
                <tr>
                    <td data-label="Domaine"><strong><?= htmlspecialchars($d['NOM_DOMAINE']) ?></strong></td>
                    <td data-label="Bonifications" class="points-plus">+<?= Format::points($d['POSITIF']) ?></td>
                    <td data-label="Plafond"><?= $d['PLAFOND'] !== null ? '+' . Format::points($d['PLAFOND']) : '—' ?></td>
                    <td data-label="Retenu" class="points-plus">+<?= Format::points($d['BONUS_RETENU']) ?></td>
                    <td data-label="Pénalités" class="points-moins">−<?= Format::points($d['NEGATIF']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
