<?php
declare(strict_types=1);

/**
 * Zentrale Validierung/Sanitization für alle API-Endpunkte.
 *
 * OWASP-Ansatz (pragmatisch):
 * - Schema-basierte Whitelist statt "blacklist"
 * - Type-Checks + Length-Limits
 * - Unbekannte Felder werden verworfen (reject unexpected fields)
 * - Input wird für sichere Persistenz normalisiert (z.B. Whitespace/Iban)
 */

function cashflow_send_json(array $data, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function cashflow_validation_error(string $message, array $details = [], int $status = 400): void
{
    cashflow_send_json([
        "error" => $message,
        "details" => $details
    ], $status);
}

function cashflow_get_request_data(): array
{
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

    // JSON Body
    if (stripos($contentType, "application/json") !== false) {
        $raw = file_get_contents("php://input");
        if (!is_string($raw) || $raw === "") {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    // multipart/form-data (z.B. Logo Upload) oder application/x-www-form-urlencoded
    return is_array($_POST) ? $_POST : [];
}

function cashflow_validate_int($value, string $field, array $rule): int
{
    $min = $rule["min"] ?? null;
    $max = $rule["max"] ?? null;

    // Akzeptiere auch numerische Strings (kommt i.d.R. aus JSON/stringified oder FormData)
    if (is_int($value)) {
        $intVal = $value;
    } elseif (is_string($value) && preg_match("/^-?\d+$/", $value)) {
        $intVal = (int)$value;
    } else {
        cashflow_validation_error("Invalid type", [
            "field" => $field,
            "expected" => "int"
        ]);
    }

    if ($min !== null && $intVal < $min) {
        cashflow_validation_error("Value out of range", [
            "field" => $field,
            "min" => $min,
            "actual" => $intVal
        ]);
    }
    if ($max !== null && $intVal > $max) {
        cashflow_validation_error("Value out of range", [
            "field" => $field,
            "max" => $max,
            "actual" => $intVal
        ]);
    }

    return $intVal;
}

function cashflow_validate_float($value, string $field, array $rule): float
{
    $min = $rule["min"] ?? null;
    $max = $rule["max"] ?? null;

    if (is_float($value) || is_int($value)) {
        $f = (float)$value;
    } elseif (is_string($value) && is_numeric($value)) {
        $f = (float)$value;
    } else {
        cashflow_validation_error("Invalid type", [
            "field" => $field,
            "expected" => "float"
        ]);
    }

    if (!is_finite($f)) {
        cashflow_validation_error("Invalid number", ["field" => $field]);
    }

    if ($min !== null && $f < $min) {
        cashflow_validation_error("Value out of range", [
            "field" => $field,
            "min" => $min,
            "actual" => $f
        ]);
    }
    if ($max !== null && $f > $max) {
        cashflow_validation_error("Value out of range", [
            "field" => $field,
            "max" => $max,
            "actual" => $f
        ]);
    }

    return $f;
}

function cashflow_validate_string($value, string $field, array $rule): string
{
    if (!is_string($value)) {
        cashflow_validation_error("Invalid type", [
            "field" => $field,
            "expected" => "string"
        ]);
    }

    $max = $rule["max"] ?? null;
    $min = $rule["min"] ?? null;
    $trim = $rule["trim"] ?? true;
    $pattern = $rule["pattern"] ?? null;

    if ($trim) {
        $value = trim($value);
    }

    // Entfernt Nullbytes (kann Validator/Storage verwirren)
    $value = str_replace("\0", "", $value);

    $len = mb_strlen($value, "UTF-8");
    if ($min !== null && $len < $min) {
        cashflow_validation_error("Value too short", [
            "field" => $field,
            "min" => $min,
            "actual" => $len
        ]);
    }
    if ($max !== null && $len > $max) {
        cashflow_validation_error("Value too long", [
            "field" => $field,
            "max" => $max,
            "actual" => $len
        ]);
    }

    if ($pattern !== null && !preg_match($pattern, $value)) {
        cashflow_validation_error("Invalid format", [
            "field" => $field
        ]);
    }

    return $value;
}

function cashflow_validate_iban($value, string $field, array $rule): string
{
    // Für die App reichen "robuste" Checks (keine vollständige IBAN-Validierung inkl. Mod-97)
    if (!is_string($value)) {
        cashflow_validation_error("Invalid type", [
            "field" => $field,
            "expected" => "string"
        ]);
    }

    $iban = strtoupper(trim($value));
    // Entfernt Whitespace (z.B. "AT12 3456 ...")
    $iban = preg_replace("/\s+/", "", $iban) ?? "";

    $len = mb_strlen($iban, "UTF-8");
    $minLen = $rule["min_len"] ?? 15;
    $maxLen = $rule["max_len"] ?? 34;
    if ($len < $minLen || $len > $maxLen) {
        cashflow_validation_error("Invalid IBAN length", [
            "field" => $field,
            "actual" => $len
        ]);
    }

    if (!preg_match("/^[A-Z0-9]+$/", $iban)) {
        cashflow_validation_error("Invalid IBAN characters", [
            "field" => $field
        ]);
    }

    return $iban;
}

function cashflow_validate_date_iso($value, string $field): string
{
    if (!is_string($value)) {
        cashflow_validation_error("Invalid type", [
            "field" => $field,
            "expected" => "string"
        ]);
    }

    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $value)) {
        cashflow_validation_error("Invalid date format", [
            "field" => $field
        ]);
    }

    $dt = DateTime::createFromFormat("Y-m-d", $value);
    if (!$dt || $dt->format("Y-m-d") !== $value) {
        cashflow_validation_error("Invalid date value", [
            "field" => $field
        ]);
    }

    return $value;
}

/**
 * @param array $data Rohdaten (JSON decodiert oder $_POST)
 * @param array $schema Whitelist-Definition: field => rule[]
 *
 * Rule Beispiele:
 * - ["required"=>true, "type"=>"int", "min"=>1, "max"=>2147483647]
 * - ["required"=>false, "type"=>"string", "max"=>100]
 * - ["required"=>false, "type"=>"date_iso"]
 * - ["required"=>true, "type"=>"iban", "min_len"=>15, "max_len"=>34]
 */
function cashflow_validate_schema(array $data, array $schema): array
{
    $rejectUnknown = $schema["_reject_unknown"] ?? true;
    unset($schema["_reject_unknown"]);

    if ($rejectUnknown) {
        $allowed = array_keys($schema);
        $unknown = array_diff(array_keys($data), $allowed);
        if (!empty($unknown)) {
            cashflow_validation_error("Unexpected fields provided", [
                "unexpected" => array_values($unknown)
            ]);
        }
    }

    $validated = [];

    foreach ($schema as $field => $rule) {
        $required = $rule["required"] ?? false;

        if (!array_key_exists($field, $data)) {
            if ($required) {
                cashflow_validation_error("Missing required field", ["field" => $field]);
            }
            continue;
        }

        $value = $data[$field];
        if ($value === null) {
            cashflow_validation_error("Invalid null value", ["field" => $field]);
        }

        $type = $rule["type"] ?? "string";

        switch ($type) {
            case "int":
                $validated[$field] = cashflow_validate_int($value, $field, $rule);
                break;
            case "float":
                $validated[$field] = cashflow_validate_float($value, $field, $rule);
                break;
            case "iban":
                $validated[$field] = cashflow_validate_iban($value, $field, $rule);
                break;
            case "date_iso":
                $validated[$field] = cashflow_validate_date_iso($value, $field);
                break;
            case "string":
            default:
                $validated[$field] = cashflow_validate_string($value, $field, $rule);
                break;
        }
    }

    return $validated;
}

function cashflow_validate_upload_image_optional(string $fieldName, int $maxBytes = 2097152): ?array
{
    if (!isset($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if (($file["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file["error"] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        cashflow_validation_error("Upload failed", ["field" => $fieldName]);
    }

    $tmpPath = $file["tmp_name"] ?? "";
    if (!is_string($tmpPath) || $tmpPath === "" || !is_uploaded_file($tmpPath)) {
        cashflow_validation_error("Invalid upload file", ["field" => $fieldName]);
    }

    $size = (int)($file["size"] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        cashflow_validation_error("Invalid upload size", [
            "field" => $fieldName,
            "maxBytes" => $maxBytes
        ]);
    }

    $imgInfo = @getimagesize($tmpPath);
    if ($imgInfo === false) {
        cashflow_validation_error("Invalid image upload", ["field" => $fieldName]);
    }

    return [
        "tmpPath" => $tmpPath,
        "size" => $size,
        "mime" => $imgInfo["mime"] ?? null
    ];
}

/**
 * Session-basiertes AuthN/AuthZ (fix gegen IDOR):
 * - Wir verwenden Server-seitig Session statt Client-input (`userid` aus URL/Body).
 */
function cashflow_session_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * @return int|null Authenticated userid
 */
function cashflow_get_authenticated_userid(): ?int
{
    cashflow_session_start();

    if (!isset($_SESSION["userid"])) {
        return null;
    }

    $u = $_SESSION["userid"];
    if (is_int($u)) return $u;
    if (is_string($u) && preg_match("/^\d+$/", $u)) return (int)$u;

    return null;
}

/**
 * Verwirft eine Anfrage, wenn Session-Userid nicht zur angefragten/bearbeiteten Userid passt.
 */
function cashflow_require_userid_match(int $useridFromInput): void
{
    $authUser = cashflow_get_authenticated_userid();
    if ($authUser === null || $authUser !== $useridFromInput) {
        // Nachricht bewusst generisch (kein Leak, ob User existiert).
        cashflow_validation_error("Unauthorized", [], 403);
    }
}

/**
 * Für GET-Endpunkte: liefert userid nur, wenn Session-Userid exakt matcht.
 * Andernfalls: null (Caller kann "[]" zurückgeben).
 */
function cashflow_get_authorized_userid(int $useridFromInput): ?int
{
    $authUser = cashflow_get_authenticated_userid();
    if ($authUser === null || $authUser !== $useridFromInput) {
        return null;
    }
    return $useridFromInput;
}

