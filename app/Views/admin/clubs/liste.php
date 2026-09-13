<div class="dashboard-header">
    <div>
        <h2>Clubs</h2>
        <p><?= $peutGerer ? "Créez les clubs et désignez leurs responsables. Chaque responsable gère ensuite ses membres et ses séances." : "Les clubs que vous animez." ?></p>
    </div>
</div>

<?php if ($peutGerer): ?>
<div class="dashboard-card">
    <h3>Nouveau club</h3>
    <form method="post" action="index.php?action=admin_club_enregistrer" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <div class="champ-ligne">
            <div class="champ"><label for="nom">Nom du club</label><input type="text" name="nom" id="nom" required maxlength="100" placeholder="Ex : Club Théâtre"></div>
            <div class="champ">
                <label for="responsable">Responsable</label>
                <select name="responsable" id="responsable">
                    <option value="">À désigner plus tard</option>
                    <?php foreach ($responsables as $r): ?><option value="<?= (int)$r['ID_PERSONNE'] ?>"><?= htmlspecialchars($r['NOM'] . ' ' . $r['PRENOM'] . ', ' . $r['LIBELLE_ROLE']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="champ"><label for="description">Présentation</label><textarea name="description" id="description" placeholder="Objectifs et activités du club"></textarea></div>
        <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Créer le club</button></div>
    </form>
</div>
<?php endif; ?>

<div class="dashboard-card">
    <h3><?= count($clubs) ?> club<?= count($clubs) > 1 ? 's' : '' ?></h3>
    <?php if (empty($clubs)): ?>
        <?= Composant::etatVide('groupe', 'Aucun club', $peutGerer ? 'Créez un premier club avec le formulaire ci-dessus.' : "Aucun club ne vous est confié pour le moment.") ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Club</th><th>Responsable</th><th>Membres</th><th>Présentation</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($clubs as $c): ?>
                    <tr>
                        <td data-label="Club"><strong><?= htmlspecialchars($c['NOM_CLUB']) ?></strong></td>
                        <td data-label="Responsable"><?= $c['RESP_NOM'] ? htmlspecialchars($c['RESP_PRENOM'] . ' ' . $c['RESP_NOM']) : '<span class="badge badge-orange">À désigner</span>' ?></td>
                        <td data-label="Membres"><?= (int)$c['NB_MEMBRES'] ?></td>
                        <td data-label="Présentation" class="cellule-large"><?= htmlspecialchars($c['DESCRIPTION'] ?? '') ?></td>
                        <td><a href="index.php?action=admin_club&id=<?= (int)$c['ID_CLUB'] ?>" class="btn btn-secondaire btn-petit"><?= Icone::svg('oeil', 14) ?> Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
