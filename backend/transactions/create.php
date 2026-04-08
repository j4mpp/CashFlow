<?php
require "../db.php";

$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "subcategoryid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100],
    "description" => ["required" => true, "type" => "string", "trim" => true, "min" => 0, "max" => 1000],
    "amount" => ["required" => true, "type" => "float", "min" => -1000000000000, "max" => 1000000000000],
    "bankid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "date" => ["required" => false, "type" => "date_iso"],
]);

$validatedUserId = $validated["userid"];

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($validatedUserId);

$date = $validated["date"] ?? date("Y-m-d");

$stmt = $pdo->prepare("INSERT INTO transactions(userid, subcategoryid, name, description, amount, bankid, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
$validated["userid"],
$validated["subcategoryid"],
$validated["name"],
$validated["description"],
$validated["amount"],
$validated["bankid"],
    $date
]);

echo json_encode([
    "success" => true,
    "id" => $pdo->lastInsertId()
]);
