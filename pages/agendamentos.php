<?php
$page_title = 'Agendamentos';
require_once 'header_afp.php';

$db   = new Database();
$conn = $db->connect();
$msg  = ''; $tipo = '';

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->prepare("DELETE FROM agendamento WHERE id_agenda = :id")->execute([':id' => $_GET['del']]);
    header('Location: agendamentos.php?ok=Agendamento+exclu%C3%ADdo'); exit;
}

if (isset($_GET['status']) && is_numeric($_GET['id'])) {
    $conn->prepare("UPDATE agendamento SET status = :s WHERE id_agenda = :id")
         ->execute([':s' => $_GET['status'], ':id' => $_GET['id']]);
    header('Location: agendamentos.php?ok=Status+atualizado'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_agenda'] ?? null;
    $d  = [
        ':cli'  => $_POST['id_cli'],
        ':func' => $_SESSION['id_func'],
        ':tipo' => sanitize($_POST['tipo_servico']),
        ':data' => $_POST['data_agenda'],
        ':obs'  => sanitize($_POST['observacoes'] ?? ''),
        ':val'  => (float)($_POST['valor_servico'] ?? 0),
        ':st'   => sanitize($_POST['status'] ?? 'Agendado'),
    ];
    if ($id) {
        $conn->prepare("UPDATE agendamento SET id_cli=:cli,id_func=:func,tipo_servico=:tipo,data_agenda=:data,observacoes=:obs,valor_servico=:val,status=:st WHERE id_agenda=:id")
             ->execute($d + [':id' => $id]);
        header('Location: agendamentos.php?ok=Agendamento+atualizado'); exit;
    } else {
        $conn->prepare("INSERT INTO agendamento (id_cli,id_func,tipo_servico,data_agenda,observacoes,valor_servico,status) VALUES (:cli,:func,:tipo,:data,:obs,:val,:st)")
             ->execute($d);
        header('Location: agendamentos.php?ok=Agendamento+criado'); exit;
    }
}

$clientes = $conn->query("SELECT id_cli, nome_cli FROM cliente ORDER BY nome_cli")->fetchAll(PDO::FETCH_ASSOC);

$filtro_status = sanitize($_GET['st'] ?? '');
$filtro_data   = sanitize($_GET['dt'] ?? '');
$where = "WHERE 1=1";
$params = [];
if ($filtro_status) { $where .= " AND a.status = :st"; $params[':st'] = $filtro_status; }
if ($filtro_data)   { $where .= " AND DATE(a.data_agenda) = :dt"; $params[':dt'] = $filtro_data; }

$stmt = $conn->prepare("SELECT a.*, c.nome_cli FROM agendamento a JOIN cliente c ON a.id_cli = c.id_cli $where ORDER BY a.data_agenda DESC");
$stmt->execute($params);
$agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $e = $conn->prepare("SELECT * FROM agendamento WHERE id_agenda = :id");
    $e->execute([':id' => $_GET['edit']]);
    $edit = $e->fetch(PDO::FETCH_ASSOC);
}

$statusColors = ['Agendado' => 'primary', 'Concluído' => 'success', 'Cancelado' => 'danger', 'Faltou' => 'warning'];
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
  <form class="d-flex gap-2 flex-wrap" method="GET">
    <input type="date" name="dt" class="form-control form-control-sm" value="<?= $filtro_data ?>">
    <select name="st" class="form-select form-select-sm" style="width:160px">
      <option value="">Todos status</option>
      <?php foreach (['Agendado','Concluído','Cancelado','Faltou'] as $s): ?>
      <option value="<?= $s ?>" <?= $filtro_status == $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-primary">Filtrar</button>
    <a href="agendamentos.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
  </form>
  <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalAg">
    <i class="bi bi-calendar-plus me-1"></i>Novo Agendamento
  </button>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr><th>Data/Hora</th><th>Cliente</th><th>Serviço</th><th>Valor</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($agendamentos as $a): ?>
          <tr>
            <td><?= formatData($a['data_agenda']) ?></td>
            <td><?= sanitize($a['nome_cli']) ?></td>
            <td><?= sanitize($a['tipo_servico']) ?></td>
            <td><?= formatMoeda($a['valor_servico']) ?></td>
            <td><span class="badge bg-<?= $statusColors[$a['status']] ?? 'secondary' ?>"><?= $a['status'] ?></span></td>
            <td class="d-flex gap-1">
              <?php if ($a['status'] === 'Agendado'): ?>
                <a href="?id=<?= $a['id_agenda'] ?>&status=Concluído" class="btn btn-xs btn-success py-0 px-1" style="font-size:11px" onclick="return confirm('Marcar como concluído?')"><i class="bi bi-check"></i></a>
                <a href="?id=<?= $a['id_agenda'] ?>&status=Cancelado" class="btn btn-xs btn-danger py-0 px-1" style="font-size:11px" onclick="return confirm('Cancelar?')"><i class="bi bi-x"></i></a>
              <?php endif; ?>
              <a href="?edit=<?= $a['id_agenda'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?del=<?= $a['id_agenda'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Excluir?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalAg" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--green);color:white">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Agendamento</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_agenda" value="<?= $edit['id_agenda'] ?? '' ?>">
          <div class="mb-3">
            <label class="form-label fw-semibold">Cliente *</label>
            <select name="id_cli" class="form-select" required>
              <option value="">Selecione...</option>
              <?php foreach ($clientes as $c): ?>
              <option value="<?= $c['id_cli'] ?>" <?= ($edit['id_cli'] ?? '') == $c['id_cli'] ? 'selected' : '' ?>><?= sanitize($c['nome_cli']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Tipo de Serviço *</label>
            <input type="text" name="tipo_servico" class="form-control" required value="<?= $edit['tipo_servico'] ?? '' ?>" list="servicosList">
            <datalist id="servicosList">
              <?php foreach (['Aferição de Pressão','Aplicação de Injetável','Aferição de Glicemia','Consulta Farmacêutica','Curativo','Teste de Covid','Outro'] as $s): ?>
              <option value="<?= $s ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="row g-2">
            <div class="col-md-7">
              <label class="form-label fw-semibold">Data e Hora *</label>
              <input type="datetime-local" name="data_agenda" class="form-control" required value="<?= $edit ? date('Y-m-d\TH:i', strtotime($edit['data_agenda'])) : '' ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label fw-semibold">Valor (R$)</label>
              <input type="number" step="0.01" name="valor_servico" class="form-control" value="<?= $edit['valor_servico'] ?? '0' ?>">
            </div>
          </div>
          <div class="mb-3 mt-3">
            <label class="form-label fw-semibold">Status</label>
            <select name="status" class="form-select">
              <?php foreach (['Agendado','Concluído','Cancelado','Faltou'] as $s): ?>
              <option value="<?= $s ?>" <?= ($edit['status'] ?? 'Agendado') == $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label fw-semibold">Observações</label>
            <textarea name="observacoes" class="form-control" rows="2"><?= $edit['observacoes'] ?? '' ?></textarea>
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
<?php if ($edit): ?><script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalAg')).show())</script><?php endif; ?>
<?php require_once 'footer_afp.php'; ?>