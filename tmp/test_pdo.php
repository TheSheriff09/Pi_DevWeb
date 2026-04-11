<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $pdo->query("SELECT sessionID, sessionType, status FROM session ORDER BY sessionID DESC LIMIT 3");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['sessionID'] . "\n";
        echo "Type: '" . $row['sessionType'] . "'\n";
        echo "Status: '" . $row['status'] . "'\n";
        echo "----\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
