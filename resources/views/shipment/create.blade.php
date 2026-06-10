@extends('layouts.app')

@section('title')
{{ $name ?? 'Shipment Form' }}
@endsection

@section('content')

<style>
.datetime-input-wrapper {
    position: relative;
}

.datetime-input-wrapper .calendar-icon {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    width: 24px;
    height: 24px;
    pointer-events: none;
    z-index: 1;
}

.datetime-input-wrapper input[type="datetime-local"] {
    padding-right: 45px;
    cursor: pointer;
}

.datetime-input-wrapper input[type="datetime-local"]::-webkit-calendar-picker-indicator {
    opacity: 0;
    cursor: pointer;
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    width: 100%;
    height: 100%;
}

/* Responsive datetime styles */
.responsive-datetime-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    width: 100%;
}

.responsive-datetime-group .date-input,
.responsive-datetime-group .time-input {
    position: relative;
    flex: 1;
    min-width: 120px;
}

/* Icon styles for mobile inputs */
.responsive-datetime-group .date-input .calendar-icon,
.responsive-datetime-group .time-input .clock-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    pointer-events: all; /* Changed from none to all */
    z-index: 3; /* Higher z-index */
    cursor: pointer;
}

/* Inline style for inputs */
.responsive-datetime-group input[type="date"],
.responsive-datetime-group input[type="time"] {
    position: relative;
    z-index: 1;
    padding-right: 40px !important; /* Ensure space for icons */
}

/* Hide/show logic */
.datetime-desktop {
    display: block !important;
}

.datetime-mobile {
    display: none !important;
}

@media (max-width: 768px) {
    .datetime-desktop {
        display: none !important;
    }
    
    .datetime-mobile {
        display: flex !important;
    }
    
    .responsive-datetime-group {
        flex-direction: column;
        gap: 8px;
    }
    
    .responsive-datetime-group .date-input,
    .responsive-datetime-group .time-input {
        min-width: 100%;
    }
    
    .responsive-datetime-group input[type="date"],
    .responsive-datetime-group input[type="time"] {
        padding-right: 40px !important;
        cursor: pointer;
        font-size: 16px !important;
        height: 45px;
        background-color: white;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        width: 100%;
    }
    
    /* Hide browser's default icons but keep them functional */
    .responsive-datetime-group input[type="time"]::-webkit-calendar-picker-indicator,
    .responsive-datetime-group input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        left: 0;
        right: 40px; /* Leave space for our icon */
        top: 0;
        bottom: 0;
        width: calc(100% - 40px);
        height: 100%;
        cursor: pointer;
        z-index: 2;
    }
    
    .responsive-datetime-group input[type="time"]::-webkit-inner-spin-button,
    .responsive-datetime-group input[type="time"]::-webkit-clear-button {
        -webkit-appearance: none;
        margin: 0;
        height: 45px;
        width: 40px;
        position: absolute;
        right: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    
    /* For Firefox */
    .responsive-datetime-group input[type="date"]::-moz-calendar-picker-indicator,
    .responsive-datetime-group input[type="time"]::-moz-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        left: 0;
        right: 40px;
        top: 0;
        bottom: 0;
        width: calc(100% - 40px);
        height: 100%;
        cursor: pointer;
        z-index: 2;
    }
    
    /* Make the entire input area clickable */
    .responsive-datetime-group .date-input,
    .responsive-datetime-group .time-input {
        position: relative;
    }
    
    /* Position our custom icons on top */
    .responsive-datetime-group .date-input .calendar-icon,
    .responsive-datetime-group .time-input .clock-icon {
        z-index: 3;
        pointer-events: auto;
        cursor: pointer;
        background-color: white;
               border-radius: 10px;
    }
    
    /* For Safari */
    .responsive-datetime-group input[type="date"]::-webkit-datetime-edit-fields-wrapper,
    .responsive-datetime-group input[type="time"]::-webkit-datetime-edit-fields-wrapper {
        padding: 0;
        padding-right: 40px; /* Make space for icon */
    }
    
    .responsive-datetime-group input[type="date"]::-webkit-datetime-edit-text,
    .responsive-datetime-group input[type="date"]::-webkit-datetime-edit-month-field,
    .responsive-datetime-group input[type="date"]::-webkit-datetime-edit-day-field,
    .responsive-datetime-group input[type="date"]::-webkit-datetime-edit-year-field,
    .responsive-datetime-group input[type="time"]::-webkit-datetime-edit-text,
    .responsive-datetime-group input[type="time"]::-webkit-datetime-edit-hour-field,
    .responsive-datetime-group input[type="time"]::-webkit-datetime-edit-minute-field,
    .responsive-datetime-group input[type="time"]::-webkit-datetime-edit-ampm-field {
        padding: 0;
        color: inherit;
    }
}

