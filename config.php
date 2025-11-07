<?php
$db_host = 'sql212.infinityfree.com'; // pegue o hostname certo no painel!
$db_name = 'if0_40352073_db_agendeaqui'; // o nome completo do banco
$db_user = 'if0_40352073'; // seu user completo
$db_pass = 'xldkrDW2IYPMMuH'; // senha definida no painel

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die('Erro de conexão com o banco: ' . $e->getMessage());
}
