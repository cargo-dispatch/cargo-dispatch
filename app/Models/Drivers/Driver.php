<?php

namespace App\Models\Drivers;

use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

use App\Models\DriverType\DriverType;
use App\Models\Remarks\Remarks;
use App\Models\Shipments\Shipment;
use App\Models\VehicleAssignment\VehicleAssignment;
use App\Models\Vehicles\Vehicle;

class Driver extends Authenticatable implements AuditableContract, CanResetPasswordContract
{
    use Auditable, SoftDeletes, HasApiTokens, Notifiable, CanResetPassword;

    protected $fillable = [
        // Basic
        'firstname', 'lastname', 'phoneno', 'emergencycontactno', 'email',
        'drivertype', 'password', 'title', 'expiry_date', 'files', 'incentive',
        'licensetype', 'licenseno',
        // Location / ELD
        'current_latitude', 'current_longitude', 'last_location_update',
        'eld_driver_id', 'current_duty_status',
        'hos_drive_remaining_minutes', 'hos_on_duty_remaining_minutes', 'hos_cycle_remaining_minutes',
        // Push notifications
        'expo_push_token', 'last_push_token_update',
        // Chat
        'connectycube_id', 'connectycube_login', 'connectycube_password',
        // Onboarding & status
        'status', 'onboarding_status', 'invited_at', 'invited_by',
        'approved_at', 'approved_by', 'rejection_reason',
        // Personal
        'date_of_birth', 'ssn_last4', 'address', 'city', 'state', 'zip', 'profile_photo',
        // CDL
        'cdl_number', 'cdl_state', 'cdl_class', 'cdl_expiry_date', 'cdl_endorsements', 'cdl_restriction',
        // Medical
        'medical_card_expiry', 'drug_test_date', 'drug_test_status', 'mvr_date',
        // Experience & pay
        'years_experience', 'preferred_truck_type_id', 'equipment_types',
        'pay_type', 'pay_rate',
        // Vehicle assignment
        'primary_vehicle_id',
    ];

    protected $casts = [
        'cdl_endorsements' => 'array',
        'equipment_types'  => 'array',
        'date_of_birth'    => 'date',
        'cdl_expiry_date'  => 'date',
        'medical_card_expiry' => 'date',
        'drug_test_date'   => 'date',
        'invited_at'       => 'datetime',
        'approved_at'      => 'datetime',
    ];

    protected $table = 'drivers';

    protected $dates = [
        'last_location_update',
    ];

    protected $hidden = [
        'password',
       
    ];

    // Authentication methods
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    // Relationships
    public function drivertype()
    {
        return $this->belongsTo(DriverType::class, 'drivertype', 'id');
    }

    public function vehicleAssignments()
    {
        return $this->hasMany(VehicleAssignment::class, 'driver_id');
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'driver_id');
    }

    public function remarks()
    {
        return $this->morphMany(Remarks::class, 'commenter');
    }

    public function documents()
    {
        return $this->hasMany(\App\Models\Drivers\DriverDocument::class);
    }

    public function invitations()
    {
        return $this->hasMany(\App\Models\Drivers\DriverInvitation::class);
    }

    public function preferredVehicleType()
    {
        return $this->belongsTo(\App\Models\VehicleType\VehicleType::class, 'preferred_truck_type_id');
    }

    public function primaryVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'primary_vehicle_id');
    }

    public function isCdlExpiringSoon(): bool
    {
        return $this->cdl_expiry_date && $this->cdl_expiry_date->diffInDays(now()) <= 60;
    }

    public function isMedicalExpiringSoon(): bool
    {
        return $this->medical_card_expiry && $this->medical_card_expiry->diffInDays(now()) <= 60;
    }
