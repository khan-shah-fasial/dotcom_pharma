<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class TaxBatchMasterListingTest extends TestCase
{
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

    public function test_tax_master_index_is_sortable_and_filterable(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/tax-masters');
        $response->assertOk();
        $response->assertSee('Tax Master', false);
        $response->assertSee('sort_by=tax_code', false);
        $response->assertSee('Filter Tax Master', false);
        $response->assertSee('Purchase Tax % from', false);
        $response->assertSee('Same as purchase', false);
        $response->assertSee('Date Of Add / Edit from', false);
    }

    public function test_tax_master_sort_and_type_filter_keep_page_ok(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/tax-masters?sort_by=tax_code&sort_dir=asc&kind=taxable&status=1')
            ->assertOk()
            ->assertSee('sort_dir=desc', false);

        $this->actingAs($this->admin())
            ->get('/admin/tax-masters/create')
            ->assertOk()
            ->assertSee('Sale tax same as purchase?', false)
            ->assertSee('Taxable = GST extra', false)
            ->assertSee('Tax % must equal CGST + SGST + IGST.', false);
    }

    public function test_batch_master_index_is_sortable_and_filterable(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/batch-masters');
        $response->assertOk();
        $response->assertSee('Batch / Lot Master', false);
        $response->assertSee('sort_by=sku', false);
        $response->assertSee('Filter Batch / Lot Master', false);
        $response->assertSee('Non-batch', false);
        $response->assertSee('Mfg from', false);
        $response->assertSee('Upload Date from', false);
    }

    public function test_batch_master_sort_filter_and_create_are_ok(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/batch-masters?sort_by=batch_code&sort_dir=asc&status=1&is_non_batch=0')
            ->assertOk();

        $this->actingAs($this->admin())
            ->get('/admin/batch-masters/create')
            ->assertOk()
            ->assertSee('SKU / Product / Full Variant', false)
            ->assertSee('Live product lots and stock qty are not changed', false);
    }

    public function test_vat_and_tax_page_still_loads(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/tax')
            ->assertOk();
    }
}
