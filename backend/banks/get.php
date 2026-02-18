<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;

$stmt = $pdo->prepare("SELECT * FROM banks WHERE userid = ?");
$stmt->execute([$userid]);

$banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($banks);
