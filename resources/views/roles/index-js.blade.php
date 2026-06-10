<script>
$(document).ready(function () {


    $(document).on('change', '.form-check-input', function () {
    const action = $(this).data('action');
    const moduleId = $(this).data('module-id');

    // If user checks 'create', 'edit' or 'delete', auto-check 'view'
    if ((action === 'create' || action === 'edit' || action === 'delete') && $(this).is(':checked')) {
        $(`input.form-check-input[data-module-id="${moduleId}"][data-action="view"]`).prop('checked', true);
    }
});
    function renderPermissions(permissions, container, roleId) {
        $('input.form-check-input[data-module-id]').prop('checked', false);

        permissions.forEach(permission => {
            const moduleId = permission.module_id;
            ['view', 'create', 'edit', 'delete'].forEach(action => {
                if (permission[action] === 1) {
                    $(`input.form-check-input[data-module-id="${moduleId}"][data-action="${action}"]`).prop('checked', true);
                }
            });
        });
    }

    function openModal() {
        $('#modal-container').removeClass('out').removeAttr('class').addClass('seven');
        $('body').addClass('modal-active');
    }

   function loadRolePermissions(roleId) {

    
    $.ajax({
        url: `${window.APP_URL}/admin/roles/${roleId}/permissions`,
        type: 'GET',
        success: function (response) {

            $('#modal-role-name').text(`Assign Permissions to ${response.role}`);
            $('#current-role-id').val(roleId);

            // Clear all checkboxes first
            $('input[type=checkbox][name="permissions[]"]').prop('checked', false);

            // Loop through permission names and check those that match
            response.permissions.forEach(function (perm) {
                $(`input[type=checkbox][data-permission="${perm}"]`).prop('checked', true);
            });

            openModal();
        },
        error: function () {
            alert('Failed to fetch role permissions.');
        }
    });
}


    $(document).on('click', '.role-detail-link', function () {
       
        const roleId = $(this).data('role-id');
        
        
        loadRolePermissions(roleId);
    });

    $(document).on('click', '#save-permissions-btn', function () {
        window.location.href = '{{ route("roles.index") }}';
    });

    $(document).on('click', '.modal', function (e) {
        e.stopPropagation();
    });

    $(document).on('click', '.modal-background', function (e) {
        if ($(e.target).hasClass('modal-background')) {
            $('#modal-container').addClass('out');
            setTimeout(() => {
                $('body').removeClass('modal-active');
                $('#modal-container').removeClass('seven out');
            }, 200);
        }
    });

    $(document).on('click', '#close-modal-btn', function () {
        $('#modal-container').addClass('out');
        setTimeout(() => {
            $('body').removeClass('modal-active');
            $('#modal-container').removeClass('seven out');
        }, 200);
    });

    $(document).on('click', '.remove-permission', function (e) {
        e.stopPropagation();
        const permissionId = $(this).data('permission-id');
        const roleId = $(this).data('role-id');

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to remove this permission from the role?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/roles/${roleId}/permissions/${permissionId}`,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function () {
                        loadRolePermissions(roleId);
                        Swal.fire({ icon: 'success', title: 'Permission removed', showConfirmButton: false, timer: 1500 });
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Failed to remove permission.' });
                    }
                });
            }
        });
    });
});
</script>