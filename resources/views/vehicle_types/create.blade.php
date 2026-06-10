@extends('layouts.app')

@section('title')
    {{ $name ?? 'Vehicle Form' }}
@endsection

@section('content')
<form id="roleForm" action="{{ isset($user) ? route('vehiclestype.update', $user->id) : route('vehiclestype.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn submit rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($user) ? "Update {$name}" : "Add {$name}" }}
        </button>
        <a href="{{ route('vehiclestype.index') }}" class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-gold">
            <div class="d-flex justify-content-center">
                <h5 class="mb-0 fs-15">{{ isset($user) ? 'Edit Vehicle Type' : 'Add Vehicle Type' }}</h5>
            </div>
        </div>

        <div class="card-body">
            {{-- Vehicle Type --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Vehicle Type</label>
                    <input type="text" name="vehicle_type" class="form-control sidebar-wrapper{{ $errors->has('vehicle_type') ? 'is-invalid' : '' }}"
                           placeholder="Enter Vehicle Type" value="{{ old('vehicle_type', $user->vehicle_type ?? '') }}">
                    @error('vehicle_type')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Vehicle Image --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Vehicle Image</label>
                    <input type="file" name="image" class="form-control fs-13 sidebar-wrapper{{ $errors->has('image') ? 'is-invalid' : '' }}">
                    @error('image')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror

                    @if(isset($user) && $user->image)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $user->image) }}" alt="Vehicle Image" width="100">
                        </div>
                    @endif
                </div>
            </div>

            {{-- Average Fuel Efficiency --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Average Fuel Efficiency (miles/gallon)</label>
                    <input type="number" step="0.01" name="avg_fuel_efficiency" class="form-control sidebar-wrapper {{ $errors->has('avg_fuel_efficiency') ? 'is-invalid' : '' }}"
                           placeholder="Enter Average Fuel Efficiency mile/gallon" value="{{ old('avg_fuel_efficiency', $user->avg_fuel_efficiency ?? '') }}">
                    @error('avg_fuel_efficiency')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Driver Cost Per Mile --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Driver Cost Per Mile ($)</label>
                    <input type="number" step="0.0001" name="driver_cost_per_mile" class="form-control sidebar-wrapper{{ $errors->has('driver_cost_per_mile') ? 'is-invalid' : '' }}"
                           placeholder="e.g. 0.52" value="{{ old('driver_cost_per_mile', $user->driver_cost_per_mile ?? '') }}">
                    <small class="text-muted fs-11">What you pay the driver per mile (overridable per driver)</small>
                    @error('driver_cost_per_mile')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Operating Cost Fields --}}
            <div class="row mb-2">
                <div class="col-md-6 mx-auto">
                    <div class="card sidebar-wrapper border-0" style="border-left: 3px solid var(--hover-color) !important;">
                        <div class="card-body py-2 px-3">
                            <div class="fs-13 fw-bold mb-2" style="color:var(--hover-color);">
                                <i class="fas fa-calculator me-1"></i> Operating Costs Per Mile
                            </div>
                            <p class="fs-11 text-muted mb-3">These are your carrier's running costs beyond fuel and driver pay. Used in cost estimation.</p>

                            <div class="mb-3">
                                <label class="form-label fs-13">Insurance Per Mile ($)</label>
                                <input type="number" step="0.0001" name="insurance_per_mile"
                                    class="form-control sidebar-wrapper"
                                    placeholder="e.g. 0.10"
                                    value="{{ old('insurance_per_mile', $user->insurance_per_mile ?? '0.10') }}">
                                <small class="text-muted fs-11">Truck insurance cost spread over miles (~$0.08–$0.15)</small>
                                @error('insurance_per_mile') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-13">Maintenance Per Mile ($)</label>
                                <input type="number" step="0.0001" name="maintenance_per_mile"
                                    class="form-control sidebar-wrapper"
                                    placeholder="e.g. 0.15"
                                    value="{{ old('maintenance_per_mile', $user->maintenance_per_mile ?? '0.15') }}">
                                <small class="text-muted fs-11">Tires, repairs, service spread over miles (~$0.12–$0.20)</small>
                                @error('maintenance_per_mile') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fs-13">Overhead Per Mile ($)</label>
                                <input type="number" step="0.0001" name="overhead_per_mile"
                                    class="form-control sidebar-wrapper"
                                    placeholder="e.g. 0.10"
                                    value="{{ old('overhead_per_mile', $user->overhead_per_mile ?? '0.10') }}">
                                <small class="text-muted fs-11">Dispatch, admin, software, phone (~$0.08–$0.15)</small>
                                @error('overhead_per_mile') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="mb-2">
                                <label class="form-label fs-13">IFTA Tax Per Mile ($)</label>
                                <input type="number" step="0.0001" name="ifta_per_mile"
                                    class="form-control sidebar-wrapper"
                                    placeholder="e.g. 0.05"
                                    value="{{ old('ifta_per_mile', $user->ifta_per_mile ?? '0.05') }}">
                                <small class="text-muted fs-11">International Fuel Tax Agreement — mandatory for interstate carriers (~$0.04–$0.06)</small>
                                @error('ifta_per_mile') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


@include('vehicle_types.index-js')
@endsection
