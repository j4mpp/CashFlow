<?php
require "../db.php";
$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("UPDATE subcategories SET name=? WHERE id=? AND userid=?");
$stmt->execute([$data["name"], $data["id"], $data["userid"]]);

echo json_encode(["success" => true]);
