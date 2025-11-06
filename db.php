<?php
// db.php
// Conexão segura usando variáveis do arquivo .env

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função simples para carregar variáveis do .env
function loadEnv($path) {
    if (!file_exists($path)) {
        exit('Arquivo .env não encontrado.');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[$name] = trim($value);
        putenv("$name=$value");
    }
}

// Carrega as variáveis do arquivo .env
loadEnv(__DIR__ . '/../.env'); // ajuste o caminho se seu db.php estiver em outra pasta

// Pega as variáveis do ambiente
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

// Monta a conexão PDO
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    exit('Erro ao conectar ao banco de dados.');
}
