<?php
$page_title = 'Produtos';
require_once 'components/header_afp.php';

$db   = new Database();
$conn = $db->connect();

if (isset($_GET['del']) && is_numeric($_GET['del'])) {
    $conn->prepare("DELETE FROM produto WHERE id_prod = :id")->execute([':id' => (int)$_GET['del']]);
    header('Location: produtos.php?ok=Produto+exclu%C3%ADdo'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id   = $_POST['id_prod'] ?? null;
    // MySQL usa 1/0 para booleanos
    $rec  = isset($_POST['necessita_receita']) ? 1 : 0;
    $data = [
        ':nome'  => sanitize($_POST['nome_prod']),
        ':cod'   => sanitize($_POST['cod_bar_prod']),
        ':cat'   => $_POST['id_cat'] ?: null,
        ':desc'  => sanitize($_POST['descricao'] ?? ''),
        ':pc'    => (float)$_POST['preco_compra'],
        ':pv'    => (float)$_POST['preco_venda'],
        ':est'   => (int)$_POST['estoque_atual'],
        ':emin'  => (int)$_POST['estoque_minimo'],
        ':rec'   => $rec,
        ':val'   => $_POST['data_validade'] ?: null,
        ':forn'  => sanitize($_POST['fornecedor'] ?? ''),
    ];
    if ($id) {
        $data[':id'] = (int)$id;
        $conn->prepare("UPDATE produto SET nome_prod=:nome,cod_bar_prod=:cod,id_cat=:cat,descricao=:desc,
                        preco_compra=:pc,preco_venda=:pv,estoque_atual=:est,estoque_minimo=:emin,
                        necessita_receita=:rec,data_validade=:val,fornecedor=:forn
                        WHERE id_prod=:id")->execute($data);
        header('Location: produtos.php?ok=Produto+atualizado'); exit;
    } else {
        $conn->prepare("INSERT INTO produto (nome_prod,cod_bar_prod,id_cat,descricao,preco_compra,preco_venda,
                        estoque_atual,estoque_minimo,necessita_receita,data_validade,fornecedor)
                        VALUES (:nome,:cod,:cat,:desc,:pc,:pv,:est,:emin,:rec,:val,:forn)")->execute($data);
        header('Location: produtos.php?ok=Produto+cadastrado'); exit;
    }
}

$categorias = $conn->query("SELECT * FROM categoria ORDER BY nome_cat")->fetchAll();


$busca = sanitize($_GET['busca'] ?? '');
$cat_f = (int)($_GET['cat'] ?? 0);
$eb_f  = $_GET['eb'] ?? '';
$where = "WHERE 1=1";
$params = [];
if ($busca) { $where .= " AND (p.nome_prod LIKE :b OR p.cod_bar_prod LIKE :b)"; $params[':b'] = "%$busca%"; }
if ($cat_f) { $where .= " AND p.id_cat = :cat"; $params[':cat'] = $cat_f; }
if ($eb_f)  { $where .= " AND p.estoque_atual <= p.estoque_minimo"; }

$stmt = $conn->prepare("SELECT p.*, c.nome_cat FROM produto p
                        LEFT JOIN categoria c ON p.id_cat = c.id_cat
                        $where ORDER BY p.nome_prod");
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$edit = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $e = $conn->prepare("SELECT * FROM produto WHERE id_prod = :id");
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

<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="d-flex gap-2 flex-wrap align-items-center justify-content-between">
      <form class="d-flex gap-2 flex-wrap" method="GET">
        <input type="text" name="busca" class="form-control form-control-sm" placeholder="Buscar produto ou código..." value="<?= $busca ?>" style="width:220px">
        <select name="cat" class="form-select form-select-sm" style="width:160px">
          <option value="">Todas categorias</option>
          <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id_cat'] ?>" <?= $cat_f == $c['id_cat'] ? 'selected' : '' ?>><?= $c['nome_cat'] ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-check form-check-inline align-items-center d-flex gap-1 mb-0">
          <input class="form-check-input" type="checkbox" name="eb" id="eb" value="1" <?= $eb_f ? 'checked' : '' ?>>
          <label class="form-check-label" for="eb" style="font-size:13px">Estoque baixo</label>
        </div>
        <button class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filtrar</button>
        <a href="produtos.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
      </form>
      <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalProd">
        <i class="bi bi-plus-circle me-1"></i>Novo Produto
      </button>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Produto</th><th>Código</th><th>Categoria</th>
            <th>Compra</th><th>Venda</th><th>Estoque</th>
            <th>Receita</th><th>Validade</th><th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($produtos as $p): ?>
          <?php $baixo = $p['estoque_atual'] <= $p['estoque_minimo']; ?>
          <tr>
            <td>
              <strong><?= sanitize($p['nome_prod']) ?></strong>
              <?= $p['necessita_receita'] ? ' <span class="badge bg-danger ms-1" style="font-size:9px">RX</span>' : '' ?>
            </td>
            <td style="font-size:12px;color:var(--gray)"><?= $p['cod_bar_prod'] ?: '-' ?></td>
            <td style="font-size:12px"><?= $p['nome_cat'] ?? '-' ?></td>
            <td style="font-size:12px"><?= formatMoeda($p['preco_compra']) ?></td>
            <td class="fw-bold" style="color:var(--green)"><?= formatMoeda($p['preco_venda']) ?></td>
            <td>
              <span class="<?= $baixo ? 'text-danger fw-bold' : 'text-dark' ?>"><?= $p['estoque_atual'] ?></span>
              <span style="font-size:11px;color:var(--gray)"> / min <?= $p['estoque_minimo'] ?></span>
              <?= $baixo ? '<i class="bi bi-exclamation-triangle-fill text-danger ms-1"></i>' : '' ?>
            </td>
            <td><?= $p['necessita_receita'] ? '<span class="badge bg-danger">Sim</span>' : '<span class="badge bg-secondary">Não</span>' ?></td>
            <td style="font-size:12px"><?= $p['data_validade'] ? formatDataSimples($p['data_validade']) : '-' ?></td>
            <td>
              <a href="?edit=<?= $p['id_prod'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="bi bi-pencil"></i></a>
              <a href="?del=<?= $p['id_prod'] ?>" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1"
                 onclick="return confirm('Excluir este produto?')"><i class="bi bi-trash"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($produtos)): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<div class="modal fade" id="modalProd" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header" style="background:var(--green);color:white">
        <h5 class="modal-title"><i class="bi bi-box-seam me-2"></i><?= $edit ? 'Editar' : 'Novo' ?> Produto</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id_prod" value="<?= $edit['id_prod'] ?? '' ?>">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Nome do Produto *</label>
              <input type="text" name="nome_prod" class="form-control" required value="<?= $edit['nome_prod'] ?? '' ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Código de Barras</label>
              <input type="text" name="cod_bar_prod" class="form-control" value="<?= $edit['cod_bar_prod'] ?? '' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Categoria</label>
              <select name="id_cat" class="form-select">
                <option value="">Sem categoria</option>
                <?php foreach ($categorias as $c): ?>
                <option value="<?= $c['id_cat'] ?>" <?= ($edit['id_cat'] ?? '') == $c['id_cat'] ? 'selected' : '' ?>><?= $c['nome_cat'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Fornecedor</label>
              <input type="text" name="fornecedor" class="form-control" value="<?= $edit['fornecedor'] ?? '' ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Preço Compra</label>
              <input type="number" step="0.01" name="preco_compra" class="form-control" required value="<?= $edit['preco_compra'] ?? '0' ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Preço Venda</label>
              <input type="number" step="0.01" name="preco_venda" class="form-control" required value="<?= $edit['preco_venda'] ?? '0' ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Estoque Atual</label>
              <input type="number" name="estoque_atual" class="form-control" required value="<?= $edit['estoque_atual'] ?? '0' ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Estoque Mínimo</label>
              <input type="number" name="estoque_minimo" class="form-control" required value="<?= $edit['estoque_minimo'] ?? '5' ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Data de Validade</label>
              <input type="date" name="data_validade" class="form-control" value="<?= $edit['data_validade'] ?? '' ?>">
            </div>
            <div class="col-md-6 d-flex align-items-end pb-1">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="necessita_receita" id="nr"
                       <?= !empty($edit['necessita_receita']) ? 'checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="nr">Necessita Receita Médica (RX)</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Descrição</label>
              <textarea name="descricao" class="form-control" rows="2"><?= $edit['descricao'] ?? '' ?></textarea>
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
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalProd')).show())</script>
<?php endif; ?>
<?php require_once 'components/footer_afp.php'; ?>
