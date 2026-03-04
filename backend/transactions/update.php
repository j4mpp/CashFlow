<?php
require "../db.php";
$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("
    UPDATE transactions 
    SET name=?, description=?, amount=?, bankid=? 
    WHERE id=? AND userid=?
");

$stmt->execute([
    $data["userid"],
    $data["subcategoryid"],
    $data["name"],
    $data["description"],
    $data["amount"],
    $data["bankid"]
]);
echo json_encode(["success" => true]);