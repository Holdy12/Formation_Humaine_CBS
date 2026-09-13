<?php
[$classe, $libelle] = Signalement::libelleStatut($dossier['STATUT']);
$negatif = (float)$dossier['VALEUR_POINTS'] < 0;
$decide = $dossier['DATE_DECISION'] !== null;
$id = (int)$dossier['ID_SIGNALEMENT'];
?>
<div class="dashboard-header">
    <div>
        <h2><?= htmlspecialchars($dossier['TITRE_SIGNALEMENT']) ?> <span class="badge <?= $classe ?>"><?= $libelle ?></span></h2>
        <p>Dossier n° <?= $id ?>, transmis le <?= Format::dateHeure($dossier['DATE_SIGNALEMENT']) ?> par <?= htmlspecialchars($dossier['AUTEUR_PRENOM'] . ' ' . $dossier['AUTEUR_NOM']) ?> (<?= htmlspecialchars($dossier['AUTEUR_ROLE'] ?? 'Personnel') ?>)</p>
    </div>
    <a href="index.php?action=admin_signalements" class="btn btn-secondaire"><?= Icone::svg('retour', 16) ?> Signalements</a>
</div>

<div class="grille-2">
    <div class="colonne-profil">
        <div class="dashboard-card">
            <h3>Les faits</h3>
            <div class="details" style="margin-bottom: 15px;">
                <div class="detail"><span class="cle">Étudiant</span><span class="val"><a href="index.php?action=admin_etudiant&id=<?= (int)$dossier['ID_PERSONNE_ETUDIANT'] ?>"><?= htmlspecialchars($dossier['ETUDIANT_NOM'] . ' ' . $dossier['ETUDIANT_PRENOM']) ?></a><br><small><?= htmlspecialchars($dossier['ETUDIANT_MATRICULE'] . ', ' . $dossier['CODE_PROMO']) ?></small></span></div>
                <div class="detail"><span class="cle">Domaine et critère</span><span class="val"><?= htmlspecialchars($dossier['NOM_DOMAINE']) ?><br><small><?= htmlspecialchars($dossier['LIBELLE_CRITERE']) ?> (<?= Format::points((float)$dossier['VALEUR_POINTS'], true) ?>)</small></span></div>
                <div class="detail"><span class="cle">Date et heure</span><span class="val"><?= Format::dateHeure($dossier['DATE_FAITS']) ?></span></div>
                <div class="detail"><span class="cle">Lieu</span><span class="val"><?= htmlspecialchars($dossier['LIEU_SIGNALEMENT']) ?></span></div>
            </div>
            <div class="bloc-texte"><?= htmlspecialchars($dossier['DESCRIPTION']) ?></div>
        </div>

        <div class="dashboard-card">
            <h3>Réponse de l'étudiant</h3>
            <?php if ($dossier['REPONSE_ETUDIANT'] !== null): ?>
                <div class="bloc-texte"><?= htmlspecialchars($dossier['REPONSE_ETUDIANT']) ?></div>
                <div class="aide" style="margin-top: 8px;">Déposée le <?= Format::dateHeure($dossier['DATE_REPONSE']) ?>.</div>
            <?php else: ?>
                <?= Composant::etatVide('message', 'Aucune réponse', $decide ? "L'étudiant n'a pas répondu avant la décision." : "L'étudiant peut répondre depuis son espace tant qu'aucune décision n'est prise.") ?>
            <?php endif; ?>
        </div>

        <?php if ($dossier['DATE_AUDITION']): ?>
        <div class="dashboard-card">
            <h3>Audition</h3>
            <div class="aide" style="margin-bottom: 10px;">Entendu le <?= Format::dateHeure($dossier['DATE_AUDITION']) ?>.</div>
            <div class="bloc-texte"><?= htmlspecialchars($dossier['NOTES_AUDITION'] ?? '') ?></div>
        </div>
        <?php endif; ?>

        <?php if ($decide): ?>
        <div class="dashboard-card">
            <h3>Décision</h3>
            <div class="bloc-texte"><?= htmlspecialchars($dossier['DECISION'] ?? '') ?></div>
            <div class="aide" style="margin-top: 8px;">
                Rendue le <?= Format::dateHeure($dossier['DATE_DECISION']) ?><?= $dossier['VALIDATEUR_NOM'] ? ' par ' . htmlspecialchars($dossier['VALIDATEUR_PRENOM'] . ' ' . $dossier['VALIDATEUR_NOM']) : '' ?>.
                <?= !empty($dossier['CONSEIL_DISCIPLINE']) ? ' Dossier transmis au conseil de discipline.' : '' ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="colonne-profil">
        <?php if ($peutAgir): ?>
        <div class="dashboard-card">
            <h3>Instruction</h3>
            <?php if ($dossier['STATUT'] === 'SOUMIS'): ?>
                <p class="aide" style="margin-bottom: 14px;">Ouvrez l'instruction pour examiner le dossier et permettre à l'étudiant de s'expliquer.</p>
                <form method="post" action="index.php?action=admin_signalement_ouvrir&id=<?= $id ?>">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <button type="submit" class="btn btn-principal"><?= Icone::svg('oeil', 16) ?> Ouvrir l'instruction</button>
                </form>
            <?php endif; ?>

            <?php if (!$decide && in_array($dossier['STATUT'], ['SOUMIS', 'EN_EXAMEN'], true)): ?>
                <form method="post" action="index.php?action=admin_signalement_audition&id=<?= $id ?>" class="formulaire" style="margin-top: 18px;">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <div class="champ">
                        <label for="date_audition">Date de l'audition</label>
                        <input type="datetime-local" name="date_audition" id="date_audition" required max="<?= date('Y-m-d\TH:i') ?>" value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="champ">
                        <label for="notes_audition">Explications de l'étudiant</label>
                        <textarea name="notes_audition" id="notes_audition" required placeholder="Ce que l'étudiant a déclaré pendant l'audition"></textarea>
                    </div>
                    <div class="actions"><button type="submit" class="btn btn-secondaire"><?= Icone::svg('message', 16) ?> Enregistrer l'audition</button></div>
                </form>
            <?php endif; ?>

            <?php if (!$decide): ?>
                <form method="post" action="index.php?action=admin_signalement_decider&id=<?= $id ?>" class="formulaire" style="margin-top: 18px; padding-top: 18px; border-top: 1px solid var(--border-color);">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <div class="champ">
                        <label>Décision</label>
                        <div class="choix-decision">
                            <label class="case"><input type="radio" name="decision" value="VALIDE" required><span>Valider<small><?= $negatif ? 'Applique le retrait de points' : 'Applique la bonification' ?></small></span></label>
                            <label class="case"><input type="radio" name="decision" value="REJETE"><span>Rejeter<small>Aucun point appliqué</small></span></label>
                            <label class="case"><input type="radio" name="decision" value="ANNULE"><span>Annuler<small>Dossier sans suite</small></span></label>
                        </div>
                    </div>
                    <?php if ($negatif): ?>
                        <label class="case"><input type="checkbox" name="conseil" value="1"><span>Transmis au conseil de discipline<small>Ajoute la pénalité prévue au barème.</small></span></label>
                    <?php endif; ?>
                    <div class="champ">
                        <label for="motif">Motif de la décision</label>
                        <textarea name="motif" id="motif" required placeholder="Ce qui fonde la décision"></textarea>
                    </div>
                    <?php if ($negatif && $dossier['STATUT'] !== 'ETUDIANT_ENTENDU'): ?>
                        <div class="alerte alerte-info"><?= Icone::svg('info', 16) ?><span>L'étudiant doit être entendu avant tout retrait de points : enregistrez l'audition ci-dessus.</span></div>
                    <?php endif; ?>
                    <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('marteau', 16) ?> Enregistrer la décision</button></div>
                </form>
            <?php elseif ($dossier['STATUT'] !== 'CLOTURE'): ?>
                <p class="aide" style="margin-bottom: 14px;">La décision est rendue. Clôturez le dossier pour l'archiver.</p>
                <form method="post" action="index.php?action=admin_signalement_cloturer&id=<?= $id ?>">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <button type="submit" class="btn btn-secondaire"><?= Icone::svg('valide', 16) ?> Clôturer le dossier</button>
                </form>
            <?php else: ?>
                <?= Composant::etatVide('valide', 'Dossier clôturé', 'Ce dossier est archivé. Les mouvements de points restent consultables dans le registre.') ?>
            <?php endif; ?>
        </div>
        <?php elseif ($estAuteur): ?>
        <div class="dashboard-card">
            <h3>Instruction</h3>
            <?= Composant::etatVide('info', 'Dossier entre les mains du chargé de discipline', "Vous avez transmis ce signalement : son instruction revient à une autre personne. Vous en suivez l'avancement ici.") ?>
        </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <h3>Suivi du dossier</h3>
            <ol class="chronologie">
                <?php foreach ($historique as $h): ?>
                    <?php [$c, $l] = Signalement::libelleStatut($h['STATUT']); ?>
                    <li>
                        <span class="chronologie-point"></span>
                        <div>
                            <span class="badge <?= $c ?>"><?= $l ?></span>
                            <div class="chronologie-detail"><?= Format::dateHeure($h['DATE_CHANGEMENT']) ?><?= $h['NOM'] ? ', ' . htmlspecialchars($h['PRENOM'] . ' ' . $h['NOM']) : '' ?></div>
                            <?php if ($h['COMMENTAIRE']): ?><div class="chronologie-commentaire"><?= htmlspecialchars($h['COMMENTAIRE']) ?></div><?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div class="dashboard-card">
            <h3>Témoins</h3>
            <?php if (empty($temoins)): ?>
                <?= Composant::etatVide('groupe', 'Aucun témoin', 'Aucun témoin n\'a été consigné pour ce dossier.') ?>
            <?php else: ?>
                <div class="details">
                    <?php foreach ($temoins as $t): ?>
                        <div class="detail"><span class="cle"><?= htmlspecialchars($t['NOM_TEMOIN'] . ' ' . $t['PRENOM_TEMOIN']) ?></span><span class="val"><?= $t['CONTACT_TEMOIN'] ? htmlspecialchars($t['CONTACT_TEMOIN']) : 'Contact non renseigné' ?></span></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card">
            <h3>Pièces jointes</h3>
            <?php if (empty($pieces)): ?>
                <?= Composant::etatVide('trombone', 'Aucune pièce', "Aucune preuve n'est attachée à ce dossier.") ?>
            <?php else: ?>
                <ul class="liste-pieces">
                    <?php foreach ($pieces as $p): ?>
                        <li><a href="index.php?action=admin_piece&id=<?= (int)$p['ID_PIECE'] ?>" target="_blank" class="lien-piece"><?= Icone::svg('trombone', 14) ?> <?= htmlspecialchars($p['NOM_FICHIER']) ?></a><small><?= htmlspecialchars(strtoupper($p['TYPE_FICHIER'])) ?></small></li>
                    <?php endforeach; ?>
                </ul>
                <div class="aide" style="margin-top: 10px;">Accès réservé aux personnes chargées de l'instruction et à l'auteur du signalement.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
