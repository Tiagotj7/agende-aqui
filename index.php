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
    /* --------------------------
       Theme variables (light/dark)
       -------------------------- */
    :root{
      --bg: #f5f7fb;
      --panel: #ffffff;
      --muted: #6b7280;
      --text: #0f172a;
      --accent: #0066ff;
      --accent-600: #0051cc;
      --glass: rgba(255,255,255,0.6);
      --success: #10b981;
      --danger: #ef4444;
      --border: rgba(15,23,42,0.06);
      --shadow: 0 8px 24px rgba(15,23,42,0.06);
      --radius: 12px;
      --glass-2: rgba(255,255,255,0.4);
    }
    [data-theme="dark"]{
      --bg: #0b1220;
      --panel: linear-gradient(180deg, rgba(18,22,30,0.8), rgba(12,16,22,0.8));
      --muted: #9aa4b2;
      --text: #e6eef8;
      --accent: #4f8cff;
      --accent-600: #2f6fe6;
      --glass: rgba(255,255,255,0.04);
      --success: #34d399;
      --danger: #f87171;
      --border: rgba(255,255,255,0.06);
      --shadow: 0 10px 30px rgba(2,6,23,0.6);
      --glass-2: rgba(255,255,255,0.02);
    }

    /* --------------------------
       Base & layout
       -------------------------- */
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family:Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      background: radial-gradient(1200px 600px at 10% 10%, rgba(0,102,255,0.04), transparent),
                  var(--bg);
      color:var(--text);
      padding:28px;
    }

    .wrap{
      max-width:1200px;
      margin:0 auto;
      display:grid;
      grid-template-columns: 1fr 380px;
      gap:22px;
      align-items:start;
    }

    /* --------------------------
       Header (title + actions)
       -------------------------- */
    header.appbar{
      grid-column:1/-1;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:12px;
      margin-bottom:2px;
    }

    .brand{
      display:flex;
      gap:14px;
      align-items:center;
    }

    .logo{
      width:44px;height:44px;border-radius:10px;
      background:linear-gradient(135deg,var(--accent),var(--accent-600));
      display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;
      box-shadow: 0 6px 18px rgba(37,99,235,0.14);
      font-family:Inter, sans-serif;
      font-size:1.05rem;
    }

    .title{
      display:flex;flex-direction:column;
      line-height:1;
    }
    .title .h{font-size:1.15rem;font-weight:700}
    .title .sub{font-size:0.85rem;color:var(--muted);margin-top:2px}

    .actions{
      display:flex;
      gap:10px;
      align-items:center;
    }

    .btn{
      display:inline-flex;align-items:center;gap:8px;
      padding:8px 12px;border-radius:10px;border:1px solid var(--border);
      background:var(--panel);box-shadow:var(--shadow);
      cursor:pointer;font-weight:600;color:var(--text);
      text-decoration:none;
    }
    .btn.primary{
      background:linear-gradient(180deg,var(--accent),var(--accent-600));
      color:#fff;border:0;box-shadow: 0 8px 28px rgba(37,99,235,0.12);
    }
    .btn.ghost{background:transparent;border:1px solid rgba(0,0,0,0.06)}
    .btn svg{opacity:0.95}

    /* --------------------------
       Left: contacts card
       -------------------------- */
    .panel{
      background:var(--panel);
      border-radius:var(--radius);
      padding:18px;
      box-shadow:var(--shadow);
      border:1px solid var(--border);
    }

    .controls{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      align-items:center;
      margin-bottom:12px;
    }

    .search{
      flex:1;min-width:200px;
      display:flex;gap:8px;align-items:center;
      background:var(--glass);padding:8px;border-radius:10px;border:1px solid var(--border);
    }
    .search input{
      border:0;background:transparent;outline:none;color:var(--text);font-size:0.95rem;width:100%;
    }

    .filter{
      display:flex;gap:8px;align-items:center;
    }
    .pill{
      padding:8px 10px;border-radius:999px;background:var(--glass-2);border:1px solid var(--border);font-size:0.9rem;color:var(--muted);
    }

    /* --------------------------
       Table style
       -------------------------- */
    .tbl{
      width:100%;border-collapse:collapse;margin-top:6px;
    }
    .tbl th, .tbl td{padding:12px 10px;text-align:left;border-bottom:1px solid var(--border);vertical-align:middle}
    .tbl thead th{font-size:0.85rem;color:var(--muted);background:transparent;border-bottom:1px dashed var(--border)}
    .row-item{display:flex;gap:12px;align-items:center}
    .avatar{
      width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;
      background:linear-gradient(135deg, rgba(0,0,0,0.06), rgba(0,0,0,0.02)); color:var(--text); font-weight:700;
      font-size:0.95rem;
    }
    .name small{display:block;color:var(--muted);font-size:0.85rem;margin-top:3px}

    .status{
      padding:6px 8px;border-radius:999px;font-weight:700;font-size:0.78rem;
    }
    .status.active{background:rgba(16,185,129,0.12);color:var(--success);border:1px solid rgba(16,185,129,0.14)}
    .status.inactive{background:rgba(239,68,68,0.08);color:var(--danger);border:1px solid rgba(239,68,68,0.08)}

    .actions-col{display:flex;gap:8px;align-items:center}
    .link-edit{padding:6px 8px;border-radius:8px;background:rgba(14,165,233,0.06);color:#0369a1;border:1px solid rgba(14,165,233,0.06);text-decoration:none;font-weight:600}
    .btn-delete{padding:6px 8px;border-radius:8px;background:rgba(239,68,68,0.06);color:var(--danger);border:1px solid rgba(239,68,68,0.06);font-weight:600}

    /* --------------------------
       Right: form
       -------------------------- */
    .form-col{position:sticky;top:28px}
    .form-grid{display:grid;gap:10px}
    label.field{font-size:0.85rem;color:var(--muted);display:block;margin-bottom:6px}
    input.field, textarea.field, select.field{
      width:100%;padding:10px;border-radius:10px;border:1px solid var(--border);background:transparent;color:var(--text);
      font-size:0.95rem;
    }
    textarea.field{min-height:110px;resize:vertical}

    .form-actions{display:flex;gap:8px;align-items:center;margin-top:8px}
    .small-link{color:var(--muted);font-weight:600;text-decoration:none}

    /* --------------------------
       Flash
       -------------------------- */
    .flash{
      padding:12px;border-radius:10px;margin-bottom:12px;font-weight:600;font-size:0.95rem;
    }
    .flash.success{background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.12);color:var(--success)}
    .flash.error{background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.08);color:var(--danger)}

    /* --------------------------
       Footer
       -------------------------- */
    footer{grid-column:1/-1;text-align:center;color:var(--muted);margin-top:20px;font-size:0.9rem}

    /* --------------------------
       Responsive
       -------------------------- */
    @media (max-width:980px){
      .wrap{grid-template-columns:1fr; padding:18px}
      .form-col{position:relative;top:auto}
      header.appbar{flex-direction:column;align-items:flex-start;gap:10px}
    }
  </style>
