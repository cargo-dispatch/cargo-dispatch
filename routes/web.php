<?php

use App\Http\Controllers\Cms\CmsController;
use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Drivers\DriversController;
use App\Http\Controllers\Drivers\CredentialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagement\UserManagementController;
use App\Http\Controllers\RoleController\AdminRoleController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\AiBoardController;
use App\Http\Controllers\Dispatch\DispatchController;
use App\Http\Controllers\DriverPayRollController;
use Illuminate\Support\Facades\Broadcast;

use App\Http\Controllers\Modules\ModuleController;
use App\Http\Controllers\DriverType\DriverTypeController;
use App\Http\Controllers\Maintenance\MaintenanceController;
use App\Http\Controllers\MaintenanceType\MaintenanceTypeController;
use App\Http\Controllers\Permissions\PermissionController;
use App\Http\Controllers\ShipmentManagement\ShipmentController;
use App\Http\Controllers\ShipmentNotificationController;
use App\Http\Controllers\Vehicles\VehiclesController;
use App\Http\Controllers\VehicleType\VehicleTypeController;
use App\Http\Controllers\Vehicleassignment\VehicleAssignmentController;
use App\Http\Controllers\GeneralSetting\GeneralSetting;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\ShipmentInvoiceController;
use App\Http\Controllers\Drivers\DriverOnboardingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Driver app download — public, no auth required
Route::get('/app', function () {
    $androidConfigured = config('services.driver_app.android_url');
    $androidUrl = $androidConfigured ?: url('/download/android');

    return view('app.download', [
        'installUrl' => route('app.install'),
        'iosUrl' => config('services.driver_app.ios_url'),
        'androidUrl' => $androidUrl,
    ]);
})->name('app.install');

Route::get('/download/android', function () {
    $playUrl = config('services.driver_app.android_url');
    if ($playUrl) {
        return redirect()->away($playUrl);
    }

    $path = public_path('downloads/cargo-dispatch.apk');
    if (!file_exists($path)) {
        return response('<html><body style="font-family:sans-serif;text-align:center;padding:60px">
            <h2>App Coming Soon</h2>
            <p>The APK has not been uploaded to the server yet.<br>Please contact the administrator.</p>
        </body></html>', 404)->header('Content-Type', 'text/html');
    }

    return response()->download($path, 'CargoDispatch.apk', [
        'Content-Type' => 'application/vnd.android.package-archive',
    ]);
})->name('app.download.android');

// Legacy /download — auto-detect platform or show chooser
Route::get('/download', function (Request $request) {
    $ua = $request->header('User-Agent', '');
    $iosUrl = config('services.driver_app.ios_url');

    if (preg_match('/iPhone|iPad|iPod/i', $ua) && $iosUrl) {
        return redirect()->away($iosUrl);
    }
    if (preg_match('/Android/i', $ua)) {
        return redirect()->route('app.download.android');
    }

    return redirect()->route('app.install');
})->name('app.download');

// ── Public driver onboarding routes (no auth) ─────────────────────────────
Route::get('/driver/register/{token}', [DriverOnboardingController::class, 'showRegistration'])
    ->name('driver.register');
Route::post('/driver/register/{token}', [DriverOnboardingController::class, 'submitRegistration'])
    ->name('driver.register.submit');




Route::get('/admin', function () {
    if (Auth::check()) {
        return redirect('/admin/dashboard');
    }
    return view('auth.login');
})->name('admin.login');

Route::get('admin/chat-session', [DashboardController::class, 'createSession'])->middleware('auth.admin')->name('chat.session');
// Theme toggle route
Route::post('/theme', function (Request $request) {
    $theme = $request->input('theme', 'light');

    // Validate theme
    if (!in_array($theme, ['light', 'dark'])) {
        return response()->json(['error' => 'Invalid theme'], 400);
    }

    // Save to session
    session(['theme' => $theme]);

    return response()->json([
        'success' => true,
        'theme' => $theme,
        'message' => 'Theme updated successfully'
    ]);
})->name('theme.update');


// Logout route
Route::post('/admin/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/admin');
})->name('admin.logout');

// Dashboard route (with auth middleware)
Route::get('admin/dashboard', [DashboardController::class, 'dashboard'])
    ->middleware('auth.admin')
    ->name('dashboard');

