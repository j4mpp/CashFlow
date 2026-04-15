<?php
require "../db.php";

$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "id" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
    "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100],
    "iban" => ["required" => true, "type" => "iban", "min_len" => 15, "max_len" => 34],
    "amount" => ["required" => true, "type" => "float", "min" => -1000000000000, "max" => 1000000000000],
    "bankfirma" => ["required" => true, "type" => "string", "trim" => true, "min" => 0, "max" => 100],
]);

$id = $validated["id"];
$userid = $validated["userid"];
$name = $validated["name"];
$iban = $validated["iban"];
$amount = $validated["amount"];
$bankfirma = $validated["bankfirma"];

// IDOR-Fix: nur eigenen User bearbeiten
cashflow_require_userid_match($userid);

$stmt = $pdo->prepare("
    UPDATE banks 
    SET name=?, iban=?, amount=?, bankfirma=? 
    WHERE id=? AND userid=?
");

$stmt->execute([
    $name,
    $iban,
    $amount,
    $bankfirma,
    $id,
    $userid
]);

// Optional neues Logo speichern
$logo = cashflow_validate_upload_image_optional("logo", 5242880);
if ($logo !== null) {
    $tmpPath = $logo["tmpPath"];

    $baseDir = realpath(__DIR__ . "/../img/bankpng");
    if ($baseDir === false) {
        $baseDir = __DIR__ . "/../img/bankpng";
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0777, true);
        }
    }

    if ($baseDir && is_dir($baseDir)) {
        $filename = "user" . $userid . "_bank" . $id . ".png";
        $targetPath = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $imageData = @file_get_contents($tmpPath);
        if ($imageData !== false) {
            $src = @imagecreatefromstring($imageData);
            if ($src !== false) {
                $dstSize = 200;
                $dst = imagecreatetruecolor($dstSize, $dstSize);

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

echo json_encode(["success" => true]);
