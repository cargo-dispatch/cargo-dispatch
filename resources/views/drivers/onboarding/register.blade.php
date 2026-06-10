<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Your Driver Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
  .brand-header { background: #1a1a2e; color: #fff; padding: 18px 0; text-align: center; }
  .brand-header h4 { margin: 0; font-size: 20px; }
  .step-indicator { display: flex; justify-content: center; gap: 0; margin: 28px 0 0; }
  .step-item { display: flex; flex-direction: column; align-items: center; position: relative; flex: 1; max-width: 160px; }
  .step-circle { width: 36px; height: 36px; border-radius: 50%; background: #dee2e6; color: #888; font-weight: bold; font-size: 14px; display: flex; align-items: center; justify-content: center; z-index: 1; }
  .step-item.active .step-circle { background: #4e73df; color: #fff; }
  .step-item.done .step-circle { background: #1cc88a; color: #fff; }
  .step-label { font-size: 11px; margin-top: 6px; color: #888; text-align: center; }
  .step-item.active .step-label { color: #4e73df; font-weight: 600; }
  .step-line { position: absolute; top: 18px; left: 50%; width: 100%; height: 2px; background: #dee2e6; z-index: 0; }
  .step-item.done .step-line { background: #1cc88a; }
  .step-item:last-child .step-line { display: none; }
  .card { border: none; border-radius: 12px; box-shadow: 0 2px 16px rgba(0,0,0,.07); }
  .section-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #4e73df; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; margin-bottom: 20px; }
  .upload-box { border: 2px dashed #dee2e6; border-radius: 8px; padding: 24px; text-align: center; cursor: pointer; transition: border-color .2s; }
  .upload-box:hover { border-color: #4e73df; }
  .upload-box.has-file { border-color: #1cc88a; background: #f0fff8; }
  .upload-box i { font-size: 28px; color: #adb5bd; margin-bottom: 8px; }
  .upload-box p { margin: 0; font-size: 13px; color: #868e96; }
  .form-step { display: none; }
  .form-step.active { display: block; }
  .req-badge { font-size: 10px; background: #e74a3b; color: #fff; padding: 2px 6px; border-radius: 3px; vertical-align: middle; }
  .opt-badge { font-size: 10px; background: #858796; color: #fff; padding: 2px 6px; border-radius: 3px; vertical-align: middle; }
</style>
</head>
<body>

<div class="brand-header">
  <h4><i class="fa fa-truck me-2"></i>{{ config('app.name') }} — Driver Registration</h4>
</div>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <!-- Step indicator -->
      <div class="step-indicator mb-4">
        <div class="step-item active" id="si-1">
          <div class="step-line"></div>
          <div class="step-circle">1</div>
          <div class="step-label">Personal Info</div>
        </div>
        <div class="step-item" id="si-2">
          <div class="step-line"></div>
          <div class="step-circle">2</div>
          <div class="step-label">CDL & Compliance</div>
        </div>
        <div class="step-item" id="si-3">
          <div class="step-line"></div>
          <div class="step-circle">3</div>
          <div class="step-label">Documents</div>
        </div>
        <div class="step-item" id="si-4">
          <div class="step-circle">4</div>
          <div class="step-label">Review & Submit</div>
        </div>
      </div>

      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
      @endif

      <form method="POST" action="{{ route('driver.register.submit', $invitation->token) }}" enctype="multipart/form-data" id="onboardingForm">
        @csrf

        <!-- STEP 1 — Personal Info -->
        <div class="form-step active" id="step-1">
          <div class="card p-4 mb-3">
            <p class="section-title">Personal Information</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">First Name *</label>
                <input type="text" name="firstname" class="form-control" value="{{ old('firstname', $invitation->firstname) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Last Name *</label>
                <input type="text" name="lastname" class="form-control" value="{{ old('lastname', $invitation->lastname) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control bg-light" value="{{ $invitation->email }}" disabled>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Phone *</label>
                <input type="text" name="phoneno" class="form-control" value="{{ old('phoneno', $invitation->phoneno) }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Emergency Contact</label>
                <input type="text" name="emergencycontactno" class="form-control" value="{{ old('emergencycontactno') }}">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Date of Birth *</label>
                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">SSN Last 4 Digits *</label>
                <input type="text" name="ssn_last4" class="form-control" maxlength="4" placeholder="XXXX" value="{{ old('ssn_last4') }}" required>
              </div>
              <div class="col-md-8">
                <label class="form-label fw-semibold">Street Address *</label>
                <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
              </div>
              <div class="col-md-5">
                <label class="form-label fw-semibold">City *</label>
                <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">State *</label>
                <input type="text" name="state" class="form-control" maxlength="2" placeholder="TX" value="{{ old('state') }}" required>
              </div>
              <div class="col-md-4">
                <label class="form-label fw-semibold">ZIP *</label>
                <input type="text" name="zip" class="form-control" value="{{ old('zip') }}" required>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-end">
            <button type="button" class="btn btn-primary px-5" onclick="nextStep(2)">Next <i class="fa fa-arrow-right ms-1"></i></button>
          </div>
        </div>

        <!-- STEP 2 — CDL & Compliance -->
        <div class="form-step" id="step-2">
          <div class="card p-4 mb-3">
            <p class="section-title">CDL Information</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">CDL Number *</label>
                <input type="text" name="cdl_number" class="form-control" value="{{ old('cdl_number') }}" required>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">CDL State *</label>
                <input type="text" name="cdl_state" class="form-control" maxlength="2" placeholder="TX" value="{{ old('cdl_state') }}" required>
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">CDL Class *</label>
                <select name="cdl_class" class="form-select" required>
                  <option value="">Select</option>
                  <option value="A" @selected(old('cdl_class')=='A')>Class A</option>
                  <option value="B" @selected(old('cdl_class')=='B')>Class B</option>
                  <option value="C" @selected(old('cdl_class')=='C')>Class C</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">CDL Expiry Date *</label>
                <input type="date" name="cdl_expiry_date" class="form-control" value="{{ old('cdl_expiry_date') }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Endorsements</label>
                <div class="d-flex flex-wrap gap-2 mt-1">
                  @foreach(['H' => 'Hazmat (H)', 'N' => 'Tank (N)', 'T' => 'Doubles/Triples (T)', 'X' => 'H+N (X)', 'P' => 'Passenger (P)', 'S' => 'School Bus (S)'] as $val => $label)
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="cdl_endorsements[]" value="{{ $val }}" id="end_{{ $val }}">
                      <label class="form-check-label small" for="end_{{ $val }}">{{ $label }}</label>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>
          <div class="card p-4 mb-3">
            <p class="section-title">Medical & Drug Test</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Medical Card Expiry *</label>
                <input type="date" name="medical_card_expiry" class="form-control" value="{{ old('medical_card_expiry') }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Drug Test Date *</label>
                <input type="date" name="drug_test_date" class="form-control" value="{{ old('drug_test_date') }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Drug Test Result *</label>
                <select name="drug_test_status" class="form-select" required>
                  <option value="">Select</option>
                  <option value="passed">Passed</option>
                  <option value="pending">Pending</option>
                  <option value="failed">Failed</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold">Years of Experience *</label>
                <input type="number" name="years_experience" class="form-control" min="0" max="50" value="{{ old('years_experience') }}" required>
              </div>
            </div>
          </div>
          <div class="card p-4 mb-3">
            <p class="section-title">Preferred Equipment</p>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Preferred Truck Type</label>
                <select name="preferred_truck_type_id" class="form-select">
                  <option value="">Any</option>
                  @foreach($vehicleTypes as $vt)
                    <option value="{{ $vt->id }}" @selected(old('preferred_truck_type_id') == $vt->id)>{{ $vt->vehicle_type }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>
          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="prevStep(1)"><i class="fa fa-arrow-left me-1"></i> Back</button>
            <button type="button" class="btn btn-primary px-5" onclick="nextStep(3)">Next <i class="fa fa-arrow-right ms-1"></i></button>
          </div>
        </div>

        <!-- STEP 3 — Documents -->
        <div class="form-step" id="step-3">
          <div class="card p-4 mb-3">
            <p class="section-title">Upload Documents</p>
            <div class="row g-4">
              @foreach([
                ['field' => 'doc_cdl_front',    'label' => 'CDL — Front',          'required' => true,  'accept' => 'image/*,.pdf'],
                ['field' => 'doc_cdl_back',     'label' => 'CDL — Back',           'required' => false, 'accept' => 'image/*,.pdf'],
                ['field' => 'doc_medical_card', 'label' => 'Medical Card (DOT)',   'required' => true,  'accept' => 'image/*,.pdf'],
                ['field' => 'doc_drug_test',    'label' => 'Drug Test Result',     'required' => true,  'accept' => 'image/*,.pdf'],
                ['field' => 'doc_profile_photo','label' => 'Profile Photo',        'required' => false, 'accept' => 'image/*'],
              ] as $doc)
                <div class="col-md-6">
                  <label class="form-label fw-semibold">
                    {{ $doc['label'] }}
                    @if($doc['required']) <span class="req-badge">Required</span>
                    @else <span class="opt-badge">Optional</span>
                    @endif
                  </label>
                  <div class="upload-box" onclick="document.getElementById('{{ $doc['field'] }}').click()" id="box_{{ $doc['field'] }}">
                    <i class="fa fa-cloud-upload-alt d-block"></i>
                    <p id="label_{{ $doc['field'] }}">Click to upload (JPG, PNG, PDF — max 10MB)</p>
                  </div>
                  {{-- No `required` on hidden file inputs — browser skips hidden fields inconsistently.
                       JS validateDocs() handles required-check before submit instead. --}}
                  <input type="file" name="{{ $doc['field'] }}" id="{{ $doc['field'] }}"
                         accept="{{ $doc['accept'] }}" class="d-none"
                         data-required="{{ $doc['required'] ? 'true' : 'false' }}"
                         onchange="fileSelected('{{ $doc['field'] }}', this)">
                </div>
              @endforeach
            </div>
          </div>
          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="prevStep(2)"><i class="fa fa-arrow-left me-1"></i> Back</button>
            <button type="button" class="btn btn-primary px-5" onclick="nextStep(4)">Next <i class="fa fa-arrow-right ms-1"></i></button>
          </div>
        </div>

        <!-- STEP 4 — Review & Submit -->
        <div class="form-step" id="step-4">
          <div class="card p-4 mb-3">
            <p class="section-title">Review & Submit</p>
            <div class="alert alert-info">
              <i class="fa fa-info-circle me-2"></i>
              Please review the information above. Once submitted, an admin will review your application and you will be notified by email within 1–2 business days.
            </div>
            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" id="confirm_accurate" required>
              <label class="form-check-label" for="confirm_accurate">
                I confirm that all information and documents I have provided are accurate and authentic.
              </label>
            </div>
          </div>
          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary px-4" onclick="prevStep(3)"><i class="fa fa-arrow-left me-1"></i> Back</button>
            <button type="submit" class="btn btn-success px-5 fw-bold">
              <i class="fa fa-paper-plane me-2"></i> Submit Application
            </button>
          </div>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
let currentStep = 1;
function nextStep(n) { goToStep(n); }
function prevStep(n) { goToStep(n); }

function goToStep(n) {
  document.getElementById('step-' + currentStep).classList.remove('active');
  document.getElementById('si-' + currentStep).classList.remove('active');
  document.getElementById('si-' + currentStep).classList.add('done');
  currentStep = n;
  document.getElementById('step-' + n).classList.add('active');
  document.getElementById('si-' + n).classList.add('active');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function fileSelected(field, input) {
  if (input.files.length > 0) {
    const box = document.getElementById('box_' + field);
    const lbl = document.getElementById('label_' + field);
    box.classList.add('has-file');
    lbl.textContent = '✅ ' + input.files[0].name;
    box.querySelector('i').style.color = '#1cc88a';
  }
}

// Validate required docs before submit
function validateDocs() {
  const missing = [];
  document.querySelectorAll('input[type="file"][data-required="true"]').forEach(function(input) {
    if (!input.files || input.files.length === 0) {
      const box = document.getElementById('box_' + input.id);
      box.style.borderColor = '#e74a3b';
      missing.push(input.id.replace('doc_', '').replace(/_/g, ' '));
    }
  });
  if (missing.length > 0) {
    alert('Please upload required documents:\n• ' + missing.join('\n• ') + '\n\nGo back to Step 3.');
    return false;
  }
  return true;
}

// Hook form submit
document.getElementById('onboardingForm').addEventListener('submit', function(e) {
  if (!validateDocs()) {
    e.preventDefault();
    return;
  }
  const checkbox = document.getElementById('confirm_accurate');
  if (!checkbox.checked) {
    e.preventDefault();
    checkbox.focus();
    checkbox.closest('.form-check').style.outline = '2px solid #e74a3b';
    alert('Please confirm that your information is accurate before submitting.');
    return;
  }
  // Disable button to prevent double-submit
  const btn = this.querySelector('button[type="submit"]');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Submitting…';
});

// If PHP validation failed and page reloaded with errors — jump to step 1 and show them
@if($errors->any())
  window.addEventListener('DOMContentLoaded', function() {
    goToStep(1);
    const errBox = document.querySelector('.alert-danger');
    if (errBox) errBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
@endif
</script>
</body>
</html>
