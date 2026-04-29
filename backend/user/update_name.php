<?php
declare(strict_types=1);

require "../db.php";

$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100],
]);

cashflow_session_start();
$userid = cashflow_get_authenticated_userid();
if ($userid === null) {
    cashflow_validation_error("Unauthorized", [], 403);
}

$name = $validated["name"];

$stmt = $pdo->prepare("UPDATE user SET name=? WHERE id=?");
$stmt->execute([
    $name,
    $userid
]);

// Session Name synchron halten, damit UI direkt den neuen Wert zeigt.
$_SESSION["name"] = $name;

echo json_encode([
    "success" => true,
    "name" => $name
]);

