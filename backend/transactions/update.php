<?php
require "../db.php";
$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "id" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
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

$stmt = $pdo->prepare("
    UPDATE transactions 
    SET subcategoryid=?, name=?, description=?, amount=?, bankid=?, date=?
    WHERE id=? AND userid=?
");

$date = $validated["date"] ?? date("Y-m-d");

$stmt->execute([
    $validated["subcategoryid"],
    $validated["name"],
    $validated["description"],
    $validated["amount"],
    $validated["bankid"],
    $date,
    $validated["id"],
    $validated["userid"]
]);
echo json_encode(["success" => true]);