@media (min-width: 769px) {
    .datetime-desktop {
        display: block !important;
    }
    
    .datetime-mobile {
        display: none !important;
    }
}
</style>

<div>
    <form id="roleForm" action="{{ isset($shipment) ? route('shipments.update', $shipment->id) : route('shipments.store') }}" method="POST">
        @csrf
        @if(isset($shipment))
        @method('PUT')
        @endif

        <div class="d-flex justify-content-end mb-3">
            <button type="submit" class="btn mbl-btn theme-btn rounded-pill px-4 py-2 shadow-lg me-2">
                {{ isset($shipment) ? "Update {$name}" : "Add {$name}" }}
            </button>
            <a href="{{ route('shipments.index') }}" class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header sidebar-wrapper bg-gold text-center">
                <h5 class="mb-0 fs-13">{{ isset($shipment) ? "Edit {$name}" : "Add {$name}" }}</h5>
            </div>

            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Customer</label>
                        <select name="customer_id" class="fs-13 form-control sidebar-wrapper">
                            <option value="">Select Customer</option>
                            @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $shipment->customer_id ?? '') == $customer->id ? 'selected' : '' }}>
                                {{ trim($customer->first_name . ' ' . $customer->last_name) }}
                            </option>
                            @endforeach
                        </select>
                        @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fs-13">Vehicle Type</label>
                        <select name="vehicle_type_id" id="vehicle_type_id" class="fs-13 form-control sidebar-wrapper">
                            <option value="">Select Vehicle Type</option>
                            @foreach($vehicleTypes as $type)
                            <option value="{{ $type->id }}"
                                data-mpg="{{ $type->avg_fuel_efficiency }}"
                                data-rate="{{ $type->driver_cost_per_mile }}"
                                data-insurance="{{ $type->insurance_per_mile ?? 0.10 }}"
                                data-maintenance="{{ $type->maintenance_per_mile ?? 0.15 }}"
                                data-overhead="{{ $type->overhead_per_mile ?? 0.10 }}"
                                data-ifta="{{ $type->ifta_per_mile ?? 0.05 }}"
                                {{ old('vehicle_type_id', $shipment->vehicle_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                {{ $type->vehicle_type }}
                            </option>
                            @endforeach
                        </select>
                        @error('vehicle_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Cost Estimate Preview --}}
                <div class="row mb-3" id="costPreviewWrap" style="display:none;">
                    <div class="col-12">
                        <div class="card sidebar-wrapper border-0" style="border-left: 3px solid var(--hover-color) !important;">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-calculator" style="color:var(--hover-color);"></i>
                                    <span class="fs-13 fw-bold">Cost Estimate Preview</span>
                                    <small class="text-muted fs-11">— same formula saved to invoice</small>
                                </div>
                                <div class="row text-center g-2">
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Distance</div>
                                        <div class="fs-13 fw-bold" id="previewMiles">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Fuel</div>
                                        <div class="fs-13 fw-bold" id="previewFuel">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Driver Pay</div>
                                        <div class="fs-13 fw-bold" id="previewDriver">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Insurance</div>
                                        <div class="fs-13 fw-bold" id="previewInsurance">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Maintenance</div>
                                        <div class="fs-13 fw-bold" id="previewMaintenance">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Overhead</div>
                                        <div class="fs-13 fw-bold" id="previewOverhead">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">IFTA Tax</div>
                                        <div class="fs-13 fw-bold" id="previewIfta">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Tolls</div>
                                        <div class="fs-13 fw-bold" id="previewTolls">$0</div>
                                    </div>
                                    <div class="col-4 col-md" id="previewAccessorialRow" style="display:none;">
                                        <div class="fs-11 text-muted">Accessorial</div>
                                        <div class="fs-13 fw-bold" id="previewAccessorial">$0</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Subtotal</div>
                                        <div class="fs-13 fw-bold" id="previewSubtotal">—</div>
                                    </div>
                                    <div class="col-4 col-md">
                                        <div class="fs-11 text-muted">Customer Price</div>
                                        <div class="fs-13 fw-bold" style="color:var(--hover-color);" id="previewTotal">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fs-13">Weight (kg)</label>
                        <input type="number" name="weight" class="fs-13 form-control sidebar-wrapper" value="{{ old('weight', $shipment->weight ?? '') }}">
                        @error('weight') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Volume (cu ft)</label>
                        <input type="number" name="volume" class="fs-13 form-control sidebar-wrapper" value="{{ old('volume', $shipment->volume ?? '') }}">
                        @error('volume') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Number of Pallets</label>
                        <input type="number" name="pallets" class="fs-13 form-control sidebar-wrapper" value="{{ old('pallets', $shipment->pallets ?? '') }}">
                        @error('pallets') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Pickup Address</label>
                        <input type="text" name="pickup_address" id="pickup_address" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('pickup_address', $shipment->pickup_address ?? '') }}">
                        @error('pickup_address') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Drop Address</label>
                        <input type="text" name="drop_address" id="drop_address" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('drop_address', $shipment->drop_address ?? '') }}">
                        @error('drop_address') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Pickup Date & Time - Responsive Fields -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Pickup Date & Time</label>
                        
                        <!-- Desktop: Single datetime-local field -->
                        <div class="datetime-input-wrapper datetime-desktop">
                            <input type="datetime-local" 
                                   name="pickup_time" 
                                   id="pickup_time_desktop" 
                                   class="fs-13 form-control sidebar-wrapper" 
                                   value="{{ old('pickup_time', isset($shipment->pickup_time) ? \Carbon\Carbon::parse($shipment->pickup_time)->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') : '') }}">
                            <img src="{{ asset('assets/img/calender.png') }}" class="calendar-icon" alt="Calendar">
                        </div>
                        
                        <!-- Mobile: Separate date and time fields -->
                        <div class="datetime-mobile responsive-datetime-group">
                            <div class="date-input">
                                <input type="date" 
                                       name="pickup_date_mobile" 
                                       id="pickup_date_mobile" 
                                       class="fs-13 form-control sidebar-wrapper"
                                       value="{{ old('pickup_date_mobile', isset($shipment->pickup_time) ? \Carbon\Carbon::parse($shipment->pickup_time)->setTimezone(config('app.timezone'))->format('Y-m-d') : '') }}">
                                <img src="{{ asset('assets/img/calender.png') }}" class="calendar-icon clickable-icon" alt="Calendar" onclick="openDatePicker('pickup_date_mobile')">
                            </div>
                            <div class="time-input">
                                <input type="time" 
                                       name="pickup_time_mobile" 
                                       id="pickup_time_mobile" 
                                       class="fs-13 form-control sidebar-wrapper"
                                       value="{{ old('pickup_time_mobile', isset($shipment->pickup_time) ? \Carbon\Carbon::parse($shipment->pickup_time)->setTimezone(config('app.timezone'))->format('H:i') : '12:00') }}">
                                <img src="{{ asset('assets/img/clock.png') }}" class="clock-icon clickable-icon" alt="Clock" onclick="openTimePicker('pickup_time_mobile')">
                            </div>
                        </div>
                        
                        @error('pickup_time') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fs-13">Expected Delivery Date & Time</label>
                        
                        <!-- Desktop: Single datetime-local field -->
                        <div class="datetime-input-wrapper datetime-desktop">
                            <input type="datetime-local" 
                                   name="delivery_time" 
                                   id="delivery_time_desktop" 
                                   class="fs-13 form-control sidebar-wrapper" 
                                   value="{{ old('delivery_time', isset($shipment->delivery_time) ? \Carbon\Carbon::parse($shipment->delivery_time)->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') : '') }}">
                            <img src="{{ asset('assets/img/calender.png') }}" class="calendar-icon" alt="Calendar">
                        </div>
                        
                        <!-- Mobile: Separate date and time fields -->
                        <div class="datetime-mobile responsive-datetime-group">
                            <div class="date-input">
                                <input type="date" 
                                       name="delivery_date_mobile" 
                                       id="delivery_date_mobile" 
                                       class="fs-13 form-control sidebar-wrapper"
                                       value="{{ old('delivery_date_mobile', isset($shipment->delivery_time) ? \Carbon\Carbon::parse($shipment->delivery_time)->setTimezone(config('app.timezone'))->format('Y-m-d') : '') }}">
                                <img src="{{ asset('assets/img/calender.png') }}" class="calendar-icon clickable-icon" alt="Calendar" onclick="openDatePicker('delivery_date_mobile')">
                            </div>
                            <div class="time-input">
                                <input type="time" 
                                       name="delivery_time_mobile" 
                                       id="delivery_time_mobile" 
                                       class="fs-13 form-control sidebar-wrapper"
                                       value="{{ old('delivery_time_mobile', isset($shipment->delivery_time) ? \Carbon\Carbon::parse($shipment->delivery_time)->setTimezone(config('app.timezone'))->format('H:i') : '12:00') }}">
                                <img src="{{ asset('assets/img/clock.png') }}" class="clock-icon clickable-icon" alt="Clock" onclick="openTimePicker('delivery_time_mobile')">
                            </div>
                        </div>
                        
                        @error('delivery_time') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Special Handling Instructions</label>
                        <textarea name="special_instructions" class="fs-13 form-control sidebar-wrapper">{{ old('special_instructions', $shipment->special_instructions ?? '') }}</textarea>
                        @error('special_instructions') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Equipment Required <span class="text-danger">*</span></label>
                        @php
                        $selectedEquipments = old('equipment_required', $shipment->equipment_required ?? []);
                        @endphp
                        
                        <div class="form-check">
                            <input class="form-check-input fs-13" type="checkbox" name="equipment_required[]" value="liftgate" 
                                id="equipment_liftgate" {{ in_array('liftgate', $selectedEquipments) ? 'checked' : '' }}>
                            <label class="form-check-label fs-13" for="equipment_liftgate">
                                Liftgate
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input fs-13" type="checkbox" name="equipment_required[]" value="hazmat" 
                                id="equipment_hazmat" {{ in_array('hazmat', $selectedEquipments) ? 'checked' : '' }}>
                            <label class="form-check-label fs-13" for="equipment_hazmat">
                                Hazmat
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input fs-13" type="checkbox" name="equipment_required[]" value="temperature_control" 
                                id="equipment_temp" {{ in_array('temperature_control', $selectedEquipments) ? 'checked' : '' }}>
                            <label class="form-check-label fs-13" for="equipment_temp">
                                Temperature Controlled
                            </label>
                        </div>
                        
                        @error('equipment_required') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fs-13">Load Type <span class="text-danger">*</span></label>
                        <select name="load_type" class="fs-13 form-control sidebar-wrapper">
                            <option value="FTL" {{ old('load_type', $shipment->load_type ?? 'FTL') == 'FTL' ? 'selected' : '' }}>FTL — Full Truck Load</option>
                            <option value="LTL" {{ old('load_type', $shipment->load_type ?? '') == 'LTL' ? 'selected' : '' }}>LTL — Less Than Truck Load</option>
                        </select>
                        @error('load_type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Estimated Tolls ($)</label>
                        <input type="number" step="0.01" min="0" name="tolls_fee" id="tolls_fee"
                            class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('tolls_fee', isset($shipment->shipmentInvoice) ? ($shipment->shipmentInvoice->first()->tolls_fee ?? 0) : 0) }}"
                            placeholder="0.00">
                        <small class="text-muted fs-11">Known toll charges on this route</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Reference / PO Number</label>
                        <input type="text" name="reference_number" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('reference_number', $shipment->reference_number ?? '') }}"
                            placeholder="e.g. PO-20240519">
                        @error('reference_number') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Pickup Contact Name</label>
                        <input type="text" name="pickup_contact_name" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('pickup_contact_name', $shipment->pickup_contact_name ?? '') }}"
                            placeholder="Contact at pickup location">
                        @error('pickup_contact_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Pickup Contact Phone</label>
                        <input type="text" name="pickup_contact_phone" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('pickup_contact_phone', $shipment->pickup_contact_phone ?? '') }}"
                            placeholder="+1 (555) 000-0000">
                        @error('pickup_contact_phone') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Delivery Contact Name</label>
                        <input type="text" name="delivery_contact_name" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('delivery_contact_name', $shipment->delivery_contact_name ?? '') }}"
                            placeholder="Contact at delivery location">
                        @error('delivery_contact_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Delivery Contact Phone</label>
                        <input type="text" name="delivery_contact_phone" class="fs-13 form-control sidebar-wrapper"
                            value="{{ old('delivery_contact_phone', $shipment->delivery_contact_phone ?? '') }}"
                            placeholder="+1 (555) 000-0000">
                        @error('delivery_contact_phone') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Additional Charges --}}
                <div class="mb-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start" id="accessorialToggle">
                        <i class="bi bi-plus-circle me-1" id="accessorialIcon"></i>
                        <strong>Additional Charges</strong>
                        <small class="text-muted ms-2">(optional — deadhead, detention, lumper, etc.)</small>
                    </button>
                </div>

                <div id="accessorialSection" style="display:none;">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fs-13">Deadhead Miles</label>
                            <input type="number" step="0.1" min="0" name="deadhead_miles" id="deadhead_miles"
                                class="fs-13 form-control sidebar-wrapper accessorial-field"
                                value="{{ old('deadhead_miles', $shipment->deadhead_miles ?? '') }}"
                                placeholder="0">
                            <small class="text-muted fs-11">Empty miles to pickup @ $0.75/mi</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">Detention Hours</label>
                            <input type="number" step="0.5" min="0" name="detention_hours" id="detention_hours"
                                class="fs-13 form-control sidebar-wrapper accessorial-field"
                                value="{{ old('detention_hours', $shipment->detention_hours ?? '') }}"
                                placeholder="0">
                            <small class="text-muted fs-11">Wait time beyond free 2hrs @ $65/hr</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">Lumper Fee ($)</label>
                            <input type="number" step="0.01" min="0" name="lumper_fee" id="lumper_fee"
                                class="fs-13 form-control sidebar-wrapper accessorial-field"
                                value="{{ old('lumper_fee', $shipment->lumper_fee ?? '') }}"
                                placeholder="0.00">
                            <small class="text-muted fs-11">Loading/unloading labor cost</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fs-13">Driver Per Diem (days)</label>
                            <input type="number" step="1" min="0" name="per_diem_days" id="per_diem_days"
                                class="fs-13 form-control sidebar-wrapper accessorial-field"
                                value="{{ old('per_diem_days', $shipment->per_diem_days ?? '') }}"
                                placeholder="0">
                            <small class="text-muted fs-11">Multi-day trips @ $65/day</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">Scale / Weigh Fees ($)</label>
                            <input type="number" step="0.01" min="0" name="scale_fees" id="scale_fees"
                                class="fs-13 form-control sidebar-wrapper accessorial-field"
                                value="{{ old('scale_fees', $shipment->scale_fees ?? '') }}"
                                placeholder="0.00">
                            <small class="text-muted fs-11">Weigh station fees en route</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">Permit Fee ($)</label>
                            <input type="number" step="0.01" min="0" name="permit_fee" id="permit_fee"
                                class="fs-13 form-control sidebar-wrapper accessorial-field"
                                value="{{ old('permit_fee', $shipment->permit_fee ?? '') }}"
                                placeholder="0.00">
                            <small class="text-muted fs-11">Oversize / overweight permits</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 d-flex align-items-center gap-2 pt-2">
                            <input type="hidden" name="tarp_required" value="0">
                            <input type="checkbox" name="tarp_required" id="tarp_required" value="1"
                                class="form-check-input accessorial-field"
                                {{ old('tarp_required', $shipment->tarp_required ?? false) ? 'checked' : '' }}>
                            <label class="form-label fs-13 mb-0" for="tarp_required">
                                Tarp Required <small class="text-muted">(+$100 flatbed)</small>
                            </label>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
