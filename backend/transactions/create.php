<?php
require "../db.php";

$data = cashflow_get_request_data();

$isTransfer = array_key_exists("from_bankid", $data) || array_key_exists("to_bankid", $data);

// Modus A: Klassischer Eintrag (Kategorie/Aktivitäten)
if (!$isTransfer) {
    $validated = cashflow_validate_schema($data, [
        "userid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
        "subcategoryid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
        "name" => ["required" => true, "type" => "string", "trim" => true, "min" => 1, "max" => 100],
        "description" => ["required" => false, "type" => "string", "trim" => true, "min" => 0, "max" => 1000],
        "amount" => ["required" => true, "type" => "float", "min" => -1000000000000, "max" => 1000000000000],
        "bankid" => ["required" => true, "type" => "int", "min" => 1, "max" => 2147483647],
        "date" => ["required" => false, "type" => "date_iso"],
    ]);

    cashflow_require_userid_match($validated["userid"]);

    $stmt = $pdo->prepare("
        INSERT INTO transactions(userid, subcategoryid, name, description, amount, bankid, date)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $validated["userid"],
        $validated["subcategoryid"],
        $validated["name"],
        $validated["description"] ?? "",
        $validated["amount"],
        $validated["bankid"],
        $validated["date"] ?? date("Y-m-d"),
    ]);

    echo json_encode([
        "success" => true,
        "id" => $pdo->lastInsertId()
    ]);
    exit;
}

// Modus B: Konto-Transfer
$validated = cashflow_validate_schema($data, [
    "userid" => ["required" => true, "type" => "int"],
    "from_bankid" => ["required" => true, "type" => "int"],
    "to_bankid" => ["required" => true, "type" => "int"],
    "amount" => ["required" => true, "type" => "float"],
    "description" => ["required" => false, "type" => "string"],
]);

cashflow_require_userid_match($validated["userid"]);

$from = $validated["from_bankid"];
$to = $validated["to_bankid"];
$amount = $validated["amount"];
$userid = $validated["userid"];
$desc = $validated["description"] ?? "";

if ($amount <= 0) {
    echo json_encode(["error" => "Ungültiger Betrag"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // Sender
    $stmt = $pdo->prepare("SELECT amount FROM banks WHERE id=? AND userid=?");
    $stmt->execute([$from, $userid]);
    $sender = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sender) {
        throw new Exception("Sender nicht gefunden");
    }
    if ($sender["amount"] < $amount) {
        throw new Exception("Nicht genug Guthaben");
    }

    // Empfänger
    $stmt = $pdo->prepare("SELECT id FROM banks WHERE id=?");
    $stmt->execute([$to]);
    if (!$stmt->fetch()) {
        throw new Exception("Empfänger nicht gefunden");
    }

    $pdo->prepare("UPDATE banks SET amount = amount - ? WHERE id=?")
        ->execute([$amount, $from]);
    $pdo->prepare("UPDATE banks SET amount = amount + ? WHERE id=?")
        ->execute([$amount, $to]);

    $pdo->prepare("
        INSERT INTO transactions(userid, name, description, amount, bankid, date)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([
        $userid,
        "Transfer",
        $desc,
        -$amount,
        $from,
        date("Y-m-d")
    ]);

    $pdo->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}