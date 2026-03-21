<?php
session_start();
require_once __DIR__ . '/../config/config.php';
if (!isset($_SESSION['id_func'])) { exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo '<p>ID inválido.</p>'; exit; }

$db   = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("
    SELECT pv.quantidade, pv.preco_unitario, pv.subtotal, p.nome_prod
    FROM produto_venda pv JOIN produto p ON pv.id_prod = p.id_prod
    WHERE pv.id_venda = :id
");
$stmt->execute([':id' => $id]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$venda = $conn->prepare("SELECT * FROM venda WHERE id_venda = :id");
$venda->execute([':id' => $id]);
$v = $venda->fetch(PDO::FETCH_ASSOC);
if (!$v) { echo '<p>Venda não encontrada.</p>'; exit; }
?>
<p class="mb-2" style="font-size:13px;color:#6b7280">Venda #<?= (int)$id ?> – <?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></p>
<table class="table table-sm">
  <thead><tr><th>Produto</th><th>Qtd</th><th>Unit.</th><th>Subtotal</th></tr></thead>
  <tbody>
  <?php foreach ($itens as $i): ?>
    <tr>
      <td><?= htmlspecialchars($i['nome_prod']) ?></td>
      <td><?= $i['quantidade'] ?></td>
      <td>R$ <?= number_format($i['preco_unitario'],2,',','.') ?></td>
      <td class="fw-bold">R$ <?= number_format($i['subtotal'],2,',','.') ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
  <tfoot>
    <?php if ($v['desconto'] > 0): ?>
    <tr><td colspan="3" class="text-end text-danger">Desconto:</td><td class="text-danger">- R$ <?= number_format($v['desconto'],2,',','.') ?></td></tr>
    <?php endif; ?>
    <tr class="table-success"><td colspan="3" class="fw-bold text-end">Total:</td><td class="fw-bold">R$ <?= number_format($v['valor_total'],2,',','.') ?></td></tr>
  </tfoot>
</table>
<p style="font-size:12px;color:#6b7280">Pagamento: <strong><?= htmlspecialchars((string)$v['forma_pagamento'], ENT_QUOTES, 'UTF-8') ?></strong><?= !empty($v['observacoes']) ? " | Obs: " . htmlspecialchars((string)$v['observacoes'], ENT_QUOTES, 'UTF-8') : "" ?></p>

