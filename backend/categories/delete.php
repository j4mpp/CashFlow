<?php
require "../db.php";
$data = json_decode(file_get_contents("php://input"), true);

/* Optional: zuerst Subcats löschen, falls keine FK-Cascade existiert */
$pdo->prepare("DELETE FROM subcategories WHERE categoryid=? AND userid=?")
    ->execute([$data["id"], $data["userid"]]);

$stmt = $pdo->prepare("DELETE FROM categories WHERE id=? AND userid=?");
$stmt->execute([$data["id"], $data["userid"]]);

echo json_encode(["success" => true]);
