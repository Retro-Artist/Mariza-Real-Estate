<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Pagination
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itensPorPagina = 10;

// Filters
$filtros = [];
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtroCidade = isset($_GET['cidade']) ? (int)$_GET['cidade'] : 0;
$filtroBairro = isset($_GET['bairro']) ? (int)$_GET['bairro'] : 0;
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroTipo)) {
    $filtros['tipo'] = $filtroTipo;
}

if (!empty($filtroCidade)) {
    $filtros['cidade'] = $filtroCidade;
}

if (!empty($filtroBairro)) {
    $filtros['bairro'] = $filtroBairro;
}

if (!empty($filtroBusca)) {
    $filtros['busca'] = $filtroBusca;
}

// Get clients with pagination using our function from admin_functions.php
$clientResult = getAdminClients($filtros, $paginaAtual, $itensPorPagina);
$clientes = $clientResult['clients'];
$totalRegistros = $clientResult['total'];
$totalPaginas = $clientResult['totalPages'];

// Get data for filters
// Get client types for filter
try {
    $stmtTipos = $databaseConnection->query("SELECT DISTINCT tipo FROM sistema_clientes ORDER BY tipo ASC");
    $tipos = $stmtTipos->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    logError("Error fetching client types: " . $e->getMessage());
    $tipos = [];
}

// Get states
$estados = getStates();

// Get cities for filter
try {
    $stmtCidades = $databaseConnection->query("SELECT c.id, c.nome, e.uf FROM sistema_cidades c LEFT JOIN sistema_estados e ON c.id_estado = e.id ORDER BY c.nome ASC");
    $cidades = $stmtCidades->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching cities: " . $e->getMessage());
    $cidades = [];
}

// Get neighborhoods for filter
try {
    $stmtBairros = $databaseConnection->query("SELECT id, bairro FROM sistema_bairros ORDER BY bairro ASC");
    $bairros = $stmtBairros->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching neighborhoods: " . $e->getMessage());
    $bairros = [];
}

// Check for alert messages in session
$alertMessage = '';
$alertType = '';

if (isset($_SESSION['alert_message'])) {
    $alertMessage = $_SESSION['alert_message'];
    $alertType = $_SESSION['alert_type'] ?? 'success';
    
    // Clear alert message from session
    unset($_SESSION['alert_message']);
    unset($_SESSION['alert_type']);
}
?>

<!-- Clients List Page -->
<div class="admin-page">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header with Add Button -->
    <div class="page-header">
        <h1 class="page-title">Gerenciar Clientes</h1>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="btn btn-success">
            <i class="fas fa-plus"></i> Adicionar Cliente
        </a>
    </div>
    
    <!-- Filter Form -->
    <div class="filter-card">
        <form method="GET" action="<?= BASE_URL ?>/admin/index.php" class="filter-form">
            <input type="hidden" name="page" value="Client_Admin">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="tipo">Tipo:</label>
                    <select id="tipo" name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?= htmlspecialchars($tipo) ?>" <?= $filtroTipo === $tipo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tipo) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="cidade">Cidade:</label>
                    <select id="cidade" name="cidade" class="form-control">
                        <option value="">Sua Cidade</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?= $cidade['id'] ?>" <?= $filtroCidade === (int)$cidade['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cidade['nome']) ?> - <?= htmlspecialchars($cidade['uf']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="bairro">Bairro:</label>
                    <select id="bairro" name="bairro" class="form-control">
                        <option value="">Bairro</option>
                        <?php foreach ($bairros as $bairro): ?>
                            <option value="<?= $bairro['id'] ?>" <?= $filtroBairro === (int)$bairro['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bairro['bairro']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group busca-group">
                    <label for="busca">Buscar por Nome:</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Digite sua busca..." value="<?= htmlspecialchars($filtroBusca) ?>">
                </div>
                
                <div class="filter-group submit-group">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Clients Table -->
    <div class="table-responsive">
        <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <p>Nenhum cliente encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="btn btn-success">
                    Adicionar Cliente
                </a>
            </div>
        <?php else: ?>
            <table class="clients-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cadastro</th>
                        <th>Tipo</th>
                        <th>Nome / Empresa</th>
                        <th>Telefone</th>
                        <th>Cidade</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= $cliente['id'] ?></td>
                            <td>
                                <?php if ($cliente['principal'] === 'Sim'): ?>
                                    <span class="badge badge-principal">Principal</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(str_replace('Pessoa ', 'PF - ', $cliente['tipo'])) ?>
                            </td>
                            <td><?= htmlspecialchars($cliente['nome_completo'] ?: $cliente['razao_social']) ?></td>
                            <td><?= !empty($cliente['telefone1']) ? htmlspecialchars($cliente['telefone1']) : '-' ?></td>
                            <td><?= !empty($cliente['cidade']) ? htmlspecialchars($cliente['cidade']) : '-' ?></td>
                            <td><?= !empty($cliente['uf']) ? htmlspecialchars($cliente['uf']) : '-' ?></td>
                            <td class="actions">
                                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_View&id=<?= $cliente['id'] ?>" class="btn btn-sm btn-view" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $cliente['id'] ?>" class="btn btn-sm btn-edit" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Delete&id=<?= $cliente['id'] ?>" class="btn btn-sm btn-delete" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Print Report Button -->
            <div class="report-actions">
                <a href="#" class="btn btn-info btn-report">
                    <i class="fas fa-print"></i> Imprimir Relatório
                </a>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $paginaAtual-1 ?><?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination-item">
                            «
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <?php if ($i == $paginaAtual): ?>
                            <span class="pagination-item active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $i ?><?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination-item"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $paginaAtual+1 ?><?= !empty($filtroTipo) ? '&tipo='.urlencode($filtroTipo) : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.urlencode($filtroBusca) : '' ?>" class="pagination-item">
                            »
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // When cidade changes, filter bairros
    const cidadeSelect = document.getElementById('cidade');
    const bairroSelect = document.getElementById('bairro');
    
    if (cidadeSelect && bairroSelect) {
        cidadeSelect.addEventListener('change', function() {
            const cidadeId = this.value;
            
            if (cidadeId) {
                // Fetch bairros for selected city
                fetch(`<?= BASE_URL ?>/admin/ajax/get_bairros.php?id_cidade=${cidadeId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Clear current options except the first one
                        while (bairroSelect.options.length > 1) {
                            bairroSelect.remove(1);
                        }
                        
                        // Add new options
                        if (data.bairros && data.bairros.length > 0) {
                            data.bairros.forEach(bairro => {
                                const option = document.createElement('option');
                                option.value = bairro.id;
                                option.text = bairro.bairro;
                                bairroSelect.add(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error fetching bairros:', error));
            } else {
                // Reset bairro dropdown
                while (bairroSelect.options.length > 1) {
                    bairroSelect.remove(1);
                }
            }
        });
    }
});
</script>