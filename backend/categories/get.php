<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE userid = ?");
$stmt->execute([$userid]);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categories);
