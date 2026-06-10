@extends('layouts.app')

@section('title')
    {{ $name ?? 'maintenance_types' }}
@endsection

@section('content')
<form id="roleForm" action="{{ isset($user) ? route('maintenance_type.update', $user->id) : route('maintenance_type.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn submit rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($user) ? "Update {$name}" : "Add {$name}" }}
        </button>
        <a href="{{ route('maintenance_type.index') }}" class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header sidebar-wrapper bg-gold">
            <div class="d-flex justify-content-center">
                <h5 class="mb-0 fs-15">{{ isset($user) ? "Edit {$name}" : "Add {$name}" }}</h5>
            </div>
        </div>

        <div class="card-body">
          
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Maintenance  Type</label>
                    <input type="text" name="maintenance_types" class="form-control sidebar-wrapper{{ $errors->has('maintenance_types') ? 'is-invalid' : '' }}"
                           placeholder="Enter Maintenance  Type" value="{{ old('maintenance_types', $user->maintenance_types ?? '') }}">
                    @error('maintenance_types')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</form>

@include('maintenance_type.index-js')
@endsection