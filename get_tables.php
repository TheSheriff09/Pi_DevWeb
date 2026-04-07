<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    file_put_contents('tables.txt', implode("\n", $tables));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
