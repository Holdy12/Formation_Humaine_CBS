<div class="dashboard-header">
    <div>
        <h2>Barème et paramètres</h2>
        <p>Les critères définissent les points retirés ou attribués. Modifier une valeur n'affecte que les mouvements à venir ; désactiver un critère le retire des formulaires sans toucher à l'historique.</p>
    </div>
</div>

<div class="dashboard-card">
    <h3>Nouveau critère</h3>
    <form method="post" action="index.php?action=admin_bareme_critere" class="barre-filtres" style="margin: 0;">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <select name="domaine" aria-label="Domaine" required>
            <option value="">Domaine…</option>
            <?php foreach ($domaines as $d): ?><option value="<?= (int)$d['ID_DOMAINE'] ?>"><?= htmlspecialchars($d['NOM_DOMAINE']) ?></option><?php endforeach; ?>
        </select>
        <input type="text" name="libelle" required maxlength="100" placeholder="Libellé du critère" class="filtre-recherche" aria-label="Libellé">
        <input type="text" name="valeur" required inputmode="decimal" placeholder="Points, ex. -0,50" aria-label="Valeur en points" style="max-width: 150px;">
        <div class="barre-filtres-actions"><button type="submit" class="btn btn-sombre"><?= Icone::svg('plus', 15) ?> Ajouter</button></div>
    </form>
</div>

<?php foreach ($domaines as $d): ?>
<div class="dashboard-card">
    <div class="entete-carte">
        <form method="post" action="index.php?action=admin_bareme_domaine" class="barre-filtres ligne-structure">
            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
            <input type="hidden" name="id" value="<?= (int)$d['ID_DOMAINE'] ?>">
            <input type="text" name="nom" value="<?= htmlspecialchars($d['NOM_DOMAINE']) ?>" required maxlength="100" aria-label="Nom du domaine" style="font-weight: 600;">
            <button type="submit" class="btn btn-fantome btn-petit"><?= Icone::svg('valide', 14) ?> Renommer</button>
        </form>
        <span class="badge badge-gris"><?= htmlspecialchars($d['CODE_DOMAINE']) ?></span>
    </div>
    <?php $liste = array_filter($criteres, fn($c) => (int)$c['ID_DOMAINE'] === (int)$d['ID_DOMAINE']); ?>
    <?php if (empty($liste)): ?>
        <?= Composant::etatVide('etoile', 'Aucun critère', 'Ajoutez un critère à ce domaine avec le formulaire du haut.') ?>
    <?php else: ?>
        <div class="liste-criteres">
            <?php foreach ($liste as $c): ?>
                <form method="post" action="index.php?action=admin_bareme_critere" class="ligne-critere<?= $c['ACTIF'] ? '' : ' inactif' ?>">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['ID_CRITERE'] ?>">
                    <input type="text" name="libelle" value="<?= htmlspecialchars($c['LIBELLE_CRITERE']) ?>" required maxlength="100" aria-label="Libellé" class="critere-libelle">
                    <input type="text" name="valeur" value="<?= number_format((float)$c['VALEUR_POINTS'], 2, ',', '') ?>" required inputmode="decimal" aria-label="Points" class="critere-valeur <?= (float)$c['VALEUR_POINTS'] < 0 ? 'points-moins' : 'points-plus' ?>">
                    <label class="case"><input type="checkbox" name="actif" value="1"<?= $c['ACTIF'] ? ' checked' : '' ?>><span>Actif</span></label>
                    <button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg('valide', 14) ?> Enregistrer</button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="dashboard-card" id="parametres">
    <h3>Paramètres système</h3>
    <form method="post" action="index.php?action=admin_bareme_parametres" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <div class="grille-parametres">
            <?php foreach ($parametres as $p): ?>
                <div class="champ">
                    <label for="param-<?= htmlspecialchars($p['CODE_PARAMETRE']) ?>"><?= htmlspecialchars($p['DESCRIPTION']) ?></label>
                    <input type="text" name="valeur[<?= htmlspecialchars($p['CODE_PARAMETRE']) ?>]" id="param-<?= htmlspecialchars($p['CODE_PARAMETRE']) ?>" value="<?= htmlspecialchars($p['VALEUR']) ?>" required inputmode="decimal">
                    <div class="aide"><?= htmlspecialchars($p['CODE_PARAMETRE']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('valide', 16) ?> Enregistrer les paramètres</button></div>
    </form>
</div>
