<?php
$actif_ = $etudiant['STATUT_COMPTE'] === 'ACTIF';
$statutLibelle = !empty($etudiant['EST_DELEGUE'])
    ? Format::genre($etudiant['SEXE'], 'Délégué de promotion', 'Déléguée de promotion')
    : Format::genre($etudiant['SEXE'], 'Étudiant', 'Étudiante');
$libellesPresence = ['PRESENT' => ['badge-vert', 'Présent'], 'RETARD' => ['badge-orange', 'Retard'], 'ABSENT' => ['badge-rouge', 'Absent'], 'ABSENT_JUSTIFIE' => ['badge-bleu', 'Absence justifiée']];
$gere = Auth::peut('etudiants.gerer');
?>
<div class="dashboard-header">
    <div>
        <h2><?= htmlspecialchars($etudiant['NOM'] . ' ' . $etudiant['PRENOM']) ?> <span class="badge <?= $actif_ ? 'badge-vert' : 'badge-gris' ?>"><?= $actif_ ? 'Actif' : 'Inactif' ?></span></h2>
        <p><?= htmlspecialchars($statutLibelle . ', ' . $etudiant['CODE_PROMO'] . ', ' . $etudiant['LIBELLE_NIVEAU'] . ' ' . $etudiant['NOM_FILIERE']) ?></p>
    </div>
    <div class="actions">
        <a href="index.php?action=admin_etudiants" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Liste</a>
        <a href="index.php?action=admin_signalement_nouveau&etudiant=<?= (int)$etudiant['ID_PERSONNE'] ?>" class="btn btn-secondaire"><?= Icone::svg('drapeau', 16) ?> Signaler</a>
        <?php if ($gere): ?><a href="index.php?action=admin_etudiant_modifier&id=<?= (int)$etudiant['ID_PERSONNE'] ?>" class="btn btn-principal"><?= Icone::svg('reglages', 16) ?> Modifier</a><?php endif; ?>
    </div>
</div>

<?php if ($motDePasse): ?>
    <div class="alerte alerte-info mot-de-passe-temporaire" role="status">
        <?= Icone::svg('cadenas', 16) ?>
        <span>Mot de passe temporaire de <?= htmlspecialchars($etudiant['MATRICULE']) ?> : <code><?= htmlspecialchars($motDePasse) ?></code>. Communiquez-le à l'étudiant ; il devra le changer à sa première connexion. Il ne sera plus affiché.</span>
    </div>
<?php endif; ?>

