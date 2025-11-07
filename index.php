<?php
require_once 'functions.php';
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
$action = $_REQUEST['action'] ?? 'list';
function respond($arr, $debugMode = false) {
    if ($debugMode) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido', 'error');
        header('Location: index.php'); exit;
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = (isset($_POST['status']) && $_POST['status'] === '0') ? 0 : 1;
    if ($name === '') {
        flash('O campo nome é obrigatório', 'error');
        header('Location: index.php'); exit;
    }
    $r = createContact($pdo, $name, $email, $phone, $notes, $status);
    flash($r['ok'] ? 'Contato adicionado com sucesso.' : 'Falha: '.$r['msg'], $r['ok'] ? 'success' : 'error');
    header('Location: index.php'); exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php'); exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = (isset($_POST['status']) && $_POST['status'] === '0') ? 0 : 1;
    if ($id <= 0 || $name === '') {
        flash('Dados inválidos.', 'error');
    } else {
        $r = updateContact($pdo, $id, $name, $email, $phone, $notes, $status);
        flash($r['msg'], $r['ok'] ? 'success' : 'error');
    }
    header('Location: index.php'); exit;
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php'); exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $r = deleteContact($pdo, $id);
        flash($r['msg'], $r['ok'] ? 'success' : 'error');
    }
    header('Location: index.php'); exit;
}

$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editing = getContact($pdo, (int)$_GET['id']);
    if (!$editing) {
        flash('Contato não encontrado', 'error');
        header('Location: index.php'); exit;
    }
}

$search = trim($_GET['search'] ?? '');
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$contacts = getContacts($pdo, $search, $statusFilter);
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Agenda Aqui</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    :root {
      --bg: #f9fafb;
      --card: #ffffff;
      --primary: #2563eb;
      --primary-hover: #1e40af;
      --border: #e5e7eb;
      --text: #111827;
      --muted: #6b7280;
      --success-bg: #ecfdf5;
      --success-border: #a7f3d0;
      --error-bg: #fef2f2;
      --error-border: #fecaca;
    }

    * {
      box-sizing: border-box;
      font-family: Inter, system-ui, Segoe UI, Arial, sans-serif;
    }

    body {
      margin: 0;
      background: var(--bg);
      color: var(--text);
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    h1 {
      font-size: 1.8rem;
      color: var(--primary);
      margin-bottom: 20px;
      text-align: center;
    }

    h2 {
      font-size: 1.2rem;
      margin-bottom: 10px;
      color: var(--text);
    }

    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
      transition: 0.2s ease;
    }

    .card:hover {
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    }

    input, textarea, select, button {
      width: 100%;
      padding: 10px;
      margin-top: 6px;
      border-radius: 8px;
      border: 1px solid var(--border);
      font-size: 0.95rem;
    }

    textarea {
      resize: none;      /* desativa o redimensionamento */
      overflow: auto;    /* garante que barras de rolagem apareçam se o texto for longo */
    }

    input:focus, textarea:focus, select:focus {
      border-color: var(--primary);
      outline: none;
      box-shadow: 0 0 0 2px rgba(37,99,235,0.1);
    }

    button {
      background: var(--primary);
      color: #fff;
      border: none;
      font-weight: 600;
      cursor: pointer;
      transition: 0.25s;
    }

    button:hover {
      background: var(--primary-hover);
    }

    a {
      text-decoration: none;
      color: var(--primary);
      font-weight: 500;
    }

    .flash {
      padding: 12px 14px;
      border-radius: 8px;
      margin-bottom: 16px;
      font-size: 0.95rem;
    }

    .flash.success {
      background: var(--success-bg);
      border: 1px solid var(--success-border);
      color: #065f46;
    }

    .flash.error {
      background: var(--error-bg);
      border: 1px solid var(--error-border);
      color: #991b1b;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
    }

    th {
      background: #f3f4f6;
      text-align: left;
      padding: 10px;
      color: var(--muted);
      font-size: 0.9rem;
    }

    td {
      padding: 10px;
      border-bottom: 1px solid var(--border);
      font-size: 0.95rem;
    }

    td a, td button {
      font-size: 0.85rem;
      padding: 6px 10px;
      border-radius: 6px;
      text-decoration: none;
      display: inline-block;
    }

    td a {
      background: #e0f2fe;
      color: #0369a1;
    }

    td button {
      background: #fee2e2;
      color: #991b1b;
      border: none;
    }

    td button:hover {
      background: #fecaca;
    }

    .layout {
      display: flex;
      gap: 20px;
      align-items: flex-start;
      flex-wrap: wrap;
    }

    .layout > .card {
      flex: 1;
      min-width: 350px;
    }

    form[action="index.php"] {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }

    form[action="index.php"] input,
    form[action="index.php"] select {
      flex: 1;
      min-width: 150px;
    }

    form[action="index.php"] button {
      flex: 0;
      padding: 10px 18px;
    }

    footer {
      text-align: center;
      font-size: 0.85rem;
      color: var(--muted);
      margin-top: 40px;
    }
  </style>
