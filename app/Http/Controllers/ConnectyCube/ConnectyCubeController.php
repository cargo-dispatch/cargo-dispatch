<?php

namespace App\Http\Controllers\ConnectyCube;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class ConnectyCubeController extends Controller
{
    protected $client;
    protected $baseUrl = 'https://api.connectycube.com';
    protected $sessionToken;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    private function getSessionToken()
    {
        if (!$this->sessionToken) {
            $response = $this->client->post('/session', [
                'json' => [
                    'application_id' => config('services.connectycube.app_id'),
                    'auth_key' => config('services.connectycube.auth_key'),
                    'timestamp' => time(),
                    'nonce' => rand(),
                    'signature' => $this->generateSignature()
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            $this->sessionToken = $data['session']['token'];
        }

        return $this->sessionToken;
    }

    private function generateSignature()
    {
        $params = [
            'application_id' => config('services.connectycube.app_id'),
            'auth_key' => config('services.connectycube.auth_key'),
            'timestamp' => time(),
            'nonce' => rand()
        ];

        ksort($params);
        $string = implode('', $params);
        return hash_hmac('sha1', $string, config('services.connectycube.auth_secret'));
    }

    public function getChatHistory(Request $request, $days = 30)
    {
        try {
            $driver = auth('sanctum')->user();
            
            if (!$driver) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Create user session
            $userSession = $this->createUserSession($driver);
            $this->sessionToken = $userSession['token'];

            // Get dialogs
            $dialogs = $this->getDialogs([
                'limit' => $request->input('per_page', 20),
                'skip' => ($request->input('page', 1) - 1) * $request->input('per_page', 20),
                'sort_desc' => 'last_message_date_sent'
            ]);

            // Process dialogs and messages
            $history = [];
            foreach ($dialogs['items'] as $dialog) {
                $messages = $this->getMessages([
                    'chat_dialog_id' => $dialog['_id'],
                    'date_sent[gte]' => strtotime("-{$days} days"),
                    'sort_desc' => 'date_sent',
                    'limit' => 100
                ]);
                
                $history[] = [
                    'dialog_id' => $dialog['_id'],
                    'dialog_name' => $dialog['name'] ?? 'Private chat',
                    'messages' => $messages['items']
                ];
            }

            return response()->json([
                'success' => true,
                'history' => $history,
                'period_days' => $days
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get chat history', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get chat history'
            ], 500);
        }
    }

    private function createUserSession($user)
    {
        $response = $this->client->post('/session', [
            'headers' => [
                'CB-Token' => $this->getSessionToken(),
            ],
            'json' => [
                'user' => [
                    'login' => $user->connectycube_login,
                    'password' => $user->connectycube_password
                ]
            ]
        ]);

        return json_decode($response->getBody(), true)['session'];
    }

    private function getDialogs($params = [])
    {
        $response = $this->client->get('/chat/Dialog', [
            'headers' => [
                'CB-Token' => $this->getSessionToken(),
            ],
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    private function getMessages($params = [])
    {
        $response = $this->client->get('/chat/Message', [
            'headers' => [
                'CB-Token' => $this->getSessionToken(),
            ],
            'query' => $params
        ]);

        return json_decode($response->getBody(), true);
    }

    // ... keep your existing verifyUser, handleWebhook, and other methods ...
}