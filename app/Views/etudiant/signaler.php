<div class="dashboard-header">
    <div>
        <h2>Signaler un comportement</h2>
        <p>Le signalement est transmis au chargé de discipline, qui entend l'étudiant concerné avant toute décision. Décrivez les faits avec précision.</p>
    </div>
</div>

<div class="dashboard-card">
    <form method="post" action="index.php?action=etudiant_signaler" enctype="multipart/form-data" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <div class="champ">
            <label for="recherche-etudiant">Étudiant concerné</label>
            <input type="search" id="recherche-etudiant" class="recherche" placeholder="Rechercher par nom, prénom ou matricule" autocomplete="off" aria-controls="selecteur-etudiant" data-selecteur="selecteur-etudiant">
            <div class="selecteur" id="selecteur-etudiant">
                <?php foreach ($camarades as $c): ?>
                    <?php $choisi = (int)($saisie['etudiant'] ?? 0) === (int)$c['ID_PERSONNE']; ?>
                    <label class="selecteur-option<?= $choisi ? ' choisi' : '' ?>" data-texte="<?= htmlspecialchars(mb_strtolower($c['NOM'] . ' ' . $c['PRENOM'] . ' ' . $c['MATRICULE'])) ?>">
                        <input type="radio" name="etudiant" value="<?= (int)$c['ID_PERSONNE'] ?>" required<?= $choisi ? ' checked' : '' ?>>
                        <span class="avatar avatar-petit"><?= Icone::svg('personne', 16) ?></span>
                        <span class="selecteur-nom"><strong><?= htmlspecialchars($c['NOM'] . ' ' . $c['PRENOM']) ?></strong><small><?= htmlspecialchars($c['MATRICULE']) ?></small></span>
                        <span class="selecteur-coche"><?= Icone::svg('valide', 18) ?></span>
                    </label>
                <?php endforeach; ?>
                <div class="selecteur-vide" hidden>Aucun étudiant de la promotion ne correspond à cette recherche.</div>
            </div>
            <div class="aide"><?= count($camarades) ?> étudiant<?= count($camarades) > 1 ? 's' : '' ?> dans votre promotion. Tapez pour filtrer, puis sélectionnez.</div>
        </div>
        <div class="champ-ligne">
            <div class="champ">
                <label for="titre">Objet</label>
                <input type="text" name="titre" id="titre" required maxlength="100" value="<?= htmlspecialchars($saisie['titre'] ?? '') ?>" placeholder="Ex : Retard répété en cours">
            </div>
            <div class="champ">
                <label for="critere">Critère</label>
                <select name="critere" id="critere" required>
                    <option value="">Choisir un critère</option>
                    <?php foreach ($criteres as $domaine => $liste): ?>
                        <optgroup label="<?= htmlspecialchars($domaine) ?>">
                            <?php foreach ($liste as $cr): ?>
                                <option value="<?= (int)$cr['ID_CRITERE'] ?>"<?= (int)($saisie['critere'] ?? 0) === (int)$cr['ID_CRITERE'] ? ' selected' : '' ?>><?= htmlspecialchars($cr['LIBELLE_CRITERE']) ?> (<?= Format::points((float)$cr['VALEUR_POINTS'], true) ?>)</option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="champ-ligne">
            <div class="champ">
                <label for="date_faits">Date et heure des faits</label>
                <input type="datetime-local" name="date_faits" id="date_faits" required max="<?= date('Y-m-d\TH:i') ?>" value="<?= htmlspecialchars($saisie['date_faits'] ?? date('Y-m-d\TH:i')) ?>">
            </div>
            <div class="champ">
                <label for="lieu">Lieu</label>
                <input type="text" name="lieu" id="lieu" required maxlength="255" value="<?= htmlspecialchars($saisie['lieu'] ?? '') ?>" placeholder="Ex : Salle B2">
            </div>
        </div>
        <div class="champ">
            <label for="description">Description des faits</label>
            <textarea name="description" id="description" required placeholder="Ce qui s'est passé, dans l'ordre, avec les personnes présentes"><?= htmlspecialchars($saisie['description'] ?? '') ?></textarea>
        </div>
        <div class="champ">
            <label>Preuves (facultatif)</label>
            <?= Composant::champFichier('preuves[]', 'preuves', '.pdf,.jpg,.jpeg,.png,.webp,.mp4', 'Photos, vidéos ou documents : pdf, jpg, png, webp, mp4, 10 Mo par fichier', true) ?>
            <div class="aide">Les preuves ne sont visibles que du chargé de discipline.</div>
        </div>
        <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('envoyer', 14) ?> Transmettre le signalement</button></div>
    </form>
</div>

<div class="dashboard-card">
    <h3>Signalements que vous avez émis</h3>
    <?php if (empty($emis)): ?>
        <?= Composant::etatVide('drapeau', 'Aucun signalement émis', 'Les signalements que vous transmettez et leur avancement apparaîtront ici.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <thead><tr><th>Date des faits</th><th>Étudiant</th><th>Objet</th><th>Critère</th><th>Statut</th></tr></thead>
                <tbody>
                <?php foreach ($emis as $d): ?>
                    <?php [$classe, $libelle] = Signalement::libelleStatut($d['STATUT']); ?>
                    <tr>
                        <td data-label="Date des faits"><?= Format::dateHeure($d['DATE_FAITS']) ?></td>
                        <td data-label="Étudiant"><?= htmlspecialchars($d['ETUDIANT_NOM'] . ' ' . $d['ETUDIANT_PRENOM']) ?></td>
                        <td data-label="Objet" class="cellule-large"><?= htmlspecialchars($d['TITRE_SIGNALEMENT']) ?></td>
                        <td data-label="Critère"><?= htmlspecialchars($d['LIBELLE_CRITERE']) ?></td>
                        <td data-label="Statut"><span class="badge <?= $classe ?>"><?= $libelle ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
