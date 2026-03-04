<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$date = $data["date"] ?? null;
if (!$date) {
    $date = date("Y-m-d");
}

$stmt = $pdo->prepare("INSERT INTO transactions(userid, subcategoryid, name, description, amount, bankid, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $data["userid"],
    $data["subcategoryid"],
    $data["name"],
    $data["description"],
    $data["amount"],
    $data["bankid"],
    $date
]);

echo json_encode([
    "success" => true,
    "id" => $pdo->lastInsertId()
]);
