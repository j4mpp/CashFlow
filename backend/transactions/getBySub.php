<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;
$subcategoryid = $_GET["subcategoryid"] ?? null;

if (!$userid || !$subcategoryid) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE userid = ? AND subcategoryid = ?");
$stmt->execute([$userid, $subcategoryid]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($transactions);

