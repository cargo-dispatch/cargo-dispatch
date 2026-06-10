
<script>
document.addEventListener('DOMContentLoaded', function () {
    const vehiclesData = @json($vehicles);
    const driversData = @json($drivers);
    
    function loadAllAssignments() {
        fetch('{{ route('vehicleassignment.all') }}')
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.json();
            })
            .then(data => {
                let html = '';
                if (data.length > 0) {
                   
                    html = `<table class="custom-table stripped sidebar-wrapper" cellspacing="0">
                                <thead class="thead-light">
                                    <tr>
                                      <th>Assigned Date</th>
                                        <th>Driver</th>
                                        <th>Assigned Vehicle</th>
                                        <th>Change Vehicle</th>
                                         
                                        <th>Actions</th>
                                     
                                    </tr>
                                </thead>
                                <tbody>`;

                    data.forEach(item => {
                        const hasAssignment = item.id !== null;
                        const vehicleDisplay = item.vehicle ? item.vehicle.vehicle_id : 'No Vehicle Assigned';
                        const assignmentDate = hasAssignment ? new Date(item.created_at).toLocaleDateString() : 'N/A';
                        
                        html += `<tr>

                          <td><small class="show-enteries">${assignmentDate}</small></td>
<td><span class="badge show-enteries p-2">${item.username}</span></td>
                            <td><strong class="text-${item.vehicle ? 'success' : '${show-enteries}'}">${vehicleDisplay}</strong></td>
                            <td>
                                <select class="form-control p-1 form-control-sm vehicle-dropdown-overview" 
                                        data-id="${item.id || ''}" 
                                        data-driver-id="${item.driver_id}"
                                        data-driver="${item.username}">`;
                        
                        if (!hasAssignment) {
                            html += `<option value="">Select a vehicle...</option>`;
                        }
                        
                        vehiclesData.forEach(vehicle => {
                            const selected = item.vehicle_ids && item.vehicle_ids.includes(vehicle.id) ? 'selected' : '';
                           
                            const imgPath = vehicle.vehicle_type && vehicle.vehicle_type.image ? 
                                `{{ asset('storage/') }}/${vehicle.vehicle_type.image}` : 
                                '{{ asset('storage/default.png') }}';

                            html += `<option value="${vehicle.id}" ${selected} 
                                data-custom-properties='{"image": "${imgPath}"}'>
                                ${vehicle.vehicle_id}
                            </option>`;
                        });

                        html += `</select>
                            </td>
                            
                            <td style="padding:10px 3px;">
                                <div class="btn-group" role="group">`;
                        
                        if (hasAssignment) {
                            html += `<button class="update-overview-btn tick-btn-hover"
                                        data-id="${item.id}"
                                        data-driver="${item.username}"
                                        title="Update Vehicle">
                                  <i class="fa-solid fa-check"></i>
                                    </button>
                                    <button class="btn btn-link text-danger p-0 remove-overview-btn" 
                                        data-id="${item.id}" 
                                        data-driver="${item.username}" 
                                        title="Remove Assignment">
                                       <i class="bi bi-trash"></i>
                                    </button>`;
                        } else {
                            html += `<button class=" assign-overview-btn" 
                                        data-driver-id="${item.driver_id}" 
                                        title="Assign Vehicle">
                                        <i class="fas fa-plus"></i> Assign
                                    </button>`;
                        }
                        
                        html += `</div>
                            </td>
                          
                        </tr>`;
                    });

                    html += '</tbody></table>';
                } else {
                    html = '<div class="alert alert-info">No drivers found.</div>';
                }

                // Replace content
                document.getElementById('all-assignments-table').innerHTML = html;

                // Initialize TomSelect on all new dropdowns
                document.querySelectorAll('.vehicle-dropdown-overview').forEach(select => {
                    new TomSelect(select, {
                        plugins: [], // Remove multiple plugin for now to simplify
                        placeholder: "Choose vehicle...",
                        render: {
                            option: function(data, escape) {
                                const img = JSON.parse(data.customProperties || '{}').image || '';
                                return `<div><img src="${img}" style="width:50px;height:30px;margin-right:5px;border-radius:3px;" onerror="this.style.display='none'"> ${escape(data.text)}</div>`;
                            },
                            item: function(data, escape) {
                                const img = JSON.parse(data.customProperties || '{}').image || '';
                                return `<div><img src="${img}" style="width:40px;height:30px;margin-right:5px;border-radius:3px;" onerror="this.style.display='none'"> ${escape(data.text)}</div>`;
                            }
                        }
                    });
                });

            })
            .catch(error => {
                console.error('Error loading all assignments:', error);
                document.getElementById('all-assignments-table').innerHTML = '<div class="alert alert-danger">Error loading assignments overview.</div>';
            });
    }

    // Load all assignments on page load
    loadAllAssignments();

    // Update assignment and remove assignment events (using event delegation)
    document.addEventListener('click', function (e) {
        
        // Handle updates from overview section
        if (e.target.classList.contains('update-overview-btn') || 
            e.target.parentElement.classList.contains('update-overview-btn')) {
            
            const button = e.target.classList.contains('update-overview-btn') ? e.target : e.target.parentElement;
            const assignmentId = button.getAttribute('data-id');
            const driverName = button.getAttribute('data-driver');
            
            
            const select = document.querySelector(`select.vehicle-dropdown-overview[data-id="${assignmentId}"]`);
            if (!select) {
                console.error('Select element not found for assignment ID:', assignmentId);
                return;
            }
            
            const newVehicleId = select.value;
            
            if (!newVehicleId || newVehicleId === '') {
                alert('Please select a vehicle first.');
                return;
            }

            const newVehicle = vehiclesData.find(v => v.id == newVehicleId)?.vehicle_id || 'Unknown';

           Swal.fire({
    title: `Update Assignment?`,
    text: `Update ${driverName}'s vehicle assignment to ${newVehicle}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#aaa',
    confirmButtonText: 'Yes, update it!'
}).then((result) => {
    if (result.isConfirmed) {
        updateAssignment(assignmentId, newVehicleId, button, '<i class="fas fa-sync-alt"></i>', true);
    }
});

        }
        
        // Handle new assignments from overview section
        else if (e.target.classList.contains('assign-overview-btn') || 
                 e.target.parentElement.classList.contains('assign-overview-btn')) {
            
            const button = e.target.classList.contains('assign-overview-btn') ? e.target : e.target.parentElement;
            const driverId = button.getAttribute('data-driver-id');
            
            
            const select = document.querySelector(`select.vehicle-dropdown-overview[data-driver-id="${driverId}"]`);
            if (!select) {
                console.error('Select element not found for driver ID:', driverId);
                return;
            }
            
            const vehicleId = select.value;
            
       if (!vehicleId || vehicleId === '') {
    Swal.fire({
        icon: 'warning',
        title: 'Oops...',
        text: 'Please select a vehicle first.',
        confirmButtonColor: '#d33', 
        confirmButtonText: 'OK'
    });
    return;
}



            const driverName = button.closest('tr').querySelector('.badge').textContent;
            const vehicleName = vehiclesData.find(v => v.id == vehicleId)?.vehicle_id || 'Unknown';

           Swal.fire({
    title: `Assign ${vehicleName} to ${driverName}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, assign it!'
}).then((result) => {
    if (result.isConfirmed) {
        assignVehicle(driverId, vehicleId, button, '<i class="fas fa-plus"></i> Assign');
    }
});

        }
        
      
        else if (e.target.classList.contains('remove-overview-btn') || 
                 e.target.parentElement.classList.contains('remove-overview-btn')) {
            
            const button = e.target.classList.contains('remove-overview-btn') ? e.target : e.target.parentElement;
            const assignmentId = button.getAttribute('data-id');
            const driverName = button.getAttribute('data-driver');
            
            
           Swal.fire({
    title: `Are you sure?`,
    text: `Do you want to remove the vehicle assignment for ${driverName}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, remove it!'
}).then((result) => {
    if (result.isConfirmed) {
        removeAssignment(assignmentId, button, '<i class="fas fa-trash"></i>', true);
    }
});

        }
    });

   
    function assignVehicle(driverId, vehicleId, buttonElement, originalButtonHtml, forceAssign = false) {

        buttonElement.disabled = true;
        buttonElement.innerHTML = 'Assigning...';

        fetch('{{ route('vehicleassignment.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                driver_id: driverId,
                vehicle_id: vehicleId,
                force_assign: forceAssign,
            })
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            if (status === 422 && data.driver_unavailable) {
                Swal.fire({
                    title: 'Driver Unavailable',
                    html: `<b>${data.driver_name}</b> is currently <span class="badge bg-secondary">${data.driver_status}</span>.<br><br>Do you still want to assign them?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#aaa',
                    confirmButtonText: 'Force Assign',
                    cancelButtonText: 'Cancel',
                }).then(result => {
                    if (result.isConfirmed) {
                        assignVehicle(driverId, vehicleId, buttonElement, originalButtonHtml, true);
                    }
                });
                return;
            }
            if (!data.success) throw new Error(data.message || 'Failed to assign vehicle');
            Swal.fire({ title: 'Success!', text: 'Vehicle assigned successfully!', icon: 'success', confirmButtonColor: '#3085d6' });
            loadAllAssignments();
        })
        .catch(error => {
            console.error('Error assigning vehicle:', error);
            Swal.fire({ title: 'Error', text: error.message, icon: 'error' });
        })
        .finally(() => {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalButtonHtml;
        });
    }

 
    function updateAssignment(assignmentId, newVehicleId, buttonElement, originalButtonHtml, isOverview = false) {
        
        buttonElement.disabled = true;
        buttonElement.innerHTML = 'Updating...';

        fetch(`{{ route('vehicleassignment.update', '') }}/${assignmentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ vehicle_id: newVehicleId })
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to update assignment');
            }
            return res.json();
        })
        .then(data => {

    Swal.fire({
        title: 'Updated!',
        text: 'Vehicle assignment updated successfully!',
        icon: 'success',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    });

    if (isOverview) {
        loadAllAssignments();
    }
})

        .catch(error => {
            console.error('Error updating assignment:', error);
            alert('Failed to update assignment: ' + error.message);
        })
        .finally(() => {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalButtonHtml;
        });
    }
    function removeAssignment(assignmentId, buttonElement, originalButtonHtml, isOverview = false) {
        
        buttonElement.disabled = true;
        buttonElement.innerHTML = 'Removing...';

        fetch(`{{ route('vehicleassignment.remove', '') }}/${assignmentId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(res => {
            if (!res.ok) {
                throw new Error('Failed to remove assignment');
            }
            return res.json();
        })
       .then(data => {

    Swal.fire({
        title: 'Removed!',
        text: 'Vehicle assignment removed successfully!',
        icon: 'success',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
    });

    if (isOverview) {
        loadAllAssignments();
    }
})

        .catch(error => {
            console.error('Error removing assignment:', error);
            alert('Failed to remove assignment: ' + error.message);
        })
        .finally(() => {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalButtonHtml;
        });
    }
});
</script>