</head>
<body data-theme="light">
  <div class="wrap">
    <header class="appbar">
      <div class="brand">
        <div class="logo">AA</div>
        <div class="title">
          <div class="h">Agenda Particular</div>
          <div class="sub">Sistema simples e elegante para gerenciar seus contatos</div>
        </div>
      </div>

      <div class="actions">
        <button class="btn" id="themeToggle" title="Alternar tema (claro/escuro)" aria-label="Alternar tema">
          <!-- moon / sun icon -->
          <svg id="iconTheme" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Tema
        </button>

        <a class="btn primary" href="index.php?action=add" onclick="document.getElementById('name')?.focus(); return true;">
          <!-- plus icon -->
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Novo contato
        </a>
      </div>
    </header>

    <!-- LEFT: contacts list -->
    <section class="panel">
      <?php if (count($flash)): foreach ($flash as $f): ?>
        <div class="flash <?=esc($f['type'])?>"><?=esc($f['msg'])?></div>
      <?php endforeach; endif; ?>

      <div class="controls" role="toolbar" aria-label="Controles de lista">
        <form method="get" action="index.php" style="display:flex;flex:1;">
          <div class="search" aria-hidden="false">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="opacity:0.7"><path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M11 19a8 8 0 100-16 8 8 0 000 16z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <input aria-label="Pesquisar" type="search" name="search" placeholder="Pesquisar por nome, email ou telefone..." value="<?=esc($search)?>">
          </div>

          <div style="display:flex;gap:8px;margin-left:8px;">
            <select name="status" aria-label="Filtrar status" style="border-radius:10px;padding:8px;border:1px solid var(--border);background:transparent;color:var(--text)">
              <option value="">Todos</option>
              <option value="1" <?=($statusFilter === '1') ? 'selected' : ''?>>Ativos</option>
              <option value="0" <?=($statusFilter === '0') ? 'selected' : ''?>>Inativos</option>
            </select>
            <button class="btn" type="submit" style="padding:8px 12px">Filtrar</button>
            <a class="btn" href="index.php" style="padding:8px 12px">Limpar</a>
          </div>
        </form>
      </div>

      <?php if (count($contacts) === 0): ?>
        <div style="padding:28px;text-align:center;color:var(--muted)">
          <strong>Nenhum contato encontrado</strong>
          <div style="margin-top:8px;font-size:0.95rem">Adicione novos contatos usando o formulário à direita.</div>
        </div>
      <?php else: ?>
        <table class="tbl" role="table" aria-label="Lista de contatos">
          <thead>
            <tr>
              <th>Contato</th>
              <th>Email</th>
              <th>Telefone</th>
              <th>Status</th>
              <th style="width:1%;">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
              <tr>
                <td>
                  <div class="row-item">
                    <div class="avatar" aria-hidden="true"><?=htmlspecialchars(mb_substr($c['name'],0,1))?></div>
                    <div class="name">
                      <strong><?=esc($c['name'])?></strong>
                      <small><?=esc($c['notes'])?></small>
                    </div>
                  </div>
                </td>
                <td><?=esc($c['email'])?></td>
                <td><?=esc($c['phone'])?></td>
                <td>
                  <?php if ($c['status']): ?>
                    <span class="status active">Ativo</span>
                  <?php else: ?>
                    <span class="status inactive">Inativo</span>
                  <?php endif; ?>
                </td>
                <td class="actions-col">
                  <a class="link-edit" href="index.php?action=edit&id=<?=intval($c['id'])?>" title="Editar">Editar</a>

                  <form method="post" action="index.php?action=delete" style="display:inline" onsubmit="return confirmDelete(event, '<?=esc(js_addslashes($c['name']))?>')">
                    <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                    <input type="hidden" name="id" value="<?=intval($c['id'])?>">
                    <button type="submit" class="btn-delete" aria-label="Excluir">Excluir</button>
                  </form>
                </td>
              </tr>
            <?php endforeach;?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <!-- RIGHT: form -->
    <aside class="form-col">
      <div class="panel">
        <h3 style="margin:0 0 6px;font-size:1.05rem"><?= $editing ? 'Editar contato' : 'Adicionar contato' ?></h3>
        <p style="margin:0 0 12px;color:var(--muted);font-size:0.9rem"><?= $editing ? 'Atualize as informações do contato.' : 'Preencha os dados para criar um novo contato.' ?></p>

        <?php if ($editing): ?>
          <form method="post" action="index.php?action=update" class="form-grid" onsubmit="return validateForm(this)">
            <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
            <input type="hidden" name="id" value="<?=intval($editing['id'])?>">
            <div>
              <label class="field">Nome</label>
              <input class="field" name="name" required value="<?=esc($editing['name'])?>">
            </div>
            <div>
              <label class="field">Email</label>
              <input class="field" name="email" type="email" value="<?=esc($editing['email'])?>">
            </div>
            <div>
              <label class="field">Telefone</label>
              <input class="field" name="phone" value="<?=esc($editing['phone'])?>">
            </div>
            <div>
              <label class="field">Notas</label>
              <textarea class="field" name="notes"><?=esc($editing['notes'])?></textarea>
            </div>
            <div>
              <label class="field">Status</label>
              <select class="field" name="status">
                <option value="1" <?=($editing['status'] ? 'selected' : '')?>>Ativo</option>
                <option value="0" <?=(!$editing['status'] ? 'selected' : '')?>>Inativo</option>
              </select>
            </div>

            <div class="form-actions" style="grid-column:1/-1">
              <button class="btn primary" type="submit">Salvar alterações</button>
              <a class="small-link" href="index.php">Cancelar</a>
            </div>
          </form>
        <?php else: ?>
          <form method="post" action="index.php?action=add" id="formAdd" class="form-grid" onsubmit="return validateForm(this)">
            <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
            <div>
              <label class="field">Nome</label>
              <input id="name" class="field" name="name" required>
            </div>
            <div>
              <label class="field">Email</label>
              <input class="field" name="email" type="email">
            </div>
            <div>
              <label class="field">Telefone</label>
              <input class="field" name="phone">
            </div>
            <div style="grid-column:1/-1">
              <label class="field">Notas</label>
              <textarea class="field" name="notes"></textarea>
            </div>
            <div>
              <label class="field">Status</label>
              <select class="field" name="status">
                <option value="1" selected>Ativo</option>
                <option value="0">Inativo</option>
              </select>
            </div>

            <div class="form-actions" style="grid-column:1/-1">
              <button class="btn primary" type="submit">Adicionar contato</button>
              <a class="small-link" href="index.php">Limpar</a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </aside>

    <footer>© <?=date('Y')?> Agenda Aqui — Sistema de agendamentos simples e eficiente</footer>
  </div>

  <script>
    // small helper: escape single quotes for JS confirm
    function confirmDelete(e, name) {
      var ok = confirm('Excluir contato "' + name + '"? Esta ação é irreversível.');
      if (!ok) e.preventDefault();
      return ok;
    }

    // Basic form client validation (name required)
    function validateForm(form) {
      var name = form.querySelector('[name="name"]');
      if (!name || !name.value.trim()) {
        alert('Por favor, preencha o nome do contato.');
        name && name.focus();
        return false;
      }
      return true;
    }

    // Theme toggle (stores preference in localStorage)
    (function(){
      var body = document.body;
      var btn = document.getElementById('themeToggle');
      var icon = document.getElementById('iconTheme');

      function setTheme(t){
        body.setAttribute('data-theme', t);
        localStorage.setItem('aa_theme', t);
        // tweak icon
        if(t === 'dark') {
          icon.innerHTML = '<path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>';
        } else {
          icon.innerHTML = '<path d="M12 3v2M12 19v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4M8 12a4 4 0 108 0 4 4 0 00-8 0z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>';
        }
      }

      var stored = localStorage.getItem('aa_theme') || 'light';
      setTheme(stored);

      btn.addEventListener('click', function(e){
        var cur = body.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        var next = cur === 'dark' ? 'light' : 'dark';
        setTheme(next);
      });
    })();

    // helper to safely insert php value into confirm string
    // defined in PHP: js_addslashes helper expected; if not present, fallback:
  </script>
</body>
</html>
