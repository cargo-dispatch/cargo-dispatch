@extends('layouts.app')

@section('title') {{ $name }} @endsection

@section('content')
<div class="container-fluid px-3 px-md-4 py-3">
    
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3 fs-15 text-center text-md-start">All Assignments Overview</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div id="all-assignments-table" class="table-responsive">
                <!-- AJAX-loaded table goes here -->
            </div>
        </div>
    </div>
    
</div>

@include('vehicleassigment.index-js')
@endsection
