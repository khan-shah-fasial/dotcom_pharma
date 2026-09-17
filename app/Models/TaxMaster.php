<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class TaxMaster extends Model
{
    public const KINDS = [
        'taxable' => 'Taxable',
        'inclusive' => 'Inclusive',
        'exempted' => 'Exempted',
    ];

    public const EPSILON = 0.01;

    public const PERMISSIONS = [
        'view_all_tax_masters',
        'add_tax_master',
        'edit_tax_master',
        'delete_tax_master',
    ];

    protected $table = 'tax_masters';

    protected $fillable = [
        'kind',
        'tax_code',
        'description',
        'purchase_tax',
        'purchase_cgst',
        'purchase_sgst',
        'purchase_igst',
        'sale_same_as_purchase',
        'sale_tax',
        'sale_cgst',
        'sale_sgst',
        'sale_igst',
        'status',
    ];

    protected $casts = [
        'purchase_tax' => 'float',
        'purchase_cgst' => 'float',
        'purchase_sgst' => 'float',
        'purchase_igst' => 'float',
        'sale_tax' => 'float',
        'sale_cgst' => 'float',
        'sale_sgst' => 'float',
        'sale_igst' => 'float',
        'sale_same_as_purchase' => 'boolean',
        'status' => 'boolean',
    ];

    public static function toDecimal($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) $value, 4);
    }

    public static function isEmptyRate($value): bool
    {
        return $value === null || $value === '';
    }

    public static function splitTotal(float $cgst, float $sgst, float $igst): float
    {
        return round($cgst + $sgst + $igst, 4);
    }

    public static function totalsMatch(float $tax, float $cgst, float $sgst, float $igst): bool
    {
        return abs($tax - self::splitTotal($cgst, $sgst, $igst)) <= self::EPSILON;
    }

    public static function isTruthy($value): bool
    {
        return in_array($value, [true, 1, '1', 'yes', 'y', 'on'], true);
    }

    public static function formatRate($value): string
    {
        $number = self::toDecimal($value);
        $formatted = number_format($number, 4, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    public static function sampleRows(): array
    {
        return [
            [
                'kind' => 'taxable',
                'tax_code' => 'G5',
                'description' => null,
                'purchase_tax' => 5,
                'purchase_cgst' => 2.5,
                'purchase_sgst' => 2.5,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 5,
                'sale_cgst' => 2.5,
                'sale_sgst' => 2.5,
                'sale_igst' => 0,
                'status' => true,
            ],
            [
                'kind' => 'taxable',
                'tax_code' => 'G18',
                'description' => null,
                'purchase_tax' => 18,
                'purchase_cgst' => 9,
                'purchase_sgst' => 9,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 18,
                'sale_cgst' => 9,
                'sale_sgst' => 9,
                'sale_igst' => 0,
                'status' => true,
            ],
            [
                'kind' => 'taxable',
                'tax_code' => 'G28',
                'description' => null,
                'purchase_tax' => 28,
                'purchase_cgst' => 0,
                'purchase_sgst' => 0,
                'purchase_igst' => 28,
                'sale_same_as_purchase' => true,
                'sale_tax' => 28,
                'sale_cgst' => 0,
                'sale_sgst' => 0,
                'sale_igst' => 28,
                'status' => true,
            ],
            [
                'kind' => 'exempted',
                'tax_code' => 'EX',
                'description' => 'No Tax',
                'purchase_tax' => 0,
                'purchase_cgst' => 0,
                'purchase_sgst' => 0,
                'purchase_igst' => 0,
                'sale_same_as_purchase' => true,
                'sale_tax' => 0,
                'sale_cgst' => 0,
                'sale_sgst' => 0,
                'sale_igst' => 0,
                'status' => true,
            ],
        ];
    }

    public static function normalize(array $input): array
    {
        $kind = (string) ($input['kind'] ?? '');
        $taxCode = strtoupper(trim((string) ($input['tax_code'] ?? '')));
        $description = trim((string) ($input['description'] ?? ''));
        $same = self::isTruthy($input['sale_same_as_purchase'] ?? false);
        $status = array_key_exists('status', $input)
            ? self::isTruthy($input['status'])
            : true;

        $purchaseTaxEmpty = self::isEmptyRate($input['purchase_tax'] ?? null);
        $saleTaxEmpty = self::isEmptyRate($input['sale_tax'] ?? null);

        $purchase = [
            'purchase_tax' => self::toDecimal($input['purchase_tax'] ?? 0),
            'purchase_cgst' => self::toDecimal($input['purchase_cgst'] ?? 0),
            'purchase_sgst' => self::toDecimal($input['purchase_sgst'] ?? 0),
            'purchase_igst' => self::toDecimal($input['purchase_igst'] ?? 0),
        ];

        if ($kind === 'exempted') {
            $purchase = [
                'purchase_tax' => 0.0,
                'purchase_cgst' => 0.0,
                'purchase_sgst' => 0.0,
                'purchase_igst' => 0.0,
            ];
            $same = true;
            $purchaseTaxEmpty = false;
            $saleTaxEmpty = false;
        }

        $purchaseTotal = self::splitTotal(
            $purchase['purchase_cgst'],
            $purchase['purchase_sgst'],
            $purchase['purchase_igst']
        );

        if ($purchaseTaxEmpty) {
            $purchase['purchase_tax'] = $purchaseTotal;
        }

        if ($same) {
            $sale = [
                'sale_tax' => $purchase['purchase_tax'],
                'sale_cgst' => $purchase['purchase_cgst'],
                'sale_sgst' => $purchase['purchase_sgst'],
                'sale_igst' => $purchase['purchase_igst'],
            ];
        } else {
            $sale = [
                'sale_tax' => self::toDecimal($input['sale_tax'] ?? 0),
                'sale_cgst' => self::toDecimal($input['sale_cgst'] ?? 0),
                'sale_sgst' => self::toDecimal($input['sale_sgst'] ?? 0),
                'sale_igst' => self::toDecimal($input['sale_igst'] ?? 0),
            ];

            $saleTotal = self::splitTotal(
                $sale['sale_cgst'],
                $sale['sale_sgst'],
                $sale['sale_igst']
            );

            if ($saleTaxEmpty) {
                $sale['sale_tax'] = $saleTotal;
            }
        }

        return array_merge([
            'kind' => $kind,
            'tax_code' => $taxCode,
            'description' => $description === '' ? null : $description,
            'sale_same_as_purchase' => $same,
            'status' => $status,
        ], $purchase, $sale);
    }

    public static function schemaBlueprint(Blueprint $table): void
    {
        $table->id();
        $table->string('kind', 20)->index();
        $table->string('tax_code', 20)->unique();
        $table->string('description')->nullable();
        $table->decimal('purchase_tax', 12, 4)->default(0);
        $table->decimal('purchase_cgst', 12, 4)->default(0);
        $table->decimal('purchase_sgst', 12, 4)->default(0);
        $table->decimal('purchase_igst', 12, 4)->default(0);
        $table->boolean('sale_same_as_purchase')->default(true);
        $table->decimal('sale_tax', 12, 4)->default(0);
        $table->decimal('sale_cgst', 12, 4)->default(0);
        $table->decimal('sale_sgst', 12, 4)->default(0);
        $table->decimal('sale_igst', 12, 4)->default(0);
        $table->boolean('status')->default(true)->index();
        $table->timestamps();
    }

    public static function seedSamples(): void
    {
        if (!Schema::hasTable('tax_masters')) {
            return;
        }

        foreach (self::sampleRows() as $row) {
            $values = $row;
            if (Schema::hasColumn('tax_masters', 'created_at')) {
                $values['created_at'] = $values['created_at'] ?? now();
                $values['updated_at'] = $values['updated_at'] ?? now();
            }

            DB::table('tax_masters')->updateOrInsert(
                ['tax_code' => $row['tax_code']],
                $values
            );
        }
    }

    public static function ensureTable(): bool
    {
        static $ready = false;
        if ($ready) {
            return Schema::hasTable('tax_masters');
        }

        if (!Schema::hasTable('tax_masters')) {
            Schema::create('tax_masters', function (Blueprint $table) {
                self::schemaBlueprint($table);
            });
            self::seedSamples();
        }

        $ready = Schema::hasTable('tax_masters');

        return $ready;
    }

    public static function ensurePermissions(): void
    {
        static $ready = false;
        if ($ready || !Schema::hasTable('permissions')) {
            $ready = $ready || !Schema::hasTable('permissions');
            return;
        }

        foreach (self::PERMISSIONS as $name) {
            $values = [
                'name' => $name,
                'section' => 'setup_configurations',
            ];

            if (Schema::hasColumn('permissions', 'guard_name')) {
                $values['guard_name'] = 'web';
            }

            if (Schema::hasColumn('permissions', 'created_at')) {
                $values['created_at'] = now();
                $values['updated_at'] = now();
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                $values
            );
        }

        $sourcePermissionId = DB::table('permissions')
            ->where('name', 'vat_&_tax_setup')
            ->value('id');

        if ($sourcePermissionId) {
            foreach (self::PERMISSIONS as $name) {
                $newPermissionId = DB::table('permissions')
                    ->where('name', $name)
                    ->value('id');

                if ($newPermissionId) {
                    self::copyRoleAssignments((int) $sourcePermissionId, (int) $newPermissionId);
                    self::copyDirectAssignments((int) $sourcePermissionId, (int) $newPermissionId);
                }
            }
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $ready = true;
    }

    private static function copyRoleAssignments(int $sourcePermissionId, int $newPermissionId): void
    {
        if (!Schema::hasTable('role_has_permissions')) {
            return;
        }

        $rows = DB::table('role_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['role_id'])
            ->map(fn ($row) => [
                'permission_id' => $newPermissionId,
                'role_id' => $row->role_id,
            ])
            ->all();

        if (!empty($rows)) {
            DB::table('role_has_permissions')->insertOrIgnore($rows);
        }
    }

    private static function copyDirectAssignments(int $sourcePermissionId, int $newPermissionId): void
    {
        if (!Schema::hasTable('model_has_permissions')) {
            return;
        }

        $rows = DB::table('model_has_permissions')
            ->where('permission_id', $sourcePermissionId)
            ->get(['model_type', 'model_id'])
            ->map(fn ($row) => [
                'permission_id' => $newPermissionId,
                'model_type' => $row->model_type,
                'model_id' => $row->model_id,
            ])
            ->all();

        if (!empty($rows)) {
            DB::table('model_has_permissions')->insertOrIgnore($rows);
        }
    }

    public function purchaseTotal(): float
    {
        return self::splitTotal((float) $this->purchase_cgst, (float) $this->purchase_sgst, (float) $this->purchase_igst);
    }

    public function saleTotal(): float
    {
        return self::splitTotal((float) $this->sale_cgst, (float) $this->sale_sgst, (float) $this->sale_igst);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? (string) $this->kind;
    }
}
