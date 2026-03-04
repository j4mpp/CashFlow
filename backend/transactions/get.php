<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE userid = ?");
$stmt->execute([$userid]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($transactions);
