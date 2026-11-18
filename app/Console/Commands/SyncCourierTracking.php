<?php

namespace App\Console\Commands;

use App\Models\OrderShipment;
use App\Models\ShippingMethod;
use Illuminate\Console\Command;

/**
 * Refreshes courier status on open shipments and moves the order's delivery
 * status forward (picked up / on the way / delivered). Never moves it back;
 * returns and cancellations are left to admin.
 */
class SyncCourierTracking extends Command
{
    protected $signature = 'couriers:sync-tracking {--limit=200 : Maximum shipments per run} {--stale=120 : Minutes since last sync}';

    protected $description = 'Refresh courier tracking status for open shipments';

    /** Courier statuses that will not change any more. */
    protected const FINAL_STATUSES = ['delivered', 'rto delivered', 'returned', 'cancelled', 'canceled', 'lost'];

    public function handle(): int
    {
        $slugs = ShippingMethod::pluck('slug', 'id');
        $synced = 0;
        $failed = 0;

        $shipments = OrderShipment::query()
            ->where('status', 'created')
            ->whereNotNull('shipping_id')
            ->where('shipping_id', '!=', '')
            ->where(function ($q) {
                $q->whereNull('courier_status')
                    ->orWhereRaw('LOWER(courier_status) NOT IN (' . implode(',', array_fill(0, count(self::FINAL_STATUSES), '?')) . ')', self::FINAL_STATUSES);
            })
            ->where(function ($q) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<', now()->subMinutes((int) $this->option('stale')));
            })
            ->orderByRaw('last_synced_at IS NOT NULL, last_synced_at')
            ->limit((int) $this->option('limit'))
            ->get();

        foreach ($shipments as $shipment) {
            $slug = strtolower((string) ($slugs[$shipment->shipping_method_id] ?? ''));
            $class = 'App\\Http\\Controllers\\Shipment\\' . ucfirst($slug) . 'Controller';

            if ($slug === '' || !class_exists($class) || !method_exists($class, 'track')) {
                continue;
            }

            try {
                $result = app($class)->track($shipment);
            } catch (\Throwable $e) {
                $result = ['success' => false, 'message' => $e->getMessage()];
            }

            if (!empty($result['success'])) {
                $synced++;
            } else {
                $failed++;
                $this->warn("{$slug} {$shipment->shipping_id}: " . ($result['message'] ?? 'failed'));
            }
        }

        $this->info("Synced {$synced}, failed {$failed}.");

        return self::SUCCESS;
    }
}
