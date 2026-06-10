window.showDetailModal = function ({
    route,
    modalId = 'detailModal',
    detailContainerId = 'detail-container',
    auditContainerId = 'audit-log-container',
    fields = [],
    renderExtras = null 
}) {
    fetch(route)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById(detailContainerId);
            container.innerHTML = '';
            
            fields.forEach(field => {
                // Handle nested object paths (e.g., "customer.customer_title")
                let value = '';
                if (field.key.includes('.')) {
                    const keys = field.key.split('.');
                    let currentObj = data;
                    
                    // Navigate through the object path
                    for (const key of keys) {
                        if (currentObj && currentObj[key] !== undefined) {
                            currentObj = currentObj[key];
                        } else {
                            currentObj = '';
                            break;
                        }
                    }
                    
                    value = currentObj;
                } else {
                    value = data[field.key] ?? '';
                }
                
                container.innerHTML += `
                    <div class="col-12 col-sm-6">
                        <strong>${field.label}:</strong> ${value}
                    </div>
                `;
            });
            
            if (typeof renderExtras === 'function') {
                const extraHtml = renderExtras(data);
                if (extraHtml) {
                    container.innerHTML += `
                        <div class="col-12 border mt-2">${extraHtml}</div>
                    `;
                }
            }
            
            // See what timezone browser detects
console.log('bb',Intl.DateTimeFormat().resolvedOptions().timeZone);
            // Audit logs
            const auditContainer = document.getElementById(auditContainerId);
            
            if (auditContainer) {
                auditContainer.innerHTML = '';
                if (Array.isArray(data.audits) && data.audits.length > 0) {
                    data.audits.forEach(audit => {
                        // Get user name if available, otherwise fallback to user_id
                        const userName = audit.user_name || (audit.user ? audit.user.name : 'User ' + audit.user_id);
                        
                        // Format the created_at date
                        const formattedDate = formatDateTime(audit.created_at);
                        
                        // Format changes with proper handling of IDs
                        const oldChanges = formatChangesWithNames(audit.old_values, data.name_mappings || {});
                        const newChanges = formatChangesWithNames(audit.new_values, data.name_mappings || {});
                        
                        auditContainer.innerHTML += `
                            <tr>
                                <td>${audit.event}</td>
                                <td>${userName}</td>
                                <td>${formattedDate}</td>
                                <td><strong>Old:</strong><br>${oldChanges}<br><strong>New:</strong><br>${newChanges}</td>
                            </tr>
                        `;
                    });
                } else {
                    auditContainer.innerHTML = `<tr><td colspan="4" class="text-muted text-center">No activity logs found.</td></tr>`;
                }
            }
            
            const modalElement = document.getElementById(modalId);
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        })
        .catch(error => {
            console.error('Error fetching details:', error);
            alert('Failed to load details.');
        });
};

/**
 * Format datetime string to a readable format in Florida timezone
 * @param {string} dateTimeString - The datetime string from Laravel
 * @returns {string} Formatted date and time in Florida timezone
 */

/**
 * Enhanced version of formatChanges that tries to use friendly names instead of IDs
 * @param {Object} changes - The changes object from audit record
 * @param {Object} nameMappings - Object containing mappings between IDs and names
 * @returns {string} Formatted HTML string
 */
function formatChangesWithNames(changes, nameMappings = {}) {
    if (!changes || Object.keys(changes).length === 0) return '—';
    
    return Object.entries(changes)
        .map(([key, value]) => {
            // If the key ends with _id, it's likely a foreign key that we want to display as a name
            if (key.endsWith('_id') && nameMappings[key] && nameMappings[key][value]) {
                return `<strong>${key.replace('_id', '')}:</strong> ${nameMappings[key][value]}`;
            }
            return `<strong>${key}:</strong> ${value}`;
        })
        .join('<br>');
}