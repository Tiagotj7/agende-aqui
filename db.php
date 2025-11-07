<?php
// db.php
// Conexão segura usando variáveis do arquivo .env

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function loadEnvFile(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $result = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"' \t\n\r\0\x0B");
        if ($name === '') continue;
        $result[$name] = $value;
        $_ENV[$name] = $value;
        putenv("$name=$value");
    }
    return $result;
}

// tenta localizações seguras (prefira fora do webroot)
$envPaths = [
    __DIR__ . '/../.env',   // pai (ex: teste/.env)
    __DIR__ . '/.env',      // mesmo diretório
];

$env = [];
foreach ($envPaths as $p) {
    $env = loadEnvFile($p);
    if (!empty($env)) break;
}

if (empty($env)) {
    throw new RuntimeException('.env não encontrado em: ' . implode(', ', $envPaths));
}

$db_host = $env['DB_HOST'] ?? '127.0.0.1';
$db_name = $env['DB_NAME'] ?? '';
$db_user = $env['DB_USER'] ?? 'root';
$db_pass = $env['DB_PASS'] ?? '';
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

if ($db_name === '') {
    throw new RuntimeException('DB_NAME não definido no .env');
}

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // em dev: lance a exceção; em prod: registre e lance genérica
    throw new RuntimeException('Falha na conexão com o banco. Verifique credenciais e host.');
}
