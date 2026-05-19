<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1';
$port = '3306';
$db = 'projetquiz';
$user = 'root';
$pass = '';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En cas d'erreur, on affiche le vrai message technique pour savoir d'où vient le problème
     header('Content-Type: application/json');
     die(json_encode([
         'erreur' => 'Impossible de lier la base de données',
         'details_techniques' => $e->getMessage()
     ]));
}
?>