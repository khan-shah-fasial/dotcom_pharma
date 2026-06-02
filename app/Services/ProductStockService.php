<?php

namespace App\Services;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Models\ProductStock;
use App\Utility\ProductUtility;
use App\Models\ProductBatch;
use App\Models\Product;
use App\Models\ProductNotify;
use App\Models\NotificationType;
use App\Models\User;
use App\Notifications\ProductRestockNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class ProductStockService
{
    protected function normalizeStockScheme($value): int
    {
        $scheme = $this->normalizeBatchScheme($value);

        return $scheme !== null ? $scheme : 0;
    }

    protected function normalizeBatchScheme($value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value >= 0 ? $value : null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Scheme must be a whole number only (no decimal).
        if (!preg_match('/^\d+$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    protected function normalizeBatchMonthYearDate($value, bool $useEndOfMonth = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            if ($useEndOfMonth) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value . '-01')->endOfMonth()->toDateString();
            }

            return $value . '-01';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            if ($useEndOfMonth) {
                return \Carbon\Carbon::parse($value)->endOfMonth()->toDateString();
            }

            return $value;
        }

        return null;
    }

    protected function normalizeBatchDiscountTimestamp($value, bool $isEndDate = false): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int) $value;
            return $timestamp > 0 ? $timestamp : null;
        }

        $dateString = $value . ($isEndDate ? ' 23:59:59' : ' 00:00:00');
        $parsed = strtotime($dateString);

        return $parsed !== false ? (int) $parsed : null;
    }

    protected function extractBatchDiscountData(array $row): array
    {
        $isActive = (int) ($row['discount_active'] ?? 0) === 1;

        if (!$isActive) {
            return [
                'discount_active' => 0,
                'discount_type' => null,
                'discount' => null,
                'discount_start_date' => null,
                'discount_end_date' => null,
            ];
        }

        $discountType = $row['discount_type'] ?? null;
        if (!in_array($discountType, ['percent', 'flat'], true)) {
            $discountType = null;
        }

        $discount = isset($row['discount']) && is_numeric($row['discount']) ? (float) $row['discount'] : null;
        if ($discount !== null && $discount <= 0) {
            $discount = null;
        }

        $discountStartDate = $this->normalizeBatchDiscountTimestamp($row['discount_start_date'] ?? null, false);
        $discountEndDate = $this->normalizeBatchDiscountTimestamp($row['discount_end_date'] ?? null, true);

        if ($discountStartDate !== null && $discountEndDate !== null && $discountEndDate < $discountStartDate) {
            $discountEndDate = $discountStartDate;
        }

        if ($discountType === null || $discount === null) {
            return [
                'discount_active' => 0,
                'discount_type' => null,
                'discount' => null,
                'discount_start_date' => null,
                'discount_end_date' => null,
            ];
        }

        return [
            'discount_active' => 1,
            'discount_type' => $discountType,
            'discount' => $discount,
            'discount_start_date' => $discountStartDate,
            'discount_end_date' => $discountEndDate,
        ];
    }

    public function store(array $data, $product)
    {
        $collection = collect($data);

        $options = ProductUtility::get_attribute_options($collection);
        
        //Generates the combinations of customer choice options
        $combinations = (new CombinationService())->generate_combination($options);
        
        $variant = '';
        if (count($combinations) > 0) {
            $product->variant_product = 1;
            $product->save();
            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);
                $product_stock = new ProductStock();
                $product_stock->product_id = $product->id;

                $product_stock->variant = $str;
                $product_stock->is_hidden = (int) request()->get('is_hidden_' . str_replace('.', '_', $str), 0);
                
                // $product_stock->price = request()['price_' . str_replace('.', '_', $str)];
                // $product_stock->role_price = generateRoleBasedPrices(request()['price_' . str_replace('.', '_', $str)]); //price by role

                // $product_stock->dimension = request()->get('dimension_' . str_replace('.', '_', $str), null);
                $product_stock->length = request()->get('length_' . str_replace('.', '_', $str), null);
                $product_stock->width = request()->get('width_' . str_replace('.', '_', $str), null);
                $product_stock->height = request()->get('height_' . str_replace('.', '_', $str), null);

                $product_stock->weight = request()->get('weight_' . str_replace('.', '_', $str), null);
                $product_stock->count = request()->get('count_' . str_replace('.', '_', $str), null);
                $product_stock->min_qty = request()->get('min_qty_' . str_replace('.', '_', $str), 1);
                $product_stock->scheme = $this->normalizeStockScheme(request()->get('scheme_' . str_replace('.', '_', $str), 0));
                $product_stock->qty_per_piece = request()->get('qty_per_piece_' . str_replace('.', '_', $str), null);
                $product_stock->qty_per_buffer_box = request()->get('qty_per_buffer_box_' . str_replace('.', '_', $str), null);
                $product_stock->total_qty_per_case = request()->get('total_qty_per_case_' . str_replace('.', '_', $str), null);
                $product_stock->weight_buffer_box = request()->get('weight_buffer_box_' . str_replace('.', '_', $str), null);
                $product_stock->weight_case = request()->get('weight_case_' . str_replace('.', '_', $str), null);
                $product_stock->buffer_length = request()->get('buffer_length_' . str_replace('.', '_', $str), null);
                $product_stock->buffer_width = request()->get('buffer_width_' . str_replace('.', '_', $str), null);
                $product_stock->buffer_height = request()->get('buffer_height_' . str_replace('.', '_', $str), null);
                $product_stock->case_length = request()->get('case_length_' . str_replace('.', '_', $str), null);
                $product_stock->case_width = request()->get('case_width_' . str_replace('.', '_', $str), null);
                $product_stock->case_height = request()->get('case_height_' . str_replace('.', '_', $str), null);

                $product_stock->sku = request()['sku_' . str_replace('.', '_', $str)];
                $product_stock->image = request()['img_' . str_replace('.', '_', $str)];
                $product_stock->save();

                $this->syncBatchesFromRequest($product_stock, $str, $product);

            }
        } else {
            unset($collection['colors_active'], $collection['colors'], $collection['choice_no']);
            $qty = $collection->get('current_stock', 0);
            // $qty = $collection['current_stock'];
            $price = $collection['unit_price'];

            $mrp_price = $collection['mrp_price'] ?? null;
            // $dimension = $collection['dimension'] ?? null;
            $length = $collection['length'] ?? null;
            $width = $collection['width'] ?? null;
            $height = $collection['height'] ?? null;

            $weight = $collection['weight'] ?? null;
            $count = $collection['count'] ?? null;
            $min_qty = $collection['min_qty'] ?? 1;
            $scheme = $this->normalizeStockScheme($collection['scheme'] ?? 0);
            $product_exp_date = $this->normalizeBatchMonthYearDate($collection['product_exp_date'] ?? null, true);
            $qty_per_piece = $collection['qty_per_piece'] ?? null;
            $qty_per_buffer_box = $collection['qty_per_buffer_box'] ?? null;
            $total_qty_per_case = $collection['total_qty_per_case'] ?? null;
            $weight_buffer_box = $collection['weight_buffer_box'] ?? null;
            $weight_case = $collection['weight_case'] ?? null;
            $buffer_length = $collection['buffer_length'] ?? null;
            $buffer_width = $collection['buffer_width'] ?? null;
            $buffer_height = $collection['buffer_height'] ?? null;
            $case_length = $collection['case_length'] ?? null;
            $case_width = $collection['case_width'] ?? null;
            $case_height = $collection['case_height'] ?? null;

            unset($collection['current_stock']);
            unset($collection['dimension']);
            unset($collection['mrp_price']);

            // $data = $collection->merge(compact('variant', 'qty', 'price', 'per_piece_price'))->toArray();

            $data = $collection->merge(compact(
                'variant',
                'qty',
                'price',
                'mrp_price',
                'length',
                'width',
                'height',
                'weight',
                'count',
                'min_qty',
                'scheme',
                'product_exp_date',
                'qty_per_piece',
                'qty_per_buffer_box',
                'total_qty_per_case',
                'weight_buffer_box',
                'weight_case',
                'buffer_length',
                'buffer_width',
                'buffer_height',
                'case_length',
                'case_width',
                'case_height'
            ))->toArray();

            ProductStock::create($data);

        }
    }

    public function product_duplicate_store($product_stocks , $product_new)
    {
        foreach ($product_stocks as $key => $stock) {
            $product_stock              = new ProductStock;
            $product_stock->product_id  = $product_new->id;
            $product_stock->variant     = $stock->variant;
            $product_stock->is_hidden   = $stock->is_hidden ?? 0;
            $product_stock->price       = $stock->price;
            $product_stock->mrp_price   = $stock->mrp_price;
            $product_stock->sku         = $stock->sku;
            $product_stock->qty         = $stock->qty;
            $product_stock->length      = $stock->length;
            $product_stock->width       = $stock->width;
            $product_stock->height      = $stock->height;
            $product_stock->weight      = $stock->weight;
            $product_stock->count       = $stock->count;
            $product_stock->min_qty     = $stock->min_qty;
            $product_stock->scheme      = $stock->scheme ?? 0;
            $product_stock->product_exp_date = $stock->product_exp_date;
            $product_stock->qty_per_piece = $stock->qty_per_piece;
            $product_stock->qty_per_buffer_box = $stock->qty_per_buffer_box;
            $product_stock->total_qty_per_case = $stock->total_qty_per_case;
            $product_stock->weight_buffer_box = $stock->weight_buffer_box;
            $product_stock->weight_case = $stock->weight_case;
            $product_stock->buffer_length = $stock->buffer_length;
            $product_stock->buffer_width = $stock->buffer_width;
            $product_stock->buffer_height = $stock->buffer_height;
            $product_stock->case_length = $stock->case_length;
            $product_stock->case_width = $stock->case_width;
            $product_stock->case_height = $stock->case_height;
            $product_stock->save();
        }
    }


    public function update(array $data, $product)
    {
        $collection = collect($data);

        $options = ProductUtility::get_attribute_options($collection);
        
        // Generates the combinations of customer choice options
        $combinations = (new CombinationService())->generate_combination($options);

        $restockedVariants = [];

        $variant = '';
        if (count($combinations) > 0) {
            $product->variant_product = 1;
            $product->save();

            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);

                // Find existing product stock by variant or SKU
                $productStock = ProductStock::where('product_id', $product->id)
                    ->where('variant', $str)
                    ->first();

                if (!$productStock) {
                    // Optionally create new stock if not found
                    $productStock = new ProductStock();
                    $productStock->product_id = $product->id;
                    $productStock->variant = $str;
                }

                $oldQty = $productStock->batches()->exists()
                    ? (int) $productStock->batches()->sum('qty')
                    : (int) ($productStock->qty ?? 0);

                // Update the fields (non-batch level)
                $productStock->is_hidden = (int) request()->get('is_hidden_' . str_replace('.', '_', $str), 0);
                // $productStock->mrp_role_price = generateRoleBasedPrices(request()['mrp_price_' . str_replace('.', '_', $str)]);

                // $productStock->price = request()['price_' . str_replace('.', '_', $str)];
                // $productStock->role_price = generateRoleBasedPrices(request()['price_' . str_replace('.', '_', $str)]);

                $productStock->length = request()->get('length_' . str_replace('.', '_', $str), null);
                $productStock->width = request()->get('width_' . str_replace('.', '_', $str), null);
                $productStock->height = request()->get('height_' . str_replace('.', '_', $str), null);

                $productStock->weight = request()->get('weight_' . str_replace('.', '_', $str), null);
                $productStock->count = request()->get('count_' . str_replace('.', '_', $str), null);
                $productStock->min_qty = request()->get('min_qty_' . str_replace('.', '_', $str), 1);
                $productStock->scheme = $this->normalizeStockScheme(request()->get('scheme_' . str_replace('.', '_', $str), $productStock->scheme ?? 0));
                $productStock->qty_per_piece = request()->get('qty_per_piece_' . str_replace('.', '_', $str), null);
                $productStock->qty_per_buffer_box = request()->get('qty_per_buffer_box_' . str_replace('.', '_', $str), null);
                $productStock->total_qty_per_case = request()->get('total_qty_per_case_' . str_replace('.', '_', $str), null);
                $productStock->weight_buffer_box = request()->get('weight_buffer_box_' . str_replace('.', '_', $str), null);
                $productStock->weight_case = request()->get('weight_case_' . str_replace('.', '_', $str), null);
                $productStock->buffer_length = request()->get('buffer_length_' . str_replace('.', '_', $str), null);
                $productStock->buffer_width = request()->get('buffer_width_' . str_replace('.', '_', $str), null);
                $productStock->buffer_height = request()->get('buffer_height_' . str_replace('.', '_', $str), null);
                $productStock->case_length = request()->get('case_length_' . str_replace('.', '_', $str), null);
                $productStock->case_width = request()->get('case_width_' . str_replace('.', '_', $str), null);
                $productStock->case_height = request()->get('case_height_' . str_replace('.', '_', $str), null);

                $productStock->sku = request()['sku_' . str_replace('.', '_', $str)];
                $productStock->image = request()['img_' . str_replace('.', '_', $str)];
                $productStock->save();
                $this->syncBatchesUpdateFromRequest($productStock, $str, $product);

                // compute new qty after sync
                $newQty = $productStock->batches()->exists()
                    ? (int) $productStock->batches()->sum('qty')
                    : (int) ($productStock->qty ?? 0);

                Log::info('[RestockDetection] Variant update', [
                    'product_id' => $product->id,
                    'variant' => $str,
                    'old_qty' => $oldQty,
                    'new_qty' => $newQty,
                ]);

                if ($oldQty <= 0 && $newQty > 0) {
                    $restockedVariants[] = $str !== '' ? $str : translate('Default');
                }
            }
        } else {
            // Single variant case
            unset($collection['colors_active'], $collection['colors'], $collection['choice_no']);

            $variant = ''; // single variant

            $qty = $collection->get('current_stock', 0);
            // $qty = $collection['current_stock'];
            $price = $collection['unit_price'];
            $mrp_price = $collection['mrp_price'] ?? null;
            $length = $collection['length'] ?? null;
            $width = $collection['width'] ?? null;
            $height = $collection['height'] ?? null;
            $weight = $collection['weight'] ?? null;
            $count = $collection['count'] ?? null;
            $min_qty = $collection['min_qty'] ?? 1;
            $schemeInput = $collection['scheme'] ?? null;
            $product_exp_date = $this->normalizeBatchMonthYearDate($collection['product_exp_date'] ?? null, true);
            $qty_per_piece = $collection['qty_per_piece'] ?? null;
            $qty_per_buffer_box = $collection['qty_per_buffer_box'] ?? null;
            $total_qty_per_case = $collection['total_qty_per_case'] ?? null;
            $weight_buffer_box = $collection['weight_buffer_box'] ?? null;
            $weight_case = $collection['weight_case'] ?? null;
            $buffer_length = $collection['buffer_length'] ?? null;
            $buffer_width = $collection['buffer_width'] ?? null;
            $buffer_height = $collection['buffer_height'] ?? null;
            $case_length = $collection['case_length'] ?? null;
            $case_width = $collection['case_width'] ?? null;
            $case_height = $collection['case_height'] ?? null;

            unset($collection['current_stock']);
            // unset($collection['dimension']);
            unset($collection['mrp_price']);

            // Find existing stock entry if possible (based on SKU or product_id + variant)
            $sku = $collection['sku'] ?? null;
            $productStock = null;

            if ($sku) {
                $productStock = ProductStock::where('product_id', $product->id)
                    ->where('sku', $sku)
                    ->first();
            }

            if (!$productStock) {
                // If no existing stock, create one
                $productStock = new ProductStock();
                $productStock->product_id = $product->id;
                $productStock->variant = $variant;
                $productStock->sku = $sku;
            }

            $oldQty = $productStock->batches()->exists()
                ? (int) $productStock->batches()->sum('qty')
                : (int) ($productStock->qty ?? 0);

            // Update fields
            $productStock->qty = $qty;
            $productStock->price = $price;
            $productStock->mrp_price = $mrp_price;
            $productStock->length = $length;
            $productStock->width = $width;
            $productStock->height = $height;
            $productStock->weight = $weight;
            $productStock->count = $count;
            $productStock->min_qty = $min_qty;
            $productStock->scheme = $this->normalizeStockScheme($schemeInput ?? ($productStock->scheme ?? 0));
            $productStock->product_exp_date = $product_exp_date;
            $productStock->qty_per_piece = $qty_per_piece;
            $productStock->qty_per_buffer_box = $qty_per_buffer_box;
            $productStock->total_qty_per_case = $total_qty_per_case;
            $productStock->weight_buffer_box = $weight_buffer_box;
            $productStock->weight_case = $weight_case;
            $productStock->buffer_length = $buffer_length;
            $productStock->buffer_width = $buffer_width;
            $productStock->buffer_height = $buffer_height;
            $productStock->case_length = $case_length;
            $productStock->case_width = $case_width;
            $productStock->case_height = $case_height;

            $productStock->save();

            $newQty = $productStock->batches()->exists()
                ? (int) $productStock->batches()->sum('qty')
                : (int) ($productStock->qty ?? 0);

            Log::info('[RestockDetection] Single variant update', [
                'product_id' => $product->id,
                'variant' => $variant,
                'old_qty' => $oldQty,
                'new_qty' => $newQty,
            ]);

            if ($oldQty <= 0 && $newQty > 0) {
                $restockedVariants[] = translate('Default');
            }
        }

        if (!empty($restockedVariants)) {
            $this->dispatchRestockNotifications($product, $restockedVariants);
        }
    }

    /**
     * Sync product batch rows for a given stock & variant from the current request.
     *
     * This reads request data in the following structure (per variant key):
     * batches[VARIANT_KEY][] = [
     *   'id'              => (optional) existing batch id,
     *   'batch'           => string,
     *   'mrp_price'       => numeric,
     *   'qty'             => int,
     *   'product_exp_date'=> date string,
     *   'coa'             => string/file id,
     * ]
     *
     * It then:
     * - Rebuilds the batch list for this stock
     * - Aggregates qty onto the stock
     * - Mirrors key fields from the first batch onto the stock (mrp_price, product_exp_date, coa)
     * - Recomputes role_price per batch based on batch MRP (using existing helper)
     */
    protected function syncBatchesFromRequest(ProductStock $stock, string $variantString, Product $product): void
    {
        // Normalize variant key: replace dots and spaces with underscores, convert to lowercase
        $variantKey = strtolower(str_replace(['.', ' ', '-'], '_', $variantString));
        $batchesInput = request()->input('batches.' . $variantKey, []);

        // If no batch data came for this variant, keep existing behaviour and just return.
        if (!is_array($batchesInput) || count($batchesInput) === 0) {
            return;
        }

        // Remove existing batches for a clean re-sync
        $stock->batches()->delete();

        $totalQty = 0;
        $firstBatch = null;

        foreach ($batchesInput as $row) {
            // Skip completely empty rows (e.g. template clones not filled)
            $hasContent = false;
            foreach (['batch', 'mrp_price', 'qty', 'product_exp_date', 'manufacturing_date', 'coa', 'is_non_batch'] as $field) {
                if (!empty($row[$field])) {
                    $hasContent = true;
                    break;
                }
            }
            if (!$hasContent) {
                continue;
            }

            $qty = (int) ($row['qty'] ?? 0);
            $totalQty += $qty;

            $mrpPrice = $row['mrp_price'] ?? null;
            $isNonBatch = !empty($row['is_non_batch']);
            $batchCode = trim((string) ($row['batch'] ?? ''));
            if ($isNonBatch && $batchCode === '') {
                $batchCode = '-';
            }

            $batch = new ProductBatch();
            $batch->product_id       = $product->id;
            $batch->product_stock_id = $stock->id;
            $batch->batch            = $batchCode !== '' ? $batchCode : null;
            $batch->mrp_price        = $mrpPrice;
            $batch->qty              = $qty;
            $batch->scheme           = $this->normalizeStockScheme($row['scheme'] ?? 0);
            $batch->product_exp_date = $isNonBatch ? null : $this->normalizeBatchMonthYearDate($row['product_exp_date'] ?? null, true);
            $batch->manufacturing_date = $isNonBatch ? null : $this->normalizeBatchMonthYearDate($row['manufacturing_date'] ?? null);
            $batch->coa              = $isNonBatch ? null : ($row['coa'] ?? null);
            $batchDiscountData       = $this->extractBatchDiscountData($row);
            $batch->discount_active  = $batchDiscountData['discount_active'];
            $batch->discount_type    = $batchDiscountData['discount_type'];
            $batch->discount         = $batchDiscountData['discount'];
            $batch->discount_start_date = $batchDiscountData['discount_start_date'];
            $batch->discount_end_date = $batchDiscountData['discount_end_date'];

            // Role-based prices per batch: preserve submitted value when available.
            if (!empty($row['role_price'])) {
                $batch->role_price = is_string($row['role_price']) ? $row['role_price'] : json_encode($row['role_price']);
            } elseif (!empty($mrpPrice) && function_exists('generateRoleBasedPrices')) {
                $batch->role_price = generateRoleBasedPrices((float) 0);
            }

            $batch->save();

            if ($firstBatch === null) {
                $firstBatch = $batch;
            }
        }

        // Mirror aggregated & representative data back onto the stock so the rest
        // of the system (which still relies on product_stocks) continues to work.
        if ($totalQty > 0) {
            $stock->qty = $totalQty;
        }

        if ($firstBatch !== null) {
            $stock->mrp_price        = $firstBatch->mrp_price;
            $stock->product_exp_date = $firstBatch->product_exp_date;
            $stock->coa              = $firstBatch->coa;
        }

        $stock->save();

        // Existing behaviour: whenever detailed stock data changes, mark product unpublished

        $product->published = 0;

        $product->save();
    }

    /**
     * Update batches from request without deleting all: update existing (by id), add new, remove only those not in request.
     * Use this in product update flow so batch records are updated in place instead of recreated.
     */
    protected function syncBatchesUpdateFromRequest(ProductStock $stock, string $variantString, Product $product): void
    {
        $variantKey = strtolower(str_replace(['.', ' ', '-'], '_', $variantString));
        $batchesInput = request()->input('batches.' . $variantKey, []);

        if (!is_array($batchesInput) || count($batchesInput) === 0) {
            return;
        }

        $submittedIds = [];
        foreach ($batchesInput as $row) {
            if (!empty($row['id'])) {
                $submittedIds[] = (int) $row['id'];
            }
        }

        // Remove batches that belong to this stock but were not in the request (user removed those rows)
        if (count($submittedIds) > 0) {
            $stock->batches()->whereNotIn('id', $submittedIds)->delete();
        }

        $totalQty = 0;
        $firstBatch = null;

        foreach ($batchesInput as $row) {
            $hasContent = false;
            foreach (['batch', 'mrp_price', 'qty', 'product_exp_date', 'manufacturing_date', 'coa', 'is_non_batch'] as $field) {
                if ($field === 'is_non_batch') {
                    if (!empty($row[$field])) {
                        $hasContent = true;
                        break;
                    }
                    continue;
                }

                if (isset($row[$field]) && (string) $row[$field] !== '') {
                    $hasContent = true;
                    break;
                }
            }
            if (!$hasContent) {
                continue;
            }

            $qty = (int) ($row['qty'] ?? 0);
            $totalQty += $qty;
            $mrpPrice = $row['mrp_price'] ?? null;
            $isNonBatch = !empty($row['is_non_batch']);
            $batchCode = trim((string) ($row['batch'] ?? ''));
            if ($isNonBatch && $batchCode === '') {
                $batchCode = '-';
            }

            $batch = null;
            if (!empty($row['id'])) {
                $batch = $stock->batches()->where('id', (int) $row['id'])->first();
            }
            if (!$batch) {
                $batch = new ProductBatch();
                $batch->product_id = $product->id;
                $batch->product_stock_id = $stock->id;
            }

            $batch->batch            = $batchCode !== '' ? $batchCode : ($batch->batch ?? null);
            $batch->mrp_price        = $mrpPrice;
            $batch->qty              = $qty;
            $batch->scheme           = $this->normalizeStockScheme($row['scheme'] ?? 0);
            $batch->product_exp_date = $isNonBatch ? null : $this->normalizeBatchMonthYearDate($row['product_exp_date'] ?? null, true);
            $batch->manufacturing_date = $isNonBatch ? null : $this->normalizeBatchMonthYearDate($row['manufacturing_date'] ?? null);
            $batch->coa              = $isNonBatch ? null : ($row['coa'] ?? null);
            $batchDiscountData       = $this->extractBatchDiscountData($row);
            $batch->discount_active  = $batchDiscountData['discount_active'];
            $batch->discount_type    = $batchDiscountData['discount_type'];
            $batch->discount         = $batchDiscountData['discount'];
            $batch->discount_start_date = $batchDiscountData['discount_start_date'];
            $batch->discount_end_date = $batchDiscountData['discount_end_date'];

            if (!empty($row['role_price'])) {
                $batch->role_price = is_string($row['role_price']) ? $row['role_price'] : json_encode($row['role_price']);
            } elseif (!$batch->role_price && !empty($mrpPrice) && function_exists('generateRoleBasedPrices')) {
                $batch->role_price = generateRoleBasedPrices((float) 0);
            }

            $batch->save();

            if ($firstBatch === null) {
                $firstBatch = $batch;
            }
        }

        if ($totalQty > 0) {
            $stock->qty = $totalQty;
        }

        if ($firstBatch !== null) {
            $stock->mrp_price        = $firstBatch->mrp_price;
            $stock->product_exp_date  = $firstBatch->product_exp_date;
            $stock->coa               = $firstBatch->coa;
        }

        $stock->save();
        $product->save();
    }

    protected function dispatchRestockNotifications(Product $product, array $restockedVariants): void
    {
        $variantNames = array_values(array_unique($restockedVariants));
        $notificationType = NotificationType::where('type', 'product_restock')->first();
        if (!$notificationType) {
            Log::warning('[RestockNotification] notification_type_missing', ['product_id' => $product->id]);
            return;
        }

        $subscriberIds = ProductNotify::where('product_id', $product->id)->pluck('user_id');
        if ($subscriberIds->isEmpty()) {
            Log::info('[RestockNotification] no_subscribers', ['product_id' => $product->id]);
            return;
        }

        $users = User::whereIn('id', $subscriberIds)->get();
        if ($users->isEmpty()) {
            Log::info('[RestockNotification] users_not_found', ['product_id' => $product->id, 'user_ids' => $subscriberIds]);
            return;
        }

        $data = [
            'notification_type_id' => $notificationType->id,
            'product_id'           => $product->id,
            'product_slug'         => $product->slug,
            'product_name'         => $product->name,
            'variant_count'        => count($variantNames),
            'variant_names'        => $variantNames,
            'link'                 => route('product', $product->slug),
        ];

        Log::info('[RestockNotification] dispatch', [
            'product_id'    => $product->id,
            'variant_names' => $variantNames,
            'subscriber_count' => $users->count(),
        ]);

        Notification::send($users, new ProductRestockNotification($data));
    }
}