</div>

@include('shipment.index-js')

<script>
// Timezone conversion helper
function convertUTCToLocal(utcString) {
    if (!utcString) return '';
    
    try {
        // Parse UTC datetime
        const utcDate = new Date(utcString + 'Z');
        if (isNaN(utcDate.getTime())) return '';
        
        // Convert to local time for display
        const year = utcDate.getFullYear();
        const month = String(utcDate.getMonth() + 1).padStart(2, '0');
        const day = String(utcDate.getDate()).padStart(2, '0');
        const hours = String(utcDate.getHours()).padStart(2, '0');
        const minutes = String(utcDate.getMinutes()).padStart(2, '0');
        
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    } catch (error) {
        console.error('Error converting UTC to local:', error);
        return '';
    }
}

// Functions to open pickers when clicking icons
function openDatePicker(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.focus();
        input.showPicker ? input.showPicker() : input.click();
    }
}

function openTimePicker(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.focus();
        input.showPicker ? input.showPicker() : input.click();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Set min values for date inputs
    const today = new Date().toISOString().split('T')[0];
    
    // Set min for mobile date inputs
    const pickupDateMobile = document.getElementById('pickup_date_mobile');
    const deliveryDateMobile = document.getElementById('delivery_date_mobile');
    
    if (pickupDateMobile) pickupDateMobile.setAttribute('min', today);
    if (deliveryDateMobile) deliveryDateMobile.setAttribute('min', today);
    
    // Set min datetime for desktop inputs
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
    
    const pickupDesktop = document.getElementById('pickup_time_desktop');
    const deliveryDesktop = document.getElementById('delivery_time_desktop');
    
    if (pickupDesktop) pickupDesktop.setAttribute('min', minDateTime);
    if (deliveryDesktop) deliveryDesktop.setAttribute('min', minDateTime);
    
    // Initialize with today's date for mobile if empty
    if (pickupDateMobile && !pickupDateMobile.value) {
        pickupDateMobile.value = today;
    }
    if (deliveryDateMobile && !deliveryDateMobile.value) {
        deliveryDateMobile.value = today;
    }
    
    // For edit mode: Convert UTC times from database to local time for display
    <?php if(isset($shipment) && $shipment->pickup_time): ?>
    const pickupUTC = "{{ $shipment->pickup_time }}";
    const deliveryUTC = "{{ $shipment->delivery_time }}";
    
    // Convert UTC to local time for display
    const localPickup = convertUTCToLocal(pickupUTC);
    const localDelivery = convertUTCToLocal(deliveryUTC);
    
    if (localPickup) {
        // Update desktop field
        if (pickupDesktop) pickupDesktop.value = localPickup;
        
        // Update mobile fields
        const [pickupDate, pickupTime] = localPickup.split('T');
        if (pickupDateMobile && pickupDate) pickupDateMobile.value = pickupDate;
        if (document.getElementById('pickup_time_mobile') && pickupTime) {
            document.getElementById('pickup_time_mobile').value = pickupTime.substring(0, 5);
        }
    }
    
    if (localDelivery) {
        // Update desktop field
        if (deliveryDesktop) deliveryDesktop.value = localDelivery;
        
        // Update mobile fields
        const [deliveryDate, deliveryTime] = localDelivery.split('T');
        if (deliveryDateMobile && deliveryDate) deliveryDateMobile.value = deliveryDate;
        if (document.getElementById('delivery_time_mobile') && deliveryTime) {
            document.getElementById('delivery_time_mobile').value = deliveryTime.substring(0, 5);
        }
    }
    <?php endif; ?>
    
    // Initialize desktop fields from mobile fields if needed
    syncDesktopFromMobile();
    
    // Setup event listeners
    setupMobileListeners();
    setupDesktopListeners();
    
    // Setup click handlers for icons
    setupIconClickHandlers();
    
    // Setup form submission
    setupFormSubmission();
});

// Setup click handlers for custom icons
function setupIconClickHandlers() {
    // Add click handlers to all clickable icons
    document.querySelectorAll('.clickable-icon').forEach(icon => {
        // Remove inline onclick if exists (we'll use event listener)
        icon.removeAttribute('onclick');
        
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Get the associated input based on icon class
            const wrapper = this.closest('.date-input, .time-input');
            if (wrapper) {
                const input = wrapper.querySelector('input');
                if (input) {
                    input.focus();
                    // Use showPicker() if supported, otherwise click()
                    if (input.showPicker) {
                        input.showPicker();
                    } else {
                        input.click();
                    }
                }
            }
        });
    });
    
    // Make the entire input area clickable
    document.querySelectorAll('.date-input, .time-input').forEach(wrapper => {
        wrapper.addEventListener('click', function(e) {
            // Don't trigger if clicking on the icon
            if (e.target.classList.contains('clickable-icon') || 
                e.target.closest('.clickable-icon')) {
                return;
            }
            
            const input = wrapper.querySelector('input');
            if (input) {
                input.focus();
                // Use showPicker() if supported, otherwise click()
                if (input.showPicker) {
                    input.showPicker();
                } else {
                    input.click();
                }
            }
        });
    });
}

// Sync desktop fields from mobile fields (for initial load)
function syncDesktopFromMobile() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        const pickupDate = document.getElementById('pickup_date_mobile')?.value;
        const pickupTime = document.getElementById('pickup_time_mobile')?.value;
        const deliveryDate = document.getElementById('delivery_date_mobile')?.value;
        const deliveryTime = document.getElementById('delivery_time_mobile')?.value;
        
        if (pickupDate && pickupTime) {
            const pickupDesktop = document.getElementById('pickup_time_desktop');
            if (pickupDesktop) {
                pickupDesktop.value = `${pickupDate}T${pickupTime}`;
            }
        }
        
        if (deliveryDate && deliveryTime) {
            const deliveryDesktop = document.getElementById('delivery_time_desktop');
            if (deliveryDesktop) {
                deliveryDesktop.value = `${deliveryDate}T${deliveryTime}`;
            }
        }
    }
}

