<?php
$page_title = 'Receitas';
require_once __DIR__ . '/../components/header_afp.php';

$db   = new Database();
$conn = $db->connect();
$msg  = ''; $tipo = '';

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->prepare("DELETE FROM receita WHERE id_receita = :id")->execute([':id' => (int)$_GET['del']]);
    header('Location: ' . BASE_URL . '/pages/receitas.php?ok=Receita+exclu%C3%ADda'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_receita'] ?? null;
    $d  = [
        ':cli'   => (int)$_POST['id_cli'],
        ':func'  => (int)$_SESSION['id_func'],
        ':med'   => sanitize($_POST['medico']),
        ':crm'   => sanitize($_POST['crm_medico']),
        ':emiss' => $_POST['data_emissao'],
        ':val'   => $_POST['data_validade'] ?: null,
        ':desc'  => sanitize($_POST['descricao'] ?? ''),
        ':st'    => sanitize($_POST['status'] ?? 'Válida'),
    ];
    if ($id) {
        $conn->prepare("UPDATE receita SET id_cli=:cli,id_func=:func,medico=:med,crm_medico=:crm,
                        data_emissao=:emiss,data_validade=:val,descricao=:desc,status=:st
                        WHERE id_receita=:id")->execute($d + [':id' => (int)$id]);
        header('Location: ' . BASE_URL . '/pages/receitas.php?ok=Receita+atualizada'); exit;
    } else {
        $conn->prepare("INSERT INTO receita (id_cli,id_func,medico,crm_medico,data_emissao,data_validade,descricao,status)
                        VALUES (:cli,:func,:med,:crm,:emiss,:val,:desc,:st)")->execute($d);
        header('Location: ' . BASE_URL . '/pages/receitas.php?ok=Receita+cadastrada'); exit;
    }
}

$conn->exec("UPDATE receita SET status='Vencida' WHERE data_validade < CURDATE() AND status='Válida'");

$clientes = $conn->query("SELECT id_cli, nome_cli FROM cliente ORDER BY nome_cli")->fetchAll();

$filtro_st = sanitize($_GET['st'] ?? '');
if ($filtro_st) {
    $stmt = $conn->prepare("SELECT r.*, c.nome_cli FROM receita r JOIN cliente c ON r.id_cli = c.id_cli
                            WHERE r.status = :st ORDER BY r.data_emissao DESC");
    $stmt->execute([':st' => $filtro_st]);
} else {
    $stmt = $conn->query("SELECT r.*, c.nome_cli FROM receita r JOIN cliente c ON r.id_cli = c.id_cli
                          ORDER BY r.data_emissao DESC");
}
$receitas = $stmt->fetchAll();

$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $e = $conn->prepare("SELECT * FROM receita WHERE id_receita = :id");
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
    <select name="st" class="form-select form-select-sm" style="width:180px">
      <option value="">Todos os status</option>
      <?php foreach (['Válida','Vencida','Cancelada'] as $s): ?>
      <option value="<?= $s ?>" <?= $filtro_st == $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
    <a href="receitas.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
  </form>
  <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalRec">
    <i class="bi bi-file-medical me-1"></i>Nova Receita
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>#</th><th>Paciente</th><th>Médico</th><th>Emissão</th><th>Validade</th><th>Status</th><th>Descrição</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($receitas as $r):
          $sc = ['Válida'=>'success','Vencida'=>'danger','Cancelada'=>'secondary'][$r['status']] ?? 'secondary'; ?>
          <tr>
            <td class="text-muted"><?= $r['id_receita'] ?></td>
            <td><?= sanitize($r['nome_cli']) ?></td>
            <td style="font-size:12px">
              <?= sanitize($r['medico']) ?><br>
              <span class="text-muted"><?= sanitize($r['crm_medico']) ?></span>
            </td>
            <td style="font-size:12px"><?= formatDataSimples($r['data_emissao']) ?></td>
            <td style="font-size:12px"><?= $r['data_validade'] ? formatDataSimples($r['data_validade']) : '-' ?></td>
            <td><span class="badge bg-<?= $sc ?>"><?= $r['status'] ?></span></td>
            <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= sanitize($r['descricao']) ?>
            </td>
            <td>
              <a href="?edit=<?= $r['id_receita'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?del=<?= $r['id_receita'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1"
                 onclick="return confirm('Excluir esta receita?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($receitas)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma receita encontrada.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRec" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--green);color:white">
        <h5 class="modal-title"><i class="bi bi-file-medical me-2"></i><?= $edit ? 'Editar' : 'Nova' ?> Receita</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_receita" value="<?= $edit['id_receita'] ?? '' ?>">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Paciente *</label>
              <select name="id_cli" class="form-select" required>
                <option value="">Selecione...</option>
                <?php foreach ($clientes as $c): ?>
                <option value="<?= $c['id_cli'] ?>" <?= ($edit['id_cli'] ?? '') == $c['id_cli'] ? 'selected' : '' ?>>
                  <?= sanitize($c['nome_cli']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['Válida','Vencida','Cancelada'] as $s): ?>
                <option value="<?= $s ?>" <?= ($edit['status'] ?? 'Válida') == $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Médico</label>
              <input type="text" name="medico" class="form-control" value="<?= $edit['medico'] ?? '' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">CRM</label>
              <input type="text" name="crm_medico" class="form-control" value="<?= $edit['crm_medico'] ?? '' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Data Emissão *</label>
              <input type="date" name="data_emissao" class="form-control" required value="<?= $edit['data_emissao'] ?? date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Data Validade</label>
              <input type="date" name="data_validade" class="form-control" value="<?= $edit['data_validade'] ?? '' ?>">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Medicamentos / Descrição</label>
              <textarea name="descricao" class="form-control" rows="3"><?= $edit['descricao'] ?? '' ?></textarea>
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
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalRec')).show())</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../components/footer_afp.php'; ?>
