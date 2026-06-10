<!-- resources/views/file/preview.blade.php -->
@extends('layouts.app')

@section('title')
    File Preview: {{ $fileName }}
@endsection

@section('content')
<div class="container p-0 ">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="fs-11">File Preview: {{ $fileName }}</p>
        <div>
            <a href="{{ asset($filePath) }}" class="btn btn-primary mbl-btn mb-2" download="{{ $fileName }}">
                <i class="bi bi-download me-2"></i> Download
            </a>
            <a href="javascript:history.back()" class="btn mbl-btn btn-outline-secondary ms-2">
                <i class="bi bi-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            @if($isPreviewable)
                @if(in_array($fileType, ['jpg', 'jpeg', 'png', 'gif']))
                    <div class="text-center">
                        <img src="{{ asset($filePath) }}" alt="{{ $fileName }}" class="img-fluid img-file-preview-container">
                    </div>
                @elseif($fileType == 'pdf')
                    <div class="ratio ratio-16x9 img-file-preview-container">
                        <iframe src="{{ asset($filePath) }}#toolbar=1&navpanes=1&scrollbar=1" title="{{ $fileName }}" allowfullscreen></iframe>
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-file-earmark-{{ $fileType == 'doc' || $fileType == 'docx' ? 'word' : 'text' }} display-1"></i>
                    </div>
                    <h3>{{ $fileName }}</h3>
                    <p class="text-muted">This file type cannot be previewed directly in the browser.</p>
                    <a href="{{ asset($filePath) }}" class="btn btn-lg btn-primary mt-3" download="{{ $fileName }}">
                        <i class="bi bi-download me-2"></i> Download File
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection