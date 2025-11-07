<?php
// index.php
require_once 'functions.php';

// Leitura segura do parâmetro action
$action = $_REQUEST['action'] ?? 'list';

// Rota: adicionar (POST)
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php');
        exit;
    }
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $status = (isset($_POST['status']) && $_POST['status'] === '0') ? 0 : 1;

    if ($name === '') {
        flash('Nome é obrigatório.', 'error');
    } else {
        if (createContact($pdo, $name, $email, $phone, $notes, $status)) {
            flash('Contato adicionado com sucesso.', 'success');
        } else {
            flash('Falha ao adicionar contato.', 'error');
        }
    }
    header('Location: index.php');
    exit;
}

// Rota: atualizar (POST)
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
        if (updateContact($pdo, $id, $name, $email, $phone, $notes, $status)) {
            flash('Contato atualizado.', 'success');
        } else {
            flash('Falha ao atualizar.', 'error');
        }
    }
    header('Location: index.php');
    exit;
}

// Rota: excluir (POST)
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        if (deleteContact($pdo, $id)) {
            flash('Contato excluído.', 'success');
        } else {
            flash('Falha ao excluir.', 'error');
        }
    }
    header('Location: index.php');
    exit;
}

// Rota: toggle status (POST)
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        flash('Token CSRF inválido.', 'error');
        header('Location: index.php');
        exit;
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        if (toggleStatus($pdo, $id)) {
            flash('Status alterado.', 'success');
        } else {
            flash('Falha ao alterar status.', 'error');
        }
    }
    header('Location: index.php');
    exit;
}

// Mostrar formulário de editar (GET)
$editing = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $editing = getContact($pdo, (int)$_GET['id']);
    if (!$editing) {
        flash('Contato não encontrado.', 'error');
        header('Location: index.php');
        exit;
    }
}

// LISTAGEM com filtros (GET)
$search = trim($_GET['search'] ?? '');
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$contacts = getContacts($pdo, $search, $statusFilter);

