<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=startupflow', 'root', '');
    $pdo->query("UPDATE session SET sessionType='online', status='planned' WHERE sessionType='' OR status=''");
    $pdo->query("UPDATE booking SET status='approved' WHERE status='' OR status='ACCEPTED'");
    $pdo->query("UPDATE booking SET status='rejected' WHERE status='REJECTED'");
    $pdo->query("UPDATE booking SET status='pending' WHERE status='PENDING'");
    echo 'Done';
} catch(Exception $e) { echo $e->getMessage(); }
