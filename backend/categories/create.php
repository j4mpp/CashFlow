<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("INSERT INTO categories (userid, name) VALUES (?, ?)");
$stmt->execute([
    $data["userid"],
    $data["name"]
]);

echo json_encode(["message" => "Category created"]);