// Mensagens flash
$flash = get_flash();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Agenda Profissional</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    /* Estilos simples, limpos e responsivos */
    body{font-family:Arial,Helvetica,sans-serif;max-width:1100px;margin:20px auto;padding:0 12px;color:#222}
    header{display:flex;justify-content:space-between;align-items:center;gap:12px}
    h1{font-size:1.6rem;margin:0}
    .card{background:#fff;border:1px solid #e5e7eb;padding:14px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.04)}
    .grid{display:grid;grid-template-columns:1fr 360px;gap:18px}
    @media(max-width:880px){.grid{grid-template-columns:1fr}}
    form label{display:block;font-size:0.85rem;margin-top:8px}
    input,textarea,select{width:100%;padding:8px;border:1px solid #cfcfcf;border-radius:6px;box-sizing:border-box}
    button{padding:8px 12px;border-radius:6px;border:none;cursor:pointer}
    .btn-primary{background:#0b74de;color:#fff}
    .btn-secondary{background:#f3f4f6}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
    th{background:#fafafa}
    .muted{color:#6b7280;font-size:0.9rem}
    .flash{padding:10px;margin-bottom:12px;border-radius:6px}
    .flash.success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
    .flash.error{background:#fff1f2;border:1px solid #fecaca;color:#981b1b}
    .actions form{display:inline}
    .small{font-size:0.85rem}
  </style>
</head>
<body>
  <header>
    <h1>Agenda Profissional</h1>
    <div class="muted">CRUD • Status (ativo/inativo) • Acessível</div>
  </header>

  <?php foreach ($flash as $f): ?>
    <div class="flash <?=esc($f['type'])?>"><?=esc($f['msg'])?></div>
  <?php endforeach; ?>

  <div class="grid" role="main">
    <!-- Listagem -->
    <section aria-labelledby="listTitle" class="card" id="list">
      <h2 id="listTitle">Contatos</h2>

      <form method="get" action="index.php" aria-label="Filtrar contatos" style="display:flex;gap:8px;flex-wrap:wrap">
        <input type="search" name="search" placeholder="Pesquisar por nome, e-mail, telefone..." value="<?=esc($search)?>" aria-label="Pesquisar">
        <select name="status" aria-label="Filtrar por status">
          <option value="">Todos os status</option>
          <option value="1" <?=($statusFilter === '1') ? 'selected' : ''?>>Ativos</option>
          <option value="0" <?=($statusFilter === '0') ? 'selected' : ''?>>Inativos</option>
        </select>
        <button class="btn-secondary" type="submit">Filtrar</button>
        <a class="btn-secondary" href="index.php" role="button">Limpar</a>
      </form>

      <?php if (count($contacts) === 0): ?>
        <p class="muted">Nenhum contato encontrado.</p>
      <?php else: ?>
        <table aria-describedby="listTitle">
          <thead>
            <tr>
              <th scope="col">Nome</th>
              <th scope="col">Email</th>
              <th scope="col">Telefone</th>
              <th scope="col">Notas</th>
              <th scope="col">Status</th>
              <th scope="col">Criado</th>
              <th scope="col">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
              <tr>
                <td><?=esc($c['name'])?></td>
                <td><a href="mailto:<?=esc($c['email'])?>"><?=esc($c['email'])?></a></td>
                <td><?=esc($c['phone'])?></td>
                <td class="small"><?=nl2br(esc($c['notes']))?></td>
                <td><?= $c['status'] ? 'Ativo' : 'Inativo' ?></td>
                <td class="muted"><?=esc($c['created_at'])?></td>
                <td class="actions">
                  <!-- Edit (GET) -->
                  <a class="small" href="index.php?action=edit&id=<?=intval($c['id'])?>" aria-label="Editar <?=esc($c['name'])?>">Editar</a>

                  <!-- Toggle status (POST form) -->
                  <form method="post" action="index.php?action=toggle" style="display:inline" onsubmit="return confirm('Alterar status deste contato?')">
                    <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                    <input type="hidden" name="id" value="<?=intval($c['id'])?>">
                    <button class="small" type="submit"><?= $c['status'] ? 'Desativar' : 'Ativar' ?></button>
                  </form>

                  <!-- Delete (POST form) -->
                  <form method="post" action="index.php?action=delete" style="display:inline" onsubmit="return confirm('Excluir contato permanentemente?')">
                    <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                    <input type="hidden" name="id" value="<?=intval($c['id'])?>">
                    <button class="small" type="submit" aria-label="Excluir <?=esc($c['name'])?>">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <!-- Formulário de adicionar / editar -->
    <aside class="card" aria-labelledby="formTitle">
      <h2 id="formTitle"><?= $editing ? 'Editar contato' : 'Adicionar contato' ?></h2>

      <?php if ($editing): ?>
        <form method="post" action="index.php?action=update" aria-describedby="formTitle">
          <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
          <input type="hidden" name="id" value="<?=intval($editing['id'])?>">

          <label for="name">Nome <span aria-hidden="true">*</span></label>
          <input id="name" name="name" required value="<?=esc($editing['name'])?>" aria-required="true">

          <label for="email">Email</label>
          <input id="email" name="email" type="email" value="<?=esc($editing['email'])?>">

          <label for="phone">Telefone</label>
          <input id="phone" name="phone" value="<?=esc($editing['phone'])?>">

          <label for="notes">Notas</label>
          <textarea id="notes" name="notes"><?=esc($editing['notes'])?></textarea>

          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="1" <?=($editing['status'] ? 'selected' : '')?>>Ativo</option>
            <option value="0" <?=(!$editing['status'] ? 'selected' : '')?>>Inativo</option>
          </select>

          <div style="margin-top:10px">
            <button class="btn-primary" type="submit">Salvar alterações</button>
            <a class="btn-secondary" href="index.php">Cancelar</a>
          </div>
        </form>

      <?php else: ?>
        <form method="post" action="index.php?action=add" aria-describedby="formTitle">
          <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">

          <label for="name">Nome <span aria-hidden="true">*</span></label>
          <input id="name" name="name" required aria-required="true">

          <label for="email">Email</label>
          <input id="email" name="email" type="email">

          <label for="phone">Telefone</label>
          <input id="phone" name="phone">

          <label for="notes">Notas</label>
          <textarea id="notes" name="notes"></textarea>

          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="1" selected>Ativo</option>
            <option value="0">Inativo</option>
          </select>

          <div style="margin-top:10px">
            <button class="btn-primary" type="submit">Adicionar</button>
          </div>
        </form>
      <?php endif; ?>

    </aside>
  </div>

  <footer style="margin-top:18px" class="muted small">
    Dica: importe o arquivo <code>tabela.sql</code> no phpMyAdmin do InfinityFree e ajuste <code>config.php</code>.
  </footer>

</body>
</html>
