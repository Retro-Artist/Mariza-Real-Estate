<?php

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
