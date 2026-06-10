<?php

use App\Http\Controllers\API\CustomerAuthController;
use App\Http\Controllers\API\DriverAuthController;
use App\Http\Controllers\API\ShipmentDocumentController;
use App\Http\Controllers\API\Admin\AdminShipmentDocumentController;
use App\Http\Controllers\Api\TruckDispatchController;
use App\Http\Controllers\Api\MockDataController;
use App\Http\Controllers\ConnectyCube\ConnectyCubeController;
use App\Http\Controllers\Drivers\DriversController;
use App\Http\Controllers\UserManagement\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('driver')->group(function () {
    Route::post('/login', [DriverAuthController::class, 'login']);
    Route::post('/forgot-password', [DriverAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [DriverAuthController::class, 'resetPassword']);
    Route::post('/profile/update', [DriverAuthController::class, 'updateProfile']); 
    

    Route::middleware('auth:sanctum')->group(function () {
         Route::get('/current-location', [DriverAuthController::class, 'getCurrentLocation']);
        Route::post('/update-location', [DriverAuthController::class, 'updateLocation']);
        Route::get('/profile', [DriverAuthController::class, 'profile']);
        Route::get('/my-shipments', [DriverAuthController::class, 'myShipments']);
        Route::get('/vehicle', [DriverAuthController::class, 'myVehicle']);
        Route::post('/logout', [DriverAuthController::class, 'logout']);

        // Push notifications
        Route::post('/register-push-token', [DriverAuthController::class, 'registerPushToken']);

        // Location tracking
        Route::post('/update-location', [DriverAuthController::class, 'updateLocation']);

        // Shipment management
        Route::post('/update-shipment-status', [DriverAuthController::class, 'updateShipmentStatus']);
        Route::post('/update-duty-status', [DriverAuthController::class, 'updateDutyStatus']);
        Route::get('/duty-status', [DriverAuthController::class, 'getDutyStatus']);

        // Shipment comments
        Route::get('/shipments/{id}/comments', [DriverAuthController::class, 'getComments']);
        Route::post('/shipments/{id}/comments', [DriverAuthController::class, 'postComment']);

        // Driver uploads (BOL/POD/Rate Confirmation) - mock OCR via Document AI
        Route::get('/shipments/{id}/documents', [ShipmentDocumentController::class, 'indexForShipment']);
        Route::post('/shipments/{id}/documents', [ShipmentDocumentController::class, 'storeForDriver']);

        // Shipment Workflow (pickup → start trip → complete delivery)
        Route::get('/shipments/{id}/workflow', [\App\Http\Controllers\API\ShipmentWorkflowController::class, 'getShipmentDetail']);
        Route::post('/shipments/{id}/workflow-action', [\App\Http\Controllers\API\ShipmentWorkflowController::class, 'executeWorkflowAction']);
        Route::get('/weather-at-location', [\App\Http\Controllers\API\ShipmentWorkflowController::class, 'getWeatherForAddress']);

        // ✅ CHAT ROUTES - Restricted driver-to-admin only

    });
});

// ✅ PUBLIC: Vehicle types list (used by customer create-shipment form)
Route::get('/vehicle-types', function () {
    return response()->json(\App\Models\VehicleType\VehicleType::select('id', 'vehicle_type')->get());
});

// ✅ CUSTOMER ROUTES
Route::prefix('customer')->group(function () {
    // Public customer routes
    Route::post('/login', [CustomerAuthController::class, 'login']);
    Route::post('/forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [CustomerAuthController::class, 'resetPassword']);

    // Protected customer routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [CustomerAuthController::class, 'profile']);
        Route::put('/profile', [CustomerAuthController::class, 'updateProfile']); // ADD THIS
        Route::post('/logout', [CustomerAuthController::class, 'logout']);
        Route::delete('/shipments/{id}', [CustomerAuthController::class, 'deleteShipment']);

        // Shipment management
        Route::post('/shipments', [CustomerAuthController::class, 'createShipment']);
        Route::put('/shipments/{id}', [CustomerAuthController::class, 'updateShipment']);
        Route::get('/shipments/{id}', [CustomerAuthController::class, 'getShipment']);
        Route::get('/shipments/{id}/tracking', [CustomerAuthController::class, 'getShipmentTracking']);
        Route::get('/my-shipments', [CustomerAuthController::class, 'myShipments']);

        // Shipment comments
        Route::get('/shipments/{id}/comments', [CustomerAuthController::class, 'getComments']);
        Route::post('/shipments/{id}/comments', [CustomerAuthController::class, 'postComment']);

        // Realtime config (same Pusher config as driver)
        Route::get('/realtime/config', [DriverAuthController::class, 'getRealtimeConfig']);
    });
});

// ✅ CONNECTYCUBE ROUTES
Route::prefix('connectycube')->group(function () {
    Route::post('/verify', [ConnectyCubeController::class, 'verifyUser']);
    Route::post('/webhook', [ConnectyCubeController::class, 'handleWebhook']);
});

