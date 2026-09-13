<?php
$libelles = [
    'LIBELLE_ANNEE' => 'Libellé', 'DATE_DEBUT' => 'Début', 'DATE_FIN' => 'Fin', 'ID_ANNEE' => 'Année',
    'CODE_SEMESTRE' => 'Code', 'LIBELLE_SEMESTRE' => 'Libellé', 'CODE_DEPT' => 'Code', 'NOM_DEPT' => 'Nom',
    'ID_DEPT' => 'Département', 'CODE_FILIERE' => 'Code', 'NOM_FILIERE' => 'Nom', 'CODE_NIVEAU' => 'Code',
    'LIBELLE_NIVEAU' => 'Libellé', 'ID_NIVEAU' => 'Niveau', 'ID_FILIERE' => 'Filière', 'CODE_PROMO' => 'Code',
];
$listes = ['ID_ANNEE' => [$annees, 'ID_ANNEE', 'LIBELLE_ANNEE'], 'ID_DEPT' => [$departements, 'ID_DEPT', 'NOM_DEPT'],
           'ID_NIVEAU' => [$niveaux, 'ID_NIVEAU', 'LIBELLE_NIVEAU'], 'ID_FILIERE' => [$filieres, 'ID_FILIERE', 'NOM_FILIERE']];
$champs = $entites[$entite]['champs'];
$champ = function (string $c, array $ligne = []) use ($listes, $libelles) {
    $valeur = $ligne[$c] ?? '';
    if (isset($listes[$c])) {
        [$options, $cle, $texte] = $listes[$c];
        $html = '<select name="' . $c . '" aria-label="' . $libelles[$c] . '">';
        foreach ($options as $o) {
            $html .= '<option value="' . (int)$o[$cle] . '"' . ((int)$valeur === (int)$o[$cle] ? ' selected' : '') . '>' . htmlspecialchars($o[$texte]) . '</option>';
        }
        return $html . '</select>';
    }
    $type = str_starts_with($c, 'DATE_') ? 'date' : 'text';
    return '<input type="' . $type . '" name="' . $c . '" value="' . htmlspecialchars((string)$valeur) . '" aria-label="' . $libelles[$c] . '"' . ($c === 'LIBELLE_SEMESTRE' ? '' : ' required') . '>';
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
    <form method="post" action="index.php?action=admin_structure&entite=<?= $entite ?>" class="barre-filtres" style="margin: 0;">
        <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
        <input type="hidden" name="op" value="creer">
        <?php foreach ($champs as $c): ?><?= $champ($c) ?><?php endforeach; ?>
        <div class="barre-filtres-actions"><button type="submit" class="btn btn-sombre"><?= Icone::svg('plus', 15) ?> Ajouter</button></div>
    </form>
</div>

<div class="dashboard-card">
    <h3><?= htmlspecialchars($entites[$entite]['titre']) ?></h3>
    <?php if (empty($lignes)): ?>
        <?= Composant::etatVide('batiment', 'Aucun enregistrement', 'Ajoutez un premier enregistrement avec le formulaire ci-dessus.') ?>
    <?php else: ?>
        <div class="defilement">
            <table class="activity-table">
                <tbody>
                <?php foreach ($lignes as $l): ?>
                    <?php $id = (int)($l['ID_ANNEE'] ?? $l['ID_SEMESTRE'] ?? $l['ID_DEPT'] ?? $l['ID_FILIERE'] ?? $l['ID_NIVEAU'] ?? $l['ID_PROMO'] ?? 0); ?>
                    <tr>
                        <td class="cellule-large">
                            <form method="post" action="index.php?action=admin_structure&entite=<?= $entite ?>" class="barre-filtres ligne-structure">
                                <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <?php foreach ($champs as $c): ?><?= $champ($c, $l) ?><?php endforeach; ?>
                                <div class="barre-filtres-actions">
                                    <button type="submit" name="op" value="modifier" class="btn btn-secondaire btn-petit"><?= Icone::svg('valide', 14) ?> Enregistrer</button>
                                    <button type="submit" name="op" value="supprimer" class="btn btn-fantome btn-petit" onclick="return confirm('Supprimer cet enregistrement ?');"><?= Icone::svg('fermer', 14) ?></button>
                                </div>
                            </form>
                            <?php if ($entite === 'semestre'): ?>
                                <?php $clos = Structure::semestreEstClos($id); ?>
                                <div class="ligne-cloture">
                                    <span class="badge <?= $clos ? 'badge-gris' : 'badge-vert' ?>"><?= $clos ? 'Clôturé' : 'En cours' ?></span>
                                    <?php if (Auth::peut('semestre.cloturer')): ?>
                                        <form method="post" action="index.php?action=<?= $clos ? 'admin_semestre_rouvrir' : 'admin_semestre_cloturer' ?>">
                                            <input type="hidden" name="jeton" value="<?= htmlspecialchars($jeton) ?>">
                                            <input type="hidden" name="semestre" value="<?= $id ?>">
                                            <button type="submit" class="btn btn-secondaire btn-petit" onclick="return confirm('<?= $clos ? 'Rouvrir ce semestre ?' : 'Clôturer ce semestre et figer les notes ?' ?>');">
                                                <?= Icone::svg($clos ? 'recharger' : 'diplome', 14) ?> <?= $clos ? 'Rouvrir' : 'Clôturer le semestre' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($entite === 'promotion'): ?>
                                <?php foreach ($promotions as $p): if ((int)$p['ID_PROMO'] === $id): ?>
                                    <div class="aide"><?= (int)$p['EFFECTIF'] ?> étudiant(s) actif(s), <?= htmlspecialchars($p['LIBELLE_NIVEAU'] . ' ' . $p['NOM_FILIERE']) ?></div>
                                <?php endif; endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
