@extends('layouts.app')

@section('content')
<div class="container">
    <div>
        <div class="d-flex justify-content-between align-items-center">
            <h4>Assign Permissions to {{ $role->name }}</h4>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('permissions.save', $role->id) }}">
            @csrf
            <input type="hidden" name="role_id" value="{{ $role->id }}">

            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>View</th>
                            <th>Create</th>
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($modules as $module)
                          @php
                            // fetch the module_role row for this module, or null if it doesn't exist
                            $perm = $permissions->get($module->id);
                          @endphp
                      
                          <tr>
                            <td>{{ ucfirst($module->name) }}</td>
                      
                            @foreach(['view','create','edit','delete'] as $action)
                              <td>
                                <div class="form-check">
                                  <input 
                                     class="form-check-input dark-checkbox"
                                     type="checkbox"
                                     name="permissions[{{ $module->id }}][]"
                                     value="{{ $action }}"
                                     {{-- If we have a row and its column is 1, check it --}}
                                     @if($perm && $perm->{$action} == 1) checked @endif
                                  >
                                </div>
                              </td>
                            @endforeach
                          </tr>
                        @endforeach
                      </tbody>
                      
                </table>
            </div>

            <button type="submit" class="btn btn-primary mt-3">Save Permissions</button>
        </form>
    </div>
</div>
@endsection
