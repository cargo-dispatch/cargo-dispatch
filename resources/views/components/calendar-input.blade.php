{{-- resources/views/components/calendar-input.blade.php --}}
@props([
    'name' => '',
    'value' => '',
    'id' => null,
    'label' => '',
    'required' => false,
    'placeholder' => '',
    'error' => null,
    'class' => '',
    'icon' => null,
    'min' => null,
    'max' => null,
    'showErrorDiv' => true,
    'inputClass' => 'sidebar-wrapper',
    'type' => 'date',  // Add type prop: 'date' or 'datetime-local'
    'showTime' => false  // Optional: separate prop for time
])

@php
    $calendarIcon = $icon ?? asset('assets/img/calender.png');
    $inputId = $id ?? $name;
    $displayId = $inputId . '_display';
    
    // Format value based on type
    if ($type === 'datetime-local') {
        try {
            $formattedValue = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i') : '';
            $displayValue = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d H:i') : '';
        } catch (Exception $e) {
            $formattedValue = $value;
            $displayValue = $value;
        }
        $placeholder = $placeholder ?: 'Select date & time';
    } else {
        $formattedValue = $value ? date('Y-m-d', strtotime($value)) : '';
        $displayValue = $formattedValue;
        $placeholder = $placeholder ?: 'Select date';
    }
@endphp

<div class="custom-date-input {{ $class }}">
    @if($label)
        <label class="form-label" for="{{ $inputId }}">
            {{ $label }} @if($required) <span class="text-danger">*</span> @endif
        </label>
    @endif
    
    <div class="position-relative">
        <input 
            type="{{ $type }}" 
            name="{{ $name }}" 
            id="{{ $inputId }}"
            value="{{ $formattedValue }}"
            {{ $required ? 'required' : '' }}
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            onchange="updateCalendarDisplay('{{ $inputId }}', '{{ $displayId }}', '{{ $type }}')"
            class="bg-overlay-opacity">
        
        <div class="date-display {{ $inputClass }}" onclick="openDatePicker('{{ $inputId }}')">
            <span id="{{ $displayId }}" class="{{ $formattedValue ? '' : 'date-placeholder' }}">
                {{ $formattedValue ? $displayValue : $placeholder }}
            </span>
            <img src="{{ $calendarIcon }}" alt="Calendar" class="calendar-icon">
        </div>
    </div>
    
    @if($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif
    
    @if($showErrorDiv)
        <div class="invalid-feedback" id="{{ $inputId }}_error"></div>
    @endif
</div>