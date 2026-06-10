@extends('layouts.app')

@section('title')
    {{ isset($record) ? 'Edit Content' : 'Add Content' }}
@endsection

@section('content')
<form id="roleForm" action="{{ isset($record) ? route('cms.update', $record->id) : route('cms.store') }}" method="POST">
    @csrf
    @if(isset($record))
        @method('PUT')
    @endif

    <div class="d-flex justify-content-end mb-3">
      <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 shadow-lg me-2">
    {{ isset($record) ? 'Update ' .$name : 'Create ' . $name }}
</button>
        <a href="{{ route('cms.index') }}" class="btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm">Back</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light text-center">
            <h5 class="mb-0">{{ isset($record) ? 'Edit Content' : 'Add New Content' }}</h5>
        </div>

        <div class="card-body">
            {{-- Type --}}
            <div class="mb-3">
                <label class="form-label">Type</label>
                <select name="type" class="form-select {{ $errors->has('type') ? 'is-invalid' : '' }}">
                    <option value="CMS" {{ (old('type', $record->type ?? '') == 'CMS') ? 'selected' : '' }}>CMS</option>
                    <option value="Services" {{ (old('type', $record->type ?? '') == 'Services') ? 'selected' : '' }}>Services</option>
                </select>
                @error('type')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Title --}}
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" maxlength="500" class="form-control {{ $errors->has('title') ? 'is-invalid' : '' }}"
                       value="{{ old('title', $record->title ?? '') }}" placeholder="Enter title">
                @error('title')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Slug --}}
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}"
                       value="{{ old('slug', $record->slug ?? '') }}" placeholder="Enter slug">
                @error('slug')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Meta Tags --}}
            <div class="mb-3">
                <label class="form-label">Meta Tags</label>
                <textarea name="meta_tags" class="form-control {{ $errors->has('meta_tags') ? 'is-invalid' : '' }}" rows="3">{{ old('meta_tags', $record->meta_tags ?? '') }}</textarea>
                @error('meta_tags')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Meta Keywords --}}
            <div class="mb-3">
                <label class="form-label">Meta Keywords</label>
                <textarea name="meta_keywords" class="form-control {{ $errors->has('meta_keywords') ? 'is-invalid' : '' }}" rows="3">{{ old('meta_keywords', $record->meta_keywords ?? '') }}</textarea>
                @error('meta_keywords')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Image --}}
            <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-control {{ $errors->has('image') ? 'is-invalid' : '' }}">
                @if(isset($record->image))
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $record->image) }}" alt="Image" class="max-height-100px">
                    </div>
                @endif
                @error('image')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Content --}}
            <div class="mb-3">
                <label class="form-label">Content</label>
                <textarea name="content" class="form-control {{ $errors->has('content') ? 'is-invalid' : '' }}" rows="5">{{ old('content', $record->content ?? '') }}</textarea>
                @error('content')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>

            {{-- Is Active --}}
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active"
                       {{ old('is_active', $record->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
    </div>
</form>
@include('cms.index-js')
@endsection
