@extends('layouts.app')

@section('title')
    {{ $name ?? 'Vehicle Form' }}
@endsection

@php
    $calendarIcon = asset('assets/img/calender.png');
@endphp

@section('content')
    <form id="roleForm" action="{{ isset($user) ? route('vehicles.update', $user->id) : route('vehicles.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="d-flex justify-content-end mb-3">
            <button type="submit" class="btn mbl-btn theme-btn submit rounded-pill px-4 py-2 shadow-lg me-2">
                {{ isset($user) ? "Update {$name}" : "Add {$name}" }}
            </button>
            <a href="{{ route('vehicles.index') }}"
                class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-gold text-center">
                <h5 class="mb-0 fs-15">{{ isset($user) ? 'Edit Vehicle' : 'Add Vehicle' }}</h5>
            </div>

            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Vehicle ID</label>
                        <input type="text" name="vehicle_id" class="form-control sidebar-wrapper"
                            value="{{ old('vehicle_id', $user->vehicle_id ?? '') }}">
                        @error('vehicle_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fs-13">License Plate Number</label>
                        <input type="text" name="license_plate_number" class="form-control sidebar-wrapper"
                            value="{{ old('license_plate_number', $user->license_plate_number ?? '') }}">
                        @error('license_plate_number') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">VIN</label>
                        <input type="text" name="vin" class="form-control sidebar-wrapper"
                            value="{{ old('vin', $user->vin ?? '') }}">
                        @error('vin') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Make & Model</label>
                        <input type="text" name="make_model" class="form-control sidebar-wrapper"
                            value="{{ old('make_model', $user->make_model ?? '') }}">
                        @error('make_model') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fs-13">Year of Manufacture</label>
                        <input type="number" name="year_of_manufacture" class="form-control sidebar-wrapper"
                            min="1990" max="{{ date('Y') + 1 }}" placeholder="{{ date('Y') }}"
                            value="{{ old('year_of_manufacture', $user->year_of_manufacture ?? '') }}">
                        @error('year_of_manufacture') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Color</label>
                        <select name="color" class="form-control fs-13 sidebar-wrapper">
                            @php $currentColor = old('color', $user->color ?? ''); @endphp
                            @foreach(['White','Black','Silver','Grey','Red','Blue','Green','Yellow','Orange','Brown'] as $c)
                            <option value="{{ $c }}" {{ $currentColor == $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('color') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Fuel Type</label>
                        <select name="fuel_type" class="form-control fs-13 sidebar-wrapper">
                            @php $ft = old('fuel_type', $user->fuel_type ?? 'Diesel'); @endphp
                            <option value="Diesel"  {{ $ft == 'Diesel'  ? 'selected' : '' }}>Diesel</option>
                            <option value="Gasoline"{{ $ft == 'Gasoline'? 'selected' : '' }}>Gasoline</option>
                            <option value="CNG"     {{ $ft == 'CNG'     ? 'selected' : '' }}>CNG (Compressed Natural Gas)</option>
                            <option value="Electric"{{ $ft == 'Electric'? 'selected' : '' }}>Electric</option>
                            <option value="Hybrid"  {{ $ft == 'Hybrid'  ? 'selected' : '' }}>Hybrid</option>
                        </select>
                        @error('fuel_type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label fs-13">Ownership Status</label>
                        <select name="ownership_status" class="form-control fs-13 sidebar-wrapper">
                            <option value="">Select Ownership Status</option>
                            <option value="Owned"  {{ old('ownership_status', $user->ownership_status ?? '') == 'Owned'  ? 'selected' : '' }}>Owned</option>
                            <option value="Leased" {{ old('ownership_status', $user->ownership_status ?? '') == 'Leased' ? 'selected' : '' }}>Leased</option>
                            <option value="Rented" {{ old('ownership_status', $user->ownership_status ?? '') == 'Rented' ? 'selected' : '' }}>Rented</option>
                        </select>
                        @error('ownership_status') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Vehicle Status</label>
                        <select name="status" class="form-control fs-13 sidebar-wrapper">
                            @php $st = old('status', $user->status ?? 'available'); @endphp
                            <option value="available"    {{ $st == 'available'    ? 'selected' : '' }}>Available</option>
                            <option value="in_use"       {{ $st == 'in_use'       ? 'selected' : '' }}>In Use</option>
                            <option value="maintenance"  {{ $st == 'maintenance'  ? 'selected' : '' }}>In Maintenance</option>
                            <option value="out_of_service"{{ $st == 'out_of_service'? 'selected' : '' }}>Out of Service</option>
                        </select>
                        @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fs-13">Load Type Compatibility</label>
                        <select name="load_type_compatibility" class="form-control fs-13 sidebar-wrapper">
                            @php $ltc = old('load_type_compatibility', $user->load_type_compatibility ?? ''); @endphp
                            <option value="">Select Load Type</option>
                            <option value="Dry Van"   {{ $ltc == 'Dry Van'   ? 'selected' : '' }}>Dry Van</option>
                            <option value="Flatbed"   {{ $ltc == 'Flatbed'   ? 'selected' : '' }}>Flatbed</option>
                            <option value="Reefer"    {{ $ltc == 'Reefer'    ? 'selected' : '' }}>Reefer (Refrigerated)</option>
                            <option value="Tanker"    {{ $ltc == 'Tanker'    ? 'selected' : '' }}>Tanker</option>
                            <option value="Step Deck" {{ $ltc == 'Step Deck' ? 'selected' : '' }}>Step Deck</option>
                            <option value="Lowboy"    {{ $ltc == 'Lowboy'    ? 'selected' : '' }}>Lowboy</option>
                            <option value="Box Truck" {{ $ltc == 'Box Truck' ? 'selected' : '' }}>Box Truck</option>
                            <option value="Container" {{ $ltc == 'Container' ? 'selected' : '' }}>Container</option>
                            <option value="FTL/LTL"   {{ $ltc == 'FTL/LTL'  ? 'selected' : '' }}>FTL / LTL</option>
                        </select>
                        @error('load_type_compatibility') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Max Cargo Weight (lbs)</label>
                        <input type="number" name="cargo_weight" class="form-control sidebar-wrapper"
                            placeholder="e.g. 44000" min="0"
                            value="{{ old('cargo_weight', $user->cargo_weight ?? '') }}">
                        <small class="text-muted fs-11">DOT max for 18-wheeler = 44,000 lbs on drive axles</small>
                        @error('cargo_weight') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Cargo Volume (cu ft)</label>
                        <input type="number" name="cargo_volume" class="form-control sidebar-wrapper"
                            placeholder="e.g. 2500" min="0"
                            value="{{ old('cargo_volume', $user->cargo_volume ?? '') }}">
                        @error('cargo_volume') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Registration Expiry Date</label>
                        <input type="date" name="registration_expiry_date" class="sidebar-wrapper fs-13 form-control" 
                            value="{{ old('registration_expiry_date', $user->registration_expiry_date ?? '') }}"
                            style="
                                background-image: url('{{ $calendarIcon }}')!important;
                                background-repeat: no-repeat !important;
                                background-position: right 10px center !important;
                                background-size: 25px !important;
                                padding-right: 40px !important;
                            ">
                        @error('registration_expiry_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Insurance Details</label>
                        <input type="text" name="insurance_details" class="form-control fs-13 sidebar-wrapper"
                            value="{{ old('insurance_details', $user->insurance_details ?? '') }}">
                        @error('insurance_details') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Insurance Expiry Date</label>
                        <input type="date" name="insurance_expiry_date" class="sidebar-wrapper fs-13 form-control" 
                            value="{{ old('insurance_expiry_date', $user->insurance_expiry_date ?? '') }}"
                            style="
                                background-image: url('{{ $calendarIcon }}')!important;
                                background-repeat: no-repeat !important;
                                background-position: right 10px center !important;
                                background-size: 25px !important;
                                padding-right: 40px !important;
                            ">
                        @error('insurance_expiry_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Vehicle Type</label>
                        <div class="dropdown sidebar-wrapper vehicle-type-dropdown">
                            <button
                                class="btn fs-13 btn-outline-secondary sidebar-wrapper form-control text-start dropdown-toggle vehicle-type-btn"
                                type="button" id="vehicleTypeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ $user->vehicleType->vehicle_type ?? 'Select Vehicle Type' }}
                            </button>
                            <ul class="dropdown-menu vehicle-type-menu w-100" aria-labelledby="vehicleTypeDropdown">
                                @foreach($results as $type)
                                    <li>
                                        <a class="dropdown-item vehicle-option d-flex align-items-center" href="#"
                                            data-id="{{ $type->id }}" data-name="{{ ucfirst($type->vehicle_type) }}">
                                            <img src="{{ asset('storage/' . $type->image) }}" alt="{{ $type->vehicle_type }}"
                                                class="me-2" width="40" height="30">
                                            {{ ucfirst($type->vehicle_type) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            <input type="hidden" name="vehicle_type_id" id="vehicle_type_id"
                                value="{{ old('vehicle_type_id', $user->vehicle_type_id ?? '') }}">
                        </div>
                        @error('vehicle_type_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
        </div>
    </form>

    <style>
        /* Vehicle Type Dropdown Theme Styling */
        .vehicle-type-dropdown .vehicle-type-btn {
            background: var(--main-wrapper-bg) !important;
            color: var(--text-color) !important;
            border: 1px solid var(--chart-grid) !important;
            border-radius: 8px;
            font-family: 'Jost', sans-serif;
            transition: all 0.3s ease-in-out;
        }

        .vehicle-type-dropdown .vehicle-type-btn:hover,
        .vehicle-type-dropdown .vehicle-type-btn:focus {
            border-color: var(--hover-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(248, 199, 31, 0.25);
        }

        /* Dropdown Menu Styling */
        .vehicle-type-menu {
            background: var(--main-wrapper-bg) !important;
            border: 1px solid var(--chart-grid) !important;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            max-height: 300px;
            overflow-y: auto;
            margin-top: 5px !important;
        }

        /* Dropdown Items */
        .vehicle-type-menu .vehicle-option {
            color: var(--text-color) !important;
            padding: 10px 15px;
            font-family: 'Jost', sans-serif;
            transition: all 0.3s ease-in-out;
            border-radius: 6px;
            margin: 4px 8px;
        }

        .vehicle-type-menu .vehicle-option:hover {
            background: var(--hover-color) !important;
            color: #000000 !important;
            transform: translateX(5px);
        }

        .vehicle-type-menu .vehicle-option:active,
        .vehicle-type-menu .vehicle-option.active {
            background: var(--hover-color) !important;
            color: #000000 !important;
            font-weight: 600;
        }

        /* Selected State */
        .vehicle-type-btn.selected {
            border-color: var(--hover-color) !important;
            background: var(--striped-bg) !important;
        }

        /* Image in dropdown */
        .vehicle-type-menu .vehicle-option img {
            border-radius: 4px;
            object-fit: cover;
            transition: transform 0.3s ease-in-out;
        }

        .vehicle-type-menu .vehicle-option:hover img {
            transform: scale(1.1);
        }

        /* Scrollbar for dropdown */
        .vehicle-type-menu::-webkit-scrollbar {
            width: 6px;
        }

        .vehicle-type-menu::-webkit-scrollbar-track {
            background: var(--main-wrapper-bg);
            border-radius: 4px;
        }

        .vehicle-type-menu::-webkit-scrollbar-thumb {
            background: var(--chart-grid);
            border-radius: 4px;
            transition: background 0.3s ease-in-out;
        }

        .vehicle-type-menu::-webkit-scrollbar-thumb:hover {
            background: var(--hover-color);
        }

        /* Dark mode specific adjustments */
        [data-theme="dark"] .vehicle-type-menu {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        /* Light mode specific adjustments */
        [data-theme="light"] .vehicle-type-menu {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Dropdown arrow color */
        .vehicle-type-btn::after {
            color: var(--text-color) !important;
        }

        /* Focus state */
        .vehicle-type-dropdown .vehicle-type-btn:focus {
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(248, 199, 31, 0.25);
        }

        /* Color input styling */
        .color-box {
            height: 45px;
            cursor: pointer;
        }

        /* Form labels */
        .form-label {
            color: var(--text-color);
            font-family: 'Jost', sans-serif;
            font-weight: 500;
            margin-bottom: 8px;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownButton = document.getElementById('vehicleTypeDropdown');
            const vehicleOptions = document.querySelectorAll('.vehicle-option');
            
            vehicleOptions.forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    // Update hidden input
                    document.getElementById('vehicle_type_id').value = id;
                    
                    // Update button text
                    dropdownButton.textContent = name;
                    
                    // Add selected class
                    dropdownButton.classList.add('selected');
                    
                    // Remove active class from all items
                    vehicleOptions.forEach(opt => opt.classList.remove('active'));
                    
                    // Add active class to selected item
                    this.classList.add('active');
                });
            });

            // Set initial active state if editing
            const currentVehicleTypeId = document.getElementById('vehicle_type_id').value;
            if (currentVehicleTypeId) {
                vehicleOptions.forEach(item => {
                    if (item.getAttribute('data-id') === currentVehicleTypeId) {
                        item.classList.add('active');
                        dropdownButton.classList.add('selected');
                    }
                });
            }
        });
    </script>

    @include('vehicles.index-js')
@endsection