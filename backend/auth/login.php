<?php
require "../db.php";
cashflow_session_start();

// Erwartet JSON: { user: string, password: string }
$data = cashflow_get_request_data();
$validated = cashflow_validate_schema($data, [
    "user" => ["required" => true, "type" => "string", "min" => 1, "max" => 254, "trim" => true],
    "password" => ["required" => true, "type" => "string", "min" => 1, "max" => 128, "trim" => true],
]);

$stmt = $pdo->prepare("SELECT * FROM user WHERE user = ?");
$stmt->execute([$validated["user"]]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($validated["password"], $user["password"])) {
    // Session für Auth/AuthZ setzen (fix gegen IDOR)
    $_SESSION["userid"] = (int)$user["id"];
    $_SESSION["name"] = (string)$user["name"];

    echo json_encode([
    "message" => "Login success",
    "userid" => $user["id"],
    "name" => $user["name"]
]);

} else {
    echo json_encode(["error" => "Invalid credentials"]);
}
