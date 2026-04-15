<?php
require "../db.php";

// Semantik bleibt: unerwartete Query-Parameter => leere Liste
$allowed = ["userid", "subcategoryid"];
$unknown = array_diff(array_keys($_GET), $allowed);
if (!empty($unknown)) {
    echo json_encode([]);
    exit;
}

$useridRaw = $_GET["userid"] ?? null;
$subcategoryidRaw = $_GET["subcategoryid"] ?? null;

// Semantik beibehalten: wenn Parameter fehlen -> leere Liste (kein Error)
if ($useridRaw === null || $subcategoryidRaw === null) {
    echo json_encode([]);
    exit;
}

$validated = cashflow_validate_schema(
    ["userid" => $useridRaw, "subcategoryid" => $subcategoryidRaw],
    [
        "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
        "subcategoryid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    ]
);
$userid = $validated["userid"];
$subcategoryid = $validated["subcategoryid"];

// IDOR-Fix: nur Daten des eingeloggten Users
$authUser = cashflow_get_authorized_userid($userid);
if ($authUser === null) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE userid = ? AND subcategoryid = ?");
$stmt->execute([$userid, $subcategoryid]);

$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($transactions);

