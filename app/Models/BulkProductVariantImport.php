<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductCategory;
use App\Models\User;
use App\Traits\PreventDemoModeChanges;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Illuminate\Support\Str;
use Auth;
use Carbon\Carbon;
use Storage;

class BulkProductVariantImport implements ToCollection, WithHeadingRow, WithValidation, ToModel, SkipsOnFailure
{
    use PreventDemoModeChanges, SkipsFailures;

    private $rows = 0;

    public function collection(Collection $rows)
    {
        $canImport = true;
        $user      = Auth::user();

        if ($user->user_type == 'seller' && addon_is_activated('seller_subscription')) {
            if ((count($rows) + $user->products()->count()) > $user->shop->product_upload_limit
                || $user->shop->package_invalid_at == null
                || Carbon::now()->diffInDays(Carbon::parse($user->shop->package_invalid_at), false) < 0
            ) {
                $canImport = false;
                flash(translate('Please upgrade your package.'))->warning();
            }
        }

        if ($canImport) {
            foreach ($rows as $row) {

                $approved = ($user->user_type == 'seller' && get_setting('product_approve_by_admin') == 1) ? 0 : 1;

                // Prepare the common product data.
                $productData = [
                    'name'              => $row['name'],
                    'description'       => $row['description'],
                    'added_by'          => $user->user_type == 'seller' ? 'seller' : 'admin',
                    'user_id'           => $user->user_type == 'seller'
                                            ? $user->id
                                            : User::where('user_type', 'admin')->first()->id,
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
                    // Initially empty arrays for colors, choice_options and variations.
                    'colors'            => json_encode([]),
                    'choice_options'    => json_encode([]),
                    'variations'        => json_encode([]),
                    // Use the provided slug, or generate one.
                    'slug'              => $row['slug']
                                            ? $row['slug']
                                            : preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', strtolower($row['name']))) . '-' . Str::random(5),
                    'thumbnail_img'     => $this->downloadThumbnail($row['thumbnail_img']),
                    'photos'            => $this->downloadGalleryImages($row['photos']),
                ];

                // Check if a product with the same slug exists.
                $existingProduct = Product::where('slug', $productData['slug'])->first();

                if ($existingProduct) {
                    // Update existing product.
                    $existingProduct->update(attributes: $productData);
                    $product = $existingProduct;

                    // Update categories: remove existing categories and re-attach new ones.
                    \DB::table('product_categories')->where('product_id', $product->id)->delete();
                    if (!empty($row['multi_categories'])) {
                        foreach (explode(',', $row['multi_categories']) as $category_id) {
                            \DB::table('product_categories')->insert([
                                "product_id"  => $product->id,
                                "category_id" => trim($category_id)
                            ]);
                        }
                    }

                    // Remove existing product stocks.
                    ProductStock::where('product_id', $product->id)->delete();
                } else {
                    // Create a new product.
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

                /**
                 * Check for variant data.
                 * We assume that if the "price_pts" column is not empty, then the product is a variant product.
                 *
                 * Note: Because of the default heading row formatter, the keys are lowercased.
                 */
                if (isset($row['price_pts']) && $row['price_pts'] !== '') {
                    // Define the fixed variant keys (in lowercase).
                    $variantKeys = ['pts', 'ptr', 'ptd', 'gov', 'expo'];
                    $collectedVariants = [];

                    foreach ($variantKeys as $key) {
                        $priceKey = 'price_' . $key;
                        $skuKey   = 'sku_' . $key;
                        $qtyKey   = 'qty_' . $key;
                        // We ignore variant image columns (if any) – they remain null.
                        if (
                            isset($row[$priceKey]) && $row[$priceKey] !== '' &&
                            isset($row[$qtyKey]) && $row[$qtyKey] !== ''
                        ) {

                            // Format the variant key with only the first letter capitalized.
                            $formattedKey = ucfirst($key);

                            ProductStock::create([
                                'product_id' => $product->id,
                                'price'      => $row[$priceKey],
                                'sku'        => isset($row[$skuKey]) ? $row[$skuKey] : '',
                                'qty'        => $row[$qtyKey],
                                // Save the variant identifier as uppercase (e.g. "Pts")
                                'variant'    => $formattedKey,
                            ]);
                            $collectedVariants[] = $formattedKey;
                        }
                    }

                    // If any variants were found, automatically set the attribute and choice_options fields.
                    if (count($collectedVariants) > 0) {
                        //Enable variant product
                        $product->variant_product = 1;
                        // Set the "attributes" column to ["3"]
                        $product->attributes = json_encode(["3"]);
                        // Set "choice_options" to the required JSON structure.
                        $product->choice_options = json_encode([
                            [
                                "attribute_id" => "3",
                                "values"       => $collectedVariants
                            ]
                        ]);
                        $product->save();
                    }
                } else {
                    // Otherwise, create a single (default) stock entry.
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
    }

    public function model(array $row)
    {
        ++$this->rows;
    }

    public function getRowCount(): int
    {
        return $this->rows;
    }

    public function rules(): array
    {
        return [
            // Common product fields
            '*.name' => function ($attribute, $value, $onFailure) {
                if (empty($value)) {
                    $onFailure('The name field is required.');
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

    public function downloadThumbnail($url)
    {
        try {
            $upload = new \App\Models\Upload;
            $upload->external_link = $url;
            $upload->type = 'image';
            $upload->save();

            return $upload->id;
        } catch (\Exception $e) {
            // Optionally log the error.
        }
        return null;
    }

    public function downloadGalleryImages($urls)
    {
        $data = [];
        foreach (explode(',', str_replace(' ', '', $urls)) as $url) {
            $data[] = $this->downloadThumbnail($url);
        }
        return implode(',', $data);
    }
}
