<?php
// Conexão PDO sem .env — usa um arquivo PHP de configuração retornando um array.
// Coloque config.php fora do diretório público (ex: c:\Users\Pc\Desktop\teste\config.php).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// caminhos seguros a procurar (priorize fora do webroot)
$configPaths = [
    __DIR__ . '/../config.php',        // exemplo: c:\Users\Pc\Desktop\teste\config.php
    __DIR__ . '/config.php',           // projeto interno (menos seguro)
    __DIR__ . '/../config/config.php', // outra possível estrutura
];

$config = null;
foreach ($configPaths as $p) {
    if (file_exists($p) && is_readable($p)) {
        $cfg = require $p;
        if (is_array($cfg)) {
            $config = $cfg;
            break;
        }
    }
}

if (!is_array($config)) {
    throw new RuntimeException(
        'Arquivo de configuração (config.php) não encontrado ou inválido. Procados em: ' .
        implode(', ', $configPaths)
    );
}

// espera estrutura: ['db' => ['host'=>..., 'name'=>..., 'user'=>..., 'pass'=>..., 'charset'=>...]]
$dbcfg = $config['db'] ?? null;
if (!is_array($dbcfg)) {
    throw new RuntimeException('Configuração "db" ausente em config.php');
}

$db_host = $dbcfg['host'] ?? 'sql212.infinityfree.com';
$db_name = $dbcfg['name'] ?? 'if0_40352073_db_agendeaqui';
$db_user = $dbcfg['user'] ?? 'epiz_40352073';
$db_pass = $dbcfg['pass'] ?? 'xldkrDW2IYPMMuH';
$charset = $dbcfg['charset'] ?? 'utf8mb4';

if ($db_name === '') {
    throw new RuntimeException('Nome do banco (db.name) não definido em config.php');
}

$dsn = "mysql:host={$db_host};dbname={$db_name};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Em ambiente de desenvolvimento você pode usar $e->getMessage(), em produção registre o erro.
    throw new RuntimeException('Falha na conexão com o banco. Verifique config.php e as credenciais.');
}
