<?php
require "../db.php";
$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "id" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
]);

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($validated["userid"]);

/* Optional: zuerst Subcats löschen, falls keine FK-Cascade existiert */
$pdo->prepare("DELETE FROM subcategories WHERE categoryid=? AND userid=?")
    ->execute([$validated["id"], $validated["userid"]]);

$stmt = $pdo->prepare("DELETE FROM categories WHERE id=? AND userid=?");
$stmt->execute([$validated["id"], $validated["userid"]]);

echo json_encode(["success" => true]);
