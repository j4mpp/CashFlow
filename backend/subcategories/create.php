<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("INSERT INTO subcategories (userid, categoryid, name) VALUES (?, ?, ?)");
$stmt->execute([
    $data["userid"],
    $data["categoryid"],
    $data["name"]
]);

echo json_encode(["message" => "Subcategory created"]);
