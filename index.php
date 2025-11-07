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
  <title>Agenda Elegante</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* --------------------------
       Design System Moderno
       -------------------------- */
    :root {
      /* Cores principais */
      --primary: #6366f1;
      --primary-dark: #4f46e5;
      --primary-light: #8b5cf6;
      --secondary: #f59e0b;
      --accent: #ec4899;
      
      /* Cores neutras - Tema Claro */
      --bg-primary: #f8fafc;
      --bg-secondary: #ffffff;
      --bg-card: rgba(255, 255, 255, 0.8);
      --text-primary: #1e293b;
      --text-secondary: #64748b;
      --text-muted: #94a3b8;
      --border: #e2e8f0;
      --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
      --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.1), 0 10px 15px -5px rgba(0, 0, 0, 0.05);
      
      /* Estados */
      --success: #10b981;
      --warning: #f59e0b;
      --error: #ef4444;
      --info: #3b82f6;
      
      /* Gradientes */
      --gradient-primary: linear-gradient(135deg, var(--primary), var(--primary-light));
      --gradient-secondary: linear-gradient(135deg, var(--secondary), #f97316);
      --gradient-bg: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 90%);
      
      /* Efeitos de vidro */
      --glass: rgba(255, 255, 255, 0.7);
      --glass-border: rgba(255, 255, 255, 0.2);
      
      /* Bordas */
      --radius-sm: 8px;
      --radius: 12px;
      --radius-lg: 16px;
      --radius-xl: 20px;
      
      /* Espaçamentos */
      --space-xs: 4px;
      --space-sm: 8px;
      --space-md: 16px;
      --space-lg: 24px;
      --space-xl: 32px;
      --space-2xl: 48px;
    }

    [data-theme="dark"] {
      /* Cores neutras - Tema Escuro */
      --bg-primary: #0f172a;
      --bg-secondary: #1e293b;
      --bg-card: rgba(30, 41, 59, 0.7);
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --text-muted: #64748b;
      --border: #334155;
      --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
      --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.4), 0 10px 15px -5px rgba(0, 0, 0, 0.3);
      
      /* Efeitos de vidro */
      --glass: rgba(30, 41, 59, 0.7);
      --glass-border: rgba(255, 255, 255, 0.1);
      
      /* Gradientes */
      --gradient-bg: radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 90%);
    }

    /* --------------------------
       Reset e Base
       -------------------------- */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      height: 100%;
      font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      background: var(--bg-primary);
      color: var(--text-primary);
      line-height: 1.5;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    body {
      padding: 0;
      background: var(--gradient-bg);
      min-height: 100vh;
    }

    /* --------------------------
       Layout Principal
       -------------------------- */
    .app-container {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      padding: var(--space-lg);
      max-width: 1400px;
      margin: 0 auto;
      gap: var(--space-xl);
    }

    .app-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-lg) 0;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: var(--space-md);
    }

    .logo {
      width: 52px;
      height: 52px;
      border-radius: var(--radius-lg);
      background: var(--gradient-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 1.25rem;
      box-shadow: var(--shadow);
      position: relative;
      overflow: hidden;
    }

    .logo::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%);
    }

    .brand-text {
      display: flex;
      flex-direction: column;
    }

    .brand-name {
      font-size: 1.5rem;
      font-weight: 700;
      background: var(--gradient-primary);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      line-height: 1.2;
    }

    .brand-tagline {
      font-size: 0.875rem;
      color: var(--text-secondary);
      margin-top: var(--space-xs);
    }

    .header-actions {
      display: flex;
      gap: var(--space-sm);
      align-items: center;
    }

    /* --------------------------
       Componentes Principais
       -------------------------- */
    .main-content {
      display: grid;
      grid-template-columns: 1fr 400px;
      gap: var(--space-xl);
      align-items: start;
    }

    .card {
      background: var(--bg-card);
      backdrop-filter: blur(10px);
      border-radius: var(--radius-xl);
      border: 1px solid var(--glass-border);
      box-shadow: var(--shadow);
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }

    .card-header {
      padding: var(--space-lg);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--text-primary);
    }

    .card-body {
      padding: var(--space-lg);
    }

    /* --------------------------
       Botões
       -------------------------- */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: var(--space-sm);
      padding: 10px 16px;
      border-radius: var(--radius);
      font-weight: 500;
      font-size: 0.875rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      border: none;
      outline: none;
      position: relative;
      overflow: hidden;
    }

    .btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%);
      opacity: 0;
      transition: opacity 0.2s ease;
    }

    .btn:hover::after {
      opacity: 1;
    }

    .btn-primary {
      background: var(--gradient-primary);
      color: white;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
    }

    .btn-secondary {
      background: var(--bg-secondary);
      color: var(--text-primary);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover {
      background: var(--bg-primary);
      transform: translateY(-1px);
    }

    .btn-ghost {
      background: transparent;
      color: var(--text-secondary);
      border: 1px solid transparent;
    }

    .btn-ghost:hover {
      background: var(--bg-primary);
      color: var(--text-primary);
    }

    .btn-icon {
      padding: 10px;
    }

    /* --------------------------
       Formulários
       -------------------------- */
    .form-group {
      margin-bottom: var(--space-md);
    }

    .form-label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-secondary);
      margin-bottom: var(--space-sm);
    }

    .form-input, .form-select, .form-textarea {
      width: 100%;
      padding: 12px 16px;
      border-radius: var(--radius);
      border: 1px solid var(--border);
      background: var(--bg-secondary);
      color: var(--text-primary);
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    .form-input:focus, .form-select:focus, .form-textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }

    .form-textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-actions {
      display: flex;
      gap: var(--space-sm);
      align-items: center;
      margin-top: var(--space-lg);
    }

    /* --------------------------
       Lista de Contatos
       -------------------------- */
    .contacts-controls {
      display: flex;
      gap: var(--space-md);
      margin-bottom: var(--space-lg);
      flex-wrap: wrap;
    }

    .search-box {
      flex: 1;
      min-width: 300px;
      display: flex;
      align-items: center;
      gap: var(--space-sm);
      background: var(--bg-secondary);
      padding: var(--space-sm) var(--space-md);
      border-radius: var(--radius);
      border: 1px solid var(--border);
    }

    .search-input {
      border: none;
      background: transparent;
      outline: none;
      color: var(--text-primary);
      font-size: 0.95rem;
      width: 100%;
    }

    .filters {
      display: flex;
      gap: var(--space-sm);
      align-items: center;
    }

    .contacts-table {
      width: 100%;
      border-collapse: collapse;
    }

    .contacts-table th {
      text-align: left;
      padding: var(--space-md);
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--text-secondary);
      border-bottom: 1px solid var(--border);
    }

    .contacts-table td {
      padding: var(--space-md);
      border-bottom: 1px solid var(--border);
      vertical-align: middle;
    }

    .contact-row {
      transition: background-color 0.2s ease;
    }

    .contact-row:hover {
      background: var(--bg-primary);
    }

    .contact-info {
      display: flex;
      align-items: center;
      gap: var(--space-md);
    }

    .contact-avatar {
      width: 44px;
      height: 44px;
      border-radius: var(--radius);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      font-size: 1rem;
      position: relative;
      overflow: hidden;
    }

    .avatar-1 { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    .avatar-2 { background: linear-gradient(135deg, #f59e0b, #f97316); }
    .avatar-3 { background: linear-gradient(135deg, #10b981, #059669); }
    .avatar-4 { background: linear-gradient(135deg, #ec4899, #db2777); }
    .avatar-5 { background: linear-gradient(135deg, #3b82f6, #2563eb); }

    .contact-avatar::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 50%);
    }

    .contact-details {
      display: flex;
      flex-direction: column;
    }

    .contact-name {
      font-weight: 600;
      color: var(--text-primary);
    }

    .contact-notes {
      font-size: 0.875rem;
      color: var(--text-secondary);
      margin-top: 2px;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 500;
    }

    .status-active {
      background: rgba(16, 185, 129, 0.1);
      color: var(--success);
    }

    .status-inactive {
      background: rgba(239, 68, 68, 0.1);
      color: var(--error);
    }

    .status-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
    }

    .status-active .status-dot {
      background: var(--success);
    }

    .status-inactive .status-dot {
      background: var(--error);
    }

    .contact-actions {
      display: flex;
      gap: var(--space-sm);
    }

    .action-btn {
      padding: 8px;
      border-radius: var(--radius-sm);
      background: transparent;
      border: none;
      color: var(--text-secondary);
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .action-btn:hover {
      background: var(--bg-primary);
      color: var(--text-primary);
    }

    .edit-btn:hover {
      color: var(--info);
    }

    .delete-btn:hover {
      color: var(--error);
    }

    /* --------------------------
       Mensagens Flash
       -------------------------- */
    .flash-messages {
      position: fixed;
      top: var(--space-lg);
      right: var(--space-lg);
      z-index: 1000;
      display: flex;
      flex-direction: column;
      gap: var(--space-sm);
      max-width: 400px;
    }

    .flash-message {
      padding: var(--space-md) var(--space-lg);
      border-radius: var(--radius);
      font-weight: 500;
      box-shadow: var(--shadow-lg);
      animation: slideInRight 0.3s ease;
      display: flex;
      align-items: center;
      gap: var(--space-sm);
    }

    .flash-success {
      background: var(--success);
      color: white;
    }

    .flash-error {
      background: var(--error);
      color: white;
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(100%);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* --------------------------
       Estados Vazios
       -------------------------- */
    .empty-state {
      padding: var(--space-2xl);
      text-align: center;
      color: var(--text-secondary);
    }

    .empty-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto var(--space-lg);
      border-radius: 50%;
      background: var(--bg-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
    }

    .empty-title {
      font-size: 1.125rem;
      font-weight: 600;
      margin-bottom: var(--space-sm);
      color: var(--text-primary);
    }

    .empty-description {
      font-size: 0.95rem;
      max-width: 400px;
      margin: 0 auto;
    }

    /* --------------------------
       Footer
       -------------------------- */
    .app-footer {
      text-align: center;
      padding: var(--space-lg) 0;
      color: var(--text-secondary);
      font-size: 0.875rem;
      margin-top: auto;
    }

    /* --------------------------
       Responsividade
       -------------------------- */
    @media (max-width: 1024px) {
      .main-content {
        grid-template-columns: 1fr;
      }
      
      .form-sidebar {
        order: -1;
      }
    }

    @media (max-width: 768px) {
      .app-container {
        padding: var(--space-md);
      }
      
      .app-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-md);
      }
      
      .header-actions {
        width: 100%;
        justify-content: space-between;
      }
      
      .contacts-controls {
        flex-direction: column;
      }
      
      .search-box {
        min-width: 100%;
      }
      
      .contacts-table {
        display: block;
        overflow-x: auto;
      }
    }

    /* --------------------------
       Utilitários
       -------------------------- */
    .text-center {
      text-align: center;
    }

    .text-muted {
      color: var(--text-muted);
    }

    .mt-1 { margin-top: var(--space-sm); }
    .mt-2 { margin-top: var(--space-md); }
    .mt-3 { margin-top: var(--space-lg); }
    .mb-1 { margin-bottom: var(--space-sm); }
    .mb-2 { margin-bottom: var(--space-md); }
    .mb-3 { margin-bottom: var(--space-lg); }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
  </style>
</head>
<body data-theme="light">
  <div class="app-container">
    <!-- Flash Messages -->
    <?php if (count($flash)): ?>
      <div class="flash-messages">
        <?php foreach ($flash as $f): ?>
          <div class="flash-message flash-<?=esc($f['type'])?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <?php if ($f['type'] === 'success'): ?>
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
              <?php else: ?>
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
              <?php endif; ?>
            </svg>
            <?=esc($f['msg'])?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Header -->
    <header class="app-header">
      <div class="brand">
        <div class="logo">AE</div>
        <div class="brand-text">
          <h1 class="brand-name">Agenda Elegante</h1>
          <p class="brand-tagline">Gerencie seus contatos com estilo e eficiência</p>
        </div>
      </div>

      <div class="header-actions">
        <button class="btn btn-secondary" id="themeToggle" aria-label="Alternar tema">
          <svg id="iconTheme" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
          </svg>
          Tema
        </button>

        <a class="btn btn-primary" href="index.php?action=add">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
          </svg>
          Novo Contato
        </a>
      </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Contacts List -->
      <section class="contacts-section">
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Meus Contatos</h2>
            <span class="text-muted"><?=count($contacts)?> contatos</span>
          </div>
          
          <div class="card-body">
            <div class="contacts-controls">
              <form method="get" action="index.php" class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input class="search-input" type="search" name="search" placeholder="Pesquisar contatos..." value="<?=esc($search)?>" aria-label="Pesquisar contatos">
              </form>
              
              <div class="filters">
                <select name="status" class="form-select" onchange="this.form.submit()" style="width: auto;">
                  <option value="">Todos os status</option>
                  <option value="1" <?=($statusFilter === '1') ? 'selected' : ''?>>Ativos</option>
                  <option value="0" <?=($statusFilter === '0') ? 'selected' : ''?>>Inativos</option>
                </select>
                <a class="btn btn-secondary" href="index.php">Limpar</a>
              </div>
            </div>

            <?php if (count($contacts) === 0): ?>
              <div class="empty-state">
                <div class="empty-icon">
                  <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                  </svg>
                </div>
                <h3 class="empty-title">Nenhum contato encontrado</h3>
                <p class="empty-description">
                  <?php if ($search || $statusFilter !== null): ?>
                    Tente ajustar seus filtros de busca ou <a href="index.php">limpar os filtros</a>.
                  <?php else: ?>
                    Comece adicionando seu primeiro contato usando o formulário ao lado.
                  <?php endif; ?>
                </p>
              </div>
            <?php else: ?>
              <div class="table-container">
                <table class="contacts-table">
                  <thead>
                    <tr>
                      <th>Contato</th>
                      <th>Email</th>
                      <th>Telefone</th>
                      <th>Status</th>
                      <th>Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                    $avatarClasses = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-4', 'avatar-5'];
                    $avatarIndex = 0;
                    foreach ($contacts as $c): 
                      $avatarClass = $avatarClasses[$avatarIndex % count($avatarClasses)];
                      $avatarIndex++;
                    ?>
                      <tr class="contact-row">
                        <td>
                          <div class="contact-info">
                            <div class="contact-avatar <?=$avatarClass?>">
                              <?=htmlspecialchars(mb_substr($c['name'],0,1))?>
                            </div>
                            <div class="contact-details">
                              <div class="contact-name"><?=esc($c['name'])?></div>
                              <?php if ($c['notes']): ?>
                                <div class="contact-notes"><?=esc($c['notes'])?></div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </td>
                        <td><?=esc($c['email'])?></td>
                        <td><?=esc($c['phone'])?></td>
                        <td>
                          <?php if ($c['status']): ?>
                            <span class="status-badge status-active">
                              <span class="status-dot"></span>
                              Ativo
                            </span>
                          <?php else: ?>
                            <span class="status-badge status-inactive">
                              <span class="status-dot"></span>
                              Inativo
                            </span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <div class="contact-actions">
                            <a href="index.php?action=edit&id=<?=intval($c['id'])?>" class="action-btn edit-btn" title="Editar contato">
                              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                              </svg>
                            </a>
                            
                            <form method="post" action="index.php?action=delete" style="display:inline" onsubmit="return confirmDelete(event, '<?=esc(js_addslashes($c['name']))?>')">
                              <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                              <input type="hidden" name="id" value="<?=intval($c['id'])?>">
                              <button type="submit" class="action-btn delete-btn" title="Excluir contato">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <polyline points="3 6 5 6 21 6"></polyline>
                                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                  <line x1="10" y1="11" x2="10" y2="17"></line>
                                  <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach;?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <!-- Form Sidebar -->
      <aside class="form-sidebar">
        <div class="card">
          <div class="card-header">
            <h2 class="card-title"><?= $editing ? 'Editar Contato' : 'Novo Contato' ?></h2>
          </div>
          
          <div class="card-body">
            <?php if ($editing): ?>
              <form method="post" action="index.php?action=update" class="contact-form" onsubmit="return validateForm(this)">
                <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                <input type="hidden" name="id" value="<?=intval($editing['id'])?>">
                
                <div class="form-group">
                  <label class="form-label" for="name">Nome *</label>
                  <input class="form-input" type="text" id="name" name="name" required value="<?=esc($editing['name'])?>">
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="email">Email</label>
                  <input class="form-input" type="email" id="email" name="email" value="<?=esc($editing['email'])?>">
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="phone">Telefone</label>
                  <input class="form-input" type="text" id="phone" name="phone" value="<?=esc($editing['phone'])?>">
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="notes">Notas</label>
                  <textarea class="form-textarea" id="notes" name="notes"><?=esc($editing['notes'])?></textarea>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="status">Status</label>
                  <select class="form-select" id="status" name="status">
                    <option value="1" <?=($editing['status'] ? 'selected' : '')?>>Ativo</option>
                    <option value="0" <?=(!$editing['status'] ? 'selected' : '')?>>Inativo</option>
                  </select>
                </div>
                
                <div class="form-actions">
                  <button class="btn btn-primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                      <polyline points="17 21 17 13 7 13 7 21"></polyline>
                      <polyline points="7 3 7 8 15 8"></polyline>
                    </svg>
                    Salvar Alterações
                  </button>
                  <a class="btn btn-secondary" href="index.php">Cancelar</a>
                </div>
              </form>
            <?php else: ?>
              <form method="post" action="index.php?action=add" id="formAdd" class="contact-form" onsubmit="return validateForm(this)">
                <input type="hidden" name="csrf" value="<?=esc(csrf_token())?>">
                
                <div class="form-group">
                  <label class="form-label" for="name">Nome *</label>
                  <input class="form-input" type="text" id="name" name="name" required autofocus>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="email">Email</label>
                  <input class="form-input" type="email" id="email" name="email">
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="phone">Telefone</label>
                  <input class="form-input" type="text" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="notes">Notas</label>
                  <textarea class="form-textarea" id="notes" name="notes"></textarea>
                </div>
                
                <div class="form-group">
                  <label class="form-label" for="status">Status</label>
                  <select class="form-select" id="status" name="status">
                    <option value="1" selected>Ativo</option>
                    <option value="0">Inativo</option>
                  </select>
                </div>
                
                <div class="form-actions">
                  <button class="btn btn-primary" type="submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <line x1="12" y1="5" x2="12" y2="19"></line>
                      <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Adicionar Contato
                  </button>
                  <a class="btn btn-secondary" href="index.php">Limpar</a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </aside>
    </main>

    <!-- Footer -->
    <footer class="app-footer">
      <p>© <?=date('Y')?> Agenda Elegante — Sistema de gerenciamento de contatos</p>
    </footer>
  </div>

  <script>
    // Confirmação de exclusão
    function confirmDelete(e, name) {
      var ok = confirm('Tem certeza que deseja excluir o contato "' + name + '"? Esta ação não pode ser desfeita.');
      if (!ok) e.preventDefault();
      return ok;
    }

    // Validação de formulário
    function validateForm(form) {
      var name = form.querySelector('[name="name"]');
      if (!name || !name.value.trim()) {
        alert('Por favor, preencha o nome do contato.');
        name && name.focus();
        return false;
      }
      return true;
    }

    // Alternância de tema
    (function(){
      var body = document.body;
      var btn = document.getElementById('themeToggle');
      var icon = document.getElementById('iconTheme');

      function setTheme(t){
        body.setAttribute('data-theme', t);
        localStorage.setItem('aa_theme', t);
        
        // Atualiza ícone
        if(t === 'dark') {
          icon.innerHTML = '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>';
        } else {
          icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
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

    // Auto-oculta mensagens flash após 5 segundos
    document.addEventListener('DOMContentLoaded', function() {
      var flashMessages = document.querySelectorAll('.flash-message');
      flashMessages.forEach(function(msg) {
        setTimeout(function() {
          msg.style.opacity = '0';
          msg.style.transform = 'translateX(100%)';
          setTimeout(function() {
            if (msg.parentNode) {
              msg.parentNode.removeChild(msg);
            }
          }, 300);
        }, 5000);
      });
    });
  </script>
</body>
</html>