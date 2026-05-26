<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        $now = now();
        $hasLang = Schema::hasColumn('business_settings', 'lang');
        $hasCreatedAt = Schema::hasColumn('business_settings', 'created_at');
        $hasUpdatedAt = Schema::hasColumn('business_settings', 'updated_at');
        $settings = [
            'delhivery_client_name' => '0f75ab-DOTCOMPHARMA-do-cdp',
            'delhivery_origin_pincode' => '400070',
            'delhivery_price_field' => 'charge_DL',
            'delhivery_shipment_payment_mode' => 'Prepaid',
            'delhivery_send_client_name' => '0',
            'delhivery_shipping_mode' => 'Express',
            'delhivery_address_type' => 'home',
            'delhivery_seller_name' => 'Pharm Vet Easy',
            'delhivery_seller_address' => '',
            'delhivery_order_reference_mode' => 'id',
            'delhivery_seller_gst_tin' => '27ACMPJ7305H1ZI',
            'delhivery_client_gst_tin' => '27AAPCS9575E1ZN',
            'delhivery_default_hsn_code' => '987456645',
            'delhivery_default_charge' => '0',
            'delhivery_pickup_locations' => json_encode([
                [
                    'id' => 1,
                    'is_active' => 1,
                    'name' => 'Ware House-1-Patel Estate',
                    'add' => 'Dotcom Pharma Unit.No.201,2nd Floor,Patel Industrial Estate Opp: Madhukosh Apartment,Near Bachoo Garage, Safedpool Bus Stop Andheri Kurla Road,Sakinaka,Kurla West, Mumbai -400072,Maharashtra,India',
                    'city' => 'Mumbai',
                    'pin_code' => '400070',
                    'country' => 'India',
                    'phone' => '9082511617',
                    'created_at' => $now->toDateTimeString(),
                ],
            ]),
        ];

        foreach ($settings as $type => $value) {
            $values = [
                'value' => $value,
            ];

            if ($hasLang) {
                $values['lang'] = null;
            }

            if ($hasCreatedAt) {
                $values['created_at'] = $now;
            }

            if ($hasUpdatedAt) {
                $values['updated_at'] = $now;
            }

            DB::table('business_settings')->updateOrInsert(
                ['type' => $type],
                $values
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_settings')) {
            return;
        }

        DB::table('business_settings')
            ->whereIn('type', [
                'delhivery_client_name',
                'delhivery_origin_pincode',
                'delhivery_price_field',
                'delhivery_shipment_payment_mode',
                'delhivery_send_client_name',
                'delhivery_shipping_mode',
                'delhivery_address_type',
                'delhivery_seller_name',
                'delhivery_seller_address',
                'delhivery_order_reference_mode',
                'delhivery_seller_gst_tin',
                'delhivery_client_gst_tin',
                'delhivery_default_hsn_code',
                'delhivery_default_charge',
                'delhivery_pickup_locations',
            ])
            ->delete();
    }
};
