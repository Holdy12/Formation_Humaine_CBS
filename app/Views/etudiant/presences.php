<?php
$libelles = [
    'PRESENT'         => ['badge-vert',   'Présent'],
    'RETARD'          => ['badge-orange', 'Retard'],
    'ABSENT'          => ['badge-rouge',  'Absent'],
    'ABSENT_JUSTIFIE' => ['badge-bleu',   'Absence justifiée'],
];
$validations = [
    'EN_ATTENTE' => ['badge-orange', 'En attente'],
    'VALIDEE'    => ['badge-vert',   'Validé'],
    'REJETEE'    => ['badge-rouge',  'Rejeté'],
];
?>
<div class="dashboard-header">
    <div>
        <h2>Mes présences</h2>
        <p>Un justificatif d'absence doit être déposé dans les <?= (int)$delai ?> heures qui suivent le début de la séance. Il est ensuite examiné par le chargé de discipline.</p>
    </div>
    <?php require __DIR__ . '/../partials/selecteur_semestre.php'; ?>
</div>

<?php if (!$semestre): ?>
    <div class="alerte alerte-info"><?= Icone::svg('info', 16) ?><span>Aucun semestre n'est configuré pour le moment.</span></div>
<?php elseif (empty($presences)): ?>
    <div class="dashboard-card"><?= Composant::etatVide('calendrier', 'Aucune séance ce semestre', "Vos présences apparaîtront ici dès qu'un appel aura été fait pour votre promotion ou votre club.") ?></div>
<?php else: ?>
<div class="dashboard-card">
    <div class="defilement">
        <table class="activity-table">
            <thead><tr><th>Date</th><th>Séance</th><th>Statut</th><th>Justificatif</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($presences as $p): ?>
                <?php [$classe, $libelle] = $libelles[$p['STATUT']] ?? ['badge-gris', $p['STATUT']]; ?>
                <tr>
                    <td data-label="Date"><?= Format::date($p['DATE_SEANCE']) ?><br><small><?= Format::heure($p['HEURE_DEBUT']) ?> – <?= Format::heure($p['HEURE_FIN']) ?></small></td>
                    <td data-label="Séance" class="cellule-large"><strong><?= htmlspecialchars($p['TITRE_SEANCE']) ?></strong><br><small><?= htmlspecialchars($p['NOM_CLUB'] ? $p['NOM_CLUB'] : 'Promotion') ?>, <?= htmlspecialchars($p['LIEU']) ?></small></td>
                    <td data-label="Statut"><span class="badge <?= $classe ?>"><?= $libelle ?></span></td>
                    <td data-label="Justificatif" class="cellule-large">
                        <?php if ($p['ID_JUSTIFICATION']): ?>
                            <?php [$vClasse, $vLibelle] = $validations[$p['STATUT_VALIDATION']] ?? ['badge-gris', $p['STATUT_VALIDATION']]; ?>
                            <span class="badge <?= $vClasse ?>"><?= $vLibelle ?></span>
                            <div><small>Déposé le <?= Format::dateHeure($p['DATE_DEPOT']) ?></small></div>
                            <?php if ($p['CHEMIN_FICHIER']): ?><div><a href="index.php?action=etudiant_fichier&id=<?= (int)$p['ID_JUSTIFICATION'] ?>" target="_blank" class="lien-piece"><?= Icone::svg('trombone', 14) ?> Voir la pièce</a></div><?php endif; ?>
                            <?php if ($p['COMMENTAIRE_VALIDATION']): ?><div><small><?= htmlspecialchars($p['COMMENTAIRE_VALIDATION']) ?></small></div><?php endif; ?>
                        <?php elseif ($p['STATUT'] === 'ABSENT'): ?>
                            <small>Aucun justificatif</small>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (Presence::peutJustifier($p)): ?>
                            <details class="depot">
                                <summary class="btn btn-principal btn-petit"><?= Icone::svg('televerser', 14) ?> Déposer un justificatif</summary>
                                <form method="post" action="index.php?action=etudiant_justificatif" enctype="multipart/form-data" class="formulaire depot-formulaire">
                                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                                    <input type="hidden" name="presence" value="<?= (int)$p['ID_PRESENCE'] ?>">
                                    <div class="champ">
                                        <label for="motif-<?= (int)$p['ID_PRESENCE'] ?>">Motif</label>
                                        <textarea name="motif" id="motif-<?= (int)$p['ID_PRESENCE'] ?>" required placeholder="Expliquez la raison de votre absence"></textarea>
                                    </div>
                                    <div class="champ">
                                        <label>Pièce justificative (facultatif)</label>
                                        <?= Composant::champFichier('fichier', 'fichier-' . (int)$p['ID_PRESENCE'], '.pdf,.jpg,.jpeg,.png,.webp', 'pdf, jpg, png ou webp, 10 Mo max') ?>
                                    </div>
                                    <div class="aide">À déposer avant le <?= Presence::dateLimite($p)->format('d/m/Y \à H\hi') ?>.</div>
                                    <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('envoyer', 14) ?> Envoyer le justificatif</button></div>
                                </form>
                            </details>
                        <?php elseif ($p['STATUT'] === 'ABSENT' && !$p['ID_JUSTIFICATION']): ?>
                            <small class="points-moins">Délai dépassé le <?= Presence::dateLimite($p)->format('d/m/Y \à H\hi') ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
