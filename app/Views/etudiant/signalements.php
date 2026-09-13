<div class="dashboard-header">
    <div>
        <h2>Mes signalements</h2>
        <p>Les dossiers qui vous concernent. Vous pouvez donner votre version des faits tant qu'aucune décision n'a été prise.</p>
    </div>
</div>

<div class="dashboard-card">
    <?php if (empty($dossiers)): ?>
        <?= Composant::etatVide('drapeau', 'Aucun signalement', 'Aucun dossier ne vous concerne. Si un signalement est déposé à votre sujet, vous pourrez y répondre ici.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date des faits</th><th>Objet</th><th>Domaine et critère</th><th>Statut</th><th>Réponse</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($dossiers as $d): ?>
                    <?php [$classe, $libelle] = Signalement::libelleStatut($d['STATUT']); ?>
                    <tr>
                        <td data-label="Date des faits"><?= Format::dateHeure($d['DATE_FAITS']) ?></td>
                        <td data-label="Objet" class="cellule-large"><strong><?= htmlspecialchars($d['TITRE_SIGNALEMENT']) ?></strong><br><small><?= htmlspecialchars($d['LIEU_SIGNALEMENT']) ?></small></td>
                        <td data-label="Domaine" class="cellule-large"><?= htmlspecialchars($d['NOM_DOMAINE']) ?><br><small><?= htmlspecialchars($d['LIBELLE_CRITERE']) ?> (<?= Format::points((float)$d['VALEUR_POINTS'], true) ?>)</small></td>
                        <td data-label="Statut"><span class="badge <?= $classe ?>"><?= $libelle ?></span></td>
                        <td data-label="Réponse">
                            <?php if ($d['REPONSE_ETUDIANT'] !== null): ?>
                                <span class="badge badge-vert">Répondu</span>
                            <?php elseif (Signalement::peutRepondre($d)): ?>
                                <span class="badge badge-orange">À répondre</span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><a href="index.php?action=etudiant_signalements&id=<?= (int)$d['ID_SIGNALEMENT'] ?>" class="btn btn-secondaire btn-petit"><?= Icone::svg('oeil', 14) ?> Ouvrir</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