// Mobile field listeners
function setupMobileListeners() {
    const mobileFields = [
        'pickup_date_mobile', 'pickup_time_mobile',
        'delivery_date_mobile', 'delivery_time_mobile'
    ];
    
    mobileFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                updateDesktopFromMobile();
                updateMobileValidation();
            });
        }
    });
}

// Update desktop fields when mobile fields change
function updateDesktopFromMobile() {
    const isMobile = window.innerWidth <= 768;
    
    if (!isMobile) return;
    
    const pickupDate = document.getElementById('pickup_date_mobile')?.value;
    const pickupTime = document.getElementById('pickup_time_mobile')?.value;
    const deliveryDate = document.getElementById('delivery_date_mobile')?.value;
    const deliveryTime = document.getElementById('delivery_time_mobile')?.value;
    
    if (pickupDate && pickupTime) {
        const pickupDesktop = document.getElementById('pickup_time_desktop');
        if (pickupDesktop) {
            pickupDesktop.value = `${pickupDate}T${pickupTime}`;
        }
    }
    
    if (deliveryDate && deliveryTime) {
        const deliveryDesktop = document.getElementById('delivery_time_desktop');
        if (deliveryDesktop) {
            deliveryDesktop.value = `${deliveryDate}T${deliveryTime}`;
        }
    }
}

