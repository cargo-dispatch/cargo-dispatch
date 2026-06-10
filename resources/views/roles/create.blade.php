@extends('layouts.app')

@section('title')
    {{ $name ?? 'Role Form' }}
@endsection

@section('content')
<form id="roleForm" action="{{ isset($results) ? route('roles.update', $results->id) : route('roles.store') }}" method="POST">
    @csrf
    @if(isset($results))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($results) ? 'Update Role' : 'Add Role' }}
        </button>
        <a href="{{ route('roles.index') }}" class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card  shadow-sm " >
        <div class="card-header bg-gold">
            <div class="d-flex bg-gold justify-content-center">
                <h5 class="mb-0 fs-13">{{ isset($results) ? 'Edit Role' : 'Add Role' }}</h5>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Role Name</label>
                    <input type="text" name="name" class="form-control sidebar-wrapper {{ $errors->has('name') ? 'is-invalid' : '' }}" 
                           placeholder="Enter Role Name" value="{{ $results->name ?? old('name') }}">
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</form>
@endsection