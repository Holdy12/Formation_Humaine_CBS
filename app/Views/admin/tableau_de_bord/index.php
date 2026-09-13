<div class="dashboard-header">
    <div>
        <h2>Bonjour <?= htmlspecialchars($personnel['PRENOM']) ?></h2>
        <p><?= htmlspecialchars(Permissions::libelleRole($personnel['CODE_ROLE'])) ?><?= $semestre ? ', ' . htmlspecialchars($semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE']) . ' en cours jusqu\'au ' . Format::date($semestre['DATE_FIN']) : ', aucun semestre en cours' ?>.</p>
    </div>
    <?php if (Auth::peut('signalements.creer')): ?>
        <a href="index.php?action=admin_signalement_nouveau" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Nouveau signalement</a>
    <?php endif; ?>
</div>

<?php if (!empty($chiffres)): ?>
<div class="stats-grid">
    <?php foreach ($chiffres as $c): ?>
        <a href="index.php?action=<?= htmlspecialchars($c['lien']) ?>" class="stat-card">
            <div class="stat-value<?= !empty($c['alerte']) && (int)$c['valeur'] > 0 ? ' points-moins' : '' ?>"><?= htmlspecialchars((string)$c['valeur']) ?></div>
            <div class="stat-title"><?= htmlspecialchars($c['libelle']) ?></div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="grille-2">
    <div class="colonne-profil">
        <div class="dashboard-card">
            <h3>À traiter</h3>
            <?php if (empty($aTraiter)): ?>
                <?= Composant::etatVide('valide', 'Rien en attente', "Aucune tâche ne vous attend pour le moment.") ?>
            <?php else: ?>
                <ul class="liste-taches">
                    <?php foreach ($aTraiter as $t): ?>
                        <li><a href="index.php?action=<?= htmlspecialchars($t['action']) ?>"><span class="tache-icone"><?= Icone::svg($t['icone'], 18) ?></span><span class="tache-texte"><?= htmlspecialchars($t['texte']) ?></span><span class="tache-fleche"><?= Icone::svg('chevron_droite', 16) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (!empty($mesClubs)): ?>
        <div class="dashboard-card">
            <h3>Mes clubs</h3>
            <div class="details">
                <?php foreach ($mesClubs as $c): ?>
                    <div class="detail"><span class="cle"><?= htmlspecialchars($c['NOM_CLUB']) ?></span><span class="val"><?= (int)$c['NB_MEMBRES'] ?> membre<?= (int)$c['NB_MEMBRES'] > 1 ? 's' : '' ?><br><small><a href="index.php?action=admin_appel&club=<?= (int)$c['ID_CLUB'] ?>">Faire l'appel</a> · <a href="index.php?action=admin_club&id=<?= (int)$c['ID_CLUB'] ?>">Gérer</a></small></span></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($synthese): ?>
        <div class="dashboard-card">
            <div class="entete-carte"><h3>Promotions</h3><a href="index.php?action=admin_rapports" class="btn btn-fantome btn-petit"><?= Icone::svg('rapport', 14) ?> Rapports</a></div>
            <?php if (empty($synthese['promotions'])): ?>
                <?= Composant::etatVide('diplome', 'Aucune promotion', 'Aucun étudiant actif sur le semestre en cours.') ?>
            <?php else: ?>
                <div class="defilement">
                    <table class="activity-table">
                        <thead><tr><th>Promotion</th><th>Effectif</th><th>Moyenne</th><th>Sous le seuil</th></tr></thead>
                        <tbody>
                        <?php foreach ($synthese['promotions'] as $p): ?>
                            <tr>
                                <td data-label="Promotion"><strong><?= htmlspecialchars($p['CODE_PROMO']) ?></strong></td>
                                <td data-label="Effectif"><?= (int)$p['EFFECTIF'] ?></td>
                                <td data-label="Moyenne"><?= Format::points($p['MOYENNE']) ?></td>
                                <td data-label="Sous le seuil"><?= $p['CRITIQUES'] ? '<span class="badge badge-rouge">' . (int)$p['CRITIQUES'] . '</span>' : '<span class="badge badge-vert">0</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="colonne-profil">
        <div class="dashboard-card">
            <div class="entete-carte"><h3><?= Auth::peut('signalements.consulter_tous') ? 'Derniers signalements' : 'Mes derniers signalements' ?></h3><a href="index.php?action=admin_signalements" class="btn btn-fantome btn-petit"><?= Icone::svg('liste', 14) ?> Tous</a></div>
            <?php if (empty($recents)): ?>
                <?= Composant::etatVide('drapeau', 'Aucun signalement', 'Les signalements transmis apparaîtront ici.') ?>
            <?php else: ?>
                <ul class="liste-recents">
                    <?php foreach ($recents as $r): ?>
                        <?php [$classe, $libelle] = Signalement::libelleStatut($r['STATUT']); ?>
                        <li>
                            <a href="index.php?action=admin_signalement&id=<?= (int)$r['ID_SIGNALEMENT'] ?>">
                                <span class="recent-texte"><strong><?= htmlspecialchars($r['ETUDIANT_NOM'] . ' ' . $r['ETUDIANT_PRENOM']) ?></strong><small><?= htmlspecialchars($r['TITRE_SIGNALEMENT']) ?>, <?= Format::date($r['DATE_FAITS']) ?></small></span>
                                <span class="badge <?= $classe ?>"><?= $libelle ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <?php if (Auth::peut('journal.consulter')): ?>
        <div class="dashboard-card">
            <div class="entete-carte"><h3>Activité récente</h3><a href="index.php?action=admin_journal" class="btn btn-fantome btn-petit"><?= Icone::svg('oeil', 14) ?> Journal</a></div>
            <?php if (empty($journal)): ?>
                <?= Composant::etatVide('oeil', 'Aucune activité', 'Les actions du personnel apparaîtront ici.') ?>
            <?php else: ?>
                <ul class="liste-recents">
                    <?php foreach ($journal as $j): ?>
                        <li><span class="recent-texte"><strong><?= htmlspecialchars($j['ACTION']) ?></strong><small><?= htmlspecialchars(($j['PRENOM'] ? $j['PRENOM'] . ' ' . $j['NOM'] . ', ' : '') . ($j['DETAILS'] ?: '')) ?></small></span><small class="recent-date"><?= Format::dateHeure($j['DATE_CONNEXION']) ?></small></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
