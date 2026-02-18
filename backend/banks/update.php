<?php
require "../db.php";
$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("
    UPDATE banks 
    SET name=?, iban=?, amount=?, bankfirma=? 
    WHERE id=? AND userid=?
");

$stmt->execute([
    $data["name"],
    $data["iban"],
    $data["amount"],
    $data["bankfirma"],
    $data["id"],
    $data["userid"]
]);

echo json_encode(["success" => true]);
