<?php
// public/index.php
require_once __DIR__ . '/../db.php';
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
</html>
