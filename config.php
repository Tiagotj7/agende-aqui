<?php
// config.php
// Preencha com seus dados do InfinityFree (ou do ambiente que estiver usando)
$db_host = 'sql212.infinityfree.com';      // ex: sql112.byetcluster.com
$db_name = 'if0_40352073_db_agendeaqui';    // ex: epiz_12345678_nomeDoBanco
$db_user = 'if0_40352073';    // ex: epiz_12345678
$db_pass = 'xldkrDW2IYPMMuH';

// URL base do seu site (sem /final). Ex: https://meusite.infinityfreeapp.com
// Opcional, facilita redirects. Ajuste conforme necessário.
//define('BASE_URL', 'https://https://agendeaqui.rf.gd/');

date_default_timezone_set('America/Sao_Paulo'); // ajuste se quiser
session_start();

// Conexão PDO segura
try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Em produção preferível logar o erro em arquivo. Aqui mostramos mensagem para debug.
    die('Erro de conexão com o banco: ' . $e->getMessage());
}

// Função simples para mensagens "flash"
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}

/**
 * Adiciona uma mensagem flash para mostrar ao usuário.
 * $type: 'success' | 'error' | 'info'
 */
function flash($msg, $type = 'info') {
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

/**
 * Recupera e limpa mensagens flash
 */
function get_flash() {
    $f = $_SESSION['flash'] ?? [];
    $_SESSION['flash'] = [];
    return $f;
}
