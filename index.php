<?php
// index.php
require_once 'functions.php';

// debug: se ?debug=1 será retornado JSON com resultado das operações
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';

// Ler parâmetro action
$action = $_REQUEST['action'] ?? 'list';

// Função utilitária para saída debug/json
function respond($arr, $debugMode = false) {
    if ($debugMode) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Rota: adicionar (POST)
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica CSRF e método
    if (!csrf_check($_POST['csrf'] ?? '')) {
        $res = ['ok' => false, 'msg' => 'Token CSRF inválido'];
        log_error('CSRF inválido em add; token recebido: ' . ($_POST['csrf'] ?? '(vazio)'));
        if ($debug) respond($res, true);
        flash($res['msg'], 'error');
        header('Location: index.php');
        exit;
    }

    // Captura e valida campos
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = (isset($_POST['status']) && $_POST['status'] === '0') ? 0 : 1;

    if ($name === '') {
        $res = ['ok' => false, 'msg' => 'O campo nome é obrigatório'];
        if ($debug) respond($res, true);
        flash($res['msg'], 'error');
        header('Location: index.php');
        exit;
    }

    // Tenta inserir
    $result = createContact($pdo, $name, $email, $phone, $notes, $status);

    if ($debug) respond($result, true);

    if ($result['ok']) {
        flash('Contato adicionado com sucesso.', 'success');
    } else {
        flash('Falha ao adicionar: ' . $result['msg'], 'error');
        // Também logamos para investigação
        log_error("Falha em fluxo add: " . $result['msg']);
    }
    header('Location: index.php');
    exit;
}

// Rota: update (POST)
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    $name  = trim($_POST['name'] ?? '');
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
    header('Location: index.php');
    exit;
}

// Rota: delete (POST)
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $r = deleteContact($pdo, $id);
        flash($r['msg'], $r['ok'] ? 'success' : 'error');
    }
    header('Location: index.php');
    exit;
}

// Rota: toggle (POST)
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $r = toggleStatus($pdo, $id);
        flash($r['msg'], $r['ok'] ? 'success' : 'error');
    }
    header('Location: index.php');
    exit;
}

// Mostrar formulário editar (GET)
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editing = getContact($pdo, (int)$_GET['id']);
    if (!$editing) {
        flash('Contato não encontrado', 'error');
        header('Location: index.php');
        exit;
    }
}

// Listagem
$search = trim($_GET['search'] ?? '');
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$contacts = getContacts($pdo, $search, $statusFilter);

// Flash
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Agenda - Debug</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* (estilos reduzidos) */
    body{font-family:Arial,Helvetica,sans-serif;max-width:1100px;margin:20px auto;padding:0 12px;color:#222}
    .card{background:#fff;border:1px solid #e5e7eb;padding:14px;border-radius:8px}
    input,textarea,select{width:100%;padding:8px;margin-top:6px}
    .flash{padding:10px;margin-bottom:12px;border-radius:6px}
    .flash.success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .flash.error{background:#fff1f2;border:1px solid #fecaca;color:#981b1b}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
  </style>
</head>
<body>
  <h1>Agenda (modo debug disponível)</h1>

  <?php foreach ($flash as $f): ?>
    <div class="flash <?=esc($f['type'])?>"><?=esc($f['msg'])?></div>
  <?php endforeach; ?>

  <div style="display:flex;gap:18px;align-items:flex-start">
    <div style="flex:1" class="card">
      <h2>Contatos</h2>

      <form method="get" action="index.php" style="display:flex;gap:8px;flex-wrap:wrap">
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
        <p>Nenhum contato.</p>
      <?php else: ?>
        <table>
          <thead><tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Status</th><th>Ações</th></tr></thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
              <tr>
                <td><?=esc($c['name'])?></td>
                <td><?=esc($c['email'])?></td>
                <td><?=esc($c['phone'])?></td>
                <td><?= $c['status'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                  <a href="index.php?action=edit&id=<?=intval($c['id'])?>">Editar</a>

              <!--   <form method="post" action="index.php?action=toggle" style="display:inline">
                    <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                    <input type="hidden" name="id" value="<?=intval($c['id'])?>">
                    <button type="submit"><?= $c['status'] ? 'Desativar' : 'Ativar' ?></button>
                  </form>
              -->

                  <form method="post" action="index.php?action=delete" style="display:inline" onsubmit="return confirm('Excluir?')">
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

    <aside style="width:380px" class="card">
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

<select id="filterStatus" onchange="loadContacts()">
  <option value="1" selected>Ativos</option>
  <option value="0">Inativos</option>
  <option value="all">Todos</option>
</select>


</body>
</html>
