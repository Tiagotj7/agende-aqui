<?php
// index.php - agenda simples
require_once 'config.php';

// Ações: add, edit, delete via POST/GET
$action = $_REQUEST['action'] ?? 'list';

// --- ADD ---
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO contacts (name,email,phone,notes) VALUES (?,?,?,?)");
        $stmt->execute([$name, $email, $phone, $notes]);
    }
    header('Location: index.php');
    exit;
}

// --- EDIT (mostrar formulário preenchido) ---
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$contact) {
        header('Location: index.php');
        exit;
    }
}

// --- UPDATE ---
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($id && $name !== '') {
        $stmt = $pdo->prepare("UPDATE contacts SET name=?, email=?, phone=?, notes=? WHERE id=?");
        $stmt->execute([$name, $email, $phone, $notes, $id]);
    }
    header('Location: index.php');
    exit;
}

// --- DELETE ---
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit;
}

// --- LISTAGEM ---
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <title>Agenda Simples</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{font-family:Arial,Helvetica,sans-serif;max-width:900px;margin:20px auto;padding:0 10px;}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{padding:8px;border:1px solid #ddd;text-align:left}
    form{margin-top:12px}
    input,textarea{width:100%;padding:8px;margin:6px 0;box-sizing:border-box}
    .row{display:flex;gap:10px}
    .col{flex:1}
    .btn{padding:8px 12px;cursor:pointer}
    .actions a{margin-right:8px}
  </style>
</head>
<body>
  <h1>Agenda Simples</h1>

  <!-- Formulário de adicionar / editar -->
  <?php if (isset($contact)): ?>
    <h2>Editar contato</h2>
    <form method="post" action="index.php?action=update">
      <input type="hidden" name="id" value="<?=htmlspecialchars($contact['id'])?>">
      <label>Nome</label>
      <input name="name" required value="<?=htmlspecialchars($contact['name'])?>">
      <label>Email</label>
      <input name="email" type="email" value="<?=htmlspecialchars($contact['email'])?>">
      <label>Telefone</label>
      <input name="phone" value="<?=htmlspecialchars($contact['phone'])?>">
      <label>Notas</label>
      <textarea name="notes"><?=htmlspecialchars($contact['notes'])?></textarea>
      <button class="btn" type="submit">Salvar</button>
      <a href="index.php">Cancelar</a>
    </form>
  <?php else: ?>
    <h2>Adicionar contato</h2>
    <form method="post" action="index.php?action=add">
      <label>Nome</label>
      <input name="name" required>
      <div class="row">
        <div class="col">
          <label>Email</label>
          <input name="email" type="email">
        </div>
        <div class="col">
          <label>Telefone</label>
          <input name="phone">
        </div>
      </div>
      <label>Notas</label>
      <textarea name="notes"></textarea>
      <button class="btn" type="submit">Adicionar</button>
    </form>
  <?php endif; ?>

  <h2>Contatos</h2>
  <?php if (count($contacts) === 0): ?>
    <p>Nenhum contato cadastrado.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr><th>Nome</th><th>Email</th><th>Telefone</th><th>Notas</th><th>Criado</th><th>Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $c): ?>
          <tr>
            <td><?=htmlspecialchars($c['name'])?></td>
            <td><?=htmlspecialchars($c['email'])?></td>
            <td><?=htmlspecialchars($c['phone'])?></td>
            <td><?=nl2br(htmlspecialchars($c['notes']))?></td>
            <td><?=htmlspecialchars($c['created_at'])?></td>
            <td class="actions">
              <a href="index.php?action=edit&id=<?=intval($c['id'])?>">Editar</a>
              <a href="index.php?action=delete&id=<?=intval($c['id'])?>" onclick="return confirm('Excluir?')">Excluir</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

</body>
</html>
