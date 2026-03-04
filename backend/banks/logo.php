<?php
require "../db.php";

$userid = $_GET["userid"] ?? null;
$bankid = $_GET["bankid"] ?? null;

// Basisverzeichnis, in dem PNGs liegen
$baseDir = realpath(__DIR__ . "/../../cashflow_frontend/src/assets/bankpng");

if ($baseDir === false) {
    http_response_code(404);
    exit;
}

$filename = null;

if ($userid && $bankid) {
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
