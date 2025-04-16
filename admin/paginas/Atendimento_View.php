<?php
// admin/paginas/Atendimento_View.php

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'ID do atendimento não especificado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
    exit;
}

$atendimento_id = (int)$_GET['id'];

// Get service request using function from admin_functions.php
$atendimento = getServiceRequestById($atendimento_id);

if (!$atendimento) {
    $_SESSION['alert_message'] = 'Atendimento não encontrado.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_Admin');
    exit;
}

// Process status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];

    // Update service request status (just the status field)
    $result = updateServiceRequest($atendimento_id, [
        'nome' => $atendimento['nome'],
        'email' => $atendimento['email'],
        'telefone' => $atendimento['telefone'],
        'mensagem' => $atendimento['mensagem'],
        'local' => $atendimento['local'],
        'status' => $new_status
    ]);

    if ($result) {
        $_SESSION['alert_message'] = 'Status atualizado com sucesso!';
        $_SESSION['alert_type'] = 'success';

        // Refresh the page to show updated data
        header('Location: ' . BASE_URL . '/admin/index.php?page=Atendimento_View&id=' . $atendimento_id);
        exit;
    } else {
        $error = 'Ocorreu um erro ao atualizar o status.';
    }
}

// Get status class for badge
$statusClass = '';
switch ($atendimento['status']) {
    case 'Pendente':
        $statusClass = 'badge--pending';
        break;
    case 'Em Andamento':
        $statusClass = 'badge--progress';
        break;
    case 'Concluído':
        $statusClass = 'badge--complete';
        break;
    case 'Cancelado':
        $statusClass = 'badge--canceled';
        break;
}

// Get source class for badge
$sourceClass = 'badge--' . strtolower($atendimento['local']);
?>

<div class="admin-page atendimento-view">
    <!-- Page Header -->
    <div class="admin-page__header">
        <h2 class="admin-page__title">Detalhes do Atendimento</h2>
        <div class="admin-page__actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento_id ?>" class="primary-button">
                <i class="fas fa-edit"></i> Editar
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Admin" class="cancel-button">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <!-- Atendimento Details Card -->
    <div class="admin-card">
        <div class="atendimento-header">
            <div class="atendimento-meta">
                <div class="atendimento-id">Atendimento #<?= $atendimento['id'] ?></div>
                <div class="atendimento-date">
                    <i class="fas fa-calendar"></i> <?= formatDate($atendimento['data']) ?> às <?= $atendimento['hora'] ?>
                </div>
            </div>
            <div class="atendimento-badges">
                <span class="badge <?= $sourceClass ?>">
                    <?= htmlspecialchars($atendimento['local']) ?>
                </span>
                <span class="badge <?= $statusClass ?>">
                    <?= htmlspecialchars($atendimento['status']) ?>
                </span>
            </div>
        </div>

        <div class="atendimento-details">
            <div class="detail-section">
                <h3 class="detail-section-title">Informações do Contato</h3>

                <div class="detail-row">
                    <div class="detail-group">
                        <label>Nome:</label>
                        <div class="detail-value"><?= htmlspecialchars($atendimento['nome']) ?></div>
                    </div>

                    <div class="detail-group">
                        <label>Email:</label>
                        <div class="detail-value">
                            <a href="mailto:<?= htmlspecialchars($atendimento['email']) ?>">
                                <?= htmlspecialchars($atendimento['email']) ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-group">
                        <label>Telefone:</label>
                        <div class="detail-value">
                            <?php if (!empty($atendimento['telefone'])): ?>
                                <a href="tel:<?= htmlspecialchars($atendimento['telefone']) ?>">
                                    <?= htmlspecialchars($atendimento['telefone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="no-data">Não informado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="detail-group">
                        <label>WhatsApp:</label>
                        <div class="detail-value">
                            <?php if (!empty($atendimento['telefone'])): ?>
                                <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/\D/', '', $atendimento['telefone']) ?>" target="_blank">
                                    Enviar mensagem <i class="fab fa-whatsapp"></i>
                                </a>
                            <?php else: ?>
                                <span class="no-data">Não disponível</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-section">
                <h3 class="detail-section-title">Mensagem</h3>
                <div class="message-content">
                    <?= nl2br(htmlspecialchars($atendimento['mensagem'])) ?>
                </div>
            </div>
        </div>

        <!-- Status Update Form with aligned button -->
        <div class="status-update">
            <h3 class="detail-section-title">Atualizar Status</h3>
            <form method="POST" action="" class="status-form">
                <div class="status-form__row">
                    <div class="form-group status-select-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Pendente" <?= $atendimento['status'] === 'Pendente' ? 'selected' : '' ?>>Pendente</option>
                            <option value="Em Andamento" <?= $atendimento['status'] === 'Em Andamento' ? 'selected' : '' ?>>Em Andamento</option>
                            <option value="Concluído" <?= $atendimento['status'] === 'Concluído' ? 'selected' : '' ?>>Concluído</option>
                            <option value="Cancelado" <?= $atendimento['status'] === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="form-group status-button-group">
                        <button type="submit" name="update_status" value="1" class="primary-button">
                            <i class="fas fa-save"></i> Atualizar Status
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Actions with improved buttons -->
        <div class="atendimento-actions">
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Update&id=<?= $atendimento_id ?>" class="btn btn-edit">
                <i class="fas fa-edit"></i> Editar Atendimento
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Atendimento_Delete&id=<?= $atendimento_id ?>" class="btn btn-delete">
                <i class="fas fa-trash"></i> Excluir Atendimento
            </a>
            <a href="<?= BASE_URL ?>/admin/index.php?page=Client_Create" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Converter em Cliente
            </a>
        </div>

    </div>
</div>