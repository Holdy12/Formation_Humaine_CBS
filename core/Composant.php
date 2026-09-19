<?php
// core/Composant.php — fragments HTML partagés par les vues.
require_once __DIR__ . '/Icone.php';

class Composant {
    public static function etatVide(string $icone, string $titre, string $texte, ?string $lienTexte = null, ?string $lienUrl = null): string {
        $html = '<div class="etat-vide">'
              . '<div class="etat-vide-icone">' . Icone::svg($icone, 24) . '</div>'
              . '<h4>' . htmlspecialchars($titre) . '</h4>'
              . '<p>' . htmlspecialchars($texte) . '</p>';
        if ($lienTexte !== null && $lienUrl !== null) {
            $html .= '<a href="' . htmlspecialchars($lienUrl) . '" class="btn btn-secondaire btn-petit">' . htmlspecialchars($lienTexte) . '</a>';
        }
        return $html . '</div>';
    }

    // Zone de dépôt de fichier : bouton, glisser-déposer et liste des fichiers choisis.
    public static function champFichier(string $nom, string $id, string $accept, string $aide, bool $multiple = false, bool $obligatoire = false): string {
        return '<div class="zone-fichier">'
             . '<input type="file" name="' . htmlspecialchars($nom) . '" id="' . htmlspecialchars($id) . '" accept="' . htmlspecialchars($accept) . '"'
             . ($multiple ? ' multiple' : '') . ($obligatoire ? ' required' : '') . ' class="zone-fichier-entree">'
             . '<label for="' . htmlspecialchars($id) . '" class="zone-fichier-etiquette">'
             . '<span class="zone-fichier-icone">' . Icone::svg('televerser', 20) . '</span>'
             . '<span class="zone-fichier-texte"><strong>' . ($multiple ? 'Choisir des fichiers' : 'Choisir un fichier') . '</strong> ou déposer ici</span>'
             . '<span class="zone-fichier-aide">' . htmlspecialchars($aide) . '</span>'
             . '</label>'
             . '<ul class="zone-fichier-liste" aria-live="polite"></ul>'
             . '</div>';
    }

    // Photo du site vitrine : WebP avec repli JPEG, deux largeurs (720 px et pleine largeur) pour que
    // les téléphones ne chargent que la petite. Le bouton qui l'entoure ouvre la photo en grand
    // (visionneuse de cadre_fin.php). $photo vient de app/Views/site/contenu.php.
    public static function photo(array $photo, string $tailles, bool $prioritaire = false, string $classe = ''): string {
        $base = 'assets/images/site/' . $photo['fichier'];
        $l = (int)$photo['largeur'];
        $srcset = fn(string $ext) => htmlspecialchars("$base-720.$ext 720w, $base.$ext {$l}w");
        $chargement = $prioritaire ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
        return '<button type="button" class="' . htmlspecialchars(trim('photo ' . $classe)) . '" data-agrandir="' . htmlspecialchars("$base.jpg") . '"'
            . ' data-legende="' . htmlspecialchars($photo['legende'] ?? '') . '" aria-label="' . htmlspecialchars('Agrandir la photo : ' . $photo['alt']) . '">'
            . '<picture>'
            . '<source type="image/webp" srcset="' . $srcset('webp') . '" sizes="' . htmlspecialchars($tailles) . '">'
            . '<img src="' . htmlspecialchars("$base.jpg") . '" srcset="' . $srcset('jpg') . '" sizes="' . htmlspecialchars($tailles) . '"'
            . ' width="' . $l . '" height="' . (int)$photo['hauteur'] . '" alt="' . htmlspecialchars($photo['alt']) . '"' . $chargement . ' decoding="async">'
            . '</picture></button>';
    }

    // Rangée de blocs ajourés, motif des claustras du campus : sert à figurer des points (site vitrine).
    public static function blocs(int $nombre, string $classe = ''): string {
        $classes = htmlspecialchars(trim('bloc-pt ' . $classe));
        $html = '';
        for ($n = 0; $n < $nombre; $n++) {
            $html .= '<span class="' . $classes . '" style="--n:' . $n . '"></span>';
        }
        return $html;
    }
}
