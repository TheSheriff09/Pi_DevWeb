<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
    $stmt = $pdo->query('DESCRIBE startup');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Startup Table Structure:\n";
    echo "========================\n";
    foreach ($columns as $column) {
        echo "Field: " . $column['Field'] . "\n";
        echo "Type: " . $column['Type'] . "\n";
        echo "Null: " . $column['Null'] . "\n";
        echo "Key: " . $column['Key'] . "\n";
        echo "Default: " . $column['Default'] . "\n";
        echo "Extra: " . $column['Extra'] . "\n";
        echo "------------------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
