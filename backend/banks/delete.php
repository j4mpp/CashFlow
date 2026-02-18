<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("DELETE FROM banks WHERE id=? AND userid=?");
$stmt->execute([
    $data["id"],
    $data["userid"]
]);

echo json_encode(["message" => "Bank deleted"]);