// Update mobile validation
function updateMobileValidation() {
    const pickupDate = document.getElementById('pickup_date_mobile')?.value;
    const deliveryDate = document.getElementById('delivery_date_mobile')?.value;
    
    if (pickupDate && deliveryDate) {
        const deliveryDateInput = document.getElementById('delivery_date_mobile');
        if (deliveryDateInput) {
            deliveryDateInput.setAttribute('min', pickupDate);
            
            if (deliveryDateInput.value && deliveryDateInput.value < pickupDate) {
                deliveryDateInput.value = pickupDate;
                updateDesktopFromMobile();
            }
        }
    }
}

// Desktop field listeners
function setupDesktopListeners() {
    const desktopFields = ['pickup_time_desktop', 'delivery_time_desktop'];
    
    desktopFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('change', function() {
                updateDesktopValidation();
                updateMobileFromDesktop();
            });
        }
    });
}

// Update desktop validation
function updateDesktopValidation() {
    const pickupDesktop = document.getElementById('pickup_time_desktop')?.value;
    const deliveryDesktop = document.getElementById('delivery_time_desktop')?.value;
    
    if (pickupDesktop && deliveryDesktop) {
        const deliveryInput = document.getElementById('delivery_time_desktop');
        if (deliveryInput) {
            deliveryInput.setAttribute('min', pickupDesktop);
            
            if (deliveryInput.value && deliveryInput.value < pickupDesktop) {
                deliveryInput.value = pickupDesktop;
            }
        }
    }
}

