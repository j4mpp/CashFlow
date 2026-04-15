<?php
require "../db.php";

$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100],
    "iban" => ["required" => true, "type" => "iban", "min_len" => 15, "max_len" => 34],
    "amount" => ["required" => true, "type" => "float", "min" => -1000000000000, "max" => 1000000000000],
    "bankfirma" => ["required" => true, "type" => "string", "trim" => true, "min" => 0, "max" => 100],
]);

$userid = $validated["userid"];
$name = $validated["name"];
$iban = $validated["iban"];
$amount = $validated["amount"];
$bankfirma = $validated["bankfirma"];

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($userid);

$stmt = $pdo->prepare("
    INSERT INTO banks (userid, name, iban, amount, bankfirma)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $userid,
    $name,
    $iban,
    $amount,
    $bankfirma
]);

$bankId = $pdo->lastInsertId();

// Logo speichern (optional)
$logo = cashflow_validate_upload_image_optional("logo", 5242880);
if ($logo !== null) {
    $tmpPath = $logo["tmpPath"];

    $targetDir = realpath(__DIR__ . "../img/bankpng");
    if ($targetDir === false) {
        // Fallback: Verzeichnis versuchen zu erstellen
        $targetDir = __DIR__ . "../img/bankpng";
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
    }

    if ($targetDir && is_dir($targetDir)) {
        $filename = "user" . $userid . "_bank" . $bankId . ".png";
        $targetPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $imageData = @file_get_contents($tmpPath);
        if ($imageData !== false) {
            $src = @imagecreatefromstring($imageData);
            if ($src !== false) {
                $dstSize = 200;
                $dst = imagecreatetruecolor($dstSize, $dstSize);

                // Transparenz für PNG
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
                imagefilledrectangle($dst, 0, 0, $dstSize, $dstSize, $transparent);

                $srcWidth = imagesx($src);
                $srcHeight = imagesy($src);

                imagecopyresampled(
                    $dst,
                    $src,
                    0,
                    0,
                    0,
                    0,
                    $dstSize,
                    $dstSize,
                    $srcWidth,
                    $srcHeight
                );

                imagepng($dst, $targetPath);

                imagedestroy($src);
                imagedestroy($dst);
            }
        }
    }
}

echo json_encode([
    "success" => true,
    "id" => $bankId
]);
