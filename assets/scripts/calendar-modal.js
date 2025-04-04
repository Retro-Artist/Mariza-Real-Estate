/**
 * Calendar Modal Handler
 * 
 * This script manages the modal interactions for calendar day clicks
 * and form submissions for creating reminders directly from the calendar.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get references to modal elements
    const calendarModal = document.getElementById('calendar-day-modal');
    const modalClose = document.querySelector('.calendar-modal__close');
    const modalTitle = document.querySelector('.calendar-modal__title');
    const calendarForm = document.getElementById('calendar-reminder-form');
    const selectedDateInput = document.getElementById('selected_date');
    const selectedDateEnd = document.getElementById('data_fim');
    
    // Check if the modal exists
    if (!calendarModal) {
        console.error('Calendar modal not found in the document');
        return;
    }
    
    console.log('Calendar modal initialized');
    
    // Get all calendar day elements that can be clicked
    const calendarDays = document.querySelectorAll('.calendar__day:not(.calendar__day--empty)');
    
    console.log(`Found ${calendarDays.length} clickable calendar days`);
    
    // Add click event to each calendar day
    calendarDays.forEach(day => {
        day.addEventListener('click', function(event) {
            // Prevent event from bubbling to parent elements
            event.stopPropagation();
            
            console.log('Calendar day clicked');
            
            // Get the day number and current month/year from the page
            const dayNumber = this.querySelector('.calendar__day-number').textContent;
            const monthYear = document.querySelector('.calendar-nav__title').textContent.trim();
            
            console.log(`Day: ${dayNumber}, Month/Year: ${monthYear}`);
            
            // Parse month and year
            const [monthName, year] = monthYear.split(' ');
            const monthNumber = getMonthNumber(monthName);
            
            // Format the date for display and form
            const formattedDate = formatDate(year, monthNumber, dayNumber);
            
            console.log(`Formatted date: ${formattedDate}`);
            
            // Update modal title and form inputs
            modalTitle.textContent = `Novo Lembrete para ${dayNumber} de ${monthName}`;
            selectedDateInput.value = formattedDate;
            selectedDateEnd.value = formattedDate;
            
            // Show the modal
            calendarModal.classList.add('active');
            document.body.classList.add('modal-open');
            
            console.log('Modal displayed');
        });
    });
    
    // Close modal when clicking the close button
    if (modalClose) {
        modalClose.addEventListener('click', function(event) {
            event.preventDefault();
            calendarModal.classList.remove('active');
            document.body.classList.remove('modal-open');
            console.log('Modal closed via close button');
        });
    } else {
        console.error('Modal close button not found');
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === calendarModal) {
            calendarModal.classList.remove('active');
            document.body.classList.remove('modal-open');
            console.log('Modal closed by clicking outside');
        }
    });
    
    /**
     * Helper function to get month number from name
     */
    function getMonthNumber(monthName) {
        const months = {
            'Janeiro': '01',
            'Fevereiro': '02',
            'Março': '03',
            'Abril': '04',
            'Maio': '05',
            'Junho': '06',
            'Julho': '07',
            'Agosto': '08',
            'Setembro': '09',
            'Outubro': '10',
            'Novembro': '11',
            'Dezembro': '12'
        };
        return months[monthName] || '01';
    }
    
    /**
     * Helper function to format date as YYYY-MM-DD
     */
    function formatDate(year, month, day) {
        // Ensure day is two digits
        const dayFormatted = day.toString().padStart(2, '0');
        return `${year}-${month}-${dayFormatted}`;
    }
    
    // Initialize time fields with current time if they're empty
    const timeInputs = document.querySelectorAll('input[type="time"]');
    timeInputs.forEach(input => {
        if (!input.value) {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            input.value = `${hours}:${minutes}`;
        }
    });
});