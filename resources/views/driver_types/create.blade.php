@extends('layouts.app')

@section('title')
    {{ $name ?? 'Driver Form' }}
@endsection

@section('content')
<form id="roleForm" action="{{ isset($user) ? route('driver.update', $user->id) : route('driver.store') }}" method="POST">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn submit rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($user) ? 'Update Driver Type' : 'Add Driver Type' }}
        </button>
        <a href="{{ route('driver.index') }}" class="btn mbl-btn btn-outline-secondary submit rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-gold">
            <div class="d-flex justify-content-center">
                <h5 class="mb-0 fs-15">{{ isset($user) ? 'Edit Driver Type' : 'Add Driver Type' }}</h5>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Driver Type Name</label>
                    <input type="text" name="name" class="form-control sidebar-wrapper {{ $errors->has('name') ? 'is-invalid' : '' }}" 
                           placeholder="Enter Driver Type " value="{{ $user->name ?? old('name') }}">
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>
    </div>
</form>


@include('driver_types.index-js')
@endsection
