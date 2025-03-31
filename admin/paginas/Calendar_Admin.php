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

<!-- HTML content remains unchanged -->

<div class="admin-page calendar-page">
    <div class="admin-page__header">
        <h2 class="admin-page__title">Calendário</h2>
        <a href="<?= BASE_URL ?>/admin/index.php?page=Calendar_Create" class="primary-button">
            <i class="fas fa-plus"></i> Novo Lembrete
        </a>
    </div>
    
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

<style>
/* Calendar Styles */
.calendar {
    width: 100%;
    overflow: hidden;
}

.calendar-header {
    margin-bottom: 20px;
}

.calendar-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
}

.calendar-nav__title {
    font-size: var(--font-xl);
    font-family: var(--font-secondary);
    text-align: center;
    margin: 0;
}

.calendar-nav__arrow {
    font-size: var(--font-xl);
    color: var(--admin-primary);
    cursor: pointer;
    text-decoration: none;
}

.calendar__weekdays {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-weight: 600;
    padding: 10px 0;
    border-bottom: 1px solid var(--admin-border);
}

.calendar__days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    grid-gap: 1px;
    background-color: var(--admin-border);
}

.calendar__day {
    background-color: var(--admin-card-bg);
    min-height: 120px;
    padding: 5px;
    position: relative;
}

.calendar__day--empty {
    background-color: #f9f9f9;
}

.calendar__day--today {
    background-color: #f0f8ff;
}

.calendar__day-number {
    font-weight: 600;
    margin-bottom: 5px;
}

.calendar__events {
    display: flex;
    flex-direction: column;
    gap: 2px;
    font-size: var(--font-xs);
}

.calendar__event {
    padding: 3px 5px;
    border-radius: 3px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    text-decoration: none;
    color: #fff;
}

.event--urgent {
    background-color: #dc3545;
}

.event--high {
    background-color: #fd7e14;
}

.event--normal {
    background-color: #007bff;
}

.event--low {
    background-color: #6c757d;
}

/* Legend Styles */
.calendar-legend {
    margin-top: 20px;
}

.legend-items {
    display: flex;
    gap: 20px;
    margin-top: 10px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 3px;
}

.legend-label {
    font-size: var(--font-sm);
}

/* Responsive Calendar */
@media (max-width: 768px) {
    .calendar__day {
        min-height: 80px;
    }
    
    .calendar__event {
        font-size: 9px;
        padding: 2px;
    }
    
    .legend-items {
        flex-wrap: wrap;
    }
}
</style>