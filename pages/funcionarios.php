<?php
$page_title = 'Funcionários';
require_once 'header_afp.php';
if (!in_array($_SESSION['cargo'] ?? '', ['Gerente', 'Administrador'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-lock me-2"></i>Acesso restrito.</div>';
    require_once 'footer_afp.php'; exit;
}

$db   = new Database();
$conn = $db->connect();
$msg  = ''; $tipo = '';

if (isset($_GET['del']) && is_numeric($_GET['del']) && $_GET['del'] != $_SESSION['id_func']) {
    $conn->prepare("UPDATE funcionario SET ativo = FALSE WHERE id_func = :id")->execute([':id' => $_GET['del']]);
    header('Location: funcionarios.php?ok=Funcion%C3%A1rio+desativado'); exit;
}

if (isset($_GET['reativar']) && is_numeric($_GET['reativar']) && $_GET['reativar'] != $_SESSION['id_func']) {
    $conn->prepare("UPDATE funcionario SET ativo = TRUE WHERE id_func = :id")->execute([':id' => $_GET['reativar']]);
    header('Location: funcionarios.php?ok=Funcion%C3%A1rio+reativado'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id_func'] ?? null;
    $nome = sanitize($_POST['nome_func']);
    $cpf  = sanitize($_POST['cpf_func']);
    $email= sanitize($_POST['email_func']);
    $tel  = sanitize($_POST['telefone']);
    $cargo= sanitize($_POST['cargo']);
    $senha= $_POST['senha'] ?? '';

    if ($id) {
        $d = [':nome'=>$nome,':cpf'=>$cpf,':email'=>$email,':tel'=>$tel,':cargo'=>$cargo,':id'=>$id];
        if ($senha) { $d[':hash'] = password_hash($senha, PASSWORD_DEFAULT); $conn->prepare("UPDATE funcionario SET nome_func=:nome,cpf_func=:cpf,email_func=:email,telefone=:tel,cargo=:cargo,senha_hash=:hash WHERE id_func=:id")->execute($d); }
        else { $conn->prepare("UPDATE funcionario SET nome_func=:nome,cpf_func=:cpf,email_func=:email,telefone=:tel,cargo=:cargo WHERE id_func=:id")->execute($d); }
        header('Location: funcionarios.php?ok=Funcion%C3%A1rio+atualizado'); exit;
    } else {
        if (!$senha) { header('Location: funcionarios.php?erro=Informe+uma+senha'); exit; }
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $conn->prepare("INSERT INTO funcionario (nome_func,cpf_func,email_func,telefone,cargo,senha_hash) VALUES (:nome,:cpf,:email,:tel,:cargo,:hash)")
             ->execute([':nome'=>$nome,':cpf'=>$cpf,':email'=>$email,':tel'=>$tel,':cargo'=>$cargo,':hash'=>$hash]);
        header('Location: funcionarios.php?ok=Funcion%C3%A1rio+cadastrado'); exit;
    }
}

$funcs = $conn->query("SELECT * FROM funcionario ORDER BY nome_func")->fetchAll(PDO::FETCH_ASSOC);

$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $e = $conn->prepare("SELECT * FROM funcionario WHERE id_func = :id");
    $e->execute([':id' => $_GET['edit']]);
    $edit = $e->fetch(PDO::FETCH_ASSOC);
}
?>

<?php
$msg = ''; $tipo = '';
if (!empty($_GET['ok']))   { $msg = htmlspecialchars($_GET['ok']);   $tipo = 'success'; }
if (!empty($_GET['erro'])) { $msg = htmlspecialchars($_GET['erro']); $tipo = 'danger';  }
?>
<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?> alert-dismissible" style="border-radius:10px">
  <?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-end mb-3">
  <a href="funcionarios.php#novo" class="btn btn-sm btn-success" id="btnNovo">
    <i class="bi bi-person-plus me-1"></i>Novo Funcionário
  </a>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Nome</th><th>Cargo</th><th>E-mail</th><th>Telefone</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($funcs as $f): ?>
          <tr>
            <td class="text-muted"><?= $f['id_func'] ?></td>
            <td><strong><?= sanitize($f['nome_func']) ?></strong></td>
            <td><span class="badge bg-secondary"><?= $f['cargo'] ?></span></td>
            <td style="font-size:12px"><?= sanitize($f['email_func']) ?></td>
            <td style="font-size:12px"><?= $f['telefone'] ?: '-' ?></td>
            <td><?= $f['ativo'] ? '<span class="badge bg-success">Ativo</span>' : '<span class="badge bg-secondary">Inativo</span>' ?></td>
            <td>
              <a href="?edit=<?= $f['id_func'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <?php if ($f['id_func'] != $_SESSION['id_func']): ?>
                <?php if ($f['ativo']): ?>
                <a href="?del=<?= $f['id_func'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1" onclick="return confirm('Desativar este funcionário?')"><i class="bi bi-person-dash"></i> Desativar</a>
                <?php else: ?>
                <a href="?reativar=<?= $f['id_func'] ?>" class="btn btn-sm btn-outline-success py-0 px-2 ms-1" onclick="return confirm('Reativar este funcionário?')"><i class="bi bi-person-check"></i> Reativar</a>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalFunc" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--green);color:white">
        <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Funcionário</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_func" value="<?= $edit['id_func'] ?? '' ?>">
          <div class="row g-3">
            <div class="col-12"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome_func" class="form-control" required value="<?= $edit['nome_func'] ?? '' ?>"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">CPF</label><input type="text" name="cpf_func" class="form-control" value="<?= $edit['cpf_func'] ?? '' ?>"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Cargo</label>
              <select name="cargo" class="form-select">
                <?php foreach (['Farmacêutico','Atendente','Gerente','Auxiliar','Administrador'] as $c): ?>
                <option value="<?= $c ?>" <?= ($edit['cargo'] ?? '') == $c ? 'selected' : '' ?>><?= $c ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-7"><label class="form-label fw-semibold">E-mail *</label><input type="email" name="email_func" class="form-control" required value="<?= $edit['email_func'] ?? '' ?>"></div>
            <div class="col-md-5"><label class="form-label fw-semibold">Telefone</label><input type="text" name="telefone" class="form-control" value="<?= $edit['telefone'] ?? '' ?>"></div>
            <div class="col-12">
              <label class="form-label fw-semibold">Senha <?= $edit ? '(deixe em branco para não alterar)' : '*' ?></label>
              <input type="password" name="senha" class="form-control" <?= $edit ? '' : 'required' ?>>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php if ($edit): ?><script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalFunc')).show())</script><?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('btnNovo').addEventListener('click', function(e) {
    e.preventDefault();
    history.replaceState(null, '', 'funcionarios.php');
    const form = document.querySelector('#modalFunc form');
    form.reset();
    form.querySelector('[name="id_func"]').value = '';
    document.querySelector('#modalFunc .modal-title').innerHTML = '<i class="bi bi-person-badge me-2"></i>Novo Funcionário';
    document.querySelector('#modalFunc [name="senha"]').required = true;
    const senhaLabel = document.querySelector('#modalFunc [name="senha"]').previousElementSibling;
    senhaLabel.textContent = 'Senha *';
    new bootstrap.Modal(document.getElementById('modalFunc')).show();
  });
});
</script>
<?php require_once 'footer_afp.php'; ?>