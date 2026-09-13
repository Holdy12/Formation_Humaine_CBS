<?php
$actionSelecteur = 'etudiant_releve';
$cloture = $resultat && $resultat['STATUT_VALIDATION'] === 'CLOTURE';
$compte = ['PRESENT' => 0, 'RETARD' => 0, 'ABSENT' => 0, 'ABSENT_JUSTIFIE' => 0];
foreach ($presences as $p) {
    if (isset($compte[$p['STATUT']])) {
        $compte[$p['STATUT']]++;
    }
}
?>
<div class="dashboard-header sans-impression">
    <div>
        <h2>Relevé de Formation Humaine</h2>
        <p>Votre situation du semestre, prête à imprimer ou à enregistrer en PDF depuis le navigateur.</p>
    </div>
    <div class="actions">
        <?php require __DIR__ . '/../partials/selecteur_semestre.php'; ?>
        <button type="button" class="btn btn-principal" onclick="window.print()"><?= Icone::svg('document', 16) ?> Imprimer</button>
    </div>
</div>

<?php if (!$semestre): ?>
    <div class="dashboard-card"><?= Composant::etatVide('calendrier', 'Aucun semestre ouvert', "Le relevé sera disponible dès qu'un semestre aura été ouvert par l'administration.") ?></div>
<?php else: ?>

<article class="releve">
    <header class="releve-entete">
        <img src="assets/images/images.jpeg" alt="Logo CBS" class="releve-logo">
        <div class="releve-titre">
            <h1>Relevé de Formation Humaine</h1>
            <p><?= htmlspecialchars(($semestre['LIBELLE_SEMESTRE'] ?: $semestre['CODE_SEMESTRE']) . ', année académique ' . $semestre['LIBELLE_ANNEE']) ?></p>
            <p class="releve-periode">Du <?= Format::date($semestre['DATE_DEBUT']) ?> au <?= Format::date($semestre['DATE_FIN']) ?>, édité le <?= date('d/m/Y') ?></p>
        </div>
    </header>

    <section class="releve-identite">
        <div><span class="libelle">Étudiant<?= Format::genre($etudiant['SEXE'], '', 'e') ?></span><strong><?= htmlspecialchars($etudiant['NOM'] . ' ' . $etudiant['PRENOM']) ?></strong></div>
        <div><span class="libelle">Matricule</span><strong><?= htmlspecialchars($etudiant['MATRICULE']) ?></strong></div>
        <div><span class="libelle">Promotion</span><strong><?= htmlspecialchars($etudiant['CODE_PROMO']) ?></strong></div>
        <div><span class="libelle">Cursus</span><strong><?= htmlspecialchars($etudiant['LIBELLE_NIVEAU'] . ' ' . $etudiant['NOM_FILIERE']) ?></strong></div>
    </section>

    <section class="releve-notes">
        <div class="releve-note">
            <span class="libelle">Note provisoire</span>
            <strong><?= Format::points($solde['solde']) ?> / <?= Format::points($solde['maximum']) ?></strong>
            <small>Capital <?= Format::points($solde['capital']) ?>, pénalités −<?= Format::points($solde['penalites']) ?>, bonifications +<?= Format::points($solde['bonus']) ?></small>
        </div>
        <div class="releve-note">
            <span class="libelle">Note finale</span>
            <?php if ($cloture): ?>
                <strong><?= Format::points((float)$resultat['NOTE_FINALE']) ?> / <?= Format::points($solde['maximum']) ?></strong>
                <small>Mention <?= htmlspecialchars($resultat['MENTION'] ?? '—') ?>, semestre clôturé le <?= Format::date($resultat['DATE_CLOTURE']) ?></small>
            <?php else: ?>
                <strong>—</strong>
                <small>Semestre non clôturé</small>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <h2 class="releve-section">Répartition par domaine</h2>
        <table class="releve-table">
            <thead><tr><th>Domaine</th><th>Bonifications</th><th>Plafond</th><th>Retenu</th><th>Pénalités</th></tr></thead>
            <tbody>
            <?php foreach ($solde['domaines'] as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['NOM_DOMAINE']) ?></td>
                    <td>+<?= Format::points($d['POSITIF']) ?></td>
                    <td><?= $d['PLAFOND'] !== null ? '+' . Format::points($d['PLAFOND']) : '—' ?></td>
                    <td>+<?= Format::points($d['BONUS_RETENU']) ?></td>
                    <td>−<?= Format::points($d['NEGATIF']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section>
        <h2 class="releve-section">Registre des points</h2>
        <?php if (empty($mouvements)): ?>
            <p class="releve-vide">Aucun mouvement de points sur ce semestre.</p>
        <?php else: ?>
            <table class="releve-table">
                <thead><tr><th>Date</th><th>Domaine</th><th>Critère</th><th>Motif</th><th>Points</th><th>Validé par</th></tr></thead>
                <tbody>
                <?php foreach ($mouvements as $m): ?>
                    <?php $valeur = ($m['TYPE_MOUVEMENT'] === 'NEGATIF' ? -1 : 1) * (float)$m['NOMBRE_POINTS']; ?>
                    <tr>
                        <td><?= Format::date($m['DATE_MOUVEMENT']) ?></td>
                        <td><?= htmlspecialchars($m['NOM_DOMAINE']) ?></td>
                        <td><?= htmlspecialchars($m['LIBELLE_CRITERE']) ?></td>
                        <td><?= htmlspecialchars($m['MOTIF_MOUVEMENT'] ?? '') ?></td>
                        <td class="<?= $valeur < 0 ? 'points-moins' : 'points-plus' ?>"><?= Format::points($valeur, true) ?></td>
                        <td><?= $m['VALIDATEUR_NOM'] ? htmlspecialchars($m['VALIDATEUR_PRENOM'] . ' ' . $m['VALIDATEUR_NOM']) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section>
        <h2 class="releve-section">Assiduité</h2>
        <div class="releve-identite">
            <div><span class="libelle">Séances</span><strong><?= count($presences) ?></strong></div>
            <div><span class="libelle">Présences</span><strong><?= $compte['PRESENT'] ?></strong></div>
            <div><span class="libelle">Retards</span><strong><?= $compte['RETARD'] ?></strong></div>
            <div><span class="libelle">Absences</span><strong><?= $compte['ABSENT'] ?> <small>dont <?= $compte['ABSENT_JUSTIFIE'] ?> justifiée<?= $compte['ABSENT_JUSTIFIE'] > 1 ? 's' : '' ?></small></strong></div>
        </div>
    </section>

    <footer class="releve-pied">
        <p>Document généré par l'application Formation Humaine CBS. La note provisoire est calculée à partir du registre des points ; seule la note finale, fixée à la clôture du semestre, fait foi.</p>
    </footer>
</article>

<?php endif; ?>