// ✅ DRIVER LOCATION API (for map/tracking)
Route::get('/drivers/locations', [DriversController::class, 'apiLocations'])
    ->name('api.drivers.locations');

// ✅ USER MANAGEMENT ROUTES (for admin panel)
Route::prefix('users')->middleware('auth:sanctum')->group(function () {
    Route::post('/sync-connectycube', [UserManagementController::class, 'syncWithConnectyCube']);
    Route::post('/update-connectycube-id', [UserManagementController::class, 'updateConnectyCubeId']);
    Route::get('/chat-users', [UserManagementController::class, 'getChatUsers']);
});

Route::middleware( 'auth:sanctum')->group(function () {
    // Get current user profile for chat
    Route::get('/chat/current-user', [DriverAuthController::class, 'getCurrentUser']);

    Route::get('/chat/online-users',  [DriverAuthController::class, 'getOnlineUsers']);


    // Batch status check (POST with user IDs array)
    Route::post('/chat/users/status', [DriverAuthController::class, 'batchCheckStatus']);

    // Get all admin users for chat
    Route::get('/chat/admin-users', [DriverAuthController::class, 'getAdminUsers']);

    // Get ConnectyCube session token
    Route::get('/chat/session-token', [DriverAuthController::class, 'getSessionToken']);

    // Get chat dialogs/conversations
    Route::get('/chat/dialogs', [DriverAuthController::class, 'getDialogs']);

    // Get messages for a specific dialog
    Route::get('/chat/messages', [DriverAuthController::class, 'getMessages']);

    // Create a new chat dialog
    Route::post('/chat/create-dialog', [DriverAuthController::class, 'createDialog']);

    // Send a message
    Route::post('/chat/send-message', [DriverAuthController::class, 'sendMessage']);

    // Typing status (near real-time helper for web/mobile chat UIs)
    Route::post('/chat/typing', [DriverAuthController::class, 'updateTypingStatus']);
    Route::get('/chat/typing', [DriverAuthController::class, 'getTypingStatus']);
    Route::get('/realtime/config', [DriverAuthController::class, 'getRealtimeConfig']);

    // Check specific user's online status by ConnectyCube ID
    Route::get('/chat/user-status/{connectycube_id}', [DriverAuthController::class, 'getUserStatus']);

    // Batch check multiple users' status
    Route::post('/chat/users/batch-status', [DriverAuthController::class, 'batchCheckStatus']);

    // Alternative: Get only online users (your existing endpoint - improved)
    Route::get('/chat/online-users', [DriverAuthController::class, 'getOnlineUsers']);
});

Route::post('/upload-image', function (Request $request) {
    try {
        $imageData = $request->input('image');
        $filename = $request->input('filename', 'image_' . time() . '.jpg');

        // Remove the data:image/...;base64, part
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
        $imageData = base64_decode($imageData);

        // Save to storage
        $path = 'uploads/images/' . $filename;
        Storage::disk('public')->put($path, $imageData);

        // Store in database if needed
        // Image::create(['filename' => $filename, 'path' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'path' => $path
        ]);
    } catch (\Exception) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to upload image'
        ], 500);
    }
});
Route::get('/chat/users-status', [DriverAuthController::class, 'getUsersStatus'])->name('check.user.status');
Route::middleware('auth:sanctum')->group(function () {
    // Truck dispatch routes
    Route::apiResource('trucks', TruckDispatchController::class);
    Route::post('trucks/{id}/dispatch', [TruckDispatchController::class, 'dispatch']);
    Route::get('dashboard/stats', [TruckDispatchController::class, 'dashboardStats']);

    // File upload routes
    Route::post('upload', [TruckDispatchController::class, 'uploadFile']);
});

// Broadcasting auth — accepts driver, customer, and admin Sanctum tokens
Route::post('/broadcasting/auth', function (\Illuminate\Http\Request $request) {
    $bearerToken = $request->bearerToken();
    if (!$bearerToken) {
        \Illuminate\Support\Facades\Log::warning('broadcasting/auth: no bearer token');
        return response()->json(['error' => 'no_token'], 403);
    }

    // Sanctum tokens are "id|rawToken" — hash only the raw part
    $parts = explode('|', $bearerToken, 2);
    $rawToken = $parts[1] ?? $bearerToken;
    $hashedToken = hash('sha256', $rawToken);

    // Find token across all models (User, Driver, Customer)
    $pat = \Laravel\Sanctum\PersonalAccessToken::where('token', $hashedToken)->first();
    if (!$pat) {
        \Illuminate\Support\Facades\Log::warning('broadcasting/auth: token not found in DB', ['token_prefix' => substr($bearerToken, 0, 20)]);
        return response()->json(['error' => 'token_not_found'], 403);
    }

    $user = $pat->tokenable;
    if (!$user) {
        return response()->json(['error' => 'user_not_found'], 403);
    }

    // Bind user to request so Broadcast::auth() and channel callbacks can access it
    $request->setUserResolver(fn() => $user);
    \Illuminate\Support\Facades\Auth::guard()->setUser($user);

    try {
        return Broadcast::auth($request);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('broadcasting/auth: Broadcast::auth() failed', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'channel_auth_failed', 'message' => $e->getMessage()], 403);
    }
});

