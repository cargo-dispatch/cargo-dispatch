@extends('layouts.app')

@section('title')
    {{ $name ?? 'Manage Driver Form' }}
@endsection

@section('content')
<form id="roleForm" action="{{ isset($user) ? route('managedriver.update', $user->id) : route('managedriver.store') }}" method="POST">
    @csrf
    @if(isset($user))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
        <button type="submit" class="btn mbl-btn theme-btn submit rounded-pill px-4 py-2 shadow-lg me-2">
            {{ isset($user) ? 'Update Driver' : 'Add Driver' }}
        </button>
        <a href="{{ route('managedriver.index') }}" class="btn mbl-btn btn-outline-secondary submit rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-gold">
            <div class="d-flex justify-content-center">
                <h5 class="mb-0 fs-15">{{ isset($user) ? 'Edit Driver' : 'Add Driver' }}</h5>
            </div>
        </div>

        <div class="card-body">

            {{-- First & Last Name --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">First Name</label>
                    <input type="text" name="firstname" class="form-control sidebar-wrapper{{ $errors->has('firstname') ? 'is-invalid' : '' }}"
                        placeholder="Enter First Name" value="{{ $user->firstname ?? '' }}">
                    @error('firstname')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Last Name</label>
                    <input type="text" name="lastname" class="form-control sidebar-wrapper{{ $errors->has('lastname') ? 'is-invalid' : '' }}"
                        placeholder="Enter Last Name" value="{{ $user->lastname ?? '' }}">
                    @error('lastname')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Phone & Emergency Contact --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Phone Number</label>
                    <input type="text" id="phone" name="phoneno" class="form-control sidebar-wrapper{{ $errors->has('phoneno') ? 'is-invalid' : '' }}"
                        placeholder="Enter Phone Number" value="{{ $user->phoneno ?? ''}}">
                    @error('phoneno')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Emergency Contact No</label>
                    <input type="text" id="emergency_phone" name="emergencycontactno" class="form-control sidebar-wrapper{{ $errors->has('emergencycontactno') ? 'is-invalid' : '' }}"
                        placeholder="Enter Emergency Contact No" value="{{ $user->emergencycontactno ?? '' }}">
                    @error('emergencycontactno')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Email & Driver Type --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Email</label>
                    <input type="email" name="email" class="form-control sidebar-wrapper{{ $errors->has('email') ? 'is-invalid' : '' }}"
                        placeholder="Enter Email" value="{{ $user->email ?? '' }}">
                    @error('email')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

               <div class="col-md-6 mx-auto">
    <label class="form-label fs-13">Driver Type</label>
    
    <div style="width: 100%; position: relative;">
        <select 
            name="drivertype" 
            id="driverTypeSelect"
            class="form-control sidebar-wrapper fs-13 {{ $errors->has('drivertype') ? 'is-invalid' : '' }}"
            style="width: 100%;"
        >
            <option value="">Select Driver Type</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" {{ ($user->drivertype ?? '') == $driver->id ? 'selected' : '' }}>
                    {{ $driver->name }}
                </option>
            @endforeach
        </select>
    </div>
    
    @error('drivertype')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>
            </div>

            {{-- License Type & License Number --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">License Type</label>
                    <input type="text" name="licensetype" class="form-control sidebar-wrapper{{ $errors->has('licensetype') ? 'is-invalid' : '' }}"
                        placeholder="Enter License Type" value="{{ $user->licensetype ?? '' }}">
                    @error('licensetype')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">License Number</label>
                    <input type="text" name="licenseno" class="form-control sidebar-wrapper{{ $errors->has('licenseno') ? 'is-invalid' : '' }}"
                        placeholder="Enter License Number" value="{{ $user->licenseno ?? ''}}">
                    @error('licenseno')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Pay Settings --}}
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card sidebar-wrapper border-0" style="border-left: 3px solid var(--hover-color) !important;">
                        <div class="card-body py-2 px-3">
                            <div class="fs-13 fw-bold mb-2" style="color:var(--hover-color);">
                                <i class="fas fa-dollar-sign me-1"></i> Driver Pay Settings
                            </div>
                            <p class="fs-11 text-muted mb-3">
                                Leave <strong>Personal Rate</strong> empty to use the vehicle type default rate.
                                Only fill this if the driver has a negotiated contract rate.
                            </p>
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label fs-13">Pay Type</label>
                                    <select name="pay_type" class="form-control fs-13 sidebar-wrapper">
                                        @php $pt = old('pay_type', $user->pay_type ?? 'per_mile'); @endphp
                                        <option value="per_mile"   {{ $pt == 'per_mile'   ? 'selected' : '' }}>Per Mile</option>
                                        <option value="per_load"   {{ $pt == 'per_load'   ? 'selected' : '' }}>Per Load (Flat)</option>
                                        <option value="percentage" {{ $pt == 'percentage' ? 'selected' : '' }}>Percentage of Load</option>
                                        <option value="hourly"     {{ $pt == 'hourly'     ? 'selected' : '' }}>Hourly</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-13">Personal Rate
                                        <small class="text-muted">(overrides vehicle type)</small>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text sidebar-wrapper fs-13">$</span>
                                        <input type="number" step="0.0001" min="0" name="pay_rate"
                                            class="form-control sidebar-wrapper fs-13"
                                            placeholder="e.g. 0.52 — leave empty for default"
                                            value="{{ old('pay_rate', $user->pay_rate ?? '') }}">
                                    </div>
                                    <small class="text-muted fs-11" id="payRateHint">per mile</small>
                                    @error('pay_rate') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fs-13">Incentive Per Mile ($)</label>
                                    <div class="input-group">
                                        <span class="input-group-text sidebar-wrapper fs-13">$</span>
                                        <input type="number" step="0.01" min="0" name="incentive"
                                               class="form-control sidebar-wrapper fs-13"
                                               placeholder="e.g. 0.05"
                                               value="{{ old('incentive', $user->incentive ?? '') }}">
                                    </div>
                                    <small class="text-muted fs-11">Bonus on top of base pay</small>
                                    @error('incentive') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Address --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label fs-13">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control sidebar-wrapper" value="{{ $user->date_of_birth ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-13">SSN Last 4 Digits</label>
                    <input type="text" name="ssn_last4" class="form-control sidebar-wrapper" maxlength="4" placeholder="XXXX" value="{{ $user->ssn_last4 ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fs-13">Years of Experience</label>
                    <input type="number" name="years_experience" class="form-control sidebar-wrapper" min="0" max="50" placeholder="0" value="{{ $user->years_experience ?? '' }}">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-5">
                    <label class="form-label fs-13">Street Address</label>
                    <input type="text" name="address" class="form-control sidebar-wrapper" placeholder="123 Main St" value="{{ $user->address ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fs-13">City</label>
                    <input type="text" name="city" class="form-control sidebar-wrapper" value="{{ $user->city ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-13">State</label>
                    <input type="text" name="state" class="form-control sidebar-wrapper" maxlength="2" placeholder="TX" value="{{ $user->state ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fs-13">ZIP</label>
                    <input type="text" name="zip" class="form-control sidebar-wrapper" value="{{ $user->zip ?? '' }}">
                </div>
            </div>

            {{-- CDL & Compliance Section --}}
            <div class="card sidebar-wrapper border-0 mb-3" style="border-left: 3px solid #4e73df !important;">
                <div class="card-body py-2 px-3">
                    <div class="fs-13 fw-bold mb-3" style="color:#4e73df;">
                        <i class="fas fa-id-card me-1"></i> CDL & Compliance
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fs-13">CDL Number</label>
                            <input type="text" name="cdl_number" class="form-control sidebar-wrapper fs-13" placeholder="CDL Number" value="{{ $user->cdl_number ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-13">CDL State</label>
                            <input type="text" name="cdl_state" class="form-control sidebar-wrapper fs-13" maxlength="2" placeholder="TX" value="{{ $user->cdl_state ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fs-13">CDL Class</label>
                            <select name="cdl_class" class="form-control sidebar-wrapper fs-13">
                                <option value="">Select</option>
                                @foreach(['A','B','C'] as $cls)
                                    <option value="{{ $cls }}" {{ ($user->cdl_class ?? '') == $cls ? 'selected' : '' }}>Class {{ $cls }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">CDL Expiry Date</label>
                            <input type="date" name="cdl_expiry_date" class="form-control sidebar-wrapper fs-13" value="{{ isset($user->cdl_expiry_date) ? $user->cdl_expiry_date->format('Y-m-d') : '' }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label fs-13">CDL Endorsements</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach(['H' => 'Hazmat (H)', 'N' => 'Tank (N)', 'T' => 'Doubles/Triples (T)', 'X' => 'H+N (X)', 'P' => 'Passenger (P)', 'S' => 'School Bus (S)'] as $val => $lbl)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="cdl_endorsements[]" value="{{ $val }}" id="end_{{ $val }}"
                                            {{ in_array($val, $user->cdl_endorsements ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label fs-13" for="end_{{ $val }}">{{ $lbl }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fs-13">Medical Card Expiry</label>
                            <input type="date" name="medical_card_expiry" class="form-control sidebar-wrapper fs-13" value="{{ isset($user->medical_card_expiry) ? $user->medical_card_expiry->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">Drug Test Date</label>
                            <input type="date" name="drug_test_date" class="form-control sidebar-wrapper fs-13" value="{{ isset($user->drug_test_date) ? $user->drug_test_date->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fs-13">Drug Test Result</label>
                            <select name="drug_test_status" class="form-control sidebar-wrapper fs-13">
                                <option value="">Select</option>
                                @foreach(['passed' => 'Passed', 'pending' => 'Pending', 'failed' => 'Failed'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ ($user->drug_test_status ?? '') == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fs-13">Preferred Truck Type</label>
                            <select name="preferred_truck_type_id" class="form-control sidebar-wrapper fs-13">
                                <option value="">Any</option>
                                @foreach($vehicleTypes ?? [] as $vt)
                                    <option value="{{ $vt->id }}" {{ ($user->preferred_truck_type_id ?? '') == $vt->id ? 'selected' : '' }}>{{ $vt->vehicle_type }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Password --}}
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <label class="form-label fs-13">Password</label>
                    <input type="password" name="password" class="form-control sidebar-wrapper{{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="Enter Password">
                    @error('password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>

        </div>
    </div>
</form>

<script>
    Inputmask({"mask": "(999) 999-9999"}).mask("#emergency_phone");
</script>

@include('drivers.index-js')
@endsection
