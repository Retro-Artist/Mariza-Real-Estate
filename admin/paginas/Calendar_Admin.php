<?php
// Security check
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/admin/Admin_Login.php');
    exit;
}

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Make sure month is between 1-12
if ($month < 1) {
    $month = 12;
    $year--;
} elseif ($month > 12) {
    $month = 1;
    $year++;
}

// Get first day of the month
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$numberDays = date('t', $firstDayOfMonth);
$firstDayOfWeek = date('w', $firstDayOfMonth);

// Month names for display
$monthNames = [
    1 => 'Janeiro',
    2 => 'Fevereiro',
    3 => 'Março',
    4 => 'Abril',
    5 => 'Maio',
    6 => 'Junho',
    7 => 'Julho',
    8 => 'Agosto',
    9 => 'Setembro',
    10 => 'Outubro',
    11 => 'Novembro',
    12 => 'Dezembro'
];

// Get events for this month using function from admin_functions.php
$events = getMonthEvents($month, $year);

// Organize events by day
$eventsByDay = [];
foreach ($events as $event) {
    $eventStart = new DateTime($event['data_inicio']);
    $eventEnd = new DateTime($event['data_fim']);
    
    // FIX: Create a date period that correctly ends on the end date (inclusive)
    // without adding an extra day
    $interval = new DateInterval('P1D');
    // Clone the end date to avoid modifying the original
    $endDateClone = clone $eventEnd;
    // Add 1 second to make the end date inclusive when iterating with DatePeriod
    $endDateClone->modify('+1 second');
    $dateRange = new DatePeriod($eventStart, $interval, $endDateClone);

    // Add event to each day in the range
    foreach ($dateRange as $date) {
        $day = $date->format('j');
        if ($date->format('n') == $month && $date->format('Y') == $year) {
            if (!isset($eventsByDay[$day])) {
                $eventsByDay[$day] = [];
            }
            $eventsByDay[$day][] = $event;
        }
    }
}

// Count reminders for today for notification indicator
$today = date('Y-m-d');
try {
    $stmt = $databaseConnection->prepare(
        "SELECT COUNT(*) as total FROM sistema_avisos 
         WHERE DATE(data_inicio) <= :today AND DATE(data_fim) >= :today"
    );
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $todayRemindersCount = $stmt->fetch()['total'];
} catch (PDOException $e) {
    logError("Error counting today's reminders: " . $e->getMessage());
    $todayRemindersCount = 0;
}

// Get today's reminders for notification dropdown
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_avisos 
         WHERE DATE(data_inicio) <= :today AND DATE(data_fim) >= :today
         ORDER BY data_inicio ASC 
         LIMIT 5"
    );
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $todayReminders = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching today's reminders: " . $e->getMessage());
    $todayReminders = [];
}

// Get the 6 most recent reminders
try {
    $stmt = $databaseConnection->prepare(
        "SELECT * FROM sistema_avisos 
         ORDER BY data_inicio DESC 
         LIMIT 6"
    );
    $stmt->execute();
    $recentReminders = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching recent reminders: " . $e->getMessage());
    $recentReminders = [];
}

// Get available users for assignment (for the modal form)
try {
    $stmt = $databaseConnection->query("SELECT id, nome FROM sistema_usuarios ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    logError("Error fetching users: " . $e->getMessage());
    $usuarios = [];
}

// Previous and next month links
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}
?>

