<?php

namespace App\Http\Controllers\Drivers;

use App\Http\Controllers\Controller;
use App\Mail\DriverApprovedMail;
use App\Mail\DriverInvitationMail;
use App\Mail\DriverRejectedMail;
use App\Models\Drivers\Driver;
use App\Models\Drivers\DriverDocument;
use App\Models\Drivers\DriverInvitation;
use App\Models\DriverType\DriverType;
use App\Models\VehicleType\VehicleType;
use App\Services\Integrations\Contracts\NotificationProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DriverOnboardingController extends Controller
{
    public function __construct(
        protected NotificationProviderInterface $notify,
    ) {}

    // =========================================================================
    // ADMIN — Send invitation
    // =========================================================================

    public function invite(Request $request)
    {
        $data = $request->validate([
            'email'          => 'required|email',
            'firstname'      => 'required|string|max:100',
            'lastname'       => 'required|string|max:100',
            'phoneno'        => 'nullable|string|max:20',
            'driver_type_id' => 'nullable|exists:driver_types,id',
        ]);

        // Check not already an active driver
        if (Driver::where('email', $data['email'])->where('status', 'active')->exists()) {
            return back()->withErrors(['email' => 'A driver with this email is already active.']);
        }

        // Invalidate any previous pending invitation
        DriverInvitation::where('email', $data['email'])->whereNull('used_at')->delete();

        $invitation = DriverInvitation::create([
            'email'          => $data['email'],
            'firstname'      => $data['firstname'],
            'lastname'       => $data['lastname'],
            'phoneno'        => $data['phoneno'] ?? null,
            'driver_type_id' => $data['driver_type_id'] ?? null,
            'token'          => Str::random(64),
            'created_by'     => Auth::id(),
            'expires_at'     => now()->addDays(7),
        ]);

        Mail::to($invitation->email)->send(new DriverInvitationMail($invitation));

        return back()->with('success', "Invitation sent to {$invitation->email}");
    }

    // =========================================================================
    // PUBLIC — Show registration form (driver clicks email link)
    // =========================================================================

    public function showRegistration(string $token)
    {
        $invitation = DriverInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            return view('drivers.onboarding.expired', compact('invitation'));
        }

        $vehicleTypes = VehicleType::all();
        $driverTypes  = DriverType::all();

        return view('drivers.onboarding.register', compact('invitation', 'vehicleTypes', 'driverTypes'));
    }

    // =========================================================================
    // PUBLIC — Driver submits registration form
    // =========================================================================

    public function submitRegistration(Request $request, string $token)
    {
        $invitation = DriverInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isValid()) {
            return back()->withErrors(['token' => 'This invitation link has expired or already been used.']);
        }

        $data = $request->validate([
            'firstname'               => 'required|string|max:100',
            'lastname'                => 'required|string|max:100',
            'phoneno'                 => 'required|string|max:20',
            'emergencycontactno'      => 'nullable|string|max:20',
            'date_of_birth'           => 'required|date|before:-18 years',
            'ssn_last4'               => 'required|digits:4',
            'address'                 => 'required|string|max:255',
            'city'                    => 'required|string|max:100',
            'state'                   => 'required|string|size:2',
            'zip'                     => 'required|string|max:10',
            'cdl_number'              => 'required|string|max:30',
            'cdl_state'               => 'required|string|size:2',
            'cdl_class'               => 'required|in:A,B,C',
            'cdl_expiry_date'         => 'required|date|after:today',
            'cdl_endorsements'        => 'nullable|array',
            'medical_card_expiry'     => 'required|date|after:today',
            'drug_test_date'          => 'required|date',
            'drug_test_status'        => 'required|in:passed,failed,pending',
            'years_experience'        => 'required|integer|min:0|max:50',
            'preferred_truck_type_id' => 'nullable|exists:vehicle_types,id',
            'equipment_types'         => 'nullable|array',
            // Documents
            'doc_cdl_front'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'doc_cdl_back'            => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'doc_medical_card'        => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'doc_drug_test'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'doc_profile_photo'       => 'nullable|image|max:5120',
        ]);

        // Wrap in a transaction with a row-level lock on the invitation so that
        // two simultaneous submits cannot both pass the isValid() check.
        $driver = DB::transaction(function () use ($invitation, $data, $request) {

            // Re-fetch with a write lock — second request will block until first commits
            $inv = DriverInvitation::where('id', $invitation->id)->lockForUpdate()->first();

            if (!$inv->isValid()) {
                // Second request arrives after first already marked used — redirect to submitted page
                return null;
            }

            // Mark used immediately inside the transaction
            $inv->update(['used_at' => now()]);

            $driver = Driver::updateOrCreate(
                ['email' => $inv->email],
                array_merge($data, [
                    'drivertype'         => $inv->driver_type_id,
                    'status'             => 'pending_review',
                    'onboarding_status'  => 'docs_submitted',
                    'invited_at'         => $inv->created_at,
                    'invited_by'         => $inv->created_by,
                    'password'           => Hash::make(Str::random(16)),
                    'expiry_date'        => $data['cdl_expiry_date'],
                ])
            );

            $inv->update(['driver_id' => $driver->id]);

            return $driver;
        });

        // Null means a duplicate submit arrived after the first — just show success
        if (is_null($driver)) {
            return view('drivers.onboarding.submitted');
        }

        // Store uploaded documents (outside transaction — file I/O doesn't need to be transactional)
        $this->storeDocument($request, $driver, 'doc_cdl_front',     'cdl_front',   $data['cdl_expiry_date']);
        $this->storeDocument($request, $driver, 'doc_cdl_back',      'cdl_back');
        $this->storeDocument($request, $driver, 'doc_medical_card',  'medical_card', $data['medical_card_expiry']);
        $this->storeDocument($request, $driver, 'doc_drug_test',     'drug_test');
        $this->storeDocument($request, $driver, 'doc_profile_photo', 'profile_photo');

        // Notify admin via SMS (mock or real Twilio)
        $adminPhone = config('app.admin_phone', '');
        if ($adminPhone) {
            $this->notify->sendSms(
                $adminPhone,
                "New driver application submitted: {$driver->firstname} {$driver->lastname}. Review at " . route('drivers.onboarding.pending')
            );
        }

        return view('drivers.onboarding.submitted');
    }

    // =========================================================================
    // ADMIN — List pending review drivers
    // =========================================================================

    public function pendingList(Request $request)
    {
        $query = Driver::with(['drivertype', 'documents'])
            ->whereIn('onboarding_status', ['docs_submitted', 'under_review'])
            ->latest();

        // Filters
        if ($request->filled('driver_type')) {
            $query->where('drivertype', $request->driver_type);
        }

        $drivers      = $query->paginate(20);
        $driverTypes  = DriverType::all();
        $totalPending = Driver::whereIn('onboarding_status', ['docs_submitted', 'under_review'])->count();

        return view('drivers.onboarding.pending', compact('drivers', 'driverTypes', 'totalPending'));
    }

    // =========================================================================
    // ADMIN — Review a single driver application
    // =========================================================================

    public function review(int $id)
    {
        $driver = Driver::with(['documents', 'drivertype'])->findOrFail($id);

        // Mark as under review if still in docs_submitted
        if ($driver->onboarding_status === 'docs_submitted') {
            $driver->update(['onboarding_status' => 'under_review']);
        }

        $docTypes = DriverDocument::$typeLabels;
        $required = DriverDocument::$requiredTypes;

        return view('drivers.onboarding.review', compact('driver', 'docTypes', 'required'));
    }

    // =========================================================================
    // ADMIN — Approve driver
    // =========================================================================

    public function approve(int $id)
    {
        $driver = Driver::findOrFail($id);

        $plainPassword = Str::random(10) . '1!'; // meets most password policies

        $driver->update([
            'status'            => 'active',
            'onboarding_status' => 'approved',
            'approved_at'       => now(),
            'approved_by'       => Auth::id(),
            'rejection_reason'  => null,
            'password'          => Hash::make($plainPassword),
        ]);

        // Send approval + credentials email
        Mail::to($driver->email)->send(new DriverApprovedMail($driver, $plainPassword));

        // SMS notification
        if ($driver->phoneno) {
            $this->notify->sendSms(
                $driver->phoneno,
                "Congratulations {$driver->firstname}! Your driver application has been approved. Check your email for login details."
            );
        }

        return redirect()->route('drivers.onboarding.pending')
            ->with('success', "Driver {$driver->firstname} {$driver->lastname} approved and credentials sent.");
    }

    // =========================================================================
    // ADMIN — Reject driver
    // =========================================================================

    public function reject(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|min:10|max:1000',
        ]);

        $driver = Driver::findOrFail($id);

        $driver->update([
            'status'            => 'rejected',
            'onboarding_status' => 'rejected',
            'rejection_reason'  => $request->reason,
        ]);

        Mail::to($driver->email)->send(new DriverRejectedMail($driver, $request->reason));

        if ($driver->phoneno) {
            $this->notify->sendSms(
                $driver->phoneno,
                "Hi {$driver->firstname}, there's an update on your driver application. Please check your email."
            );
        }

        return redirect()->route('drivers.onboarding.pending')
            ->with('success', "Driver rejected and notified.");
    }

    // =========================================================================
    // ADMIN — Serve document file (avoids storage symlink issues on Windows)
    // =========================================================================

    public function viewDocument(int $docId)
    {
        $doc  = DriverDocument::findOrFail($docId);
        $path = Storage::disk('public')->path($doc->file_path);

        if (!file_exists($path)) {
            abort(404, 'Document file not found.');
        }

        return response()->file($path, [
            'Content-Type'        => $doc->mime_type ?? mime_content_type($path),
            'Content-Disposition' => 'inline; filename="' . $doc->original_name . '"',
        ]);
    }

    // =========================================================================
    // ADMIN — Verify a single document
    // =========================================================================

    public function verifyDocument(Request $request, int $docId)
    {
        $doc    = DriverDocument::findOrFail($docId);
        $action = $request->input('action'); // 'verify' or 'reject'

        if ($action === 'verify') {
            $doc->update([
                'status'      => 'verified',
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);
        } else {
            $request->validate(['reason' => 'required|string']);
            $doc->update([
                'status'           => 'rejected',
                'rejection_reason' => $request->reason,
            ]);
        }

        return response()->json(['success' => true, 'status' => $doc->status]);
    }

    // =========================================================================
    // ADMIN — All drivers list with filters
    // =========================================================================

    public function allDrivers(Request $request)
    {
        $query = Driver::with(['drivertype', 'vehicleAssignments.vehicle'])
            ->where('status', '!=', 'invited');

        // Filter: status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: onboarding
        if ($request->filled('onboarding_status')) {
            $query->where('onboarding_status', $request->onboarding_status);
        }

        // Filter: driver type
        if ($request->filled('driver_type')) {
            $query->where('drivertype', $request->driver_type);
        }

        // Filter: preferred truck type
        if ($request->filled('truck_type')) {
            $query->where('preferred_truck_type_id', $request->truck_type);
        }

        // Filter: HOS remaining (driving hours)
        if ($request->filled('min_hos')) {
            $minMinutes = (int) $request->min_hos * 60;
            $query->where('hos_drive_remaining_minutes', '>=', $minMinutes);
        }

        // Filter: CDL class
        if ($request->filled('cdl_class')) {
            $query->where('cdl_class', $request->cdl_class);
        }

        // Filter: duty status
        if ($request->filled('duty_status')) {
            $query->where('current_duty_status', $request->duty_status);
        }

        // Filter: CDL expiring soon
        if ($request->boolean('cdl_expiring')) {
            $query->whereDate('cdl_expiry_date', '<=', now()->addDays(60));
        }

        // Filter: medical card expiring soon
        if ($request->boolean('medical_expiring')) {
            $query->whereDate('medical_card_expiry', '<=', now()->addDays(60));
        }

        $drivers     = $query->latest()->paginate(25)->withQueryString();
        $driverTypes = DriverType::all();
        $vehicleTypes = VehicleType::all();

        // Single aggregated query for summary counts
        $statusCounts = Driver::selectRaw("
            SUM(status = 'active') as active,
            SUM(status = 'pending_review') as pending_review,
            SUM(status = 'suspended') as suspended,
            SUM(current_duty_status = 'driving') as driving
        ")->first();

        $counts = [
            'active'         => (int) $statusCounts->active,
            'pending_review' => (int) $statusCounts->pending_review,
            'suspended'      => (int) $statusCounts->suspended,
            'driving'        => (int) $statusCounts->driving,
        ];

        return view('drivers.index', compact('drivers', 'driverTypes', 'vehicleTypes', 'counts'));
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function storeDocument(Request $request, Driver $driver, string $field, string $type, ?string $expiresAt = null): void
    {
        if (!$request->hasFile($field)) return;

        $file = $request->file($field);
        $path = $file->store("drivers/{$driver->id}/documents", 'public');

        DriverDocument::create([
            'driver_id'     => $driver->id,
            'type'          => $type,
            'file_path'     => $path,
            'original_name' => $file->getClientOriginalName(),
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'status'        => 'pending',
            'expires_at'    => $expiresAt,
        ]);
    }
}
