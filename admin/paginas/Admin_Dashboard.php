<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Get some basic statistics for the dashboard
try {
    // Count total properties
    $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_imoveis WHERE status = 'ativo'");
    $totalImoveis = $stmt->fetch()['total'];
    
    // Count properties by type (venda/aluguel)
    $stmt = $databaseConnection->query(
        "SELECT para, COUNT(*) as total FROM sistema_imoveis 
         WHERE status = 'ativo' 
         GROUP BY para"
    );
    $imoveisPorTipo = $stmt->fetchAll();
    
    // Count total categories
    $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_imoveis_categorias");
    $totalCategorias = $stmt->fetch()['total'];
    
    // Count total clients
    $stmt = $databaseConnection->query("SELECT COUNT(*) as total FROM sistema_clientes");
    $totalClientes = $stmt->fetch()['total'];
    
    // Get latest properties
    $stmt = $databaseConnection->query(
        "SELECT i.id, i.titulo, i.para, i.valor, i.data, c.categoria 
         FROM sistema_imoveis i
         LEFT JOIN sistema_imoveis_categorias c ON i.id_categoria = c.id
         WHERE i.status = 'ativo'
         ORDER BY i.data DESC, i.hora DESC
         LIMIT 5"
    );
    $ultimosImoveis = $stmt->fetchAll();

    // Get latest calendar events
    $stmt = $databaseConnection->query(
        "SELECT * FROM sistema_avisos 
         WHERE status = 'Pendente' 
         ORDER BY data_inicio ASC
         LIMIT 5"
    );
    $ultimosLembretes = $stmt->fetchAll();

    // Get latest service requests
    $stmt = $databaseConnection->query(
        "SELECT * FROM sistema_interacao
         WHERE status = 'Pendente' 
         ORDER BY data DESC, hora DESC
         LIMIT 5"
    );
    $ultimosAtendimentos = $stmt->fetchAll();
    
} catch (PDOException $e) {
    logError("Dashboard error: " . $e->getMessage());
    // Initialize with default values in case of error
    $totalImoveis = 0;
    $imoveisPorTipo = [];
    $totalCategorias = 0;
    $totalClientes = 0;
    $ultimosImoveis = [];
    $ultimosLembretes = [];
    $ultimosAtendimentos = [];
}

// Calculate properties by type for easy display
$imoveisVenda = 0;
$imoveisAluguel = 0;

foreach ($imoveisPorTipo as $tipo) {
    if ($tipo['para'] === 'venda') {
        $imoveisVenda = $tipo['total'];
    } elseif ($tipo['para'] === 'aluguel') {
        $imoveisAluguel = $tipo['total'];
    }
}
?>

