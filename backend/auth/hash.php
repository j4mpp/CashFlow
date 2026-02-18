<?php
header("Content-Type: text/plain");

$password = "admin";

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Original Passwort: " . $password . "\n\n";
echo "Hash:\n" . $hash;
