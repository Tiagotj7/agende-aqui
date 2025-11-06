<?php
// public/register.php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (!$name || !$email || !$pass) {
        $err = 'Preencha todos os campos.';
    } else {
        // verifica duplicidade
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $err = 'Email já cadastrado.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $ins->execute([$name, $email, $hash]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Registrar - Agenda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4>Registrar</h4>
          <?php if (!empty($err)): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-2">
              <label class="form-label">Nome</label>
              <input name="name" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Senha</label>
              <input name="password" type="password" class="form-control" required>
            </div>
            <button class="btn btn-primary">Criar conta</button>
            <a class="btn btn-link" href="login.php">Já tenho conta</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
