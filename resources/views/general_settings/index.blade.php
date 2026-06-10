@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <!-- Page title -->
            <div class="my-0 my-md-5">
                <h3 class="fs-18">General Settings</h3>
                <hr>
            </div>


            <!-- Form START -->
            <form class="file-upload" id="generalSettingsForm" method="POST">
                @csrf
                <div class="row mb-5 gx-1">
                    <div class="col-xxl-12 mb-5 mb-xxl-0">
                        <div class="bg-secondary-soft px-0 py-0 px-md-4 py-md-5 rounded">

                            <div class="row g-3">
                                <h4 class="mb-4 fs-15 mt-0">General Details</h4>

                                <div class="col-md-6">
                                    <label class=" fs-13 form-label">Fuel Price ($ per gallon) *</label>
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        class="form-control fs-13 sidebar-wrapper" 
                                        id="fuel_price"
                                        name="fuel_price" 
                                        value="{{ old('fuel_price', $setting->fuel_price ?? '') }}" 
                                        placeholder="Enter fuel price">
                                    <div class="invalid-feedback" id="fuel_price_error"></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fs-13">Company Profit (%) *</label>
                                    <input 
                                        type="number" 
                                        step="0.01"
                                        class="form-control fs-13 sidebar-wrapper" 
                                        id="company_profit"
                                        name="company_profit" 
                                        value="{{ old('company_profit', $setting->company_profit ?? '') }}" 
                                        placeholder="Enter company profit percentage">
                                    <div class="invalid-feedback" id="company_profit_error"></div>
                                </div>

                                <div class="col-12 d-flex justify-content-center justify-content-md-end mt-3">

                                    <button type="submit" class="btn theme-btn" id="submitBtn">
                                        <span id="btnText">{{ $setting ? 'Update Settings' : 'Save Settings' }}</span>
                                        <span id="btnSpinner" class="d-none">
                                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                            Saving...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form> <!-- Form END -->
        </div>
    </div>
</div>
@endsection


<script>
// Wait for all scripts to load
window.addEventListener('load', function() {
    // Double check jQuery and Swal are loaded
    if (typeof jQuery === 'undefined') {
        console.error('jQuery not loaded!');
        return;
    }
    
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 not loaded!');
        return;
    }
    
    (function($) {
        'use strict';
        
        // Clear previous errors on input
        $(document).on('input', '#fuel_price, #company_profit', function() {
            $(this).removeClass('is-invalid');
            $('#' + $(this).attr('id') + '_error').text('').hide();
        });

        // Form submission
        $(document).on('submit', '#generalSettingsForm', function(e) {
            e.preventDefault();
            
            // Clear previous errors
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').text('').hide();
            
            // Show loading state
            $('#btnText').addClass('d-none');
            $('#btnSpinner').removeClass('d-none');
            $('#submitBtn').prop('disabled', true);
            
            // Get form data
            var formData = {
                _token: $('input[name="_token"]').val(),
                fuel_price: $('#fuel_price').val(),
                company_profit: $('#company_profit').val()
            };
            
            // AJAX request
            $.ajax({
                url: '{{ route("general.settings.update") }}',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    // Hide loading state
                    $('#btnText').removeClass('d-none');
                    $('#btnSpinner').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                    
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'General settings updated successfully!',
                        timer: 3000,
                        showConfirmButton: false,
                        timerProgressBar: true,
                        toast: true,
                        position: 'top-end'
                    });
                    
                    // Update button text if it was "Save Settings"
                    if ($('#btnText').text().trim() === 'Save Settings') {
                        $('#btnText').text('Update Settings');
                    }
                },
                error: function(xhr) {
                    // Hide loading state
                    $('#btnText').removeClass('d-none');
                    $('#btnSpinner').addClass('d-none');
                    $('#submitBtn').prop('disabled', false);
                    
                    if (xhr.status === 422) {
                        // Validation errors
                        var errors = xhr.responseJSON.errors;
                        
                        // Display errors below each field
                        $.each(errors, function(field, messages) {
                            var input = $('#' + field);
                            var errorDiv = $('#' + field + '_error');
                            
                            input.addClass('is-invalid');
                            errorDiv.html(messages.join('<br>')).show();
                        });
                        
                        // Show toast notification for validation errors
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please fix the errors below',
                            timer: 3000,
                            showConfirmButton: false,
                            timerProgressBar: true,
                            toast: true,
                            position: 'top-end'
                        });
                    } else {
                        // Other errors
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Something went wrong. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });
        
    })(jQuery);
});
</script>
