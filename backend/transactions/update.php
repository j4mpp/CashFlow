<?php
require "../db.php";
$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("
    UPDATE transactions 
    SET subcategoryid=?, name=?, description=?, amount=?, bankid=?, date=?
    WHERE id=? AND userid=?
");

$date = $data["date"] ?? null;
if (!$date) {
    $date = date("Y-m-d");
}

$stmt->execute([
    $data["subcategoryid"],
    $data["name"],
    $data["description"],
    $data["amount"],
    $data["bankid"],
    $date,
    $data["id"],
    $data["userid"]
]);
echo json_encode(["success" => true]);