<?php

namespace App\Models;

use App\Traits\PreventDemoModeChanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductStockDimension extends Model
{
    use PreventDemoModeChanges;

    protected $table = 'product_stock_dimensions';

    protected $fillable = [
        'product_stock_id',
        'piece_gross',
        'outer_net',
        'weight_buffer_per_case',
    ];

    public function stock()
    {
        return $this->belongsTo(ProductStock::class, 'product_stock_id');
    }

    public static function ensureTable(): void
    {
        static $ready = false;
        if ($ready) {
            return;
        }

        if (Schema::hasTable('product_stock_dimensions')) {
            $type = Schema::getColumnType('product_stock_dimensions', 'product_stock_id');
            $hasUnique = collect(Schema::getConnection()->select('SHOW INDEX FROM product_stock_dimensions'))
                ->contains(fn ($index) => $index->Column_name === 'product_stock_id' && (int) $index->Non_unique === 0);
            $empty = Schema::getConnection()->table('product_stock_dimensions')->count() === 0;
            $keepTable = ($type === 'integer' && $hasUnique) || !$empty;

            if ($keepTable) {
                static::ensureOuterNetColumn();
                $ready = true;
                return;
            }

            Schema::drop('product_stock_dimensions');
        }

        Schema::create('product_stock_dimensions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('product_stock_id')->unique();
            $table->decimal('piece_gross', 12, 3)->nullable();
            $table->decimal('outer_net', 12, 3)->nullable();
            $table->decimal('weight_buffer_per_case', 12, 3)->nullable();
            $table->timestamps();
        });

        $ready = true;
    }

    protected static function ensureOuterNetColumn(): void
    {
        if (Schema::hasColumn('product_stock_dimensions', 'outer_net')) {
            return;
        }

        Schema::table('product_stock_dimensions', function (Blueprint $table) {
            $table->decimal('outer_net', 12, 3)->nullable()->after('piece_gross');
        });
    }
}
