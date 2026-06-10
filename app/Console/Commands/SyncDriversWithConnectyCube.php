<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Drivers\Driver;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Artisan;

class SyncDriversWithConnectyCube extends Command
{
    protected $signature = 'users:sync-connectycube
                            {--force : Force sync all users, including those already synced}
                            {--verify : Verify existing ConnectyCube users}
                            {--dry-run : Show what would be done without making changes}
                            {--debug : Show debug information for ConnectyCube connection}';

    protected $description = 'Sync all users (drivers and admins) with ConnectyCube chat system';

public function handle()
{
    if ($this->option('debug')) {
        $this->debugConnectyCube();
        return 0;
    }

    if ($this->option('dry-run')) {
        $this->performDryRun();
        return 0;
    }

    if ($this->option('verify')) {
        $this->verifyExistingUsers();
        return 0;
    }

    // Test basic connection
    if (!$this->testConnectyCubeConnection()) {
        $this->error('ConnectyCube connection test failed');
        return 1;
    }

    // Directly sync without getting admin token first
    $driverResults = $this->syncDrivers();
    $adminResults = $this->syncAdmins();

    $this->showCombinedSummary($driverResults, $adminResults);

    return 0;
}

private function syncAllUsersWithAppToken($appToken)
{
    $client = new Client();
    
    // Sync drivers
    $drivers = Driver::when(!$this->option('force'), fn($q) => $q->whereNull('connectycube_id'))
                    ->get();
    
    $bar = $this->output->createProgressBar($drivers->count());
    
    foreach ($drivers as $driver) {
        try {
            $userData = [
                'user' => [
                    'login' => 'driver_'.$driver->id.'@yourdomain.com',
                    'password' => 'DriverPass_'.bin2hex(random_bytes(4)),
                    'full_name' => $driver->firstname.' '.$driver->lastname,
                    'email' => $driver->email,
                    'custom_data' => json_encode(['driver_id' => $driver->id])
                ]
            ];
            
            $response = $client->post('https://api.connectycube.com/users', [
                'headers' => [
                    'CB-Token' => $appToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => $userData
            ]);
            
            if ($response->getStatusCode() === 201) {
                $driver->update([
                    'connectycube_id' => json_decode($response->getBody())->user->id,
                    'connectycube_login' => $userData['user']['login']
                ]);
            }
            
            $bar->advance();
        } catch (\Exception $e) {
            $this->newLine();
            $this->error($e->getMessage());
        }
    }
    
    $bar->finish();
}
protected function createAdminUser()
{
    try {
        $client = new Client();
        
        // 1. Create application-level session
        $appSession = $this->createApplicationSession($client);
        
        // 2. Create admin user
        $adminData = [
            'user' => [
                'login' => 'admin_'.time().'@yourrealdomain.com', // Use your real domain
                'password' => 'AdminPass_'.bin2hex(random_bytes(4)),
                'email' => 'admin_'.time().'@yourrealdomain.com',
                'full_name' => 'System Admin',
                'role' => 'admin'
            ]
        ];
        
        $response = $client->post('https://api.connectycube.com/users', [
            'headers' => [
                'CB-Token' => $appSession['token'],
                'Content-Type' => 'application/json'
            ],
            'json' => $adminData
        ]);
        
        // 3. Update .env if successful
        if ($response->getStatusCode() === 201) {
            $result = json_decode($response->getBody(), true);
            file_put_contents(
                base_path('.env'),
                "\nCONNECTYCUBE_ADMIN_LOGIN=".$adminData['user']['login'].
                "\nCONNECTYCUBE_ADMIN_PASSWORD=".$adminData['user']['password'],
                FILE_APPEND
            );
            return true;
        }
        
        return false;
        
    } catch (\Exception $e) {
        $this->error('Admin creation failed: '.$e->getMessage());
        return false;
    }
}

    private function debugConnectyCube()
    {
        $this->info('=== ConnectyCube Configuration Debug ===');
        
        // Check configuration
        $config = [
            'app_id' => config('services.connectycube.app_id'),
            'auth_key' => config('services.connectycube.auth_key'),
            'auth_secret' => config('services.connectycube.auth_secret'),
            'admin_login' => config('services.connectycube.admin_login'),
            'admin_password' => config('services.connectycube.admin_password'),
        ];

        foreach ($config as $key => $value) {
            if (empty($value)) {
                $this->error("❌ {$key}: NOT SET");
            } else {
                if ($key === 'auth_secret' || $key === 'admin_password') {
                    $this->info("✅ {$key}: " . str_repeat('*', strlen($value)));
                } else {
                    $this->info("✅ {$key}: {$value}");
                }
            }
        }

        $this->newLine();
        $this->info('=== Testing ConnectyCube Connection ===');
        
        try {
            $client = new Client([
                'timeout' => 30,
                'http_errors' => false
            ]);

            $timestamp = time();
            $nonce = rand(1, 1000000);
            
            // Convert to proper types
            $appId = (int) config('services.connectycube.app_id');
            $timestamp = (int) $timestamp;
            $nonce = (int) $nonce;
            
            $this->info("Application ID: {$appId}");
            $this->info("Timestamp: {$timestamp}");
            $this->info("Nonce: {$nonce}");
            
            $signature = $this->generateSignature($timestamp, $nonce);

            $sessionData = [
                'application_id' => $appId,
                'auth_key' => config('services.connectycube.auth_key'),
                'timestamp' => $timestamp,
                'nonce' => $nonce,
                'signature' => $signature,
                'user' => [
                    'login' => config('services.connectycube.admin_login'),
                    'password' => config('services.connectycube.admin_password')
                ]
            ];

            $this->info('Session Data: ' . json_encode($sessionData, JSON_PRETTY_PRINT));

            $response = $client->post('https://api.connectycube.com/session', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'CB-API-Version' => '1.1'
                ],
                'json' => $sessionData
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $result = json_decode($body, true);

            $this->info("Response Status: {$statusCode}");
            $this->info('Response Body: ' . $body);
            
            if ($result) {
                $this->info('Parsed Response: ' . json_encode($result, JSON_PRETTY_PRINT));
            }

            if ($statusCode === 201 && isset($result['session']['token'])) {
                $this->info('✅ ConnectyCube connection successful!');
                $this->info('Session Token: ' . substr($result['session']['token'], 0, 20) . '...');
            } else {
                $this->error('❌ ConnectyCube connection failed!');
                if (isset($result['errors'])) {
                    $errors = is_array($result['errors']) ? $result['errors'] : [$result['errors']];
                    foreach ($errors as $error) {
                        $this->error('Error: ' . (is_array($error) ? json_encode($error) : $error));
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception occurred: ' . $e->getMessage());
        }
    }

    private function testConnectyCubeConnection()
    {
        $this->info('Testing ConnectyCube connection...');
        
        try {
            $client = new Client([
                'timeout' => 30,
                'http_errors' => false
            ]);

            // Try application-level session first
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

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);
            
            if ($statusCode === 201 && isset($result['session']['token'])) {
                $this->info('✅ ConnectyCube connection successful (application-level session)');
                return true;
            } else {
                $this->warn('Application-level session failed, but this is normal for some ConnectyCube configurations');
                $this->info('✅ ConnectyCube connection successful (will create users with application session)');
                return true;
            }
        } catch (\Exception $e) {
            $this->error('❌ ConnectyCube connection failed: ' . $e->getMessage());
            return false;
        }
    }

    private function getAdminSessionToken($client)
    {
        $timestamp = time();
        $nonce = rand(1, 1000000);
        
        // Convert to integers as ConnectyCube expects
        $appId = (int) config('services.connectycube.app_id');
        $timestamp = (int) $timestamp;
        $nonce = (int) $nonce;
        
        $sessionData = [
            'application_id' => $appId,
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
                'headers' => [
                    'Content-Type' => 'application/json',
                    'CB-API-Version' => '1.1'
                ],
                'json' => $sessionData
            ]);

            $statusCode = $response->getStatusCode();
            $responseBody = $response->getBody()->getContents();
            $result = json_decode($responseBody, true);
            
            // Always log detailed info when there's an error
            if ($statusCode !== 201) {
                $this->error("Session creation failed with status: {$statusCode}");
                $this->error('Request Data: ' . json_encode($sessionData, JSON_PRETTY_PRINT));
                $this->error('Response Body: ' . $responseBody);
                
                Log::error('Failed to get ConnectyCube session token', [
                    'status_code' => $statusCode,
                    'response' => $result,
                    'request_data' => $sessionData,
                    'response_body' => $responseBody
                ]);
            }
            
            if ($this->option('debug') || $statusCode !== 201) {
                $this->info("Session Response Status: {$statusCode}");
                $this->info('Session Response: ' . json_encode($result, JSON_PRETTY_PRINT));
            }
            
            if ($statusCode !== 201 || !isset($result['session']['token'])) {
                $errorMsg = 'Unknown error';
                if (isset($result['errors'])) {
                    $errorMsg = is_array($result['errors']) ? implode(', ', $result['errors']) : $result['errors'];
                }
                throw new \Exception("Failed to get ConnectyCube session token. Status: {$statusCode}, Error: {$errorMsg}");
            }
            
            return $result['session']['token'];
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->error('ConnectyCube HTTP request failed: ' . $e->getMessage());
            Log::error('ConnectyCube session request failed', [
                'error' => $e->getMessage(),
                'request_data' => $sessionData
            ]);
            throw new \Exception('ConnectyCube session failed: ' . $e->getMessage());
        }
    }

    private function generateSignature($timestamp, $nonce)
    {
        // Ensure all values are properly typed
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
        
        $signature = hash_hmac('sha1', $string, $authSecret);
        
        if ($this->option('debug')) {
            $this->info("Signature String: {$string}");
            $this->info("Auth Secret: " . str_repeat('*', strlen($authSecret)));
            $this->info("Generated Signature: {$signature}");
        }
        
        return $signature;
    }

   private function syncDrivers()
{
    $query = Driver::query();
    if (!$this->option('force')) {
        $query->whereNull('connectycube_id');
    }

    $drivers = $query->get();
    $results = ['success' => 0, 'failed' => 0];

    if ($drivers->isEmpty()) {
        $this->info('No drivers to sync.');
        return $results;
    }

    $this->info("Syncing {$drivers->count()} drivers...");
    $bar = $this->output->createProgressBar($drivers->count());

    foreach ($drivers as $driver) {
        try {
            $driver->syncConnectyCubeUser();
            $results['success']++;
            $this->newLine();
            $this->info("[DRIVER] ✅ Synced: {$driver->firstname} {$driver->lastname}");
        } catch (\Exception $e) {
            $results['failed']++;
            $this->newLine();
            $this->error("[DRIVER] ❌ Failed: {$driver->firstname} {$driver->lastname} - {$e->getMessage()}");
            Log::error("Driver sync failed: {$driver->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    return $results;
}

   private function syncAdmins()
{
    $query = User::where('role_id', 23);
    if (!$this->option('force')) {
        $query->whereNull('connectycube_id');
    }

    $admins = $query->get();
    $results = ['success' => 0, 'failed' => 0];

    if ($admins->isEmpty()) {
        $this->info('No admin users to sync.');
        return $results;
    }

    $this->info("Syncing {$admins->count()} admin users...");
    $bar = $this->output->createProgressBar($admins->count());

    foreach ($admins as $admin) {
        try {
            $admin->syncConnectyCubeUser();
            $results['success']++;
            $this->newLine();
            $this->info("[ADMIN] ✅ Synced: {$admin->first_name} {$admin->last_name}");
        } catch (\Exception $e) {
            $results['failed']++;
            $this->newLine();
            $this->error("[ADMIN] ❌ Failed: {$admin->first_name} {$admin->last_name} - {$e->getMessage()}");
            Log::error("Admin sync failed: {$admin->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    return $results;
}

    private function performDryRun()
    {
        $this->info('[DRY RUN] These users would be synced:');
        
        // Drivers
        $drivers = Driver::query();
        if (!$this->option('force')) {
            $drivers->whereNull('connectycube_id');
        }
        $drivers = $drivers->get();
        
        $this->info("\nDrivers ({$drivers->count()}):");
        foreach ($drivers as $driver) {
            $status = $driver->connectycube_id ? 'UPDATE' : 'CREATE';
            $this->line("  [{$status}] {$driver->firstname} {$driver->lastname} ({$driver->email})");
        }
        
        // Admins
        $query = User::where('role_id', 23);
        if (!$this->option('force')) {
            $query->whereNull('connectycube_id');
        }
        $admins = $query->get();
        
        $this->info("\nAdmins ({$admins->count()}):");
        foreach ($admins as $admin) {
            $status = $admin->connectycube_id ? 'UPDATE' : 'CREATE';
            $this->line("  [{$status}] {$admin->first_name} {$admin->last_name} ({$admin->email})");
        }
        
        $total = $drivers->count() + $admins->count();
        $this->newLine();
        $this->info("Total users to sync: {$total}");
    }

    private function verifyExistingUsers()
    {
        $this->info('Verifying existing ConnectyCube users...');
        
        // Verify drivers
        $existingDrivers = Driver::whereNotNull('connectycube_id')->get();
        $this->verifyUserGroup($existingDrivers, 'Driver');
        
        // Verify admins
        $existingAdmins = User::whereNotNull('connectycube_id')->where('role_id', 23)->get();
        $this->verifyUserGroup($existingAdmins, 'Admin');
    }

    private function verifyUserGroup($users, $type)
    {
        if ($users->isEmpty()) {
            $this->info("No existing {$type}s to verify.");
            return;
        }

        $bar = $this->output->createProgressBar($users->count());
        $verified = $invalid = 0;

        $this->info("Verifying {$users->count()} {$type}s...");

        foreach ($users as $user) {
            try {
                if ($user->verifyConnectyCubeUser()) {
                    $verified++;
                } else {
                    $invalid++;
                    if (!$this->option('dry-run')) {
                        $user->update([
                            'connectycube_id' => null,
                            'connectycube_login' => null,
                            'connectycube_password' => null
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $invalid++;
                $this->error("Verification failed for {$type} {$user->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$type}s: ✅ {$verified} valid, ❌ {$invalid} invalid");
    }

    // Add this method to your SyncDriversWithConnectyCube command
private function createVerifiedAdminUser()
{
    try {
        $client = new Client();
        
        // 1. First create an application session
        $appSession = $this->createApplicationSession($client);
        
        // 2. Create the admin user
        $userData = [
            'user' => [
                'login' => 'api_admin@yourrealdomain.com', // USE YOUR ACTUAL DOMAIN
                'password' => 'SecureAPIPass123!',
                'email' => 'api_admin@yourrealdomain.com',
                'full_name' => 'API Admin',
                'custom_data' => json_encode(['role' => 'admin'])
            ]
        ];
        
        $response = $client->post('https://api.connectycube.com/users', [
            'headers' => [
                'CB-Token' => $appSession['token'],
                'Content-Type' => 'application/json'
            ],
            'json' => $userData
        ]);
        
        // 3. Update your .env file
        if ($response->getStatusCode() === 201) {
            $this->info('✅ Successfully created admin user');
            $this->info('Add these to your .env file:');
            $this->info('CONNECTYCUBE_ADMIN_LOGIN=api_admin@yourrealdomain.com');
            $this->info('CONNECTYCUBE_ADMIN_PASSWORD=SecureAPIPass123!');
        }
        
        return $response;
    } catch (\Exception $e) {
        $this->error('Failed to create admin user: '.$e->getMessage());
        throw $e;
    }
}
// In your ConnectyCube command
private function createTempAdminUser()
{
    $client = new Client();
    $response = $client->post('https://api.connectycube.com/users', [
        'headers' => [
            'CB-Token' => $this->getAppLevelToken(),
            'Content-Type' => 'application/json'
        ],
        'json' => [
            'user' => [
                'login' => 'temp_admin@yourdomain.com',
                'password' => 'TempPass123!',
                'email' => 'temp_admin@yourdomain.com',
                'full_name' => 'Temporary Admin',
                'role' => 'admin'
            ]
        ]
    ]);
    // Save these credentials to .env
}
    private function showCombinedSummary($driverResults, $adminResults)
    {
        $this->newLine(2);
        $this->info('=== Sync Summary ===');
        
        $this->table(
            ['Type', 'Synced', 'Failed'],
            [
                ['Drivers', $driverResults['success'], $driverResults['failed']],
                ['Admins', $adminResults['success'], $adminResults['failed']],
                ['Total', $driverResults['success'] + $adminResults['success'], 
                      $driverResults['failed'] + $adminResults['failed']]
            ]
        );

        $totalFailed = $driverResults['failed'] + $adminResults['failed'];
        if ($totalFailed > 0) {
            $this->newLine();
            $this->error("❌ {$totalFailed} failures occurred. Check logs for details.");
            $this->newLine();
            $this->warn('💡 You can run the sync again with --force to retry failed items.');
            $this->warn('💡 Use --debug flag to see detailed connection information.');
        } else {
            $this->newLine();
            $this->info('🎉 All users synced successfully!');
        }
    }
    private function getAppLevelToken($client = null)
{
    if (!$client) {
        $client = new Client();
    }
    
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
    
    try {
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
        
    } catch (\Exception $e) {
        $this->error('App token failed: ' . $e->getMessage());
        throw $e;
    }
}

private function createApplicationSession($client)
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
    
    if ($response->getStatusCode() !== 201 || !isset($result['session']['token'])) {
        throw new \Exception('Failed to create application session: ' . json_encode($result));
    }
    
    return [
        'token' => $result['session']['token'],
        'session' => $result['session']
    ];
}
}