<div class="admin-page calendar-page">
    <!-- Add notification indicator to the header via JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the user element in the topbar
            const topbarUser = document.querySelector('.admin-topbar__user');

            // Create the notification indicator
            const notificationIndicator = document.createElement('div');
            notificationIndicator.classList.add('notification-indicator');
            notificationIndicator.innerHTML = `
                <i class="fas fa-bell"></i>
                <?php if ($todayRemindersCount > 0): ?>
                <span class="notification-count"><?= $todayRemindersCount ?></span>
                <?php endif; ?>
            `;

            // Create the dropdown
            const notificationDropdown = document.createElement('div');
            notificationDropdown.classList.add('notification-dropdown');
            notificationDropdown.innerHTML = `
                <div class="notification-dropdown__header">
                    Lembretes de Hoje
                </div>
                <?php if (empty($todayReminders)): ?>
                <div class="notification-dropdown__item">
                    <p>Nenhum lembrete para hoje.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($todayReminders as $reminder): ?>
                    <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_View&id=<?= $reminder['id'] ?>" class="notification-dropdown__item">
                        <div class="notification-dropdown__title"><?= htmlspecialchars($reminder['titulo']) ?></div>
                        <div class="notification-dropdown__meta">
                            <span><?= $reminder['prioridade'] ?></span>
                            <span><?= (new DateTime($reminder['data_inicio']))->format('H:i') ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            `;

            // Add click event to toggle dropdown
            notificationIndicator.addEventListener('click', function(e) {
                e.stopPropagation();
                notificationDropdown.classList.toggle('active');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function() {
                notificationDropdown.classList.remove('active');
            });

            // Prevent dropdown from closing when clicking inside it
            notificationDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            // Append elements to the DOM
            notificationIndicator.appendChild(notificationDropdown);
            topbarUser.insertAdjacentElement('beforebegin', notificationIndicator);
        });
    </script>

    <div class="calendar-layout">
        <!-- Calendar Column -->
        <div class="calendar-column">
            <div class="admin-card">
                <div class="calendar-header">
                    <div class="calendar-nav">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar&month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="calendar-nav__arrow">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <h3 class="calendar-nav__title"><?= $monthNames[$month] ?> <?= $year ?></h3>
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar&month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="calendar-nav__arrow">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <div class="calendar">
                    <div class="calendar__weekdays">
                        <div class="calendar__day-name">Dom</div>
                        <div class="calendar__day-name">Seg</div>
                        <div class="calendar__day-name">Ter</div>
                        <div class="calendar__day-name">Qua</div>
                        <div class="calendar__day-name">Qui</div>
                        <div class="calendar__day-name">Sex</div>
                        <div class="calendar__day-name">Sáb</div>
                    </div>

                    <div class="calendar__days">
                        <?php
                        // Fill empty cells for days before the first day of month
                        for ($i = 0; $i < $firstDayOfWeek; $i++) {
                            echo '<div class="calendar__day calendar__day--empty"></div>';
                        }

                        // Output days of the month
                        for ($day = 1; $day <= $numberDays; $day++) {
                            $isToday = ($day == date('j') && $month == date('n') && $year == date('Y'));
                            $dayClass = $isToday ? 'calendar__day calendar__day--today' : 'calendar__day';

                            echo '<div class="' . $dayClass . '">';
                            echo '<div class="calendar__day-number">' . $day . '</div>';

                            // Output events for this day
                            if (isset($eventsByDay[$day]) && !empty($eventsByDay[$day])) {
                                echo '<div class="calendar__events">';
                                foreach ($eventsByDay[$day] as $event) {
                                    $priorityClass = '';
                                    switch ($event['prioridade']) {
                                        case 'Urgente':
                                            $priorityClass = 'event--urgent';
                                            break;
                                        case 'Alta':
                                            $priorityClass = 'event--high';
                                            break;
                                        case 'Normal':
                                            $priorityClass = 'event--normal';
                                            break;
                                        case 'Baixa':
                                            $priorityClass = 'event--low';
                                            break;
                                    }

                                    echo '<a href="' . BASE_URL . '/admin/index.php?page=Calendar_View&id=' . $event['id'] . '" ';
                                    echo 'class="calendar__event ' . $priorityClass . '">';
                                    echo htmlspecialchars($event['titulo']);
                                    echo '</a>';
                                }
                                echo '</div>';

                                // Add indicator dot
                                $highestPriority = 'normal';
                                foreach ($eventsByDay[$day] as $event) {
                                    if ($event['prioridade'] === 'Urgente') {
                                        $highestPriority = 'urgent';
                                        break;
                                    } else if ($event['prioridade'] === 'Alta' && $highestPriority !== 'urgent') {
                                        $highestPriority = 'high';
                                    } else if ($event['prioridade'] === 'Baixa' && $highestPriority === 'normal') {
                                        $highestPriority = 'low';
                                    }
                                }

                                echo '<div class="calendar__day-indicator">';
                                echo '<div class="calendar__day-dot priority--' . $highestPriority . '"></div>';
                                echo '</div>';
                            }

                            echo '</div>';
                        }

                        // Fill empty cells after the last day of the month
                        $totalCells = $firstDayOfWeek + $numberDays;
                        $remaining = 7 - ($totalCells % 7);
                        if ($remaining < 7) {
                            for ($i = 0; $i < $remaining; $i++) {
                                echo '<div class="calendar__day calendar__day--empty"></div>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Legend for event priorities -->
            <div class="admin-card calendar-legend">
                <h3>Legenda de Prioridades</h3>
                <div class="legend-items">
                    <div class="legend-item">
                        <div class="legend-color event--urgent"></div>
                        <div class="legend-label">Urgente</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color event--high"></div>
                        <div class="legend-label">Alta</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color event--normal"></div>
                        <div class="legend-label">Normal</div>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color event--low"></div>
                        <div class="legend-label">Baixa</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reminders Column -->
        <div class="reminders-column">
            <div class="admin-card">
                <h3 class="card-title">Lembretes Recentes</h3>

                <?php if (empty($recentReminders)): ?>
                    <div class="empty-state">
                        <p>Nenhum lembrete cadastrado.</p>
                    </div>
                <?php else: ?>
                    <div class="reminders-list">
                        <?php foreach ($recentReminders as $reminder): ?>
                            <?php
                            $reminderDate = new DateTime($reminder['data_inicio']);

                            $priorityClass = '';
                            switch ($reminder['prioridade']) {
                                case 'Urgente':
                                    $priorityClass = 'priority--urgent';
                                    break;
                                case 'Alta':
                                    $priorityClass = 'priority--high';
                                    break;
                                case 'Normal':
                                    $priorityClass = 'priority--normal';
                                    break;
                                case 'Baixa':
                                    $priorityClass = 'priority--low';
                                    break;
                            }
                            ?>
                            <div class="reminder-item">
                                <div class="reminder-header">
                                    <div class="reminder-priority <?= $priorityClass ?>"></div>
                                    <span class="reminder-date"><?= $reminderDate->format('d/m/Y') ?></span>
                                    <span class="reminder-status status--<?= strtolower($reminder['status']) ?>">
                                        <?= htmlspecialchars($reminder['status']) ?>
                                    </span>
                                </div>
                                <h4 class="reminder-title">
                                    <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_View&id=<?= $reminder['id'] ?>">
                                        <?= htmlspecialchars($reminder['titulo']) ?>
                                    </a>
                                </h4>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="reminders-actions">
                        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar" class="see-all-link">
                            Ver todos os lembretes <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Create Button -->
            <div class="admin-card">
                <div class="quick-create">
                    <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Create" class="primary-button">
                        <i class="fas fa-plus"></i> Criar Novo Lembrete
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Day Modal -->
<div class="calendar-modal" id="calendar-day-modal">
    <div class="calendar-modal__content">
        <div class="calendar-modal__header">
            <h3 class="calendar-modal__title">Novo Lembrete</h3>
            <button class="calendar-modal__close">&times;</button>
        </div>
        <div class="calendar-modal__body">
            <!-- Quick Reminder Form -->
            <form method="POST" action="<?= BASE_URL ?>/admin/index.php" id="calendar-reminder-form" class="admin-form">
                <input type="hidden" name="action" value="quick_create_reminder">
                <input type="hidden" name="selected_date" id="selected_date" value="">

                <div class="form-group form-group--full">
                    <label for="modal_titulo">Título <span class="required">*</span></label>
                    <input type="text" id="modal_titulo" name="titulo" class="form-control" required>
                </div>

                <div class="form-group form-group--full">
                    <label for="modal_descricao">Descrição</label>
                    <textarea id="modal_descricao" name="descricao" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_para">Para</label>
                        <select id="modal_para" name="para" class="form-control">
                            <option value="Todos">Todos os Usuários</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?= htmlspecialchars($usuario['nome']) ?>">
                                    <?= htmlspecialchars($usuario['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="modal_prioridade">Prioridade</label>
                        <select id="modal_prioridade" name="prioridade" class="form-control">
                            <option value="Baixa">Baixa</option>
                            <option value="Normal" selected>Normal</option>
                            <option value="Alta">Alta</option>
                            <option value="Urgente">Urgente</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="modal_hora_inicio">Hora de Início</label>
                        <input type="time" id="modal_hora_inicio" name="hora_inicio" class="form-control" value="<?= date('H:i') ?>">
                    </div>

                    <div class="form-group">
                        <label for="data_fim">Data de Término <span class="required">*</span></label>
                        <input type="date" id="data_fim" name="data_fim" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="modal_hora_fim">Hora de Término</label>
                        <input type="time" id="modal_hora_fim" name="hora_fim" class="form-control" value="<?= date('H:i') ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="primary-button">
                        <i class="fas fa-save"></i> Salvar Lembrete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Load the calendar modal script -->
<script src="<?= BASE_URL ?>/assets/scripts/calendar-modal.js"></script>

<script>
    // Debug listener to check if calendar days are clickable
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Calendar days setup in progress...');
        const days = document.querySelectorAll('.calendar__day:not(.calendar__day--empty)');

        console.log(`Found ${days.length} calendar days`);

        days.forEach(day => {
            day.addEventListener('click', function() {
                console.log('Day clicked:', this.querySelector('.calendar__day-number').textContent);
            });
        });
    });
</script>