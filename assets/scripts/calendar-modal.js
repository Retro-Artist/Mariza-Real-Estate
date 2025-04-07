/**
 * Calendar Modal Interaction Script
 * Handles showing reminders when clicking on calendar days
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const modal = document.getElementById('calendar-day-modal');
    const modalClose = modal.querySelector('.calendar-modal__close');
    const modalTitle = modal.querySelector('.calendar-modal__title');
    const calendarDays = document.querySelectorAll('.calendar__day:not(.calendar__day--empty)');
    const remindersList = document.getElementById('day-reminders-list');
    const selectedDateInput = document.getElementById('selected_date');
    const newReminderBtn = document.getElementById('new-reminder-btn');
    const reminderForm = document.getElementById('calendar-reminder-form');
    
    // Format date for display (Day, Month DD, YYYY)
    function formatDateForDisplay(year, month, day) {
        const date = new Date(year, month - 1, day);
        return date.toLocaleDateString('pt-BR', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }

    // Handle day click
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            // Get day number from the clicked day
            const dayNumber = this.querySelector('.calendar__day-number').textContent;
            
            // Get current month and year from URL or page
            const urlParams = new URLSearchParams(window.location.search);
            const month = urlParams.get('month') || new Date().getMonth() + 1;
            const year = urlParams.get('year') || new Date().getFullYear();
            
            // Format date for display and form input
            const formattedDisplayDate = formatDateForDisplay(year, month, dayNumber);
            const formattedInputDate = `${year}-${month.toString().padStart(2, '0')}-${dayNumber.toString().padStart(2, '0')}`;
            
            // Update modal title with formatted date
            modalTitle.textContent = `Lembretes para ${formattedDisplayDate}`;
            
            // Set the selected date in the form
            if (selectedDateInput) {
                selectedDateInput.value = formattedInputDate;
                
                // Also set the end date to match start date
                const dataFimInput = document.getElementById('data_fim');
                if (dataFimInput) {
                    dataFimInput.value = formattedInputDate;
                }
            }
            
            // Collect all events for this day from the data attributes
            const dayEvents = JSON.parse(this.getAttribute('data-events') || '[]');
            
            // Clear previous reminders
            remindersList.innerHTML = '';
            
            // If there are no events, show a message
            if (dayEvents.length === 0) {
                remindersList.innerHTML = `
                    <div class="no-reminders">
                        <p>Nenhum lembrete para este dia.</p>
                    </div>
                `;
            } else {
                // Add each event to the list
                dayEvents.forEach(event => {
                    let priorityClass = '';
                    switch (event.prioridade) {
                        case 'Urgente': priorityClass = 'priority--urgent'; break;
                        case 'Alta': priorityClass = 'priority--high'; break;
                        case 'Normal': priorityClass = 'priority--normal'; break;
                        case 'Baixa': priorityClass = 'priority--low'; break;
                    }
                    
                    const eventTime = new Date(event.data_inicio).toLocaleTimeString('pt-BR', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Truncate description to 200 characters if needed
                    let descriptionHtml = '';
                    if (event.descricao && event.descricao.trim() !== '') {
                        let description = event.descricao;
                        if (description.length > 200) {
                            description = description.substring(0, 200) + '...';
                        }
                        descriptionHtml = `<div class="reminder-list-item__description">${description}</div>`;
                    }
                    
                    const reminderItem = document.createElement('div');
                    reminderItem.className = `reminder-list-item ${priorityClass}`;
                    reminderItem.innerHTML = `
                        <div class="reminder-list-item__time">${eventTime}</div>
                        <div class="reminder-list-item__content">
                            <div class="reminder-list-item__title">${event.titulo}</div>
                            ${descriptionHtml}
                        </div>
                        <div class="reminder-list-item__actions">
                            <a href="${BASE_URL}/admin/index.php?page=Calendar_View&id=${event.id}" 
                               class="action-button action-button--view" title="Visualizar">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="${BASE_URL}/admin/index.php?page=Calendar_Update&id=${event.id}" 
                               class="action-button action-button--edit" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="${BASE_URL}/admin/index.php?page=Calendar_Delete&id=${event.id}" 
                               class="action-button action-button--delete" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    `;
                    
                    remindersList.appendChild(reminderItem);
                });
            }
            
            // Reset the form display
            if (reminderForm && newReminderBtn) {
                reminderForm.style.display = 'none';
                newReminderBtn.style.display = 'block';
            }
            
            // Show the modal
            modal.classList.add('active');
            document.body.classList.add('modal-open');
        });
    });
    
    // Toggle form visibility when clicking "Add New Reminder"
    if (newReminderBtn) {
        newReminderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            reminderForm.style.display = 'block';
            this.style.display = 'none';
        });
    }
    
    // Hide form when clicking the cancel button
    const cancelReminderBtn = document.getElementById('cancel-reminder-btn');
    if (cancelReminderBtn) {
        cancelReminderBtn.addEventListener('click', function() {
            reminderForm.style.display = 'none';
            newReminderBtn.style.display = 'block';
        });
    }
    
    // Close modal when clicking the close button
    modalClose.addEventListener('click', function() {
        modal.classList.remove('active');
        document.body.classList.remove('modal-open');
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
        }
    });
    
    // Prevent propagation of clicks inside the modal content
    const modalContent = modal.querySelector('.calendar-modal__content');
    if (modalContent) {
        modalContent.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
});