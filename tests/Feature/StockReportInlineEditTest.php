<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\ProductBatch;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StockReportInlineEditTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['HTTP_HOST'] = '127.0.0.1:8000';
        $_SERVER['SERVER_NAME'] = '127.0.0.1';
        $this->withServerVariables([
            'HTTP_HOST' => '127.0.0.1:8000',
            'SERVER_NAME' => '127.0.0.1',
            'SERVER_PORT' => '8000',
        ]);
    }

    private function admin(): User
    {
        $admin = User::where('user_type', 'admin')->first();
        $this->assertNotNull($admin, 'An admin user is required for this test.');

        return $admin;
    }

    private function customer(): User
    {
        $customer = User::where('user_type', 'customer')->first();
        $this->assertNotNull($customer, 'A customer user is required for this test.');

        return $customer;
    }

    private function sampleBatch(): ProductBatch
    {
        $batch = ProductBatch::with('stock')->orderBy('id')->first();
        $this->assertNotNull($batch, 'A product batch is required for this test.');

        return $batch;
    }

    private function actingAsAdminWithoutCsrf()
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        return $this->actingAs($this->admin());
    }

    public function test_guest_is_redirected_from_stock_report(): void
    {
        $this->get(route('stock_report.index'))
            ->assertRedirect();

        $this->post(route('stock_report.update_batch'), [
            'batch_id' => 1,
            'field' => 'qty',
            'value' => 1,
        ])->assertRedirect();
    }

    public function test_customer_cannot_open_or_update_stock_report(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $batch = $this->sampleBatch();

        $this->actingAs($this->customer())
            ->get(route('stock_report.index'))
            ->assertNotFound();

        $this->actingAs($this->customer())
            ->post(route('stock_report.update_batch'), [
                'batch_id' => $batch->id,
                'field' => 'qty',
                'value' => 99,
            ])
            ->assertNotFound();
    }

    public function test_admin_stock_report_page_renders_inline_editors(): void
    {
        $response = $this->actingAsAdminWithoutCsrf()
            ->get(route('stock_report.index'));

        $response->assertOk();
        $response->assertSee('Product wise stock report', false);
        $response->assertSee('stock-inline-input', false);
        $response->assertSee('data-field="qty"', false);
        $response->assertSee('data-field="batch"', false);
        $response->assertSee('data-field="mrp_price"', false);
        $response->assertSee('data-field="pts"', false);
        $response->assertSee(route('stock_report.update_batch'), false);
    }

    public function test_admin_can_update_qty_and_sync_variant_stock_total(): void
    {
        $batch = $this->sampleBatch();
        $stockId = $batch->product_stock_id;
        $originalQty = (int) $batch->qty;

        $response = $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'qty',
            'value' => $originalQty + 3,
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'qty' => $originalQty + 3,
        ]);

        $batch->refresh();
        $this->assertSame($originalQty + 3, (int) $batch->qty);

        $expectedStockQty = (int) ProductBatch::where('product_stock_id', $stockId)->sum('qty');
        $this->assertSame($expectedStockQty, (int) ProductStock::find($stockId)->qty);
    }

    public function test_admin_can_update_batch_mrp_role_price_and_dates(): void
    {
        $batch = $this->sampleBatch();
        $originalRole = is_array($batch->role_price)
            ? $batch->role_price
            : json_decode((string) $batch->role_price, true);
        $originalRole = is_array($originalRole) ? $originalRole : [];
        $originalPtr = $originalRole['ptr'] ?? null;

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'batch',
            'value' => 'QA-LOT-1',
        ])->assertOk()->assertJson(['success' => true]);

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'mrp_price',
            'value' => '123.456',
        ])->assertOk()->assertJsonPath('display', '123.46');

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'pts',
            'value' => '55.5',
        ])->assertOk();

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'manufacturing_date',
            'value' => '2026-01',
        ])->assertOk();

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'product_exp_date',
            'value' => '2026-02',
        ])->assertOk();

        $batch->refresh();
        $this->assertSame('QA-LOT-1', $batch->batch);
        $this->assertEquals(123.46, (float) $batch->mrp_price);
        $this->assertSame('2026-01-01', substr((string) $batch->manufacturing_date, 0, 10));
        $this->assertSame('2026-02-28', substr((string) $batch->product_exp_date, 0, 10));

        $role = is_array($batch->role_price)
            ? $batch->role_price
            : json_decode((string) $batch->role_price, true);
        $this->assertEquals(55.5, (float) $role['pts']);
        if ($originalPtr !== null) {
            $this->assertEquals((float) $originalPtr, (float) $role['ptr']);
        }
    }

    public function test_invalid_updates_are_rejected_without_changing_data(): void
    {
        $batch = $this->sampleBatch();
        $originalQty = (int) $batch->qty;
        $originalBatch = $batch->batch;

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'sku',
            'value' => 'HACK',
        ])->assertStatus(422);

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'qty',
            'value' => '-1',
        ])->assertStatus(422);

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'batch',
            'value' => '   ',
        ])->assertStatus(422);

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => $batch->id,
            'field' => 'pts',
            'value' => 'abc',
        ])->assertStatus(422);

        $this->actingAsAdminWithoutCsrf()->post(route('stock_report.update_batch'), [
            'batch_id' => 99999999,
            'field' => 'qty',
            'value' => 1,
        ])->assertStatus(404);

        $batch->refresh();
        $this->assertSame($originalQty, (int) $batch->qty);
        $this->assertSame($originalBatch, $batch->batch);
    }

    public function test_product_and_price_list_still_loads(): void
    {
        $this->actingAsAdminWithoutCsrf()
            ->get(route('product_detail_report.index'))
            ->assertOk()
            ->assertSee('Product And Price List', false);
    }
}
