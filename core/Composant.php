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
}
