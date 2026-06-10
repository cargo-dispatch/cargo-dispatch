<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting\GeneralSetting;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    public function index()
    {
        // Fetch existing settings or create new instance
        $setting = GeneralSetting::first();
        
        return view('general_settings.index', compact('setting'));
    }
    
    public function update(Request $request)
    {
        // Custom validation messages
        $messages = [
            'fuel_price.required' => 'Fuel price is required.',
            'fuel_price.numeric' => 'Fuel price must be a number.',
            'fuel_price.min' => 'Fuel price cannot be negative.',
            'fuel_price.max' => 'Fuel price cannot exceed 999,999.99.',
            'fuel_price.regex' => 'Fuel price can have maximum 2 decimal places.',
            
            'company_profit.required' => 'Company profit is required.',
            'company_profit.numeric' => 'Company profit must be a number.',
            'company_profit.min' => 'Company profit cannot be negative.',
            'company_profit.max' => 'Company profit cannot exceed 100%.',
            'company_profit.regex' => 'Company profit can have maximum 2 decimal places.',
        ];
        
        // Validation rules matching database constraints
        $validated = $request->validate([
            'fuel_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',  // decimal(8,2) max value
                'regex:/^\d+(\.\d{1,2})?$/'  // Max 2 decimal places
            ],
            'company_profit' => [
                'required',
                'numeric',
                'min:0',
                'max:100',  // Percentage cannot exceed 100
                'regex:/^\d+(\.\d{1,2})?$/'  // Max 2 decimal places
            ],
        ], $messages);

        // Get first setting or create new one
        $setting = GeneralSetting::first();
        
        if ($setting) {
            // Update existing settings
            $setting->update([
                'fuel_price' => $validated['fuel_price'],
                'company_profit' => $validated['company_profit'],
            ]);
        } else {
            // Create new settings
            GeneralSetting::create([
                'fuel_price' => $validated['fuel_price'],
                'company_profit' => $validated['company_profit'],
            ]);
        }

        // Return JSON response for AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'General settings updated successfully!'
            ]);
        }

        // Fallback for non-AJAX requests
        return redirect()->route('general.settings')->with('success', 'General settings updated successfully!');
    }
}