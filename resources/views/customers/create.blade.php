@extends('layouts.app')

@section('title')
    {{ $name ?? 'Customer Form' }}
@endsection

@section('content')

<div>
<form id="roleForm" action="{{ isset($user) ? route('customers.update', $user->id) : route('customers.store') }}" method="POST">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($user) ? "Update {$name}" : "Add {$name}" }}
        </button>
        <a href="{{ route('customers.index') }}" class="btn mbl-btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header sidebar-wrapper bg-gold text-center">
            <h5 class="mb-0 fs-13">{{ isset($user) ? "Edit {$name}" : "Add {$name}" }}</h5>
        </div>

        <div class="card-body">
            <div class="row mb-3">
                 <div class="col-md-6">
                    <label class="form-label fs-13">Customer Ttile</label>
                    <input type="text" name="customer_title" class="form-control sidebar-wrapper" value="{{ old('customer_title', $user->customer_title ?? '') }}">
                    @error('customer_title') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-13">Contact First Name</label>
                    <input type="text" name="first_name" class=" fs-13 form-control sidebar-wrapper" value="{{ old('first_name', $user->first_name ?? '') }}">
                    @error('first_name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-13">Contact Last Name</label>
                    <input type="text" name="last_name" class="fs-13 form-control sidebar-wrapper" value="{{ old('last_name', $user->last_name ?? '') }}">
                    @error('last_name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
               
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fs-13">Email</label>
                    <input type="email" name="email" class="fs-13 form-control sidebar-wrapper" value="{{ old('email', $user->email ?? '') }}">
                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-13">Phone</label>
                    <input type="text" name="phone" id="phone" class="fs-13 form-control sidebar-wrapper" value="{{ old('phone', $user->phone ?? '') }}">
                    @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fs-13">Address1</label>
                    <textarea name="address1" class="fs-13 form-control sidebar-wrapper">{{ old('address1', $user->address1 ?? '') }}</textarea>
                    @error('address1') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label fs-13">Address2</label>
                    <textarea name="address2" class="fs-13 form-control sidebar-wrapper">{{ old('address2', $user->address2 ?? '') }}</textarea>
                    @error('address2') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
              <div class="row mb-3 ">
                <div class="col-md-6">
                    <label class="form-label fs-13">City</label>
                  <input name="city" class="fs-13 form-control sidebar-wrapper" value="{{ old('city', $user->city ?? '') }}">

                    @error('city') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-13">State</label>
                    <input name="state" class="fs-13 form-control sidebar-wrapper" value = " {{ old('state', $user->state ?? '') }}" >
                    @error('state') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                 <div class="col-md-3">
                    <label class="form-label fs-13">Zip</label>
                    <input name="zip" class="fs-13 form-control sidebar-wrapper" value ="{{ old('zip', $user->zip ?? '') }}">
                    @error('zip') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                
            </div>

         
            <div class="row mb-3">
                <!-- <div class="col-md-6">
                    <label class="form-label">User Name</label>
                    <input name="user_name" class="form-control" value ="{{ old('user_name', $user->user_name ?? '') }}">
                    @error('user_name') <span class="text-danger">{{ $message }}</span> @enderror
                </div> -->
                <div class="col-md-6">
                    <div class="alert alert-info py-2 px-3 fs-13 mb-0" style="border-radius:6px;">
                        <i class="bi bi-info-circle me-1"></i>
                        A temporary password will be auto-generated and emailed to the customer.
                    </div>
                </div>
                
            </div>
        
        </div>
    </div>
</form>

</div>
<script>
Inputmask({"mask": "(999) 999-9999"}).mask("#phone");
//Inputmask({"mask": "99:99"}).mask("#time");
</script>
@include('customers.index-js')
@endsection