// Update mobile fields when desktop fields change (for screen resize)
function updateMobileFromDesktop() {
    const isDesktop = window.innerWidth > 768;
    
    if (!isDesktop) return;
    
    const pickupDesktop = document.getElementById('pickup_time_desktop')?.value;
    const deliveryDesktop = document.getElementById('delivery_time_desktop')?.value;
    
    if (pickupDesktop) {
        const [datePart, timePart] = pickupDesktop.split('T');
        const pickupDate = document.getElementById('pickup_date_mobile');
        const pickupTime = document.getElementById('pickup_time_mobile');
        
        if (pickupDate && datePart) pickupDate.value = datePart;
        if (pickupTime && timePart) pickupTime.value = timePart.substring(0, 5);
    }
    
    if (deliveryDesktop) {
        const [datePart, timePart] = deliveryDesktop.split('T');
        const deliveryDate = document.getElementById('delivery_date_mobile');
        const deliveryTime = document.getElementById('delivery_time_mobile');
        
        if (deliveryDate && datePart) deliveryDate.value = datePart;
        if (deliveryTime && timePart) deliveryTime.value = timePart.substring(0, 5);
    }
}

// Form submission
function setupFormSubmission() {
    const form = document.getElementById('roleForm');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        // Ensure desktop fields are updated from mobile fields
        updateDesktopFromMobile();
        
        // Disable mobile-only inputs to prevent duplicate submission
        disableMobileInputs();
        
        // Validate delivery is after pickup
        if (!validateDateTimeOrder()) {
            e.preventDefault();
            alert('Delivery time must be after pickup time.');
            return false;
        }
        
        return true;
    });
}

// Disable mobile inputs before submission
function disableMobileInputs() {
    const mobileInputs = [
        'pickup_date_mobile', 'pickup_time_mobile',
        'delivery_date_mobile', 'delivery_time_mobile'
    ];
    
    mobileInputs.forEach(name => {
        const inputs = document.querySelectorAll(`input[name="${name}"]`);
        inputs.forEach(input => {
            input.disabled = true;
        });
    });
}

// Validate datetime order
function validateDateTimeOrder() {
    const pickupDesktop = document.getElementById('pickup_time_desktop')?.value;
    const deliveryDesktop = document.getElementById('delivery_time_desktop')?.value;
    
    if (!pickupDesktop || !deliveryDesktop) {
        return true; // Let server validation handle missing fields
    }
    
    const pickup = new Date(pickupDesktop);
    const delivery = new Date(deliveryDesktop);
    
    return delivery > pickup;
}

// ── Cost Estimate Preview ───────────────────────────────────────────────────
// Mirrors ShipmentInvoiceService formula exactly:
//   fuel_cost   = (miles / mpg) × fuel_price_per_gallon
//   driver_cost = driver_rate_per_mile × miles
//   subtotal    = fuel + driver + tolls
//   customer    = subtotal × (1 + profit% / 100)

const FUEL_PRICE = {{ $fuelPrice ?? 3.80 }};
const PROFIT_PCT = {{ $profitPct ?? 20 }};
const vTypeSel   = document.getElementById('vehicle_type_id');
const tollsInput = document.getElementById('tolls_fee');

vTypeSel?.addEventListener('change', updateCostPreview);
tollsInput?.addEventListener('input', updateCostPreview);

// Listen to all accessorial fields
document.querySelectorAll('.accessorial-field').forEach(f => {
    f.addEventListener('input', updateCostPreview);
    f.addEventListener('change', updateCostPreview);
});

