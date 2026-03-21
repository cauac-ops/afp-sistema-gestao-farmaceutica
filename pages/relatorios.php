<?php
$page_title = 'Relatórios';
require_once 'components/header_afp.php';
if (!in_array($_SESSION['cargo'] ?? '', ['Gerente', 'Administrador', 'Farmacêutico'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-lock me-2"></i>Acesso restrito.</div>';
    require_once 'components/footer_afp.php'; exit;
}

$db   = new Database();
$conn = $db->connect();

$data_inicio = sanitize($_GET['di'] ?? date('Y-m-01'));
$data_fim    = sanitize($_GET['df'] ?? date('Y-m-d'));
$tipo        = sanitize($_GET['tipo'] ?? 'vendas');


if ($tipo === 'vendas') {
    $stmt = $conn->prepare("
        SELECT DATE(data_venda) as dia, COUNT(*) as qtd, SUM(valor_total) as total, forma_pagamento
        FROM venda WHERE DATE(data_venda) BETWEEN :di AND :df
        GROUP BY DATE(data_venda), forma_pagamento ORDER BY dia DESC
    ");
    $stmt->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $dados = $stmt->fetchAll();

    $rs = $conn->prepare("SELECT COUNT(*) as qtd, COALESCE(SUM(valor_total),0) as total,
                           COALESCE(AVG(valor_total),0) as media
                          FROM venda WHERE DATE(data_venda) BETWEEN :di AND :df");
    $rs->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $resumo = $rs->fetch();

    $sp = $conn->prepare("SELECT forma_pagamento, COUNT(*) as qtd, SUM(valor_total) as total
                          FROM venda WHERE DATE(data_venda) BETWEEN :di AND :df
                          GROUP BY forma_pagamento ORDER BY total DESC");
    $sp->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $por_pagamento = $sp->fetchAll();

    $ev = $conn->prepare("SELECT DATE(data_venda) as dia, SUM(valor_total) as total
                          FROM venda WHERE DATE(data_venda) BETWEEN :di AND :df
                          GROUP BY DATE(data_venda) ORDER BY dia");
    $ev->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $evolucao     = $ev->fetchAll();
    $chart_labels = array_map(fn($r) => date('d/m', strtotime($r['dia'])), $evolucao);
    $chart_vals   = array_map(fn($r) => (float)$r['total'], $evolucao);
}


if ($tipo === 'produtos') {
    $stmt = $conn->prepare("
        SELECT p.nome_prod, p.cod_bar_prod, p.estoque_atual, p.estoque_minimo, p.preco_venda,
               COALESCE(SUM(pv.quantidade),0) as vendido,
               COALESCE(SUM(pv.subtotal),0)   as receita
        FROM produto p
        LEFT JOIN produto_venda pv ON p.id_prod = pv.id_prod
        LEFT JOIN venda v ON pv.id_venda = v.id_venda AND DATE(v.data_venda) BETWEEN :di AND :df
        GROUP BY p.id_prod
        ORDER BY receita DESC
    ");
    $stmt->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $dados = $stmt->fetchAll();
}


if ($tipo === 'clientes') {
    $stmt = $conn->prepare("
        SELECT c.nome_cli, c.cpf_cli, c.telefone_cli,
               COUNT(v.id_venda)           as compras,
               COALESCE(SUM(v.valor_total),0) as gasto,
               MAX(v.data_venda)           as ultima
        FROM cliente c
        LEFT JOIN venda v ON c.id_cli = v.id_cli AND DATE(v.data_venda) BETWEEN :di AND :df
        GROUP BY c.id_cli
        HAVING COUNT(v.id_venda) > 0
        ORDER BY gasto DESC
    ");
    $stmt->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $dados = $stmt->fetchAll();
}


if ($tipo === 'agendamentos') {
    $stmt = $conn->prepare("
        SELECT a.tipo_servico, a.status, a.data_agenda, a.valor_servico, c.nome_cli
        FROM agendamento a JOIN cliente c ON a.id_cli = c.id_cli
        WHERE DATE(a.data_agenda) BETWEEN :di AND :df ORDER BY a.data_agenda DESC
    ");
    $stmt->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $dados = $stmt->fetchAll();

    $s2 = $conn->prepare("SELECT status, COUNT(*) as qtd, COALESCE(SUM(valor_servico),0) as total
                          FROM agendamento WHERE DATE(data_agenda) BETWEEN :di AND :df GROUP BY status");
    $s2->execute([':di' => $data_inicio, ':df' => $data_fim]);
    $por_status = $s2->fetchAll();
}
?>

<div class="card mb-4">
  <div class="card-body py-2">
    <form method="GET" class="d-flex gap-2 flex-wrap align-items-end">
      <div>
        <label class="form-label mb-1 fw-semibold" style="font-size:12px">Relatório</label>
        <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php foreach (['vendas'=>'Vendas','produtos'=>'Produtos','clientes'=>'Clientes','agendamentos'=>'Agendamentos'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $tipo == $v ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label mb-1 fw-semibold" style="font-size:12px">Data Início</label>
        <input type="date" name="di" class="form-control form-control-sm" value="<?= $data_inicio ?>">
      </div>
      <div>
        <label class="form-label mb-1 fw-semibold" style="font-size:12px">Data Fim</label>
        <input type="date" name="df" class="form-control form-control-sm" value="<?= $data_fim ?>">
      </div>
      <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Gerar</button>
    </form>
  </div>
</div>

<?php if ($tipo === 'vendas'): ?>
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card text-white" style="background:linear-gradient(135deg,#1a7a4a,#25a96a)">
      <div class="label">Total de Vendas</div><div class="value"><?= $resumo['qtd'] ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-white" style="background:linear-gradient(135deg,#0369a1,#0ea5e9)">
      <div class="label">Receita Total</div><div class="value" style="font-size:20px"><?= formatMoeda($resumo['total']) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-white" style="background:linear-gradient(135deg,#7c3aed,#a855f7)">
      <div class="label">Ticket Médio</div><div class="value" style="font-size:20px"><?= formatMoeda($resumo['media']) ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-8">
    <div class="card h-100">
      <div class="card-header" style="background:white;border-bottom:1px solid var(--border)">
        <h6 class="mb-0 fw-bold"><i class="bi bi-graph-up me-2 text-success"></i>Evolução de Vendas</h6>
      </div>
      <div class="card-body"><canvas id="chartEvolucao" height="100"></canvas></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-header" style="background:white;border-bottom:1px solid var(--border)">
        <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Por Pagamento</h6>
      </div>
      <div class="card-body"><canvas id="chartPgto"></canvas></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header" style="background:white;border-bottom:1px solid var(--border)">
    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i>Detalhamento</h6>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>Data</th><th>Qtd</th><th>Pagamento</th><th>Total</th></tr></thead>
        <tbody>
        <?php foreach ($dados as $d): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($d['dia'])) ?></td>
            <td><?= $d['qtd'] ?></td>
            <td><span class="badge bg-secondary"><?= $d['forma_pagamento'] ?></span></td>
            <td class="fw-bold" style="color:var(--green)"><?= formatMoeda($d['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartEvolucao'),{
  type:'line',
  data:{labels:<?= json_encode($chart_labels) ?>,datasets:[{label:'Receita',data:<?= json_encode($chart_vals) ?>,borderColor:'#25a96a',backgroundColor:'rgba(37,169,106,.1)',fill:true,tension:.4,pointRadius:4}]},
  options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{ticks:{callback:v=>'R$'+v.toLocaleString('pt-BR')},grid:{color:'rgba(0,0,0,.05)'}},x:{grid:{display:false}}}}
});
new Chart(document.getElementById('chartPgto'),{
  type:'doughnut',
  data:{labels:<?= json_encode(array_column($por_pagamento,'forma_pagamento')) ?>,datasets:[{data:<?= json_encode(array_column($por_pagamento,'total')) ?>,backgroundColor:['#25a96a','#0ea5e9','#a855f7','#f59e0b']}]},
  options:{responsive:true,plugins:{legend:{position:'bottom'}}}
});
</script>

<?php elseif ($tipo === 'produtos'): ?>
<div class="card">
  <div class="card-header" style="background:white;border-bottom:1px solid var(--border)"><h6 class="mb-0 fw-bold"><i class="bi bi-box-seam me-2"></i>Desempenho de Produtos</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>Produto</th><th>Código</th><th>Estoque</th><th>Qtd. Vendida</th><th>Receita Gerada</th></tr></thead>
        <tbody>
        <?php foreach ($dados as $d): ?>
          <tr>
            <td><?= sanitize($d['nome_prod']) ?></td>
            <td style="font-size:12px"><?= $d['cod_bar_prod'] ?: '-' ?></td>
            <td><span class="<?= $d['estoque_atual'] <= $d['estoque_minimo'] ? 'text-danger fw-bold' : '' ?>"><?= $d['estoque_atual'] ?></span></td>
            <td><?= $d['vendido'] ?> un.</td>
            <td class="fw-bold" style="color:var(--green)"><?= formatMoeda($d['receita']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tipo === 'clientes'): ?>
<div class="card">
  <div class="card-header" style="background:white;border-bottom:1px solid var(--border)"><h6 class="mb-0 fw-bold"><i class="bi bi-people me-2"></i>Ranking de Clientes</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>Cliente</th><th>CPF</th><th>Compras</th><th>Total Gasto</th><th>Última Compra</th></tr></thead>
        <tbody>
        <?php foreach ($dados as $d): ?>
          <tr>
            <td><?= sanitize($d['nome_cli']) ?></td>
            <td style="font-size:12px"><?= $d['cpf_cli'] ?></td>
            <td><?= $d['compras'] ?></td>
            <td class="fw-bold" style="color:var(--green)"><?= formatMoeda($d['gasto']) ?></td>
            <td style="font-size:12px"><?= formatData($d['ultima']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php elseif ($tipo === 'agendamentos'): ?>
<div class="row g-3 mb-3">
  <?php foreach ($por_status as $s):
    $col = ['Agendado'=>'primary','Concluído'=>'success','Cancelado'=>'danger','Faltou'=>'warning'][$s['status']] ?? 'secondary'; ?>
  <div class="col-md-3">
    <div class="card text-center py-2">
      <div class="card-body">
        <span class="badge bg-<?= $col ?> mb-1"><?= $s['status'] ?></span>
        <div class="fw-bold fs-4"><?= $s['qtd'] ?></div>
        <div style="font-size:12px;color:var(--gray)"><?= formatMoeda($s['total']) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead><tr><th>Data</th><th>Paciente</th><th>Serviço</th><th>Status</th><th>Valor</th></tr></thead>
        <tbody>
        <?php foreach ($dados as $d):
          $col = ['Agendado'=>'primary','Concluído'=>'success','Cancelado'=>'danger','Faltou'=>'warning'][$d['status']] ?? 'secondary'; ?>
          <tr>
            <td style="font-size:12px"><?= formatData($d['data_agenda']) ?></td>
            <td><?= sanitize($d['nome_cli']) ?></td>
            <td><?= sanitize($d['tipo_servico']) ?></td>
            <td><span class="badge bg-<?= $col ?>"><?= $d['status'] ?></span></td>
            <td><?= formatMoeda($d['valor_servico']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once 'footer_afp.php'; ?>
