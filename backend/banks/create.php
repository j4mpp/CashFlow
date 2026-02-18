<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("INSERT INTO banks (userid, name, iban, amount, bankfirma) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $data["userid"],
    $data["name"],
    $data["iban"],
    $data["amount"],
    $data["bankfirma"]
]);

echo json_encode(["message" => "Bank created"]);
