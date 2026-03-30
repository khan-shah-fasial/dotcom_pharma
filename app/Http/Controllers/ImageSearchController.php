<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Search2Controller;

use App\Services\ImageSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

class ImageSearchController extends Controller
{
    public function __construct(protected ImageSearchService $service)
    {
    }

    public function search(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $traceId = (string) \Illuminate\Support\Str::uuid();
        $debugTrace = config('image_search.debug_trace', false);

        $keywords = [];
        $preview  = 'data:' . $request->file('image')->getMimeType() . ';base64,' . base64_encode(file_get_contents($request->file('image')->getRealPath()));
        session()->flash('image_search_preview', $preview);
        session()->flash('image_search_hide_query', true);

        try {
            Log::info('Image search: received upload', [
                'user_id' => optional($request->user())->id,
                'ip'      => $request->ip(),
                'mime'    => $request->file('image')->getMimeType(),
                'size'    => $request->file('image')->getSize(),
                'trace'   => $traceId,
            ]);

            $analysis = $this->service->analyzeUploadedImage($request->file('image'));
            $keywords = $this->service->extractKeywords($analysis);
            Log::info('Image search: extracted keywords', [
                'user_id'  => optional($request->user())->id,
                'keywords' => $keywords,
                'trace'    => $traceId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Image search failed: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        // Direct token-based search (no keyword merge)
        $products = $this->service->searchProductsByTokens($keywords);

        if ($debugTrace) {
            Log::info('Image search: rendering direct results', [
                'trace' => $traceId,
                'product_count' => $products->count(),
            ]);
        }

        // minimal view data to satisfy product_listing_ajax includes
        $viewData = [
            'categories' => collect(),
            'category' => null,
            'category_id' => null,
            'selected_category_id' => null,
            'selected_group_id' => null,
            'groupsTree' => collect(),
            'preloadedChildren' => [],
            'preloadedGroupChildren' => [],
            'groupCounts' => [],
            'categoryCounts' => [],
            'attributes' => [],
            'attributeValueCounts' => [],
            'colors' => [],
            'colorCounts' => [],
            'selected_color' => null,
            'filter_brand_id' => null,
            'filter_role_label' => '',
            'filter_product_origin' => '',
            'filter_brands' => collect(),
            'filter_roles' => [],
            'filter_origins' => [],
            'globalMin' => 0,
            'globalMax' => 0,
            'scopedMin' => 0,
            'scopedMax' => 0,
            'perPage' => $products->count(),
            'totalPages' => 1,
            'currentPage' => 1,
            'total' => $products->count(),
            'ajaxNextPageUrl' => null,
            'min_price' => null,
            'max_price' => null,
            'sort_by' => null,
            'query' => '',
            'drug_name' => '',
            'expandedIds' => [],
            'groupExpandedIds' => [],
            'selected_category_name' => null,
            'selected_group_name' => null,
        ];

        foreach (['colors', 'attributes', 'categories', 'groupsTree', 'filter_brands'] as $key) {
            $viewData[$key] = collect($viewData[$key] ?? []);
        }

        return view('frontend.product_listing_ajax', array_merge($viewData, compact('products')));
    }
}
