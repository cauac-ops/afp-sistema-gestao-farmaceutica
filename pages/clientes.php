<?php
$page_title = 'Clientes';
require_once __DIR__ . '/../components/header_afp.php';

$db   = new Database();
$conn = $db->connect();

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->prepare("DELETE FROM cliente WHERE id_cli = :id")->execute([':id' => (int)$_GET['del']]);
    header('Location: ' . BASE_URL . '/pages/clientes.php?ok=Cliente+exclu%C3%ADdo'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_cli'] ?? null;
    $d  = [
        ':nome'  => sanitize($_POST['nome_cli']),
        ':cpf'   => sanitize($_POST['cpf_cli']),
        ':email' => sanitize($_POST['email_cli']),
        ':tel'   => sanitize($_POST['telefone_cli']),
        ':nasc'  => $_POST['data_nasc'] ?: null,
        ':end'   => sanitize($_POST['endereco'] ?? ''),
        ':obs'   => sanitize($_POST['observacoes'] ?? ''),
    ];
    if ($id) {
        $conn->prepare("UPDATE cliente SET nome_cli=:nome,cpf_cli=:cpf,email_cli=:email,
                        telefone_cli=:tel,data_nasc=:nasc,endereco=:end,observacoes=:obs
                        WHERE id_cli=:id")->execute($d + [':id' => (int)$id]);
        header('Location: ' . BASE_URL . '/pages/clientes.php?ok=Cliente+atualizado'); exit;
    } else {
        $conn->prepare("INSERT INTO cliente (nome_cli,cpf_cli,email_cli,telefone_cli,data_nasc,endereco,observacoes)
                        VALUES (:nome,:cpf,:email,:tel,:nasc,:end,:obs)")->execute($d);
        header('Location: ' . BASE_URL . '/pages/clientes.php?ok=Cliente+cadastrado'); exit;
    }
}

$busca = sanitize($_GET['busca'] ?? '');
if ($busca) {
    $stmt = $conn->prepare("SELECT * FROM cliente WHERE nome_cli LIKE :b OR cpf_cli LIKE :b ORDER BY nome_cli");
    $stmt->execute([':b' => "%$busca%"]);
} else {
    $stmt = $conn->query("SELECT * FROM cliente ORDER BY nome_cli");
}
$clientes = $stmt->fetchAll();

$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $e = $conn->prepare("SELECT * FROM cliente WHERE id_cli = :id");
    $e->execute([':id' => (int)$_GET['edit']]);
    $edit = $e->fetch();
}
?>

<?php
$msg = ''; $tipo = '';
if (!empty($_GET['ok'])) { $msg = htmlspecialchars($_GET['ok']); $tipo = 'success'; }
?>
<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?> alert-dismissible" style="border-radius:10px">
  <i class="bi bi-check-circle me-2"></i><?= $msg ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <form class="d-flex gap-2" method="GET">
    <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar cliente..." value="<?= $busca ?>" style="width:240px">
    <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
    <a href="clientes.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
  </form>
  <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalCli">
    <i class="bi bi-person-plus me-1"></i>Novo Cliente
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr><th>#</th><th>Nome</th><th>CPF</th><th>Telefone</th><th>E-mail</th><th>Nasc.</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($clientes as $c): ?>
          <tr>
            <td class="text-muted"><?= $c['id_cli'] ?></td>
            <td><strong><?= sanitize($c['nome_cli']) ?></strong></td>
            <td style="font-size:12px"><?= $c['cpf_cli'] ?: '-' ?></td>
            <td style="font-size:12px"><?= $c['telefone_cli'] ?: '-' ?></td>
            <td style="font-size:12px"><?= $c['email_cli'] ?: '-' ?></td>
            <td style="font-size:12px"><?= $c['data_nasc'] ? formatDataSimples($c['data_nasc']) : '-' ?></td>
            <td>
              <a href="?edit=<?= $c['id_cli'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?del=<?= $c['id_cli'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1"
                 onclick="return confirm('Excluir este cliente?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($clientes)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum cliente encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalCli" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--green);color:white">
        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Cliente</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_cli" value="<?= $edit['id_cli'] ?? '' ?>">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Nome *</label>
              <input type="text" name="nome_cli" class="form-control" required value="<?= $edit['nome_cli'] ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">CPF</label>
              <input type="text" name="cpf_cli" class="form-control" value="<?= $edit['cpf_cli'] ?? '' ?>" placeholder="000.000.000-00">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">E-mail</label>
              <input type="email" name="email_cli" class="form-control" value="<?= $edit['email_cli'] ?? '' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Telefone</label>
              <input type="text" name="telefone_cli" class="form-control" value="<?= $edit['telefone_cli'] ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Data de Nascimento</label>
              <input type="date" name="data_nasc" class="form-control" value="<?= $edit['data_nasc'] ?? '' ?>">
            </div>
            <div class="col-md-8">
              <label class="form-label fw-semibold">Endereço</label>
              <input type="text" name="endereco" class="form-control" value="<?= $edit['endereco'] ?? '' ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Observações</label>
              <textarea name="observacoes" class="form-control" rows="2"><?= $edit['observacoes'] ?? '' ?></textarea>
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
<?php if ($edit): ?>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalCli')).show())</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../components/footer_afp.php'; ?>
