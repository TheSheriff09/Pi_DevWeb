<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
$stmt = $pdo->query("DESCRIBE businessplan");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
