<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
$stmt = $pdo->query('SELECT id, email, password_hash, role FROM users LIMIT 3');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
