/**
 * SweetAlert Utility Functions
 * Place this file in your assets/js directory
 * Path: public/assets/js/sweetalert-utils.js
 */

// Add custom CSS for SweetAlert buttons
(function() {
    const style = document.createElement('style');
    style.innerHTML = `
        .swal2-confirm, .swal2-cancel {
            margin: 0 5px; /* Add margin between the buttons */
        }
    `;
    document.head.appendChild(style);
})();

// Create SweetAlert utility object
const SwalUtil = {
    /**
     * Confirmation dialog for delete operations
     * @param {number} itemId - ID of the item to delete
     * @param {string} deleteUrl - URL for the delete request
     * @param {string} itemType - Type of item being deleted (e.g., 'Driver', 'User', etc.)
     * @param {Function} onSuccess - Callback function to execute on successful deletion
     */
    deleteConfirmation: function(itemId, deleteUrl, itemType = 'item', onSuccess) {
        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
            },
            buttonsStyling: false
        });
        
        swalWithBootstrapButtons.fire({
            title: 'Are you sure?',
            text: `You won't be able to revert this ${itemType.toLowerCase()} deletion!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Get the CSRF token directly
                const token = $('meta[name="csrf-token"]').attr('content');
                
                // Perform the AJAX request to delete the item
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token
                    },
                    success: function(response) {
                        // Show success message
                        Swal.fire({
                            title: 'Deleted!',
                            text: `The ${itemType.toLowerCase()} has been deleted.`,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Execute success callback if provided
                        if (typeof onSuccess === 'function') {
                            onSuccess(response, itemId);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        Swal.fire({
                            title: 'Error!',
                            text: `An error occurred while deleting the ${itemType.toLowerCase()}.`,
                            icon: 'error'
                        });
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire({
                    title: 'Cancelled',
                    text: `The ${itemType.toLowerCase()} is safe!`,
                    icon: 'error',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        });
    },

    /**
     * Show success notification
     * @param {string} title - Title of the notification
     * @param {string} message - Message to display
     * @param {number} timer - Auto-close timer in milliseconds
     */
    success: function(title, message, timer = 2000) {
        Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            timer: timer,
            showConfirmButton: false
        });
    },

    /**
     * Show error notification
     * @param {string} title - Title of the notification
     * @param {string} message - Message to display
     */
    error: function(title, message) {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message
        });
    },

    /**
     * Show warning notification
     * @param {string} title - Title of the notification
     * @param {string} message - Message to display
     */
    warning: function(title, message) {
        Swal.fire({
            icon: 'warning',
            title: title,
            text: message
        });
    },

    /**
     * Show information notification
     * @param {string} title - Title of the notification
     * @param {string} message - Message to display
     */
    info: function(title, message) {
        Swal.fire({
            icon: 'info',
            title: title,
            text: message
        });
    },

    /**
     * Confirmation dialog for any action
     * @param {string} title - Title of the dialog
     * @param {string} message - Message to display
     * @param {Function} onConfirm - Callback function when confirmed
     * @param {Function} onCancel - Callback function when cancelled
     */
    confirm: function(title, message, onConfirm, onCancel) {
        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
            },
            buttonsStyling: false
        });
        
        swalWithBootstrapButtons.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, proceed!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed && typeof onConfirm === 'function') {
                onConfirm();
            } else if (result.dismiss === Swal.DismissReason.cancel && typeof onCancel === 'function') {
                onCancel();
            }
        });
    }
};