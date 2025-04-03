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
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

// Get events for this month using function from admin_functions.php
$events = getMonthEvents($month, $year);

// Organize events by day
$eventsByDay = [];
foreach ($events as $event) {
    $eventStart = new DateTime($event['data_inicio']);
    $eventEnd = new DateTime($event['data_fim']);
    
    // Create a period from start to end date
    $interval = new DateInterval('P1D');
    $dateRange = new DatePeriod($eventStart, $interval, $eventEnd->modify('+1 day'));
    
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
        </div>
    </div>
</div>