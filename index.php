<?php
// procura db.php em locais comuns e inclui o primeiro encontrado
$possible = [
    __DIR__ . '/../db.php',         // caminho atual usado
    __DIR__ . '/db.php',            // mesmo diretório
    __DIR__ . '/../config/db.php',  // possível pasta config
    __DIR__ . '/config/db.php',
];

$found = false;
foreach ($possible as $p) {
    if (file_exists($p)) {
        require_once $p;
        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(500);
    echo 'Erro: arquivo db.php não encontrado. Coloque db.php em um destes caminhos ou atualize o require em index.php:<br>';
    foreach ($possible as $p) echo htmlspecialchars($p) . '<br>';
    exit;
}

// garante session se necessário
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userName = $_SESSION['user_name'];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Minha Agenda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand bg-white shadow-sm mb-3">
  <div class="container">
    <a class="navbar-brand" href="#">Agenda</a>
    <div class="ms-auto">
      <span class="me-3">Olá, <?=htmlspecialchars($userName)?></span>
      <a class="btn btn-outline-secondary btn-sm" href="logout.php">Sair</a>
    </div>
  </div>
</nav>

<div class="container">
  <div id="calendar"></div>
</div>

<!-- Modal para criar/editar evento -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="eventForm" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="eventId" name="id">
        <div class="mb-2">
          <label class="form-label">Título</label>
          <input id="title" name="title" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Descrição</label>
          <textarea id="description" name="description" class="form-control"></textarea>
        </div>
        <div class="mb-2">
          <label class="form-label">Início</label>
          <input id="start" name="start" type="datetime-local" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Fim</label>
          <input id="end" name="end" type="datetime-local" class="form-control" required>
        </div>
      </div>
      <div class="modal-footer">
        <button id="deleteBtn" type="button" class="btn btn-danger me-auto">Excluir</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html><?php
$errors = [];
$success = '';
// preserva valores do formulário
$old = function($k){ return htmlspecialchars($_POST[$k] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') $errors[] = 'Nome é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E‑mail inválido.';
    if ($service === '') $errors[] = 'Selecione um serviço.';
    if ($date === '') $errors[] = 'Data é obrigatória.';
    if ($time === '') $errors[] = 'Horário é obrigatório.';

    if (empty($errors)) {
        $appointment = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'service' => $service,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
            'notes' => $notes,
            'created_at' => date('c')
        ];

        $file = __DIR__ . '/appointments.json';
        $list = [];
        if (file_exists($file)) {
            $json = file_get_contents($file);
            $list = json_decode($json, true) ?: [];
        }
        $list[] = $appointment;
        file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $success = 'Agendamento confirmado. Você receberá confirmação por e‑mail.';
        // limpa POST para não repopular os campos
        $_POST = [];
    }
}
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Agende Aqui</title>
  <meta name="description" content="Sistema simples para agendamento" />
  <style>
    :root{--bg:#f6f8fa;--card:#ffffff;--primary:#0b76ef;--muted:#6b7280}
    *{box-sizing:border-box;font-family:Inter,system-ui,Segoe UI,Arial,sans-serif}
    body{margin:0;background:var(--bg);color:#111;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:24px}
    .container{width:100%;max-width:900px;background:var(--card);border-radius:12px;box-shadow:0 6px 30px rgba(15,23,42,0.08);overflow:hidden;display:grid;grid-template-columns:1fr 420px}
    .hero{padding:32px 40px}
    .hero h1{margin:0 0 8px;font-size:1.5rem}
    .hero p{margin:0 0 20px;color:var(--muted)}
    .features{display:grid;gap:12px;grid-template-columns:repeat(2,1fr);margin-top:12px}
    .feature{background:#f8fafc;padding:10px;border-radius:8px;font-size:0.9rem;color:var(--muted)}
    form{padding:28px;background:linear-gradient(180deg,rgba(11,118,239,0.03),transparent);display:flex;flex-direction:column;gap:12px}
    label{font-size:0.85rem;color:var(--muted)}
    input,select,textarea{width:100%;padding:10px;border:1px solid #e6e9ee;border-radius:8px;font-size:0.95rem}
    .row{display:flex;gap:12px}
    .row > *{flex:1}
    button{background:var(--primary);color:#fff;border:0;padding:12px;border-radius:10px;font-weight:600;cursor:pointer}
    footer{padding:12px 16px;text-align:center;font-size:0.85rem;color:var(--muted)}
    .msg{padding:10px;border-radius:8px}
    .error{background:#fff1f0;color:#8b1e1e;border:1px solid #f5c2c7}
    .ok{background:#f0fff4;color:#064e3b;border:1px solid #bbf7d0}
    @media (max-width:880px){.container{grid-template-columns:1fr;}.hero{order:2}.form-wrap{order:1}}
  </style>
</head>
<body>
  <main class="container" role="main">
    <section class="hero" aria-labelledby="title">
      <h1 id="title">Agende Aqui</h1>
      <p>Marque seu horário de forma rápida e prática. Preencha os dados abaixo e confirme.</p>

      <div class="features" aria-hidden="true">
        <div class="feature">Confirmação por e-mail</div>
        <div class="feature">Horários em tempo real</div>
        <div class="feature">Cancelamento fácil</div>
        <div class="feature">Suporte 24/7</div>
      </div>
    </section>

    <section class="form-wrap" aria-labelledby="form-title">
      <form id="schedule-form" method="post" autocomplete="on" novalidate>
        <h2 id="form-title" style="margin:0 0 8px;font-size:1.05rem">Agendamento</h2>

        <?php if ($errors): ?>
          <div class="msg error" role="alert">
            <ul style="margin:0;padding-left:18px">
              <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="msg ok" role="status"><?= htmlspecialchars($success, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php endif; ?>

        <label for="name">Nome completo</label>
        <input id="name" name="name" type="text" required placeholder="Seu nome" value="<?= $old('name') ?>" />

        <label for="email">E‑mail</label>
        <input id="email" name="email" type="email" required placeholder="seu@exemplo.com" value="<?= $old('email') ?>" />

        <label for="phone">Telefone</label>
        <input id="phone" name="phone" type="tel" inputmode="tel" placeholder="(11) 9 9999-9999" value="<?= $old('phone') ?>" />

        <div class="row">
          <div>
            <label for="service">Serviço</label>
            <select id="service" name="service" required>
              <option value="">Selecione</option>
              <option <?= ($old('service') === 'Consulta rápida') ? 'selected' : '' ?>>Consulta rápida</option>
              <option <?= ($old('service') === 'Atendimento estendido') ? 'selected' : '' ?>>Atendimento estendido</option>
              <option <?= ($old('service') === 'Retorno') ? 'selected' : '' ?>>Retorno</option>
            </select>
          </div>

          <div>
            <label for="date">Data</label>
            <input id="date" name="date" type="date" required value="<?= $old('date') ?>" />
          </div>
        </div>

        <div class="row">
          <div>
            <label for="time">Horário</label>
            <input id="time" name="time" type="time" required value="<?= $old('time') ?>" />
          </div>
          <div>
            <label for="duration">Duração (min)</label>
            <select id="duration" name="duration">
              <option <?= ($old('duration') === '30') ? 'selected' : '' ?>>30</option>
              <option <?= ($old('duration') === '60' || $old('duration') === '') ? 'selected' : '' ?>>60</option>
              <option <?= ($old('duration') === '90') ? 'selected' : '' ?>>90</option>
            </select>
          </div>
        </div>

        <label for="notes">Observações (opcional)</label>
        <textarea id="notes" name="notes" rows="3" placeholder="Digite informações relevantes"><?= $old('notes') ?></textarea>

        <button type="submit">Confirmar agendamento</button>
        <footer>Ao enviar, você receberá confirmação por e‑mail.</footer>
      </form>
    </section>
  </main>
</body>
</html>