</head>
<body>
  <h1>Agenda Particular</h1>

  <?php foreach ($flash as $f): ?>
    <div class="flash <?=esc($f['type'])?>"><?=esc($f['msg'])?></div>
  <?php endforeach; ?>

  <div class="layout">
    <div class="card">
      <h2>Contatos</h2>

      <form method="get" action="index.php">
        <input type="search" name="search" placeholder="Pesquisar..." value="<?=esc($search)?>">
        <select name="status">
          <option value="">Todos</option>
          <option value="1" <?=($statusFilter === '1') ? 'selected' : ''?>>Ativos</option>
          <option value="0" <?=($statusFilter === '0') ? 'selected' : ''?>>Inativos</option>
        </select>
        <button type="submit">Filtrar</button>
        <a href="index.php">Limpar</a>
      </form>

      <?php if (count($contacts) === 0): ?>
        <p>Nenhum contato encontrado.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Nome</th>
              <th>Email</th>
              <th>Telefone</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
              <tr>
                <td><?=esc($c['name'])?></td>
                <td><?=esc($c['email'])?></td>
                <td><?=esc($c['phone'])?></td>
                <td><?= $c['status'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                  <a href="index.php?action=edit&id=<?=intval($c['id'])?>">Editar</a>
                  <form method="post" action="index.php?action=delete" style="display:inline" onsubmit="return confirm('Excluir este contato?')">
                    <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                    <input type="hidden" name="id" value="<?=intval($c['id'])?>">
                    <button type="submit">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <aside class="card">
      <h2><?= $editing ? 'Editar contato' : 'Adicionar contato' ?></h2>
      <?php if ($editing): ?>
        <form method="post" action="index.php?action=update">
          <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
          <input type="hidden" name="id" value="<?=intval($editing['id'])?>">
          <label>Nome</label>
          <input name="name" required value="<?=esc($editing['name'])?>">
          <label>Email</label>
          <input name="email" type="email" value="<?=esc($editing['email'])?>">
          <label>Telefone</label>
          <input name="phone" value="<?=esc($editing['phone'])?>">
          <label>Notas</label>
          <textarea name="notes"><?=esc($editing['notes'])?></textarea>
          <label>Status</label>
          <select name="status">
            <option value="1" <?=($editing['status'] ? 'selected' : '')?>>Ativo</option>
            <option value="0" <?=(!$editing['status'] ? 'selected' : '')?>>Inativo</option>
          </select>
          <div style="margin-top:10px">
            <button type="submit">Salvar</button>
            <a href="index.php">Cancelar</a>
          </div>
        </form>
      <?php else: ?>
        <form method="post" action="index.php?action=add" id="formAdd">
          <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
          <label>Nome</label>
          <input id="name" name="name" required>
          <label>Email</label>
          <input name="email" type="email">
          <label>Telefone</label>
          <input name="phone">
          <label>Notas</label>
          <textarea name="notes"></textarea>
          <label>Status</label>
          <select name="status">
            <option value="1" selected>Ativo</option>
            <option value="0">Inativo</option>
          </select>
          <div style="margin-top:10px">
            <button type="submit">Adicionar</button>
          </div>
        </form>
      <?php endif; ?>
    </aside>
  </div>

  <footer>© 2025 Agenda Aqui — Sistema de agendamentos simples e eficiente.</footer>
</body>
</html>
