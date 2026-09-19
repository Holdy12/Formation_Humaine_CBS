<?php // app/Views/site/capital.php — figure « vingt points » en blocs ajourés ; attend $contenu,
// et $avecPlafonds (faux sur l'accueil, où les cartes des domaines portent déjà les plafonds).
$plafonds = $contenu['plafonds'];
$avecPlafonds = $avecPlafonds ?? true;
$legende = 'Vingt points au départ de chaque semestre. Bonus au plus : '
    . implode(', ', array_map(fn($p) => $p[1] . ' points pour ' . mb_strtolower($p[0]), $plafonds)) . '.';
?>
<figure class="capital" role="img" aria-label="<?= htmlspecialchars($legende) ?>">
    <div class="capital-grille" aria-hidden="true"><?= Composant::blocs((int)$contenu['capital']) ?></div>
    <figcaption aria-hidden="true">
        <p class="capital-titre"><span class="capital-nombre"><?= (int)$contenu['capital'] ?></span> points au départ de chaque semestre</p>
        <?php if ($avecPlafonds): ?>
        <ul class="capital-plafonds">
            <?php foreach ($plafonds as [$nom, $plafond, $classe]): ?>
                <li><span class="capital-mini"><?= Composant::blocs((int)$plafond, 'bloc-pt-' . $classe) ?></span><span>+<?= (int)$plafond ?> au plus, <?= htmlspecialchars(mb_strtolower($nom)) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </figcaption>
</figure>
