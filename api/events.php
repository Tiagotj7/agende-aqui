<?php
// api/events.php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}
$uid = $_SESSION['user_id'];

// se pedir single
if (isset($_GET['single'])) {
    $id = (int)$_GET['single'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $uid]);
    $ev = $stmt->fetch();
    if ($ev) {
        echo json_encode(['success' => true, 'event' => $ev]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Evento não encontrado.']);
    }
    exit;
}

// buscar todos os eventos do usuário e converter para formato FullCalendar
$stmt = $pdo->prepare("SELECT id, title, start_datetime, end_datetime FROM events WHERE user_id = ?");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

$events = array_map(function($r){
    return [
        'id' => $r['id'],
        'title' => $r['title'],
        'start' => $r['start_datetime'],
        'end' => $r['end_datetime']
    ];
}, $rows);

echo json_encode($events);
