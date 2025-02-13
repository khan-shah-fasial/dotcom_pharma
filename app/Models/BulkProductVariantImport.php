<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductStock;
// use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Category; // for verifying related IDs
use App\Models\Brand;
use App\Traits\PreventDemoModeChanges;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithMapping; // Allows custom mapping
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Auth;
use Carbon\Carbon;
use Storage;

// Disable the automatic heading formatting so we get raw header values.
HeadingRowFormatter::default('none');

class BulkProductVariantImport implements ToCollection, WithHeadingRow, ToModel, WithValidation, WithMapping, SkipsOnFailure
{
    use PreventDemoModeChanges, SkipsFailures;

    private $rows = 0;

    /**
     * Map each row from the CSV into an array of internal keys.
     * Note: The description field is processed through formatDescription().
     */
    public function map($row): array
    {
        return [
            'name'              => $this->getMappingValue($row, ['Product Name', 'Name']),
            // Process the description value into HTML.
            'description'       => $this->formatDescription($this->getMappingValue($row, ['Product Description', 'Description'])),
            'category_id'       => $this->getMappingValue($row, ['Category Id', 'Category']),
            'multi_categories'  => $this->getMappingValue($row, ['Categories', 'Multi Categories']),
            'brand_id'          => $this->getMappingValue($row, ['Brand Id', 'Brand']),
            'video_provider'    => $this->getMappingValue($row, ['Video Provider']),
            'video_link'        => $this->getMappingValue($row, ['Video Link']),
            'tags'              => $this->getMappingValue($row, ['Tags']),
            'unit_price'        => $this->getMappingValue($row, ['Price', 'Unit Price']),
            'unit'              => $this->getMappingValue($row, ['Unit']),
            'slug'              => $this->getMappingValue($row, ['Slug']),
            'current_stock'     => $this->getMappingValue($row, ['Stock', 'Current Stock']),
            'est_shipping_days' => $this->getMappingValue($row, ['Shipping Days', 'Est Shipping Days']),
            'sku'               => $this->getMappingValue($row, ['SKU']),
            'meta_title'        => $this->getMappingValue($row, ['Meta Title']),
            'meta_description'  => $this->getMappingValue($row, ['Meta Description']),
            'thumbnail_img'     => $this->getMappingValue($row, ['Thumbnail', 'Thumbnail Img']),
            'photos'            => $this->getMappingValue($row, ['Photos']),
            // Variant fields:
            'price_pts'         => $this->getMappingValue($row, ['Price Pts', 'price_pts', 'PricePts']),
            'sku_pts'           => $this->getMappingValue($row, ['SKU Pts', 'sku_pts']),
            'qty_pts'           => $this->getMappingValue($row, ['Quantity Pts', 'qty_pts']),
            'price_ptr'         => $this->getMappingValue($row, ['Price Ptr', 'price_ptr']),
            'sku_ptr'           => $this->getMappingValue($row, ['SKU Ptr', 'sku_ptr']),
            'qty_ptr'           => $this->getMappingValue($row, ['Quantity Ptr', 'qty_ptr']),
            'price_ptd'         => $this->getMappingValue($row, ['Price Ptd', 'price_ptd']),
            'sku_ptd'           => $this->getMappingValue($row, ['SKU Ptd', 'sku_ptd']),
            'qty_ptd'           => $this->getMappingValue($row, ['Quantity Ptd', 'qty_ptd']),
            'price_gov'         => $this->getMappingValue($row, ['Price Gov', 'price_gov']),
            'sku_gov'           => $this->getMappingValue($row, ['SKU Gov', 'sku_gov']),
            'qty_gov'           => $this->getMappingValue($row, ['Quantity Gov', 'qty_gov']),
            'price_expo'        => $this->getMappingValue($row, ['Price Expo', 'price_expo']),
            'sku_expo'          => $this->getMappingValue($row, ['SKU Expo', 'sku_expo']),
            'qty_expo'          => $this->getMappingValue($row, ['Quantity Expo', 'qty_expo']),
        ];
    }

