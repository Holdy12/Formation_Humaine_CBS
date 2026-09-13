<div class="dashboard-header">
    <div>
        <h2>Barème et paramètres</h2>
        <p>Les critères définissent les points retirés ou attribués. Modifier une valeur n'affecte que les mouvements à venir ; désactiver un critère le retire des formulaires sans toucher à l'historique.</p>
    </div>
</div>

<div class="dashboard-card">
    <h3>Nouveau critère</h3>
    <form method="post" action="index.php?action=admin_bareme_critere" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <div class="grille-champs">
            <div class="champ">
                <label for="nouveau-domaine">Domaine</label>
                <select name="domaine" id="nouveau-domaine" required>
                    <option value="">Choisir</option>
                    <?php foreach ($domaines as $d): ?><option value="<?= (int)$d['ID_DOMAINE'] ?>"><?= htmlspecialchars($d['NOM_DOMAINE']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="champ champ-double"><label for="nouveau-libelle">Libellé</label><input type="text" name="libelle" id="nouveau-libelle" required maxlength="100" placeholder="Ex : Usage du téléphone en cours"></div>
            <div class="champ"><label for="nouveau-valeur">Points</label><input type="text" name="valeur" id="nouveau-valeur" required inputmode="decimal" placeholder="Ex : -0,50"><div class="aide">Négatif pour un retrait, positif pour une bonification.</div></div>
        </div>
        <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Ajouter au barème</button></div>
    </form>
</div>

<?php foreach ($domaines as $d): ?>
<div class="dashboard-card">
    <form method="post" action="index.php?action=admin_bareme_domaine" class="entete-domaine">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <input type="hidden" name="id" value="<?= (int)$d['ID_DOMAINE'] ?>">
        <div class="champ champ-double">
            <label for="domaine-<?= (int)$d['ID_DOMAINE'] ?>">Domaine <span class="badge badge-gris"><?= htmlspecialchars($d['CODE_DOMAINE']) ?></span></label>
            <input type="text" name="nom" id="domaine-<?= (int)$d['ID_DOMAINE'] ?>" value="<?= htmlspecialchars($d['NOM_DOMAINE']) ?>" required maxlength="100">
        </div>
        <button type="submit" class="btn btn-fantome btn-petit"><?= Icone::svg('valide', 14) ?> Renommer</button>
    </form>
    <?php $liste = array_filter($criteres, fn($c) => (int)$c['ID_DOMAINE'] === (int)$d['ID_DOMAINE']); ?>
    <?php if (empty($liste)): ?>
        <?= Composant::etatVide('etoile', 'Aucun critère', 'Ajoutez un critère à ce domaine avec le formulaire du haut.') ?>
    <?php else: ?>
        <div class="liste-editions">
            <?php foreach ($liste as $c): ?>
                <form method="post" action="index.php?action=admin_bareme_critere" class="ligne-edition ligne-edition-formulaire<?= $c['ACTIF'] ? '' : ' inactif' ?>">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <input type="hidden" name="id" value="<?= (int)$c['ID_CRITERE'] ?>">
                    <div class="grille-champs grille-critere">
                        <div class="champ champ-double"><label for="critere-<?= (int)$c['ID_CRITERE'] ?>">Libellé</label><input type="text" name="libelle" id="critere-<?= (int)$c['ID_CRITERE'] ?>" value="<?= htmlspecialchars($c['LIBELLE_CRITERE']) ?>" required maxlength="100"></div>
                        <div class="champ"><label for="valeur-<?= (int)$c['ID_CRITERE'] ?>">Points</label><input type="text" name="valeur" id="valeur-<?= (int)$c['ID_CRITERE'] ?>" value="<?= number_format((float)$c['VALEUR_POINTS'], 2, ',', '') ?>" required inputmode="decimal" class="<?= (float)$c['VALEUR_POINTS'] < 0 ? 'points-moins' : 'points-plus' ?>"></div>
                        <div class="champ"><label>Statut</label><label class="case"><input type="checkbox" name="actif" value="1"<?= $c['ACTIF'] ? ' checked' : '' ?>><span>Actif</span></label></div>
                    </div>
                    <div class="ligne-edition-actions"><button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg('valide', 14) ?> Enregistrer</button></div>
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