// Profile routes
Route::middleware('auth.admin')->prefix('admin')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::middleware(['auth.admin'])->prefix('admin')
    ->controller(GeneralSettingController::class)
    ->group(function () {
        Route::get('/settings/general', 'index')->name('general.settings');
        Route::post('/settings/general', 'update')->name('general.settings.update');
    });

/*////////////////////// Drivers Type Controller Start   */
Route::middleware(['auth.admin'])->prefix('admin/driver')->controller(DriverTypeController::class)->group(function () {
    Route::get('/', 'index')->name('driver.index');
    Route::get('/get-drivers', 'getUsers')->name('drivers.get');
    Route::get('/delete/{id}', [DriverTypeController::class, 'delete'])->name('user.delete');
    Route::get('/create', 'create')->middleware('can:driver type.create')->name('driver.create');
    Route::post('/', 'store')->name('driver.store');
    Route::get('/{driver}/edit', 'edit')->name('driver.edit');
    Route::put('/{driver}', 'update')->name('driver.update');
    Route::get('/type/details/{id}', [DriverTypeController::class, 'show'])->name('driver.details');
    Route::delete('/{driver}', 'destroy')->middleware('can:driver type.delete')->name('driver.destroy');
    Route::post('/drivers/bulk-destroy', [DriverTypeController::class, 'bulkDestroy'])->middleware('can:driver type.delete')->name('driver.bulk-destroy');
});

/*////////////////////// DriversType Controller End   */

/*////////////////////// Drivers  Controller Start   */
Route::middleware(['auth.admin'])->prefix('admin/drivers')->controller(DriversController::class)->group(function () {
    Route::get('/', 'index')->name('managedriver.index');
    Route::get('/drivers/map', [DriversController::class, 'showMap'])->name('drivers.map');

    Route::get('/get-managedrivers', 'getUsers')->name('managedriver.get');
    Route::get('/get-driver-types', 'getDriverTypes')->name('managedriver.driver-types');
    Route::get('/delete/{id}', [DriversController::class, 'delete'])->name('managedriver.delete');
    Route::get('/create', 'create')->middleware('can:drivers.create')->name('managedriver.create');
    Route::post('/', 'store')->name('managedriver.store');
    Route::get('/{managedriver}/edit', 'edit')->name('managedriver.edit');
    Route::put('/{managedriver}', 'update')->name('managedriver.update');
    Route::delete('/{managedriver}', 'destroy')->middleware('can:drivers.delete')->name('managedriver.destroy');
    Route::get('/details/{id}', [DriversController::class, 'show'])->name('drivers.details');
    Route::post('/{id}/status', [DriversController::class, 'updateStatus'])->name('drivers.update-status');
    Route::post('/bulk-destroy', [DriversController::class, 'bulkDestroy'])->middleware('can:drivers.delete')->name('managedriver.bulk-destroy');
});
/*////////////////////// Drivers  Controller End  */

// ── Driver Onboarding (admin) ──────────────────────────────────────────────
Route::middleware(['auth.admin'])->prefix('admin/drivers/onboarding')->group(function () {
    // Send invitation
    Route::post('/invite', [DriverOnboardingController::class, 'invite'])
        ->name('drivers.onboarding.invite');
    // Pending approvals list
    Route::get('/pending', [DriverOnboardingController::class, 'pendingList'])
        ->name('drivers.onboarding.pending');
    // Review a single driver application
    Route::get('/{id}/review', [DriverOnboardingController::class, 'review'])
        ->name('drivers.onboarding.review');
    // Approve
    Route::post('/{id}/approve', [DriverOnboardingController::class, 'approve'])
        ->name('drivers.onboarding.approve');
    // Reject
    Route::post('/{id}/reject', [DriverOnboardingController::class, 'reject'])
        ->name('drivers.onboarding.reject');
    // Verify/reject a document
    Route::post('/document/{docId}/verify', [DriverOnboardingController::class, 'verifyDocument'])
        ->name('drivers.onboarding.verify-doc');
    // Serve a document file (bypasses storage symlink requirement)
    Route::get('/document/{docId}/view', [DriverOnboardingController::class, 'viewDocument'])
        ->name('drivers.onboarding.view-doc');
    // All drivers with advanced filters
    Route::get('/all', [DriverOnboardingController::class, 'allDrivers'])
        ->name('drivers.onboarding.all');
});

