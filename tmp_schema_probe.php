<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select('SHOW TABLES');
$key = array_keys((array) $tables[0])[0];
foreach ($tables as $t) {
    $name = ((array) $t)[$key];
    if (preg_match('/purchase|supplier|vendor|batch|stock|grn|inward|buy|order_detail|product_tax|tax/i', $name)) {
        echo $name . PHP_EOL;
    }
}

echo "---- product_batches columns ----\n";
foreach (Schema::getColumnListing('product_batches') as $col) {
    echo $col . PHP_EOL;
}

echo "---- product_stocks columns ----\n";
foreach (Schema::getColumnListing('product_stocks') as $col) {
    echo $col . PHP_EOL;
}

echo "---- products sample financial/meta columns ----\n";
$productCols = Schema::getColumnListing('products');
foreach ($productCols as $col) {
    if (preg_match('/price|mrp|cost|purchase|supplier|origin|hsn|hs|tax|scheme|weight|coa|rate/i', $col)) {
        echo $col . PHP_EOL;
    }
}
