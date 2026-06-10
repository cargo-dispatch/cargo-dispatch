// public/js/calendar.js

/**
 * Open date picker for a specific input
 * @param {string} inputId - The ID of the date input
 */
function openDatePicker(inputId) {
    const dateInput = document.getElementById(inputId);
    if (dateInput && !dateInput.disabled) {
        dateInput.showPicker();
    }
}

/**
 * Update the display text for a calendar input
 * @param {string} inputId - The ID of the date input
 * @param {string} displayId - The ID of the display element
 */
function updateCalendarDisplay(inputId, displayId) {
    const dateInput = document.getElementById(inputId);
    const dateDisplay = document.getElementById(displayId);
    
    if (dateInput && dateDisplay) {
        const selectedDate = dateInput.value;
        if (selectedDate) {
            dateDisplay.textContent = formatDate(selectedDate);
            dateDisplay.classList.remove('date-placeholder');
        } else {
            dateDisplay.textContent = dateDisplay.dataset.placeholder || 'Select date';
            dateDisplay.classList.add('date-placeholder');
        }
    }
}

/**
 * Format date to YYYY-MM-DD
 * @param {string} dateString - Date string
 * @returns {string} Formatted date
 */
function formatDate(dateString) {
    if (!dateString) return '';
    
    try {
        const date = new Date(dateString);
        return date.toISOString().split('T')[0];
    } catch (error) {
        console.error('Error formatting date:', error);
        return dateString;
    }
}

/**
 * Set min date for a calendar input
 * @param {string} inputId - The ID of the date input
 * @param {string} minDate - Minimum date in YYYY-MM-DD format
 */
function setMinDate(inputId, minDate) {
    const dateInput = document.getElementById(inputId);
    if (dateInput) {
        dateInput.min = minDate;
    }
}

/**
 * Set max date for a calendar input
 * @param {string} inputId - The ID of the date input
 * @param {string} maxDate - Maximum date in YYYY-MM-DD format
 */
function setMaxDate(inputId, maxDate) {
    const dateInput = document.getElementById(inputId);
    if (dateInput) {
        dateInput.max = maxDate;
    }
}

/**
 * Disable a calendar input
 * @param {string} inputId - The ID of the date input
 */
function disableCalendar(inputId) {
    const dateInput = document.getElementById(inputId);
    const wrapper = dateInput?.closest('.custom-date-input');
    if (dateInput && wrapper) {
        dateInput.disabled = true;
        wrapper.classList.add('disabled');
    }
}

/**
 * Enable a calendar input
 * @param {string} inputId - The ID of the date input
 */
function enableCalendar(inputId) {
    const dateInput = document.getElementById(inputId);
    const wrapper = dateInput?.closest('.custom-date-input');
    if (dateInput && wrapper) {
        dateInput.disabled = false;
        wrapper.classList.remove('disabled');
    }
}

/**
 * Initialize all calendar inputs on the page
 */
function initializeCalendarInputs() {
    // Initialize date displays
    document.querySelectorAll('.custom-date-input input[type="date"]').forEach(input => {
        const displayId = input.id + '_display';
        const display = document.getElementById(displayId);
        
        if (display) {
            // Set placeholder from data attribute
            const placeholder = display.dataset.placeholder || 'Select date';
            display.dataset.placeholder = placeholder;
            
            // Initialize display
            updateCalendarDisplay(input.id, displayId);
        }
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', initializeCalendarInputs);

// Re-initialize for dynamically loaded content
document.addEventListener('ajaxComplete', initializeCalendarInputs);



