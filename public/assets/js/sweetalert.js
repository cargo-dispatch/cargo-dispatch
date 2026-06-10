
document.addEventListener("DOMContentLoaded", function () {
    const deleteButtons = document.querySelectorAll('.delete-button');
    
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault(); 
            
            const form = this.closest('form');
            const userId = form.dataset.id; 
            
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'btn btn-danger'
                },
                buttonsStyling: false
            });
            
            swalWithBootstrapButtons.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Get the CSRF token directly
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    
                    // Perform the AJAX request to delete the user
                    $.ajax({
                        url: form.action, // Get the form action URL (which includes the user ID)
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': token
                        },
                        success: function (response) {
                            if (response.success) {
                                // Remove the user row from the table
                                $('tr[data-id="' + userId + '"]').remove();
                                Swal.fire({
                                    title: 'Deleted!',
                                    text: 'The user has been deleted.',
                                    icon: 'success'
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Could not delete the user. Please try again.',
                                    icon: 'error'
                                });
                            }
                        },
                        error: function (xhr) {
                            console.error('Error:', xhr);
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred. Please try again.',
                                icon: 'error'
                            });
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    Swal.fire({
                        title: 'Cancelled',
                        text: 'Your data is safe!',
                        icon: 'error'
                    });
                }
            });
        });
    });
    
    // Add custom CSS for margin between buttons
    const style = document.createElement('style');
    style.innerHTML = `
        .swal2-confirm, .swal2-cancel {
            margin: 0 5px; /* Add margin between the buttons */
        }
    `;
    document.head.appendChild(style);
});