<div class="grille-profil">
    <div class="dashboard-card carte-identite">
        <div class="avatar avatar-profil"><?= $etudiant['PHOTO'] ? '<img src="' . htmlspecialchars($etudiant['PHOTO']) . '" alt="">' : Icone::svg('personne', 44) ?></div>
        <h3 class="identite-nom"><?= htmlspecialchars($etudiant['PRENOM'] . ' ' . $etudiant['NOM']) ?></h3>
        <div class="identite-statut"><?= htmlspecialchars($statutLibelle) ?></div>
        <span class="badge badge-bleu identite-matricule"><?= htmlspecialchars($etudiant['MATRICULE']) ?></span>
        <div class="details details-identite">
            <div class="detail"><span class="cle">Email</span><span class="val"><?= htmlspecialchars($etudiant['EMAIL']) ?></span></div>
            <div class="detail"><span class="cle">Téléphone</span><span class="val"><?= htmlspecialchars($etudiant['TELEPHONE']) ?></span></div>
            <div class="detail"><span class="cle">Adresse</span><span class="val"><?= $etudiant['ADRESSE'] ? htmlspecialchars($etudiant['ADRESSE']) : 'Non renseignée' ?></span></div>
            <div class="detail"><span class="cle">Date de naissance</span><span class="val"><?= Format::date($etudiant['DATE_NAISSANCE']) ?></span></div>
            <div class="detail"><span class="cle">Club</span><span class="val"><?= $etudiant['NOM_CLUB'] ? htmlspecialchars($etudiant['NOM_CLUB']) : 'Aucun' ?></span></div>
            <div class="detail"><span class="cle">Compte créé le</span><span class="val"><?= Format::date($etudiant['DATE_CREATION']) ?></span></div>
        </div>
        <?php if ($gere): ?>
        <div class="actions-fiche">
            <form method="post" action="index.php?action=admin_etudiant_reinitialiser"><input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>"><input type="hidden" name="id" value="<?= (int)$etudiant['ID_PERSONNE'] ?>"><button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg('cadenas', 14) ?> Réinitialiser le mot de passe</button></form>
            <form method="post" action="index.php?action=admin_etudiant_statut" onsubmit="return confirm('<?= $actif_ ? 'Désactiver ce compte ?' : 'Réactiver ce compte ?' ?>');"><input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>"><input type="hidden" name="id" value="<?= (int)$etudiant['ID_PERSONNE'] ?>"><button type="submit" class="btn btn-secondaire btn-petit"><?= Icone::svg($actif_ ? 'fermer' : 'valide', 14) ?> <?= $actif_ ? 'Désactiver le compte' : 'Réactiver le compte' ?></button></form>
            <?php if (!$aHistorique): ?>
            <form method="post" action="index.php?action=admin_etudiant_supprimer" onsubmit="return confirm('Supprimer définitivement cet étudiant ?');"><input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>"><input type="hidden" name="id" value="<?= (int)$etudiant['ID_PERSONNE'] ?>"><button type="submit" class="btn btn-secondaire btn-petit btn-danger"><?= Icone::svg('fermer', 14) ?> Supprimer</button></form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="colonne-profil">
        <?php if ($voitPoints): ?>
        <div class="dashboard-card">
            <div class="entete-carte">
                <h3>Formation Humaine</h3>
                <form method="get" action="index.php" class="actions">
                    <input type="hidden" name="action" value="admin_etudiant"><input type="hidden" name="id" value="<?= (int)$etudiant['ID_PERSONNE'] ?>">
                    <select name="semestre" class="filter-btn" onchange="this.form.submit()">
                        <?php foreach ($semestres as $s): ?><option value="<?= (int)$s['ID_SEMESTRE'] ?>"<?= $semestre && (int)$s['ID_SEMESTRE'] === (int)$semestre['ID_SEMESTRE'] ? ' selected' : '' ?>><?= htmlspecialchars(($s['LIBELLE_SEMESTRE'] ?: $s['CODE_SEMESTRE']) . ', ' . $s['LIBELLE_ANNEE']) ?></option><?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php if (!$semestre): ?>
                <?= Composant::etatVide('calendrier', 'Aucun semestre', "Aucun semestre n'est configuré.") ?>
            <?php else: ?>
            <div class="resultat">
                <div class="resultat-valeur<?= $solde['solde'] < Parametre::nombre('SEUIL_CRITIQUE_NOTE', 10) ? ' critique' : '' ?>"><?= Format::points($solde['solde']) ?><small>/ <?= Format::points($solde['maximum']) ?></small></div>
                <div class="solde-decomposition">
                    <div><span>Pénalités</span><b class="points-moins">−<?= Format::points($solde['penalites']) ?></b></div>
                    <div><span>Bonifications retenues</span><b class="points-plus">+<?= Format::points($solde['bonus']) ?></b></div>
                    <div><span>Note finale</span><b><?= $resultat && $resultat['STATUT_VALIDATION'] === 'CLOTURE' ? Format::points((float)$resultat['NOTE_FINALE']) . ' (' . htmlspecialchars($resultat['MENTION'] ?? '—') . ')' : 'Semestre en cours' ?></b></div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h3>Registre des points</h3>
            <?php if (empty($mouvements)): ?>
                <?= Composant::etatVide('etoile', 'Aucun mouvement', 'Aucun point sur ce semestre.') ?>
            <?php else: ?>
            <div class="defilement"><table class="activity-table">
                <thead><tr><th>Date</th><th>Critère</th><th>Motif</th><th>Points</th><th>Validé par</th></tr></thead>
                <tbody><?php foreach ($mouvements as $m): $valeur = ($m['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$m['NOMBRE_POINTS']; ?>
                    <tr><td data-label="Date"><?= Format::date($m['DATE_MOUVEMENT']) ?></td><td data-label="Critère"><?= htmlspecialchars($m['NOM_DOMAINE'] . ' · ' . $m['LIBELLE_CRITERE']) ?></td><td data-label="Motif" class="cellule-large"><?= htmlspecialchars($m['MOTIF_MOUVEMENT'] ?? '') ?><?php if ($m['ID_SIGNALEMENT']): ?> <a href="index.php?action=admin_signalement&id=<?= (int)$m['ID_SIGNALEMENT'] ?>">Dossier</a><?php endif; ?></td><td data-label="Points" class="<?= $valeur < 0 ? 'points-moins' : 'points-plus' ?>"><?= Format::points($valeur, true) ?></td><td data-label="Validé par"><?= $m['VALIDATEUR_NOM'] ? htmlspecialchars($m['VALIDATEUR_PRENOM'] . ' ' . $m['VALIDATEUR_NOM']) : '—' ?></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <h3>Présences</h3>
            <?php if (empty($presences)): ?>
                <?= Composant::etatVide('calendrier', 'Aucune séance', 'Aucune présence enregistrée sur ce semestre.') ?>
            <?php else: ?>
            <div class="defilement"><table class="activity-table">
                <thead><tr><th>Date</th><th>Séance</th><th>Statut</th><th>Justificatif</th></tr></thead>
                <tbody><?php foreach ($presences as $p): [$c, $l] = $libellesPresence[$p['STATUT']] ?? ['badge-gris', $p['STATUT']]; ?>
                    <tr><td data-label="Date"><?= Format::date($p['DATE_SEANCE']) ?></td><td data-label="Séance" class="cellule-large"><?= htmlspecialchars($p['TITRE_SEANCE']) ?><br><small><?= htmlspecialchars($p['NOM_CLUB'] ?: 'Promotion') ?></small></td><td data-label="Statut"><span class="badge <?= $c ?>"><?= $l ?></span></td><td data-label="Justificatif"><?= $p['ID_JUSTIFICATION'] ? htmlspecialchars($p['STATUT_VALIDATION']) : '—' ?></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h3>Signalements</h3>
            <?php if (empty($dossiers)): ?>
                <?= Composant::etatVide('drapeau', 'Aucun signalement', Auth::peut('signalements.consulter_tous') ? 'Aucun dossier ne concerne cet étudiant.' : "Aucun dossier que vous avez transmis ne concerne cet étudiant.") ?>
            <?php else: ?>
            <div class="defilement"><table class="activity-table">
                <thead><tr><th>Date des faits</th><th>Objet</th><th>Critère</th><th>Statut</th><th></th></tr></thead>
                <tbody><?php foreach ($dossiers as $d): [$c, $l] = Signalement::libelleStatut($d['STATUT']); ?>
                    <tr><td data-label="Date"><?= Format::dateHeure($d['DATE_FAITS']) ?></td><td data-label="Objet" class="cellule-large"><?= htmlspecialchars($d['TITRE_SIGNALEMENT']) ?></td><td data-label="Critère"><?= htmlspecialchars($d['LIBELLE_CRITERE']) ?></td><td data-label="Statut"><span class="badge <?= $c ?>"><?= $l ?></span></td><td><a href="index.php?action=admin_signalement&id=<?= (int)$d['ID_SIGNALEMENT'] ?>" class="btn btn-secondaire btn-petit">Ouvrir</a></td></tr>
                <?php endforeach; ?></tbody>
            </table></div>
            <?php endif; ?>
        </div>
    </div>
</div>
