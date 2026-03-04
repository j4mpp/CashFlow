<?php
require "../db.php";

// Unterstützt sowohl JSON (alte Aufrufe) als auch multipart/form-data (mit optionalem Dateiupload)
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";

if (stripos($contentType, "application/json") !== false) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
} else {
    $data = $_POST;
}

$id = $data["id"] ?? null;
$userid = $data["userid"] ?? null;
$name = $data["name"] ?? null;
$iban = $data["iban"] ?? null;
$amount = $data["amount"] ?? 0;
$bankfirma = $data["bankfirma"] ?? "";

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
if (
    $userid &&
    $id &&
    isset($_FILES["logo"]) &&
    is_array($_FILES["logo"]) &&
    ($_FILES["logo"]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
) {
    $tmpPath = $_FILES["logo"]["tmp_name"];

    if (is_uploaded_file($tmpPath)) {
        $baseDir = realpath(__DIR__ . "/../../cashflow_frontend/src/assets/bankpng");
        if ($baseDir === false) {
            $baseDir = __DIR__ . "/../../cashflow_frontend/src/assets/bankpng";
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
}

echo json_encode(["success" => true]);