/*////////////////////// Users Controller Start   */
Route::prefix('admin')->middleware('auth.admin')->controller(UserManagementController::class)->group(function () {
    Route::get('user-management', 'index')->name('users.index');
    Route::get('get-credential', 'getUsers')->name('users.get');
    Route::get('create/user', 'create')->name('users.create')->middleware('can:user management.create');
    Route::post('create/user', 'store')->name('users.store');
    Route::get('edit/user/{id}', 'edit')->name('users.edit')->middleware('can:user management.update');
    Route::get('user/{id}', 'show')->name('users.show');
    Route::put('update/user/{id}', 'update')->name('users.update');
    Route::delete('user/{id}', 'destroy')->name('users.destroy')->middleware('can:user management.delete');
    Route::post('user-management/bulk-destroy', 'bulkDestroy')
        ->name('users.bulk-destroy')
        ->middleware('can:user management.delete');
    Route::get('users/details/{id}', 'show')->name('users.details');
});

/*////////////////////// Users Type Controller End   */

///////////////////////////Role Controller///////////////////
Route::middleware(['auth.admin'])->prefix('admin')->controller(AdminRoleController::class)->group(function () {
    Route::get('roles', 'index')->name('roles.index');
    Route::get('roles/create', 'create')->name('roles.create');
    Route::post('roles', 'store')->name('roles.store');
    Route::get('roles/{role}/edit', 'edit')->name('roles.edit');
    Route::put('roles/{role}', 'update')->name('roles.update');
    Route::delete('roles/{role}', 'destroy')->name('roles.destroy');
});

//////////////////////end////////////////////////////////////////////////

///////////////////////Module Controller/////////////////////////////
Route::prefix('admin/module')
    ->middleware('auth.admin')
    ->controller(ModuleController::class)
    ->group(function () {
        Route::get('/', 'index')->name('modules.index');
        Route::get('create', 'create')->name('modules.create');
        Route::post('store', 'store')->name('modules.store');
        Route::get('show/{id}', 'show')->name('modules.show');
        Route::get('{role}/edit', 'edit')->name('modules.edit');
        Route::put('{role}', 'update')->name('modules.update');
        Route::delete('{role}', 'destroy')->name('modules.destroy');
        Route::get('get-modules/{roleId}', 'getModules')->name('modules.get');
    });
////////////////////////////end//////////////////////////////////////////////////

// Permission routes
Route::middleware('auth')->group(function () {
    Route::get('admin/roles/{id}/permissions', [PermissionController::class, 'getPermissions'])->name('roles.permissions');
    Route::put('admin/roles/{role}/permissions', [PermissionController::class, 'assignPermissions'])->name('roles.permissions.assign');
    Route::delete('admin/roles/{role}/permissions/{permission}', [PermissionController::class, 'removePermission']);
    Route::get('admin/get-permissions/{moduleId}', [PermissionController::class, 'getPermission'])->name('permissions.get');
    Route::post('admin/save-permissions/{id}', [PermissionController::class, 'savePermissions'])->name('permissions.save');
    Route::get('admin/permissions', [PermissionController::class, 'getPermission']);
    Route::post('admin/roles/{role}/permissions', [PermissionController::class, 'assignPermission']);
    Route::get('admin/permissions/{role}', [PermissionController::class, 'getPermissions'])->name('permissions.fetch');
});

