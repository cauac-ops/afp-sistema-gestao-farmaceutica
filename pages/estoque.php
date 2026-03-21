<?php
$page_title = 'Gestão de Estoque';
require_once __DIR__ . '/../components/header_afp.php';

$db   = new Database();
$conn = $db->connect();
$msg  = ''; $tipo = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prod   = (int)$_POST['id_prod'];
    $tipo_mov  = sanitize($_POST['tipo_mov']);
    $qtd       = (int)$_POST['quantidade'];
    $motivo    = sanitize($_POST['motivo'] ?? '');

    if ($id_prod && $qtd > 0) {
        $op = $tipo_mov === 'Entrada' ? '+' : '-';
        $conn->prepare("UPDATE produto SET estoque_atual = estoque_atual $op :q WHERE id_prod = :id")
             ->execute([':q' => $qtd, ':id' => $id_prod]);
        $conn->prepare("INSERT INTO movimentacao_estoque (id_prod, id_func, tipo, quantidade, motivo) VALUES (:p,:f,:t,:q,:m)")
             ->execute([':p' => $id_prod, ':f' => $_SESSION['id_func'], ':t' => $tipo_mov, ':q' => $qtd, ':m' => $motivo]);
        $msg = "Movimentação registrada ($tipo_mov de $qtd unidades)."; $tipo = 'success';
    } else {
        $msg = 'Dados inválidos.'; $tipo = 'danger';
    }
}


$produtos = $conn->query("SELECT p.*, c.nome_cat FROM produto p LEFT JOIN categoria c ON p.id_cat = c.id_cat ORDER BY p.nome_prod")
                 ->fetchAll(PDO::FETCH_ASSOC);

$movs = $conn->query("SELECT m.*, p.nome_prod, f.nome_func FROM movimentacao_estoque m
                      JOIN produto p ON m.id_prod = p.id_prod
                      JOIN funcionario f ON m.id_func = f.id_func
                      ORDER BY m.data_mov DESC LIMIT 20")
             ->fetchAll(PDO::FETCH_ASSOC);

$estoque_baixo = array_filter($produtos, fn($p) => $p['estoque_atual'] <= $p['estoque_minimo']);
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?> alert-dismissible" style="border-radius:10px">
  <i class="bi bi-check-circle me-2"></i><?= $msg ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>


<?php if (count($estoque_baixo) > 0): ?>
<div class="alert" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:16px">
  <div class="d-flex align-items-center gap-2 mb-2">
    <i class="bi bi-exclamation-triangle-fill text-danger"></i>
    <strong><?= count($estoque_baixo) ?> produto(s) com estoque abaixo do mínimo:</strong>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <?php foreach ($estoque_baixo as $p): ?>
    <span class="badge bg-danger"><?= sanitize($p['nome_prod']) ?> (<?= $p['estoque_atual'] ?>/<?= $p['estoque_minimo'] ?>)</span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="row g-3">
  
  <div class="col-md-4">
    <div class="card">
      <div class="card-header" style="background:var(--green);color:white">
        <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Registrar Movimentação</h6>
      </div>
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label fw-semibold">Produto *</label>
            <select name="id_prod" class="form-select" required>
              <option value="">Selecione...</option>
              <?php foreach ($produtos as $p): ?>
              <option value="<?= $p['id_prod'] ?>"><?= sanitize($p['nome_prod']) ?> (atual: <?= $p['estoque_atual'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Tipo</label>
            <div class="d-flex gap-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="tipo_mov" value="Entrada" id="ent" checked>
                <label class="form-check-label text-success fw-semibold" for="ent"><i class="bi bi-arrow-down-circle"></i> Entrada</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="tipo_mov" value="Saída" id="sai">
                <label class="form-check-label text-danger fw-semibold" for="sai"><i class="bi bi-arrow-up-circle"></i> Saída</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Quantidade *</label>
            <input type="number" name="quantidade" class="form-control" min="1" required value="1">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Motivo</label>
            <input type="text" name="motivo" class="form-control" placeholder="Ex: Compra do fornecedor, Perda...">
          </div>
          <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle me-1"></i>Registrar</button>
        </form>
      </div>
    </div>
  </div>


  <div class="col-md-8">
    <div class="card">
      <div class="card-header" style="background:white;border-bottom:1px solid var(--border)">
        <h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2 text-success"></i>Situação do Estoque</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr><th>Produto</th><th>Categoria</th><th>Atual</th><th>Mínimo</th><th>Situação</th></tr></thead>
            <tbody>
            <?php foreach ($produtos as $p): ?>
              <?php $baixo = $p['estoque_atual'] <= $p['estoque_minimo']; ?>
              <tr <?= $baixo ? 'style="background:#fff5f5"' : '' ?>>
                <td><?= sanitize($p['nome_prod']) ?></td>
                <td style="font-size:12px"><?= $p['nome_cat'] ?? '-' ?></td>
                <td class="fw-bold <?= $baixo ? 'text-danger' : 'text-success' ?>"><?= $p['estoque_atual'] ?></td>
                <td style="font-size:12px;color:var(--gray)"><?= $p['estoque_minimo'] ?></td>
                <td>
                  <?php if ($p['estoque_atual'] == 0): ?>
                    <span class="badge bg-danger">Esgotado</span>
                  <?php elseif ($baixo): ?>
                    <span class="badge bg-warning text-dark">Baixo</span>
                  <?php else: ?>
                    <span class="badge bg-success">OK</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>


  <div class="col-12">
    <div class="card">
      <div class="card-header" style="background:white;border-bottom:1px solid var(--border)">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Histórico de Movimentações</h6>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead><tr><th>Data/Hora</th><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Motivo</th><th>Funcionário</th></tr></thead>
            <tbody>
            <?php foreach ($movs as $m): ?>
              <tr>
                <td style="font-size:12px"><?= formatData($m['data_mov']) ?></td>
                <td style="font-size:13px"><?= sanitize($m['nome_prod']) ?></td>
                <td><span class="badge <?= $m['tipo'] === 'Entrada' ? 'bg-success' : 'bg-danger' ?>"><?= $m['tipo'] ?></span></td>
                <td class="fw-bold"><?= $m['quantidade'] ?></td>
                <td style="font-size:12px"><?= sanitize($m['motivo']) ?: '-' ?></td>
                <td style="font-size:12px"><?= sanitize($m['nome_func']) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../components/footer_afp.php'; ?>
