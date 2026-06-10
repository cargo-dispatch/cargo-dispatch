<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Remarks\Remarks;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use OwenIt\Auditing\Auditable;
use Spatie\Permission\Models\Role;
use Laravel\Sanctum\HasApiTokens; // Add this import

use GuzzleHttp\Client;

class User extends Authenticatable implements AuditableContract
{
    use HasFactory, HasApiTokens,Notifiable, HasRoles, Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'phoneNumber',
        'role_id',
        'status',
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'connectycube_id',
        'connectycube_login',
        'connectycube_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role() 
    {
        return $this->belongsTo(Role::class);
    }

    public function remarks() 
    {
        return $this->morphMany(Remarks::class, 'commenter');
    }

    /**
     * Sync user with ConnectyCube - creates or updates user
     */

//     private function getAppLevelToken($client)
// {
//     $timestamp = time();
//     $nonce = rand(1, 1000000);
    
//     $appId = (int) config('services.connectycube.app_id');
//     $timestamp = (int) $timestamp;
//     $nonce = (int) $nonce;
    
//     $sessionData = [
//         'application_id' => $appId,
//         'auth_key' => config('services.connectycube.auth_key'),
//         'timestamp' => $timestamp,
//         'nonce' => $nonce,
//         'signature' => $this->generateSignature($timestamp, $nonce)
//     ];
    
//     $response = $client->post('https://api.connectycube.com/session', [
//         'headers' => [
//             'Content-Type' => 'application/json',
//             'CB-API-Version' => '1.1'
//         ],
//         'json' => $sessionData
//     ]);
    
//     $result = json_decode($response->getBody(), true);
    
//     if ($response->getStatusCode() === 201 && isset($result['session']['token'])) {
//         return $result['session']['token'];
//     }
    
//     throw new \Exception('Failed to get app level token: ' . json_encode($result));
// }
// public function syncConnectyCubeUser()
// {
//     try {
//         $client = new Client();
//         $appToken = $this->getAppLevelToken($client);
        
//         $userData = [
//             'user' => [
//                 'login' => 'admin_'.$this->id.'_'.time().'@yourdomain.com',
//                 'password' => 'AdminPass_'.bin2hex(random_bytes(6)),
//                 'full_name' => $this->first_name.' '.$this->last_name,
//                 'email' => $this->email ?: 'admin_'.$this->id.'@yourdomain.com',
//                 'custom_data' => json_encode([
//                     'user_id' => $this->id,
//                     'type' => 'admin',
//                     'role_id' => $this->role_id
//                 ])
//             ]
//         ];
        
//         $response = $client->post('https://api.connectycube.com/users', [
//             'headers' => [
//                 'CB-Token' => $appToken,
//                 'Content-Type' => 'application/json'
//             ],
//             'json' => $userData
//         ]);
        
//         $statusCode = $response->getStatusCode();
//         $result = json_decode($response->getBody(), true);
        
//         if ($statusCode === 201 && isset($result['user']['id'])) {
//             $this->update([
//                 'connectycube_id' => $result['user']['id'],
//                 'connectycube_login' => $userData['user']['login'],
//                 'connectycube_password' => $userData['user']['password']
//             ]);
            
//             Log::info("Admin user synced with ConnectyCube", [
//                 'user_id' => $this->id,
//                 'connectycube_id' => $result['user']['id']
//             ]);
            
//             return true;
//         }
        
//         throw new \Exception('Failed to create ConnectyCube user: ' . json_encode($result));
        
//     } catch (\Exception $e) {
//         Log::error("Admin user ConnectyCube sync failed", [
//             'user_id' => $this->id,
//             'error' => $e->getMessage()
//         ]);
//         throw $e;
//     }
// }
   public function syncConnectyCubeUser()
    {
        try {
            $client = new Client();
            
            // Get application-level token
            $appToken = $this->getAppLevelToken($client);
            
            // Generate unique credentials for admin users
            $timestamp = time();
            $uniqueLogin = 'admin_' . $this->id . '_' . $timestamp . '@yourdomain.com';
            $securePassword = 'AdminPass_' . bin2hex(random_bytes(8));
            
            // Prepare user data
            $userData = [
                'user' => [
                    'login' => $uniqueLogin,
                    'password' => $securePassword,
                    'full_name' => trim($this->first_name . ' ' . $this->last_name),
                    'email' => $this->email ?: $uniqueLogin,
                    'custom_data' => json_encode([
                        'user_id' => $this->id,
                        'type' => 'admin',
                        'role_id' => $this->role_id,
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
                // Update user record with ConnectyCube details
                $this->update([
                    'connectycube_id' => $result['user']['id'],
                    'connectycube_login' => $uniqueLogin,
                    'connectycube_password' => $securePassword
                ]);
                
                Log::info("Admin user synced with ConnectyCube", [
                    'user_id' => $this->id,
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
                        'user_id' => $this->id,
                        'attempted_login' => $uniqueLogin,
                        'error' => $errorMessage
                    ]);
                    
                    // Try with a different login
                    $uniqueLogin = 'admin_' . $this->id . '_' . time() . '_' . rand(100, 999) . '@yourdomain.com';
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
            Log::error("User ConnectyCube sync failed", [
                'user_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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
     * Create new ConnectyCube user
     */
    private function createConnectyCubeUser()
    {
        // Generate a more secure password
        $password = 'user_' . $this->id . '_' . bin2hex(random_bytes(8));
        
        // Use email as login for admins
        $login = $this->email;
        
        $userData = [
            'login' => $login,
            'password' => $password,
            'full_name' => trim($this->first_name . ' ' . $this->last_name),
            'email' => $this->email,
            'phone' => $this->phoneNumber ?? '',
            'website' => '', // Sometimes required by ConnectyCube
            'custom_data' => json_encode([
                'user_id' => $this->id,
                'phone' => $this->phoneNumber ?? '',
                'type' => 'admin',
                'role_id' => $this->role_id,
                'created_at' => now()->toISOString()
            ])
        ];

        $client = new Client([
            'timeout' => 30,
            'http_errors' => false // Don't throw exceptions on HTTP errors
        ]);
        
        try {
            // Get admin session token
            $sessionToken = $this->getAdminSessionToken($client);
            
            // Create user
            $response = $client->post('https://api.connectycube.com/users', [
                'headers' => [
                    'CB-Token' => $sessionToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['user' => $userData]
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);
            
            Log::info('ConnectyCube create user response', [
                'user_id' => $this->id,
                'status_code' => $statusCode,
                'response' => $result
            ]);
            
            if ($statusCode === 201 && isset($result['user'])) {
                // Update user with ConnectyCube details
                $this->update([
                    'connectycube_id' => $result['user']['id'],
                    'connectycube_login' => $login,
                    'connectycube_password' => $password
                ]);
                
                Log::info('ConnectyCube user created for user ' . $this->id, [
                    'connectycube_id' => $result['user']['id'],
                    'login' => $login
                ]);
                
                return $result;
            }
            
            // Handle specific error cases
            if (isset($result['errors'])) {
                $errorMessage = is_array($result['errors']) ? implode(', ', $result['errors']) : $result['errors'];
                throw new \Exception('ConnectyCube API error: ' . $errorMessage);
            }
            
            throw new \Exception('Failed to create ConnectyCube user. Status: ' . $statusCode . ', Response: ' . json_encode($result));
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('ConnectyCube HTTP request failed', [
                'user_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('ConnectyCube request failed: ' . $e->getMessage());
        }
    }

    /**
     * Update existing ConnectyCube user
     */
    private function updateConnectyCubeUser()
    {
        $userData = [
            'full_name' => trim($this->first_name . ' ' . $this->last_name),
            'email' => $this->email,
            'phone' => $this->phoneNumber ?? '',
            'custom_data' => json_encode([
                'user_id' => $this->id,
                'phone' => $this->phoneNumber ?? '',
                'type' => 'admin',
                'role_id' => $this->role_id,
                'updated_at' => now()->toISOString()
            ])
        ];

        $client = new Client([
            'timeout' => 30,
            'http_errors' => false
        ]);
        
        try {
            $sessionToken = $this->getAdminSessionToken($client);
            
            $response = $client->put('https://api.connectycube.com/users/' . $this->connectycube_id, [
                'headers' => [
                    'CB-Token' => $sessionToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['user' => $userData]
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);
            
            Log::info('ConnectyCube update user response', [
                'user_id' => $this->id,
                'connectycube_id' => $this->connectycube_id,
                'status_code' => $statusCode,
                'response' => $result
            ]);

            if ($statusCode === 200) {
                return $result;
            }
            
            throw new \Exception('Failed to update ConnectyCube user. Status: ' . $statusCode);
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('ConnectyCube update request failed', [
                'user_id' => $this->id,
                'connectycube_id' => $this->connectycube_id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('ConnectyCube update failed: ' . $e->getMessage());
        }
    }

    /**
     * Get admin session token for ConnectyCube API calls
     */
    private function getAdminSessionToken($client)
    {
        $timestamp = time();
        $nonce = rand(1, 1000000);
        
        $sessionData = [
            'application_id' => config('services.connectycube.app_id'),
            'auth_key' => config('services.connectycube.auth_key'),
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'signature' => $this->generateSignature($timestamp, $nonce),
            'user' => [
                'login' => config('services.connectycube.admin_login'),
                'password' => config('services.connectycube.admin_password')
            ]
        ];

        try {
            $response = $client->post('https://api.connectycube.com/session', [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $sessionData
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);
            
            if ($statusCode !== 201 || !isset($result['session']['token'])) {
                Log::error('Failed to get ConnectyCube session token', [
                    'status_code' => $statusCode,
                    'response' => $result
                ]);
                throw new \Exception('Failed to get ConnectyCube session token');
            }
            
            return $result['session']['token'];
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('ConnectyCube session request failed', [
                'error' => $e->getMessage()
            ]);
            throw new \Exception('ConnectyCube session failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate signature for ConnectyCube API
     */
//     private function generateSignature($timestamp, $nonce)
// {
//     $appId = (int) config('services.connectycube.app_id');
//     $authKey = (string) config('services.connectycube.auth_key');
//     $authSecret = (string) config('services.connectycube.auth_secret');
//     $timestamp = (int) $timestamp;
//     $nonce = (int) $nonce;
    
//     $params = [
//         'application_id' => $appId,
//         'auth_key' => $authKey,
//         'timestamp' => $timestamp,
//         'nonce' => $nonce,
//     ];
    
//     ksort($params);
//     $string = '';
//     foreach ($params as $key => $value) {
//         $string .= $key . '=' . $value . '&';
//     }
//     $string = rtrim($string, '&');
    
//     return hash_hmac('sha1', $string, $authSecret);
// }
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

    /**
     * Get ConnectyCube formatted data for chat widget
     */
    // public function getConnectyCubeData()
    // {
    //     return [
    //         'id' => (int)$this->connectycube_id,
    //         'name' => trim($this->first_name . ' ' . $this->last_name),
    //         'login' => $this->connectycube_login,
    //         'email' => $this->email,
    //         'phone' => $this->phoneNumber ?? '',
    //         'full_name' => trim($this->first_name . ' ' . $this->last_name),
    //         'role_id' => $this->role_id,
    //         'type' => 'admin'
    //     ];
    // }

     public function getConnectyCubeData()
    {
        return [
            'id' => 'admin_' . $this->id,
            'connectycube_id' => (int)$this->connectycube_id,
            'name' => trim($this->first_name . ' ' . $this->last_name),
            'login' => $this->connectycube_login,
            'email' => $this->email,
            'role' => 'admin',
            'full_name' => trim($this->first_name . ' ' . $this->last_name)
        ];
    }
    public function hasConnectyCubeCredentials(): bool
    {
        return !empty($this->connectycube_id) && 
               !empty($this->connectycube_login) && 
               !empty($this->connectycube_password);
    }
      public function generateConnectyCubeCredentials(): void
    {
        if ($this->hasConnectyCubeCredentials()) {
            return; // Already has credentials
        }

        $this->update([
            'connectycube_id' => 'user_' . $this->id . '_' . time(),
            'connectycube_login' => $this->email,
            'connectycube_password' => 'cc_' . $this->id . '_' . \Illuminate\Support\Str::random(12)
        ]);
    }
      public function getFullNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        
        return $this->name ?? $this->email;
    }

    /**
     * Verify user exists in ConnectyCube
     */
//    public function verifyConnectyCubeUser()
// {
//     if (!$this->connectycube_id) {
//         return false;
//     }
    
//     try {
//         $client = new Client();
//         $appToken = $this->getAppLevelToken($client);
        
//         $response = $client->get("https://api.connectycube.com/users/{$this->connectycube_id}", [
//             'headers' => [
//                 'CB-Token' => $appToken,
//                 'Content-Type' => 'application/json'
//             ]
//         ]);
        
//         return $response->getStatusCode() === 200;
        
//     } catch (\Exception $e) {
//         Log::error("Driver ConnectyCube verification failed", [
//             'driver_id' => $this->id,
//             'connectycube_id' => $this->connectycube_id,
//             'error' => $e->getMessage()
//         ]);
//         return false;
//     }
// }
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
                
                if (isset($result['user'])) {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("User ConnectyCube verification failed", [
                'user_id' => $this->id,
                'connectycube_id' => $this->connectycube_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }



    /**
     * Static method to sync all admin users with ConnectyCube
     */
    public static function syncAllWithConnectyCube()
    {
        $users = self::where('role_id', 23)->whereNull('connectycube_id')->get();
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($users as $user) {
            try {
                $user->syncConnectyCubeUser();
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "User {$user->id}: " . $e->getMessage();
            }
        }
        
        return $results;
    }

    /**
     * Get all admin users for chat with their ConnectyCube data
     */
    public static function getChatUsers()
    {
        return self::where('role_id', 23)
            ->whereNotNull('connectycube_id')
            ->get()
            ->map(function ($user) {
                return $user->getConnectyCubeData();
            });
    }
}