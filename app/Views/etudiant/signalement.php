<?php [$classe, $libelle] = Signalement::libelleStatut($dossier['STATUT']); ?>
<div class="dashboard-header">
    <div>
        <h2><?= htmlspecialchars($dossier['TITRE_SIGNALEMENT']) ?> <span class="badge <?= $classe ?>"><?= $libelle ?></span></h2>
        <p>Signalé par : <?= htmlspecialchars($dossier['AUTEUR_ROLE'] ?? 'Personnel') ?>, le <?= Format::dateHeure($dossier['DATE_SIGNALEMENT']) ?></p>
    </div>
    <a href="index.php?action=etudiant_signalements" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Tous mes signalements</a>
</div>

<div class="grille-2">
    <div class="dashboard-card">
        <h3>Les faits</h3>
        <div class="details" style="margin-bottom: 15px;">
            <div class="detail"><span class="cle">Domaine</span><span class="val"><?= htmlspecialchars($dossier['NOM_DOMAINE']) ?></span></div>
            <div class="detail"><span class="cle">Critère</span><span class="val"><?= htmlspecialchars($dossier['LIBELLE_CRITERE']) ?> (<?= Format::points((float)$dossier['VALEUR_POINTS'], true) ?>)</span></div>
            <div class="detail"><span class="cle">Date et heure</span><span class="val"><?= Format::dateHeure($dossier['DATE_FAITS']) ?></span></div>
            <div class="detail"><span class="cle">Lieu</span><span class="val"><?= htmlspecialchars($dossier['LIEU_SIGNALEMENT']) ?></span></div>
        </div>
        <div class="bloc-texte"><?= htmlspecialchars($dossier['DESCRIPTION']) ?></div>
    </div>

    <div class="dashboard-card">
        <h3>Votre réponse</h3>
        <?php if ($peutRepondre): ?>
            <form method="post" action="index.php?action=etudiant_repondre" class="formulaire">
                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                <input type="hidden" name="signalement" value="<?= (int)$dossier['ID_SIGNALEMENT'] ?>">
                <div class="champ">
                    <label for="reponse"><?= $dossier['REPONSE_ETUDIANT'] !== null ? 'Modifier votre réponse' : 'Vos explications' ?></label>
                    <textarea name="reponse" id="reponse" required placeholder="Présentez votre version des faits ou vos explications"><?= htmlspecialchars($dossier['REPONSE_ETUDIANT'] ?? '') ?></textarea>
                </div>
                <?php if ($dossier['DATE_REPONSE']): ?><div class="aide">Dernière réponse enregistrée le <?= Format::dateHeure($dossier['DATE_REPONSE']) ?>.</div><?php endif; ?>
                <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('envoyer', 14) ?> Enregistrer ma réponse</button></div>
            </form>
        <?php elseif ($dossier['REPONSE_ETUDIANT'] !== null): ?>
            <div class="bloc-texte"><?= htmlspecialchars($dossier['REPONSE_ETUDIANT']) ?></div>
            <div class="aide" style="margin-top: 8px;">Réponse enregistrée le <?= Format::dateHeure($dossier['DATE_REPONSE']) ?>. Le dossier n'accepte plus de modification.</div>
        <?php else: ?>
            <?= Composant::etatVide('message', 'Sans réponse', "Aucune réponse n'a été déposée et le dossier n'accepte plus de réponse.") ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($dossier['DATE_DECISION']): ?>
<div class="dashboard-card">
    <h3>Décision</h3>
    <div class="bloc-texte"><?= htmlspecialchars($dossier['DECISION'] ?? '') ?></div>
    <div class="aide" style="margin-top: 8px;">
        Rendue le <?= Format::dateHeure($dossier['DATE_DECISION']) ?><?= $dossier['VALIDATEUR_NOM'] ? ' par ' . htmlspecialchars($dossier['VALIDATEUR_PRENOM'] . ' ' . $dossier['VALIDATEUR_NOM']) : '' ?>.
    </div>
</div>
<?php endif; ?>
