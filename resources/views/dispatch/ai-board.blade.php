@extends('layouts.app')
@section('title', 'AI Load Board')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ai-board.css') }}">
@endpush

@section('content')
<div class="container-fluid wrapper-color">

    {{-- Page header --}}
    <div class="row mb-3">
        <div class="col-12 text-md-end mt-2 mt-md-0">
            <button id="refreshLoadsBtn" class="btn btn-sm theme-btn">
                <i class="fas fa-rotate"></i> Refresh
            </button>
        </div>
    </div>

    {{-- Rate Intelligence --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card sidebar-wrapper shadow">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background:var(--sidebar-bg);border-bottom:1px solid var(--chart-grid);">
                    <h6 class="mb-0" style="color:var(--text-color);font-family:'Jost',sans-serif;">
                        <i class="fas fa-chart-line me-2" style="color:var(--hover-color);"></i>
                        Rate Intelligence
                        <span style="color:var(--hover-color);font-size:11px;margin-left:6px;">AI · Groq</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <small id="riStatus" style="color:var(--search-placeholder);font-family:'Jost',sans-serif;"></small>
                        <button id="riRefreshBtn" class="btn btn-sm btn-outline-secondary" style="font-size:11px;font-family:'Jost',sans-serif;">
                            <i class="fas fa-rotate"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div id="riContent">
                        <div class="text-center p-3 text-muted" style="font-family:'Jost',sans-serif;font-size:13px;">
                            <span class="loading-spinner"></span> Analysing lane rates...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FreightFinder Live Load Board --}}
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card sidebar-wrapper shadow">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background:var(--sidebar-bg);border-bottom:1px solid var(--chart-grid);">
                    <h6 class="mb-0" style="color:var(--text-color);font-family:'Jost',sans-serif;">
                        Live Load Board
                        <span style="color:var(--hover-color);font-size:11px;">FreightFinder.com</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="text" id="lbOriginInput" value="Dallas, TX" placeholder="Origin city, state"
                            class="form-control form-control-sm filter-input" style="width:150px;">
                        <select id="lbPagesSelect" class="form-select form-select-sm filter-input" style="width:120px;">
                            <option value="2">~50 loads</option>
                            <option value="4" selected>~100 loads</option>
                            <option value="6">~150 loads</option>
                            <option value="8">~200 loads</option>
                        </select>
                        <button id="lbSearchBtn" class="btn btn-sm theme-btn">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <select id="lbFilterType" class="form-select form-select-sm filter-input" style="width:145px;display:none;">
                            <option value="all">All Fields</option>
                            <option value="originState">Origin State</option>
                            <option value="destState">Dest State</option>
                            <option value="company">Company</option>
                            <option value="equipment">Equipment</option>
                            <option value="phone">Phone</option>
                        </select>
                        <small id="lbStatus" style="color:var(--search-placeholder);font-family:'Jost',sans-serif;"></small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="custom-table sidebar-wrapper mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Origin City</th>
                                    <th>Origin State</th>
                                    <th>Dest City</th>
                                    <th>Dest State</th>
                                    <th>Company</th>
                                    <th>Phone</th>
                                    <th>Equipment</th>
                                    <th>AI Match</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="lbBody">
                                <tr><td colspan="10" class="text-center p-3">
                                    <span class="loading-spinner"></span> Loading live loads...
                                </td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2"
                     style="background:var(--sidebar-bg);border-top:1px solid var(--chart-grid);padding-right:90px;">
                    <div class="d-flex align-items-center gap-2">
                        <small id="lbPageInfo" style="color:var(--search-placeholder);font-family:'Jost',sans-serif;"></small>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="d-flex align-items-center gap-1">
                            <small style="color:var(--search-placeholder);font-family:'Jost',sans-serif;white-space:nowrap;">Per page:</small>
                            <select id="lbPerPageSelect" class="form-select form-select-sm" style="width:70px;">
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                        <div id="lbPagination" class="d-flex gap-1 flex-wrap"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Load Detail Modal --}}
    <div id="lbModal">
        <div class="lb-modal-inner">
            <div class="lb-modal-header">
                <span class="lb-modal-title">Load Detail</span>
                <button id="lbModalClose" class="lb-modal-close">&times;</button>
            </div>
            <div id="lbModalBody"></div>
        </div>
    </div>

</div>

{{-- Toast --}}
<div id="assignToast">
    <i class="fas fa-check-circle me-2" style="color:var(--hover-color);"></i>
    <span id="assignToastMsg"></span>
</div>

@include('dispatch.ai-board-js')
@endsection
