<?php
require "../db.php";

$allowed = ["userid"];
$unknown = array_diff(array_keys($_GET), $allowed);
if (!empty($unknown)) {
    echo json_encode([]);
    exit;
}

$useridRaw = $_GET["userid"] ?? null;
if ($useridRaw === null || !is_string($useridRaw) || !preg_match("/^\d+$/", $useridRaw)) {
    echo json_encode([]);
    exit;
}
$userid = (int)$useridRaw;

// IDOR-Fix: nur Daten des eingeloggten Users
$authUser = cashflow_get_authorized_userid($userid);
if ($authUser === null) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM banks WHERE userid = ?");
$stmt->execute([$userid]);

$banks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($banks);
