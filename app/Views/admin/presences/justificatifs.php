<div class="dashboard-header">
    <div>
        <h2>Justificatifs d'absence</h2>
        <p><?= (int)$pagination['total'] ?> justificatif<?= $pagination['total'] > 1 ? 's' : '' ?> en attente d'examen. Une validation transforme l'absence en absence justifiée ; un rejet la laisse pénalisable.</p>
    </div>
</div>

<?php if (empty($justificatifs)): ?>
    <div class="dashboard-card"><?= Composant::etatVide('valide', 'Aucun justificatif en attente', 'Les justificatifs déposés par les étudiants apparaîtront ici pour examen.') ?></div>
<?php else: ?>
    <?php foreach ($justificatifs as $j): ?>
        <div class="dashboard-card">
            <div class="entete-carte">
                <h3><?= htmlspecialchars($j['NOM'] . ' ' . $j['PRENOM']) ?><span class="badge badge-gris" style="margin-left: 8px;"><?= htmlspecialchars($j['MATRICULE']) ?></span></h3>
                <span class="badge badge-orange">Déposé le <?= Format::dateHeure($j['DATE_DEPOT']) ?></span>
            </div>
            <div class="details" style="margin-bottom: 14px;">
                <div class="detail"><span class="cle">Séance</span><span class="val"><?= htmlspecialchars($j['TITRE_SEANCE']) ?><br><small><?= htmlspecialchars($j['NOM_CLUB'] ?: 'Promotion ' . $j['CODE_PROMO']) ?></small></span></div>
                <div class="detail"><span class="cle">Date de l'absence</span><span class="val"><?= Format::date($j['DATE_SEANCE']) ?>, <?= Format::heure($j['HEURE_DEBUT']) ?></span></div>
                <div class="detail"><span class="cle">Pièce jointe</span><span class="val"><?= $j['CHEMIN_FICHIER'] ? '<a href="index.php?action=admin_justificatif_fichier&id=' . (int)$j['ID_JUSTIFICATION'] . '" target="_blank" class="lien-piece">' . Icone::svg('trombone', 14) . ' Consulter</a>' : 'Aucune' ?></span></div>
            </div>
            <div class="bloc-texte"><?= htmlspecialchars($j['MOTIF']) ?></div>

            <form method="post" action="index.php?action=admin_justificatif_decider" class="formulaire" style="margin-top: 16px; max-width: none;">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <input type="hidden" name="id" value="<?= (int)$j['ID_JUSTIFICATION'] ?>">
                <div class="champ">
                    <label for="commentaire-<?= (int)$j['ID_JUSTIFICATION'] ?>">Commentaire pour l'étudiant</label>
                    <input type="text" name="commentaire" id="commentaire-<?= (int)$j['ID_JUSTIFICATION'] ?>" maxlength="255" placeholder="Facultatif si vous validez, obligatoire en cas de rejet">
                </div>
                <div class="actions">
                    <button type="submit" name="decision" value="valider" class="btn btn-principal"><?= Icone::svg('valide', 15) ?> Valider l'absence</button>
                    <button type="submit" name="decision" value="rejeter" class="btn btn-secondaire btn-danger"><?= Icone::svg('fermer', 15) ?> Rejeter</button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
    <?php require __DIR__ . '/../../partials/pagination.php'; ?>
<?php endif; ?>
