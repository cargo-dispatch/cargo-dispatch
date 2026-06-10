<?php
namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient
{
    public function generateContent(array $contents): array
    {
        $apiKey = config('services.groq.key');
        $model  = config('services.groq.model', 'llama3-8b-8192');

        // Extract text from Gemini-style contents format
        $messages = collect($contents)->map(function ($content) {
            $text = collect($content['parts'] ?? [])
                ->map(fn($part) => $part['text'] ?? '')
                ->implode('\n');

            return [
                'role'    => $content['role'] ?? 'user',
                'content' => $text,
            ];
        })->toArray();

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model'       => $model,
                'messages'    => $messages,
                'temperature' => 0.2,
                'max_tokens'  => 1024,
            ])
            ->throw()
            ->json();

        // Convert Groq response to Gemini-style format
        $text = $response['choices'][0]['message']['content'] ?? '[]';

        return [
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Extract fields from document using Groq API
     */
    public function extractDocumentFields(string $filePath, string $documentType): array
    {
        try {
            // Check if file exists
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$filePath}");
            }

            // Read file as base64
            $fileContent = base64_encode(file_get_contents($filePath));
            $mimeType = mime_content_type($filePath);
            
            if (!$mimeType) {
                $mimeType = 'application/pdf';
            }

            // Build prompt based on document type
            $prompt = $this->getExtractionPrompt($documentType);

            $apiKey = config('services.groq.key');
            $model  = config('services.groq.model', 'llama-3.2-90b-vision-preview');

            // Call Groq API with vision capabilities
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a document extraction expert. Extract information from shipping documents and return ONLY valid JSON. Do not include any other text or explanation.'
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$fileContent}"
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 1024,
                    'response_format' => ['type' => 'json_object']
                ])
                ->throw()
                ->json();

            // Parse the response
            $extractedText = $response['choices'][0]['message']['content'] ?? '{}';
            $extractedData = json_decode($extractedText, true);
            
            // Ensure we have an array
            if (!is_array($extractedData)) {
                $extractedData = [];
            }
            
            return $extractedData;

        } catch (\Exception $e) {
            Log::error('Groq document extraction failed', [
                'file' => $filePath,
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);
            
            // Return empty array on failure
            return [];
        }
    }

    /**
     * Get extraction prompt based on document type
     */
    private function getExtractionPrompt(string $documentType): string
    {
        if ($documentType === 'BOL') {
            return <<<PROMPT
Extract the following fields from this Bill of Lading (BOL) document. Return ONLY a JSON object with these exact keys:

{
    "bol_number": "string or null",
    "shipper_name": "string or null",
    "consignee_name": "string or null",
    "pickup_date": "string in YYYY-MM-DD format or null",
    "weight": "number or null",
    "pallets": "integer or null",
    "product_description": "string or null"
}

If a field is not visible in the document, use null. Do not invent or guess missing information.
PROMPT;
        } else {
            // POD document
            return <<<PROMPT
Extract the following fields from this Proof of Delivery (POD) document. Return ONLY a JSON object with these exact keys:

{
    "delivery_date": "string in YYYY-MM-DD format or null",
    "received_by": "string or null",
    "signature_present": "boolean",
    "delivery_notes": "string or null"
}

If a field is not visible in the document, use null for strings, false for signature_present. Do not invent or guess missing information.
PROMPT;
        }
    }
}