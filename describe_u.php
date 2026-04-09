<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
    $stmt = $pdo->query('DESCRIBE users');
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
