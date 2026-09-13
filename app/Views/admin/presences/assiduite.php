<div class="dashboard-header">
    <div>
        <h2>Assiduité à pénaliser</h2>
        <p>Absences dont le délai de justification de <?= (int)$delai ?> heures est écoulé, et retards, qui n'ont pas encore de pénalité. Le retrait est daté du jour de la séance.</p>
    </div>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_assiduite">
    <select name="promo" aria-label="Promotion">
        <option value="">Toutes les promotions</option>
        <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= $filtres['promo'] == $p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO']) ?></option><?php endforeach; ?>
    </select>
    <div class="barre-filtres-actions"><button type="submit" class="btn btn-sombre"><?= Icone::svg('filtre', 15) ?> Filtrer</button></div>
</form>

<?php if (empty($lignes)): ?>
    <div class="dashboard-card"><?= Composant::etatVide('valide', 'Rien à pénaliser', "Toutes les absences et tous les retards en attente ont été traités.") ?></div>
<?php else: ?>
<form method="post" action="index.php?action=admin_assiduite_penaliser">
    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
    <div class="dashboard-card">
        <div class="entete-carte">
            <h3><?= count($lignes) ?> ligne<?= count($lignes) > 1 ? 's' : '' ?> en attente</h3>
            <label class="case"><input type="checkbox" id="tout-cocher"><span>Tout sélectionner</span></label>
        </div>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th></th><th>Étudiant</th><th>Séance</th><th>Date</th><th>Statut</th><th>Retrait prévu</th></tr></thead>
                <tbody>
                <?php foreach ($lignes as $l): ?>
                    <?php $critere = Presence::criterePourAssiduite($l['STATUT'], !empty($l['ID_CLUB'])); ?>
                    <tr>
                        <td><label class="case"><input type="checkbox" name="presences[]" value="<?= (int)$l['ID_PRESENCE'] ?>" class="ligne-assiduite"><span class="sr-only">Sélectionner cette ligne</span></label></td>
                        <td data-label="Étudiant" class="cellule-large"><strong><?= htmlspecialchars($l['NOM'] . ' ' . $l['PRENOM']) ?></strong><br><small><?= htmlspecialchars($l['MATRICULE'] . ', ' . $l['CODE_PROMO']) ?></small></td>
                        <td data-label="Séance" class="cellule-large"><?= htmlspecialchars($l['TITRE_SEANCE']) ?><br><small><?= htmlspecialchars($l['NOM_CLUB'] ?: 'Promotion') ?></small></td>
                        <td data-label="Date"><?= Format::date($l['DATE_SEANCE']) ?></td>
                        <td data-label="Statut"><span class="badge <?= $l['STATUT'] === 'RETARD' ? 'badge-orange' : 'badge-rouge' ?>"><?= $l['STATUT'] === 'RETARD' ? 'Retard' : 'Absence' ?></span></td>
                        <td data-label="Retrait prévu" class="points-moins"><?= $critere ? Format::points((float)$critere['VALEUR_POINTS'], true) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="actions" style="margin-top: 16px;">
            <button type="submit" class="btn btn-principal"><?= Icone::svg('etoile', 16) ?> Appliquer les pénalités sélectionnées</button>
        </div>
    </div>
</form>
<script>
    document.getElementById('tout-cocher').addEventListener('change', function () {
        var coche = this.checked;
        document.querySelectorAll('.ligne-assiduite').forEach(function (c) { c.checked = coche; });
    });
</script>
<?php endif; ?>
