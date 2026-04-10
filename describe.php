<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
$tables = ['mentor_favorites', 'booking', 'schedule', 'session', 'mentor_evaluations', 'session_notes', 'session_todos', 'users'];
foreach ($tables as $t) {
    echo "--- $t ---\n";
    foreach ($pdo->query("DESCRIBE `$t`") as $row) {
        echo $row['Field'] . "\n";
    }
}
