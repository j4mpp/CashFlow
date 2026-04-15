<?php
require "../db.php";

$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "categoryid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100],
]);

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($validated["userid"]);

$stmt = $pdo->prepare("INSERT INTO subcategories (userid, categoryid, name) VALUES (?, ?, ?)");
$stmt->execute([
    $validated["userid"],
    $validated["categoryid"],
    $validated["name"]
]);

echo json_encode([
    "success" => true,
    "id" => $pdo->lastInsertId()
]);
