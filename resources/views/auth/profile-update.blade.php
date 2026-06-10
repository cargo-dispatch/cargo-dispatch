<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.1/css/all.min.css"
    integrity="sha256-2XFplPlrFClt0bIdPgpz8H7ojnk10H69xRqd9+uTShA=" crossorigin="anonymous" />
<link href="{{ asset('assets/css/profile.css') }}" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-12">
                <!-- Page title -->
                <div class="my-0 my-md-5">

                    <h3 class="fs-18">My Profile</h3>
                    <hr>
                </div>
                
                <!-- Display Success Message -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                <!-- Display Error Message -->
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Form START -->
                <form class="file-upload" id="userForm" action="{{ route('profile.update') }}" enctype="multipart/form-data"
                    method="POST">
                    @csrf
                    @method('PATCH')
                    
                    <div class="row mb-5 gx-5">
                        <!-- Contact detail -->
                        <div class="col-xxl-8 p-0 p-xxl-3 mb-5 mb-xxl-0">

                            <div class="bg-secondary-soft px-4 py-5 rounded">
                                <div class="row g-3">
                                  <h4 class="mb-0 fs-15 mb-md-4 mt-0">Contact detail</h4>
                                    
                                    <!-- First Name -->
                                    <div class="col-md-6">
                                        <label class="form-label fs-13">First Name <span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control fs-13 sidebar-wrapper @error('first_name') is-invalid @enderror" 
                                               name="first_name" placeholder="" aria-label="First name" 
                                               value="{{ old('first_name', $user->first_name) }}">
                                        @error('first_name')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    
                                    <!-- Last name -->
                                    <div class="col-md-6">
                                        <label class="form-label fs-13">Last Name <span class="text-danger"> *</span></label>
                                        <input type="text" class="form-control fs-13 sidebar-wrapper @error('last_name') is-invalid @enderror" 
                                               placeholder="" name="last_name" aria-label="Last name" 
                                               value="{{ old('last_name', $user->last_name) }}">
                                        @error('last_name')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    
                                    <!-- Phone number -->
                                    <div class="col-md-6">
                                        <label class="form-label fs-13">Phone number</label>
                                        <input type="tel" class="form-control fs-13 sidebar-wrapper @error('phoneNumber') is-invalid @enderror" 
                                               name="phoneNumber" placeholder="(123) 456-7890" aria-label="Phone number"
                                               value="{{ old('phoneNumber', $user->phoneNumber) }}" 
                                               pattern="[0-9\(\)-]+"
                                               title="Only numbers, parentheses (), and dashes - are allowed" >
                                        @error('phoneNumber')
                                            <div class="invalid-feedback d-block">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div> <!-- Row END -->
                            </div>

                            <!-- Personal Information -->
                            <div class="col-xxl-12 mb-5 mb-xxl-0 mt-5">
                                <div class="bg-secondary-soft px-4 py-5 rounded">
                                    <div class="row g-3">
                                        <h4 class="mb-4 fs-15 mt-0">Personal Information</h4>

                                        <!-- Address 1 -->
                                        <div class="col-md-6">
                                            <label class="form-label fs-13">Address 1<span class="text-danger"> *</span></label>
                                            <input type="text" class="form-control fs-13 sidebar-wrapper @error('address1') is-invalid @enderror" 
                                                   name="address1" value="{{ old('address1', $user->address1 ?? '') }}">
                                            @error('address1')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <!-- Address 2 -->
                                        <div class="col-md-6">
                                            <label class="form-label fs-13">Address 2</label>
                                            <input type="text" class="form-control fs-13 sidebar-wrapper @error('address2') is-invalid @enderror" 
                                                   name="address2" value="{{ old('address2', $user->address2 ?? '') }}">
                                            @error('address2')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <!-- City -->
                                        <div class="col-md-4">
                                            <label class="form-label fs-13">City <span class="text-danger"> *</span></label>
                                            <input type="text" class="form-control fs-13 sidebar-wrapper @error('city') is-invalid @enderror" 
                                                   name="city" value="{{ old('city', $user->city ?? '') }}">
                                            @error('city')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <!-- State -->
                                        <div class="col-md-4">
                                            <label class="form-label fs-13">State<span class="text-danger"> *</span></label>
                                            <input type="text" class="form-control fs-13 sidebar-wrapper @error('state') is-invalid @enderror" 
                                                   name="state" value="{{ old('state', $user->state ?? '') }}">
                                            @error('state')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <!-- ZIP -->
                                        <div class="col-md-4">
                                            <label class="form-label fs-13">ZIP Code<span class="text-danger"> *</span></label>
                                            <input type="text" class="form-control fs-13 sidebar-wrapper @error('zip') is-invalid @enderror" 
                                                   name="zip" value="{{ old('zip', $user->zip ?? '') }}">
                                            @error('zip')
                                                <div class="invalid-feedback d-block">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upload profile -->
                        <div class="col-xxl-4">
                            <div class="bg-secondary-soft p-0 p-md-5 rounded">

                                <div class="row g-3">
                                    <h4 class="mb-4 mt-0">Upload your profile photo</h4>
                                    <div class="text-center">
                                        <!-- Image upload -->
                                        <div class="square sidebar-wrapper position-relative display-2 mb-3">
                                            @if(old('profile_image') || $user->profile_image)
                                                <img id="uploadedPreview" 
                                                     src="{{ old('profile_image') ? asset('storage/' . old('profile_image')) : ($user->profile_image ? asset('storage/' . $user->profile_image) : '') }}" 
                                                     alt="Profile Photo"
                                                     class="img-user-profile">
                                            @else
                                                <i class="fas fa-fw fa-user position-absolute top-50 start-50 translate-middle text-secondary"
                                                    id="uploadedImage"></i>
                                            @endif
                                        </div>

                                        <!-- Button -->
                                        <input type="file" id="customFile" name="profile_image" hidden
                                            onchange="previewImage(event)" accept="image/*">

                                        <div class="d-flex gap-2 justify-content-center align-items-center">
                                            <label for="customFile" class="btn"
                                                class="alert alert-dismissible fade show alert-success-custom">
                                                Upload
                                            </label>

                                            <button type="button" class="btn" id="removeBtn" onclick="removeImage()"
                                                style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; transition: 0.3s; margin-bottom: auto">
                                                Remove
                                            </button>
                                        </div>

                                        <!-- Profile image error -->
                                        @error('profile_image')
                                            <div class="text-danger mt-2">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        <p class="text-muted mt-3 mb-0 note-style"><span class="me-1">Note:</span>Minimum size 300px x 300px</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden field to store old profile image path on validation error -->
                            <input type="hidden" name="old_profile_image" value="{{ $user->profile_image ?? '' }}">
                            
                            <div class="gap-3 mt-3 justify-content-md-end text-center">
                                <button type="submit" class="btn theme-btn btn-md">Update profile</button>
                            </div>
                        </div>
                    </div> <!-- Row END -->
                </form> <!-- Form END -->
            </div>
        </div>
    </div>

    <script>
        // Phone number validation - allow only numbers, parentheses, and dashes
        document.querySelector('input[name="phoneNumber"]').addEventListener('input', function (e) {
            this.value = this.value.replace(/[^0-9\(\)-]/g, '');
        });

        // Preview uploaded image
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!validTypes.includes(file.type)) {
                    alert('Please upload a valid image file (JPEG, PNG, GIF)');
                    event.target.value = '';
                    return;
                }
                
                // Validate file size (max 2MB)
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (file.size > maxSize) {
                    alert('Image size should be less than 2MB');
                    event.target.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function (e) {
                    const imgElement = document.getElementById("uploadedPreview");
                    const icon = document.getElementById("uploadedImage");
                    
                    // Create image element if it doesn't exist
                    if (!imgElement) {
                        const container = document.querySelector('.square.sidebar-wrapper');
                        const newImg = document.createElement('img');
                        newImg.id = 'uploadedPreview';
                        newImg.style.maxWidth = '250px';
                        newImg.style.borderRadius = '50%';
                        newImg.style.width = '-webkit-fill-available';
                        container.insertBefore(newImg, container.firstChild);
                    }
                    
                    // Update image source
                    document.getElementById("uploadedPreview").src = e.target.result;
                    document.getElementById("uploadedPreview").classList.remove('d-none');
                    if (icon) icon.classList.add('d-none');
                };
                reader.readAsDataURL(file);
            }
        }

        // Remove image
        function removeImage() {
            const fileInput = document.getElementById("customFile");
            const imgElement = document.getElementById("uploadedPreview");
            const icon = document.getElementById("uploadedImage");
            
            fileInput.value = "";
            
            if (imgElement) {
                imgElement.classList.add('d-none');
                imgElement.src = '';
            }
            
            if (icon) {
                icon.classList.remove('d-none');
            }
        }

        // Clear validation errors on input
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
                const errorElement = this.nextElementSibling;
                if (errorElement && errorElement.classList.contains('invalid-feedback')) {
                    errorElement.remove();
                }
            });
        });
    </script>
@endsection