// Credential routes
Route::middleware(['auth.admin'])
    ->prefix('admin/driver/credential')
    ->controller(CredentialController::class)
    ->group(function () {
        Route::get('/create/{driver}', 'create')->name('credentials.create');
        Route::get('/get-credential', 'getCredentials')->name('credentials.get');
        Route::get('/delete/{id}', 'delete')->name('credentials.delete');
        Route::post('/store/{driver}', 'store')->name('credentials.store');
        Route::get('/{managedriver}/edit', 'edit')->name('credentials.edit');
        Route::put('/{managedriver}', 'update')->name('credentials.update');
        Route::delete('/{managedriver}', 'destroy')->name('credentials.destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->name('credentials.bulk-destroy');
        Route::get('/{id?}', 'index')->name('credentials.index');
    });
Route::middleware(['auth.admin'])->prefix('admin')->controller(DriverPayRollController::class)
    ->group(function () {

        Route::get('payroll', 'index')->name('payroll.index');
        Route::post('get/payroll', 'getData')->name('payroll-data.index');
        // Driver Payroll Routes
        Route::get('/driver-payroll/{driver}/pdf', 'downloadPDF')->name('driver-payroll.pdf');
        Route::get('/driver-payroll/{driver}/details', 'viewDetails')->name('driver-payroll.details');
    });
Route::get('/file/preview', [CredentialController::class, 'preview'])->name('file.preview');

// Vehicle Type routes
Route::middleware(['auth.admin'])
    ->prefix('admin/vehicle/type')
    ->controller(VehicleTypeController::class)
    ->group(function () {
        Route::get('/create', 'create')->middleware('can:vehicle type.create')->name('vehiclestype.create');
        Route::get('/get-credential', 'getVehicleTypes')->name('vehiclestype.get');
        Route::get('/delete/{id}', 'delete')->name('vehiclestype.delete');
        Route::post('/store', 'store')->name('vehiclestype.store');
        Route::get('/{vehicle}/edit', 'edit')->middleware('can:vehicle type.update')->name('vehiclestype.edit');
        Route::put('/{vehicle}', 'update')->name('vehiclestype.update');
        Route::delete('/{vehicle}', 'destroy')->middleware('can:vehicle type.delete')->name('vehiclestype.destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->middleware('can: vehicle type.delete')->name('vehiclestype.bulk-destroy');
        Route::get('', 'index')->name('vehiclestype.index');
        Route::get('/vehiclestype/details/{id}', [VehicleTypeController::class, 'show'])->name('vehiclestype.details');
    });

// Vehicles routes
Route::middleware(['auth.admin'])
    ->prefix('admin/vehicles')
    ->controller(VehiclesController::class)
    ->group(function () {
        Route::get('/create', 'create')
            ->middleware('can:vehicles.create')
            ->name('vehicles.create');
        Route::get('/get-credential', 'getVehicles')->name('vehicles.get');
        Route::get('/delete/{id}', 'delete')->name('vehicles.delete');
        Route::post('/store', 'store')->name('vehicles.store');
        Route::get('/{vehicle}/edit', 'edit')->middleware('can:vehicles.update')->name('vehicles.edit');
        Route::put('/{vehicle}', 'update')->name('vehicles.update');
        Route::delete('/{vehicle}', 'destroy')->middleware('can:vehicles.delete')->name('vehicles.destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->middleware('can:vehicles.delete')->name('vehicles.bulk-destroy');
        Route::get('', 'index')->name('vehicles.index');
        Route::get('/vehicles/details/{id}', [VehiclesController::class, 'show'])->name('vehicles.details');
    });

Route::middleware(['auth.admin'])->prefix('admin')->controller(MaintenanceTypeController::class)->group(function () {
    Route::get('/vehicle/maintenance/type', 'index')->name('maintenance_type.index');
    Route::get('/vehicle/maintenance/type/create', 'create')->middleware('can:maintenance type.create')->name('maintenance_type.create');
    Route::post('/vehicle/maintenance/type/create', 'store')->name('maintenance_type.store');
    Route::get('/get/vehicle/maintenance/type', 'getUsers')->name('maintenance_type.get');
    Route::get('vehicle/maintenance/type/{id}/edit', 'edit')->middleware('can:maintenance type.update')->name('maintenance_type.edit');
    Route::put('vehicle/maintenance/type/{id}', 'update')->middleware('can:maintenance type.update')->name('maintenance_type.update');
    Route::get('vehicle/maintenance/type/details/{id}', [MaintenanceTypeController::class, 'show'])->name('maintenance_type.details');
    Route::post('vehicle/maintenance/type/bulk-destroy', 'bulkDestroy')
        ->middleware('can:vehicles.delete')
        ->name('maintenance_type.bulk-destroy');
    Route::delete('vehicle/maintenance/type/{id}', 'destroy')->name('maintenance_type.destroy');
});
Route::middleware(['auth.admin'])->prefix('admin')->controller(MaintenanceController::class)->group(function () {
    Route::get('/vehicle/maintenance', 'index')->name('maintenance.index');
    Route::get('/vehicle/maintenance/create', 'create')->middleware('can:maintenance.create')->name('maintenance.create');
    Route::post('/vehicle/maintenance/create', 'store')->middleware('can:maintenance.create')->name('maintenance.store');
    Route::get('/get/vehicle/maintenance', 'getUsers')->name('maintenance.get');
    Route::get('vehicle/maintenance/{id}/edit', 'edit')->middleware('can:maintenance.update')->name('maintenance.edit');
    Route::put('vehicle/maintenance/{id}', 'update')->middleware('can:maintenance.update')->name('maintenance.update');
    Route::get('vehicle/maintenance/details/{id}', [MaintenanceController::class, 'show'])->name('maintenance.details');
    Route::post('/bulk-destroy', 'bulkDestroy')->middleware('can:maintenance.delete')->name('maintenance.bulk-destroy');
    Route::post('/maintenance/disable/{id}', [MaintenanceController::class, 'disable'])->name('maintenance.disable');

    Route::delete('vehicle/maintenance/{id}', 'destroy')->name('maintenance.destroy');
});
// Customer routes
Route::middleware(['auth.admin'])
    ->prefix('admin/customers')
    ->controller(CustomerController::class)
    ->group(function () {
        Route::get('/create', 'create')->middleware('can:customers.create')->name('customers.create');
        Route::get('/get-credential', 'getCustomers')->name('customers.get');
        Route::post('/store', 'store')->name('customers.store');
        Route::get('/{vehicle}/edit', 'edit')->middleware('can:customers.update')->name('customers.edit');
        Route::put('/{vehicle}', 'update')->name('customers.update');
        Route::delete('/{vehicle}', 'destroy')->middleware('can:customers.delete')->name('customers.destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->middleware('can:customers.delete')->name('customers.bulk-destroy');
        Route::post('/{id}/toggle-status', 'toggleStatus')->name('customers.toggle-status');
        Route::get('/customers/details/{id}', [CustomerController::class, 'show'])->name('customers.details');
        Route::get('', 'index')->name('customers.index');
    });
Route::get('admin/invoice', function () {
    return view('invoice.template');
})->middleware('auth.admin')->name('invoice');

Route::get('/dashboard/top-states',    [DashboardController::class, 'getTopStatesAjax'])   ->name('dashboard.topStates.data');
Route::get('/dashboard/top-customers', [DashboardController::class, 'getTopCustomersAjax'])->name('dashboard.topCustomers.data');

Route::middleware(['auth.admin'])
    ->prefix('admin/shipment')
    ->controller(ShipmentController::class)
    ->group(function () {
        Route::get('/', 'index')->name('shipments.index');
        Route::get('/shipments/invoive', 'shipmentsInvoice')->name('shipment-invoice.index');
        Route::get('/create', 'create')->middleware('can:shipments.create')->name('shipments.create');

        Route::post('/store', 'store')->name('shipments.store');
        Route::get('/{shipment}/edit', 'edit')->middleware('can:shipments.update')->name('shipments.edit');
        Route::put('/{shipment}', 'update')->name('shipments.update');
        Route::delete('/{shipment}', 'destroy')->middleware('can:shipments.delete')->name('shipments.destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->middleware('can:shipments.delete')->name('shipments.bulk-destroy');

        // Main shipments page data
        Route::get('/get-credential', 'getShipments')->name('shipments.get');

        Route::get('/counts', 'getCounts')->name('shipments.counts');
        Route::get('/details/{id}', 'show')->name('shipments.details');

        // Report routes
        Route::get('/report', 'shipmentReport')->name('shipments.report');
        Route::get('/report/data', 'shipmentReportData')->name('shipments.report.data');
        Route::get('/report/download', 'downloadReport')->name('shipments.report.download');

        // Completed shipments
        Route::get('/completed', 'completedShipments')->name('shipments.completed');
        Route::get('/completed/get-credential', 'getCompletedShipments')->name('shipments.completed.get');
    });

// Shipment Documents Dashboard (kept for old URLs)
Route::middleware(['auth.admin'])
    ->get('/admin/shipment/documents', function () {
        return redirect()->route('shipments.completed');
    })->name('shipments.documents');

Route::middleware(['auth.admin'])
    ->prefix('admin/shipments/invoice')
    ->controller(ShipmentInvoiceController::class)
    ->group(function () {
        // Invoice report page
        Route::get('/', 'index')->name('shipments-invoice.index');

        // Get invoice data (AJAX)
        Route::post('/data', 'getInvoiceData')->name('shipments-invoice.data');

        // Generate PDF
        Route::post('/generate', 'generatePDF')->name('shipments-invoice.generate');

        // Preview single invoice
        Route::get('/{shipment}/preview', 'previewInvoice')->name('shipments-invoice.preview');

        // Download single invoice
        Route::get('/{shipment}/download', 'generateSingleInvoice')->name('shipments-invoice.download');
    });


// Dispatch routes (protected with auth)
Route::middleware(['auth.admin'])->prefix('admin')->controller(DispatchController::class)->group(function () {
    Route::get('/dispatch', 'index')->name('dispatch.index');
    Route::get('/tomorrow-dispatch', 'tomorrowDispatch')->name('dispatch.tomorrow');
    Route::get('/dispatch/ai-board', 'aiBoard')->name('dispatch.ai-board');
    Route::delete('/dispatch/{id}', 'destroy');
    Route::put('/shipments/{shipment}/assign-vehicle', 'assignVehicle')->name('shipments.assign-vehicle');
    Route::get('/current-dispatch/details/{id}', [DispatchController::class, 'show'])->name('dispatch.details');
    Route::get('/dispatch/{id}/map-data', 'getMapData')->name('dispatch.map-data'); // Add this line

});


// API routes for dispatch (these might need to be moved to api.php if they're truly API routes)
Route::group(['middleware' => 'auth.admin'], function () {
    Route::get('admin/shipments', [DispatchController::class, 'getShipments']);
    Route::get('/shipments/tomorrow/dispatch', [DispatchController::class, 'getTomorrowShipments']);
    Route::get('/shipments/counts', [DispatchController::class, 'getCounts']);
    Route::get('/shipments/tomorrow/counts', [DispatchController::class, 'getTomorrowShipmentCounts']);
    Route::put('/shipments/{id}/status', [DispatchController::class, 'updateStatus']);
    Route::put('/shipments/{id}/cancel', [DispatchController::class, 'cancelShipment']);
    Route::post('/shipments/bulk-update-status', [DispatchController::class, 'bulkUpdateStatus']);
});

// CMS routes
Route::middleware(['auth.admin'])
    ->prefix('cms/type')
    ->controller(CmsController::class)
    ->group(function () {
        Route::get('/create', 'create')->middleware('can:vehicle type.create')->name('cms.create');
        Route::get('/get-credential', 'getCms')->name('cms.get');
        Route::get('/delete/{id}', 'delete')->name('cms.delete');
        Route::post('/store', 'store')->name('cms.store');
        Route::get('/{vehicle}/edit', 'edit')->name('cms.edit');
        Route::put('/{vehicle}', 'update')->name('cms.update');
        Route::delete('/{vehicle}', 'destroy')->middleware('can:cms pages.delete')->name('cms.destroy');
        Route::post('/bulk-destroy', 'bulkDestroy')->middleware('can:cms pages.delete')->name('cms.bulk-destroy');
        Route::get('', 'index')->name('cms.index');
        Route::get('/details/{id}', [CmsController::class, 'show'])->name('cms.details');
    });

use App\Services\BrevoMailService;

Route::get('admin/test-brevo-api', function () {
    try {
        $brevo = new BrevoMailService();

        $brevo->send(
            'your-test-email@example.com', // Replace with your email
            'Test Email via Brevo API',
            '<h1>Success! 🎉</h1><p>Your email is working via Brevo API (HTTPS).</p>',
            'Success! Your email is working via Brevo API.'
        );

        return '✅ Email sent successfully! Check your inbox.';
    } catch (\Exception $e) {
        return '❌ Error: ' . $e->getMessage();
    }
});

// Vehicle Assignment routes
Route::middleware(['auth.admin'])
    ->prefix('admin/vehicle/assignment')
    ->controller(VehicleAssignmentController::class)
    ->group(function () {
        Route::get('/', 'index')->name('vehicleassignment.index');
        Route::post('assign', 'store')->name('vehicleassignment.store');
        Route::get('all-assignments', 'getAllAssignments')->name('vehicleassignment.all');
        Route::put('assignments/{assignmentId?}', 'updateAssignment')->name('vehicleassignment.update');
        Route::delete('assignments/{assignmentId}', 'removeAssignment')->name('vehicleassignment.remove');
    });

Route::middleware('auth.admin')->prefix('admin')->group(function () {
    Route::get('/shipments/{id}/comments', [ShipmentController::class, 'getComments']);
    Route::post('/shipments/{id}/comments', [ShipmentController::class, 'storeComment']);
});
Route::get('/admin/calendar/view', [ShipmentController::class, 'calendar'])->name('shipments.calendar');
Route::post('/admin/shipments/calculate-distance', [ShipmentController::class, 'calculateDistance'])->name('shipments.calculate-distance');

Route::get('/admin/calendar/data', [ShipmentController::class, 'getCalendarData'])->name('shipments.calendar.data');
Route::get('/admin/calendar/customers', [ShipmentController::class, 'getCustomersForFilter'])->name('shipments.calendar.customers');
Route::get('/admin/calendar/counts', [ShipmentController::class, 'getShipmentCounts'])->name('shipments.calendar.counts');
Route::get('/admin/calendar/shipment/{id}', [ShipmentController::class, 'getShipmentForCalendar'])->name('shipments.calendar.shipment');




Broadcast::routes();

Route::middleware(['auth'])->group(function () {
    // Notification routes

    // Get latest notification ID for a shipment

    // Shipment routes
    Route::post('admin/shipments/{id}/update-status', [ShipmentController::class, 'updateStatus'])
        ->name('shipments.update-status');





    // Shipment notification detail
    Route::get('admin/shipments/{id}/notification/{notificationId?}', [ShipmentNotificationController::class, 'detail'])
        ->name('shipmentsNotification.detail');
    Route::get('admin/notifications', [ShipmentNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [ShipmentNotificationController::class, 'markAllAsRead'])
        ->name('notifications.markAllRead');





    // ✅ NEW: Add route to mark notification as read
    Route::post('admin/notifications/{notificationId}/mark-read', function ($notificationId) {
        $notification = Auth::user()->notifications()->where('id', $notificationId)->first();

        if ($notification && is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $redirect = request('redirect', '/admin/dashboard');
        return redirect($redirect);
    })->name('notifications.mark-read');
});

Route::get('admin/invoice', function () {
    return view('invoice.index');
})->middleware('auth.admin')->name('invoice');

Route::get('/admin/unread-notifications', function () {
    return response()->json(Auth::user()->unreadNotifications);
})->name('notifications.unread')->middleware('auth.admin');


Route::get('/dashboard/activity-data', [DashboardController::class, 'getActivityDataAjax'])->name('dashboard.activity.data');
Route::get('/dashboard/orders-data', [DashboardController::class, 'getOrdersDataAjax'])->name('dashboard.orders.data');
Route::get('/dashboard/loadboard-loads',    [DashboardController::class, 'getLoadboardLoads'])->name('dashboard.loadboard.loads');
Route::get('/dashboard/loadboard-detail',  [DashboardController::class, 'getLoadboardDetail'])->name('dashboard.loadboard.detail');
Route::get('/dashboard/rate-intelligence', [DashboardController::class, 'getRateIntelligence'])->name('dashboard.rate-intelligence');


Route::middleware(['auth.admin'])->prefix('admin')->group(function () {
 
    // View
    Route::get('/dispatch/ai-board', [AiBoardController::class, 'index'])
        ->name('dispatch.ai-board');
 
    // API endpoints used by the blade JS
    Route::prefix('api/ai-board')->group(function () {
        Route::get('/open-loads',       [AiBoardController::class, 'openLoads']);
        Route::get('/eld-snapshot',     [AiBoardController::class, 'eldSnapshot']);
        Route::post('/rank',            [AiBoardController::class, 'rank']);
        Route::post('/assign',          [AiBoardController::class, 'assign']);
        Route::post('/assign-external', [AiBoardController::class, 'assignExternal']);
    });
 
});



// Your existing routes...

// routes/web.php
Route::get('/admin/poll-notifications', [ShipmentNotificationController::class, 'pollNotifications'])
    ->name('notifications.poll')
    ->middleware('auth.admin');
// routes/web.php
Route::get('admin/notifications/fetch', [ShipmentNotificationController::class, 'fetch'])->name('notifications.fetch');
// React SPA catch-all route - MUST BE LAST
Route::get('/{any}', function () {
    return view('react-app');
})->where('any', '^(?!api|sanctum|admin).*$');

require __DIR__ . '/auth.php';