    /**
     * Helper method to retrieve a value from a row using possible header keys.
     * It normalizes the keys to lowercase for case-insensitive matching.
     */
    protected function getMappingValue($row, array $possibleKeys)
    {
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedRow[strtolower($key)] = $value;
        }
        foreach ($possibleKeys as $key) {
            $keyLower = strtolower($key);
            if (isset($normalizedRow[$keyLower])) {
                return $normalizedRow[$keyLower];
            }
        }
        return null;
    }

    /**
     * Format a product description string into HTML.
     *
     * Convention:
     * - The first block of text (up to the first completely blank line) is treated as table data.
     *   Within this block, each key/value pair is separated by a semicolon (;)
     *   and the key and value are separated by the first colon (:).
     * - Any text after the first blank line is appended as additional description.
     * - Within the table, literal "\n" or actual newlines are replaced by HTML <br> tags.
     *
     * @param string $description Raw description from Excel.
     * @return string HTML markup.
     */
    protected function formatDescription($description)
    {
    // Trim and normalize newlines.
    $description = str_replace(["\r\n", "\r"], "\n", trim($description));
    if (empty($description)) {
        return '';
    }

    // Split the description into blocks by one or more blank lines.
    $blocks = preg_split('/\n\s*\n/', $description);

    // Check if the first block contains a colon.
    if (strpos($blocks[0], ':') !== false) {
        // The first block is considered a table.
        $tableBlock = trim($blocks[0]);
        $html = $this->convertTableBlockToHTML($tableBlock);

        // If there is extra text after the table block, append it as a paragraph.
        if (count($blocks) > 1) {
            $extraText = trim(implode("\n", array_slice($blocks, 1)));
            if (!empty($extraText)) {
                $html .= '<p>' . nl2br(htmlspecialchars($extraText)) . '</p>';
            }
        }
        return $html;
    } else {
        // No colon found in the first block: treat the entire text as free description.
        return '<p>' . nl2br(htmlspecialchars($description)) . '</p>';
    }
    }

    /**
     * Convert a block of key/value pairs into an HTML table.
     *
     * Each pair is separated by a semicolon (;). Within each pair, the key and value
     * are separated by the first colon (:). Literal "\n" and actual newlines in values
     * are replaced with <br>.
     *
     * @param string $tableBlock The raw table block.
     * @return string HTML table markup.
     */
    protected function convertTableBlockToHTML($tableBlock)
    {
        $rows = explode(';', $tableBlock);
        $html = '<table class="table table-bordered"><tbody>';
        foreach ($rows as $row) {
            $row = trim($row);
            if (empty($row)) {
                continue;
            }
            $parts = explode(':', $row, 2);
            if (count($parts) == 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Replace literal "\n" with <br> and convert actual newlines.
                $value = str_replace('\n', '<br>', $value);
                $value = nl2br($value);
                $html .= "<tr><td><strong>" . htmlspecialchars($key) . "</strong></td><td>" . $value . "</td></tr>";
            } else {
                $html .= "<tr><td colspan='2'>" . htmlspecialchars($row) . "</td></tr>";
            }
        }
        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Process each mapped row.
     * (Invalid rows are skipped thanks to the SkipsFailures trait.)
     */
    public function collection(Collection $rows)
    {
        $user = Auth::user();
        $approved = ($user->user_type == 'seller' && get_setting('product_approve_by_admin') == 1) ? 0 : 1;

        foreach ($rows as $row) {
            // Verify related IDs exist (if needed).
            if (!Category::find($row['category_id'])) {
                continue;
            }
            if (!Brand::find($row['brand_id'])) {
                continue;
            }

            // Process image fields.
            $row['thumbnail_img'] = $this->downloadThumbnail($row['thumbnail_img']);
            $row['photos'] = $this->downloadGalleryImages($row['photos']);

            // Prepare product data.
            $productData = [
                'name'              => $row['name'],
                'description'       => $row['description'],
                'added_by'          => $user->user_type == 'seller' ? 'seller' : 'admin',
                'user_id'           => $user->user_type == 'seller' ? $user->id : User::where('user_type', 'admin')->first()->id,
                'approved'          => $approved,
                'category_id'       => $row['category_id'],
                'brand_id'          => $row['brand_id'],
                'video_provider'    => $row['video_provider'],
                'video_link'        => $row['video_link'],
                'tags'              => $row['tags'],
                'unit_price'        => $row['unit_price'],
                'unit'              => $row['unit'],
                'meta_title'        => $row['meta_title'],
                'meta_description'  => $row['meta_description'],
                'est_shipping_days' => $row['est_shipping_days'],
                'colors'            => json_encode([]),
                'choice_options'    => json_encode([]),
                'variations'        => json_encode([]),
                'slug'              => $row['slug'] ? $row['slug'] : preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', strtolower($row['name']))) . '-' . Str::random(5),
                'thumbnail_img'     => $row['thumbnail_img'],
                'photos'            => $row['photos'],
            ];

            // Check for existing product by slug.
            $existingProduct = Product::where('slug', $productData['slug'])->first();
            if ($existingProduct) {
                $existingProduct->update($productData);
                $product = $existingProduct;
                \DB::table('product_categories')->where('product_id', $product->id)->delete();
                if (!empty($row['multi_categories'])) {
                    foreach (explode(',', $row['multi_categories']) as $category_id) {
                        \DB::table('product_categories')->insert([
                            "product_id"  => $product->id,
                            "category_id" => trim($category_id)
                        ]);
                    }
                }
                ProductStock::where('product_id', $product->id)->delete();
            } else {
                $product = Product::create($productData);
                if (!empty($row['multi_categories'])) {
                    foreach (explode(',', $row['multi_categories']) as $category_id) {
                        \DB::table('product_categories')->insert([
                            "product_id"  => $product->id,
                            "category_id" => trim($category_id)
                        ]);
                    }
                }
            }

            // Process variant data.
            if (isset($row['price_pts']) && $row['price_pts'] !== '') {
                $variantKeys = ['pts', 'ptr', 'ptd', 'gov', 'expo'];
                $collectedVariants = [];
                foreach ($variantKeys as $key) {
                    $priceKey = 'price_' . $key;
                    $skuKey   = 'sku_' . $key;
                    $qtyKey   = 'qty_' . $key;
                    if (isset($row[$priceKey]) && $row[$priceKey] !== '' &&
                        isset($row[$qtyKey]) && $row[$qtyKey] !== ''
                    ) {
                        $formattedKey = ucfirst($key);
                        ProductStock::create([
                            'product_id' => $product->id,
                            'price'      => $row[$priceKey],
                            'sku'        => isset($row[$skuKey]) ? $row[$skuKey] : '',
                            'qty'        => $row[$qtyKey],
                            'variant'    => $formattedKey,
                        ]);
                        $collectedVariants[] = $formattedKey;
                    }
                }
                if (count($collectedVariants) > 0) {
                    $product->variant_product = 1;
                    $product->attributes = json_encode(["3"]);
                    $product->choice_options = json_encode([
                        [
                            "attribute_id" => "3",
                            "values"       => $collectedVariants
                        ]
                    ]);
                    $product->save();
                }
            } else {
                ProductStock::create([
                    'product_id' => $product->id,
                    'qty'        => $row['current_stock'],
                    'price'      => $row['unit_price'],
                    'sku'        => $row['sku'],
                    'variant'    => '',
                ]);
            }

            ++$this->rows;
        }
    }

    /**
     * Dummy method to satisfy the ToModel interface.
     */
    public function model(array $row)
    {
        ++$this->rows;
    }

    /**
     * Return the number of valid rows processed.
     */
    public function getRowCount(): int
    {
        return $this->rows;
    }

    /**
     * Closure-based validation rules.
     */
    public function rules(): array
    {
        return [
            // Common product fields.
            '*.name' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The product name field is required.');
                }
            },
            '*.description' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The description field is required.');
                }
            },
            '*.category_id' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The category id field is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('The category id must be numeric.');
                }
            },
            '*.brand_id' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The brand id field is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('The brand id must be numeric.');
                }
            },
            '*.unit_price' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The unit price field is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Unit price is not numeric.');
                }
            },
            '*.slug' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The slug field is required.');
                }
            },
            // Variant fields for "Pts"
            '*.price_pts' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Price for variant Pts is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Price for variant Pts must be numeric.');
                }
            },
            '*.sku_pts' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('SKU for variant Pts is required.');
                }
            },
            '*.qty_pts' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Quantity for variant Pts is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Quantity for variant Pts must be numeric.');
                }
            },
            // Variant fields for "Ptr"
            '*.price_ptr' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Price for variant Ptr is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Price for variant Ptr must be numeric.');
                }
            },
            '*.sku_ptr' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('SKU for variant Ptr is required.');
                }
            },
            '*.qty_ptr' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Quantity for variant Ptr is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Quantity for variant Ptr must be numeric.');
                }
            },
            // Variant fields for "Ptd"
            '*.price_ptd' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Price for variant Ptd is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Price for variant Ptd must be numeric.');
                }
            },
            '*.sku_ptd' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('SKU for variant Ptd is required.');
                }
            },
            '*.qty_ptd' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Quantity for variant Ptd is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Quantity for variant Ptd must be numeric.');
                }
            },
            // Variant fields for "Gov"
            '*.price_gov' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Price for variant Gov is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Price for variant Gov must be numeric.');
                }
            },
            '*.sku_gov' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('SKU for variant Gov is required.');
                }
            },
            '*.qty_gov' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Quantity for variant Gov is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Quantity for variant Gov must be numeric.');
                }
            },
            // Variant fields for "Expo"
            '*.price_expo' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Price for variant Expo is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Price for variant Expo must be numeric.');
                }
            },
            '*.sku_expo' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('SKU for variant Expo is required.');
                }
            },
            '*.qty_expo' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('Quantity for variant Expo is required.');
                } elseif (!is_numeric($value)) {
                    $onFailure('Quantity for variant Expo must be numeric.');
                }
            },
        ];
    }

    /**
     * Process an image field.
     * If the input is empty, returns null.
     * If the input is numeric, returns it directly.
     * Otherwise, treats the input as a URL, downloads the image, and returns the new upload ID.
     */
    public function downloadThumbnail($input)
    {
        if (empty($input)) {
            return null;
        }
        if (is_numeric($input)) {
            return $input;
        }
        try {
            $upload = new \App\Models\Upload;
            $upload->external_link = $input;
            $upload->type = 'image';
            $upload->save();
            return $upload->id;
        } catch (\Exception $e) {
            // Optionally log the error.
        }
        return null;
    }

    /**
     * Process a comma-separated list of image fields.
     * For each value, if numeric returns it directly; otherwise, processes as a URL.
     */
    public function downloadGalleryImages($input)
    {
        if (empty($input)) {
            return '';
        }
        $data = [];
        $parts = explode(',', $input);
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if (empty($trimmed)) {
                continue;
            }
            if (is_numeric($trimmed)) {
                $data[] = $trimmed;
            } else {
                $data[] = $this->downloadThumbnail($trimmed);
            }
        }
        return implode(',', $data);
    }
}
