<div class="dashboard-header">
    <div>
        <h2>Séances</h2>
        <p>Planifiez une séance à l'avance ou retrouvez celles qui attendent encore leur appel.</p>
    </div>
    <a href="index.php?action=admin_appel" class="btn btn-principal"><?= Icone::svg('appel', 16) ?> Faire l'appel</a>
</div>

<div class="dashboard-card">
    <h3>Planifier une séance</h3>
    <form method="post" action="index.php?action=admin_seance_planifier" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <div class="champ-ligne">
            <?php if (!empty($promotions)): ?>
            <div class="champ">
                <label for="promo">Promotion</label>
                <select name="promo" id="promo">
                    <option value="">Aucune</option>
                    <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"><?= htmlspecialchars($p['CODE_PROMO']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="champ">
                <label for="club">Club</label>
                <select name="club" id="club">
                    <option value="">Aucun</option>
                    <?php foreach ($clubs as $c): ?><option value="<?= (int)$c['ID_CLUB'] ?>"><?= htmlspecialchars($c['NOM_CLUB']) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="champ-ligne">
            <div class="champ"><label for="titre">Intitulé</label><input type="text" name="titre" id="titre" required maxlength="100" placeholder="Ex : Sortie reboisement"></div>
            <div class="champ"><label for="lieu">Lieu</label><input type="text" name="lieu" id="lieu" required maxlength="50" placeholder="Ex : Campus"></div>
        </div>
        <div class="champ-ligne">
            <div class="champ"><label for="date">Date</label><input type="date" name="date" id="date" required value="<?= date('Y-m-d') ?>"></div>
            <div class="champ"><label for="debut">Début</label><input type="time" name="debut" id="debut" required value="08:00"></div>
            <div class="champ"><label for="fin">Fin</label><input type="time" name="fin" id="fin" required value="10:00"></div>
        </div>
        <div class="aide">Une séance concerne soit une promotion, soit un club.</div>
        <div class="actions"><button type="submit" class="btn btn-secondaire"><?= Icone::svg('calendrier', 16) ?> Planifier</button></div>
    </form>
</div>

<div class="dashboard-card">
    <h3>Séances enregistrées</h3>
    <?php if (empty($seances)): ?>
        <?= Composant::etatVide('calendrier', 'Aucune séance', "Planifiez une séance ou faites directement l'appel.") ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date</th><th>Séance</th><th>Cible</th><th>Appel</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($seances as $s): ?>
                    <tr>
                        <td data-label="Date"><?= Format::date($s['DATE_SEANCE']) ?><br><small><?= Format::heure($s['HEURE_DEBUT']) ?> – <?= Format::heure($s['HEURE_FIN']) ?></small></td>
                        <td data-label="Séance" class="cellule-large"><strong><?= htmlspecialchars($s['TITRE_SEANCE']) ?></strong><br><small><?= htmlspecialchars($s['LIEU']) ?></small></td>
                        <td data-label="Cible"><?= htmlspecialchars($s['NOM_CLUB'] ?: 'Promotion ' . $s['CODE_PROMO']) ?></td>
                        <td data-label="Appel"><?= $s['APPEL_FAIT'] ? '<span class="badge badge-vert">Fait, ' . (int)$s['PRESENTS'] . ' relevé(s)</span>' : '<span class="badge badge-orange">À faire</span>' ?></td>
                        <td><?php if (!$s['APPEL_FAIT']): ?><a href="index.php?action=admin_appel&seance=<?= (int)$s['ID_SEANCE'] ?>" class="btn btn-secondaire btn-petit"><?= Icone::svg('appel', 14) ?> Faire l'appel</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php require __DIR__ . '/../../partials/pagination.php'; ?>
    <?php endif; ?>
</div>
