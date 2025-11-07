<?php
// functions.php
require_once 'config.php';

/**
 * Escapa saída HTML
 */
function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Logging simples: grava em logs/errors.log
 */
function log_error($msg) {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/errors.log';
    $when = date('Y-m-d H:i:s');
    @file_put_contents($file, "[$when] $msg\n", FILE_APPEND | LOCK_EX);
}

/**
 * CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Flash messages já em config.php (funções flash/get_flash)
 * Mas definimos wrappers caso não existam
 */
if (!function_exists('flash')) {
    function flash($msg, $type = 'info') {
        $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
    }
}
if (!function_exists('get_flash')) {
    function get_flash() {
        $f = $_SESSION['flash'] ?? [];
        $_SESSION['flash'] = [];
        return $f;
    }
}

/**
 * CRUD functions
 * Cada função retorna array: ['ok'=>bool, 'msg'=>string]
 */

/** Busca contatos com filtros opcionais */
function getContacts($pdo, $search = '', $status = null) {
    $sql = "SELECT * FROM contacts WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR notes LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($status === '0' || $status === '1') {
        $sql .= " AND status = ?";
        $params[] = (int)$status;
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** Busca 1 contato pelo id */
function getContact($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

/** Cria novo contato - captura exceções e loga */
function createContact($pdo, $name, $email, $phone, $notes, $status = 1) {
    try {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, notes, status) VALUES (?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$name, $email, $phone, $notes, (int)$status]);
        if ($ok) {
            return ['ok' => true, 'msg' => 'Contato criado com sucesso', 'id' => $pdo->lastInsertId()];
        } else {
            // Resultado falso sem exception (raro) — logar
            $errorInfo = $stmt->errorInfo();
            log_error("createContact execute returned false: " . json_encode($errorInfo));
            return ['ok' => false, 'msg' => 'Falha ao inserir contato (ver logs).'];
        }
    } catch (PDOException $e) {
        log_error("createContact PDOException: " . $e->getMessage() . " | trace: " . $e->getTraceAsString());
        return ['ok' => false, 'msg' => 'Erro ao inserir contato: ' . $e->getMessage()];
    } catch (Throwable $t) {
        log_error("createContact Throwable: " . $t->getMessage());
        return ['ok' => false, 'msg' => 'Erro inesperado ao inserir contato.'];
    }
}

/** Atualiza contato */
function updateContact($pdo, $id, $name, $email, $phone, $notes, $status = 1) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET name=?, email=?, phone=?, notes=?, status=? WHERE id=?");
        $ok = $stmt->execute([$name, $email, $phone, $notes, (int)$status, (int)$id]);
        return ['ok' => $ok, 'msg' => $ok ? 'Atualizado' : 'Falha ao atualizar'];
    } catch (PDOException $e) {
        log_error("updateContact PDOException: " . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao atualizar: ' . $e->getMessage()];
    }
}

/** Delete (remove) contato */
function deleteContact($pdo, $id) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET status = 0 WHERE id = ?");
        $ok = $stmt->execute([(int)$id]);
        return ['ok' => $ok, 'msg' => $ok ? 'Contato desativado (status=0)' : 'Falha ao desativar contato'];
    } catch (PDOException $e) {
        return ['ok' => false, 'msg' => 'Erro: ' . $e->getMessage()];
    }
}



/** Alterna status entre 0 e 1 */
function toggleStatus($pdo, $id) {
    try {
        $c = getContact($pdo, $id);
        if (!$c) return ['ok' => false, 'msg' => 'Contato não encontrado'];
        $new = $c['status'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        $ok = $stmt->execute([(int)$new, (int)$id]);
        return ['ok' => $ok, 'msg' => $ok ? 'Status alterado' : 'Falha ao alterar status'];
    } catch (PDOException $e) {
        log_error("toggleStatus PDOException: " . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao alternar status: ' . $e->getMessage()];
    }
}
