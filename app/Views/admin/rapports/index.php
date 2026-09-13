<?php $libelleSemestre = $semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE']; $lienExport = 'index.php?action=admin_rapports_export&semestre=' . (int)$semestre['ID_SEMESTRE']; ?>
<div class="dashboard-header">
    <div>
        <h2>Rapports</h2>
        <p><?= htmlspecialchars($libelleSemestre) ?>, du <?= Format::date($semestre['DATE_DEBUT']) ?> au <?= Format::date($semestre['DATE_FIN']) ?><?= $clos ? ', semestre clôturé' : ', semestre en cours' ?>.</p>
    </div>
    <button type="button" class="btn btn-secondaire" onclick="window.print()"><?= Icone::svg('imprimante', 16) ?> Imprimer</button>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_rapports">
    <label class="filtre-groupe"><span>Semestre</span>
        <select name="semestre" onchange="this.form.submit()">
            <?php foreach ($semestres as $s): ?><option value="<?= (int)$s['ID_SEMESTRE'] ?>"<?= (int)$s['ID_SEMESTRE'] === (int)$semestre['ID_SEMESTRE'] ? ' selected' : '' ?>><?= htmlspecialchars(($s['LIBELLE_SEMESTRE'] ?: $s['CODE_SEMESTRE']) . ', ' . $s['LIBELLE_ANNEE']) ?></option><?php endforeach; ?>
        </select>
    </label>
    <label class="filtre-groupe"><span>Promotion</span>
        <select name="promo" onchange="this.form.submit()">
            <option value="">Toutes</option>
            <?php foreach ($promotions as $p): ?><option value="<?= (int)$p['ID_PROMO'] ?>"<?= $idPromo === (int)$p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO']) ?></option><?php endforeach; ?>
        </select>
    </label>
</form>

<?php $critiques = array_sum(array_column($synthese['promotions'], 'CRITIQUES')); ?>
<div class="stats-grid">
    <div class="stat-card"><div class="stat-value"><?= (int)$synthese['effectif'] ?></div><div class="stat-title">Étudiants actifs</div></div>
    <div class="stat-card"><div class="stat-value"><?= Format::points($synthese['moyenne']) ?> / 20</div><div class="stat-title">Note moyenne</div></div>
    <div class="stat-card"><div class="stat-value<?= $critiques > 0 ? ' points-moins' : '' ?>"><?= $critiques ?></div><div class="stat-title">Sous le seuil de <?= Format::points($synthese['seuil']) ?></div></div>
    <div class="stat-card"><div class="stat-value"><?= array_sum(array_column($domaines, 'TOTAL')) ?></div><div class="stat-title">Signalements</div></div>
</div>

