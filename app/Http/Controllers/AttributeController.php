<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attribute;
use App\Models\Color;
use App\Models\AttributeTranslation;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Log;
use CoreComponentRepository;
use Str;

use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:view_product_attributes'])->only('index');
        $this->middleware(['permission:edit_product_attribute'])->only('edit');
        $this->middleware(['permission:delete_product_attribute'])->only('destroy');

        $this->middleware(['permission:view_product_attribute_values'])->only('show');
        $this->middleware(['permission:edit_product_attribute_value'])->only('edit_attribute_value');
        $this->middleware(['permission:delete_product_attribute_value'])->only('destroy_attribute_value');

        $this->middleware(['permission:view_colors'])->only('colors');
        $this->middleware(['permission:edit_color'])->only('edit_color');
        $this->middleware(['permission:delete_color'])->only('destroy_color');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        CoreComponentRepository::instantiateShopRepository();
        CoreComponentRepository::initializeCache();
        $attributes = Attribute::with('attribute_values')->orderBy('created_at', 'desc')->paginate(15);
        return view('backend.product.attribute.index', compact('attributes'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attribute = new Attribute;
        $attribute->name = $request->name;
        $attribute->save();

        $attribute_translation = AttributeTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'attribute_id' => $attribute->id]);
        $attribute_translation->name = $request->name;
        $attribute_translation->save();

        flash(translate('Attribute has been inserted successfully'))->success();
        return redirect()->route('attributes.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['attribute'] = Attribute::findOrFail($id);
        $data['all_attribute_values'] = AttributeValue::with('attribute')->where('attribute_id', $id)->get();

        // echo '<pre>';print_r($data['all_attribute_values']);die;

        return view("backend.product.attribute.attribute_value.index", $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $lang      = $request->lang;
        $attribute = Attribute::findOrFail($id);
        return view('backend.product.attribute.edit', compact('attribute','lang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);
        if($request->lang == env("DEFAULT_LANGUAGE")){
          $attribute->name = $request->name;
        }
        $attribute->save();

        $attribute_translation = AttributeTranslation::firstOrNew(['lang' => $request->lang, 'attribute_id' => $attribute->id]);
        $attribute_translation->name = $request->name;
        $attribute_translation->save();

        flash(translate('Attribute has been updated successfully'))->success();
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);

        foreach ($attribute->attribute_translations as $key => $attribute_translation) {
            $attribute_translation->delete();
        }

        Attribute::destroy($id);
        flash(translate('Attribute has been deleted successfully'))->success();
        return redirect()->route('attributes.index');

    }

    public function store_attribute_value(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'value' => [
                'required',
                'regex:/^[a-zA-Z0-9.–&_]+$/u', // Updated regex to allow en dash, dot, ampersand
            ],
        ]);

        if ($validator->fails()) {
            flash(translate('Only letters, numbers, en dash (–), underscore (_), and ampersand (&) are allowed. No spaces or hyphens (-).'))->error();
            return back();
        }

        $attribute_value = new AttributeValue;
        $attribute_value->attribute_id = $request->attribute_id;
        $attribute_value->value = ucfirst($request->value);
        $attribute_value->save();

        flash(translate('Attribute value has been inserted successfully'))->success();
        return redirect()->route('attributes.show', $request->attribute_id);
    }

    public function edit_attribute_value(Request $request, $id)
    {
        $attribute_value = AttributeValue::findOrFail($id);
        return view("backend.product.attribute.attribute_value.edit", compact('attribute_value'));
    }

    public function update_attribute_value(Request $request, $id)
    {
        Log::info('Update attribute value request received', [
            'attribute_value_id' => $id,
            'payload' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'value' => [
                'required',
                'regex:/^[a-zA-Z0-9.–&_]+$/u',
            ],
        ]);

        if ($validator->fails()) {
            Log::warning('Attribute value validation failed', [
                'errors' => $validator->errors()->toArray(),
            ]);

            flash(translate('Only letters, numbers, en dash (–), underscore (_), and ampersand (&) are allowed. No spaces or hyphens (-).'))->error();
            return back();
        }

        $attribute_value = AttributeValue::findOrFail($id);
        $oldValue = $attribute_value->value;

        Log::info('Attribute value found', [
            'attribute_id' => $attribute_value->attribute_id,
            'old_value' => $oldValue,
        ]);

        $attribute_value->attribute_id = $request->attribute_id;
        $attribute_value->value = ucfirst($request->value);
        $attribute_value->save();

        Log::info('Attribute value updated', [
            'new_value' => $attribute_value->value,
        ]);

        if ($oldValue !== $attribute_value->value) {
            Log::info('Attribute value changed, syncing usages', [
                'old_value' => $oldValue,
                'new_value' => $attribute_value->value,
            ]);

            $this->renameAttributeValueUsage($attribute_value, $oldValue);
        }

        flash(translate('Attribute value has been updated successfully'))->success();
        return back();
    }


    private function renameAttributeValueUsage(AttributeValue $attributeValue, string $oldValue): void
    {
        $newValue = $attributeValue->value;

        Log::info('Starting attribute value rename propagation', [
            'attribute_id' => $attributeValue->attribute_id,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);

        Product::whereNotNull('choice_options')
            ->where('choice_options', 'like', '%' . $oldValue . '%')
            ->chunkById(100, function ($products) use ($attributeValue, $oldValue, $newValue) {

                Log::debug('Processing product chunk', [
                    'count' => $products->count(),
                ]);

                foreach ($products as $product) {
                    Log::debug('Checking product', [
                        'product_id' => $product->id,
                    ]);

                    $choiceOptions = json_decode($product->choice_options, true);

                    if (!is_array($choiceOptions)) {
                        Log::warning('Invalid choice_options JSON', [
                            'product_id' => $product->id,
                        ]);
                        continue;
                    }

                    $updated = false;

                    foreach ($choiceOptions as &$option) {
                        $attributeId = $option['attribute_id'] ?? ($option['attribute_at'] ?? null);

                        if ($attributeId !== $attributeValue->attribute_id) {
                            continue;
                        }

                        if (!empty($option['values']) && is_array($option['values'])) {
                            foreach ($option['values'] as &$value) {
                                if ($value === $oldValue) {
                                    $value   = $newValue;
                                    $updated = true;

                                    Log::debug('Choice option value replaced', [
                                        'product_id' => $product->id,
                                        'old_value' => $oldValue,
                                        'new_value' => $newValue,
                                    ]);
                                }
                            }
                            unset($value);
                        }
                    }
                    unset($option);

                    if ($updated) {
                        $product->choice_options = json_encode($choiceOptions, JSON_UNESCAPED_UNICODE);
                        $product->save();

                        Log::info('Product choice_options updated', [
                            'product_id' => $product->id,
                        ]);

                        $product->stocks()
                            ->where('variant', 'like', '%' . $oldValue . '%')
                            ->get()
                            ->each(function ($stock) use ($oldValue, $newValue) {
                                Log::debug('Updating stock variant', [
                                    'stock_id' => $stock->id,
                                    'old_variant' => $stock->variant,
                                ]);

                                $stock->variant = str_replace($oldValue, $newValue, $stock->variant);
                                $stock->save();
                            });

                        Cart::where('product_id', $product->id)
                            ->whereNotNull('variation')
                            ->where('variation', 'like', '%' . $oldValue . '%')
                            ->chunkById(100, function ($carts) use ($oldValue, $newValue) {

                                Log::debug('Updating cart variations', [
                                    'count' => $carts->count(),
                                ]);

                                foreach ($carts as $cart) {
                                    $cart->variation = str_replace($oldValue, $newValue, $cart->variation);
                                    $cart->save();
                                }
                            });
                    }
                }
            });

        Log::info('Finished attribute value rename propagation');
    }



    public function destroy_attribute_value($id)
    {
        $attribute_values = AttributeValue::findOrFail($id);
        AttributeValue::destroy($id);
        
        flash(translate('Attribute value has been deleted successfully'))->success();
        return redirect()->route('attributes.show', $attribute_values->attribute_id);

    }
    
    public function colors(Request $request) {
        $sort_search = null;
        $colors = Color::orderBy('created_at', 'desc');

        if ($request->search != null){
            $colors = $colors->where('name', 'like', '%'.$request->search.'%');
            $sort_search = $request->search;
        }
        $colors = $colors->paginate(10);

        return view('backend.product.color.index', compact('colors', 'sort_search'));
    }
    
    public function store_color(Request $request) {
        $request->validate([
            'name' => 'required',
            'code' => 'required|unique:colors|max:255',
        ]);
        $color = new Color;
        $color->name = Str::replace(' ', '', $request->name);
        $color->code = $request->code;
        
        $color->save();

        flash(translate('Color has been inserted successfully'))->success();
        return redirect()->route('colors');
    }
    
    public function edit_color(Request $request, $id)
    {
        $color = Color::findOrFail($id);
        return view('backend.product.color.edit', compact('color'));
    }

    /**
     * Update the color.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update_color(Request $request, $id)
    {
        $color = Color::findOrFail($id);

        $request->validate([
            'code' => 'required|unique:colors,code,'.$color->id,
        ]);
        
        $color->name = Str::replace(' ', '', $request->name);
        $color->code = $request->code;
        
        $color->save();

        flash(translate('Color has been updated successfully'))->success();
        return back();
    }
    
    public function destroy_color($id)
    {
        Color::destroy($id);
        
        flash(translate('Color has been deleted successfully'))->success();
        return redirect()->route('colors');

    }
    
}
