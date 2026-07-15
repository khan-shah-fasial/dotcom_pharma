<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Address;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\CouponUsage;
use App\Models\Coupon;
use App\Models\User;
use App\Models\CombinedOrder;
use App\Models\Country;
use App\Models\SmsTemplate;
use App\Models\ProductBatch;
use App\Models\BookedTo;
use App\Models\LocalDeliveryPartner;
use App\Models\ShippingMethod;
use App\Models\Transport;
use Auth;
use Mail;
use App\Mail\InvoiceEmailManager;
use App\Models\OrdersExport;
use App\Utility\NotificationUtility;
use CoreComponentRepository;
use App\Utility\SmsUtility;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Notification;
use App\Notifications\OrderNotification;
use App\Utility\EmailUtility;
use Illuminate\Support\Facades\Session;
use App\Services\OrderPlacementService;
use App\Services\WalletRewardService;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{

    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_orders|view_inhouse_orders|view_seller_orders|view_pickup_point_orders|view_all_offline_payment_orders'])->only('all_orders');
        $this->middleware(['permission:view_order_details'])->only('show');
        $this->middleware(['permission:delete_order'])->only('destroy','bulk_order_delete');
        $this->middleware(['permission:add_order'])->only('create', 'store', 'backendCustomerSearch', 'backendCustomerAddresses', 'backendProductSearch', 'backendProductQuote', 'backendCourierRates', 'backendOrderSummary');
    }

    // All Orders
    public function all_orders(Request $request)
    {
        CoreComponentRepository::instantiateShopRepository();

        $date = $request->date;
        $sort_search = null;
        $delivery_status = null;
        $payment_status = '';
        $order_type = '';

        // $orders = Order::orderBy('id', 'desc');
        $orders = Order::with(['orderDetails', 'shipment', 'transport', 'bookedTo', 'localDeliveryPartner'])->orderBy('id', 'desc');
        $admin_user_id = get_admin()->id;

        if (Route::currentRouteName() == 'inhouse_orders.index' && Auth::user()->can('view_inhouse_orders')) {
            $orders = $orders->where('orders.seller_id', '=', $admin_user_id);
        }
        elseif (Route::currentRouteName() == 'seller_orders.index' && Auth::user()->can('view_seller_orders')) {
            $orders = $orders->where('orders.seller_id', '!=', $admin_user_id);
        }
        elseif (Route::currentRouteName() == 'pick_up_point.index' && Auth::user()->can('view_pickup_point_orders')) {
            if (get_setting('vendor_system_activation') != 1) {
                $orders = $orders->where('orders.seller_id', '=', $admin_user_id);
            }
            $orders->where('shipping_type', 'pickup_point')->orderBy('code', 'desc');
            if (
                Auth::user()->user_type == 'staff' &&
                Auth::user()->staff->pick_up_point != null
            ) {
                $orders->where('shipping_type', 'pickup_point')
                    ->where('pickup_point_id', Auth::user()->staff->pick_up_point->id);
            }
        }
        elseif (Route::currentRouteName() == 'all_orders.index' && Auth::user()->can('view_all_orders')) {
            if (get_setting('vendor_system_activation') != 1) {
                $orders = $orders->where('orders.seller_id', '=', $admin_user_id);
            }
        }
        elseif (Route::currentRouteName() == 'offline_payment_orders.index' && Auth::user()->can('view_all_offline_payment_orders')) {
            $orders = $orders->where('orders.manual_payment', 1);
            if($request->order_type != null){
                $order_type = $request->order_type;
                $orders = $order_type =='inhouse_orders' ? 
                            $orders->where('orders.seller_id', '=', $admin_user_id) : 
                            $orders->where('orders.seller_id', '!=', $admin_user_id);
            }
        }
        elseif (Route::currentRouteName() == 'unpaid_orders.index' && Auth::user()->can('view_all_unpaid_orders')) {
            $orders = $orders->where('orders.payment_status', 'unpaid');
        }
        else {
            abort(403);
        }

        if ($request->search) {
            $sort_search = $request->search;
            $orders = $orders->where('code', 'like', '%' . $sort_search . '%');
        }
        if ($request->payment_status != null) {
            $orders = $orders->where('payment_status', $request->payment_status);
            $payment_status = $request->payment_status;
        }
        if ($request->delivery_status != null) {
            $orders = $orders->where('delivery_status', $request->delivery_status);
            $delivery_status = $request->delivery_status;
        }
        if ($date != null) {
            $orders = $orders->where('created_at', '>=', date('Y-m-d', strtotime(explode(" to ", $date)[0])) . '  00:00:00')
                ->where('created_at', '<=', date('Y-m-d', strtotime(explode(" to ", $date)[1])) . '  23:59:59');
        }
        $orders = $orders->paginate(15);
        $unpaid_order_payment_notification = get_notification_type('complete_unpaid_order_payment', 'type');
        return view('backend.sales.index', compact('orders', 'sort_search', 'order_type', 'payment_status', 'delivery_status', 'date', 'unpaid_order_payment_notification'));
    }

    public function show($id)
    {
        // $order = Order::findOrFail(decrypt($id));
        $order = Order::with(['orderDetails', 'shipment', 'transport', 'bookedTo', 'localDeliveryPartner'])->findOrFail(decrypt($id));
        
        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
                ->where('user_type', 'delivery_boy')
                ->get();
                
        if(env('DEMO_MODE') != 'On') {
            $order->viewed = 1;
            $order->save();
        }

        return view('backend.sales.show', compact('order', 'delivery_boys'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $countries = Country::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $shippingMethods = ShippingMethod::where('is_active', 1)->orderBy('name')->get();
        $transports = Transport::active()->orderBy('name')->get();
        $bookedToOptions = BookedTo::active()->orderBy('name')->get();
        $localDeliveryPartners = LocalDeliveryPartner::active()->orderBy('name')->get();

        return view('backend.sales.create', compact(
            'countries',
            'shippingMethods',
            'transports',
            'bookedToOptions',
            'localDeliveryPartners'
        ));
    }

    public function backendCustomerSearch(Request $request, OrderPlacementService $orders)
    {
        $query = trim((string) $request->input('q'));

        $customers = $orders->approvedCustomerQuery()
            ->with('user_details')
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $prefixLike = $query . '%';

                $builder->where(function ($nested) use ($like) {
                    $nested->whereHas('user_details', function ($details) use ($like) {
                        $details->where('company_name', 'like', $like)
                            ->orWhere('con_person_name', 'like', $like)
                            ->orWhere('crm_id', 'like', $like)
                            ->orWhere('account_no_business', 'like', $like)
                            ->orWhere('account_no_personal', 'like', $like);
                    })
                        ->orWhere('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                    ->orderByRaw(
                        "CASE WHEN EXISTS (SELECT 1 FROM user_details WHERE user_details.user_id = users.id AND user_details.company_name LIKE ?) THEN 0 ELSE 1 END",
                        [$prefixLike]
                    );
            })
            ->orderByRaw("COALESCE((SELECT NULLIF(company_name, '') FROM user_details WHERE user_details.user_id = users.id LIMIT 1), users.name) ASC")
            ->limit(20)
            ->get([
                'id',
                'name',
                'email',
                'phone',
                'user_subtype',
                'user_type',
                'approval_status',
                'credit_status',
                'credit_days',
                'credit_limit',
            ]);

        return response()->json($customers->map(function ($customer) {
            $details = $customer->user_details;
            $approvalStatus = (string) $customer->approval_status === '1'
                ? translate('Approved')
                : ((string) $customer->approval_status === '2' ? translate('Rejected') : translate('Pending'));

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => optional($details)->company_name,
                'person_name' => optional($details)->con_person_name ?: $customer->name,
                'account_no' => optional($details)->crm_id ?: (optional($details)->account_no_business ?: optional($details)->account_no_personal),
                'email' => $customer->email,
                'phone' => $customer->phone,
                'role' => $customer->user_subtype ?: $customer->user_type,
                'approval_status' => $approvalStatus,
                'current_status' => optional($details)->current_status,
                'credit_status' => $customer->credit_status,
                'credit_days' => $customer->credit_days,
                'credit_limit' => $customer->credit_limit,
            ];
        })->values());
    }

    public function backendCustomerAddresses(Request $request, OrderPlacementService $orders, $customerId)
    {
        $customer = $orders->resolveApprovedCustomer($customerId);

        $addresses = Address::with(['country', 'state', 'city'])
            ->where('user_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        return response()->json($addresses->map(function ($address) {
            return [
                'id' => $address->id,
                'type' => $address->type,
                'address' => $address->address,
                'country' => optional($address->country)->name,
                'state' => optional($address->state)->name,
                'city' => optional($address->city)->name,
                'postal_code' => $address->postal_code,
                'phone' => $address->phone,
                'set_default' => (bool) $address->set_default,
            ];
        })->values());
    }

    public function backendProductSearch(Request $request)
    {
        $query = trim((string) $request->input('q'));

        $products = Product::with(['stocks.batches', 'brand', 'thumbnail', 'user'])
            ->where('approved', '1')
            ->where('published', 1)
            ->where('digital', 0)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    $nested->where('name', 'like', '%' . $query . '%')
                        ->orWhere('barcode', 'like', '%' . $query . '%');
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($products->map(function ($product) {
            $stocks = $product->stocks
                ->filter(fn ($stock) => !(bool) ($stock->is_hidden ?? false))
                ->values()
                ->map(function ($stock) {
                    $batches = valid_batches_for_stock($stock, true)->map(function ($batch) {
                        return [
                            'id' => $batch->id,
                            'batch' => $batch->batch,
                            'qty' => (int) $batch->qty,
                            'mrp_price' => (float) ($batch->mrp_price ?? 0),
                            'product_exp_date' => $batch->product_exp_date,
                        ];
                    })->values();

                    return [
                        'id' => $stock->id,
                        'variant' => $stock->variant ?: translate('Default'),
                        'raw_variant' => $stock->variant,
                        'id_variant' => $stock->id_variant,
                        'qty' => (int) ($stock->qty ?? 0),
                        'min_qty' => (int) ($stock->min_qty ?? 1),
                        'scheme' => (int) ($stock->scheme ?? 0),
                        'batches' => $batches,
                    ];
                });

            return [
                'id' => $product->id,
                'name' => $product->getTranslation('name'),
                'brand' => optional($product->brand)->name,
                'owner_id' => $product->user_id,
                'owner_name' => optional($product->user)->name ?: translate('Inhouse'),
                'thumbnail' => uploaded_asset($product->thumbnail_img),
                'stocks' => $stocks,
            ];
        })->values());
    }

    public function backendProductQuote(Request $request, OrderPlacementService $orders)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $orders->quoteBackendLine($request),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    public function backendCourierRates(Request $request, OrderPlacementService $orders)
    {
        try {
            $customer = $orders->resolveApprovedCustomer($request->input('customer_id'));
            $method = ShippingMethod::where('is_active', 1)->find($request->input('shipping_method_id'));
            if (!$method) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => translate('Please select an active courier provider.'),
                ]);
            }

            $addressId = $request->boolean('shipping_same_as_billing')
                ? $request->input('billing_address_id')
                : $request->input('shipping_address_id');
            $address = $addressId
                ? Address::where('user_id', $customer->id)->find($addressId)
                : null;
            $toPincode = optional($address)->postal_code
                ?: $request->input($request->boolean('shipping_same_as_billing') ? 'billing_postal_code' : 'shipping_postal_code');

            if (!$toPincode) {
                throw ValidationException::withMessages([
                    'shipping_address_id' => translate('Select a shipping address with a postal code before loading courier services.'),
                ]);
            }

            $weight = 0.0;
            $length = 0.0;
            $width = 0.0;
            $height = 0.0;
            foreach ((array) $request->input('items', []) as $item) {
                $product = Product::find($item['product_id'] ?? null);
                if (!$product) {
                    continue;
                }

                $stock = !empty($item['stock_id'])
                    ? $product->stocks()->where('id', $item['stock_id'])->first()
                    : null;
                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $weight += (float) ($stock->weight ?? $product->weight ?? 0.21) * $quantity;
                $length += (float) ($stock->length ?? $product->length ?? 10) * $quantity;
                $width += (float) ($stock->width ?? $product->width ?? 10) * $quantity;
                $height += (float) ($stock->height ?? $product->height ?? 10) * $quantity;
            }

            if ($weight <= 0) {
                throw ValidationException::withMessages([
                    'items' => translate('Please add at least one product before loading courier services.'),
                ]);
            }

            $volumetricWeight = (max(10, $length) * max(10, $width) * max(10, $height)) / 5000;
            $package = [
                'total_physical_weight' => number_format($weight, 2, '.', ''),
                'box_length' => number_format(max(10, $length), 2, '.', ''),
                'box_breadth' => number_format(max(10, $width), 2, '.', ''),
                'box_height' => number_format(max(10, $height), 2, '.', ''),
                'volumetric_weight' => number_format($volumetricWeight, 2, '.', ''),
                'charged_weight' => number_format(max($weight, $volumetricWeight), 2, '.', ''),
            ];

            $rateRequest = Request::create('/', 'GET', [
                'provider' => $method->slug,
                'address_id' => optional($address)->id,
                'to_pincode' => $toPincode,
                'payment_type' => $request->input('payment_type') === 'cash_on_delivery' ? 'cod' : 'prepaid',
                'package' => $package,
            ]);
            $class = 'App\\Http\\Controllers\\Shipment\\' . ucfirst($method->slug) . 'Controller';
            if (!class_exists($class) || !method_exists($class, 'rates')) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => translate('Courier provider is not available.'),
                ]);
            }

            return response()->json(app($class)->rates($rateRequest));
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    public function backendOrderSummary(Request $request, OrderPlacementService $orders)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $orders->summarizeBackendRequest($request),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if ($request->boolean('backend_add_order')) {
            try {
                $combinedOrder = app(OrderPlacementService::class)->placeFromBackendRequest($request);
                $firstOrder = $combinedOrder->orders()->orderBy('id')->first();

                flash(translate('Order has been created successfully.'))->success();

                if ($firstOrder) {
                    return redirect()->route('all_orders.show', encrypt($firstOrder->id));
                }

                return redirect()->route('all_orders.index');
            } catch (ValidationException $exception) {
                $message = collect($exception->errors())->flatten()->first() ?: translate('Unable to create order.');
                flash($message)->warning();
                return back()->withInput();
            }
        }

        $carts = Cart::where('user_id', Auth::user()->id)->active()->get();

        if ($carts->isEmpty()) {
            flash(translate('Your cart is empty'))->warning();
            return redirect()->route('home');
        }

        $address = Address::where('id', $carts[0]['address_id'])->first();

        $buildAddressPayload = function (?Address $addr) {
            if ($addr === null) {
                return [];
            }

            $payload = [
                'name'        => Auth::user()->name,
                'email'       => Auth::user()->email,
                'address'     => $addr->address,
                'country'     => optional($addr->country)->name,
                'state'       => optional($addr->state)->name,
                'city'        => optional($addr->city)->name,
                'postal_code' => $addr->postal_code,
                'phone'       => $addr->phone,
            ];

            if ($addr->latitude || $addr->longitude) {
                $payload['lat_lang'] = $addr->latitude . ',' . $addr->longitude;
            }

            return $payload;
        };

        $shippingAddress = $buildAddressPayload($address);
        $billingAddressModel = $request->filled('billing_address_id')
            ? Address::find($request->billing_address_id)
            : $address;
        $billingAddress = $buildAddressPayload($billingAddressModel ?: $address);

        $combined_order = new CombinedOrder;
        $combined_order->user_id = Auth::user()->id;
        $combined_order->shipping_address = json_encode($shippingAddress);
        $combined_order->save();

        $seller_products = array();
        foreach ($carts as $cartItem) {
            $product_ids = array();
            $product = Product::find($cartItem['product_id']);
            if (isset($seller_products[$product->user_id])) {
                $product_ids = $seller_products[$product->user_id];
            }
            array_push($product_ids, $cartItem);
            $seller_products[$product->user_id] = $product_ids;
        }

        $firstOrderForReferral = null;
        foreach ($seller_products as $seller_product) {
            $shippingChoice = $request->shipping_method ?: 'courier';
            $transport = null;
            $bookedTo = null;
            $localDeliveryPartner = null;

            if ($shippingChoice === 'transport') {
                $transport = $this->resolveTransport($request);
                $bookedTo = $this->resolveBookedTo($request, $transport);
            } elseif ($shippingChoice === 'local') {
                $localDeliveryPartner = $this->resolveLocalDeliveryPartner($request);
            }

            $order = new Order;
            $order->combined_order_id = $combined_order->id;
            $order->user_id = Auth::user()->id;
            $order->shipping_address = $combined_order->shipping_address;
            $order->billing_address = json_encode(!empty($billingAddress) ? $billingAddress : $shippingAddress);
            $order->additional_info = $request->additional_info;
            $order->payment_type = $request->payment_option;
            $order->shipping_choice = $shippingChoice;
            $order->shipping_by = $shippingChoice === 'courier'
                ? (get_shipping_method_slug_by_id($request->shipping_method_id) ?? 'shipway')
                : ($shippingChoice === 'transport' ? optional($transport)->name : optional($localDeliveryPartner)->name);
            $order->fod_mode = $shippingChoice === 'transport' ? $request->fod_mode : null;
            $order->shipping_courier_id = $shippingChoice === 'courier' ? $request->courier_service : null;
            $order->transport_id = optional($transport)->id;
            $order->booked_to_id = optional($bookedTo)->id;
            $order->local_delivery_partner_id = optional($localDeliveryPartner)->id;
            $order->transport_mode = $shippingChoice === 'transport' ? $request->fod_mode : null;
            $order->transport_surface_mode = ($shippingChoice === 'transport' && $request->fod_mode === 'surface') ? $request->transport_surface_mode : null;
            $order->transport_delivery_type = $shippingChoice === 'transport' ? $request->transport_delivery_type : null;
            $order->delivery_viewed = '0';
            $order->payment_status_viewed = '0';
            $order->code = generate_financial_year_order_code();
            $order->date = strtotime('now');    
            $order->save();
            if ($firstOrderForReferral === null) {
                $firstOrderForReferral = $order;
            }

            storeIPLocation('orders', $order->id); //store ip location            

            $subtotal = 0;
            $tax = 0;
            $shipping = 0;
            $coupon_discount = 0;
            $affectedProductIds = [];
            $schemeAllocationsByGroup = [];
            $schemeGroupsWritten = [];

            $paidSellerItems = collect($seller_product)->filter(function ($item) {
                return !(bool) ($item['is_scheme'] ?? false);
            });

            foreach ($paidSellerItems->groupBy(function ($item) {
                return (int) $item['product_id'] . '|' . (string) ($item['variation'] ?? '');
            }) as $groupKey => $groupItems) {
                $firstItem = $groupItems->first();
                $groupProduct = Product::find($firstItem['product_id']);
                $groupStock = $groupProduct ? $groupProduct->stocks->where('variant', $firstItem['variation'])->first() : null;

                if (!$groupProduct || $groupProduct->digital == 1 || !$groupStock) {
                    $schemeAllocationsByGroup[$groupKey] = [];
                    continue;
                }

                $reservations = [];
                $unbatchedQty = 0;
                foreach ($groupItems as $line) {
                    if (!empty($line['batch_id'])) {
                        $lineBatch = $groupStock->batches()->where('id', $line['batch_id'])->first();
                        if (!$lineBatch || is_batch_expired($lineBatch)) {
                            flash(translate('Invalid batch selected for ') . $groupProduct->getTranslation('name'))->warning();
                            $order->delete();
                            return redirect()->route('cart')->send();
                        }
                        $reservations[(int) $lineBatch->id] = ($reservations[(int) $lineBatch->id] ?? 0) + (int) $line['quantity'];
                    } else {
                        $unbatchedQty += (int) $line['quantity'];
                    }
                }

                if ($unbatchedQty > (int) $groupStock->qty) {
                    flash(translate('The requested quantity is not available for ') . $groupProduct->getTranslation('name'))->warning();
                    $order->delete();
                    return redirect()->route('cart')->send();
                }

                foreach ($reservations as $reservedBatchId => $reservedQty) {
                    $reservedBatch = $groupStock->batches()->where('id', $reservedBatchId)->first();
                    if (!$reservedBatch || (int) $reservedBatch->qty < (int) $reservedQty) {
                        flash(translate('The requested quantity is not available for ') . $groupProduct->getTranslation('name'))->warning();
                        $order->delete();
                        return redirect()->route('cart')->send();
                    }
                }

                $minQty = $groupStock->min_qty ?? $groupProduct->min_qty ?? 1;
                $schemeQty = calculate_scheme_qty($groupItems->sum('quantity'), $minQty, (int) ($groupStock->scheme ?? 0));
                $schemePreview = allocate_scheme_free_batches($groupStock, $schemeQty, $reservations);
                if (!$schemePreview['success']) {
                    flash(translate('The requested scheme quantity is not available for ') . $groupProduct->getTranslation('name'))->warning();
                    $order->delete();
                    return redirect()->route('cart')->send();
                }
                $schemeAllocationsByGroup[$groupKey] = $schemePreview['allocations'];
            }

            //Order Details Storing
            foreach ($seller_product as $cartItem) {
                $product = Product::find($cartItem['product_id']);
                $isSchemeLine = (bool) ($cartItem['is_scheme'] ?? false);
                if ($isSchemeLine) {
                    continue;
                }

                $unitSalePrice = $cartItem['sale_price'] ?? cart_product_price($cartItem, $product, false, false);
                $unitBasePrice = $cartItem['before_productandbatch_discount'] ?? $cartItem['price'] ?? $unitSalePrice;

                $subtotal += $unitSalePrice * $cartItem['quantity'];
                // Use stored tax from cart (calculated from batch/stock price at add-to-cart)
                $itemTax = ($cartItem['tax'] ?? cart_product_tax($cartItem, $product, false)) * $cartItem['quantity'];
                $tax += $itemTax;
                $coupon_discount += $cartItem['discount'];

                $product_variation = $cartItem['variation'];

                $product_stock = $product->stocks->where('variant', $product_variation)->first();
                
                // Get batch_id from cart if available
                $batchId = $cartItem['batch_id'] ?? null;
                $selectedBatch = null;
                $groupKey = (int) $cartItem['product_id'] . '|' . (string) ($product_variation ?? '');
                
                // Stock validation and deduction
                if ($product->digital != 1 && $product_stock) {
                    if ($batchId) {
                        // Validate batch belongs to this stock and deduct from batch
                        $selectedBatch = $product_stock->batches()->where('id', $batchId)->first();
                        if (!$selectedBatch || is_batch_expired($selectedBatch)) {
                            flash(translate('Invalid batch selected for ') . $product->getTranslation('name'))->warning();
                            $order->delete();
                            return redirect()->route('cart')->send();
                        }

                        if ($cartItem['quantity'] > $selectedBatch->qty) {
                            flash(translate('The requested quantity is not available for ') . $product->getTranslation('name'))->warning();
                            $order->delete();
                            return redirect()->route('cart')->send();
                        }
                        
                        $selectedBatch->qty -= $cartItem['quantity'];
                        $selectedBatch->save();
                        
                        // Update parent stock quantity (aggregate from batches)
                        $product_stock->load('batches');
                        $batches = $product_stock->batches;
                        $totalBatchQty = $batches->sum('qty');
                        $product_stock->qty = $totalBatchQty;
                        $product_stock->save();
                    } else {
                        // Fallback to stock validation if no batch
                        if ($cartItem['quantity'] > $product_stock->qty) {
                            flash(translate('The requested quantity is not available for ') . $product->getTranslation('name'))->warning();
                            $order->delete();
                            return redirect()->route('cart')->send();
                        }

                        $product_stock->qty -= $cartItem['quantity'];
                        $product_stock->save();
                    }

                    $affectedProductIds[] = (int) $product->id;
                }

                $order_detail = new OrderDetail;
                $order_detail->order_id = $order->id;
                $order_detail->seller_id = $product->user_id;
                $order_detail->product_id = $product->id;
                $order_detail->variation = $product_variation;
                $order_detail->batch_id = $batchId;
                $order_detail->price = $unitSalePrice * $cartItem['quantity'];
                $order_detail->before_productandbatch_discount = $unitBasePrice;
                $order_detail->sale_price = $unitSalePrice;
                $order_detail->mrp_price = $cartItem['mrp_price'] ?? ($selectedBatch ? $selectedBatch->mrp_price : (optional($product_stock)->mrp_price ?? $product->mrp_price));
                $order_detail->discount_amount = round(max(0, (float) $unitBasePrice - (float) $unitSalePrice) * (int) $cartItem['quantity'], 2);
                // Use stored tax from cart so order_detail matches cart (batch-aware)
                $order_detail->tax = ($cartItem['tax'] ?? cart_product_tax($cartItem, $product, false)) * $cartItem['quantity'];
                $order_detail->shipping_type = $cartItem['shipping_type'];
                $order_detail->product_referral_code = $cartItem['product_referral_code'];
                $order_detail->shipping_cost = $cartItem['shipping_cost'];
                $order_detail->is_scheme = 0;

                $shipping += $order_detail->shipping_cost;
                //End of storing shipping cost

                $order_detail->quantity = $cartItem['quantity'];

                if (addon_is_activated('club_point')) {
                    $order_detail->earn_point = $product->earn_point;
                }

                $order_detail->save();

                foreach (($schemeGroupsWritten[$groupKey] ?? false) ? [] : ($schemeAllocationsByGroup[$groupKey] ?? []) as $allocation) {
                    $schemeGroupsWritten[$groupKey] = true;
                    $schemeBatchForDeduction = ProductBatch::find($allocation['batch_id']);
                    if (!$schemeBatchForDeduction || is_batch_expired($schemeBatchForDeduction) || (int) $schemeBatchForDeduction->qty < (int) $allocation['quantity']) {
                        flash(translate('The requested scheme quantity is not available for ') . $product->getTranslation('name'))->warning();
                        $order->delete();
                        return redirect()->route('cart')->send();
                    }
                    $schemeBatchForDeduction->qty -= (int) $allocation['quantity'];
                    $schemeBatchForDeduction->save();

                    $schemeBatch = $allocation['batch'] ?? ProductBatch::find($allocation['batch_id']);
                    $scheme_order_detail = new OrderDetail;
                    $scheme_order_detail->order_id = $order->id;
                    $scheme_order_detail->seller_id = $product->user_id;
                    $scheme_order_detail->product_id = $product->id;
                    $scheme_order_detail->variation = $product_variation;
                    $scheme_order_detail->batch_id = $allocation['batch_id'];
                    $scheme_order_detail->price = 0;
                    $scheme_order_detail->before_productandbatch_discount = 0;
                    $scheme_order_detail->sale_price = 0;
                    $scheme_order_detail->mrp_price = $schemeBatch->mrp_price ?? $order_detail->mrp_price;
                    $scheme_order_detail->tax = 0;
                    $scheme_order_detail->shipping_type = $cartItem['shipping_type'];
                    $scheme_order_detail->product_referral_code = null;
                    $scheme_order_detail->shipping_cost = 0;
                    $scheme_order_detail->is_scheme = 1;
                    $scheme_order_detail->quantity = (int) $allocation['quantity'];
                    $scheme_order_detail->save();
                }
                $schemeGroupsWritten[$groupKey] = true;

                if ($product_stock) {
                    $product_stock->load('batches');
                    if ($product_stock->batches->isNotEmpty()) {
                        $product_stock->qty = $product_stock->batches->sum('qty');
                        $product_stock->save();
                    }
                }

                $product->num_of_sale += $cartItem['quantity'];
                $product->save();

                $order->seller_id = $product->user_id;
                $order->shipping_type = $cartItem['shipping_type'];

                if ($cartItem['shipping_type'] == 'pickup_point') {
                    $order->pickup_point_id = $cartItem['pickup_point'];
                }
                if ($cartItem['shipping_type'] == 'carrier') {
                    $order->carrier_id = $cartItem['carrier_id'];
                }

                if ($product->added_by == 'seller' && $product->user->seller != null) {
                    $seller = $product->user->seller;
                    $seller->num_of_sale += $cartItem['quantity'];
                    $seller->save();
                }

                if (addon_is_activated('affiliate_system') && $order_detail->product_referral_code) {
                    $referred_by_user = User::where('referral_code', $order_detail->product_referral_code)->first();
                    if ($referred_by_user && class_exists('App\\Http\\Controllers\\AffiliateController')) {
                        $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                        if (method_exists($affiliateController, 'processAffiliateStats')) {
                            $affiliateController->processAffiliateStats($referred_by_user->id, 0, $order_detail->quantity, 0, 0);
                        }
                    }
                }
            }

            $order->grand_total = $subtotal + $tax + $shipping;

            $couponCode = collect($seller_product)
                ->filter(fn ($item) => (float) ($item['discount'] ?? 0) > 0 && !empty($item['coupon_code']))
                ->pluck('coupon_code')
                ->first();

            if ($coupon_discount > 0 && $couponCode != null) {
                $order->coupon_discount = $coupon_discount;
                $order->grand_total -= $coupon_discount;

                $coupon_usage = new CouponUsage;
                $coupon_usage->user_id = Auth::user()->id;
                $coupon = Coupon::where('code', $couponCode)->first();
                if ($coupon) {
                    $coupon_usage->coupon_id = $coupon->id;
                    $coupon_usage->save();
                }
            }

            $combined_order->grand_total += $order->grand_total;


            $order->quote_grand_total = $order->grand_total;
            $order->quote_currency_code = Session::get('currency_code');
            $order->quote_currency_exchange_rate = Session::get('currency_exchange_rate');

            $order->save();

            if (!empty($affectedProductIds)) {
                dispatch_low_stock_admin_notifications($affectedProductIds);
            }
        }

        $combined_order->save();

        $request->session()->put('combined_order_id', $combined_order->id);
    }

    private function resolveTransport(Request $request): ?Transport
    {
        $id = (int) $request->input('transport_id');
        if ($id > 0) {
            $transport = Transport::find($id);
            if ($transport) {
                return $transport;
            }
        }

        $name = trim((string) $request->input('transport_name'));
        if ($name === '') {
            return null;
        }

        $transport = Transport::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($transport) {
            return $transport;
        }

        return Transport::create([
            'name' => $name,
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    private function resolveBookedTo(Request $request, ?Transport $transport): ?BookedTo
    {
        if (!$transport) {
            return null;
        }

        $id = (int) $request->input('booked_to_id');
        if ($id > 0) {
            $bookedTo = BookedTo::where('transport_id', $transport->id)->where('id', $id)->first();
            if ($bookedTo) {
                return $bookedTo;
            }
        }

        $location = trim((string) $request->input('booked_to_name'));
        if ($location === '') {
            return null;
        }

        $bookedTo = BookedTo::where('transport_id', $transport->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($location)])
            ->first();
        if ($bookedTo) {
            return $bookedTo;
        }

        return BookedTo::create([
            'transport_id' => $transport->id,
            'name' => $location,
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    private function resolveLocalDeliveryPartner(Request $request): ?LocalDeliveryPartner
    {
        $id = (int) $request->input('local_delivery_partner_id');
        if ($id > 0) {
            $partner = LocalDeliveryPartner::find($id);
            if ($partner) {
                return $partner;
            }
        }

        $name = trim((string) $request->input('local_delivery_partner_name'));
        if ($name === '') {
            return null;
        }

        $partner = LocalDeliveryPartner::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($partner) {
            return $partner;
        }

        return LocalDeliveryPartner::create([
            'name' => $name,
            'status' => 'inactive',
            'created_by' => Auth::id(),
        ]);
    }

    private function isProductDiscountCurrentlyActive(Product $product): bool
    {
        if (($product->discount_start_date ?? null) === null) {
            return true;
        }

        $now = strtotime(date('d-m-Y H:i:s'));
        return $now >= (int) $product->discount_start_date && $now <= (int) $product->discount_end_date;
    }

    private function resolveProductDiscountAmountPerUnit(Product $product, float $unitPriceAfterProductDiscount): float
    {
        if (!$this->isProductDiscountCurrentlyActive($product)) {
            return 0.0;
        }

        if (($product->discount_type ?? null) === 'amount') {
            return max(0, (float) ($product->discount ?? 0));
        }

        if (($product->discount_type ?? null) === 'percent') {
            $percent = max(0, min(99.99, (float) ($product->discount ?? 0)));
            if ($percent <= 0) {
                return 0.0;
            }

            $baseBeforeProductDiscount = $unitPriceAfterProductDiscount / (1 - ($percent / 100));
            return max(0, $baseBeforeProductDiscount - $unitPriceAfterProductDiscount);
        }

        return 0.0;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        if ($order != null) {
            $order->commissionHistory()->delete();
            foreach ($order->orderDetails as $key => $orderDetail) {
                try {
                    product_restock($orderDetail);
                } catch (\Exception $e) {
                }

                $orderDetail->delete();
            }
            $order->delete();
            flash(translate('Order has been deleted successfully'))->success();
        } else {
            flash(translate('Something went wrong'))->error();
        }
        return back();
    }

    public function bulk_order_delete(Request $request)
    {
        if ($request->id) {
            foreach ($request->id as $order_id) {
                $this->destroy($order_id);
            }
        }

        return 1;
    }

    public function order_details(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->save();
        return view('seller.order_details_seller', compact('order'));
    }

    public function update_delivery_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->delivery_viewed = '0';
        $order->delivery_status = $request->status;
        $order->save();

        if ($request->status == 'cancelled' && $order->payment_type == 'wallet') {
            $user = User::where('id', $order->user_id)->first();
            $user->balance += $order->grand_total;
            $user->save();
        }

        if ($request->status == 'cancelled') {
            release_referral_discount_lock_on_order_cancel($order);
        }

        // If the order is cancelled and the seller commission is calculated, deduct seller earning
        if($request->status == 'cancelled' && $order->user->user_type == 'seller' && $order->payment_status == 'paid' && $order->commission_calculated == 1){
            $sellerEarning = $order->commissionHistory->seller_earning;
            $shop = $order->shop;
            $shop->admin_to_pay -= $sellerEarning;
            $shop->save();
        }

        if (Auth::user()->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
                $orderDetail->delivery_status = $request->status;
                $orderDetail->save();

                if ($request->status == 'cancelled') {
                    product_restock($orderDetail);
                }
            }
        } else {
            foreach ($order->orderDetails as $key => $orderDetail) {

                $orderDetail->delivery_status = $request->status;
                $orderDetail->save();

                if ($request->status == 'cancelled') {
                    product_restock($orderDetail);
                }

                if (addon_is_activated('affiliate_system')) {
                    if (($request->status == 'delivered' || $request->status == 'cancelled') &&
                        $orderDetail->product_referral_code
                    ) {

                        $no_of_delivered = 0;
                        $no_of_canceled = 0;

                        if ($request->status == 'delivered') {
                            $no_of_delivered = $orderDetail->quantity;
                        }
                        if ($request->status == 'cancelled') {
                            $no_of_canceled = $orderDetail->quantity;
                        }

                        $referred_by_user = User::where('referral_code', $orderDetail->product_referral_code)->first();
                        if ($referred_by_user && class_exists('App\\Http\\Controllers\\AffiliateController')) {
                            $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                            if (method_exists($affiliateController, 'processAffiliateStats')) {
                                $affiliateController->processAffiliateStats($referred_by_user->id, 0, 0, $no_of_delivered, $no_of_canceled);
                            }
                        }
                    }
                }
            }
        }
        // Delivery Status change email notification to Admin, seller, Customer
        EmailUtility::order_email($order, $request->status);  

        // Delivery Status change SMS notification
        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'delivery_status_change')->first()->status == 1) {
            try {
                SmsUtility::delivery_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {}
        }

        //Send web Notifications to user
        NotificationUtility::sendNotification($order, $request->status);

        //Sends Firebase Notifications to user
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->delivery_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('delivery_boy')) {
            if (Auth::user()->user_type == 'delivery_boy') {
                $deliveryBoyController = new DeliveryBoyController;
                $deliveryBoyController->store_delivery_history($order);
            }
        }

        return 1;
    }

    public function update_tracking_code(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->tracking_code = $request->tracking_code;
        $order->save();

        return 1;
    }

    public function update_payment_status(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $previousPaymentStatus = $order->payment_status;
        $order->payment_status_viewed = '0';
        $order->save();

        if (Auth::user()->user_type == 'seller') {
            foreach ($order->orderDetails->where('seller_id', Auth::user()->id) as $key => $orderDetail) {
                $orderDetail->payment_status = $request->status;
                $orderDetail->save();
            }
        } else {
            foreach ($order->orderDetails as $key => $orderDetail) {
                $orderDetail->payment_status = $request->status;
                $orderDetail->save();
            }
        }

        $status = 'paid';
        foreach ($order->orderDetails as $key => $orderDetail) {
            if ($orderDetail->payment_status != 'paid') {
                $status = 'unpaid';
            }
        }
        $order->payment_status = $status;
        $order->save();

        $statusChangedToPaid = $previousPaymentStatus !== 'paid' && $order->payment_status == 'paid';

        if (
            $order->payment_status == 'paid' &&
            $order->commission_calculated == 0
        ) {
            calculateCommissionAffilationClubPoint($order);
        }
        if ($order->payment_status == 'paid') {
            finalize_referral_rewards_for_paid_order($order);
            if ($statusChangedToPaid) {
                app(WalletRewardService::class)->applyReward($order);
            }
        }

        // Payment Status change email notification to Admin, seller, Customer
        if($request->status == 'paid'){
            EmailUtility::order_email($order, $request->status);  
        }

        //Sends Web Notifications to Admin, seller, Customer
        NotificationUtility::sendNotification($order, $request->status);

        //Sends Firebase Notifications to Admin, seller, Customer
        if (get_setting('google_firebase') == 1 && $order->user->device_token != null) {
            $request->device_token = $order->user->device_token;
            $request->title = "Order updated !";
            $status = str_replace("_", "", $order->payment_status);
            $request->text = " Your order {$order->code} has been {$status}";

            $request->type = "order";
            $request->id = $order->id;
            $request->user_id = $order->user->id;

            NotificationUtility::sendFirebaseNotification($request);
        }


        if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'payment_status_change')->first()->status == 1) {
            try {
                SmsUtility::payment_status_change(json_decode($order->shipping_address)->phone, $order);
            } catch (\Exception $e) {
            }
        }
        return 1;
    }

    public function assign_delivery_boy(Request $request)
    {
        if (addon_is_activated('delivery_boy')) {

            $order = Order::findOrFail($request->order_id);
            $order->assign_delivery_boy = $request->delivery_boy;
            $order->delivery_history_date = date("Y-m-d H:i:s");
            $order->save();

            $delivery_history = \App\Models\DeliveryHistory::where('order_id', $order->id)
                ->where('delivery_status', $order->delivery_status)
                ->first();

            if (empty($delivery_history)) {
                $delivery_history = new \App\Models\DeliveryHistory;

                $delivery_history->order_id = $order->id;
                $delivery_history->delivery_status = $order->delivery_status;
                $delivery_history->payment_type = $order->payment_type;
            }
            $delivery_history->delivery_boy_id = $request->delivery_boy;

            $delivery_history->save();

            if (env('MAIL_USERNAME') != null && get_setting('delivery_boy_mail_notification') == '1') {
                $array['view'] = 'emails.invoice';
                $array['subject'] = translate('You are assigned to delivery an order. Order code') . ' - ' . $order->code;
                $array['from'] = env('MAIL_FROM_ADDRESS');
                $array['order'] = $order;

                try {
                    Mail::to($order->delivery_boy->email)->queue(new InvoiceEmailManager($array));
                } catch (\Exception $e) {
                }
            }

            if (addon_is_activated('otp_system') && SmsTemplate::where('identifier', 'assign_delivery_boy')->first()->status == 1) {
                try {
                    SmsUtility::assign_delivery_boy($order->delivery_boy->phone, $order->code);
                } catch (\Exception $e) {
                }
            }
        }

        return 1;
    }

    public function orderBulkExport(Request $request)
    {
        if($request->id){
          return Excel::download(new OrdersExport($request->id), 'orders.xlsx');
        }
        return back();
    }

    public function unpaid_order_payment_notification_send(Request $request){
        if($request->order_ids != null){
            $notificationType = get_notification_type('complete_unpaid_order_payment', 'type');
            foreach (explode(",",$request->order_ids) as $order_id) {
                $order = Order::where('id', $order_id)->first();
                $user = $order->user;
                if($notificationType->status == 1 && $order->payment_status == 'unpaid'){
                    $order_notification['order_id']     = $order->id;
                    $order_notification['order_code']   = $order->code;
                    $order_notification['user_id']      = $order->user_id;
                    $order_notification['seller_id']    = $order->seller_id;
                    $order_notification['status']       = $order->payment_status;
                    $order_notification['notification_type_id'] = $notificationType->id;
                    Notification::send($user, new OrderNotification($order_notification));
                }
            }
            flash(translate('Notification Sent Successfully.'))->success();
        }
        else{
            flash(translate('Something went wrong!.'))->warning();
        }
        return back();
    }
}
