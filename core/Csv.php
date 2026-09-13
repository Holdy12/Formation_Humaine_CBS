<?php
// core/Csv.php — export CSV lisible par Excel (UTF-8 avec BOM, séparateur point-virgule).

class Csv {
    public static function envoyer(string $nom, array $entetes, iterable $lignes): never {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nom . '-' . date('Y-m-d') . '.csv"');
        $sortie = fopen('php://output', 'w');
        fwrite($sortie, "\xEF\xBB\xBF");
        fputcsv($sortie, $entetes, ';');
        foreach ($lignes as $ligne) {
            fputcsv($sortie, $ligne, ';');
        }
        fclose($sortie);
        exit();
    }
}
