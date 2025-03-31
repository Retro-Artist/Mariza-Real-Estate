<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do cliente não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
    exit;
}

$client_id = (int)$_GET['id'];

// Get client data using our function from admin_functions.php
$cliente = getAdminClientById($client_id);

if (!$cliente) {
    $_SESSION['alert_message'] = 'Cliente não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Client_Admin');
    exit;
}

// Format the date of birth if exists
$data_nascimento = !empty($cliente['data_nascimento']) ? date('d/m/Y', strtotime($cliente['data_nascimento'])) : '00/00/0000';
?>

<!-- HTML content remains unchanged -->

<div class="admin-page client-view">
    <!-- Page Header -->
    <div class="page-header">
        <h2 class="page-title">Detalhes do Cliente</h2>
        <div class="action-buttons">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $client_id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
    
    <!-- Client Details Card -->
    <div class="client-details-card">
        <div class="card-header">
            <h3>Informações Detalhadas</h3>
        </div>
        
        <div class="card-body">
            <div class="details-row">
                <div class="detail-group">
                    <label>Cadastro:</label>
                    <?php if ($cliente['principal'] === 'Sim'): ?>
                        <span class="value badge-principal">Principal</span>
                    <?php else: ?>
                        <span class="value">Regular</span>
                    <?php endif; ?>
                </div>
                
                <div class="detail-group">
                    <label>Tipo de Cadastro:</label>
                    <span class="value"><?= htmlspecialchars($cliente['tipo']) ?> - <?= htmlspecialchars($cliente['categoria']) ?></span>
                </div>
            </div>
            
            <div class="details-row">
                <div class="detail-group">
                    <label>Nome Completo:</label>
                    <span class="value"><?= htmlspecialchars($cliente['nome_completo'] ?: '-') ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Data de Nascimento:</label>
                    <span class="value"><?= $data_nascimento ?></span>
                </div>
            </div>
            
            <?php if ($cliente['tipo'] === 'Pessoa Física'): ?>
            <div class="details-row">
                <div class="detail-group">
                    <label>CPF:</label>
                    <span class="value"><?= !empty($cliente['cpf']) ? htmlspecialchars($cliente['cpf']) : '' ?></span>
                </div>
                
                <div class="detail-group">
                    <label>RG:</label>
                    <span class="value"><?= !empty($cliente['rg']) ? htmlspecialchars($cliente['rg']) : '' ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="details-row">
                <div class="detail-group">
                    <label>Razão Social:</label>
                    <span class="value"><?= !empty($cliente['razao_social']) ? htmlspecialchars($cliente['razao_social']) : '' ?></span>
                </div>
                
                <div class="detail-group">
                    <label>CNPJ:</label>
                    <span class="value"><?= !empty($cliente['cnpj']) ? htmlspecialchars($cliente['cnpj']) : '' ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="details-row">
                <div class="detail-group">
                    <label>Profissão:</label>
                    <span class="value"><?= !empty($cliente['profissao']) ? htmlspecialchars($cliente['profissao']) : '' ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Telefone:</label>
                    <span class="value"><?= !empty($cliente['telefone1']) ? htmlspecialchars($cliente['telefone1']) : '' ?></span>
                </div>
            </div>
            
            <div class="details-row">
                <div class="detail-group">
                    <label>Telfone 2:</label>
                    <span class="value"><?= !empty($cliente['telefone2']) ? htmlspecialchars($cliente['telefone2']) : '' ?></span>
                </div>
                
                <div class="detail-group">
                    <label>E-mail:</label>
                    <span class="value"><?= !empty($cliente['email']) ? htmlspecialchars($cliente['email']) : '' ?></span>
                </div>
            </div>
            
            <div class="details-row">
                <div class="detail-group">
                    <label>Endereço:</label>
                    <span class="value"><?= !empty($cliente['endereco']) ? htmlspecialchars($cliente['endereco']) : '' ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Estado:</label>
                    <span class="value">
                        <?php if (!empty($cliente['estado_nome']) && !empty($cliente['estado_uf'])): ?>
                            <?= htmlspecialchars($cliente['estado_nome']) ?> - <?= htmlspecialchars($cliente['estado_uf']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <div class="details-row">
                <div class="detail-group">
                    <label>Cidade:</label>
                    <span class="value"><?= !empty($cliente['cidade_nome']) ? htmlspecialchars($cliente['cidade_nome']) : '-' ?></span>
                </div>
                
                <div class="detail-group">
                    <label>Bairro:</label>
                    <span class="value"><?= !empty($cliente['bairro_nome']) ? htmlspecialchars($cliente['bairro_nome']) : '-' ?></span>
                </div>
            </div>
            
            <div class="details-row observation">
                <div class="detail-group full-width">
                    <label>Observação:</label>
                    <div class="observation-text">
                        <?= !empty($cliente['observacoes']) ? nl2br(htmlspecialchars($cliente['observacoes'])) : '-' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="action-bar">
        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Admin" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar para Lista
        </a>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Update&id=<?= $client_id ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Editar Cliente
        </a>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Delete&id=<?= $client_id ?>" class="btn btn-danger">
            <i class="fas fa-trash"></i> Excluir Cliente
        </a>
    </div>
</div>

<style>
/* Client View Styles */
.client-view {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.page-title {
    font-size: 24px;
    color: #333;
    margin: 0;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

/* Card Styles */
.client-details-card {
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-header {
    padding: 15px 20px;
    background-color: #f8f8f8;
    border-bottom: 1px solid #ddd;
}

.card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 500;
    color: #333;
}

.card-body {
    padding: 20px;
}

/* Details Layout */
.details-row {
    display: flex;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.detail-group {
    flex: 1;
    min-width: 300px;
    display: flex;
    flex-direction: column;
}

.detail-group.full-width {
    flex-basis: 100%;
    min-width: 100%;
}

.detail-group label {
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
}

.detail-group .value {
    font-size: 16px;
    color: #333;
}

/* Badge for Principal */
.badge-principal {
    display: inline-block;
    background-color: #4CAF50;
    color: white;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 14px;
    font-weight: 600;
}

/* Observation Styling */
.observation {
    margin-top: 30px;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.observation-text {
    padding: 15px;
    background-color: #f9f9f9;
    border-radius: 4px;
    min-height: 80px;
}

/* Action Buttons */
.action-bar {
    display: flex;
    justify-content: flex-start;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-2px);
}

.btn-primary {
    background-color: #4CAF50;
    color: white;
}

.btn-secondary {
    background-color: #f5f5f5;
    color: #333;
    border: 1px solid #ddd;
}

.btn-danger {
    background-color: #f44336;
    color: white;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .details-row {
        flex-direction: column;
    }
    
    .detail-group {
        margin-bottom: 15px;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .action-bar {
        flex-direction: column;
        width: 100%;
    }
    
    .action-bar .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>