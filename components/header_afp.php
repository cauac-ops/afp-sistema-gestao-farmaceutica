<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['id_func'])) { header('Location: login_afp.php'); exit; }

// Atualiza cargo da sessão a cada request (caso tenha sido alterado)
$_db_refresh = new Database();
$_c_refresh  = $_db_refresh->connect();
$_r = $_c_refresh->prepare("SELECT cargo, nome_func FROM funcionario WHERE id_func = :id AND ativo = 1");
$_r->execute([':id' => $_SESSION['id_func']]);
$_row = $_r->fetch();
if ($_row) {
    $_SESSION['cargo']     = $_row['cargo'];
    $_SESSION['nome_func'] = $_row['nome_func'];
} else {
    session_destroy(); header('Location: login_afp.php'); exit;
}

// Detect active page
$current = basename($_SERVER['PHP_SELF']);
function isActive($page) { global $current; return $current == $page ? 'active' : ''; }

// Badges do sidebar
$db_h = new Database();
$c_h  = $db_h->connect();
$ebq  = $c_h->query("SELECT COUNT(*) FROM produto WHERE estoque_atual <= estoque_minimo")->fetchColumn();
$ag_h = $c_h->query("SELECT COUNT(*) FROM agendamento WHERE DATE(data_agenda) = CURDATE() AND status = 'Agendado'")->fetchColumn();
$rv_h = $c_h->query("SELECT COUNT(*) FROM receita WHERE data_validade < CURDATE() AND status = 'Válida'")->fetchColumn();
$initials = strtoupper(substr($_SESSION['nome_func'], 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AFP – <?= $page_title ?? 'Sistema' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    :root {
      --green:    #1a7a4a;
      --green2:   #25a96a;
      --green-lt: #e8f7f0;
      --dark:     #0f1f18;
      --sidebar:  #111d16;
      --sidebar2: #192618;
      --gray:     #6b7280;
      --border:   #e5e7eb;
      --bg:       #f7fbf9;
    }
    * { box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); margin: 0; overflow-x: hidden; }

    /* ---- SIDEBAR ---- */
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: 240px;
      background: var(--sidebar);
      display: flex; flex-direction: column;
      z-index: 100;
      transition: transform .3s;
    }
    .sidebar-brand {
      padding: 24px 20px 18px;
      display: flex; align-items: center; gap: 11px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .brand-icon {
      width: 38px; height: 38px; border-radius: 10px;
      background: var(--green); display: flex; align-items: center;
      justify-content: center; color: white; font-size: 18px; flex-shrink: 0;
    }
    .brand-name { font-size: 18px; font-weight: 700; color: white; letter-spacing: .5px; }
    .brand-name span { color: var(--green2); }
    .brand-sub { font-size: 10px; color: rgba(255,255,255,0.35); text-transform: uppercase; letter-spacing: 1px; }
    .sidebar-user {
      padding: 16px 20px;
      display: flex; align-items: center; gap: 10px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
      margin-bottom: 8px;
    }
    .user-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: var(--green2); display: flex; align-items: center;
      justify-content: center; color: white; font-size: 14px; font-weight: 600;
      flex-shrink: 0;
    }
    .user-name { font-size: 13px; font-weight: 600; color: rgba(255,255,255,.85); }
    .user-role { font-size: 11px; color: rgba(255,255,255,.4); }
    .sidebar-section { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: rgba(255,255,255,.3); padding: 12px 20px 5px; font-weight: 600; }
    .sidebar-nav { flex: 1; overflow-y: auto; padding-bottom: 16px; }
    .sidebar-nav::-webkit-scrollbar { width: 4px; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 2px; }
    .nav-link-side {
      display: flex; align-items: center; gap: 11px;
      padding: 10px 20px;
      color: rgba(255,255,255,.55);
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 500;
      border-radius: 0;
      transition: background .15s, color .15s;
      position: relative;
    }
    .nav-link-side i { font-size: 16px; width: 20px; text-align: center; }
    .nav-link-side:hover { background: rgba(255,255,255,.06); color: rgba(255,255,255,.85); }
    .nav-link-side.active { background: rgba(37,169,106,.15); color: var(--green2); }
    .nav-link-side.active::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--green2); border-radius: 0 3px 3px 0; }
    .badge-side { margin-left: auto; background: var(--green); color: white; font-size: 10px; padding: 2px 7px; border-radius: 10px; }
    .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.06); }
    .btn-logout {
      display: flex; align-items: center; gap: 9px;
      color: rgba(255,255,255,.4); font-size: 13px;
      text-decoration: none; padding: 8px 0;
      transition: color .15s;
    }
    .btn-logout:hover { color: #fc8181; }

    /* ---- MAIN ---- */
    .main-content { margin-left: 240px; min-height: 100vh; display: flex; flex-direction: column; }
    .topbar {
      background: white;
      border-bottom: 1px solid var(--border);
      padding: 14px 28px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .topbar-title { font-size: 18px; font-weight: 700; color: var(--dark); }
    .topbar-sub { font-size: 12px; color: var(--gray); }
    .topbar-right { display: flex; align-items: center; gap: 12px; }
    .topbar-date { font-size: 13px; color: var(--gray); }
    .page-body { padding: 28px; flex: 1; }

    /* ---- CARDS ---- */
    .stat-card {
      border-radius: 14px; padding: 22px;
      display: flex; flex-direction: column; gap: 4px;
      position: relative; overflow: hidden;
      border: none; box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .stat-card .icon-bg { position: absolute; right: 16px; top: 16px; font-size: 40px; opacity: .12; }
    .stat-card .label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .8px; opacity: .8; }
    .stat-card .value { font-size: 28px; font-weight: 700; line-height: 1.1; }
    .stat-card .sub   { font-size: 12px; opacity: .75; margin-top: 2px; }
    .card { border: 1px solid var(--border); border-radius: 12px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .card-header { border-radius: 12px 12px 0 0 !important; }
    .table { font-size: 13.5px; }
    .table thead th { font-size: 11px; text-transform: uppercase; letter-spacing: .7px; color: var(--gray); font-weight: 600; border-bottom: 1px solid var(--border); }
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .main-content { margin-left: 0; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="bi bi-capsule"></i></div>
    <div>
      <div class="brand-name">A<span>F</span>P</div>
      <div class="brand-sub">Farmacêutico</div>
    </div>
  </div>
  <div class="sidebar-user">
    <div class="user-avatar"><?= $initials ?></div>
    <div>
      <div class="user-name"><?= sanitize($_SESSION['nome_func']) ?></div>
      <div class="user-role"><?= sanitize($_SESSION['cargo'] ?? 'Funcionário') ?></div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Principal</div>
    <a href="dashboard.php"     class="nav-link-side <?= isActive('dashboard.php') ?>"><i class="bi bi-speedometer2"></i>Dashboard</a>
    <a href="vendas.php"        class="nav-link-side <?= isActive('vendas.php') ?>"><i class="bi bi-cart-check"></i>Nova Venda</a>
    <a href="historico_vendas.php" class="nav-link-side <?= isActive('historico_vendas.php') ?>"><i class="bi bi-receipt"></i>Histórico de Vendas</a>

    <div class="sidebar-section">Cadastros</div>
    <a href="produtos.php"      class="nav-link-side <?= isActive('produtos.php') ?>"><i class="bi bi-box-seam"></i>Produtos<?= $ebq > 0 ? "<span class='badge-side'>$ebq</span>" : '' ?></a>
    <a href="clientes.php"      class="nav-link-side <?= isActive('clientes.php') ?>"><i class="bi bi-people"></i>Clientes</a>
    <?php if (in_array($_SESSION['cargo'] ?? '', ['Gerente', 'Administrador', 'Farmacêutico'])): ?>
    <a href="funcionarios.php"  class="nav-link-side <?= isActive('funcionarios.php') ?>"><i class="bi bi-person-badge"></i>Funcionários</a>
    <?php endif; ?>

    <div class="sidebar-section">Clínico</div>
    <a href="agendamentos.php"  class="nav-link-side <?= isActive('agendamentos.php') ?>"><i class="bi bi-calendar-check"></i>Agendamentos<?= $ag_h > 0 ? "<span class='badge-side'>$ag_h</span>" : '' ?></a>
    <a href="receitas.php"      class="nav-link-side <?= isActive('receitas.php') ?>"><i class="bi bi-file-medical"></i>Receitas<?= $rv_h > 0 ? "<span class='badge-side text-warning' style='background:#b45309'>$rv_h</span>" : '' ?></a>

    <div class="sidebar-section">Análises</div>
    <?php if (in_array($_SESSION['cargo'] ?? '', ['Gerente', 'Administrador', 'Farmacêutico'])): ?>
    <a href="relatorios.php"    class="nav-link-side <?= isActive('relatorios.php') ?>"><i class="bi bi-graph-up-arrow"></i>Relatórios</a>
    <?php endif; ?>
    <a href="estoque.php"       class="nav-link-side <?= isActive('estoque.php') ?>"><i class="bi bi-clipboard2-pulse"></i>Estoque</a>
  </nav>
  <div class="sidebar-footer">
    <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i>Sair do Sistema</a>
  </div>
</nav>

<!-- MAIN -->
<div class="main-content">
  <div class="topbar">
    <div>
      <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
      <div class="topbar-sub">AFP – Agenda Farmacêutica de Planejamento</div>
    </div>
    <div class="topbar-right">
      <span class="topbar-date"><i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y – H:i') ?></span>
    </div>
  </div>
  <div class="page-body">