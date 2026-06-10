@extends('layouts.app')

@section('content')
@if(session('success'))
    <div class="alert alert-success" id="success-message">
        {{ session('success') }}
    </div>
    <script>
        setTimeout(function() {
            var successBox = document.getElementById('success-message');
            if(successBox) {
                successBox.style.display = 'none';
            }
        }, 4000);
    </script>
@endif

@if(session('error'))
    <div class="alert alert-danger" id="error-message">
        {{ session('error') }}
    </div>
    <script>
        setTimeout(function() {
            var errorBox = document.getElementById('error-message');
            if(errorBox) {
                errorBox.style.display = 'none';
            }
        }, 4000);
    </script>
@endif

<div class="card shadow mb-4">
    <div class="card-header sidebar-wrapper py-3 d-flex justify-content-between">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $name }}</li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="{{ route('roles.create') }}" class="btn theme-btn mbl-btn border border-dark d-flex align-items-center me-2">
                <i class="bi bi-plus-circle me-2"></i> Add Role
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="custom-table stripped sidebar-wrapper" width="100%" cellspacing="0">
                <thead>
                    <tr>
                     
                        <th>Role</th>
                       
                        <th>Action</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                    
                        <th>Role</th>
                      
                        <th>Action</th>
                    </tr>
                </tfoot>
                <tbody>
                    @foreach($roles as $role)
                    <tr data-id="{{ $role->id }}">
                    
                        <td>
                          <a href="javascript:void(0);" 
                             class="role-detail-link text-decoration-none show-enteries fw-semibold" 
                             data-role-id="{{ $role->id }}">
                             {{ $role->name }}
                          </a>
                        </td>
                       
                        <td>
                           
 <a href="javascript:void(0);" 
   class="role-detail-link text-decoration-none text-dark fw-semibold" 
   data-role-id="{{ $role->id }}">
   <i class="fa-solid fa-users-gear text-primary me-2"></i>
</a>

                            <a href="{{ route('roles.edit', $role->id) }}" class="text-primary me-2" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        
                            @include('components.delete-button', [
                                'route' => route('roles.destroy', $role->id),
                                'id' => $role->id,
                                'useAjax' => false
                            ])
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@include('modals.permission_modal')






@include('roles.index-js')
<script src="{{ asset('assets/js/sweetalert.js') }}"></script>
@endsection