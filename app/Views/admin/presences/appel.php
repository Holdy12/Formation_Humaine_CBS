<div class="dashboard-header">
    <div>
        <h2>Faire l'appel</h2>
        <p>Choisissez la cible, reprenez une séance planifiée ou saisissez-en une, puis relevez le statut de chaque étudiant. Les pénalités éventuelles s'appliquent ensuite depuis la page Assiduité.</p>
    </div>
</div>

<form method="get" action="index.php" class="barre-filtres">
    <input type="hidden" name="action" value="admin_appel">
    <label class="filtre-groupe"><span>Cible</span>
        <select name="cible" onchange="if (this.form.seance) this.form.seance.value = ''; this.form.submit()">
            <option value="">Choisir</option>
            <?php if (!empty($promotions)): ?>
            <optgroup label="Promotions">
                <?php foreach ($promotions as $p): ?><option value="promo:<?= (int)$p['ID_PROMO'] ?>"<?= $idPromo === (int)$p['ID_PROMO'] ? ' selected' : '' ?>><?= htmlspecialchars($p['CODE_PROMO'] . ', ' . $p['LIBELLE_NIVEAU'] . ' ' . $p['NOM_FILIERE']) ?></option><?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
            <optgroup label="Clubs">
                <?php foreach ($clubs as $c): ?><option value="club:<?= (int)$c['ID_CLUB'] ?>"<?= $idClub === (int)$c['ID_CLUB'] ? ' selected' : '' ?>><?= htmlspecialchars($c['NOM_CLUB']) ?></option><?php endforeach; ?>
            </optgroup>
        </select>
    </label>
    <?php if (!empty($seancesPretes)): ?>
    <label class="filtre-groupe"><span>Séance</span>
        <select name="seance" onchange="this.form.submit()">
            <option value="">Nouvelle séance</option>
            <?php foreach ($seancesPretes as $s): ?>
                <option value="<?= (int)$s['ID_SEANCE'] ?>"<?= $seance && (int)$seance['ID_SEANCE'] === (int)$s['ID_SEANCE'] ? ' selected' : '' ?>><?= htmlspecialchars($s['TITRE_SEANCE']) ?>, <?= Format::date($s['DATE_SEANCE']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php endif; ?>
</form>

<?php if (empty($participants)): ?>
    <div class="dashboard-card"><?= Composant::etatVide('groupe', 'Aucun étudiant', "Choisissez une promotion ou un club comptant au moins un étudiant actif.") ?></div>
<?php else: ?>
<form method="post" action="index.php?action=admin_appel">
    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
    <input type="hidden" name="club" value="<?= $idClub ?>">
    <input type="hidden" name="promo" value="<?= $idPromo ?>">
    <input type="hidden" name="seance" value="<?= $seance ? (int)$seance['ID_SEANCE'] : 0 ?>">
    <div class="grille-2">
        <div class="dashboard-card">
            <h3>Séance</h3>
            <?php if ($seance): ?>
                <div class="details">
                    <div class="detail"><span class="cle">Intitulé</span><span class="val"><?= htmlspecialchars($seance['TITRE_SEANCE']) ?></span></div>
                    <div class="detail"><span class="cle">Date</span><span class="val"><?= Format::date($seance['DATE_SEANCE']) ?>, <?= Format::heure($seance['HEURE_DEBUT']) ?> – <?= Format::heure($seance['HEURE_FIN']) ?></span></div>
                    <div class="detail"><span class="cle">Lieu</span><span class="val"><?= htmlspecialchars($seance['LIEU']) ?></span></div>
                    <div class="detail"><span class="cle">Cible</span><span class="val"><?= htmlspecialchars($seance['NOM_CLUB'] ?: 'Promotion ' . $seance['CODE_PROMO']) ?></span></div>
                </div>
                <div class="aide" style="margin-top: 12px;">Séance déjà planifiée : il ne reste qu'à relever les présences.</div>
            <?php else: ?>
                <div class="formulaire">
                    <div class="champ"><label for="titre">Intitulé</label><input type="text" name="titre" id="titre" required maxlength="100" placeholder="Ex : Cours de Formation Humaine" value="<?= htmlspecialchars($saisie['titre'] ?? '') ?>"></div>
                    <div class="champ-ligne">
                        <div class="champ"><label for="date">Date</label><input type="date" name="date" id="date" required max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($saisie['date'] ?? date('Y-m-d')) ?>"></div>
                        <div class="champ"><label for="lieu">Lieu</label><input type="text" name="lieu" id="lieu" required maxlength="50" placeholder="Ex : Salle B2" value="<?= htmlspecialchars($saisie['lieu'] ?? '') ?>"></div>
                    </div>
                    <div class="champ-ligne">
                        <div class="champ"><label for="debut">Heure de début</label><input type="time" name="debut" id="debut" required value="<?= htmlspecialchars($saisie['debut'] ?? '08:00') ?>"></div>
                        <div class="champ"><label for="fin">Heure de fin</label><input type="time" name="fin" id="fin" required value="<?= htmlspecialchars($saisie['fin'] ?? '10:00') ?>"></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h3><?= count($participants) ?> étudiant<?= count($participants) > 1 ? 's' : '' ?> attendu<?= count($participants) > 1 ? 's' : '' ?></h3>
            <div class="liste-appel">
                <?php foreach ($participants as $p): ?>
                    <?php $id = (int)$p['ID_PERSONNE']; $choix = $saisie['statut'][$id] ?? 'PRESENT'; ?>
                    <div class="appel-ligne">
                        <div class="appel-nom"><strong><?= htmlspecialchars($p['NOM'] . ' ' . $p['PRENOM']) ?></strong><small><?= htmlspecialchars($p['MATRICULE']) ?></small></div>
                        <div class="appel-choix" role="radiogroup" aria-label="Statut de <?= htmlspecialchars($p['PRENOM'] . ' ' . $p['NOM']) ?>">
                            <?php foreach (['PRESENT' => 'Présent', 'RETARD' => 'Retard', 'ABSENT' => 'Absent'] as $valeur => $libelle): ?>
                                <label class="choix choix-<?= strtolower($valeur) ?>"><input type="radio" name="statut[<?= $id ?>]" value="<?= $valeur ?>"<?= $choix === $valeur ? ' checked' : '' ?>><span><?= $libelle ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="actions" style="margin: -10px 0 30px;">
        <button type="submit" class="btn btn-principal"><?= Icone::svg('appel', 16) ?> Enregistrer l'appel</button>
    </div>
</form>
<?php endif; ?>
