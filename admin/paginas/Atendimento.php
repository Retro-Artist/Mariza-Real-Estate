<?php
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
$filtroOrigem = isset($_GET['origem']) ? $_GET['origem'] : '';
$filtroBusca = isset($_GET['busca']) ? $_GET['busca'] : '';

if (!empty($filtroStatus)) {
    $filtros[] = "i.status = :status";
}

if (!empty($filtroOrigem)) {
    $filtros[] = "i.local = :origem";
}

if (!empty($filtroBusca)) {
    $filtros[] = "(i.nome LIKE :busca OR i.email LIKE :busca OR i.telefone LIKE :busca OR i.mensagem LIKE :busca)";
}

// Construir a cláusula WHERE
$whereClause = !empty($filtros) ? " AND " . implode(" AND ", $filtros) : "";

// Get all service requests
try {
    // Pegar total de registros para paginação
    $sqlCount = "SELECT COUNT(*) as total FROM sistema_interacao i WHERE 1=1" . $whereClause;
    $stmtCount = $databaseConnection->prepare($sqlCount);
    
    // Bind parameters for count query
    if (!empty($filtroStatus)) {
        $stmtCount->bindParam(':status', $filtroStatus);
    }
    if (!empty($filtroOrigem)) {
        $stmtCount->bindParam(':origem', $filtroOrigem);
    }
    if (!empty($filtroBusca)) {
        $termoBusca = "%" . $filtroBusca . "%";
        $stmtCount->bindParam(':busca', $termoBusca);
    }
    
    $stmtCount->execute();
    $totalRegistros = $stmtCount->fetch()['total'];
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);
    
    // Pegar registros paginados
    $sql = "SELECT i.*
            FROM sistema_interacao i
            WHERE 1=1" . $whereClause . " 
            ORDER BY i.data DESC, i.hora DESC
            LIMIT :limit OFFSET :offset";
    
    $stmt = $databaseConnection->prepare($sql);
    
    // Bind parameters for main query
    if (!empty($filtroStatus)) {
        $stmt->bindParam(':status', $filtroStatus);
    }
    if (!empty($filtroOrigem)) {
        $stmt->bindParam(':origem', $filtroOrigem);
    }
    if (!empty($filtroBusca)) {
        $stmt->bindParam(':busca', $termoBusca);
    }
    
    $stmt->bindParam(':limit', $itensPorPagina, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $atendimentos = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching service requests: " . $e->getMessage());
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

<!-- Service Requests List Page -->
<div class="admin-page atendimento-admin">
    <?php if (!empty($alertMessage)): ?>
        <div class="alert-message alert-message--<?= $alertType ?>">
            <?= htmlspecialchars($alertMessage) ?>
        </div>
    <?php endif; ?>
    
    <!-- Page Header with Add Button -->
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
            <input type="hidden" name="page" value="Atendimento">
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Todos</option>
                        <option value="Pendente" <?= $filtroStatus === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="Em Andamento" <?= $filtroStatus === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                        <option value="Concluído" <?= $filtroStatus === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="origem">Origem</label>
                    <select id="origem" name="origem" class="form-control">
                        <option value="">Todas</option>
                        <option value="Site" <?= $filtroOrigem === 'Site' ? 'selected' : '' ?>>Site</option>
                        <option value="WhatsApp" <?= $filtroOrigem === 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="Telefone" <?= $filtroOrigem === 'Telefone' ? 'selected' : '' ?>>Telefone</option>
                        <option value="Pessoal" <?= $filtroOrigem === 'Pessoal' ? 'selected' : '' ?>>Pessoal</option>
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
                    <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento" class="cancel-button">Limpar</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Service Requests Table -->
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
                                <td><?= formatDate($atendimento['data']) ?></td>
                                <td><?= htmlspecialchars($atendimento['nome']) ?></td>
                                <td><?= htmlspecialchars($atendimento['telefone']) ?></td>
                                <td><?= htmlspecialchars($atendimento['email']) ?></td>
                                <td>
                                    <span class="badge badge--<?= strtolower($atendimento['local']) ?>">
                                        <?= htmlspecialchars($atendimento['local']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge--<?= strtolower(str_replace(' ', '-', $atendimento['status'])) ?>">
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
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento&pagina=1<?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroOrigem) ? '&origem='.$filtroOrigem : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento&pagina=<?= $paginaAtual-1 ?><?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroOrigem) ? '&origem='.$filtroOrigem : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
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
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Atendimento&pagina=1'.(!empty($filtroStatus) ? '&status='.$filtroStatus : '').(!empty($filtroOrigem) ? '&origem='.$filtroOrigem : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                    }
                    
                    // Display page numbers within range
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $paginaAtual) ? 'pagination__item--active' : '';
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Atendimento&pagina='.$i.(!empty($filtroStatus) ? '&status='.$filtroStatus : '').(!empty($filtroOrigem) ? '&origem='.$filtroOrigem : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item '.$activeClass.'">'.$i.'</a>';
                    }
                    
                    // Always show last page
                    if ($endPage < $totalPaginas) {
                        if ($endPage < $totalPaginas - 1) {
                            echo '<span class="pagination__item pagination__item--ellipsis">...</span>';
                        }
                        echo '<a href="'.BASE_URL.'/admin/index.php?page=Atendimento&pagina='.$totalPaginas.(!empty($filtroStatus) ? '&status='.$filtroStatus : '').(!empty($filtroOrigem) ? '&origem='.$filtroOrigem : '').(!empty($filtroBusca) ? '&busca='.$filtroBusca : '').'" class="pagination__item">'.$totalPaginas.'</a>';
                    }
                    ?>
                    
                    <?php if ($paginaAtual < $totalPaginas): ?>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento&pagina=<?= $paginaAtual+1 ?><?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroOrigem) ? '&origem='.$filtroOrigem : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-right"></i>
                        </a>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento&pagina=<?= $totalPaginas ?><?= !empty($filtroStatus) ? '&status='.$filtroStatus : '' ?><?= !empty($filtroOrigem) ? '&origem='.$filtroOrigem : '' ?><?= !empty($filtroBusca) ? '&busca='.$filtroBusca : '' ?>" class="pagination__item">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Badge styles for different statuses and sources */
.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: var(--font-xs);
    font-weight: 600;
    text-align: center;
}

.badge--pendente {
    background-color: #ffc107;
    color: #333;
}

.badge--em-andamento {
    background-color: #17a2b8;
    color: white;
}

.badge--concluído {
    background-color: #28a745;
    color: white;
}

.badge--site {
    background-color: #6610f2;
    color: white;
}

.badge--whatsapp {
    background-color: #25D366;
    color: white;
}

.badge--telefone {
    background-color: #fd7e14;
    color: white;
}

.badge--pessoal {
    background-color: #6c757d;
    color: white;
}
</style>