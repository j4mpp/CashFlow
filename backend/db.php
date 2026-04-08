<?php
declare(strict_types=1);

/**
 * Datenbankzugriff
 *
 * OWASP: Keine Secrets im Repo (keine Hardcodings für Passwörter).
 * Konfig via Environment Variablen.
 *
 * Beispiellisten (nur als Name, Werte müssen gesetzt werden):
 * - CASHFLOW_DB_HOST
 * - CASHFLOW_DB_PORT
 * - CASHFLOW_DB_NAME
 * - CASHFLOW_DB_USER
 * - CASHFLOW_DB_PASS
 *
 * Key-Rotation passiert operativ: Env-Value ändern + Server/Container neu starten.
 */

// CORS nur für Requests; Content-Type wird von den jeweiligen Endpunkten gesetzt.
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
    http_response_code(204);
    exit;
}

require __DIR__ . "/validation.php";

// Optional: lokale `.env` Datei laden (nur wenn vorhanden).
// Vorteil: Dev/Tests laufen ohne manuelles Exportieren der Variablen.
// Hinweis: Secrets sollen nicht im Repo landen. `.env` sollte typischerweise ignoriert sein.
$envCandidates = [
    __DIR__ . "/../.env", // Projekt-root
    __DIR__ . "/.env" // legacy: im backend-Ordner
];

foreach ($envCandidates as $envPath) {
    if (!is_file($envPath)) {
        continue;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        continue;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }
        if (!str_contains($line, "=")) {
            continue;
        }
        [$key, $value] = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value);

        // Entfernt umschließende Quotes: KEY="value" / KEY='value'
        if (
            (str_starts_with($value, "\"") && str_ends_with($value, "\"")) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if ($key !== "") {
            putenv($key . "=" . $value);
            $_ENV[$key] = $value;
        }
    }

    // Sobald eine Datei erfolgreich geladen wurde, keine weitere suchen.
    break;
}

$host = getenv("CASHFLOW_DB_HOST") ?: "127.0.0.1";
$port = getenv("CASHFLOW_DB_PORT") ?: "3306";
$db = getenv("CASHFLOW_DB_NAME") ?: "cashflow";
$user = getenv("CASHFLOW_DB_USER") ?: "";
$pass = getenv("CASHFLOW_DB_PASS") ?: "";

if ($user === "" || $pass === "") {
    error_log("CashFlow DB credentials missing (CASHFLOW_DB_USER/CASHFLOW_DB_PASS).");
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "Server misconfigured"]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db . ";charset=utf8",
        $user,
        $pass
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Keine Detailinformationen an den Client ausgeben (verhindert Info-Leaks).
    error_log("CashFlow DB connection failed.");
    http_response_code(500);
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["error" => "Server error"]);
    exit;
}
