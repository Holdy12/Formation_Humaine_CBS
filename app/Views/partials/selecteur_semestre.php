<?php // attend $semestres, $semestre, $actif ; $actionSelecteur permet de viser une autre route ?>
<form method="get" action="index.php" class="actions">
    <input type="hidden" name="action" value="<?= htmlspecialchars($actionSelecteur ?? 'etudiant_' . $actif) ?>">
    <label class="aide" for="choix-semestre">Semestre</label>
    <select name="semestre" aria-label="Semestre" id="choix-semestre" class="filter-btn" onchange="this.form.submit()">
        <?php foreach ($semestres as $s): ?>
            <option value="<?= (int)$s['ID_SEMESTRE'] ?>"<?= $semestre && (int)$s['ID_SEMESTRE'] === (int)$semestre['ID_SEMESTRE'] ? ' selected' : '' ?>>
                <?= htmlspecialchars(($s['LIBELLE_SEMESTRE'] ?: $s['CODE_SEMESTRE']) . ', ' . $s['LIBELLE_ANNEE']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</form>
