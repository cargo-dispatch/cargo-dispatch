<?php

namespace App\Services\Integrations\Providers;

use App\Services\Integrations\Contracts\DocumentAiProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentAiProvider implements DocumentAiProviderInterface
{
    private string $groqKey;
    private string $groqModel;

    public function __construct()
    {
        $this->groqKey   = (string) (config('services.groq.key')   ?? '');
        $this->groqModel = (string) (config('services.groq.model') ?? 'llama-3.3-70b-versatile');
    }

    public function extractFields(string $path): array
    {
        if (!empty($this->groqKey)) {
            try {
                return $this->groqExtract($path);
            } catch (\Throwable $e) {
                Log::error('Groq extraction failed', ['error' => $e->getMessage()]);
            }
        }

        return $this->mockExtract($path);
    }

    private function groqExtract(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);

        if ($isImage) {
            return $this->groqVisionExtract($path, $ext);
        }

        return $this->groqTextExtract($path);
    }

    private function groqVisionExtract(string $path, string $ext): array
    {
        $base64   = base64_encode(file_get_contents($path));
        $mimeType = $ext === 'jpg' ? 'image/jpeg' : "image/{$ext}";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->groqKey}",
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'       => 'meta-llama/llama-4-scout-17b-16e-instruct',
            'messages'    => [[
                'role'    => 'user',
                'content' => [
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$mimeType};base64,{$base64}"]],
                    ['type' => 'text', 'text' => $this->extractionPrompt()],
                ],
            ]],
            'temperature' => 0,
            'max_tokens'  => 1024,
        ])->throw()->json();

        return $this->parseGroqResponse($response, $path);
    }

    private function groqTextExtract(string $path): array
    {
        $text = $this->extractTextFromFile($path);

        if (empty(trim($text))) {
            return $this->mockExtract($path);
        }

        // Truncate to avoid token limits
        $text = mb_substr($text, 0, 6000);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->groqKey}",
            'Content-Type'  => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'       => $this->groqModel,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a document parser. Extract structured fields from document text and return only valid JSON.',
                ],
                [
                    'role'    => 'user',
                    'content' => $this->extractionPrompt() . "\n\nDocument text:\n" . $text,
                ],
            ],
            'temperature' => 0,
            'max_tokens'  => 1024,
        ])->throw()->json();

        return $this->parseGroqResponse($response, $path);
    }

    private function extractTextFromFile(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'pdf' && class_exists(\Smalot\PdfParser\Parser::class)) {
            try {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf    = $parser->parseFile($path);
                return $pdf->getText();
            } catch (\Throwable $e) {
                Log::warning('PDF parse failed', ['error' => $e->getMessage()]);
            }
        }

        // For txt or fallback — read raw
        if (in_array($ext, ['txt', 'csv'])) {
            return file_get_contents($path);
        }

        return '';
    }

    private function parseGroqResponse(array $response, string $path): array
    {
        $content = $response['choices'][0]['message']['content'] ?? '';

        preg_match('/\{.*\}/s', $content, $matches);
        $fields = [];

        if (!empty($matches[0])) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                $fields = $decoded;
            }
        }

        return [
            'fields'      => $fields,
            'confidence'  => 0.88,
            'source_path' => $path,
            '_source'     => 'groq',
        ];
    }

    private function extractionPrompt(): string
    {
        return <<<PROMPT
You are a strict document parser for freight/trucking documents (BOL, POD, Rate Confirmation).
Extract fields ONLY from what is explicitly written in the document. Do NOT guess, infer, or hallucinate any values.
If a field is not clearly visible in the document, do NOT include it.

Return ONLY a valid JSON object with these fields (only if present):
- shipper: company name in SHIP FROM section
- shipper_address: full street address in SHIP FROM section
- shipper_city, shipper_state, shipper_zip
- consignee: company or person name in SHIP TO section
- consignee_address: full street address in SHIP TO section
- consignee_city, consignee_state, consignee_zip
- bol_number, po_number, reference_number
- carrier, total_weight_lbs, pickup_date, delivery_date
- total_charges, rate

Return ONLY the JSON object. No explanation, no markdown, no code block.
PROMPT;
    }

    private function mockExtract(string $path): array
    {
        return [
            'fields' => [
                'shipper'          => 'Acme Foods',
                'consignee'        => 'XYZ Distribution',
                'pickup_city'      => 'Dallas',
                'pickup_state'     => 'TX',
                'delivery_city'    => 'Atlanta',
                'delivery_state'   => 'GA',
                'total_weight_lbs' => 42000,
                'po_number'        => 'PO-' . date('ymd'),
                'reference_number' => 'REF-' . substr(sha1($path), 0, 8),
            ],
            'confidence'  => 0.95,
            'source_path' => $path,
            '_source'     => 'mock',
        ];
    }
}
