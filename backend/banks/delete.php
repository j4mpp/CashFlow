<?php
require "../db.php";
$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "id" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
]);

$id = $validated["id"];
$userid = $validated["userid"];

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($userid);

// Delete (inkl. zugehörigem Logo)
$stmt = $pdo->prepare("DELETE FROM banks WHERE id=? AND userid=?");
$stmt->execute([$id, $userid]);

// Logo-Datei (falls vorhanden) ebenfalls löschen
$baseDir = realpath(__DIR__ . "/../img/bankpng");
if ($baseDir !== false) {
    $logoPath = $baseDir . DIRECTORY_SEPARATOR . "user" . $userid . "_bank" . $id . ".png";
    if (is_file($logoPath)) {
        @unlink($logoPath);
    }
}

echo json_encode(["success" => true]);
