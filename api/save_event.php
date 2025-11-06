<?php
// api/save_event.php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Não autenticado']);
    exit;
}

$uid = $_SESSION['user_id'];
$id = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
$title = trim($_POST['title'] ?? '');
$desc = trim($_POST['description'] ?? '');
$start = $_POST['start'] ?? '';
$end = $_POST['end'] ?? '';

// validações básicas
if (!$title || !$start || !$end) {
    echo json_encode(['success'=>false,'message'=>'Campos obrigatórios faltando.']);
    exit;
}

// converter para formato MySQL se necessário (datetime-local já no formato YYYY-MM-DDTHH:MM)
$start = str_replace('T', ' ', $start) . ':00';
$end = str_replace('T', ' ', $end) . ':00';

try {
    if ($id) {
        // atualiza — garante que pertence ao usuário
        $stmt = $pdo->prepare("UPDATE events SET title = ?, description = ?, start_datetime = ?, end_datetime = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $desc, $start, $end, $id, $uid]);
        echo json_encode(['success'=>true, 'action'=>'updated']);
    } else {
        // inserir
        $stmt = $pdo->prepare("INSERT INTO events (user_id, title, description, start_datetime, end_datetime) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $title, $desc, $start, $end]);
        echo json_encode(['success'=>true, 'action'=>'created', 'id' => $pdo->lastInsertId()]);
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
