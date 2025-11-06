<?php
// db.php
// Configurações do banco — ajuste conforme seu ambiente
$db_host = '127.0.0.1';
$db_name = 'agenda_db';
$db_user = 'root';
$db_pass = '';
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Em produção você deve logar e não exibir detalhes
    exit('DB error: ' . $e->getMessage());
}
session_start();