public function syncConnectyCubeUser()
{
    try {
        $client = new Client();
        
        // Get application-level token
        $appToken = $this->getAppLevelToken($client);
        
        // Generate unique credentials to avoid conflicts
        $timestamp = time();
        $uniqueLogin = 'driver_' . $this->id . '_' . $timestamp . '@yourdomain.com';
        $securePassword = 'DriverPass_' . bin2hex(random_bytes(8));
        
        // Prepare user data
        $userData = [
            'user' => [
                'login' => $uniqueLogin,
                'password' => $securePassword,
                'full_name' => trim($this->firstname . ' ' . $this->lastname),
                'email' => $this->email ?: $uniqueLogin, // Use login as fallback
             
                'custom_data' => json_encode([
                    'driver_id' => $this->id,
                    'type' => 'driver',
                
                    'created_at' => now()->toISOString()
                ])
            ]
        ];
        
        // Create user in ConnectyCube
        $response = $client->post('https://api.connectycube.com/users', [
            'headers' => [
                'CB-Token' => $appToken,
                'Content-Type' => 'application/json'
            ],
            'json' => $userData
        ]);
        
        $statusCode = $response->getStatusCode();
        $result = json_decode($response->getBody(), true);
        
        if ($statusCode === 201 && isset($result['user']['id'])) {
            // Update driver record with ConnectyCube details
            $this->update([
                'connectycube_id' => $result['user']['id'],
                'connectycube_login' => $uniqueLogin,
                'connectycube_password' => $securePassword
            ]);
            
            Log::info("Driver synced with ConnectyCube", [
                'driver_id' => $this->id,
                'connectycube_id' => $result['user']['id'],
                'login' => $uniqueLogin
            ]);
            
            return [
                'success' => true,
                'connectycube_id' => $result['user']['id'],
                'login' => $uniqueLogin
            ];
        }
        
        // Handle specific errors
        if (isset($result['errors'])) {
            $errorMessage = is_array($result['errors']) ? implode(', ', $result['errors']) : $result['errors'];
            
            // Check if user already exists with this login
            if (str_contains($errorMessage, 'login') || str_contains($errorMessage, 'taken')) {
                Log::warning("ConnectyCube user creation failed - login conflict", [
                    'driver_id' => $this->id,
                    'attempted_login' => $uniqueLogin,
                    'error' => $errorMessage
                ]);
                
                // Try with a different login
                $uniqueLogin = 'driver_' . $this->id . '_' . time() . '_' . rand(100, 999) . '@yourdomain.com';
                $userData['user']['login'] = $uniqueLogin;
                
                // Retry once with new login
                $retryResponse = $client->post('https://api.connectycube.com/users', [
                    'headers' => [
                        'CB-Token' => $appToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => $userData
                ]);
                
                $retryResult = json_decode($retryResponse->getBody(), true);
                
                if ($retryResponse->getStatusCode() === 201 && isset($retryResult['user']['id'])) {
                    $this->update([
                        'connectycube_id' => $retryResult['user']['id'],
                        'connectycube_login' => $uniqueLogin,
                        'connectycube_password' => $securePassword
                    ]);
                    
                    return [
                        'success' => true,
                        'connectycube_id' => $retryResult['user']['id'],
                        'login' => $uniqueLogin
                    ];
                }
            }
            
            throw new \Exception('ConnectyCube API error: ' . $errorMessage);
        }
        
        throw new \Exception('Failed to create ConnectyCube user: ' . json_encode($result));
        
    } catch (\Exception $e) {
        Log::error("Driver ConnectyCube sync failed", [
            'driver_id' => $this->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

/**
 * Get application-level token for ConnectyCube API calls
 */

   private function generateSignature($timestamp, $nonce)
    {
        $appId = (int) config('services.connectycube.app_id');
        $authKey = (string) config('services.connectycube.auth_key');
        $authSecret = (string) config('services.connectycube.auth_secret');
        $timestamp = (int) $timestamp;
        $nonce = (int) $nonce;
        
        $params = [
            'application_id' => $appId,
            'auth_key' => $authKey,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ];
        
        ksort($params);
        $string = '';
        foreach ($params as $key => $value) {
            $string .= $key . '=' . $value . '&';
        }
        $string = rtrim($string, '&');
        
        return hash_hmac('sha1', $string, $authSecret);
    }
private function getAppLevelToken($client)
{
    $timestamp = time();
    $nonce = rand(1, 1000000);
    
    $appId = (int) config('services.connectycube.app_id');
    $timestamp = (int) $timestamp;
    $nonce = (int) $nonce;
    
    $sessionData = [
        'application_id' => $appId,
        'auth_key' => config('services.connectycube.auth_key'),
        'timestamp' => $timestamp,
        'nonce' => $nonce,
        'signature' => $this->generateSignature($timestamp, $nonce)
    ];
    
    $response = $client->post('https://api.connectycube.com/session', [
        'headers' => [
            'Content-Type' => 'application/json',
            'CB-API-Version' => '1.1'
        ],
        'json' => $sessionData
    ]);
    
    $result = json_decode($response->getBody(), true);
    
    if ($response->getStatusCode() === 201 && isset($result['session']['token'])) {
        return $result['session']['token'];
    }
    
    throw new \Exception('Failed to get app level token: ' . json_encode($result));
}

/**
 * Verify user exists in ConnectyCube
 */
public function verifyConnectyCubeUser()
{
    if (!$this->connectycube_id) {
        return false;
    }
    
    try {
        $client = new Client();
        $appToken = $this->getAppLevelToken($client);
        
        $response = $client->get("https://api.connectycube.com/users/{$this->connectycube_id}", [
            'headers' => [
                'CB-Token' => $appToken,
                'Content-Type' => 'application/json'
            ]
        ]);
        
        if ($response->getStatusCode() === 200) {
            $result = json_decode($response->getBody(), true);
            
            // Verify the user data matches
            if (isset($result['user'])) {
                return true;
            }
        }
        
        return false;
        
    } catch (\Exception $e) {
        Log::error("Driver ConnectyCube verification failed", [
            'driver_id' => $this->id,
            'connectycube_id' => $this->connectycube_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Get ConnectyCube formatted data for chat
 */
public function getConnectyCubeData()
{
    return [
        'id' => 'driver_' . $this->id,
        'connectycube_id' => (int)$this->connectycube_id,
        'name' => trim($this->firstname . ' ' . $this->lastname),
        'login' => $this->connectycube_login,
        'email' => $this->email,
       
        'role' => 'driver',
        'full_name' => trim($this->firstname . ' ' . $this->lastname)
    ];
}
}