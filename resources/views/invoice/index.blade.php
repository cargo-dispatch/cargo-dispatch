@extends('layouts.app')

<style>
    .invoice-section {
        position: relative;
        border-bottom: 2px solid #ccc;
    }

    .invoice-col {
        position: relative;
        padding-right: 20px;
    }

    /* Add dotted separator line to all but last column */
    .invoice-col:not(:last-child)::after {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        width: 1px;
        height: 100%;
        border-right: 2px dotted grey;
    }
</style>

@section('content')
<div class="container my-5  shadow-lg" style="max-width: 900px; background-color: #fff; ">
    <!-- Header Section -->
    <div class="row  pb-3 text-white gx-0 pt-4 pb-4"
        style="background-color: #6E6E6E;  margin-left: -1.5rem; margin-right: -1.5rem;height: 250px;">
        <div class="col-md-4 ps-5 text-start text-md-start">
            <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" class="img-fluid" style="max-height: 80px;">
            <h5 class="mt-2 fw-bold">Cargo Disptach</h5>
        </div>
        <div class="col-md-8 text-end">
            <h4 class="fw-bold mb-1">Invoice</h4>
            <small>Tax Invoice</small><br> <br> 
            <small>email@truckinglogistics.co.au</small><br>
            <small>www.truckinglogistics.co.au</small>
        </div>
    </div>

    <!-- Invoice Info -->
  <div class="container-fluid gx-0 p-0 m-0 text-white border-bottom " style="background-color: rgb(74, 87, 95);">
    <div class="row  text-white gx-0 "
        style="background-color:rgb(74, 87, 95);  margin-left: -1.5rem; margin-right: -1.5rem;">
        <div class="col-md-4 d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="fw-bold mb-0">INVOICE NO:</h6>
            <p class="mb-0">2022036</p>
        </div>

        <div class="col-md-4 d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="fw-bold mb-0">ISSUE DATE:</h6>
            <p class="mb-0">28/09/2023</p>
        </div>

        <div class="col-md-4 d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="fw-bold mb-0">DUE DATE:</h6>
            <p class="mb-0">12/10/2023</p>
        </div>
    </div>
</div>



    <!-- From and To -->
 <div class="row mt-4 invoice-section pb-3">
    <div class="col-md-4 invoice-col">
        <h6 class="fw-bold text-black">FROM</h6>
        <p class="mb-0">Trucking Logistics</p>
        <p class="mb-0">5 Martin Pl</p>
        <p class="mb-0">Sydney NSW 2000</p>
        <p>Australia</p>
    </div>

    <div class="col-md-4 invoice-col">
        <h6 class="fw-bold text-black">TO</h6>
        <p class="mb-0">Your Client</p>
        <p class="mb-0">100 Harris St</p>
        <p class="mb-0">Sydney NSW 2009</p>
        <p>Australia</p>
    </div>

    <div class="col-md-4 invoice-col">
        <h6 class="fw-bold text-black">Total Due</h6>
        <p class="fw-bold text-black fs-5">$5,760.00</p>
      
       
    </div>
</div>

    <!-- Description Table -->
    <div class="table-responsive mt-4">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>DESCRIPTION</th>
                    <th>QUANTITY</th>
                    <th>UNIT PRICE ($)</th>
                    <th>AMOUNT ($)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Hypermarket Chain Distribution</td>
                    <td>1</td>
                    <td>3,200.00</td>
                    <td>3,200.00</td>
                </tr>
                <tr>
                    <td>Supply Chain Management</td>
                    <td>1</td>
                    <td>1,600.00</td>
                    <td>1,600.00</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="row mt-4 " style="height:400px">
        <div class="col-md-6"></div>
        <div class="col-md-6">
            <table class="table border-0">
                <tr>
                    <td class="fw-bold border-0">Subtotal:</td>
                    <td class="text-end border-0">$4,800.00</td>
                </tr>
                <tr>
                    <td class="fw-bold border-0">GST 20% from $4,800.00:</td>
                    <td class="text-end border-0">$960.00</td>
                </tr>
                <tr>
                    <td class="fw-bold fs-5 border-0">Total (AUD):</td>
                    <td class="text-end fw-bold fs-5 border-0">$5,760.00</td>
                </tr>
            </table>
              <hr class="text-black fw-5" style="border:1px dotted black">
         
        </div>
         
    </div>
</div>
@endsection