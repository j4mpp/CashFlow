<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("INSERT INTO transactions(userid, subcategoryid, name, description, amount, bankid) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $data["userid"],
    $data["subcategoryid"],
    $data["name"],
    $data["description"],
    $data["amount"],
    $data["bankid"]
]);

echo json_encode([
    "success" => true,
    "id" => $pdo->lastInsertId()
]);
