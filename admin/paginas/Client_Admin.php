<?php

// Pagination
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itensPorPagina = 10;

// Process filters
$filters = [];
$filtroTipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtroCidade = isset($_GET['cidade']) ? (int)$_GET['cidade'] : 0;
$filtroBairro = isset($_GET['bairro']) ? (int)$_GET['bairro'] : 0;
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

// Add non-empty filters to the array
if (!empty($filtroTipo)) {
    $filters['tipo'] = $filtroTipo;
}
if (!empty($filtroCidade)) {
    $filters['cidade'] = $filtroCidade;
}
if (!empty($filtroBairro)) {
    $filters['bairro'] = $filtroBairro;
}
if (!empty($filtroBusca)) {
    $filters['busca'] = $filtroBusca;
}

// Get clients with pagination
$clientResult = getAdminClients($filters, $paginaAtual, $itensPorPagina);
$clientes = $clientResult['clients'];
$totalRegistros = $clientResult['total'];
$totalPaginas = $clientResult['totalPages'];

// Get cities for filter
$cidades = getAllCities();

// Get neighborhoods for filter
$bairros = getAllBairros();

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
<div class="admin-page client-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter Form -->
    <div class="admin-card">
        <h3 class="card-title">Filtros</h3>
        <form method="GET" action="<?= BASE_URL ?>/admin/index.php" class="filter-form">
            <input type="hidden" name="page" value="Client_Admin">
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo">Tipo</label>
                    <select id="tipo" name="tipo" class="form-control">
                        <option value="">Todos</option>
                        <option value="Física" <?= $filtroTipo === 'Física' ? 'selected' : '' ?>>Pessoa Física</option>
                        <option value="Jurídica" <?= $filtroTipo === 'Jurídica' ? 'selected' : '' ?>>Pessoa Jurídica</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cidade">Cidade</label>
                    <select id="cidade" name="cidade" class="form-control cidade-select">
                        <option value="">Todas</option>
                        <?php foreach ($cidades as $cidade): ?>
                            <option value="<?= $cidade['id'] ?>" <?= $filtroCidade === (int)$cidade['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cidade['nome']) ?> (<?= htmlspecialchars($cidade['uf']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="bairro">Bairro</label>
                    <select id="bairro" name="bairro" class="form-control" <?= empty($filtroCidade) ? 'disabled' : '' ?>>
                        <option value="">Todos</option>
                        <?php foreach ($bairros as $bairro): ?>
                            <?php 
                            // Only show neighborhoods from the selected city
                            $showBairro = empty($filtroCidade) || $bairro['id_cidade'] == $filtroCidade;
                            if ($showBairro):
                            ?>
                                <option value="<?= $bairro['id'] ?>" 
                                        data-cidade="<?= $bairro['id_cidade'] ?>"
                                        <?= $filtroBairro === (int)$bairro['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($bairro['bairro']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="busca">Busca</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Nome, email ou telefone" value="<?= htmlspecialchars($filtroBusca) ?>">
                </div>
                
                <div class="form-group filter-buttons">
                    <label class="invisible">Ações</label>
                    <div class="button-group">
                        <button type="submit" class="primary-button">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="cancel-button">Limpar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

        
    <!-- Page Header with Add Button -->
    <div class="admin-page__header">
        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="primary-button">
            <i class="fas fa-plus"></i> Novo Cliente
        </a>
    </div>
    
    <!-- Clients Table -->
    <div class="admin-card">
        <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <p>Nenhum cliente encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="primary-button">
                    Adicionar Cliente
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome/Razão Social</th>
                            <th>Tipo</th>
                            <th>Email</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th>Categoria</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?= $cliente['id'] ?></td>
                                <td>
                                    <?php if ($cliente['tipo'] === 'Jurídica'): ?>
                                        <?= htmlspecialchars($cliente['razao_social']) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($cliente['nome_completo']) ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($cliente['principal'] === 'Sim'): ?>
                                        <span class="badge badge--principal">Principal</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $cliente['tipo'] === 'Física' ? 'badge--blue' : 'badge--orange' ?>">
                                        <?= $cliente['tipo'] === 'Física' ? 'Pessoa Física' : 'Pessoa Jurídica' ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($cliente['email']) ?></td>
                                <td><?= htmlspecialchars($cliente['telefone1']) ?></td>
                                <td><?= htmlspecialchars($cliente['cidade'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($cliente['categoria'])): ?>
                                        <span class="badge badge--category">
                                            <?= htmlspecialchars($cliente['categoria']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-data">Não definida</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Client_View&id=<?= $cliente['id'] ?>" class="action-button action-button--view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $cliente['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Delete&id=<?= $cliente['id'] ?>" class="action-button action-button--delete" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPaginas > 1): ?>
                <div class="pagination">
                    <?php if ($paginaAtual > 1): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=1<?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $paginaAtual-1 ?><?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    // Determine range of page numbers to display
                    $range = 2; // Show 2 pages before and after current page
                    $startPage = max(1, $paginaAtual - $range);
                    $endPage = min($totalPaginas, $paginaAtual + $range);
                    
                    // Always show first page
                    if ($startPage > 1) {
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Client_Admin&pagina=1'.(!empty($filtroTipo) ? '&tipo='.$filtroTipo : '').(!empty($filtroCidade) ? '&cidade='.$filtroCidade : '').(!empty($filtroBairro) ? '&bairro='.$filtroBairro : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Display page numbers within range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $paginaAtual) ? 'pagination__item--active' : '';
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Client_Admin&pagina='.$i.(!empty($filtroTipo) ? '&tipo='.$filtroTipo : '').(!empty($filtroCidade) ? '&cidade='.$filtroCidade : '').(!empty($filtroBairro) ? '&bairro='.$filtroBairro : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item '.$activeClass.'">'.$i.'</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPaginas) {
                        if ($endPage < $totalPaginas - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Client_Admin&pagina='.$totalPaginas.(!empty($filtroTipo) ? '&tipo='.$filtroTipo : '').(!empty($filtroCidade) ? '&cidade='.$filtroCidade : '').(!empty($filtroBairro) ? '&bairro='.$filtroBairro : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">'.$totalPaginas.'</a>';
                    }
                    ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $paginaAtual+1 ?><?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin&pagina=<?= $totalPaginas ?><?= !empty($filtroTipo) ? '&tipo='.$filtroTipo : '' ?><?= !empty($filtroCidade) ? '&cidade='.$filtroCidade : '' ?><?= !empty($filtroBairro) ? '&bairro='.$filtroBairro : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic neighborhood filtering based on selected city
    const cidadeSelect = document.querySelector('.cidade-select');
    const bairroSelect = document.getElementById('bairro');
    
    if (cidadeSelect && bairroSelect) {
        cidadeSelect.addEventListener('change', function() {
            const cidadeId = this.value;
            bairroSelect.disabled = !cidadeId;
            
            // Clear current options except the first one
            while (bairroSelect.options.length > 1) {
                bairroSelect.remove(1);
            }
            
            if (!cidadeId) {
                return; // No city selected, nothing to load
            }
            
            // Load neighborhoods via AJAX
            fetch(`<?= BASE_URL ?>/admin/ajax/get_bairros.php?id_cidade=${cidadeId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.bairros && data.bairros.length > 0) {
                        data.bairros.forEach(bairro => {
                            const option = document.createElement('option');
                            option.value = bairro.id;
                            option.textContent = bairro.bairro;
                            bairroSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching neighborhoods:', error));
        });
    }
});
</script>