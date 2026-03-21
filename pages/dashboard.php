<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$page_title = 'Dashboard';
require_once 'header_afp.php';

$db   = new Database();
$conn = $db->connect();

$sql_dash = "
SELECT
    (SELECT COUNT(*) FROM venda WHERE DATE(data_venda) = CURDATE()) AS vendas_hoje,
    (SELECT COALESCE(SUM(valor_total), 0) FROM venda WHERE DATE(data_venda) = CURDATE()) AS receita_hoje,
    (SELECT COUNT(*) FROM produto) AS total_produtos,
    (SELECT COUNT(*) FROM cliente) AS total_clientes,
    (SELECT COUNT(*) FROM produto WHERE estoque_atual <= estoque_minimo) AS estoque_baixo,
    (SELECT COUNT(*) FROM agendamento WHERE DATE(data_agenda) = CURDATE() AND status = 'Agendado') AS agendamentos_hoje,
    (SELECT COUNT(*) FROM receita WHERE data_validade < CURDATE() AND status = 'Válida') AS receitas_vencidas
";

try {
    $d = $conn->query($sql_dash)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $d = [
        'vendas_hoje' => 0, 'receita_hoje' => 0, 'total_produtos' => 0,
        'total_clientes' => 0, 'estoque_baixo' => 0, 'agendamentos_hoje' => 0, 'receitas_vencidas' => 0
    ];
}

$dias_labels = [];
$dias_valores = [];
for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-$i days"));
    $dias_labels[] = date('d/m', strtotime($data));
    $st = $conn->prepare("SELECT COALESCE(SUM(valor_total),0) FROM venda WHERE DATE(data_venda) = :dt");
    $st->execute([':dt' => $data]);
    $dias_valores[] = (float)$st->fetchColumn();
}
?>

<div class="container-fluid">
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small fw-bold">VENDAS HOJE</div>
        <div class="fs-3 fw-bold text-success"><?= $d['vendas_hoje'] ?></div>
        <div class="text-muted" style="font-size:12px">Total: <?= formatMoeda($d['receita_hoje']) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small fw-bold">AGENDAMENTOS</div>
        <div class="fs-3 fw-bold text-primary"><?= $d['agendamentos_hoje'] ?></div>
        <div class="text-muted" style="font-size:12px">Para o dia de hoje</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small fw-bold">PRODUTOS</div>
        <div class="fs-3 fw-bold text-dark"><?= $d['total_produtos'] ?></div>
        <div class="text-muted text-danger" style="font-size:12px">
          <i class="bi bi-exclamation-triangle"></i> <?= $d['estoque_baixo'] ?> em falta
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card border-0 shadow-sm p-3">
        <div class="text-muted small fw-bold">RECEITAS VENCIDAS</div>
        <div class="fs-3 fw-bold text-warning"><?= $d['receitas_vencidas'] ?></div>
        <div class="text-muted" style="font-size:12px">Atencao ao prazo</div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-md-8">
      <div class="card border-0 shadow-sm p-4">
        <h6 class="fw-bold mb-4"><i class="bi bi-graph-up me-2"></i>Desempenho de Vendas (7 dias)</h6>
        <canvas id="chartVendas" height="120"></canvas>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm p-4">
        <h6 class="fw-bold mb-3">Acesso Rapido</h6>
        <div class="d-grid gap-2">
          <a href="vendas.php" class="btn btn-success text-start"><i class="bi bi-plus-circle me-2"></i>Nova Venda</a>
          <a href="agendamentos.php" class="btn btn-outline-primary text-start"><i class="bi bi-calendar-plus me-2"></i>Novo Agendamento</a>
          <a href="estoque.php" class="btn btn-outline-secondary text-start"><i class="bi bi-box-seam me-2"></i>Consultar Estoque</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chartVendas').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($dias_labels) ?>,
        datasets: [{
            label: 'Receita (R$)',
            data: <?= json_encode($dias_valores) ?>,
            backgroundColor: '#1a7a4a',
            borderRadius: 5
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { ticks: { callback: v => 'R$' + v.toLocaleString('pt-BR') } }
        }
    }
});
</script>

<?php require_once 'footer_afp.php'; ?>
