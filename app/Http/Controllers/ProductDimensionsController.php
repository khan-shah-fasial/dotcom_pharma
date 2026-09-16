<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductStock;
use App\Models\ProductStockDimension;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ProductDimensionsController extends Controller
{
    protected array $integerFields = [
        'min_qty',
        'qty_per_piece',
        'qty_per_buffer_box',
        'total_qty_per_case',
    ];

    public function __construct()
    {
        $this->middleware(['permission:show_all_products']);
        ProductStockDimension::ensureTable();
    }

    public function packingGroups(): array
    {
        $pieceNet = 'COALESCE(product_stocks.weight, 0)';
        $pieceGross = $this->pieceGrossSql();
        $bufferQty = 'COALESCE(product_stocks.qty_per_buffer_box, 0)';
        $buffersPerCase = 'COALESCE(product_stocks.count, 0)';
        $piecesPerCase = 'COALESCE(product_stocks.total_qty_per_case, 0)';
        $bufferPerCaseGross = $this->bufferPerCaseGrossSql();

        return [
            'piece' => [
                'label' => 'Each Piece-(Base)',
                'qty_field' => 'qty_per_piece',
                'qty_fixed' => null,
                'net_field' => 'weight',
                'gross_field' => 'piece_gross',
                'gross_virtual' => true,
                'net_sql' => $pieceNet,
                'gross_sql' => $pieceGross,
                'net_factors' => ['weight'],
                'gross_factors' => ['piece_gross'],
                'length' => 'length',
                'width' => 'width',
                'height' => 'height',
            ],
            'buffer' => [
                'label' => 'Inner Buffer Or Shrink Pack',
                'qty_field' => 'qty_per_buffer_box',
                'qty_fixed' => null,
                'net_field' => null,
                'gross_field' => 'weight_buffer_box',
                'net_sql' => '(' . $bufferQty . ' * ' . $pieceNet . ')',
                'gross_sql' => 'COALESCE(product_stocks.weight_buffer_box, 0)',
                'net_factors' => ['qty_per_buffer_box', 'weight'],
                'length' => 'buffer_length',
                'width' => 'buffer_width',
                'height' => 'buffer_height',
            ],
            'buffer_per_case' => [
                'label' => 'Inner Buffer Or Shrink Pack / Case / Carton',
                'qty_field' => 'count',
                'qty_fixed' => null,
                'net_field' => null,
                'gross_field' => 'weight_buffer_per_case',
                'gross_virtual' => true,
                'net_sql' => '(' . $buffersPerCase . ' * ' . $bufferQty . ' * ' . $pieceNet . ')',
                'gross_sql' => $bufferPerCaseGross,
                'net_factors' => ['count', 'qty_per_buffer_box', 'weight'],
                'length' => 'case_length',
                'width' => 'case_width',
                'height' => 'case_height',
            ],
            'qty_per_case' => [
                'label' => 'Qty Per Outer Case/Shipper/Carton',
                'qty_field' => 'total_qty_per_case',
                'qty_fixed' => null,
                'net_field' => null,
                'gross_field' => null,
                'net_sql' => '(' . $piecesPerCase . ' * ' . $pieceNet . ')',
                'gross_sql' => '(' . $piecesPerCase . ' * (' . $pieceGross . '))',
                'net_factors' => ['total_qty_per_case', 'weight'],
                'gross_factors' => ['total_qty_per_case', 'piece_gross'],
                'length' => 'case_length',
                'width' => 'case_width',
                'height' => 'case_height',
            ],
            'outer_case' => [
                'label' => 'Outer Case / Shipper / Carton',
                'qty_field' => null,
                'qty_fixed' => 1,
                'net_field' => null,
                'gross_field' => 'weight_case',
                'net_sql' => '(' . $piecesPerCase . ' * ' . $pieceNet . ')',
                'gross_sql' => 'COALESCE(product_stocks.weight_case, 0)',
                'net_factors' => ['total_qty_per_case', 'weight'],
                'length' => 'case_length',
                'width' => 'case_width',
                'height' => 'case_height',
            ],
        ];
    }

    public function index(Request $request): View
    {
        $groups = $this->packingGroups();
        $sortable = $this->sortableColumns($groups);
        $sortBy = $request->get('sort_by', 'product_name');
        if (!array_key_exists($sortBy, $sortable)) {
            $sortBy = 'product_name';
        }
        $sortDir = strtolower((string) $request->get('sort_dir')) === 'desc' ? 'desc' : 'asc';

        $productName = trim((string) $request->get('product_name', ''));
        $sku = trim((string) $request->get('sku', ''));
        $variant = trim((string) $request->get('variant', ''));
        $categoryId = $request->filled('category_id') ? (int) $request->get('category_id') : null;
        $brandId = $request->filled('brand_id') ? (int) $request->get('brand_id') : null;

        $query = ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->leftJoin('product_stock_dimensions', 'product_stock_dimensions.product_stock_id', '=', 'product_stocks.id')
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

        if ($variant !== '') {
            $query->where('product_stocks.variant', 'like', '%' . $variant . '%');
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

        $this->applyNumericRange($query, 'product_stocks.min_qty', $request->get('min_qty_from'), $request->get('min_qty_to'));
        $this->applyNumericRange($query, 'product_stocks.count', $request->get('count_from'), $request->get('count_to'));

        foreach ($groups as $key => $group) {
            if (!empty($group['qty_field'])) {
                $this->applyNumericRange($query, 'product_stocks.' . $group['qty_field'], $request->get($key . '_qty_from'), $request->get($key . '_qty_to'));
            }
            $this->applyRawRange($query, $group['net_sql'], $request->get($key . '_net_from'), $request->get($key . '_net_to'));
            $this->applyRawRange($query, $group['net_sql'], $this->kgToGm($request->get($key . '_net_kg_from')), $this->kgToGm($request->get($key . '_net_kg_to')));
            $this->applyRawRange($query, $group['gross_sql'], $request->get($key . '_gross_from'), $request->get($key . '_gross_to'));
            $this->applyRawRange($query, $group['gross_sql'], $this->kgToGm($request->get($key . '_gross_kg_from')), $this->kgToGm($request->get($key . '_gross_kg_to')));
            $this->applyNumericRange($query, 'product_stocks.' . $group['length'], $request->get($key . '_length_from'), $request->get($key . '_length_to'));
            $this->applyNumericRange($query, 'product_stocks.' . $group['width'], $request->get($key . '_width_from'), $request->get($key . '_width_to'));
            $this->applyNumericRange($query, 'product_stocks.' . $group['height'], $request->get($key . '_height_from'), $request->get($key . '_height_to'));
            $this->applyCbmRange($query, $group, $request->get($key . '_cbm_from'), $request->get($key . '_cbm_to'));

            if ($request->get('missing_' . $key) === '1') {
                $query->where(function ($missingQuery) use ($group) {
                    $this->applyMissingParts($missingQuery, $group);
                });
            }
        }

        if ($request->get('missing_any') === '1') {
            $query->where(function ($missingQuery) use ($groups) {
                foreach ($groups as $group) {
                    $this->applyMissingParts($missingQuery, $group);
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
                'dimensionSheet',
            ])
            ->orderByRaw($sortable[$sortBy] . ' ' . $sortDir)
            ->orderBy('product_stocks.id')
            ->paginate(25)
            ->appends($request->query());

        $stocks->getCollection()->each(function ($stock) {
            $this->overlaySatellite($stock);
        });

        $filterValues = $request->except(['page']);
        $filtersApplied = collect($filterValues)->contains(function ($value, $key) {
            if (in_array($key, ['sort_by', 'sort_dir'], true)) {
                return false;
            }

            return $value !== null && $value !== '';
        });

        return view('backend.product.dimensions.index', [
            'stocks' => $stocks,
            'packingGroups' => $groups,
            'categories' => Category::where('digital', 0)->orderBy('name')->get(['id', 'name']),
            'brands' => Brand::orderBy('name')->get(['id', 'name']),
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'filtersApplied' => $filtersApplied,
            'productName' => $productName,
            'sku' => $sku,
            'variant' => $variant,
            'categoryId' => $categoryId,
            'brandId' => $brandId,
            'jsGroups' => $this->jsGroups($groups),
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
        $columns = array_merge(
            ['product_stocks.id', 'product_stocks.sku', 'product_stocks.variant', 'products.name as product_name'],
            collect($this->existingFields($this->copyFields()))
                ->map(fn ($field) => 'product_stocks.' . $field)
                ->all()
        );

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
            ->with('dimensionSheet')
            ->orderBy('products.name')
            ->orderBy('product_stocks.sku')
            ->limit(20)
            ->get($columns);

        return response()->json([
            'results' => $results->map(function ($stock) {
                $this->overlaySatellite($stock);
                $dims = $this->fieldPayload($stock, $this->copyFields());

                return [
                    'id' => $stock->id,
                    'label' => $this->sameAsLabel($stock->product_name, $stock->sku, $stock->variant),
                    'dims' => $dims,
                ];
            })->values(),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $fields = $this->editableFields();
        $rules = [];
        foreach ($fields as $field) {
            $rules[$field] = ['nullable', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        $stock = ProductStock::query()
            ->whereHas('product', function ($productQuery) {
                $productQuery->where('digital', 0);
            })
            ->with('dimensionSheet')
            ->findOrFail($id);

        foreach ($this->existingFields($fields) as $field) {
            $value = $validated[$field] ?? null;
            if ($field === 'count') {
                $this->persistCount($stock, $value);
                continue;
            }
            if ($value === null || $value === '') {
                $stock->{$field} = $field === 'min_qty' ? 1 : null;
                continue;
            }

            $stock->{$field} = in_array($field, $this->integerFields, true)
                ? (int) round((float) $value)
                : round((float) $value, in_array($field, $this->weightFields(), true) ? 3 : 2);
        }

        $stock->save();
        $this->persistSatellite($stock, $validated);
        $this->overlaySatellite($stock);
        $dims = $this->fieldPayload($stock, $fields);

        return response()->json([
            'success' => true,
            'message' => translate('Dimensions saved'),
            'dims' => $dims,
        ]);
    }

    protected function sortableColumns(array $groups): array
    {
        $sortable = [
            'product_name' => 'products.name',
            'sku' => 'product_stocks.sku',
            'variant' => 'product_stocks.variant',
            'min_qty' => 'product_stocks.min_qty',
            'count' => 'product_stocks.count',
        ];

        foreach ($groups as $key => $group) {
            if (!empty($group['qty_field']) && $this->columnExists($group['qty_field'])) {
                $sortable[$key . '_qty'] = 'product_stocks.' . $group['qty_field'];
            }
            $sortable[$key . '_net'] = $group['net_sql'];
            $sortable[$key . '_net_kg'] = '(' . $group['net_sql'] . ' / 1000)';
            $sortable[$key . '_gross'] = $group['gross_sql'];
            $sortable[$key . '_gross_kg'] = '(' . $group['gross_sql'] . ' / 1000)';
            if ($this->columnExists($group['length'])) {
                $sortable[$key . '_length'] = 'product_stocks.' . $group['length'];
            }
            if ($this->columnExists($group['width'])) {
                $sortable[$key . '_width'] = 'product_stocks.' . $group['width'];
            }
            if ($this->columnExists($group['height'])) {
                $sortable[$key . '_height'] = 'product_stocks.' . $group['height'];
            }
            if ($this->columnExists($group['length']) && $this->columnExists($group['width']) && $this->columnExists($group['height'])) {
                $sortable[$key . '_cbm'] = $this->cbmSql($group);
            }
        }

        return $sortable;
    }

    protected function editableFields(): array
    {
        return array_values(array_unique(array_merge(['min_qty', 'count'], $this->copyFields())));
    }

    protected function copyFields(): array
    {
        $fields = [];
        foreach ($this->packingGroups() as $group) {
            foreach (['qty_field', 'net_field', 'gross_field', 'length', 'width', 'height'] as $part) {
                if (!empty($group[$part])) {
                    $fields[] = $group[$part];
                }
            }
        }

        return array_values(array_unique($fields));
    }

    protected function satelliteFields(): array
    {
        return ['piece_gross', 'weight_buffer_per_case'];
    }

    protected function weightFields(): array
    {
        $fields = ['weight'];
        foreach ($this->packingGroups() as $group) {
            foreach (['net_field', 'gross_field'] as $part) {
                if (!empty($group[$part])) {
                    $fields[] = $group[$part];
                }
            }
        }

        return array_values(array_unique($fields));
    }

    protected function jsGroups(array $groups): array
    {
        $payload = [];
        foreach ($groups as $key => $group) {
            $payload[$key] = [
                'qtyField' => $group['qty_field'],
                'qtyFixed' => $group['qty_fixed'],
                'netField' => $group['net_field'],
                'grossField' => $group['gross_field'] ?? null,
                'grossFactors' => $group['gross_factors'] ?? [],
                'netFactors' => $group['net_factors'] ?? [],
                'length' => $group['length'],
                'width' => $group['width'],
                'height' => $group['height'],
            ];
        }

        return $payload;
    }

    protected function persistSatellite(ProductStock $stock, array $validated): void
    {
        ProductStockDimension::ensureTable();

        $sheet = $stock->dimensionSheet ?: $stock->dimensionSheet()->make();
        $sheet->product_stock_id = $stock->id;

        foreach ($this->satelliteFields() as $field) {
            $value = $validated[$field] ?? null;
            $sheet->{$field} = $this->hasNumeric($value) ? round((float) $value, 3) : null;
        }

        $sheet->save();
        $stock->setRelation('dimensionSheet', $sheet);
    }

    protected function overlaySatellite(ProductStock $stock): void
    {
        $sheet = $stock->dimensionSheet;
        foreach ($this->satelliteFields() as $field) {
            $stock->setAttribute($field, $sheet?->{$field});
        }
    }

    protected function persistCount(ProductStock $stock, $value): void
    {
        $existing = $stock->getRawOriginal('count');
        if ($existing === null) {
            $existing = $stock->count;
        }

        if ($value === null || $value === '') {
            if ($this->isPackTextCount($existing)) {
                return;
            }
            $stock->count = null;
            return;
        }

        $incoming = (string) (int) round((float) $value);
        if ($this->isPackTextCount($existing)) {
            $existingNumeric = (string) (int) round((float) $existing);
            if ($existingNumeric === $incoming) {
                return;
            }
        }

        $stock->count = $incoming;
    }

    protected function isPackTextCount($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return !preg_match('/^\d+(\.\d+)?$/', trim((string) $value));
    }

    protected function applyMissingParts($missingQuery, array $group): void
    {
        foreach (['qty_field', 'net_field', 'gross_field', 'length', 'width', 'height'] as $part) {
            if (empty($group[$part])) {
                continue;
            }

            $field = $group[$part];
            if ($this->isSatelliteField($field)) {
                $missingQuery->orWhereNull('product_stock_dimensions.' . $field);
                continue;
            }

            if ($this->columnExists($field)) {
                $missingQuery->orWhereNull('product_stocks.' . $field);
            }
        }
    }

    protected function pieceGrossSql(): string
    {
        return 'product_stock_dimensions.piece_gross';
    }

    protected function bufferPerCaseGrossSql(): string
    {
        return 'product_stock_dimensions.weight_buffer_per_case';
    }

    protected function isVirtualField($field): bool
    {
        return $this->isSatelliteField($field);
    }

    protected function isSatelliteField($field): bool
    {
        return in_array($field, $this->satelliteFields(), true);
    }

    protected function hasNumeric($value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }

    protected function applyRawRange($query, string $sql, $from, $to): void
    {
        if ($from !== null && $from !== '' && is_numeric($from)) {
            $query->whereRaw($sql . ' >= ?', [(float) $from]);
        }
        if ($to !== null && $to !== '' && is_numeric($to)) {
            $query->whereRaw($sql . ' <= ?', [(float) $to]);
        }
    }

    protected function applyNumericRange($query, string $column, $from, $to): void
    {
        $field = str_replace('product_stocks.', '', $column);
        if (!$this->columnExists($field)) {
            return;
        }

        if ($from !== null && $from !== '' && is_numeric($from)) {
            $query->where($column, '>=', $from);
        }
        if ($to !== null && $to !== '' && is_numeric($to)) {
            $query->where($column, '<=', $to);
        }
    }

    protected function applyCbmRange($query, array $group, $from, $to): void
    {
        if (!$this->columnExists($group['length']) || !$this->columnExists($group['width']) || !$this->columnExists($group['height'])) {
            return;
        }

        $sql = $this->cbmSql($group);
        if ($from !== null && $from !== '' && is_numeric($from)) {
            $query->whereRaw($sql . ' >= ?', [(float) $from]);
        }
        if ($to !== null && $to !== '' && is_numeric($to)) {
            $query->whereRaw($sql . ' <= ?', [(float) $to]);
        }
    }

    protected function cbmSql(array $group): string
    {
        return '(COALESCE(product_stocks.' . $group['length'] . ', 0) * COALESCE(product_stocks.' . $group['width'] . ', 0) * COALESCE(product_stocks.' . $group['height'] . ', 0)) / 1000000';
    }

    protected function kgToGm($value)
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $value;
        }

        return (float) $value * 1000;
    }

    protected function fieldPayload($stock, array $fields): array
    {
        $payload = [];
        foreach ($fields as $field) {
            $payload[$field] = $this->formatNumber($stock->{$field} ?? null, in_array($field, $this->weightFields(), true) ? 3 : 2);
        }

        return $payload;
    }

    protected function existingFields(array $fields): array
    {
        return array_values(array_filter($fields, fn ($field) => $this->columnExists($field)));
    }

    protected function columnExists(string $field): bool
    {
        static $columns = null;
        if ($columns === null) {
            $columns = array_flip(Schema::getColumnListing('product_stocks'));
        }

        return isset($columns[$field]);
    }

    protected function formatNumber($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;
        if (!is_finite($number)) {
            return '';
        }

        if ($decimals === 0) {
            return (string) (int) round($number);
        }

        return rtrim(rtrim(sprintf('%.' . $decimals . 'f', $number), '0'), '.') ?: '0';
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
