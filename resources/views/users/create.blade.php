@extends('layouts.app')

@section('title')
    {{ $name ?? 'User Form' }}
@endsection

@section('content')



<form id="roleForm" action="{{ isset($results) ? route('users.update', $results->id) : route('users.store') }}" method="POST">
    @csrf
    @if(isset($results))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($results) ? 'Update User' : 'Add User' }}
        </button>
        <a href="{{ route('users.index') }}" class="btn mbl-btn btn-outline-secondary theme-btn rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card sidebar-wrapper shadow-sm me-2">
        <div class="card-header card-header-yellow">
            <div class="d-flex justify-content-center">
                <h5 class="mb-0 fs-15">{{ isset($results) ? 'Edit User' : 'Add User' }}</h5>
            </div>
        </div>

        <div class="card-body">
            @if(isset($results))
                <input type="hidden" name="id" value="{{ $results->id }}">
            @endif

            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label fs-13">First Name</label>
                    <input type="text" name="first_name" class="text-dark sidebar-wrapper form-control {{ $errors->has('first_name') ? 'is-invalid' : '' }}" placeholder="Enter First Name" value="{{ $results->first_name ?? old('first_name') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fs-13">Last Name</label>
                    <input type="text" name="last_name" class="text-dark sidebar-wrapper form-control {{ $errors->has('last_name') ? 'is-invalid' : '' }}" placeholder="Enter Last Name" value="{{ $results->last_name ?? old('last_name') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-13">Phone Number</label>
                    <input type="text" class="text-dark sidebar-wrapper form-control" placeholder="Enter phone number" name="phoneNumber" id="phone" value="{{ $results->phoneNumber ?? old('phoneNumber') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="fs-13 form-label">Role</label>
                    <select name="role_id" class="text-dark fs-13 sidebar-wrapper form-control">
    <option value="">Select Role</option>
    @foreach ($roles as $role)
        <option value="{{ $role->id }}"
            @if(isset($results))
                {{ $results->role_id == $role->id ? 'selected' : '' }}
            @else
                {{ old('role_id') == $role->id ? 'selected' : '' }}
            @endif
        >
            {{ ucwords(str_replace('_', ' ', $role->name)) }}
        </option>
    @endforeach
</select>

                </div>

                <div class="col-md-4">
                    <label class="fs-13 form-label">Status</label>
                    <select name="status" class="text-dark fs-13 sidebar-wrapper form-control {{ $errors->has('status') ? 'is-invalid' : '' }}">
                        <option value="">Select Status</option>
                        <option value="active" {{ (isset($results) && $results->status == 'active') ? 'selected' : (old('status') == 'active' ? 'selected' : '') }}>active</option>
                        <option value="in-active" {{ (isset($results) && $results->status == 'in-active') ? 'selected' : (old('status') == 'in-active' ? 'selected' : '') }}>in-active</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fs-13 mt-3">Street Address 1</label>
                    <input type="text" name="address1" class="text-dark sidebar-wrapper form-control" placeholder="123 Main St" value="{{ $results->address1 ?? old('address1') }}">

                    <label class="form-label fs-13 mt-3">City</label>
                    <input type="text" name="city" class="text-dark sidebar-wrapper form-control" placeholder="City" value="{{ $results->city ?? old('city') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label fs-13 mt-3">Street Address 2</label>
                    <input type="text" name="address2" class="text-dark sidebar-wrapper form-control" placeholder="Apt #123" value="{{ $results->address2 ?? old('address2') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-13 mt-3">State</label>
                    <input type="text" name="state" class="text-dark sidebar-wrapper form-control" placeholder="State" value="{{ $results->state ?? old('state') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label fs-13 mt-3">Zip</label>
                    <input type="text" name="zip" class="text-dark sidebar-wrapper form-control" placeholder="Zip" value="{{ $results->zip ?? old('zip') }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="fs-13 form-label">Email</label>
                    <input type="email" name="email" class="text-dark sidebar-wrapper form-control {{ $errors->has('email') ? 'is-invalid' : '' }}" placeholder="Enter Email" value="{{ $results->email ?? old('email') }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-13">Password</label>
                    <input type="password" name="password" id="password" class="text-dark sidebar-wrapper form-control {{ $errors->has('password') ? 'is-invalid' : '' }}" placeholder="Enter Password (leave blank to keep current)">
                </div>

                <div class="col-md-4">
                    <label class="form-label fs-13">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="text-dark sidebar-wrapper form-control" placeholder="Confirm Password">
                    <span class="text-danger display-none" id="password-match-error">Passwords do not match.</span>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
Inputmask({"mask": "(999) 999-9999"}).mask("#phone");
//Inputmask({"mask": "99:99"}).mask("#time");
</script>
@include('users.index-js')
@endsection
