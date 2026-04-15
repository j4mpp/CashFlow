<?php
require "../db.php";

$allowed = ["userid", "bankid"];
$unknown = array_diff(array_keys($_GET), $allowed);
if (!empty($unknown)) {
    http_response_code(404);
    exit;
}

$useridRaw = $_GET["userid"] ?? null;
$bankidRaw = $_GET["bankid"] ?? null;

// Basisverzeichnis, in dem PNGs liegen
$baseDir = realpath(__DIR__ . "/../../img/bankpng");

if ($baseDir === false) {
    http_response_code(404);
    exit;
}

$filename = null;

// Wenn user/bankid übergeben wurden: strikt als positive Integer validieren
if ($useridRaw !== null && $bankidRaw !== null) {
    if (!is_string($useridRaw) || !preg_match("/^\d+$/", $useridRaw)) {
        http_response_code(404);
        exit;
    }
    if (!is_string($bankidRaw) || !preg_match("/^\d+$/", $bankidRaw)) {
        http_response_code(404);
        exit;
    }

    $userid = (int)$useridRaw;
    $bankid = (int)$bankidRaw;

    // IDOR-Fix: nur Logos des eingeloggten Users
    $authUser = cashflow_get_authorized_userid($userid);
    if ($authUser === null) {
        http_response_code(404);
        exit;
    }

    $candidate = $baseDir . DIRECTORY_SEPARATOR . "user" . $userid . "_bank" . $bankid . ".png";
    if (is_file($candidate)) {
        $filename = $candidate;
    }
}

// Fallback: Standardbild bank.png
if ($filename === null) {
    $fallback = $baseDir . DIRECTORY_SEPARATOR . "bank.png";
    if (is_file($fallback)) {
        $filename = $fallback;
    }
}

if ($filename === null) {
    http_response_code(404);
    exit;
}

header("Content-Type: image/png");
readfile($filename);
