<div class="dashboard-header">
    <div>
        <h2>Bonjour <?= htmlspecialchars($etudiant['PRENOM']) ?></h2>
        <p><?= htmlspecialchars($etudiant['LIBELLE_NIVEAU'] . ' ' . $etudiant['NOM_FILIERE'] . ', promotion ' . $etudiant['CODE_PROMO']) ?></p>
    </div>
</div>

<?php if (!$semestre): ?>
    <div class="dashboard-card"><?= Composant::etatVide('calendrier', 'Aucun semestre ouvert', "Votre solde apparaîtra dès que l'administration aura ouvert le semestre.") ?></div>
<?php else: ?>

<?php foreach ($alertes as $alerte): ?>
    <div class="alerte alerte-info"><?= $alerte ?></div>
<?php endforeach; ?>

<?php
    $seuil = Parametre::nombre('SEUIL_CRITIQUE_NOTE', 10);
    $critique = $solde['solde'] < $seuil;
    $pourcentage = $solde['maximum'] > 0 ? round($solde['solde'] / $solde['maximum'] * 100, 1) : 0;
    $positionSeuil = $solde['maximum'] > 0 ? round($seuil / $solde['maximum'] * 100, 1) : 0;
?>
<section class="solde-hero" aria-label="Solde de Formation Humaine">
    <div>
        <div class="solde-titre">Votre note de Formation Humaine, <?= htmlspecialchars(($semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE']) . ' ' . $semestre['LIBELLE_ANNEE']) ?></div>
        <div class="solde-valeur<?= $critique ? ' critique' : '' ?>"><?= Format::points($solde['solde']) ?><small>/ <?= Format::points($solde['maximum']) ?></small></div>
        <div class="jauge" role="img" aria-label="<?= Format::points($solde['solde']) ?> sur <?= Format::points($solde['maximum']) ?>">
            <div class="jauge-remplissage" data-cible="<?= $pourcentage ?>"></div>
            <div class="jauge-seuil" style="left: <?= $positionSeuil ?>%" data-libelle="seuil <?= Format::points($seuil) ?>"></div>
        </div>
        <div class="jauge-bornes"><span>0</span><span><?= Format::points($solde['maximum']) ?></span></div>
    </div>
    <div>
        <div class="solde-decomposition">
            <div><span>Capital de départ</span><b><?= Format::points($solde['capital']) ?></b></div>
            <div><span>Pénalités</span><b class="points-moins">−<?= Format::points($solde['penalites']) ?></b></div>
            <div><span>Bonifications retenues</span><b class="points-plus">+<?= Format::points($solde['bonus']) ?></b></div>
            <div><span>Note provisoire</span><b><?= Format::points($solde['solde']) ?></b></div>
        </div>
        <p class="solde-note" style="margin-top: 12px;">
            <?php if ($solde['brut'] > $solde['maximum']): ?>
                Vos bonifications dépassent le plafond : la note reste bloquée à <?= Format::points($solde['maximum']) ?>.
            <?php else: ?>
                Calculée à chaque instant à partir du registre des points, après validation du chargé de discipline.
            <?php endif; ?>
        </p>
    </div>
</section>

<div class="dashboard-row" style="margin-top: 24px;">
    <div class="dashboard-card">
        <h3>Par domaine</h3>
        <div class="domaines">
            <?php foreach ($solde['domaines'] as $d): ?>
                <?php
                // Une seule échelle par domaine : son plafond quand il existe, pour que la barre
                // corresponde au « sur X » affiché à côté ; sinon le capital de départ.
                $reference = $d['PLAFOND'] > 0 ? $d['PLAFOND'] : max($solde['capital'], 0.01);
                $largeurPlus = min(100, (int)round($d['BONUS_RETENU'] / $reference * 100));
                $largeurMoins = min(100 - $largeurPlus, (int)round($d['NEGATIF'] / $reference * 100));
                ?>
                <div class="domaine">
                    <span class="nom"><?= htmlspecialchars($d['NOM_DOMAINE']) ?></span>
                    <div class="barre">
                        <div class="plus" style="width: <?= $largeurPlus ?>%"></div>
                        <div class="moins" style="width: <?= $largeurMoins ?>%"></div>
                    </div>
                    <span class="chiffres">
                        <?php if ($d['POSITIF'] == 0 && $d['NEGATIF'] == 0): ?>
                            aucun mouvement<?php if ($d['PLAFOND'] !== null): ?>, plafond +<?= Format::points($d['PLAFOND']) ?><?php endif; ?>
                        <?php else: ?>
                            <?php if ($d['POSITIF'] > 0): ?><span class="plus">+<?= Format::points($d['BONUS_RETENU']) ?></span><?php if ($d['PLAFOND'] !== null): ?> sur <?= Format::points($d['PLAFOND']) ?><?php endif; ?><?php endif; ?>
                            <?php if ($d['POSITIF'] > 0 && $d['NEGATIF'] > 0): ?> · <?php endif; ?>
                            <?php if ($d['NEGATIF'] > 0): ?><span class="moins">−<?= Format::points($d['NEGATIF']) ?></span><?php endif; ?>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="entete-carte">
            <h3>Derniers mouvements</h3>
            <a href="index.php?action=etudiant_points" class="btn btn-secondaire btn-petit">Tout le registre</a>
        </div>
        <?php if (empty($derniers)): ?>
            <?= Composant::etatVide('etoile', 'Aucun mouvement ce semestre', 'Votre capital de ' . Format::points($solde['capital']) . ' est intact. Les points validés par le chargé de discipline apparaîtront ici.') ?>
        <?php else: ?>
            <div class="defilement">
                <table class="activity-table">
                    <thead><tr><th>Date</th><th>Motif</th><th>Points</th></tr></thead>
                    <tbody>
                    <?php foreach ($derniers as $m): ?>
                        <?php $valeur = ($m['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$m['NOMBRE_POINTS']; ?>
                        <tr>
                            <td data-label="Date"><?= Format::date($m['DATE_MOUVEMENT']) ?></td>
                            <td data-label="Motif" class="cellule-large"><strong><?= htmlspecialchars($m['LIBELLE_CRITERE']) ?></strong><br><small><?= htmlspecialchars($m['NOM_DOMAINE']) ?><?= $m['MOTIF_MOUVEMENT'] ? ' — ' . htmlspecialchars($m['MOTIF_MOUVEMENT']) : '' ?></small></td>
                            <td data-label="Points" class="<?= $valeur < 0 ? 'points-moins' : 'points-plus' ?>"><?= Format::points($valeur, true) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        var remplissage = document.querySelector('.jauge-remplissage');
        if (remplissage) { remplissage.style.width = remplissage.dataset.cible + '%'; }
    });
</script>

<?php endif; ?>
