<div class="dashboard-header">
    <div>
        <h2>Étudiants</h2>
        <p><?= (int)$pagination['total'] ?> étudiant<?= $pagination['total'] > 1 ? 's' : '' ?> correspondant à la recherche.</p>
    </div>
    <?php if (Auth::peut('etudiants.gerer')): ?>
    <div class="actions">
        <a href="index.php?action=admin_etudiants_import" class="btn btn-secondaire"><?= Icone::svg('televerser', 16) ?> Importer un CSV</a>
        <a href="index.php?action=admin_etudiant_nouveau" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Nouvel étudiant</a>
    </div>
    <?php endif; ?>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_etudiants">
    <input type="search" name="q" value="<?= htmlspecialchars($filtres['q']) ?>" placeholder="Nom, prénom, matricule ou email" class="filtre-recherche">
    <select name="promo" aria-label="Promotion">
        <option value="">Toutes les promotions</option>
        <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= $filtres['promo'] == $p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO']) ?></option><?php endforeach; ?>
    </select>
    <select name="niveau" aria-label="Niveau">
        <option value="">Tous les niveaux</option>
        <?php foreach ($niveaux as $n): ?><option value="<?= (int)$n['ID_NIVEAU'] ?>"<?= $filtres['niveau'] == $n['ID_NIVEAU'] ? ' selected' : '' ?>><?= htmlspecialchars($n['LIBELLE_NIVEAU']) ?></option><?php endforeach; ?>
    </select>
    <select name="filiere" aria-label="Filière">
        <option value="">Toutes les filières</option>
        <?php foreach ($filieres as $f): ?><option value="<?= (int)$f['ID_FILIERE'] ?>"<?= $filtres['filiere'] == $f['ID_FILIERE'] ? ' selected' : '' ?>><?= htmlspecialchars($f['NOM_FILIERE']) ?></option><?php endforeach; ?>
    </select>
    <select name="statut" aria-label="Statut du compte">
        <option value="">Actifs et inactifs</option>
        <option value="ACTIF"<?= $filtres['statut'] === 'ACTIF' ? ' selected' : '' ?>>Actifs</option>
        <option value="INACTIF"<?= $filtres['statut'] === 'INACTIF' ? ' selected' : '' ?>>Inactifs</option>
    </select>
    <div class="barre-filtres-actions">
        <button type="submit" class="btn btn-sombre"><?= Icone::svg('filtre', 15) ?> Filtrer</button>
        <a href="index.php?action=admin_etudiants_export&<?= htmlspecialchars(http_build_query(array_filter($filtres))) ?>" class="btn btn-fantome"><?= Icone::svg('document', 15) ?> Exporter</a>
    </div>
</form>

<div class="dashboard-card">
    <?php if (empty($etudiants)): ?>
        <?= Composant::etatVide('diplome', 'Aucun étudiant', 'Aucun étudiant ne correspond à ces critères. Élargissez la recherche ou ajoutez un étudiant.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th></th><th>Étudiant</th><th>Promotion</th><th>Contact</th><th>Club</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($etudiants as $e): ?>
                    <tr>
                        <td class="cellule-avatar"><span class="avatar"><?= $e['PHOTO'] ? '<img src="' . htmlspecialchars($e['PHOTO']) . '" alt="">' : Icone::svg('personne', 18) ?></span></td>
                        <td data-label="Étudiant" class="cellule-large"><strong><?= htmlspecialchars($e['NOM'] . ' ' . $e['PRENOM']) ?></strong><?= $e['EST_DELEGUE'] ? ' <span class="badge badge-bleu">' . Format::genre($e['SEXE'], 'Délégué', 'Déléguée') . '</span>' : '' ?><br><small><?= htmlspecialchars($e['MATRICULE']) ?></small></td>
                        <td data-label="Promotion" class="cellule-large"><?= htmlspecialchars($e['CODE_PROMO']) ?><br><small><?= htmlspecialchars($e['LIBELLE_NIVEAU'] . ' ' . $e['NOM_FILIERE']) ?></small></td>
                        <td data-label="Contact" class="cellule-large"><?= htmlspecialchars($e['EMAIL']) ?><br><small><?= htmlspecialchars($e['TELEPHONE']) ?></small></td>
                        <td data-label="Club"><?= $e['NOM_CLUB'] ? htmlspecialchars($e['NOM_CLUB']) : '—' ?></td>
                        <td data-label="Statut"><span class="badge <?= $e['STATUT_COMPTE'] === 'ACTIF' ? 'badge-vert' : 'badge-gris' ?>"><?= $e['STATUT_COMPTE'] === 'ACTIF' ? 'Actif' : 'Inactif' ?></span></td>
                        <td><a href="index.php?action=admin_etudiant&id=<?= (int)$e['ID_PERSONNE'] ?>" class="btn btn-secondaire btn-petit"><?= Icone::svg('oeil', 14) ?> Fiche</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../../partials/pagination.php'; ?>
    <?php endif; ?>
</div>
