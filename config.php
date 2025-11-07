<?php
// config.php
// Preencha com os dados do painel do InfinityFree
// Ex.: $db_host = 'sql123.byetcluster.com'; $db_name = 'epiz_12345678_dbname';
$db_host = 'sql212.infinityfree.com';
$db_name = 'if0_40352073_db_agendeaqui';
$db_user = 'epiz_40352073';
$db_pass = 'xldkrDW2IYPMMuH';

try {
    // DSN usando charset utf8mb4
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    // PDO com exceptions e modo emulacao desligado
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    // Em produção você pode registrar o erro; aqui mostramos a mensagem para facilitar o debug.
    die('Erro de conexão com o banco: ' . $e->getMessage());
}

// Criação da tabela "contacts" se não existir