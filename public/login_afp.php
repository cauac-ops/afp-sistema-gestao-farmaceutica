<?php
session_start();
require_once 'config.php';

if (isset($_SESSION['id_func'])) {
    header('Location: dashboard.php');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    
    if ($nome && $senha) {
        try {
            $db   = new Database();
            $conn = $db->connect();
            
            
            $sql  = "SELECT * FROM funcionario WHERE (nome_func = :n OR email_func = :n) AND ativo = 1 LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':n' => $nome]);
            $func = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($func && password_verify($senha, $func['senha_hash'])) {
                $_SESSION['id_func']   = $func['id_func'];
                $_SESSION['nome_func'] = $func['nome_func'];
                $_SESSION['cargo']     = $func['cargo'];
                header('Location: dashboard.php');
                exit;
            } else {
                $erro = 'Usuário ou senha inválidos.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro de conexão com o banco de dados.';
        }
    } else {
        $erro = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AFP – Login</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet"/>
  <style>
    :root {
      --green:   #1a7a4a;
      --green2:  #25a96a;
      --mint:    #e8f7f0;
      --dark:    #0f1f18;
      --gray:    #6b7280;
      --light:   #f4fbf7;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      min-height: 100vh;
      display: flex;
      background: var(--dark);
      overflow: hidden;
    }

    .left-panel {
      width: 55%;
      background: linear-gradient(135deg, #0d2b1e 0%, #1a5c38 50%, #0f3d28 100%);
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px;
      position: relative;
      overflow: hidden;
    }
    .left-panel::before {
      content: '';
      position: absolute; width: 600px; height: 600px; border-radius: 50%;
      background: rgba(37,169,106,0.12); top: -200px; right: -200px; pointer-events: none;
    }
    .brand-badge {
      display: inline-flex; align-items: center; gap: 12px;
      background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
      border-radius: 50px; padding: 8px 18px; width: fit-content; margin-bottom: 40px;
    }
    .brand-badge .dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: var(--green2); animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:.5; transform:scale(1.4); } }
    .brand-badge span { color: rgba(255,255,255,0.7); font-size: 13px; letter-spacing: 1px; text-transform: uppercase; }
    .left-title { font-family: 'Playfair Display', serif; font-size: 52px; line-height: 1.1; color: #fff; margin-bottom: 20px; }
    .left-title em { color: var(--green2); font-style: normal; }
    .left-sub { color: rgba(255,255,255,0.55); font-size: 16px; line-height: 1.7; max-width: 380px; margin-bottom: 48px; }
    .feature-list { display: flex; flex-direction: column; gap: 14px; }
    .feature-item { display: flex; align-items: center; gap: 14px; color: rgba(255,255,255,0.75); font-size: 14px; }
    .feature-icon {
      width: 36px; height: 36px; border-radius: 10px;
      background: rgba(37,169,106,0.2); display: flex; align-items: center; justify-content: center;
      color: var(--green2); flex-shrink: 0;
    }

    .right-panel {
      width: 45%; background: #fff; display: flex; align-items: center; justify-content: center; padding: 60px 50px;
    }
    .login-box { width: 100%; max-width: 380px; }
    .login-logo { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; }
    .logo-mark {
      width: 44px; height: 44px; background: var(--green); border-radius: 12px;
      display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;
    }
    .login-logo-text { font-size: 22px; font-weight: 700; color: var(--dark); }
    .login-logo-text span { color: var(--green2); }
    h2 { font-size: 26px; font-weight: 700; color: var(--dark); margin-bottom: 6px; }
    .login-sub { color: var(--gray); font-size: 14px; margin-bottom: 32px; }
    .form-group { margin-bottom: 20px; }
    label { display: block; font-size: 13px; font-weight: 600; color: var(--dark); margin-bottom: 7px; }
    .input-wrap { position: relative; }
    .input-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 16px; }
    input[type=text], input[type=password] {
      width: 100%; padding: 12px 14px 12px 42px; border: 1.5px solid #e5e7eb;
      border-radius: 10px; font-family: 'DM Sans', sans-serif; font-size: 14px;
      color: var(--dark); transition: border-color .2s; outline: none; background: var(--light);
    }
    input:focus { border-color: var(--green2); background: #fff; }
    .toggle-pass {
      position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
      background: none; border: none; cursor: pointer; color: #9ca3af;
    }
    .btn-login {
      width: 100%; padding: 13px; background: var(--green); color: white; border: none;
      border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-login:hover { background: var(--green2); }
    .alert-error {
      background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
      border-radius: 8px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px;
      display: flex; align-items: center; gap: 8px;
    }
    @media (max-width: 768px) { .left-panel { display: none; } .right-panel { width: 100%; } }
  </style>
</head>
<body>

<div class="left-panel">
  <div class="brand-badge"><div class="dot"></div><span>Sistema AFP v1.0</span></div>
  <div class="left-title">Agenda<br>Farmacêutica de<br><em>Planejamento</em></div>
  <p class="left-sub">Gerencie sua farmácia de forma inteligente — vendas, estoque, agendamentos e relatórios em um só lugar.</p>
  <div class="feature-list">
    <div class="feature-item"><div class="feature-icon"><i class="bi bi-cart-check"></i></div>Controle completo de vendas e caixa</div>
    <div class="feature-item"><div class="feature-icon"><i class="bi bi-box-seam"></i></div>Gestão de estoque com alertas</div>
    <div class="feature-item"><div class="feature-icon"><i class="bi bi-calendar-check"></i></div>Agendamentos de serviços clínicos</div>
    <div class="feature-item"><div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>Relatórios e estatísticas em tempo real</div>
  </div>
</div>

<div class="right-panel">
  <div class="login-box">
    <div class="login-logo">
      <div class="logo-mark"><i class="bi bi-capsule"></i></div>
      <div class="login-logo-text">A<span>F</span>P</div>
    </div>
    <h2>Bem-vindo de volta</h2>
    <p class="login-sub">Acesse sua conta para continuar</p>

    <?php if ($erro): ?>
    <div class="alert-error"><i class="bi bi-exclamation-circle"></i><?= $erro ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Nome ou E-mail</label>
        <div class="input-wrap">
          <i class="bi bi-person"></i>
          <input type="text" name="nome" placeholder="Seu nome ou e-mail" required value="<?= isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : '' ?>"/>
        </div>
      </div>
      <div class="form-group">
        <label>Senha</label>
        <div class="input-wrap">
          <i class="bi bi-lock"></i>
          <input type="password" name="senha" id="senhaInput" placeholder="Sua senha" required/>
          <button type="button" class="toggle-pass" onclick="toggleSenha()"><i class="bi bi-eye" id="eyeIcon"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-login"><i class="bi bi-arrow-right-circle"></i> Entrar no Sistema</button>
    </form>
    
    <div class="mt-4 text-center">
        <small class="text-muted">Acesso restrito a funcionários autorizados.</small>
    </div>
  </div>
</div>

<script>
function toggleSenha() {
  const inp = document.getElementById('senhaInput');
  const ico = document.getElementById('eyeIcon');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
}
</script>
</body>
</html>
