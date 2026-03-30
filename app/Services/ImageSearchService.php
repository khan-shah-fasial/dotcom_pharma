<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use App\Models\Product;

class ImageSearchService
{
    protected string $endpoint;
    protected string $apiKey;
    protected string $provider;
    protected int $maxTokens;
    protected int $minTerms;
    protected bool $debugTrace;

    public function __construct()
    {
        $config = config('image_search');
        $this->provider = $config['provider'] ?? 'gemini';
        $providerConfig = $config[$this->provider] ?? $config['gemini'];

        $this->endpoint = (string) ($providerConfig['endpoint'] ?? '');
        $this->apiKey   = (string) ($providerConfig['api_key'] ?? '');
        $this->maxTokens = (int) ($config['max_tokens'] ?? 6);
        $this->minTerms  = (int) ($config['min_terms'] ?? 2);
        $this->debugTrace = (bool) ($config['debug_trace'] ?? false);
    }

    /**
     * Call selected vision provider.
     */
    public function analyzeUploadedImage(UploadedFile $file): array
    {
        if ($this->provider === 'vision') {
            return $this->callVision($file);
        }

        return $this->callGemini($file);
    }

    protected function callGemini(UploadedFile $file): array
    {
        $content = base64_encode(file_get_contents($file->getRealPath()));

        $payload = [
            'contents' => [[
                'parts' => [
                    [
                        'text' => <<<PROMPT
You are an OCR + vision extractor for medicine/health-product packs.
Return STRICT JSON only, no prose. Schema:
{
  "medicine_name": "<full printed product name, e.g., Paracetamol Tablets IP>",
  "composition": "<main molecule/generic, e.g., Paracetamol>",
  "dosage": "<strength as printed, e.g., 500mg>",
  "brand": "<brand or manufacturer if visible>"
}
If a field is unknown, use an empty string. Do not add extra fields. Do not wrap in Markdown.
PROMPT
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
                            'data' => $content,
                        ],
                    ],
                ],
            ]],
        ];

        $url = rtrim($this->endpoint, '/') . '?key=' . $this->apiKey;

        \Log::info('Image search: calling Gemini', [
            'endpoint' => $this->endpoint,
            'mime'     => $file->getMimeType(),
            'size'     => $file->getSize(),
        ]);

        $response = Http::timeout(20)
            ->retry(2, 300)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $body = $response->body();
        \Log::info('Image search: Gemini raw response received', [
            'length' => strlen($body),
            'sample' => Str::limit($body, 4000),
        ]);

        $this->maybeDumpFullResponse($body, 'gemini');

