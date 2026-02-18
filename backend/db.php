<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Content-Type: application/json");

$host = "127.0.0.1";
$port = "3306";
$db   = "cashflow";
$user = "root";
$pass = "rootpass";

/* 
$host = "10.0.108.46";
$port = "3306";
$db   = "cashflow";
$user = "root";
$pass = "TGM2026!aa";
*/

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
    exit;
}
