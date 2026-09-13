<div class="dashboard-header">
    <div>
        <h2>Registre des points</h2>
        <p>Toutes les écritures, dans l'ordre des faits. Un mouvement n'est jamais modifié ni supprimé : une erreur se corrige par une écriture inverse.</p>
    </div>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_points">
    <input type="search" name="q" aria-label="Rechercher" value="<?= htmlspecialchars($filtres['q']) ?>" placeholder="Étudiant ou matricule" class="filtre-recherche">
    <select name="promo" aria-label="Promotion">
        <option value="">Toutes les promotions</option>
        <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= $filtres['promo'] == $p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO']) ?></option><?php endforeach; ?>
    </select>
    <select name="domaine" aria-label="Domaine">
        <option value="">Tous les domaines</option>
        <?php foreach ($domaines as $d): ?><option value="<?= (int)$d['ID_DOMAINE'] ?>"<?= $filtres['domaine'] == $d['ID_DOMAINE'] ? ' selected' : '' ?>><?= htmlspecialchars($d['NOM_DOMAINE']) ?></option><?php endforeach; ?>
    </select>
    <select name="sens" aria-label="Sens">
        <option value="">Retraits et bonifications</option>
        <option value="NEGATIF"<?= $filtres['sens'] === 'NEGATIF' ? ' selected' : '' ?>>Retraits</option>
        <option value="POSITIF"<?= $filtres['sens'] === 'POSITIF' ? ' selected' : '' ?>>Bonifications</option>
    </select>
    <label class="filtre-groupe"><span>Du</span><input type="date" name="debut" value="<?= htmlspecialchars($filtres['debut']) ?>"></label>
    <label class="filtre-groupe"><span>au</span><input type="date" name="fin" value="<?= htmlspecialchars($filtres['fin']) ?>"></label>
    <div class="barre-filtres-actions">
        <button type="submit" class="btn btn-sombre"><?= Icone::svg('filtre', 15) ?> Filtrer</button>
        <a href="index.php?action=admin_points_export&<?= htmlspecialchars(http_build_query(array_filter($filtres))) ?>" class="btn btn-fantome"><?= Icone::svg('document', 15) ?> Exporter</a>
    </div>
</form>

<div class="dashboard-card">
    <?php if (empty($mouvements)): ?>
        <?= Composant::etatVide('etoile', 'Aucun mouvement', 'Aucune écriture ne correspond à ces critères.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date des faits</th><th>Étudiant</th><th>Critère</th><th>Motif</th><th>Points</th><th>Validé par</th><?php if ($peutCorriger): ?><th></th><?php endif; ?></tr></thead>
                <tbody>
                <?php foreach ($mouvements as $m): ?>
                    <?php $valeur = ($m['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$m['NOMBRE_POINTS']; ?>
                    <tr>
                        <td data-label="Date des faits"><?= Format::date($m['DATE_MOUVEMENT']) ?></td>
                        <td data-label="Étudiant" class="cellule-large"><a href="index.php?action=admin_etudiant&id=<?= (int)$m['ID_PERSONNE'] ?>"><?= htmlspecialchars($m['NOM'] . ' ' . $m['PRENOM']) ?></a><br><small><?= htmlspecialchars($m['MATRICULE'] . ', ' . $m['CODE_PROMO']) ?></small></td>
                        <td data-label="Critère" class="cellule-large"><?= htmlspecialchars($m['NOM_DOMAINE']) ?><br><small><?= htmlspecialchars($m['LIBELLE_CRITERE']) ?></small></td>
                        <td data-label="Motif" class="cellule-large">
                            <?= htmlspecialchars($m['MOTIF_MOUVEMENT'] ?? '') ?>
                            <?php if ($m['ID_SIGNALEMENT']): ?><br><small><a href="index.php?action=admin_signalement&id=<?= (int)$m['ID_SIGNALEMENT'] ?>">Dossier n° <?= (int)$m['ID_SIGNALEMENT'] ?></a></small><?php endif; ?>
                            <?php if ($m['ID_MOUVEMENT_CORRIGE']): ?><br><span class="badge badge-bleu">Écriture de correction</span><?php elseif ($m['CORRIGE']): ?><br><span class="badge badge-gris">Corrigé</span><?php endif; ?>
                        </td>
                        <td data-label="Points" class="<?= $valeur < 0 ? 'points-moins' : 'points-plus' ?>"><?= Format::points($valeur, true) ?></td>
                        <td data-label="Validé par"><?= $m['VALIDATEUR_NOM'] ? htmlspecialchars($m['VALIDATEUR_PRENOM'] . ' ' . $m['VALIDATEUR_NOM']) : '—' ?></td>
                        <?php if ($peutCorriger): ?>
                        <td>
                            <?php if (!$m['CORRIGE'] && !$m['ID_MOUVEMENT_CORRIGE']): ?>
                                <button type="button" class="btn btn-secondaire btn-petit" data-basculer="correction-<?= (int)$m['ID_MOUVEMENT'] ?>"><?= Icone::svg('recharger', 14) ?> Corriger</button>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php if ($peutCorriger && !$m['CORRIGE'] && !$m['ID_MOUVEMENT_CORRIGE']): ?>
                    <tr class="ligne-panneau" id="correction-<?= (int)$m['ID_MOUVEMENT'] ?>" hidden>
                        <td colspan="7">
                            <form method="post" action="index.php?action=admin_point_corriger" class="formulaire panneau-correction">
                                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                                <input type="hidden" name="mouvement" value="<?= (int)$m['ID_MOUVEMENT'] ?>">
                                <div class="champ">
                                    <label for="motif-<?= (int)$m['ID_MOUVEMENT'] ?>">Motif de la correction de l'écriture du <?= Format::date($m['DATE_MOUVEMENT']) ?> (<?= Format::points($valeur, true) ?>, <?= htmlspecialchars($m['NOM'] . ' ' . $m['PRENOM']) ?>)</label>
                                    <textarea name="motif" id="motif-<?= (int)$m['ID_MOUVEMENT'] ?>" required placeholder="Pourquoi cette écriture est annulée"></textarea>
                                    <div class="aide">Une écriture de sens inverse sera ajoutée ; l'écriture d'origine reste au registre.</div>
                                </div>
                                <div class="actions">
                                    <button type="submit" class="btn btn-principal btn-petit"><?= Icone::svg('valide', 14) ?> Enregistrer la correction</button>
                                    <button type="button" class="btn btn-fantome btn-petit" data-basculer="correction-<?= (int)$m['ID_MOUVEMENT'] ?>">Annuler</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../../partials/pagination.php'; ?>
    <?php endif; ?>
</div>