// Admin document routes (web session auth)
Route::middleware('auth.admin')->group(function () {
    Route::prefix('admin/shipments')->group(function () {
        Route::get('/documents', [AdminShipmentDocumentController::class, 'getDocuments']);
        Route::get('/documents/{id}', [AdminShipmentDocumentController::class, 'getDocument']);
        Route::get('/documents/stats', [AdminShipmentDocumentController::class, 'getDocumentStats']);

        // Shipment assignment with push notifications
        Route::post('/{id}/assign-driver', [\App\Http\Controllers\API\ShipmentAssignmentController::class, 'assignDriver']);
        Route::put('/{id}/driver', [\App\Http\Controllers\API\ShipmentAssignmentController::class, 'updateDriver']);
    });
});

// Integration test endpoints (no auth — dev/demo only)
// Each returns mock data until you add the real API key to .env
Route::prefix('mock')->group(function () {
    // Existing
    Route::get('/eld-snapshot', [MockDataController::class, 'eldSnapshot']);
    Route::get('/open-loads',   [MockDataController::class, 'openLoads']);
    Route::post('/ai-test',     [MockDataController::class, 'aiTest']);

    // Weather  →  add TOMORROW_IO_KEY to .env to switch to real
    Route::get('/weather', function (
        \Illuminate\Http\Request $req,
        \App\Services\Integrations\Contracts\WeatherProviderInterface $weather
    ) {
        return response()->json([
            'route_weather' => $weather->getRouteWeather(
                (float) $req->query('lat1', 32.7767),
                (float) $req->query('lng1', -96.7970),
                (float) $req->query('lat2', 33.7490),
                (float) $req->query('lng2', -84.3880)
            ),
            'alerts' => $weather->getWeatherAlerts($req->query('state', 'TX')),
        ]);
    });

    // Fuel stations  →  add NREL_API_KEY + EIA_API_KEY to .env to switch to real
    Route::get('/fuel', function (
        \Illuminate\Http\Request $req,
        \App\Services\Integrations\Contracts\FuelProviderInterface $fuel
    ) {
        $radiusMiles = (int) $req->query('radius_miles', 25);
        return response()->json([
            'nearby_stations'     => $fuel->getNearbyStations(
                (float) $req->query('lat', 32.7767),
                (float) $req->query('lng', -96.7970),
                $radiusMiles
            ),
            'state_diesel_price'  => $fuel->getStateDieselPrice($req->query('state', 'TX')),
        ]);
    });

    // SMS  →  add TWILIO_SID + TWILIO_TOKEN + TWILIO_FROM to .env to switch to real
    Route::post('/sms', function (
        \Illuminate\Http\Request $req,
        \App\Services\Integrations\Contracts\NotificationProviderInterface $notify
    ) {
        $req->validate(['phone' => 'required', 'message' => 'required']);
        return response()->json(
            $notify->sendSms($req->input('phone'), $req->input('message'))
        );
    });

    // Compliance  →  add FMCSA_API_KEY to .env to switch to real (free key)
    Route::get('/compliance/{dot}', function (
        string $dot,
        \App\Services\Integrations\Contracts\ComplianceProviderInterface $compliance
    ) {
        return response()->json([
            'carrier_info' => $compliance->getCarrierInfo($dot),
            'authority'    => $compliance->checkAuthority($dot),
        ]);
    });

    // Payment  →  add STRIPE_SECRET_KEY to .env to switch to real
    Route::post('/invoice', function (
        \Illuminate\Http\Request $req,
        \App\Services\Integrations\Contracts\PaymentProviderInterface $payment
    ) {
        return response()->json(
            $payment->createInvoice($req->all())
        );
    });
    Route::get('/invoice/{id}/status', function (
        string $id,
        \App\Services\Integrations\Contracts\PaymentProviderInterface $payment
    ) {
        return response()->json($payment->getPaymentStatus($id));
    });

    // IFTA  →  always internal calculation (no external key needed)
    Route::get('/ifta/{vehicleId}', function (
        int $vehicleId,
        \Illuminate\Http\Request $req,
        \App\Services\Integrations\Contracts\IftaProviderInterface $ifta
    ) {
        return response()->json(
            $ifta->generateQuarterlyReport(
                $vehicleId,
                (int) $req->query('year', date('Y')),
                (int) $req->query('quarter', ceil(date('n') / 3))
            )
        );
    });

    // Maps  →  add GOOGLE_MAPS_API_KEY to .env to switch to real
    Route::get('/route', function (
        \Illuminate\Http\Request $req,
        \App\Services\Integrations\Contracts\MapsProviderInterface $maps
    ) {
        return response()->json(
            $maps->estimateRoute(
                (float) $req->query('lat1', 32.7767),
                (float) $req->query('lng1', -96.7970),
                (float) $req->query('lat2', 33.7490),
                (float) $req->query('lng2', -84.3880)
            )
        );
    });
});