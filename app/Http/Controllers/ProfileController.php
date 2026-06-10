<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {

 
        return view('auth.profile-update', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
public function update(Request $request): RedirectResponse
{
    $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'phoneNumber' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\(\)-]+$/'],
        'address1' => ['required', 'string', 'max:255'],
        'address2' => ['nullable', 'string', 'max:255'],
        'city' => ['required', 'string', 'max:255'],
        'state' => ['required', 'string', 'max:255'],
        'zip' => ['required', 'string', 'max:20'],
        'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
    ], [
        'phoneNumber.regex' => 'Phone number can only contain numbers, parentheses, and dashes.',
        'profile_image.max' => 'The profile image must not be larger than 2MB.',
        'profile_image.mimes' => 'Only JPEG, PNG, JPG, and GIF images are allowed.',
    ]);

    $user = $request->user();

    // Handle file upload - CHANGED FROM 'file' TO 'profile_image'
    if ($request->hasFile('profile_image')) {
        // Delete old profile image if exists
        if ($user->profile_image && file_exists(public_path('storage/' . $user->profile_image))) {
            unlink(public_path('storage/' . $user->profile_image));
        }

        $image = $request->file('profile_image'); // CHANGED HERE
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        
        // Create directory if it doesn't exist
        $publicStoragePath = public_path('storage/profile_images');
        if (!file_exists($publicStoragePath)) {
            mkdir($publicStoragePath, 0755, true);
        }
        
        // Move file directly to public/storage/profile_images
        $image->move($publicStoragePath, $imageName);
        
        // Save the path for database: profile_images/imagename
        $user->profile_image = 'profile_images/' . $imageName;
    }

    $user->fill([
        'first_name' => $request->first_name,
        'last_name' => $request->last_name,
        'phoneNumber' => $request->phoneNumber,
        'address1' => $request->address1,
        'address2' => $request->address2,
        'city' => $request->city,
        'state' => $request->state,
        'zip' => $request->zip,
    ]);

    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    $user->save();

    return Redirect::route('users.index')->with('success', 'Profile Updated Successfully');
}

}