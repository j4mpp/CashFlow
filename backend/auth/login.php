<?php
require "../db.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("SELECT * FROM user WHERE user = ?");
$stmt->execute([$data["user"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($data["password"], $user["password"])) {
    echo json_encode([
        "message" => "Login success",
        "userid" => $user["id"]
    ]);
} else {
    echo json_encode(["error" => "Invalid credentials"]);
}
