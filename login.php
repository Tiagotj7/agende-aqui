<?php
// public/login.php
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($email && $pass) {
        $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && password_verify($pass, $u['password'])) {
            // login ok
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['user_name'] = $u['name'];
            header('Location: index.php');
            exit;
        } else {
            $err = 'Usuário ou senha inválidos.';
        }
    } else {
        $err = 'Preencha email e senha.';
    }
}
$registered = isset($_GET['registered']);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login - Agenda</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4>Login</h4>
          <?php if (!empty($err)): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
          <?php endif; ?>
          <?php if ($registered): ?>
            <div class="alert alert-success">Conta criada! Faça login.</div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-2">
              <label class="form-label">Email</label>
              <input name="email" type="email" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Senha</label>
              <input name="password" type="password" class="form-control" required>
            </div>
            <button class="btn btn-primary">Entrar</button>
            <a class="btn btn-link" href="register.php">Criar conta</a>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
