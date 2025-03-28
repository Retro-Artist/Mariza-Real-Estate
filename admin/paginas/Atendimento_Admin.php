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
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Filtros
$filtros = [];
$filtroStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filtroLocal = isset($_GET['local']) ? $_GET['local'] : '';
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroStatus)) {
    $filtros[] = "status = :status";
}

if (!empty($filtroLocal)) {
    $filtros[] = "local = :local";
}

if (!empty($filtroBusca)) {
    $filtros[] = "(nome LIKE :busca OR email LIKE :busca OR telefone LIKE :busca OR mensagem LIKE :busca)";
}

// Construir a cláusula WHERE
$whereClause = !empty($filtros) ? " WHERE " . implode(" AND ", $filtros) : "";

// Get all interactions
try {
    // Pegar total de registros para paginação
    $sqlCount = "SELECT COUNT(*) as total FROM sistema_interacao" . $whereClause;
    $stmtCount = $databaseConnection->prepare($sqlCount);
    
    // Bind parameters for count query
    if (!empty($filtroStatus)) {
        $stmtCount->bindParam(':status', $filtroStatus);
    }
    if (!empty($filtroLocal)) {
        $stmtCount->bindParam(':local', $filtroLocal);
    }
    if (!empty($filtroBusca)) {
        $termoBusca = "%" . $filtroBusca . "%";
        $stmtCount->bindParam(':busca', $termoBusca);
    }
    
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    
    // Pegar registros paginados
    $sql = "SELECT * FROM sistema_interacao" . $whereClause . " 
           ORDER BY data DESC, hora DESC
           LIMIT :limit OFFSET :offset";
    
    $stmt = $databaseConnection->prepare($sql);
    
    // Bind parameters for main query
    if (!empty($filtroStatus)) {
        $stmt->bindParam(':status', $filtroStatus);
    }
    if (!empty($filtroLocal)) {
        $stmt->bindParam(':local', $filtroLocal);
    }
    if (!empty($filtroBusca)) {
        $stmt->bindParam(':busca', $termoBusca);
    }
    
    $stmt->bindParam(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $atendimentos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError("Error fetching atendimentos: " . $e->getMessage());
    $atendimentos = [];
    $totalRegistros = 0;
    $totalPaginas = 1;
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

<style>
/* Badge styles for sources and status */
.badge--site {
    background-color: rgba(102, 16, 242, 0.2);
    color: #6610f2;
}

.badge--whatsapp {
    background-color: rgba(37, 211, 102, 0.2);
    color: #25D366;
}

.badge--telefone {
    background-color: rgba(0, 123, 255, 0.2);
    color: #007bff;
}

.badge--pessoal {
    background-color: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.badge--pending {
    background-color: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.badge--progress {
    background-color: rgba(0, 123, 255, 0.2);
    color: #007bff;
}

.badge--complete {
    background-color: rgba(40, 167, 69, 0.2);
    color: #28a745;
}
</style>