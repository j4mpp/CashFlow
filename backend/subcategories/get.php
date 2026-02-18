<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;

$stmt = $pdo->prepare("SELECT * FROM subcategories WHERE userid = ?");
$stmt->execute([$userid]);

$subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($subcategories);
