<?php
require "../db.php";
$data = json_decode(file_get_contents("php://input"), true);

$id = $data["id"] ?? null;
$userid = $data["userid"] ?? null;

if ($id && $userid) {
    $stmt = $pdo->prepare("DELETE FROM banks WHERE id=? AND userid=?");
    $stmt->execute([$id, $userid]);

    // Logo-Datei (falls vorhanden) ebenfalls löschen
    $baseDir = realpath(__DIR__ . "/../../cashflow_frontend/src/assets/bankpng");
    if ($baseDir !== false) {
        $logoPath = $baseDir . DIRECTORY_SEPARATOR . "user" . $userid . "_bank" . $id . ".png";
        if (is_file($logoPath)) {
            @unlink($logoPath);
        }
    }
}

echo json_encode(["success" => true]);
