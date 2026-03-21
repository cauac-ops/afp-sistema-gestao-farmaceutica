<?php
$page_title = 'Histórico de Vendas';
require_once 'header_afp.php';

$db   = new Database();
$conn = $db->connect();

$data_inicio = sanitize($_GET['di'] ?? date('Y-m-01'));
$data_fim    = sanitize($_GET['df'] ?? date('Y-m-d'));
$busca       = sanitize($_GET['busca'] ?? '');

// MySQL: usa LIKE ao invés de ILIKE
$where  = "WHERE DATE(v.data_venda) BETWEEN :di AND :df";
$params = [':di' => $data_inicio, ':df' => $data_fim];
if ($busca) {
    $where .= " AND c.nome_cli LIKE :b";
    $params[':b'] = "%$busca%";
}

$stmt = $conn->prepare("
    SELECT v.id_venda, v.data_venda, v.valor_total, v.desconto, v.forma_pagamento,
           v.status, v.observacoes, c.nome_cli, f.nome_func
    FROM venda v
    JOIN cliente c    ON v.id_cli  = c.id_cli
    JOIN funcionario f ON v.id_func = f.id_func
    $where
    ORDER BY v.data_venda DESC
");
$stmt->execute($params);
$vendas = $stmt->fetchAll();

$total_qtd = count($vendas);
$total_val = array_sum(array_column($vendas, 'valor_total'));
?>

<div class="row g-2 mb-3">
  <div class="col-md-3">
    <div class="stat-card text-white" style="background:linear-gradient(135deg,#1a7a4a,#25a96a)">
      <div class="icon-bg"><i class="bi bi-receipt"></i></div>
      <div class="label">Total de Vendas</div>
      <div class="value"><?= $total_qtd ?></div>
      <div class="sub">no período selecionado</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-white" style="background:linear-gradient(135deg,#0369a1,#0ea5e9)">
      <div class="icon-bg"><i class="bi bi-cash-stack"></i></div>
      <div class="label">Receita Total</div>
      <div class="value" style="font-size:18px"><?= formatMoeda($total_val) ?></div>
      <div class="sub">valor acumulado</div>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body py-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
      <div>
        <label class="form-label mb-1 fw-semibold" style="font-size:12px">Data Início</label>
        <input type="date" name="di" class="form-control form-control-sm" value="<?= $data_inicio ?>">
      </div>
      <div>
        <label class="form-label mb-1 fw-semibold" style="font-size:12px">Data Fim</label>
        <input type="date" name="df" class="form-control form-control-sm" value="<?= $data_fim ?>">
      </div>
      <div>
        <label class="form-label mb-1 fw-semibold" style="font-size:12px">Cliente</label>
        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar cliente..." value="<?= $busca ?>" style="width:200px">
      </div>
      <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
      <a href="historico_vendas.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>#</th><th>Data/Hora</th><th>Cliente</th><th>Funcionário</th>
            <th>Pagamento</th><th>Desconto</th><th>Total</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($vendas as $v): ?>
          <tr>
            <td class="text-muted">#<?= $v['id_venda'] ?></td>
            <td style="font-size:12px"><?= formatData($v['data_venda']) ?></td>
            <td><?= sanitize($v['nome_cli']) ?></td>
            <td style="font-size:12px"><?= sanitize($v['nome_func']) ?></td>
            <td><span class="badge bg-secondary"><?= $v['forma_pagamento'] ?></span></td>
            <td style="font-size:12px;color:#dc2626"><?= $v['desconto'] > 0 ? '- ' . formatMoeda($v['desconto']) : '-' ?></td>
            <td class="fw-bold" style="color:var(--green)"><?= formatMoeda($v['valor_total']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-info py-0 px-2"
                      onclick="verItens(<?= $v['id_venda'] ?>)"
                      data-bs-toggle="modal" data-bs-target="#modalItens">
                <i class="bi bi-eye"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($vendas)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma venda encontrada no período.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Itens -->
<div class="modal fade" id="modalItens" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--green);color:white">
        <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Itens da Venda</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="itensContent">
        <div class="text-center py-3"><div class="spinner-border text-success"></div></div>
      </div>
    </div>
  </div>
</div>

<script>
function verItens(id) {
  document.getElementById('itensContent').innerHTML =
    '<div class="text-center py-3"><div class="spinner-border text-success"></div></div>';
  fetch('ajax_itens.php?id=' + id)
    .then(r => r.text())
    .then(html => document.getElementById('itensContent').innerHTML = html)
    .catch(() => document.getElementById('itensContent').innerHTML =
      '<p class="text-danger"><i class="bi bi-exclamation-circle me-2"></i>Erro ao carregar itens.</p>');
}
</script>

<?php require_once 'footer_afp.php'; ?>
