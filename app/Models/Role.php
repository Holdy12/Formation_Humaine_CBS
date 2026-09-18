<?php
// app/Models/Role.php

require_once __DIR__ . '/../../config/database.php';

class Role {
    public static function creer(string $libelle): bool {
    // Génère un code simple à partir du libellé (ex: "SECRETAIRE" -> "SEC" ou strtoupper)
    $code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $libelle), 0, 10));
    
    $db = Database::getConnection();
    $stmt = $db->prepare("INSERT INTO ROLE (CODE_ROLE, LIBELLE_ROLE) VALUES (:code, :libelle)");
    return $stmt->execute([
        'code' => $code,
        'libelle' => $libelle
    ]);
}

    public static function tous(): array {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM ROLE ORDER BY ID_ROLE ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}