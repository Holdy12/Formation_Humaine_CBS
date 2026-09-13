<div class="dashboard-header">
    <div>
        <h2>Mon club</h2>
        <p>Les activités de votre club comptent dans votre note : présence aux séances, engagement et esprit d'initiative.</p>
    </div>
</div>

<?php if ($monClub): ?>
<div class="grille-2">
    <div class="dashboard-card">
        <h3><?= htmlspecialchars($monClub['NOM_CLUB']) ?></h3>
        <div class="details" style="margin-bottom: 15px;">
            <div class="detail"><span class="cle">Responsable</span><span class="val"><?= $monClub['RESP_NOM'] ? htmlspecialchars($monClub['RESP_PRENOM'] . ' ' . $monClub['RESP_NOM']) : 'Non désigné' ?></span></div>
            <div class="detail"><span class="cle">Membres</span><span class="val"><?= (int)$monClub['NB_MEMBRES'] ?></span></div>
        </div>
        <?php if ($monClub['DESCRIPTION']): ?><div class="bloc-texte"><?= htmlspecialchars($monClub['DESCRIPTION']) ?></div><?php endif; ?>
    </div>

    <div class="dashboard-card">
        <h3>Séances à venir</h3>
        <?php if (empty($seances)): ?>
            <?= Composant::etatVide('calendrier', 'Aucune séance programmée', 'Les prochaines séances de votre club apparaîtront ici dès que le responsable les aura planifiées.') ?>
        <?php else: ?>
            <div class="defilement">
                <table class="activity-table">
                    <thead><tr><th>Date</th><th>Séance</th><th>Lieu</th></tr></thead>
                    <tbody>
                    <?php foreach ($seances as $s): ?>
                        <tr>
                            <td data-label="Date"><?= Format::date($s['DATE_SEANCE']) ?><br><small><?= Format::heure($s['HEURE_DEBUT']) ?> – <?= Format::heure($s['HEURE_FIN']) ?></small></td>
                            <td data-label="Séance"><?= htmlspecialchars($s['TITRE_SEANCE']) ?></td>
                            <td data-label="Lieu"><?= htmlspecialchars($s['LIEU']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
    <div class="alerte alerte-info"><?= Icone::svg('info', 16) ?><span>Vous n'êtes membre d'aucun club. Rapprochez-vous d'un responsable de club pour y adhérer.</span></div>
<?php endif; ?>

<div class="dashboard-card">
    <h3>Les clubs de l'école</h3>
    <div class="defilement">
        <table class="activity-table">
            <thead><tr><th>Club</th><th>Responsable</th><th>Membres</th><th>Présentation</th></tr></thead>
            <tbody>
            <?php foreach ($clubs as $c): ?>
                <tr>
                    <td data-label="Club"><strong><?= htmlspecialchars($c['NOM_CLUB']) ?></strong><?= $monClub && (int)$c['ID_CLUB'] === (int)$monClub['ID_CLUB'] ? ' <span class="badge badge-vert">Mon club</span>' : '' ?></td>
                    <td data-label="Responsable"><?= $c['RESP_NOM'] ? htmlspecialchars($c['RESP_PRENOM'] . ' ' . $c['RESP_NOM']) : '—' ?></td>
                    <td data-label="Membres"><?= (int)$c['NB_MEMBRES'] ?></td>
                    <td data-label="Présentation" class="cellule-large"><?= htmlspecialchars($c['DESCRIPTION'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
