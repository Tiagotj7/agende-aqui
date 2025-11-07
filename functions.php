<?php
// functions.php
require_once 'config.php';

/** Sanitiza saída HTML */
function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Gera e verifica token CSRF simples */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/** Busca contatos com filtros opcionais: search, status */
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

/** Cria novo contato */
function createContact($pdo, $name, $email, $phone, $notes, $status = 1) {
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, notes, status) VALUES (?, ?, ?, ?, ?)");
    return $stmt->execute([$name, $email, $phone, $notes, (int)$status]);
}

/** Atualiza contato */
function updateContact($pdo, $id, $name, $email, $phone, $notes, $status = 1) {
    $stmt = $pdo->prepare("UPDATE contacts SET name=?, email=?, phone=?, notes=?, status=? WHERE id=?");
    return $stmt->execute([$name, $email, $phone, $notes, (int)$status, (int)$id]);
}

/** Delete (remove) contato */
function deleteContact($pdo, $id) {
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    return $stmt->execute([(int)$id]);
}

/** Alterna status entre 0 e 1 */
function toggleStatus($pdo, $id) {
    // Busca status atual
    $c = getContact($pdo, $id);
    if (!$c) return false;
    $new = $c['status'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE contacts SET status = ? WHERE id = ?");
    return $stmt->execute([(int)$new, (int)$id]);
}