<div class="grille-2">
    <div class="dashboard-card">
        <div class="entete-carte"><h3>Résultats par promotion</h3><a href="<?= $lienExport ?>&type=soldes<?= $idPromo ? '&promo=' . $idPromo : '' ?>" class="btn btn-fantome btn-petit"><?= Icone::svg('document', 14) ?> Exporter les notes</a></div>
        <?php if (empty($synthese['promotions'])): ?>
            <?= Composant::etatVide('diplome', 'Aucun résultat', 'Aucun étudiant actif ne correspond à cette sélection.') ?>
        <?php else: ?>
            <div class="defilement">
                <table class="activity-table">
                    <thead><tr><th>Promotion</th><th>Effectif</th><th>Moyenne</th><th>Min.</th><th>Max.</th><th>Sous le seuil</th></tr></thead>
                    <tbody>
                    <?php foreach ($synthese['promotions'] as $p): ?>
                        <tr>
                            <td data-label="Promotion"><strong><?= htmlspecialchars($p['CODE_PROMO']) ?></strong></td>
                            <td data-label="Effectif"><?= (int)$p['EFFECTIF'] ?></td>
                            <td data-label="Moyenne"><?= Format::points($p['MOYENNE']) ?></td>
                            <td data-label="Min."><?= Format::points($p['MIN']) ?></td>
                            <td data-label="Max."><?= Format::points($p['MAX']) ?></td>
                            <td data-label="Sous le seuil"><?= $p['CRITIQUES'] ? '<span class="badge badge-rouge">' . (int)$p['CRITIQUES'] . '</span>' : '<span class="badge badge-vert">0</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-card">
        <h3>Répartition des mentions</h3>
        <?php if (empty($synthese['mentions'])): ?>
            <?= Composant::etatVide('diplome', 'Aucune mention', 'Les mentions apparaîtront dès qu\'un étudiant aura un solde sur la période.') ?>
        <?php else: ?>
            <div class="barres-mentions">
                <?php $ordre = ['Très bien', 'Bien', 'Assez bien', 'Passable', 'Insuffisant']; ?>
                <?php foreach ($ordre as $mention): if (!isset($synthese['mentions'][$mention])) continue; $n = $synthese['mentions'][$mention]; ?>
                    <div class="barre-mention">
                        <span class="barre-mention-libelle"><?= htmlspecialchars($mention) ?></span>
                        <span class="barre-mention-piste"><span class="barre-mention-remplissage" style="width: <?= round($n * 100 / max(1, $synthese['effectif'])) ?>%"></span></span>
                        <span class="barre-mention-valeur"><?= $n ?></span>
                    </div>
                <?php endforeach; ?>
                <?php foreach ($synthese['mentions'] as $mention => $n): if (in_array($mention, $ordre, true)) continue; ?>
                    <div class="barre-mention">
                        <span class="barre-mention-libelle"><?= htmlspecialchars($mention) ?></span>
                        <span class="barre-mention-piste"><span class="barre-mention-remplissage" style="width: <?= round($n * 100 / max(1, $synthese['effectif'])) ?>%"></span></span>
                        <span class="barre-mention-valeur"><?= $n ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="grille-2">
    <div class="dashboard-card">
        <div class="entete-carte"><h3>Signalements par domaine</h3><a href="<?= $lienExport ?>&type=signalements" class="btn btn-fantome btn-petit"><?= Icone::svg('document', 14) ?> Exporter</a></div>
        <?php if (empty($domaines)): ?>
            <?= Composant::etatVide('drapeau', 'Aucun signalement', 'Aucun signalement sur la période.') ?>
        <?php else: ?>
            <div class="defilement">
                <table class="activity-table">
                    <thead><tr><th>Domaine</th><th>Total</th><th>Validés</th><th>Rejetés</th><th>En cours</th><th>Conseil</th></tr></thead>
                    <tbody>
                    <?php foreach ($domaines as $d): ?>
                        <tr>
                            <td data-label="Domaine"><strong><?= htmlspecialchars($d['NOM_DOMAINE']) ?></strong></td>
                            <td data-label="Total"><?= (int)$d['TOTAL'] ?></td>
                            <td data-label="Validés"><?= (int)$d['VALIDES'] ?></td>
                            <td data-label="Rejetés"><?= (int)$d['REJETES'] ?></td>
                            <td data-label="En cours"><?= (int)$d['EN_COURS'] ?></td>
                            <td data-label="Conseil"><?= (int)$d['CONSEILS'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="dashboard-card">
        <div class="entete-carte"><h3>Assiduité par promotion</h3><a href="<?= $lienExport ?>&type=assiduite" class="btn btn-fantome btn-petit"><?= Icone::svg('document', 14) ?> Exporter</a></div>
        <?php if (empty($assiduite)): ?>
            <?= Composant::etatVide('appel', 'Aucun relevé', 'Aucun appel enregistré sur la période.') ?>
        <?php else: ?>
            <div class="defilement">
                <table class="activity-table">
                    <thead><tr><th>Promotion</th><th>Relevés</th><th>Présents</th><th>Retards</th><th>Absences</th><th>Justifiées</th></tr></thead>
                    <tbody>
                    <?php foreach ($assiduite as $a): ?>
                        <tr>
                            <td data-label="Promotion"><strong><?= htmlspecialchars($a['CODE_PROMO']) ?></strong></td>
                            <td data-label="Relevés"><?= (int)$a['RELEVES'] ?></td>
                            <td data-label="Présents"><?= (int)$a['PRESENTS'] ?> <small>(<?= $a['RELEVES'] ? round($a['PRESENTS'] * 100 / $a['RELEVES']) : 0 ?> %)</small></td>
                            <td data-label="Retards"><?= (int)$a['RETARDS'] ?></td>
                            <td data-label="Absences"><?= (int)$a['ABSENCES'] ?></td>
                            <td data-label="Justifiées"><?= (int)$a['JUSTIFIEES'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="dashboard-card">
    <h3>Critères les plus fréquents</h3>
    <?php if (empty($criteres)): ?>
        <?= Composant::etatVide('etoile', 'Aucun mouvement', 'Aucun point attribué ou retiré sur la période.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Critère</th><th>Domaine</th><th>Occurrences</th><th>Points cumulés</th></tr></thead>
                <tbody>
                <?php foreach ($criteres as $c): ?>
                    <tr>
                        <td data-label="Critère"><strong><?= htmlspecialchars($c['LIBELLE_CRITERE']) ?></strong></td>
                        <td data-label="Domaine"><?= htmlspecialchars($c['NOM_DOMAINE']) ?></td>
                        <td data-label="Occurrences"><?= (int)$c['N'] ?></td>
                        <td data-label="Points cumulés" class="<?= $c['TYPE_MOUVEMENT'] === 'NEGATIF' ? 'points-moins' : 'points-plus' ?>"><?= Format::points(($c['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$c['POINTS'], true) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="dashboard-card">
    <div class="entete-carte"><h3>Détail des notes</h3><span class="aide"><?= count($soldes) ?> étudiant<?= count($soldes) > 1 ? 's' : '' ?></span></div>
    <?php if (empty($soldes)): ?>
        <?= Composant::etatVide('personne', 'Aucun étudiant', 'Aucun étudiant actif ne correspond à cette sélection.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Étudiant</th><th>Promotion</th><th>Pénalités</th><th>Bonifications</th><th>Note</th><th>Mention</th></tr></thead>
                <tbody>
                <?php foreach ($soldes as $s): ?>
                    <tr>
                        <td data-label="Étudiant" class="cellule-large"><a href="index.php?action=admin_etudiant&id=<?= (int)$s['ID_PERSONNE'] ?>"><?= htmlspecialchars($s['NOM'] . ' ' . $s['PRENOM']) ?></a><br><small><?= htmlspecialchars($s['MATRICULE']) ?></small></td>
                        <td data-label="Promotion"><?= htmlspecialchars($s['CODE_PROMO']) ?></td>
                        <td data-label="Pénalités" class="points-moins"><?= $s['PENALITES'] > 0 ? Format::points(-$s['PENALITES'], true) : '—' ?></td>
                        <td data-label="Bonifications" class="points-plus"><?= $s['BONUS'] > 0 ? Format::points($s['BONUS'], true) : '—' ?></td>
                        <td data-label="Note"><strong class="<?= $s['SOLDE'] < $synthese['seuil'] ? 'points-moins' : '' ?>"><?= Format::points($s['SOLDE']) ?></strong></td>
                        <td data-label="Mention"><?= htmlspecialchars($s['MENTION']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
