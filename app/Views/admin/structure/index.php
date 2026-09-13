<?php
$libelles = [
    'LIBELLE_ANNEE' => 'Libellé', 'DATE_DEBUT' => 'Date de début', 'DATE_FIN' => 'Date de fin', 'ID_ANNEE' => 'Année académique',
    'CODE_SEMESTRE' => 'Code', 'LIBELLE_SEMESTRE' => 'Libellé', 'CODE_DEPT' => 'Code', 'NOM_DEPT' => 'Nom du département',
    'ID_DEPT' => 'Département', 'CODE_FILIERE' => 'Code', 'NOM_FILIERE' => 'Nom de la filière', 'CODE_NIVEAU' => 'Code',
    'LIBELLE_NIVEAU' => 'Libellé', 'ID_NIVEAU' => 'Niveau', 'ID_FILIERE' => 'Filière', 'CODE_PROMO' => 'Code de la promotion',
];
$exemples = ['LIBELLE_ANNEE' => '2026-2027', 'CODE_SEMESTRE' => 'S1', 'LIBELLE_SEMESTRE' => 'Semestre 1', 'CODE_DEPT' => 'GI',
             'NOM_DEPT' => 'Génie Informatique', 'CODE_FILIERE' => 'GI', 'NOM_FILIERE' => 'Génie Informatique', 'CODE_NIVEAU' => 'L1',
             'LIBELLE_NIVEAU' => 'Licence 1', 'CODE_PROMO' => 'L1-GI-2026'];
$listes = ['ID_ANNEE' => [$annees, 'ID_ANNEE', 'LIBELLE_ANNEE'], 'ID_DEPT' => [$departements, 'ID_DEPT', 'NOM_DEPT'],
           'ID_NIVEAU' => [$niveaux, 'ID_NIVEAU', 'LIBELLE_NIVEAU'], 'ID_FILIERE' => [$filieres, 'ID_FILIERE', 'NOM_FILIERE']];
$champs = $entites[$entite]['champs'];
$champ = function (string $c, string $prefixe, array $ligne = []) use ($listes, $libelles, $exemples) {
    $valeur = $ligne[$c] ?? '';
    $id = $prefixe . '-' . strtolower($c);
    $html = '<div class="champ"><label for="' . $id . '">' . $libelles[$c] . '</label>';
    if (isset($listes[$c])) {
        [$options, $cle, $texte] = $listes[$c];
        $html .= '<select name="' . $c . '" id="' . $id . '" required>';
        if (!$ligne) {
            $html .= '<option value="">Choisir</option>';
        }
        foreach ($options as $o) {
            $html .= '<option value="' . (int)$o[$cle] . '"' . ((int)$valeur === (int)$o[$cle] ? ' selected' : '') . '>' . htmlspecialchars($o[$texte]) . '</option>';
        }
        return $html . '</select></div>';
    }
    $type = str_starts_with($c, 'DATE_') ? 'date' : 'text';
    $placeholder = isset($exemples[$c]) ? ' placeholder="Ex : ' . htmlspecialchars($exemples[$c]) . '"' : '';
    return $html . '<input type="' . $type . '" name="' . $c . '" id="' . $id . '" value="' . htmlspecialchars((string)$valeur) . '"' . $placeholder . ($c === 'LIBELLE_SEMESTRE' ? '' : ' required') . '></div>';
};
?>
<div class="dashboard-header">
    <div>
        <h2>Structure académique</h2>
        <p>Années, semestres et organisation pédagogique. Un enregistrement rattaché à des données existantes ne peut pas être supprimé.</p>
    </div>
</div>

<nav class="onglets">
    <?php foreach ($entites as $cle => $e): ?>
        <a href="index.php?action=admin_structure&entite=<?= $cle ?>" class="onglet<?= $entite === $cle ? ' actif' : '' ?>"><?= htmlspecialchars($e['titre']) ?></a>
    <?php endforeach; ?>
</nav>

