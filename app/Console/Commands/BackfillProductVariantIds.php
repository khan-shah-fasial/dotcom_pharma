<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\ProductStock;
use App\Utility\ProductUtility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class BackfillProductVariantIds extends Command
{
    protected $signature = 'product-variants:backfill-id-variant';

    protected $description = 'Backfill product_stocks.id_variant and carts.id_variant from existing variant strings.';

    public function handle(): int
    {
        $stockUpdated = 0;
        $stockSkipped = 0;
        $cartUpdated = 0;
        $cartSkipped = 0;
        $pendingStocks = [];
        $pendingCarts = [];

        ProductStock::with('product')
            ->whereNotNull('variant')
            ->where(function ($query) {
                $query->whereNull('id_variant')->orWhere('id_variant', '');
            })
            ->chunkById(100, function ($stocks) use (&$stockUpdated, &$stockSkipped, &$pendingStocks) {
                foreach ($stocks as $stock) {
                    if (!$stock->product || $stock->variant === '') {
                        $stockSkipped++;
                        $pendingStocks[] = $this->pendingStockRow($stock, 'missing product or variant');
                        $this->warn("Skipped stock {$stock->id}: missing product or variant.");
                        continue;
                    }

                    $idVariant = ProductUtility::resolve_id_variant_for_product_variant($stock->product, $stock->variant);
                    if ($idVariant === null) {
                        $stockSkipped++;
                        $pendingStocks[] = $this->pendingStockRow($stock, 'unable to resolve variant from product choice_options');
                        $this->warn("Skipped stock {$stock->id}: unable to resolve {$stock->variant}.");
                        continue;
                    }

                    $stock->id_variant = $idVariant;
                    $stock->save();
                    $stockUpdated++;
                }
            });

        Cart::with('product')
            ->whereNotNull('variation')
            ->where(function ($query) {
                $query->whereNull('id_variant')->orWhere('id_variant', '');
            })
            ->chunkById(100, function ($carts) use (&$cartUpdated, &$cartSkipped, &$pendingCarts) {
                foreach ($carts as $cart) {
                    if (!$cart->product || $cart->variation === '') {
                        $cartSkipped++;
                        $pendingCarts[] = $this->pendingCartRow($cart, 'missing product or variation');
                        $this->warn("Skipped cart {$cart->id}: missing product or variation.");
                        continue;
                    }

                    $stock = ProductStock::where('product_id', $cart->product_id)
                        ->where('variant', $cart->variation)
                        ->whereNotNull('id_variant')
                        ->first();

                    $idVariant = $stock
                        ? $stock->id_variant
                        : ProductUtility::resolve_id_variant_for_product_variant($cart->product, $cart->variation);

                    if ($idVariant === null) {
                        $cartSkipped++;
                        $pendingCarts[] = $this->pendingCartRow($cart, 'unable to resolve variation from stock or product choice_options');
                        $this->warn("Skipped cart {$cart->id}: unable to resolve {$cart->variation}.");
                        continue;
                    }

                    $cart->id_variant = $idVariant;
                    $cart->save();
                    $cartUpdated++;
                }
            });

        $this->info("Backfill complete. Stocks updated: {$stockUpdated}, stocks skipped: {$stockSkipped}, carts updated: {$cartUpdated}, carts skipped: {$cartSkipped}.");
        $this->writePendingReport($pendingStocks, $pendingCarts);

        return self::SUCCESS;
    }

    private function pendingStockRow(ProductStock $stock, string $reason): array
    {
        return [
            'type' => 'stock',
            'product_id' => $stock->product_id,
            'product_name' => optional($stock->product)->name,
            'row_id' => $stock->id,
            'variant' => $stock->variant,
            'reason' => $reason,
        ];
    }

    private function pendingCartRow(Cart $cart, string $reason): array
    {
        return [
            'type' => 'cart',
            'product_id' => $cart->product_id,
            'product_name' => optional($cart->product)->name,
            'row_id' => $cart->id,
            'variant' => $cart->variation,
            'reason' => $reason,
        ];
    }

    private function writePendingReport(array $pendingStocks, array $pendingCarts): void
    {
        $pendingProductIds = collect($pendingStocks)->pluck('product_id')->filter()->unique()->values();
        $this->warn("Pending stock variants: " . count($pendingStocks) . " across " . $pendingProductIds->count() . " product(s).");

        Log::warning('Product variant id_variant backfill pending summary', [
            'pending_stock_variants' => count($pendingStocks),
            'pending_products' => $pendingProductIds->count(),
            'pending_carts' => count($pendingCarts),
        ]);

        if (empty($pendingStocks) && empty($pendingCarts)) {
            return;
        }

        $rows = array_merge($pendingStocks, $pendingCarts);
        $path = storage_path('logs/product_variant_id_backfill_pending_' . now()->format('Ymd_His') . '.csv');
        $handle = fopen($path, 'w');

        fputcsv($handle, ['type', 'product_id', 'product_name', 'row_id', 'variant', 'reason']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['type'],
                $row['product_id'],
                $row['product_name'],
                $row['row_id'],
                $row['variant'],
                $row['reason'],
            ]);
        }

        fclose($handle);

        $this->info("Pending report written to: {$path}");
        Log::warning('Product variant id_variant backfill pending report written', [
            'path' => $path,
        ]);

        collect($pendingStocks)
            ->groupBy('product_id')
            ->each(function ($rows, $productId) {
                $productName = $rows->first()['product_name'] ?: '-';
                $variants = $rows->pluck('variant')->filter()->unique()->values();
                $variantList = $variants->implode(', ');

                $this->line("Product {$productId} ({$productName}): {$rows->count()} pending variant(s)");
                $this->line("  Variants: {$variantList}");

                Log::warning('Product variant id_variant backfill pending product detail', [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'pending_variant_count' => $rows->count(),
                    'variants' => $variants->all(),
                ]);
            });
    }
}
