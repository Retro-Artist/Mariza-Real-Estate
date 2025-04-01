/**
 * Calendar Functions
 * Handles notification dots and event lightboxes
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize calendar dots
    initCalendarDots();
    
    // Initialize lightbox functionality
    initLightbox();
    
    // Initialize mobile responsiveness
    checkWindowSize();
    window.addEventListener('resize', checkWindowSize);
});

/**
 * Initialize calendar event notification dots
 */
function initCalendarDots() {
    // Get all calendar days and event data
    const calendarDays = document.querySelectorAll('.calendar__day:not(.calendar__day--empty)');
    
    // For each calendar day, check if there are events
    calendarDays.forEach(day => {
        const dayEvents = day.querySelector('.calendar__events');
        if (dayEvents && dayEvents.children.length > 0) {
            // Create notification dot for days with events
            const dot = document.createElement('div');
            dot.className = 'calendar__day-dot';
            dot.dataset.date = day.querySelector('.calendar__day-number').textContent;
            
            // Add click event to show lightbox with events for that day
            dot.addEventListener('click', function(e) {
                e.stopPropagation();
                showEventsLightbox(day);
            });
            
            day.appendChild(dot);
        }
    });
}

/**
 * Show events lightbox for a specific day
 * @param {Element} day The calendar day element
 */
function showEventsLightbox(day) {
    const dayNumber = day.querySelector('.calendar__day-number').textContent;
    const events = day.querySelectorAll('.calendar__event');
    const eventIds = [];
    
    // Collect all event IDs for this day
    events.forEach(event => {
        const eventId = event.href.split('id=')[1];
        eventIds.push(eventId);
    });
    
    // If no events, don't show lightbox
    if (eventIds.length === 0) return;
    
    // Get event details via AJAX
    getEventDetails(eventIds, function(eventDetails) {
        // Create and display lightbox
        createLightbox(dayNumber, eventDetails);
    });
}

/**
 * Get event details via AJAX
 * @param {Array} eventIds Array of event IDs
 * @param {Function} callback Callback function with event details
 */
function getEventDetails(eventIds, callback) {
    // Real AJAX call to the server
    const url = `${BASE_URL}/admin/ajax/get_events.php?ids=${eventIds.join(',')}`;
    
    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                console.error('Error fetching events:', data.error);
                // Fallback to DOM data if AJAX fails
                fallbackToDomData(eventIds, callback);
                return;
            }
            
            if (data.events && data.events.length > 0) {
                callback(data.events);
            } else {
                // Fallback to DOM data if no events returned
                fallbackToDomData(eventIds, callback);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            // Fallback to DOM data if AJAX fails
            fallbackToDomData(eventIds, callback);
        });
}

/**
 * Fallback function to extract event data from DOM
 * @param {Array} eventIds Array of event IDs
 * @param {Function} callback Callback function with event details
 */
function fallbackToDomData(eventIds, callback) {
    const eventDetails = [];
    
    // For each event ID, get its anchor element to extract data
    eventIds.forEach(id => {
        const eventLink = document.querySelector(`a[href$="id=${id}"]`);
        if (eventLink) {
            // Extract event class to determine priority
            let priority = 'Normal';
            if (eventLink.classList.contains('event--urgent')) priority = 'Urgente';
            else if (eventLink.classList.contains('event--high')) priority = 'Alta';
            else if (eventLink.classList.contains('event--low')) priority = 'Baixa';
            
            // Get the content information
            eventDetails.push({
                id: id,
                titulo: eventLink.textContent.trim(),
                prioridade: priority,
                // We don't have this information in the DOM, so we'll use placeholder
                hora_inicio: '08:00',
                para: 'Todos',
                data_inicio: 'Hoje'
            });
        }
    });
    
    callback(eventDetails);
}

/**
 * Create and display lightbox with event details
 * @param {String} dayNumber The day number
 * @param {Array} events Array of event details
 */
