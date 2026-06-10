@extends('layouts.app')

@section('title')
    {{ $name ?? 'maintenance_types' }}
@endsection

@section('content')
    <form id="roleForm" action="{{ isset($user) ? route('maintenance.update', $user->id) : route('maintenance.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf
        @if(isset($user))
            @method('PUT')
        @endif

        <div class="d-flex justify-content-end mb-3">
            <button type="submit" class="btn mbl-btn theme-btn submit rounded-pill px-4 py-2 shadow-lg me-2">
                {{ isset($maintenance) ? "Update {$name}" : "Add {$name}" }}
            </button>
            <a href="{{ route('maintenance.index') }}"
                class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-gold">
                <div class="d-flex justify-content-center">
                    <h5 class="mb-0 fs-13">{{ isset($maintenance) ? "Edit {$name}" : "Add {$name}" }}</h5>
                </div>
            </div>

            <div class="card-body">
                <!-- Vehicle Selection -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Vehicle *</label>
                        <select name="vehicle_id"
                            class="form-control fs-13 sidebar-wrapper  @error('vehicle_id') is-invalid @enderror">
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $maintenance->vehicle_id ?? '') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->vehicle_id }}
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="vehicle_id_error"></div>
                    </div>

                    <!-- Driver Selection -->
                    <div class="col-md-6">
                        <label class="form-label fs-13">Driver (Optional)</label>
                        <select name="driver_id"
                            class="form-control fs-13 sidebar-wrapper @error('driver_id') is-invalid @enderror">
                            <option value="">Select Driver</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" {{ old('driver_id', $maintenance->driver_id ?? '') == $driver->id ? 'selected' : '' }}>
                                    {{ $driver->firstname }} {{ $driver->lastname }}
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="driver_id_error"></div>
                    </div>
                </div>

                <!-- Maintenance Type & Date -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Maintenance Type *</label>
                        <select name="maintenance_type_id"
                            class="form-control fs-13 sidebar-wrapper @error('maintenance_type_id') is-invalid @enderror">
                            <option value="">Select Maintenance Type</option>
                            @foreach($maintenance_types as $type)
                                <option value="{{ $type->id }}" {{ old('maintenance_type_id', $maintenance->maintenance_type_id ?? '') == $type->id ? 'selected' : '' }}>
                                    {{ $type->maintenance_types }}
                                </option>
                            @endforeach
                        </select>
                        @error('maintenance_type_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="maintenance_type_id_error"></div>
                    </div>

                    <div class="col-md-6 fs-13">
                        <!-- Use component for maintenance date -->
                        <x-calendar-input 
                            name="maintenance_date" 
                            label="Maintenance Date *"
                            id="maintenance_date"
                            value="{{ old('maintenance_date', isset($maintenance) ? $maintenance->maintenance_date->format('Y-m-d') : date('Y-m-d')) }}"
                            required="true"
                            placeholder="Select maintenance date"
                            error="{{ $errors->first('maintenance_date') }}"
                            showErrorDiv="false"
                        />
                        @error('maintenance_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="maintenance_date_error"></div>
                    </div>
                </div>

                <!-- Cost & Status -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fs-13">Cost ($)</label>
                        <input type="number" name="cost" step="0.01" min="0"
                            class="form-control fs-13 sidebar-wrapper @error('cost') is-invalid @enderror"
                            placeholder="Enter cost" value="{{ old('cost', $maintenance->cost ?? '0.00') }}">
                        @error('cost')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="cost_error"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fs-13">Status *</label>
                        <select name="status" id="status_select"
                            class="form-control fs-13 sidebar-wrapper @error('status') is-invalid @enderror"
                            @if(!isset($maintenance)) disabled @endif>
                            <option value="scheduled" {{ old('status', $maintenance->status ?? '') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                            <option value="completed" {{ old('status', $maintenance->status ?? '') == 'completed' ? 'selected' : '' }}>Completed</option>

                            {{-- Only show Cancelled option when editing --}}
                            @if(isset($maintenance))
                                <option value="cancelled" {{ old('status', $maintenance->status ?? '') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            @endif
                        </select>

                        {{-- Keep status value in form even when disabled --}}
                        @if(!isset($maintenance))
                            <input type="hidden" name="status" id="hidden_status" value="scheduled">
                        @endif

                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="status_error"></div>
                    </div>
                </div>

                <!-- Next Maintenance Schedule -->
                <div class="row mb-3">
                    <div class="col-md-6 fs-13">
                        <!-- Use component for next maintenance date -->
                        <x-calendar-input 
                            name="next_maintenance_date" 
                            label="Next Maintenance Date Alert"
                            value="{{ old('next_maintenance_date', $maintenance->next_maintenance_date ?? '') }}"
                            placeholder="Select next maintenance date"
                            error="{{ $errors->first('next_maintenance_date') }}"
                            showErrorDiv="false"
                        />
                        @error('next_maintenance_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="next_maintenance_date_error"></div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fs-13">Next Maintenance Miles</label>
                        <input type="number" name="next_maintenance_miles"
                            class="form-control fs-13 sidebar-wrapper @error('next_maintenance_miles') is-invalid @enderror"
                            placeholder="Enter next maintenance mileage"
                            value="{{ old('next_maintenance_miles', $maintenance->next_maintenance_miles ?? '') }}">
                        @error('next_maintenance_miles')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="next_maintenance_miles_error"></div>
                    </div>
                </div>

                <!-- Description -->
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label fs-13">Description *</label>
                        <textarea name="description"
                            class="form-control fs-13 sidebar-wrapper @error('description') is-invalid @enderror" rows="3"
                            placeholder="Enter maintenance description">{{ old('description', $maintenance->description ?? '') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="invalid-feedback" id="description_error"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @include('maintenance.index-js')
@endsection