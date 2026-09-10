<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductDimensionsController extends Controller
{
    protected array $dimensionFields = [
        'length',
        'width',
        'height',
        'buffer_length',
        'buffer_width',
        'buffer_height',
        'case_length',
        'case_width',
        'case_height',
    ];

    public function __construct()
    {
        $this->middleware(['permission:show_all_products']);
    }

    public function index(Request $request): View
    {
        $productName = trim((string) $request->get('product_name', ''));
        $sku = trim((string) $request->get('sku', ''));
        $categoryId = $request->filled('category_id') ? (int) $request->get('category_id') : null;
        $brandId = $request->filled('brand_id') ? (int) $request->get('brand_id') : null;
        $missingDimensions = $request->get('missing_dimensions') === '1';

        $sortable = [
            'product_name' => 'products.name',
            'sku' => 'product_stocks.sku',
            'variant' => 'product_stocks.variant',
            'piece_cbm' => '(COALESCE(product_stocks.length, 0) * COALESCE(product_stocks.width, 0) * COALESCE(product_stocks.height, 0)) / 1000000',
            'buffer_cbm' => '(COALESCE(product_stocks.buffer_length, 0) * COALESCE(product_stocks.buffer_width, 0) * COALESCE(product_stocks.buffer_height, 0)) / 1000000',
            'case_cbm' => '(COALESCE(product_stocks.case_length, 0) * COALESCE(product_stocks.case_width, 0) * COALESCE(product_stocks.case_height, 0)) / 1000000',
        ];

        $sortBy = $request->get('sort_by', 'product_name');
        if (!array_key_exists($sortBy, $sortable)) {
            $sortBy = 'product_name';
        }
        $sortDir = strtolower((string) $request->get('sort_dir')) === 'desc' ? 'desc' : 'asc';

        $query = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('products.digital', 0)
            ->select('product_stocks.*');

        if ($productName !== '') {
            $like = '%' . $productName . '%';
            $query->where(function ($nameQuery) use ($like) {
                $nameQuery
                    ->where('products.name', 'like', $like)
                    ->orWhereHas('product.product_translations', function ($translationQuery) use ($like) {
                        $translationQuery->where('name', 'like', $like);
                    });
            });
        }

        if ($sku !== '') {
            $query->where('product_stocks.sku', 'like', '%' . $sku . '%');
        }

        if ($categoryId) {
            $query->where(function ($categoryQuery) use ($categoryId) {
                $categoryQuery
                    ->where('products.category_id', $categoryId)
                    ->orWhereExists(function ($exists) use ($categoryId) {
                        $exists->selectRaw('1')
                            ->from('product_categories')
                            ->whereColumn('product_categories.product_id', 'products.id')
                            ->where('product_categories.category_id', $categoryId);
                    });
            });
        }

        if ($brandId) {
            $query->where('products.brand_id', $brandId);
        }

        if ($missingDimensions) {
            $query->where(function ($missingQuery) {
                foreach ($this->dimensionFields as $field) {
                    $missingQuery->orWhereNull('product_stocks.' . $field);
                }
            });
        }

        $stocks = $query
            ->with([
                'product' => function ($productQuery) {
                    $productQuery
                        ->select(['id', 'name', 'brand_id', 'category_id'])
                        ->without(['product_translations', 'taxes', 'thumbnail'])
                        ->with(['brand' => function ($brandQuery) {
                            $brandQuery->select(['id', 'name']);
                        }]);
                },
            ])
            ->orderByRaw($sortable[$sortBy] . ' ' . $sortDir)
            ->orderBy('product_stocks.id')
            ->paginate(25)
            ->appends($request->query());

        $categories = Category::where('digital', 0)->orderBy('name')->get(['id', 'name']);
        $brands = Brand::orderBy('name')->get(['id', 'name']);

        $filtersApplied = collect([
            $productName,
            $sku,
            $categoryId,
            $brandId,
            $missingDimensions ? '1' : '',
        ])->contains(fn ($value) => $value !== null && $value !== '' && $value !== 0);

        return view('backend.product.dimensions.index', [
            'stocks' => $stocks,
            'categories' => $categories,
            'brands' => $brands,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'filtersApplied' => $filtersApplied,
            'productName' => $productName,
            'sku' => $sku,
            'categoryId' => $categoryId,
            'brandId' => $brandId,
            'missingDimensions' => $missingDimensions,
        ]);
    }

    public function sameAsSearch(Request $request): JsonResponse
    {
        $term = trim((string) $request->get('q', ''));
        $excludeId = (int) $request->get('exclude', 0);

        if (mb_strlen($term) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%' . $term . '%';

        $results = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('products.digital', 0)
            ->whereNotNull('product_stocks.length')
            ->whereNotNull('product_stocks.width')
            ->whereNotNull('product_stocks.height')
            ->when($excludeId > 0, function ($query) use ($excludeId) {
                $query->where('product_stocks.id', '!=', $excludeId);
            })
            ->where(function ($searchQuery) use ($like) {
                $searchQuery
                    ->where('products.name', 'like', $like)
                    ->orWhere('product_stocks.sku', 'like', $like)
                    ->orWhere('product_stocks.variant', 'like', $like);
            })
            ->orderBy('products.name')
            ->orderBy('product_stocks.sku')
            ->limit(20)
            ->get([
                'product_stocks.id',
                'product_stocks.sku',
                'product_stocks.variant',
                'products.name as product_name',
                'product_stocks.length',
                'product_stocks.width',
                'product_stocks.height',
                'product_stocks.buffer_length',
                'product_stocks.buffer_width',
                'product_stocks.buffer_height',
                'product_stocks.case_length',
                'product_stocks.case_width',
                'product_stocks.case_height',
            ]);

        return response()->json([
            'results' => $results->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'label' => $this->sameAsLabel($stock->product_name, $stock->sku, $stock->variant),
                    'dims' => $this->dimensionPayload($stock),
                ];
            })->values(),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $rules = [];
        foreach ($this->dimensionFields as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        $stock = ProductStock::query()
            ->whereHas('product', function ($productQuery) {
                $productQuery->where('digital', 0);
            })
            ->findOrFail($id);

        foreach ($this->dimensionFields as $field) {
            $value = $validated[$field] ?? null;
            $stock->{$field} = ($value === null || $value === '') ? null : round((float) $value, 2);
        }

        $stock->save();

        return response()->json([
            'success' => true,
            'message' => translate('Dimensions saved'),
            'dims' => $this->dimensionPayload($stock),
            'cbm' => [
                'piece' => $this->cbm($stock->length, $stock->width, $stock->height),
                'buffer' => $this->cbm($stock->buffer_length, $stock->buffer_width, $stock->buffer_height),
                'case' => $this->cbm($stock->case_length, $stock->case_width, $stock->case_height),
            ],
        ]);
    }

    protected function dimensionPayload($stock): array
    {
        $payload = [];
        foreach ($this->dimensionFields as $field) {
            $payload[$field] = $this->formatDim($stock->{$field} ?? null);
        }

        return $payload;
    }

    protected function formatDim($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;
        if (!is_finite($number)) {
            return '';
        }

        return rtrim(rtrim(sprintf('%.2f', $number), '0'), '.') ?: '0';
    }

    protected function cbm($length, $width, $height): string
    {
        if ($length === null || $width === null || $height === null || $length === '' || $width === '' || $height === '') {
            return '';
        }

        $value = ((float) $length * (float) $width * (float) $height) / 1000000;
        if (!is_finite($value) || $value < 0) {
            return '';
        }

        return number_format($value, 4, '.', '');
    }

    protected function sameAsLabel($productName, $sku, $variant): string
    {
        $sku = trim((string) $sku);
        $variant = trim((string) $variant);
        $parts = array_filter([
            $productName,
            $sku !== '' ? $sku : null,
            $variant !== '' ? $variant : null,
        ]);

        return implode(' — ', $parts);
    }
}