function createLightbox(dayNumber, events) {
    // Check if lightbox already exists
    let lightbox = document.querySelector('.calendar-lightbox');
    if (!lightbox) {
        // Create new lightbox
        lightbox = document.createElement('div');
        lightbox.className = 'calendar-lightbox';
        document.body.appendChild(lightbox);
    }
    
    // Current month and year from the calendar title
    const calendarTitle = document.querySelector('.calendar-nav__title').textContent;
    const [month, year] = calendarTitle.split(' ');
    
    // Create lightbox content
    const content = `
        <div class="calendar-lightbox__content">
            <div class="calendar-lightbox__header">
                <h3 class="calendar-lightbox__title">Lembretes do dia ${dayNumber} de ${month} de ${year}</h3>
                <button type="button" class="calendar-lightbox__close">&times;</button>
            </div>
            <div class="calendar-lightbox__body">
                ${events.map(event => `
                    <div class="event-item">
                        <h4 class="event-item__title">${event.titulo || event.title}</h4>
                        <div class="event-item__details">
                            <p><strong>Horário:</strong> ${event.hora_inicio || event.time}</p>
                            <p><strong>Prioridade:</strong> <span class="badge priority-${(event.prioridade || event.priority).toLowerCase()}">${event.prioridade || event.priority}</span></p>
                            <p><strong>Para:</strong> ${event.para}</p>
                            ${event.descricao ? `<p><strong>Descrição:</strong> ${event.descricao}</p>` : ''}
                        </div>
                        <div class="event-item__actions">
                            <a href="${BASE_URL}/admin/index.php?page=Calendar_View&id=${event.id}" class="btn btn-sm btn-primary">Ver Detalhes</a>
                        </div>
                    </div>
                `).join('<hr>')}
            </div>
            <div class="calendar-lightbox__footer">
                <button type="button" class="cancel-button lightbox-close">Fechar</button>
                <a href="${BASE_URL}/admin/index.php?page=Calendar_Create" class="primary-button">
                    <i class="fas fa-plus"></i> Novo Lembrete
                </a>
            </div>
        </div>
    `;
    
    lightbox.innerHTML = content;
    lightbox.classList.add('active');
    
    // Add close button functionality
    const closeButtons = lightbox.querySelectorAll('.lightbox-close, .calendar-lightbox__close');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            lightbox.classList.remove('active');
        });
    });
    
    // Close lightbox when clicking outside content
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            lightbox.classList.remove('active');
        }
    });
}

/**
 * Check window size and adjust layout for responsive design
 */
function checkWindowSize() {
    const calendarContainer = document.querySelector('.calendar-container');
    if (!calendarContainer) return;
    
    if (window.innerWidth < 992) {
        calendarContainer.classList.add('calendar-container--mobile');
    } else {
        calendarContainer.classList.remove('calendar-container--mobile');
    }
}

/**
 * Load upcoming reminders for sidebar
 * @param {String} url API endpoint to fetch reminders
 */
function loadUpcomingReminders(url) {
    // In a production environment, this would fetch data from the server
    // For now, we'll use the existing reminders in the DOM
    const remindersList = document.querySelector('.calendar-reminders');
    if (!remindersList) return;
    
    const existingReminders = document.querySelectorAll('.calendar__event');
    if (existingReminders.length === 0) {
        remindersList.innerHTML = `
            <div class="calendar-no-reminders">
                <p>Nenhum lembrete encontrado para este mês.</p>
                <a href="${BASE_URL}/admin/index.php?page=Calendar_Create" class="primary-button">
                    <i class="fas fa-plus"></i> Adicionar Lembrete
                </a>
            </div>
        `;
        return;
    }
    
    // Get a sample of reminders (up to 10)
    const reminders = Array.from(existingReminders).slice(0, 10);
    
    // Transform into reminder items
    const reminderItems = reminders.map(reminder => {
        // Extract priority class
        let priorityClass = 'priority-normal';
        if (reminder.classList.contains('event--urgent')) priorityClass = 'priority-urgente';
        else if (reminder.classList.contains('event--high')) priorityClass = 'priority-alta';
        else if (reminder.classList.contains('event--low')) priorityClass = 'priority-baixa';
        
        // Extract event ID
        const eventId = reminder.href.split('id=')[1];
        
        return `
            <div class="calendar-reminder">
                <div class="calendar-reminder__date">05/04/2025 às 08:00</div>
                <a href="${BASE_URL}/admin/index.php?page=Calendar_View&id=${eventId}" class="calendar-reminder__title">
                    ${reminder.textContent.trim()}
                </a>
                <div class="calendar-reminder__meta">
                    <span class="calendar-reminder__priority ${priorityClass}">
                        ${priorityClass.replace('priority-', '')}
                    </span>
                    <span class="calendar-reminder__for">Para: Todos</span>
                </div>
            </div>
        `;
    }).join('');
    
    remindersList.innerHTML = reminderItems;
}