// Toggle accessorial section
document.getElementById('accessorialToggle')?.addEventListener('click', function () {
    const section = document.getElementById('accessorialSection');
    const icon    = document.getElementById('accessorialIcon');
    const visible = section.style.display !== 'none';
    section.style.display = visible ? 'none' : 'block';
    icon.className = visible ? 'bi bi-plus-circle me-1' : 'bi bi-dash-circle me-1';
});

function updateCostPreview() {
    const vtypeOpt    = vTypeSel?.options[vTypeSel.selectedIndex];
    const mpg         = parseFloat(vtypeOpt?.dataset.mpg) || 0;
    const driverRate  = parseFloat(vtypeOpt?.dataset.rate) || 0;
    const insurance   = parseFloat(vtypeOpt?.dataset.insurance) || 0;
    const maintenance = parseFloat(vtypeOpt?.dataset.maintenance) || 0;
    const overhead    = parseFloat(vtypeOpt?.dataset.overhead) || 0;
    const ifta        = parseFloat(vtypeOpt?.dataset.ifta) || 0;
    const miles       = parseFloat(window._shipmentMiles) || 0;
    const tolls       = parseFloat(tollsInput?.value) || 0;

    // Accessorial values
    const deadheadMiles  = parseFloat(document.getElementById('deadhead_miles')?.value) || 0;
    const detentionHours = parseFloat(document.getElementById('detention_hours')?.value) || 0;
    const lumperFee      = parseFloat(document.getElementById('lumper_fee')?.value) || 0;
    const perDiemDays    = parseInt(document.getElementById('per_diem_days')?.value) || 0;
    const scaleFees      = parseFloat(document.getElementById('scale_fees')?.value) || 0;
    const tarpRequired   = document.getElementById('tarp_required')?.checked ? 100 : 0;
    const permitFee      = parseFloat(document.getElementById('permit_fee')?.value) || 0;

    const deadheadCost  = deadheadMiles * 0.75;
    const detentionCost = detentionHours * 65;
    const perDiemCost   = perDiemDays * 65;
    const accessorial   = deadheadCost + detentionCost + lumperFee + perDiemCost + scaleFees + tarpRequired + permitFee;

    if (!miles || !mpg || !driverRate) {
        document.getElementById('costPreviewWrap').style.display = 'none';
        return;
    }

    const fuelCost        = (miles / mpg) * FUEL_PRICE;
    const driverCost      = driverRate * miles;
    const insuranceCost   = insurance * miles;
    const maintenanceCost = maintenance * miles;
    const overheadCost    = overhead * miles;
    const iftaCost        = ifta * miles;
    const subtotal        = fuelCost + driverCost + insuranceCost + maintenanceCost + overheadCost + iftaCost + tolls + accessorial;
    const total           = subtotal * (1 + PROFIT_PCT / 100);

    document.getElementById('previewMiles').textContent       = miles.toFixed(0) + ' mi';
    document.getElementById('previewFuel').textContent        = '$' + fuelCost.toFixed(2);
    document.getElementById('previewDriver').textContent      = '$' + driverCost.toFixed(2);
    document.getElementById('previewInsurance').textContent   = '$' + insuranceCost.toFixed(2);
    document.getElementById('previewMaintenance').textContent = '$' + maintenanceCost.toFixed(2);
    document.getElementById('previewOverhead').textContent    = '$' + overheadCost.toFixed(2);
    document.getElementById('previewIfta').textContent        = '$' + iftaCost.toFixed(2);
    document.getElementById('previewTolls').textContent       = '$' + tolls.toFixed(2);
    if (accessorial > 0) {
        document.getElementById('previewAccessorial').textContent = '$' + accessorial.toFixed(2);
        document.getElementById('previewAccessorialRow').style.display = '';
    } else {
        document.getElementById('previewAccessorialRow').style.display = 'none';
    }
    document.getElementById('previewSubtotal').textContent    = '$' + subtotal.toFixed(2);
    document.getElementById('previewTotal').textContent       = '$' + total.toFixed(2);
    document.getElementById('costPreviewWrap').style.display  = 'block';
}

// Intercept the distance API response to feed the cost preview
const _origFetch = window.fetch;
window.fetch = async function (...args) {
    const resp = await _origFetch.apply(this, args);
    if (typeof args[0] === 'string' && args[0].includes('calculate-distance')) {
        resp.clone().json().then(data => {
            if (data?.distance_miles) {
                window._shipmentMiles = data.distance_miles;
                updateCostPreview();
            }
        }).catch(() => {});
    }
    return resp;
};

// Handle window resize
window.addEventListener('resize', function() {
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // When switching to mobile, sync mobile fields from desktop
        updateMobileFromDesktop();
    } else {
        // When switching to desktop, sync desktop fields from mobile
        updateDesktopFromMobile();
    }
});
</script>

@endsection