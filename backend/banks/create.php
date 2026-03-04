<?php
require "../db.php";

// Unterstützt sowohl JSON (alte Aufrufe) als auch multipart/form-data (mit Dateiupload)
$contentType = $_SERVER["CONTENT_TYPE"] ?? "";

if (stripos($contentType, "application/json") !== false) {
    $data = json_decode(file_get_contents("php://input"), true) ?? [];
} else {
    $data = $_POST;
}

$userid = $data["userid"] ?? null;
$name = $data["name"] ?? null;
$iban = $data["iban"] ?? null;
$amount = $data["amount"] ?? 0;
$bankfirma = $data["bankfirma"] ?? "";

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
if (
    $userid &&
    $bankId &&
    isset($_FILES["logo"]) &&
    is_array($_FILES["logo"]) &&
    ($_FILES["logo"]["error"] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
) {
    $tmpPath = $_FILES["logo"]["tmp_name"];

    if (is_uploaded_file($tmpPath)) {
        $targetDir = realpath(__DIR__ . "/../../cashflow_frontend/src/assets/bankpng");
        if ($targetDir === false) {
            // Fallback: Verzeichnis versuchen zu erstellen
            $targetDir = __DIR__ . "/../../cashflow_frontend/src/assets/bankpng";
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
}

echo json_encode([
    "success" => true,
    "id" => $bankId
]);
