/**
 * Alert Notification Handler
 * 
 * This script manages the creation and display of alert notifications
 * for user feedback after form submissions or other actions.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check if there's an alert message in session
    const alertMessage = document.querySelector('.php-alert-message');
    if (alertMessage) {
        const message = alertMessage.getAttribute('data-message');
        const type = alertMessage.getAttribute('data-type');
        
        if (message) {
            showNotification(message, type || 'info');
            
            // Remove the PHP alert element to prevent duplicates
            alertMessage.remove();
        }
    }
    
    /**
     * Show a notification alert
     * 
     * @param {string} message - The message to display
     * @param {string} type - Type of notification (success, error, warning, info)
     * @param {number} duration - How long to show the notification in ms (default: 5000)
     */
    function showNotification(message, type = 'info', duration = 5000) {
        // Create notification container if not exists
        let notificationsContainer = document.getElementById('notifications-container');
        if (!notificationsContainer) {
            notificationsContainer = document.createElement('div');
            notificationsContainer.id = 'notifications-container';
            document.body.appendChild(notificationsContainer);
        }
        
        // Create the notification element
        const notification = document.createElement('div');
        notification.className = `alert-notification alert-notification--${type}`;
        
        // Set icon based on type
        let icon = '';
        switch (type) {
            case 'success':
                icon = 'check-circle';
                break;
            case 'error':
                icon = 'exclamation-circle';
                break;
            case 'warning':
                icon = 'exclamation-triangle';
                break;
            default:
                icon = 'info-circle';
                break;
        }
        
        // Set notification content
        notification.innerHTML = `
            <div class="alert-notification__icon"><i class="fas fa-${icon}"></i></div>
            <div class="alert-notification__message">${message}</div>
            <button class="alert-notification__close">&times;</button>
        `;
        
        // Add to container
        notificationsContainer.appendChild(notification);
        
        // Add click event for close button
        const closeButton = notification.querySelector('.alert-notification__close');
        closeButton.addEventListener('click', function() {
            notification.remove();
        });
        
        // Automatically remove after duration
        setTimeout(function() {
            // Check if notification still exists
            if (notification.parentElement) {
                // Add fade-out class first, then remove after animation
                notification.style.animation = 'fadeOut 0.5s ease forwards';
                setTimeout(function() {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 500);
            }
        }, duration);
    }
    
    // Expose function globally
    window.showNotification = showNotification;
});