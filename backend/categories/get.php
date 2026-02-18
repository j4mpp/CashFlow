<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;

$stmt = $pdo->prepare("SELECT * FROM categories WHERE userid = ?");
$stmt->execute([$userid]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
