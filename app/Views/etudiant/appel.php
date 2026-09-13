<div class="dashboard-header">
    <div>
        <h2>Faire l'appel, <?= htmlspecialchars($etudiant['CODE_PROMO']) ?></h2>
        <p>Renseignez la séance puis le statut de chaque étudiant. Les pénalités éventuelles ne sont appliquées qu'après validation par le chargé de discipline.</p>
    </div>
</div>

<form method="post" action="index.php?action=etudiant_appel">
    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
    <div class="grille-2">
        <div class="dashboard-card">
            <h3>Séance</h3>
            <div class="formulaire">
                <div class="champ">
                    <label for="titre">Intitulé</label>
                    <input type="text" name="titre" id="titre" required maxlength="100" placeholder="Ex : Cours de Formation Humaine" value="<?= htmlspecialchars($saisie['titre'] ?? '') ?>">
                </div>
                <div class="champ-ligne">
                    <div class="champ">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($saisie['date'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="champ">
                        <label for="lieu">Lieu</label>
                        <input type="text" name="lieu" id="lieu" required maxlength="50" placeholder="Ex : Salle B2" value="<?= htmlspecialchars($saisie['lieu'] ?? '') ?>">
                    </div>
                </div>
                <div class="champ-ligne">
                    <div class="champ">
                        <label for="debut">Heure de début</label>
                        <input type="time" name="debut" id="debut" required value="<?= htmlspecialchars($saisie['debut'] ?? '08:00') ?>">
                    </div>
                    <div class="champ">
                        <label for="fin">Heure de fin</label>
                        <input type="time" name="fin" id="fin" required value="<?= htmlspecialchars($saisie['fin'] ?? '10:00') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <h3>Liste de la promotion, <?= count($camarades) ?> étudiant<?= count($camarades) > 1 ? 's' : '' ?></h3>
            <?php if (empty($camarades)): ?>
                <?= Composant::etatVide('groupe', 'Promotion vide', "Aucun étudiant actif n'est rattaché à votre promotion.") ?>
            <?php else: ?>
            <div class="liste-appel">
                <?php foreach ($camarades as $c): ?>
                    <?php $id = (int)$c['ID_PERSONNE']; $choix = $saisie['statut'][$id] ?? 'PRESENT'; ?>
                    <div class="appel-ligne">
                        <div class="appel-nom">
                            <strong><?= htmlspecialchars($c['NOM'] . ' ' . $c['PRENOM']) ?></strong>
                            <small><?= htmlspecialchars($c['MATRICULE']) ?></small>
                        </div>
                        <div class="appel-choix" role="radiogroup" aria-label="Statut de <?= htmlspecialchars($c['PRENOM'] . ' ' . $c['NOM']) ?>">
                            <?php foreach (['PRESENT' => 'Présent', 'RETARD' => 'Retard', 'ABSENT' => 'Absent'] as $valeur => $libelle): ?>
                                <label class="choix choix-<?= strtolower($valeur) ?>">
                                    <input type="radio" name="statut[<?= $id ?>]" value="<?= $valeur ?>"<?= $choix === $valeur ? ' checked' : '' ?>>
                                    <span><?= $libelle ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($camarades)): ?>
    <div class="actions" style="margin: -10px 0 30px;">
        <button type="submit" class="btn btn-principal"><?= Icone::svg('appel', 16) ?> Enregistrer l'appel</button>
    </div>
    <?php endif; ?>
</form>

<div class="dashboard-card">
    <h3>Derniers appels de la promotion</h3>
    <?php if (empty($appels)): ?>
        <?= Composant::etatVide('liste', 'Aucun appel enregistré', 'Les appels faits pour votre promotion, par vous ou par un enseignant, apparaîtront ici.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Séance</th><th>Fait par</th><th>Présents</th><th>Retards</th><th>Absents</th></tr></thead>
                <tbody>
                <?php foreach ($appels as $a): ?>
                    <tr>
                        <td data-label="Séance" class="cellule-large"><strong><?= htmlspecialchars($a['TITRE_SEANCE']) ?></strong><br><small><?= Format::date($a['DATE_SEANCE']) ?> à <?= Format::heure($a['HEURE_DEBUT']) ?></small></td>
                        <td data-label="Fait par"><?= $a['AUTEUR_NOM'] ? htmlspecialchars($a['AUTEUR_PRENOM'] . ' ' . $a['AUTEUR_NOM']) : '—' ?></td>
                        <td data-label="Présents" class="points-plus"><?= (int)$a['PRESENTS'] ?></td>
                        <td data-label="Retards"><?= (int)$a['RETARDS'] ?></td>
                        <td data-label="Absents" class="points-moins"><?= (int)$a['ABSENTS'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
