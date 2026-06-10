@extends('layouts.app')

@section('title') {{$name}} @endsection


@section('content')


@if(session('success'))
<div class="alert alert-success" id="success-message">
    {{ session('success') }}
</div>
<script>
    setTimeout(function() {
        var successBox = document.getElementById('success-message');
        if (successBox) {
            successBox.style.display = 'none';
        }
    }, 4000);
</script>
@endif

<div class="card shadow mb-4">
    <div class="card-header sidebar-wrapper py-3 d-flex justify-content-between">

        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb ">
                    <li class="breadcrumb-item"><a href="{{route('dashboard')}}">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $name }}</li>
                </ol>
            </nav>

        </div>
        <div class="centered-add-user" id="formContainer">
            <a href="{{ route('modules.create') }}" class="btn theme-btn  border border-dark d-flex align-items-center me-2">
                <i class="bi bi-plus-circle me-2"></i> ADD {{$name}}
            </a>

        </div>


    </div>
    <div class="card-body">
        <div class="table-responsive">
           <table class="custom-table stripped sidebar-wrapper" width="100%" cellspacing="0">
                <thead>
                    <tr>
                    
                        <th>First Name</th>

                        <th>
                            Action
                        </th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                     
                        <th>First Name</th>

                        <th>
                            Action
                        </th>
                    </tr>
                </tfoot>
                <tbody>
                    @foreach($users as $user)
                    <tr data-id="{{ $user->id }}">
                      
                        <td>{{ $user->name }}</td>

                        <td>

                            <a href="{{ route('modules.edit', $user->id) }}" class="text-primary me-2" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <form action="{{ route('modules.destroy', $user->id) }}" method="POST" class="delete-form d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-link text-danger delete-button" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>



                        </td>
                    </tr>
                    @endforeach

                </tbody>
            </table>

        </div>
    </div>
</div>


@include('users.index-js')

<script>
    $(document).on('click', '.delete-button', function(e) {
        e.preventDefault();

        let form = $(this).closest('form');
        let action = form.attr('action');
        let data = form.serialize();

        Swal.fire({
            title: 'Are you sure?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: action,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Redirect to index
                                window.location.href = "{{ route('modules.index') }}";
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'Something went wrong.', 'error');
                    }
                });
            }
        });
    });
</script>


@endsection