<div class="dashboard-card">
    <h3>Ajouter</h3>
    <form method="post" action="index.php?action=admin_structure&entite=<?= $entite ?>" class="formulaire formulaire-large">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <input type="hidden" name="op" value="creer">
        <div class="grille-champs">
            <?php foreach ($champs as $c): ?><?= $champ($c, 'nouveau') ?><?php endforeach; ?>
        </div>
        <div class="actions"><button type="submit" class="btn btn-principal"><?= Icone::svg('plus', 16) ?> Ajouter</button></div>
    </form>
</div>

<div class="dashboard-card">
    <h3><?= htmlspecialchars($entites[$entite]['titre']) ?></h3>
    <?php if (empty($lignes)): ?>
        <?= Composant::etatVide('batiment', 'Aucun enregistrement', 'Ajoutez un premier enregistrement avec le formulaire ci-dessus.') ?>
    <?php else: ?>
        <div class="liste-editions">
        <?php foreach ($lignes as $l): ?>
            <?php $id = (int)($l['ID_ANNEE'] ?? $l['ID_SEMESTRE'] ?? $l['ID_DEPT'] ?? $l['ID_FILIERE'] ?? $l['ID_NIVEAU'] ?? $l['ID_PROMO'] ?? 0); ?>
            <div class="ligne-edition">
                <form method="post" action="index.php?action=admin_structure&entite=<?= $entite ?>" class="ligne-edition-formulaire">
                    <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div class="grille-champs">
                        <?php foreach ($champs as $c): ?><?= $champ($c, $entite . '-' . $id, $l) ?><?php endforeach; ?>
                    </div>
                    <div class="ligne-edition-actions">
                        <button type="submit" name="op" value="modifier" class="btn btn-secondaire btn-petit"><?= Icone::svg('valide', 14) ?> Enregistrer</button>
                        <button type="submit" name="op" value="supprimer" class="btn btn-fantome btn-petit btn-danger" onclick="return confirm('Supprimer cet enregistrement ?');"><?= Icone::svg('fermer', 14) ?> Supprimer</button>
                    </div>
                </form>
                <?php if ($entite === 'semestre'): ?>
                    <?php $clos = Structure::semestreEstClos($id); ?>
                    <div class="ligne-edition-pied">
                        <span class="badge <?= $clos ? 'badge-gris' : 'badge-vert' ?>"><?= $clos ? 'Clôturé' : 'En cours' ?></span>
                        <span class="aide"><?= $clos ? 'Les points de ce semestre sont figés et les notes finales publiées.' : 'Les points restent modifiables jusqu\'à la clôture.' ?></span>
                        <?php if (Auth::peut('semestre.cloturer')): ?>
                            <form method="post" action="index.php?action=<?= $clos ? 'admin_semestre_rouvrir' : 'admin_semestre_cloturer' ?>">
                                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                                <input type="hidden" name="semestre" value="<?= $id ?>">
                                <button type="submit" class="btn <?= $clos ? 'btn-secondaire' : 'btn-sombre' ?> btn-petit" onclick="return confirm('<?= $clos ? 'Rouvrir ce semestre ?' : 'Clôturer ce semestre et figer les notes ?' ?>');">
                                    <?= Icone::svg($clos ? 'recharger' : 'diplome', 14) ?> <?= $clos ? 'Rouvrir' : 'Clôturer le semestre' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php elseif ($entite === 'promotion'): ?>
                    <?php foreach ($promotions as $p): if ((int)$p['ID_PROMO'] === $id): ?>
                        <div class="ligne-edition-pied"><span class="aide"><?= (int)$p['EFFECTIF'] ?> étudiant<?= (int)$p['EFFECTIF'] > 1 ? 's' : '' ?> actif<?= (int)$p['EFFECTIF'] > 1 ? 's' : '' ?>, <?= htmlspecialchars($p['LIBELLE_NIVEAU'] . ' ' . $p['NOM_FILIERE']) ?></span></div>
                    <?php endif; endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
