<?php
// admin/paginas/Atendimento_Admin.php

// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Paginação
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$itensPorPagina = 10;

// Filtros
$filtros = [];
$filtroStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filtroLocal = isset($_GET['local']) ? $_GET['local'] : '';
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroStatus)) {
    $filtros['status'] = $filtroStatus;
}

if (!empty($filtroLocal)) {
    $filtros['local'] = $filtroLocal;
}

if (!empty($filtroBusca)) {
    $filtros['busca'] = $filtroBusca;
}

// Get service requests with pagination using function from admin_functions.php
$requestResult = getServiceRequests($filtros, $paginaAtual, $itensPorPagina);
$atendimentos = $requestResult['requests'];
$totalRegistros = $requestResult['total'];
$totalPaginas = $requestResult['totalPages'];

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

<div class="admin-page atendimento-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Gerenciar Atendimentos</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Create" class="primary-button">
            <i class="fas fa-plus"></i> Novo Atendimento
        </a>
    </div>
    
    <!-- Filter Form -->
    <div class="admin-card">
        <h3 class="card-title">Filtros</h3>
        <form method="GET" action="<?= BASE_URL ?>/admin/index.php" class="filter-form">
            <input type="hidden" name="page" value="Atendimento_Admin">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pendente" <?= $filtroStatus === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Em Andamento" <?= $filtroStatus === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="Concluído" <?= $filtroStatus === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                        <option value="Cancelado" <?= $filtroStatus === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="local">Origem</label>
                    <select id="local" name="local" class="form-control">
                        <option value="">Todas</option>
                        <option value="Site" <?= $filtroLocal === 'Site' ? 'selected' : '' ?>>Site</option>
                        <option value="WhatsApp" <?= $filtroLocal === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="Telefone" <?= $filtroLocal === 'Telefone' ? 'selected' : '' ?>>Telefone</option>
                        <option value="Pessoal" <?= $filtroLocal === 'Pessoal' ? 'selected' : '' ?>>Pessoal</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="busca">Busca</label>
                    <input type="text" id="busca" name="busca" class="form-control" 
                           placeholder="Nome, email ou telefone" value="<?= htmlspecialchars($filtroBusca) ?>">
                </div>
                
                <div class="form-group form-group--submit">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="cancel-button">Limpar</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Atendimentos Table -->
    <div class="admin-card">
        <?php if (empty($atendimentos)): ?>
            <div class="empty-state">
                <p>Nenhum atendimento encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Create" class="primary-button">
                    Adicionar Atendimento
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Origem</th>
                            <th>Status</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atendimentos as $atendimento): ?>
                            <tr>
                                <td><?= $atendimento['id'] ?></td>
                                <td><?= formatDate($atendimento['data']) ?> <?= $atendimento['hora'] ?></td>
                                <td><?= htmlspecialchars($atendimento['nome']) ?></td>
                                <td><?= htmlspecialchars($atendimento['telefone']) ?></td>
                                <td><?= htmlspecialchars($atendimento['email']) ?></td>
                                <td>
                                    <span class="badge badge--<?= strtolower($atendimento['local']) ?>">
                                        <?= htmlspecialchars($atendimento['local']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $atendimento['status'] === 'Pendente' ? 'badge--pending' : ($atendimento['status'] === 'Concluído' ? 'badge--complete' : 'badge--progress') ?>">
                                        <?= htmlspecialchars($atendimento['status']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_View&id=<?= $atendimento['id'] ?>" class="action-button action-button--view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Delete&id=<?= $atendimento['id'] ?>" class="action-button action-button--delete delete-button" title="Excluir">
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
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin&pagina=<?= $paginaAtual-1 ?><?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroLocal) ? '&local='.$filtroLocal : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <?php if ($i == $paginaAtual): ?>
                            <span class="pagination__item pagination__item--active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin&pagina=<?= $i ?><?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroLocal) ? '&local='.$filtroLocal : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin&pagina=<?= $paginaAtual+1 ?><?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroLocal) ? '&local='.$filtroLocal : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>