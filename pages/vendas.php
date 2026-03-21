<?php
$page_title = 'Nova Venda';
require_once 'header_afp.php';

$db   = new Database();
$conn = $db->connect();
$msg  = ''; $tipo = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_venda'])) {
    try {
        $conn->beginTransaction();
        $id_cli          = (int)$_POST['id_cliente'];
        $id_func         = (int)$_SESSION['id_func'];
        $forma_pagamento = sanitize($_POST['forma_pagamento']);
        $desconto        = (float)($_POST['desconto'] ?? 0);
        $obs             = sanitize($_POST['observacoes'] ?? '');
        $valor_total     = 0;

        foreach ($_POST['produtos'] as $item) {
            if (!empty($item['id_prod']) && (int)$item['quantidade'] > 0) {
                $valor_total += (float)$item['preco'] * (int)$item['quantidade'];
            }
        }
        $valor_total = max(0, $valor_total - $desconto);

        // MySQL: sem RETURNING — usa lastInsertId()
        $stmt = $conn->prepare("INSERT INTO venda (id_cli, id_func, valor_total, desconto, forma_pagamento, observacoes)
                                VALUES (:id_cli, :id_func, :valor_total, :desc, :forma_pagamento, :obs)");
        $stmt->execute([
            ':id_cli'          => $id_cli,
            ':id_func'         => $id_func,
            ':valor_total'     => $valor_total,
            ':desc'            => $desconto,
            ':forma_pagamento' => $forma_pagamento,
            ':obs'             => $obs
        ]);
        $id_venda = $conn->lastInsertId();

        foreach ($_POST['produtos'] as $item) {
            if (!empty($item['id_prod']) && (int)$item['quantidade'] > 0) {
                $subtotal = (float)$item['preco'] * (int)$item['quantidade'];
                $conn->prepare("INSERT INTO produto_venda (id_venda, id_prod, quantidade, preco_unitario, subtotal)
                                VALUES (:iv,:ip,:q,:p,:s)")
                     ->execute([':iv' => $id_venda, ':ip' => (int)$item['id_prod'],
                                ':q' => (int)$item['quantidade'], ':p' => (float)$item['preco'], ':s' => $subtotal]);
                $conn->prepare("UPDATE produto SET estoque_atual = estoque_atual - :q WHERE id_prod = :ip")
                     ->execute([':q' => (int)$item['quantidade'], ':ip' => (int)$item['id_prod']]);
            }
        }
        $conn->commit();
        $msg = "Venda #$id_venda registrada com sucesso! Total: " . formatMoeda($valor_total);
        $tipo = 'success';
    } catch (Exception $e) {
        $conn->rollBack();
        $msg = 'Erro ao registrar venda: ' . $e->getMessage();
        $tipo = 'danger';
    }
}

$clientes = $conn->query("SELECT id_cli, nome_cli, cpf_cli FROM cliente ORDER BY nome_cli")->fetchAll();
$produtos  = $conn->query("SELECT id_prod, nome_prod, cod_bar_prod, preco_venda, estoque_atual
                           FROM produto WHERE estoque_atual > 0 ORDER BY nome_prod")->fetchAll();
?>

<?php if ($msg): ?>
<div class="alert alert-<?= $tipo ?> alert-dismissible" style="border-radius:10px">
  <i class="bi bi-<?= $tipo === 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i><?= $msg ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header" style="background:var(--green);color:white">
    <h5 class="mb-0"><i class="bi bi-cart-plus me-2"></i>Registrar Nova Venda</h5>
  </div>
  <div class="card-body">
    <form method="POST" id="formVenda">
      <div class="row mb-3 g-2">
        <div class="col-md-5">
          <label class="form-label fw-semibold">Cliente *</label>
          <select class="form-select" name="id_cliente" required>
            <option value="">Selecione o cliente...</option>
            <?php foreach ($clientes as $cli): ?>
              <option value="<?= $cli['id_cli'] ?>"><?= sanitize($cli['nome_cli']) ?> – <?= $cli['cpf_cli'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Forma de Pagamento *</label>
          <select class="form-select" name="forma_pagamento" required>
            <?php foreach (['Dinheiro','Débito','Crédito','PIX'] as $fp): ?>
            <option value="<?= $fp ?>"><?= $fp ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Desconto (R$)</label>
          <input type="number" step="0.01" min="0" class="form-control" name="desconto"
                 id="descontoInput" value="0" onchange="calcularTotal()">
        </div>
        <div class="col-md-2">
          <label class="form-label fw-semibold">Observações</label>
          <input type="text" class="form-control" name="observacoes" placeholder="Opcional">
        </div>
      </div>

      <h6 class="mb-2 fw-bold"><i class="bi bi-bag me-2 text-success"></i>Produtos</h6>
      <div id="itens-venda">
        <div class="row mb-2 item-venda align-items-end g-2">
          <div class="col-md-5">
            <select class="form-select form-select-sm produto-select" name="produtos[0][id_prod]" onchange="atualizarPreco(this,0)">
              <option value="">Selecione o produto...</option>
              <?php foreach ($produtos as $prod): ?>
                <option value="<?= $prod['id_prod'] ?>" data-preco="<?= $prod['preco_venda'] ?>" data-estoque="<?= $prod['estoque_atual'] ?>">
                  <?= sanitize($prod['nome_prod']) ?> (Estq: <?= $prod['estoque_atual'] ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <input type="number" class="form-control form-control-sm quantidade-input" name="produtos[0][quantidade]" min="1" value="1" onchange="calcularTotal()" placeholder="Qtd">
          </div>
          <div class="col-md-2">
            <input type="number" step="0.01" class="form-control form-control-sm preco-input" name="produtos[0][preco]" readonly placeholder="Preço">
          </div>
          <div class="col-md-2">
            <input type="text" class="form-control form-control-sm subtotal-display" readonly placeholder="Subtotal">
          </div>
          <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removerItem(this)"><i class="bi bi-trash"></i></button>
          </div>
        </div>
      </div>

      <button type="button" class="btn btn-sm btn-outline-secondary mb-3" onclick="adicionarItem()">
        <i class="bi bi-plus-circle me-1"></i>Adicionar Produto
      </button>

      <div class="row">
        <div class="col-md-8"></div>
        <div class="col-md-4">
          <div class="card" style="background:var(--green-lt);border-color:rgba(26,122,74,.2)">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between"><span>Subtotal:</span><span id="subtotalDisp">R$ 0,00</span></div>
              <div class="d-flex justify-content-between text-danger"><span>Desconto:</span><span id="descontoDisp">R$ 0,00</span></div>
              <hr class="my-1">
              <div class="d-flex justify-content-between fw-bold fs-5" style="color:var(--green)">
                <span>Total:</span><span id="valorTotal">R$ 0,00</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-3 d-flex gap-2">
        <button type="submit" name="finalizar_venda" class="btn btn-success btn-lg">
          <i class="bi bi-check-circle me-2"></i>Finalizar Venda
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
let contador = 1;
const produtosSelect = document.querySelector('.produto-select').innerHTML;

function atualizarPreco(sel, idx) {
  const opt = sel.options[sel.selectedIndex];
  const preco = opt.getAttribute('data-preco') || '';
  sel.closest('.item-venda').querySelector('.preco-input').value = preco;
  calcularTotal();
}
function calcularTotal() {
  let sub = 0;
  document.querySelectorAll('.item-venda').forEach(row => {
    const q = parseFloat(row.querySelector('.quantidade-input').value) || 0;
    const p = parseFloat(row.querySelector('.preco-input').value) || 0;
    const st = q * p;
    row.querySelector('.subtotal-display').value = st.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
    sub += st;
  });
  const desc = parseFloat(document.getElementById('descontoInput').value) || 0;
  document.getElementById('subtotalDisp').textContent = sub.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
  document.getElementById('descontoDisp').textContent = desc.toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
  document.getElementById('valorTotal').textContent   = Math.max(0,sub-desc).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
}
function adicionarItem() {
  const container = document.getElementById('itens-venda');
  const novo = container.querySelector('.item-venda').cloneNode(true);
  novo.querySelectorAll('input,select').forEach(el => {
    const n = el.getAttribute('name');
    if (n) el.setAttribute('name', n.replace(/\[\d+\]/, `[${contador}]`));
    if (el.tagName === 'INPUT') el.value = el.classList.contains('quantidade-input') ? 1 : '';
    if (el.tagName === 'SELECT') el.selectedIndex = 0;
  });
  novo.querySelector('.produto-select').setAttribute('onchange', `atualizarPreco(this,${contador})`);
  container.appendChild(novo);
  contador++;
}
function removerItem(btn) {
  const items = document.querySelectorAll('.item-venda');
  if (items.length > 1) { btn.closest('.item-venda').remove(); calcularTotal(); }
  else alert('Deve haver pelo menos 1 item na venda.');
}
</script>

<?php require_once 'footer_afp.php'; ?>
