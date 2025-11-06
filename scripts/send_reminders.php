<?php
// scripts/send_reminders.php
// Executar via CLI: php send_reminders.php
require_once __DIR__ . '/../db.php';

// tempo de antecedência (em minutos) para enviar lembrete
$aheadMinutes = 30;
$now = new DateTimeImmutable();
$targetStart = $now->add(new DateInterval('PT' . $aheadMinutes . 'M'))->format('Y-m-d H:i:00');

// buscamos eventos que começam dentro do intervalo [targetStart, targetStart + 59s]
// ajuste a query conforme sua lógica de lembrete
$stmt = $pdo->prepare("
  SELECT e.*, u.email, u.name
  FROM events e
  JOIN users u ON u.id = e.user_id
  WHERE e.start_datetime = ?
");
$stmt->execute([$targetStart]);
$events = $stmt->fetchAll();

foreach ($events as $ev) {
    $to = $ev['email'];
    $subject = "Lembrete: {$ev['title']} às {$ev['start_datetime']}";
    $message = "Olá {$ev['name']},\n\nEste é um lembrete do seu evento:\n\nTitulo: {$ev['title']}\nInício: {$ev['start_datetime']}\nDescrição: {$ev['description']}\n\n— Agenda Web";
    $headers = "From: no-reply@seu-dominio.com\r\nReply-To: no-reply@seu-dominio.com";

    // tente enviar
    @mail($to, $subject, $message, $headers);
}
