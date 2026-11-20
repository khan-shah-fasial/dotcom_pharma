<?php

namespace Tests\Unit;

use App\Support\BluedartRateCard as Card;
use PHPUnit\Framework\TestCase;

class BluedartRateCardTest extends TestCase
{
    protected array $card = [
        'zones' => [
            'local' => ['first' => 40, 'additional' => 30],
            'state' => ['first' => 50, 'additional' => 40],
            'metro' => ['first' => 60, 'additional' => 50],
            'rest' => ['first' => 80, 'additional' => 70],
            'special' => ['first' => 120, 'additional' => 100],
        ],
        'fuel_pct' => 10,
        'cod_flat' => 50,
        'cod_pct' => 2,
        'gst_pct' => 18,
    ];

    public function test_zones_from_pincodes(): void
    {
        $this->assertSame('local', Card::zone('400070', '400072'));
        $this->assertSame('state', Card::zone('400070', '411001'));   // Pune: same circle wins over metro
        $this->assertSame('metro', Card::zone('400070', '110001'));   // Delhi
        $this->assertSame('rest', Card::zone('400070', '302001'));    // Jaipur
        $this->assertSame('special', Card::zone('400070', '781001')); // Guwahati
        $this->assertSame('special', Card::zone('400070', '190001')); // Srinagar
        $this->assertSame('special', Card::zone('400070', '744101')); // Port Blair
    }

    public function test_weight_is_charged_in_500g_slabs(): void
    {
        $this->assertSame(1, Card::price($this->card, 'metro', 0.5, false, 0)['slabs']);
        $this->assertSame(2, Card::price($this->card, 'metro', 0.51, false, 0)['slabs']);
        $this->assertSame(3, Card::price($this->card, 'metro', 1.5, false, 0)['slabs']);
    }

    public function test_price_adds_fuel_and_gst(): void
    {
        // metro, 1.2 kg -> 3 slabs: 60 + 2 x 50 = 160; fuel 16; GST 18% of 176 = 31.68; total 207.68
        $q = Card::price($this->card, 'metro', 1.2, false, 0);

        $this->assertSame(160.0, $q['freight']);
        $this->assertSame(16.0, $q['fuel']);
        $this->assertSame(0.0, $q['cod_fee']);
        $this->assertSame(207.68, $q['total']);
    }

    public function test_cod_fee_is_higher_of_flat_and_percent(): void
    {
        $this->assertSame(50.0, Card::price($this->card, 'local', 0.5, true, 1000)['cod_fee']);  // 2% = 20 < 50
        $this->assertSame(100.0, Card::price($this->card, 'local', 0.5, true, 5000)['cod_fee']); // 2% = 100
    }

    public function test_missing_zone_rate_returns_null(): void
    {
        $this->assertNull(Card::price(['zones' => []], 'metro', 1, false, 0));
        $this->assertNull(Card::price(['zones' => ['metro' => ['first' => '']]], 'metro', 1, false, 0));
    }
}
