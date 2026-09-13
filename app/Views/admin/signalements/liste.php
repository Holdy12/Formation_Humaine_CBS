<div class="dashboard-header">
    <div>
        <h2><?= $mesDossiers ? 'Mes signalements' : 'Signalements' ?></h2>
        <p><?= (int)$pagination['total'] ?> dossier<?= $pagination['total'] > 1 ? 's' : '' ?><?= $mesDossiers ? ' que vous avez transmis.' : ' correspondant à la recherche.' ?></p>
    </div>
    <div class="actions">
        <?php if ($peutTousVoir): ?>
            <a href="index.php?action=admin_signalements<?= $mesDossiers ? '' : '&miens=1' ?>" class="btn btn-secondaire"><?= Icone::svg($mesDossiers ? 'liste' : 'personne', 16) ?> <?= $mesDossiers ? 'Tous les dossiers' : 'Mes signalements' ?></a>
        <?php endif; ?>
        <a href="index.php?action=admin_signalement_nouveau" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Nouveau signalement</a>
    </div>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_signalements">
    <?php if ($mesDossiers && $peutTousVoir): ?><input type="hidden" name="miens" value="1"><?php endif; ?>
    <input type="search" name="q" aria-label="Rechercher" value="<?= htmlspecialchars($filtres['q']) ?>" placeholder="Étudiant, matricule ou objet" class="filtre-recherche">
    <select name="statut" aria-label="Statut">
        <option value="">Tous les statuts</option>
        <?php foreach (['SOUMIS', 'EN_EXAMEN', 'ETUDIANT_ENTENDU', 'VALIDE', 'REJETE', 'ANNULE', 'CLOTURE'] as $st): ?>
            <?php [, $libelle] = Signalement::libelleStatut($st); ?>
            <option value="<?= $st ?>"<?= $filtres['statut'] === $st ? ' selected' : '' ?>><?= $libelle ?></option>
        <?php endforeach; ?>
    </select>
    <select name="domaine" aria-label="Domaine">
        <option value="">Tous les domaines</option>
        <?php foreach ($domaines as $d): ?><option value="<?= (int)$d['ID_DOMAINE'] ?>"<?= $filtres['domaine'] == $d['ID_DOMAINE'] ? ' selected' : '' ?>><?= htmlspecialchars($d['NOM_DOMAINE']) ?></option><?php endforeach; ?>
    </select>
    <select name="promo" aria-label="Promotion">
        <option value="">Toutes les promotions</option>
        <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= $filtres['promo'] == $p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO']) ?></option><?php endforeach; ?>
    </select>
    <div class="barre-filtres-actions">
        <button type="submit" class="btn btn-sombre"><?= Icone::svg('filtre', 15) ?> Filtrer</button>
    </div>
</form>

<div class="dashboard-card">
    <?php if (empty($dossiers)): ?>
        <?= Composant::etatVide('drapeau', 'Aucun dossier', $mesDossiers ? "Vous n'avez transmis aucun signalement." : 'Aucun signalement ne correspond à ces critères.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date des faits</th><th>Étudiant</th><th>Objet</th><th>Critère</th><th>Statut</th><th>Suivi</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dossiers as $d): ?>
                    <?php [$classe, $libelle] = Signalement::libelleStatut($d['STATUT']); ?>
                    <tr>
                        <td data-label="Date des faits"><?= Format::dateHeure($d['DATE_FAITS']) ?></td>
                        <td data-label="Étudiant" class="cellule-large"><strong><?= htmlspecialchars($d['ETUDIANT_NOM'] . ' ' . $d['ETUDIANT_PRENOM']) ?></strong><br><small><?= htmlspecialchars($d['LIEU_SIGNALEMENT']) ?></small></td>
                        <td data-label="Objet" class="cellule-large"><?= htmlspecialchars($d['TITRE_SIGNALEMENT']) ?><br><small>Signalé par <?= htmlspecialchars($d['AUTEUR_ROLE'] ?? 'Personnel') ?></small></td>
                        <td data-label="Critère" class="cellule-large"><?= htmlspecialchars($d['NOM_DOMAINE']) ?><br><small><?= htmlspecialchars($d['LIBELLE_CRITERE']) ?> (<?= Format::points((float)$d['VALEUR_POINTS'], true) ?>)</small></td>
                        <td data-label="Statut"><span class="badge <?= $classe ?>"><?= $libelle ?></span></td>
                        <td data-label="Suivi">
                            <?php if ($d['DATE_DECISION']): ?><span class="badge badge-gris">Décidé</span>
                            <?php elseif ($d['REPONSE_ETUDIANT'] !== null): ?><span class="badge badge-vert">Réponse reçue</span>
                            <?php else: ?><span class="badge badge-orange">En attente</span><?php endif; ?>
                        </td>
                        <td><a href="index.php?action=admin_signalement&id=<?= (int)$d['ID_SIGNALEMENT'] ?>" class="btn btn-secondaire btn-petit"><?= Icone::svg('oeil', 14) ?> Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../../partials/pagination.php'; ?>
    <?php endif; ?>
</div>
