<?php
// app/Http/Controllers/CronjobController.php

namespace App\Http\Controllers;

use App\Services\ExchangeRatesApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CronjobController extends Controller
{
    /**
     * GET /cron/update-currencies?token=CRON_TOKEN
     * Fetch INR→USD and INR→AED conversion_rate and update currencies.exchange_rate.
     */
    public function updateCurrencyRates(Request $request, ExchangeRatesApiService $service)
    {
        $expected = config('services.cron.secret');
        if ($expected && $request->query('token') !== $expected) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $service->refresh();
            Log::info('Cron: currency and country forex rates updated', $result);

            return response()->json([
                'status'  => 'ok',
                'updated' => $result,
            ]);
        } catch (\Throwable $e) {
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
