<?php
// functions.php - versão corrigida
require_once 'config.php';

/** Escapa saída HTML */
function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Logging simples */
function log_error($msg) {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/errors.log';
    $when = date('Y-m-d H:i:s');
    @file_put_contents($file, "[$when] $msg\n", FILE_APPEND | LOCK_EX);
}

/** CSRF token helpers */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/** Flash wrappers (caso não existam em config.php) */
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
 * getContacts
 * - $search: string de busca (opcional)
 * - $status: null | '1' | '0' | 'all'
 *   - null (padrão) -> retorna só ATIVOS (status = 1)
 *   - '1' -> apenas ativos
 *   - '0' -> apenas inativos
 *   - 'all' -> todos os registros (sem filtro de status)
 */
function getContacts($pdo, $search = '', $status = null) {
    $sql = "SELECT * FROM contacts WHERE 1=1";
    $params = [];

    // filtro de busca
    if ($search !== '') {
        $sql .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ? OR notes LIKE ?)";
        $like = "%{$search}%";
        $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    }

    // filtro de status
    if ($status === null) {
        // padrão: somente ativos
        $sql .= " AND status = 1";
    } elseif ($status === '1' || $status === 1) {
        $sql .= " AND status = 1";
    } elseif ($status === '0' || $status === 0) {
        $sql .= " AND status = 0";
    } elseif ($status === 'all') {
        // sem filtro
    } else {
        // qualquer outro valor tratado como "somente ativos"
        $sql .= " AND status = 1";
    }

    $sql .= " ORDER BY created_at DESC, id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/** getContact por id */
function getContact($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([(int)$id]);
    return $stmt->fetch();
}

/**
 * createContact - insere contato
 * retorna array: ['ok'=>bool, 'msg'=>string, 'id'=>int|null]
 */
function createContact($pdo, $name, $email, $phone, $notes, $status = 1) {
    try {
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, notes, status) VALUES (?, ?, ?, ?, ?)");
        $ok = $stmt->execute([$name, $email, $phone, $notes, (int)$status]);
        if ($ok) {
            return ['ok' => true, 'msg' => 'Contato criado com sucesso', 'id' => $pdo->lastInsertId()];
        } else {
            $err = $stmt->errorInfo();
            log_error("createContact returned false: " . json_encode($err));
            return ['ok' => false, 'msg' => 'Falha ao inserir contato'];
        }
    } catch (PDOException $e) {
        log_error("createContact PDOException: " . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao inserir contato: ' . $e->getMessage()];
    }
}

/**
 * updateContact - atualiza contato
 * retorna array ['ok'=>bool,'msg'=>string]
 */
function updateContact($pdo, $id, $name, $email, $phone, $notes, $status = 1) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET name=?, email=?, phone=?, notes=?, status=? WHERE id=?");
        $ok = $stmt->execute([$name, $email, $phone, $notes, (int)$status, (int)$id]);
        return ['ok' => (bool)$ok, 'msg' => $ok ? 'Atualizado com sucesso' : 'Falha ao atualizar'];
    } catch (PDOException $e) {
        log_error("updateContact PDOException: " . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao atualizar: ' . $e->getMessage()];
    }
}

/**
 * deleteContact - EXCLUSÃO LÓGICA
 * altera status = 0 mas NÃO remove linha do DB.
 * retorna array ['ok'=>bool,'msg'=>string]
 */
function deleteContact($pdo, $id) {
    try {
        $stmt = $pdo->prepare("UPDATE contacts SET status = 0 WHERE id = ?");
        $ok = $stmt->execute([(int)$id]);
        return ['ok' => (bool)$ok, 'msg' => $ok ? 'Contato desativado (status=0)' : 'Falha ao desativar contato'];
    } catch (PDOException $e) {
        log_error("deleteContact PDOException: " . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao desativar: ' . $e->getMessage()];
    }
}

/**
 * toggleStatus - alterna status 0<->1
 */
function toggleStatus($pdo, $id) {
    try {
        $c = getContact($pdo, $id);
        if (!$c) return ['ok' => false, 'msg' => 'Contato não encontrado'];
        $new = $c['status'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
        $ok = $stmt->execute([(int)$new, (int)$id]);
        return ['ok' => (bool)$ok, 'msg' => $ok ? 'Status alterado' : 'Falha ao alterar status'];
    } catch (PDOException $e) {
        log_error("toggleStatus PDOException: " . $e->getMessage());
        return ['ok' => false, 'msg' => 'Erro ao alternar status: ' . $e->getMessage()];
    }
}
