<div id="modal-container">
    <div class="modal-background">
        <div class="modal" role="dialog" aria-labelledby="modal-role-name">
           
            <div class="modal-body theme-modal-body p-4">
                <!-- Assign Permissions Section -->
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" style="color: var(--text-color); font-family: 'Jost', sans-serif;">
                            Assign Permissions to: <strong>{{ $role->name }}</strong>
                        </h4>
                    </div>

                    <form method="POST" action="{{ route('permissions.save', $role->id) }}">
                        @csrf
                        <input type="hidden" name="role_id" id="current-role-id" value="{{ $role->id }}">

                        <div class="table-responsive mt-4">
                            <table class="table table-hover table-striped table-bordered theme-table">
                                <thead class="theme-table-header">
                                    <tr>
                                        <th class="theme-table-th">Module</th>
                                        <th class="theme-table-th">View</th>
                                        <th class="theme-table-th">Create</th>
                                        <th class="theme-table-th">Edit</th>
                                        <th class="theme-table-th">Delete</th>
                                    </tr>
                                </thead>
                                <tbody class="theme-table-body">
                                @foreach($modules as $module)
                                <tr>
                                    <td>{{ ucfirst($module->name) }}</td>
                                    @foreach(['view', 'create', 'update', 'delete'] as $action)
                                        @php
                                            $permissionName = strtolower($module->name) . '.' . $action;
                                            $isChecked = $role->permissions->contains('name', $permissionName);
                                        @endphp
                                        <td>
                                            <div class="form-check d-flex justify-content-center align-items-center" style="min-height: 40px;">
                                                <input 
                                                    class="form-check-input permission-checkbox"
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permissionName }}"
                                                    data-permission="{{ $permissionName }}"
                                                    id="permission-{{ $module->id }}-{{ $action }}"
                                                    {{ $isChecked ? 'checked' : '' }}
                                                    style="
                                                        width: 20px;
                                                        height: 20px;
                                                        cursor: pointer;
                                                        border: 2px solid #666;
                                                        border-radius: 4px;
                                                        appearance: none;
                                                        -webkit-appearance: none;
                                                        background-color: white;
                                                        position: relative;
                                                        transition: all 0.2s ease-in-out;
                                                        margin: 0;
                                                    "
                                                >
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="modal-footer theme-modal-footer" style="
                            background: var(--sidebar-bg) !important;
                            border-top: 1px solid var(--chart-grid);
                            padding: 1rem;
                            margin-top: 1.5rem;
                        ">
                            <button type="submit" class="btn btn-primary theme-btn-outline" style="
                                background: var(--btn-bg) !important;
                                color: var(--btn-text) !important;
                                border: 1px solid var(--btn-border) !important;
                                padding: 10px 24px;
                                border-radius: 8px;
                                font-family: 'Jost', sans-serif;
                                font-weight: 600;
                                transition: all 0.3s ease-in-out;
                            ">
                                Save Permissions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Apply theme-specific styles to checkboxes
function updateCheckboxStyles() {
    const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark';
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    
    checkboxes.forEach(checkbox => {
        if (isDarkMode) {
            // Dark mode styles
            checkbox.style.backgroundColor = checkbox.checked ? '#F8C71F' : '#2B2F3B';
            checkbox.style.borderColor = checkbox.checked ? '#F8C71F' : 'rgba(255, 255, 255, 0.3)';
            
            // Custom checkmark for dark mode
            if (checkbox.checked) {
                checkbox.style.backgroundImage = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E\")";
                checkbox.style.backgroundRepeat = 'no-repeat';
                checkbox.style.backgroundPosition = 'center';
                checkbox.style.backgroundSize = '12px 12px';
            } else {
                checkbox.style.backgroundImage = 'none';
            }
        } else {
            // Light mode styles
            checkbox.style.backgroundColor = checkbox.checked ? '#F8C71F' : 'white';
            checkbox.style.borderColor = checkbox.checked ? '#F8C71F' : '#666666';
            
            // Custom checkmark for light mode
            if (checkbox.checked) {
                checkbox.style.backgroundImage = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23000000' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'%3E%3C/polyline%3E%3C/svg%3E\")";
                checkbox.style.backgroundRepeat = 'no-repeat';
                checkbox.style.backgroundPosition = 'center';
                checkbox.style.backgroundSize = '12px 12px';
            } else {
                checkbox.style.backgroundImage = 'none';
            }
        }
    });
}

// Initialize checkbox styles
document.addEventListener('DOMContentLoaded', updateCheckboxStyles);

// Update when theme changes
const observer = new MutationObserver(updateCheckboxStyles);
observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

// Update checkboxes when clicked
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('permission-checkbox')) {
        updateCheckboxStyles();
    }
});
</script>