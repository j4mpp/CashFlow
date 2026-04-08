<?php
require "../db.php";
$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "id" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100]
]);

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($validated["userid"]);

$stmt = $pdo->prepare("UPDATE categories SET name=? WHERE id=? AND userid=?");
$stmt->execute([$validated["name"], $validated["id"], $validated["userid"]]);

echo json_encode(["success" => true]);
