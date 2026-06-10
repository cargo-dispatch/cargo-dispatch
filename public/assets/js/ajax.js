function handleFormSubmit(formSelector, redirectUrl, successMessage = "Saved successfully!") {
    const form = $(formSelector);
    const actionUrl = form.attr('action');
    const submitBtn = form.find('button[type="submit"]');
    const originalBtnText = submitBtn.html();

    let formData = form.serializeArray();

    const methodInput = form.find('input[name="_method"]');
    if (methodInput.length) {
        formData.push({ name: '_method', value: methodInput.val() });
    }

    submitBtn.prop('disabled', true).html('Processing...');

    $.ajax({
        url: actionUrl,
        method: "POST",
        data: formData,
        success: function () {
            Swal.fire('Success', successMessage, 'success').then(() => {
                window.location.href = redirectUrl;
            });
        },
        error: function (xhr) {
            submitBtn.prop('disabled', false).html(originalBtnText);

            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                $('.text-danger').remove();
                $('.is-invalid').removeClass('is-invalid');

                $.each(errors, function (key, value) {
                    const input = $('[name="' + key + '"]');
                    input.addClass('is-invalid');
                    input.next('.text-danger').remove();
                    input.after('<span class="text-danger">' + value[0] + '</span>');
                });
            } else {
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
                console.error(xhr.responseText);
            }
        }
    });
}
function handleDeleteRecord(buttonSelector, getDeleteUrlCallback, reloadCallback) {
    $(document).on('click', buttonSelector, function () {
        const id = $(this).data('id');
        const deleteUrl = getDeleteUrlCallback(id);

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: deleteUrl,
                    method: "POST",
                    data: {
                        _method: "DELETE",
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function () {
                        Swal.fire('Deleted!', 'Record has been deleted.', 'success');
                        if (typeof reloadCallback === 'function') {
                            reloadCallback();
                        }
                    },
                    error: function (error) {
                        console.error('Error deleting record:', error);
                        Swal.fire('Error', 'Failed to delete the record.', 'error');
                    }
                });
            }
        });
    });
}