        return $response->json();
    }

    protected function callVision(UploadedFile $file): array
    {
        $content = base64_encode(file_get_contents($file->getRealPath()));

        $payload = [
            'requests' => [[
                'image' => ['content' => $content],
                'features' => [
                    ['type' => 'TEXT_DETECTION'],
                    ['type' => 'LABEL_DETECTION', 'maxResults' => 8],
                ],
            ]],
        ];

        $url = rtrim($this->endpoint, '?') . '?key=' . $this->apiKey;

        \Log::info('Image search: calling Vision', [
            'endpoint' => $this->endpoint,
            'mime'     => $file->getMimeType(),
            'size'     => $file->getSize(),
        ]);

        $response = Http::timeout(15)
            ->retry(2, 200)
            ->post($url, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Vision API error: ' . $response->body());
        }

        $body = $response->body();
        \Log::info('Image search: Vision raw response received', [
            'length' => strlen($body),
            'sample' => Str::limit($body, 4000),
        ]);

        $this->maybeDumpFullResponse($body, 'vision');

        return $response->json();
    }

    /**
     * Extract keywords from provider response; normalize + dedupe.
     */
    public function extractKeywords(array $response): array
    {
        return $this->provider === 'vision'
            ? $this->extractFromVision($response)
            : $this->extractFromGemini($response);
    }

    protected function extractFromGemini(array $response): array
    {
        $stopwords = [
            'tablet','tablets','capsule','capsules','mg','ml','strip','strips','bottle','bottles','syrup','cream','ointment','pack','packs','tab','tabs','dose','doses','tablet(s)','capsule(s)','medicine','medicines','rx','usp','ip','bp','box','boxes'
        ];

        $text = (string) Arr::get($response, 'candidates.0.content.parts.0.text', '');
        $data = $text !== '' ? json_decode($text, true) : null;

        $fields = collect();
        if (is_array($data)) {
            $fields = collect([
                Arr::get($data, 'medicine_name', ''),
                Arr::get($data, 'composition', ''),
                Arr::get($data, 'dosage', ''),
                Arr::get($data, 'brand', ''),
            ]);
        } else {
            $fields = collect(preg_split('/[,\n]/', strtolower($text)));
        }

        return $this->tokenizeFields($fields, $stopwords);
    }

    protected function extractFromVision(array $response): array
    {
        $stopwords = [
            'tablet','tablets','capsule','capsules','mg','ml','strip','strips','bottle','bottles','syrup','cream','ointment','pack','packs','tab','tabs','dose','doses','tablet(s)','capsule(s)','medicine','medicines','rx','usp','ip','bp','box','boxes'
        ];

        $rawText = (string) Arr::get($response, 'responses.0.fullTextAnnotation.text', '');
        $clean = strtolower($rawText);
        $clean = preg_replace('/[^a-z0-9\s]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        $clean = trim($clean);

        $words = $clean === '' ? collect() : collect(explode(' ', $clean));

        $labels = collect(Arr::get($response, 'responses.0.labelAnnotations', []))
            ->pluck('description')
            ->map(fn ($d) => strtolower((string) $d));

        $fields = $words->merge($labels);

        return $this->tokenizeFields($fields, $stopwords);
    }

    protected function tokenizeFields($fields, array $stopwords): array
    {
        return collect($fields)
            ->map(fn ($w) => trim(preg_replace('/[^a-z0-9\s]/', ' ', strtolower((string) $w))))
            ->flatMap(fn ($w) => preg_split('/\s+/', $w))
            ->filter(fn ($w) => $w !== '' && strlen($w) > 2)
            ->reject(fn ($w) => in_array($w, $stopwords, true))
            ->unique()
            ->take(15)
            ->values()
            ->all();
    }

    protected function maybeDumpFullResponse(string $body, string $provider): void
    {
        if (!config('image_search.debug_full')) {
            return;
        }

        $filename = 'logs/image-search-' . $provider . '-' . now()->format('Ymd-His-u') . '.json';
        Storage::disk('local')->put($filename, $body);
        \Log::info('Image search: full response dumped', ['file' => $filename, 'provider' => $provider]);
    }

    /**
     * Match products based on keywords using existing filter_products helper.
     */
    public function matchProducts(array $keywords)
    {
        if (empty($keywords)) {
            return collect();
        }

        $cleanTokens = $this->prepareTokens($keywords);

        if (count($cleanTokens) < $this->minTerms) {
            \Log::info('Image search: insufficient tokens for fulltext', ['tokens' => $cleanTokens]);
            return collect();
        }

        \Log::info('Image search: primary LIKE/AND', [
            'tokens' => $cleanTokens,
        ]);

        return $this->searchProductsByTokens($cleanTokens);
    }

    protected function prepareTokens(array $keywords): array
    {
        $stopwords = [
            'tablet','tablets','capsule','capsules','mg','ml','strip','strips','bottle','bottles','syrup','cream','ointment','pack','packs','tab','tabs','dose','doses','tablet(s)','capsule(s)','medicine','medicines','rx','usp','ip','bp','box','boxes','for','and','the','with','of','net','content'
        ];

        return collect($keywords)
            ->map(fn ($w) => strtolower(preg_replace('/[^a-z0-9]+/i', ' ', (string) $w)))
            ->flatMap(fn ($w) => preg_split('/\s+/', $w))
            ->filter(fn ($w) => $w !== '' && strlen($w) > 2 && !ctype_digit($w))
            ->reject(fn ($w) => in_array($w, $stopwords, true))
            ->unique()
            ->sortByDesc(fn ($w) => strlen($w))
            ->take($this->maxTokens)
            ->values()
            ->tap(function ($tokens) {
                if ($this->debugTrace) {
                    \Log::info('Image search: prepared tokens', ['tokens' => $tokens]);
                }
            })
            ->all();
    }

    public function searchProductsByTokens(array $cleanTokens)
    {
        // 🔹 1. Remove useless words (VERY IMPORTANT)
        $stopWords = ['for','use','only','and','the','with','lmv'];
        $cleanTokens = array_values(array_filter($cleanTokens, function ($word) use ($stopWords) {
            return !in_array(strtolower(trim($word)), $stopWords);
        }));

        if (count($cleanTokens) < $this->minTerms) {
            \Log::info('Image search: insufficient tokens for direct search', ['tokens' => $cleanTokens]);
            return collect();
        }

        // 🔹 2. Base query
        $primary = Product::query()
            ->leftJoin('product_categories as pc', 'pc.product_id', '=', 'products.id')
            ->leftJoin('categories as c', 'c.id', '=', 'pc.category_id')
            ->select('products.*');

        $scoreParts = [];
        $bindings = [];

        // 🔹 3. IMPORTANT: OR logic instead of AND
        $primary->where(function ($query) use ($cleanTokens, &$scoreParts, &$bindings) {

            foreach ($cleanTokens as $tok) {
                $tok = trim($tok);
                if ($tok === '') continue;

                $like = '%' . $tok . '%';

                // ✅ OR condition (this is the main fix)
                $query->orWhere(function ($q) use ($like) {
                    $q->where('products.name', 'like', $like)
                        ->orWhere('products.drug_name', 'like', $like)
                        ->orWhere('products.role_label', 'like', $like)
                        ->orWhere('products.tags', 'like', $like)
                        ->orWhere('products.description', 'like', $like)
                        ->orWhereHas('product_translations', function ($qt) use ($like) {
                            $qt->where('name', 'like', $like)
                            ->orWhere('description', 'like', $like);
                        })
                        ->orWhere('c.name', 'like', $like);
                });

                // 🔥 scoring logic (kept from your code)
                $scoreParts[] = "(
                    (products.name LIKE ?) * 3 +
                    (products.drug_name LIKE ?) * 5 +
                    (products.role_label LIKE ?) * 2 +
                    (products.tags LIKE ?) * 2 +
                    (products.description LIKE ?) * 1 +
                    (IFNULL(c.name,'') LIKE ?) * 1
                )";

                $bindings = array_merge($bindings, [
                    $like, $like, $like, $like, $like, $like
                ]);
            }
        });

        // 🔹 4. Add score column
        if (!empty($scoreParts)) {
            $primary->selectRaw(implode(' + ', $scoreParts) . ' as match_score', $bindings);
        } else {
            $primary->selectRaw('0 as match_score');
        }

        // 🔹 5. Prevent duplicate rows (VERY IMPORTANT)
        $primary->groupBy('products.id');

        // 🔹 6. Execute query
        $results = filter_products($primary)
            ->with(['thumbnail'])
            ->orderByDesc('match_score')
            ->orderBy('products.created_at', 'desc')
            ->limit(20)
            ->get();

        // 🔹 7. Debug logs
        \Log::info('Image search: final results', [
            'tokens' => $cleanTokens,
            'count' => $results->count(),
            'top_scores' => $results->take(5)->pluck('match_score', 'id'),
        ]);

        return $results;
    }
}
