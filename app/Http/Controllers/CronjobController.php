<?php
// app/Http/Controllers/CronjobController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CronjobController extends Controller
{
    /**
     * GET /api/cron/update-currencies?token=CRON_TOKEN
     * Fetch INR→USD and INR→AED conversion_rate and update currencies.exchange_rate.
     */
    public function updateCurrencyRates(Request $request)
    {
        // Simple token gate for safety
        $expected = config('services.cron.secret');
        if ($expected && $request->query('token') !== $expected) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        $apiKey = config('services.exchangerate.key');
        $baseUrl = rtrim(config('services.exchangerate.base'), '/');

        if (empty($apiKey)) {
            return response()->json(['status' => 'error', 'message' => 'EXCHANGERATE_API_KEY missing'], 500);
        }

        // Map of pairs to currency row IDs
        $pairs = [
            ['from' => 'INR', 'to' => 'USD', 'currency_id' => 1],
            ['from' => 'INR', 'to' => 'AED', 'currency_id' => 29],
        ];

        $updated = [];
        DB::beginTransaction();

        try {
            foreach ($pairs as $p) {
                $url = "{$baseUrl}/{$apiKey}/pair/{$p['from']}/{$p['to']}";

                $res = Http::timeout(15)->retry(2, 200)->get($url);
                if (!$res->ok()) {
                    throw new \RuntimeException("HTTP {$res->status()} for {$p['from']}/{$p['to']}");
                }

                $json = $res->json();

                // Expected structure: result=success and conversion_rate present
                if (($json['result'] ?? null) !== 'success' || !isset($json['conversion_rate'])) {
                    throw new \RuntimeException("Bad API response for {$p['from']}/{$p['to']}: " . substr(json_encode($json), 0, 300));
                }

                $rate = (float) $json['conversion_rate'];

                DB::table('currencies')
                    ->where('id', $p['currency_id'])
                    ->update([
                        'exchange_rate' => $rate,
                        'updated_at'    => now(),
                    ]);

                $updated[] = [
                    'pair' => "{$p['from']}/{$p['to']}",
                    'currency_id' => $p['currency_id'],
                    'rate' => $rate,
                ];
            }

            DB::commit();

            // Clear Laravel caches (at minimum cache:clear; others optional)
            Artisan::call('cache:clear');
            // Optional deeper clear:
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            Log::info('Cron: currency rates updated', ['updated' => $updated]);

            return response()->json([
                'status'  => 'ok',
                'updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Cron: currency rates update FAILED', [
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function clearCarts(Request $request)
    {
        // Token gate
        $expected = config('services.cron.secret');
        if ($expected && $request->query('token') !== $expected) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        try {
            // ---- Option A: FK-safe bulk delete (recommended default) ----
            // DB::beginTransaction();
            // DB::table('carts')->delete();   // generates: DELETE FROM `carts`
            // DB::commit();

            DB::transaction(function () {
                $chunk = 5000;
                do {
                    $affected = DB::table('carts')->limit($chunk)->delete();
                } while ($affected > 0);
            });

            // ---- Option B: Truncate (fast, but ensure no FKs referencing carts) ----
            // DB::statement('TRUNCATE TABLE carts');

            Log::info('Cron: all carts cleared');

            return response()->json([
                'status' => 'ok',
                'cleared' => true,
                'method' => 'delete' // change to 'truncate' if you enable the truncate variant
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Cron: clear carts FAILED', ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }



}
