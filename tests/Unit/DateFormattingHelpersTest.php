<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;

class DateFormattingHelpersTest extends TestCase
{
    public function test_it_formats_valid_dates(): void
    {
        $this->assertSame('22-07-2026', format_dd_mm_yy('2026-07-22'));
        $this->assertSame('22-07-2026', format_dd_mm_yy(Carbon::parse('2026-07-22')));
    }

    public function test_it_returns_a_placeholder_for_missing_or_invalid_dates(): void
    {
        foreach ([null, '', 'NA', 'N/A', 'null', '-', '0000-00-00', '1970-01-01', 'not-a-date'] as $date) {
            $this->assertSame('-', format_dd_mm_yy($date));
        }
    }
}