<!-- Dashboard Content -->
<div class="dashboard">
    <!-- Statistics Cards -->
    <div class="dashboard__stats">
        <div class="stat-card">
            <div class="stat-card__icon">
                <i class="fas fa-home"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Total de Imóveis</h3>
                <p class="stat-card__value"><?= $totalImoveis ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--blue">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Categorias</h3>
                <p class="stat-card__value"><?= $totalCategorias ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--orange">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Clientes</h3>
                <p class="stat-card__value"><?= $totalClientes ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--green">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Imóveis para Venda</h3>
                <p class="stat-card__value"><?= $imoveisVenda ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card__icon stat-card__icon--red">
                <i class="fas fa-key"></i>
            </div>
            <div class="stat-card__content">
                <h3 class="stat-card__title">Imóveis para Aluguel</h3>
                <p class="stat-card__value"><?= $imoveisAluguel ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard__actions">
        <h2 class="dashboard__section-title">Ações Rápidas</h2>
        
        <div class="quick-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Create" class="quick-action">
                <i class="fas fa-plus-circle"></i>
                <span>Adicionar Imóvel</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/index.php?page=Category_Create" class="quick-action">
                <i class="fas fa-folder-plus"></i>
                <span>Adicionar Categoria</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="quick-action">
                <i class="fas fa-user-plus"></i>
                <span>Adicionar Cliente</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Create" class="quick-action">
                <i class="fas fa-calendar-plus"></i>
                <span>Adicionar Lembrete</span>
            </a>
            
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Create" class="quick-action">
                <i class="fas fa-headset"></i>
                <span>Registrar Atendimento</span>
            </a>
        </div>
    </div>
    
    <!-- Latest Properties -->
    <div class="dashboard__recent">
        <h2 class="dashboard__section-title">Imóveis Recentes</h2>
        
        <?php if (empty($ultimosImoveis)): ?>
            <div class="empty-state">
                <p>Nenhum imóvel cadastrado ainda.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Create" class="primary-button">
                    Adicionar Imóvel
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th width="150">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosImoveis as $imovel): ?>
                            <tr>
                                <td><?= $imovel['id'] ?></td>
                                <td><?= htmlspecialchars($imovel['titulo']) ?></td>
                                <td><?= htmlspecialchars($imovel['categoria'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge <?= $imovel['para'] === 'venda' ? 'badge--green' : 'badge--blue' ?>">
                                        <?= ucfirst($imovel['para']) ?>
                                    </span>
                                </td>
                                <td><?= formatCurrency($imovel['valor']) ?></td>
                                <td><?= formatDate($imovel['data']) ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Update&id=<?= $imovel['id'] ?>" class="action-button action-button--edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/imovel/<?= $imovel['id'] ?>" class="action-button action-button--view" title="Visualizar" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="dashboard__see-all">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Property_Admin" class="see-all-link">
                    Ver todos os imóveis <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Latest Calendar Events -->
    <div class="dashboard__recent">
        <h2 class="dashboard__section-title">Lembretes Recentes</h2>
        
        <?php if (empty($ultimosLembretes)): ?>
            <div class="empty-state">
                <p>Nenhum lembrete pendente encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Create" class="primary-button">
                    Adicionar Lembrete
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Para</th>
                            <th>Prioridade</th>
                            <th>Data Início</th>
                            <th>Data Fim</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosLembretes as $lembrete): ?>
                            <?php 
                                $data_inicio = new DateTime($lembrete['data_inicio']);
                                $data_fim = new DateTime($lembrete['data_fim']);
                                
                                $priorityClass = '';
                                switch ($lembrete['prioridade']) {
                                    case 'Urgente':
                                        $priorityClass = 'badge--urgent';
                                        break;
                                    case 'Alta':
                                        $priorityClass = 'badge--high';
                                        break;
                                    case 'Normal':
                                        $priorityClass = 'badge--normal';
                                        break;
                                    case 'Baixa':
                                        $priorityClass = 'badge--low';
                                        break;
                                }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($lembrete['titulo']) ?></td>
                                <td><?= htmlspecialchars($lembrete['para']) ?></td>
                                <td>
                                    <span class="badge <?= $priorityClass ?>">
                                        <?= htmlspecialchars($lembrete['prioridade']) ?>
                                    </span>
                                </td>
                                <td><?= $data_inicio->format('d/m/Y H:i') ?></td>
                                <td><?= $data_fim->format('d/m/Y H:i') ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_View&id=<?= $lembrete['id'] ?>" class="action-button action-button--view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="dashboard__see-all">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="see-all-link">
                    Ver todos os lembretes <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Latest Service Requests -->
    <div class="dashboard__recent">
        <h2 class="dashboard__section-title">Atendimentos Recentes</h2>
        
        <?php if (empty($ultimosAtendimentos)): ?>
            <div class="empty-state">
                <p>Nenhum atendimento pendente encontrado.</p>
                <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Create" class="primary-button">
                    Adicionar Atendimento
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Email</th>
                            <th>Origem</th>
                            <th width="100">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ultimosAtendimentos as $atendimento): ?>
                            <tr>
                                <td><?= formatDate($atendimento['data']) ?></td>
                                <td><?= htmlspecialchars($atendimento['nome']) ?></td>
                                <td><?= htmlspecialchars($atendimento['telefone']) ?></td>
                                <td><?= htmlspecialchars($atendimento['email']) ?></td>
                                <td>
                                    <span class="badge badge--<?= strtolower($atendimento['local']) ?>">
                                        <?= htmlspecialchars($atendimento['local']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_View&id=<?= $atendimento['id'] ?>" class="action-button action-button--view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="dashboard__see-all">
                <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento" class="see-all-link">
                    Ver todos os atendimentos <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Badge styles for priorities and service request sources */
.badge--urgent {
    background-color: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.badge--high {
    background-color: rgba(253, 126, 20, 0.2);
    color: #fd7e14;
}

.badge--normal {
    background-color: rgba(0, 123, 255, 0.2);
    color: #007bff;
}

.badge--low {
    background-color: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.badge--site {
    background-color: rgba(102, 16, 242, 0.2);
    color: #6610f2;
}

.badge--whatsapp {
    background-color: rgba(37, 211, 102, 0.2);
    color: #25D366;
}

.badge--telefone {
    background-color: rgba(253, 126, 20, 0.2);
    color: #fd7e14;
}

.badge--pessoal {
    background-color: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}
</style>