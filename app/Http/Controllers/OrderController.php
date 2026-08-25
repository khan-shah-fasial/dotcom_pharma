<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderAttachment;
use App\Models\Cart;
use App\Models\Address;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\CouponUsage;
use App\Models\Coupon;
use App\Models\User;
use App\Models\CombinedOrder;
use App\Models\Company;
use App\Models\Country;
use App\Models\Airport;
use App\Models\SeaPort;
use App\Models\SmsTemplate;
use App\Models\ProductBatch;
use App\Models\BookedTo;
use App\Models\LocalDeliveryPartner;
use App\Models\ShippingMethod;
use App\Models\Staff;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Support\InvoiceType;
use App\Services\OrderPlacementService;
use App\Services\WalletRewardService;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{

    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_orders|view_inhouse_orders|view_seller_orders|view_pickup_point_orders|view_all_offline_payment_orders'])->only('all_orders');
        $this->middleware(['permission:view_order_details'])->only('show');
        $this->middleware(['permission:delete_order'])->only('destroy','bulk_order_delete');
        $this->middleware(['permission:add_order'])->only('create', 'store', 'edit', 'update', 'backendOrderNumberPreview', 'backendCustomerSearch', 'backendCustomerAddresses', 'backendProductSearch', 'backendProductQuote', 'backendCourierRates', 'backendOrderSummary', 'deleteAttachment');
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
        $order = Order::with([
            'user',
            'delivery_boy',
            'shipment',
            'transport',
            'bookedTo',
            'localDeliveryPartner',
            'loadingSeaPort',
            'loadingAirport',
            'dischargeSeaPort',
            'dischargeAirport',
            'salesPerson',
            'salesExecutive',
            'packedByStaff',
            'checkedByStaff',
            'billingByStaff',
            'attachments',
            'orderDetails.product.stocks',
            'orderDetails.batch',
        ])->findOrFail(decrypt($id));

        $order_shipping_address = json_decode($order->shipping_address);
        $delivery_boys = User::where('city', $order_shipping_address->city)
                ->where('user_type', 'delivery_boy')
                ->get();

        if(env('DEMO_MODE') != 'On') {
            $order->viewed = 1;
            $order->save();
        }

        return view('backend.sales.show2', compact('order', 'delivery_boys'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $countries = Country::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $seaPorts = SeaPort::where('status', 1)->orderBy('country')->orderBy('name')->get();
        $airports = Airport::where('status', 1)->orderBy('country')->orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', 1)->orderBy('name')->get();
        $transports = Transport::active()->orderBy('name')->get();
        $bookedToOptions = BookedTo::active()->orderBy('name')->get();
        $localDeliveryPartners = LocalDeliveryPartner::active()->orderBy('name')->get();
        $companies = Company::orderBy('company_name')->get(['id', 'code', 'company_name']);
        extract($this->orderFormStaffOptions());
        $selectedCompany = $companies->firstWhere('id', (int) old('company_id'));
        $orderNumberParts = financial_year_order_code_parts(old('order_date', now()->toDateString()), 'S');

        return view('backend.sales.create', compact(
            'countries',
            'seaPorts',
            'airports',
            'shippingMethods',
            'transports',
            'bookedToOptions',
            'localDeliveryPartners',
            'companies',
            'salesPeople',
            'packedStaff',
            'checkedStaff',
            'billingStaff',
            'selectedCompany',
            'orderNumberParts'
        ));
    }

    public function backendCustomerSearch(Request $request, OrderPlacementService $orders)
    {
        $query = trim((string) $request->input('q'));

        $customers = $orders->approvedCustomerQuery()
            ->with([
                'user_details.businessCity',
                'user_details.businessState',
                'user_details.businessCountry',
                'user_details.personalCity',
                'user_details.personalState',
                'user_details.personalCountry',
            ])
            ->when($query !== '', function ($builder) use ($query) {
                $like = '%' . $query . '%';
                $prefixLike = $query . '%';

                $builder->where(function ($nested) use ($like) {
                    $nested->whereHas('user_details', function ($details) use ($like) {
                        $details->where('company_name', 'like', $like)
                            ->orWhere('con_person_name', 'like', $like)
                            ->orWhere('crm_id', 'like', $like)
                            ->orWhere('record_file_no', 'like', $like)
                            ->orWhere('account_no_business', 'like', $like)
                            ->orWhere('account_no_personal', 'like', $like)
                            ->orWhere('village_business', 'like', $like)
                            ->orWhere('post_business', 'like', $like)
                            ->orWhere('district_business', 'like', $like)
                            ->orWhere('pincode_business', 'like', $like)
                            ->orWhere('village', 'like', $like)
                            ->orWhere('post', 'like', $like)
                            ->orWhere('district', 'like', $like)
                            ->orWhere('pincode', 'like', $like)
                            ->orWhere('prim_mobile_no_business', 'like', $like)
                            ->orWhere('alt_mobile_no_business', 'like', $like)
                            ->orWhere('prim_mobile_no', 'like', $like)
                            ->orWhere('alt_mobile_no', 'like', $like)
                            ->orWhere('prim_whats_app_no_business', 'like', $like)
                            ->orWhere('alternate_whats_app_no_business', 'like', $like)
                            ->orWhere('prim_whats_app_no', 'like', $like)
                            ->orWhere('alt_whats_app_no', 'like', $like)
                            ->orWhere('salesman', 'like', $like);
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
                'type_option',
                'name',
                'email',
                'phone',
                'user_subtype',
                'user_type',
                'approval_status',
                'gst_no',
                'iec_no',
                'aadhaar_no',
                'pan_no',
                'passport_no',
                'credit_status',
                'credit_days',
                'credit_limit',
                'balance',
            ]);

        return response()->json($customers->map(function ($customer) {
            $details = $customer->user_details;
            $businessAccount = !empty(optional($details)->company_name) || !empty(optional($details)->account_no_business);
            $mobileNumbers = collect($businessAccount
                ? [optional($details)->prim_mobile_no_business, optional($details)->alt_mobile_no_business, $customer->phone]
                : [optional($details)->prim_mobile_no, optional($details)->alt_mobile_no, $customer->phone])
                ->filter()->unique()->values();
            $whatsappNumbers = collect($businessAccount
                ? [optional($details)->prim_whats_app_no_business, optional($details)->alternate_whats_app_no_business]
                : [optional($details)->prim_whats_app_no, optional($details)->alt_whats_app_no])
                ->filter()->unique()->values();
            $approvalStatus = (string) $customer->approval_status === '1'
                ? translate('Approved')
                : ((string) $customer->approval_status === '2' ? translate('Rejected') : translate('Pending'));

            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => optional($details)->company_name,
                'person_name' => optional($details)->con_person_name ?: $customer->name,
                'account_no' => optional($details)->crm_id ?: (optional($details)->account_no_business ?: optional($details)->account_no_personal),
                'record_file_no' => optional($details)->record_file_no,
                'gst_no' => optional($details)->gst_no ?: $customer->gst_no,
                'iec_no' => optional($details)->iec_no ?: $customer->iec_no,
                'aadhaar_no' => optional($details)->aadhaar_no ?: $customer->aadhaar_no,
                'pan_no' => optional($details)->pan_no ?: $customer->pan_no,
                'passport_no' => optional($details)->passport_no ?: $customer->passport_no,
                'dl1' => optional($details)->dl1,
                'dl2' => optional($details)->dl2,
                'dl_expiry' => optional($details)->dl_expiry,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'mobile_numbers' => $mobileNumbers,
                'whatsapp_numbers' => $whatsappNumbers,
                'village' => $businessAccount ? optional($details)->village_business : optional($details)->village,
                'post' => $businessAccount ? optional($details)->post_business : optional($details)->post,
                'city' => $businessAccount ? optional(optional($details)->businessCity)->name : optional(optional($details)->personalCity)->name,
                'district' => $businessAccount ? optional($details)->district_business : optional($details)->district,
                'state' => $businessAccount ? optional(optional($details)->businessState)->name : optional(optional($details)->personalState)->name,
                'pincode' => $businessAccount ? optional($details)->pincode_business : optional($details)->pincode,
                'country' => $businessAccount ? optional(optional($details)->businessCountry)->name : optional(optional($details)->personalCountry)->name,
                'sales_man_code' => optional($details)->salesman,
                'role' => $customer->user_subtype ?: $customer->user_type,
                'approval_status' => $approvalStatus,
                'current_status' => optional($details)->current_status,
                'credit_status' => $customer->credit_status,
                'credit_days' => $customer->credit_days,
                'credit_limit' => $customer->credit_limit,
                'credit_balance_amount' => single_price((float) $customer->balance),
                'default_shipping_method' => optional($details)->default_shipping_method
                    ?: 'transport',
                'default_transport_id' => optional($details)->transport_id,
                'default_booked_to_id' => optional($details)->booked_to_id,
                'default_transport_mode' => optional($details)->default_transport_mode ?: 'surface',
                'default_transport_surface_mode' => optional($details)->default_transport_surface_mode ?: 'road',
                'default_delivery_type' => optional($details)->default_delivery_type ?: 'door_delivery',
                'type_option' => InvoiceType::forUser($customer),
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
                'contact_person' => $address->contact_person,
                'village' => $address->village,
                'district' => $address->district,
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

        $products = Product::with([
            'stocks.batches',
            'brand',
            'thumbnail',
            'user',
            'main_category',
            'main_group',
            'categories',
            'groups',
            'taxes',
        ])
            ->where('approved', '1')
            ->where('published', 1)
            ->where('digital', 0)
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($nested) use ($query) {
                    $nested->where('name', 'like', '%' . $query . '%')
                        ->orWhere('barcode', 'like', '%' . $query . '%')
                        ->orWhereHas('stocks', function ($stocks) use ($query) {
                            $stocks->where('sku', 'like', '%' . $query . '%');
                        });
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json($products->map(function ($product) {
            $variantValueIds = $product->stocks
                ->flatMap(fn ($stock) => array_filter(explode('-', (string) $stock->id_variant), 'is_numeric'))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $variantValueLookup = AttributeValue::with('attribute')
                ->whereIn('id', $variantValueIds)
                ->get()
                ->keyBy('id');
            $stocks = $product->stocks
                ->filter(fn ($stock) => !(bool) ($stock->is_hidden ?? false))
                ->values()
                ->map(function ($stock) use ($product, $variantValueLookup) {
                    $variantAttributes = collect(explode('-', (string) $stock->id_variant))
                        ->filter(fn ($valueId) => is_numeric($valueId))
                        ->mapWithKeys(function ($valueId) use ($variantValueLookup) {
                            $attributeValue = $variantValueLookup->get((int) $valueId);
                            $attributeName = strtolower(trim((string) optional(optional($attributeValue)->attribute)->name));

                            return $attributeName !== '' ? [$attributeName => optional($attributeValue)->value] : [];
                        });
                    $batches = valid_batches_for_stock($stock, true)->map(function ($batch) {
                        return [
                            'id' => $batch->id,
                            'batch' => $batch->batch,
                            'qty' => (int) $batch->qty,
                            'mrp_price' => (float) ($batch->mrp_price ?? 0),
                            'role_price' => (float) ($batch->role_price ?? 0),
                            'scheme' => (int) ($batch->scheme ?? 0),
                            'manufacturing_date' => $batch->manufacturing_date,
                            'product_exp_date' => $batch->product_exp_date,
                            'stock_upload_date' => optional($batch->created_at)->toDateString(),
                            'discount' => (float) ($batch->discount ?? 0),
                            'discount_type' => $batch->discount_type,
                            'discount_active' => (bool) ($batch->discount_active ?? false),
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
                        'sku' => $stock->sku,
                        'variant_price' => (float) ($stock->price ?? 0),
                        'mrp_price' => (float) ($stock->mrp_price ?? 0),
                        'role_price' => (float) ($stock->role_price ?? 0),
                        'stock_upload_date' => optional($stock->created_at)->toDateString(),
                        'product_exp_date' => $stock->product_exp_date,
                        'current_stock' => (int) ($stock->qty ?? 0),
                        'pack_size' => $variantAttributes->get('pack size') ?: ($stock->variant ?: ($product->product_min_pack_size ?: $product->unit)),
                        'type' => $variantAttributes->get('type') ?: ($product->product_type ?: $product->product_form),
                        'quality' => $variantAttributes->get('quality'),
                        'no' => $variantAttributes->get('no') ?: $variantAttributes->get('no.'),
                        'material' => $variantAttributes->get('material') ?: $product->product_material,
                        'shape' => $variantAttributes->get('shape'),
                        'size' => $variantAttributes->get('size') ?: collect([$stock->length, $stock->width, $stock->height])
                            ->filter(fn ($value) => $value !== null && $value !== '')
                            ->implode(' × '),
                        'piece_weight' => $stock->weight,
                        'piece_length' => $stock->length,
                        'piece_width' => $stock->width,
                        'piece_height' => $stock->height,
                        'qty_per_piece' => $stock->qty_per_piece,
                        'qty_per_buffer_box' => $stock->qty_per_buffer_box,
                        'total_qty_per_case' => $stock->total_qty_per_case,
                        'weight_buffer_box' => $stock->weight_buffer_box,
                        'weight_case' => $stock->weight_case,
                        'buffer_length' => $stock->buffer_length,
                        'buffer_width' => $stock->buffer_width,
                        'buffer_height' => $stock->buffer_height,
                        'case_length' => $stock->case_length,
                        'case_width' => $stock->case_width,
                        'case_height' => $stock->case_height,
                        'batches' => $batches,
                    ];
                });

            $contentSections = is_array($product->contents)
                ? $product->contents
                : json_decode((string) $product->contents, true);
            $composition = collect(is_array($contentSections) ? $contentSections : [])
                ->filter(function ($section) {
                    return is_array($section)
                        && strcasecmp(trim((string) ($section['title'] ?? '')), 'Composition') === 0;
                })
                ->map(function ($section) {
                    $html = html_entity_decode((string) ($section['content'] ?? ''));
                    $html = preg_replace('/<br\s*\/?\s*>|<\/p\s*>|<\/li\s*>/iu', ' ', $html);

                    return trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
                })
                ->filter()
                ->implode(' ');
            $percentageTax = $product->taxes
                ->where('tax_type', 'percent')
                ->sum(fn ($tax) => (float) $tax->tax);
            if ($percentageTax <= 0 && $product->tax_type === 'percent') {
                $percentageTax = (float) $product->tax;
            }

            return [
                'id' => $product->id,
                'name' => $product->getTranslation('name'),
                'brand' => optional($product->brand)->name,
                'drug_name' => $product->drug_name,
                'drug_role' => $product->role_label,
                'composition' => \Illuminate\Support\Str::limit($composition, 600),
                'category' => optional($product->main_category)->getTranslation('name'),
                'categories' => $product->categories->map(fn ($category) => $category->getTranslation('name'))->filter()->values(),
                'group' => optional($product->main_group)->getTranslation('name'),
                'groups' => $product->groups->map(fn ($group) => $group->getTranslation('name'))->filter()->values(),
                'schedule' => $product->schedule,
                'unit' => $product->unit,
                'hsn_code' => $product->product_hsn,
                'hs_code' => $product->product_hs,
                'tax_percentage' => round($percentageTax, 2),
                'shipping_type' => $product->shipping_type,
                'shipping_cost' => (float) ($product->shipping_cost ?? 0),
                'owner_id' => $product->user_id,
                'owner_name' => optional($product->user)->name ?: translate('Inhouse'),
                'product_type' => $product->product_type ?: $product->product_form,
                'quality' => null,
                'material' => $product->product_material,
                'country_of_origin' => $product->product_origin,
                'pack_size' => $product->product_min_pack_size ?: $product->unit,
                'sku' => optional($stocks->first())->sku,
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

    public function backendOrderNumberPreview(Request $request)
    {
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
        ]);

        $company = Company::findOrFail($validated['company_id']);
        $parts = financial_year_order_code_parts($validated['order_date'], 'S', $company->code);
        $code = preview_financial_year_order_code($validated['order_date'], 'S', $company->code);

        return response()->json([
            'code' => $code,
            'company_code' => $parts['brand'],
            'series' => $parts['document'],
            'financial_year' => $parts['segment'],
            'number' => substr($code, strlen($parts['prefix'])),
        ]);
    }

    public function backendCourierRates(Request $request, OrderPlacementService $orders)
    {
        try {
            $existingOrder = null;
            if ($request->filled('order_id')) {
                $existingOrder = Order::with(['user', 'orderDetails.product.stocks'])
                    ->findOrFail($request->integer('order_id'));
                $customer = $existingOrder->user;
                if (!$customer) {
                    throw ValidationException::withMessages([
                        'order_id' => translate('The order customer could not be found.'),
                    ]);
                }
            } else {
                $customer = $orders->resolveApprovedCustomer($request->input('customer_id'));
            }

            $method = ShippingMethod::where('is_active', 1)->find($request->input('shipping_method_id'));
            if (!$method) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => translate('Please select an active courier provider.'),
                ]);
            }

            if ($existingOrder) {
                $address = null;
                $shippingAddress = json_decode((string) $existingOrder->shipping_address, true) ?: [];
                $toPincode = $shippingAddress['postal_code'] ?? null;
            } else {
                $addressId = $request->boolean('shipping_same_as_billing')
                    ? $request->input('billing_address_id')
                    : $request->input('shipping_address_id');
                $address = $addressId
                    ? Address::where('user_id', $customer->id)->find($addressId)
                    : null;
                $toPincode = optional($address)->postal_code
                    ?: $request->input($request->boolean('shipping_same_as_billing') ? 'billing_postal_code' : 'shipping_postal_code');
            }

            if (!$toPincode) {
                throw ValidationException::withMessages([
                    'shipping_address_id' => translate('Select a shipping address with a postal code before loading courier services.'),
                ]);
            }

            $weight = 0.0;
            $length = 0.0;
            $width = 0.0;
            $height = 0.0;
            if ($existingOrder) {
                $weight = $request->filled('weight_grams')
                    ? (float) $request->input('weight_grams') / 1000
                    : (float) ($existingOrder->weight_kg ?: ((float) $existingOrder->weight_grams / 1000));
                $length = $request->filled('length_cm')
                    ? (float) $request->input('length_cm')
                    : (float) $existingOrder->length_cm;
                $width = $request->filled('width_cm')
                    ? (float) $request->input('width_cm')
                    : (float) $existingOrder->width_cm;
                $height = $request->filled('height_cm')
                    ? (float) $request->input('height_cm')
                    : (float) $existingOrder->height_cm;

                if ($weight <= 0 || $length <= 0 || $width <= 0 || $height <= 0) {
                    $weight = 0.0;
                    $length = 0.0;
                    $width = 0.0;
                    $height = 0.0;
                    foreach ($existingOrder->orderDetails as $detail) {
                        $product = $detail->product;
                        if (!$product || (bool) ($detail->is_scheme ?? false)) {
                            continue;
                        }
                        $stock = $product->stocks->firstWhere('variant', $detail->variation);
                        $quantity = max(1, (int) $detail->quantity);
                        $weight += (float) ($stock->weight ?? $product->weight ?? 0.21) * $quantity;
                        $length += (float) ($stock->length ?? $product->length ?? 10) * $quantity;
                        $width += (float) ($stock->width ?? $product->width ?? 10) * $quantity;
                        $height += (float) ($stock->height ?? $product->height ?? 10) * $quantity;
                    }
                }
            } else {
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
                'payment_type' => $request->input('payment_type', optional($existingOrder)->payment_type) === 'cash_on_delivery' ? 'cod' : 'prepaid',
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

    public function deleteAttachment(Order $order, OrderAttachment $attachment)
    {
        abort_unless((int) $attachment->order_id === (int) $order->id, 404);

        $path = $attachment->path;
        $attachment->delete();

        if (!OrderAttachment::where('path', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }

        if ($order->cc_attached_path === $attachment->path) {
            $replacement = $order->attachments()
                ->where('category', 'consignee_copy')
                ->orderBy('id')
                ->first();
            $order->cc_attached_path = optional($replacement)->path;
        }
        if (!$order->attachments()->where('category', 'consignee_copy')->exists()
            && blank($order->cc_attached_path)) {
            $order->consignee_copy_status = 'not_attached';
        }
        $order->save();

        flash(translate('Attachment removed successfully.'))->success();

        return back();
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
            $customer = app(OrderPlacementService::class)->resolveApprovedCustomer($request->input('customer_id'));
            $invoiceType = InvoiceType::forUser($customer);
            $isInternational = $invoiceType === InvoiceType::INTERNATIONAL;
            $request->merge(['order_code_letter' => 'S']);

            if ($request->hasFile('cc_attachments')) {
                $request->merge(['consignee_copy_status' => 'attached']);
            }

            $shippingCostType = $request->input('shipping_cost_type');
            if (!in_array($shippingCostType, ['by_seller', 'free_shipping'], true)) {
                $shippingCostType = $request->boolean('free_shipping') ? 'free_shipping' : 'by_seller';
            }
            $request->merge([
                'shipping_cost_type' => $shippingCostType,
                'free_shipping' => $shippingCostType === 'free_shipping' ? 1 : 0,
            ]);
            if ($shippingCostType === 'free_shipping') {
                $request->merge(['shipping_costs' => [], 'shipping_items' => []]);
            }
            $usesPortLogistics = $request->input('shipping_method') === 'transport'
                && in_array($request->input('fod_mode'), ['sea', 'air'], true);

            $request->validate([
                'customer_id' => ['required', 'integer'],
                'company_id' => ['required', 'integer', 'exists:companies,id'],
                'payment_type' => ['required', Rule::in(array_keys(InvoiceType::paymentTerms($invoiceType)))],
                'order_no_preview' => ['nullable', 'string', 'max:191'],
                'order_code_letter' => ['required', 'string', 'size:1', 'regex:/^[A-Za-z]$/'],
                'order_date' => ['required', 'date'],
                'order_time' => ['required', 'date_format:H:i'],
                'cases' => ['nullable', 'integer', 'min:0'],
                'attached_file_name' => ['nullable', 'string', 'max:255'],
                'po_number' => ['nullable', 'string', 'max:255'],
                'po_date' => ['nullable', 'date'],
                'lr_number' => ['nullable', 'string', 'max:255'],
                'lr_date' => ['nullable', 'date'],
                'consignee_copy_status' => ['required', Rule::in(['attached', 'not_attached'])],
                'cc_attachments' => ['required_if:consignee_copy_status,attached', 'array', 'max:20'],
                'cc_attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv', 'max:10240'],
                'order_attachments' => ['nullable', 'array', 'max:20'],
                'order_attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv', 'max:10240'],
                'sales_executive_id' => ['nullable', 'integer', Rule::exists('staff', 'user_id')->where(fn ($query) => $query->where('status', 1))],
                'packed_by' => ['nullable', 'integer', Rule::exists('staff', 'user_id')->where(fn ($query) => $query->where('status', 1))],
                'checked_by' => ['nullable', 'integer', Rule::exists('staff', 'user_id')->where(fn ($query) => $query->where('status', 1))],
                'billing_by' => ['nullable', 'integer', Rule::exists('staff', 'user_id')->where(fn ($query) => $query->where('status', 1))],
                'weight_grams' => ['nullable', 'numeric', 'min:0', 'max:99999999999.999'],
                'length_cm' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_with:width_cm,height_cm'],
                'width_cm' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_with:length_cm,height_cm'],
                'height_cm' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_with:length_cm,width_cm'],
                'freight_type' => ['nullable', Rule::in(['pre_paid', 'to_pay', 'fod'])],
                'shipping_cost_type' => ['required', Rule::in(['by_seller', 'free_shipping'])],
                'free_shipping' => ['nullable', 'boolean'],
                'shipping_costs' => ['required_if:shipping_cost_type,by_seller', 'array'],
                'shipping_costs.*' => ['numeric', 'min:0', 'max:99999999999.99'],
                'shipping_costs_tax_inclusive' => ['nullable', 'array'],
                'shipping_costs_tax_inclusive.*' => ['boolean'],
                'shipping_items' => ['nullable', 'array'],
                'shipping_items.*.description' => ['nullable', 'string', 'max:255'],
                'shipping_items.*.seller_id' => ['nullable', 'integer'],
                'shipping_items.*.amount' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
                'shipping_items.*.source' => ['nullable', Rule::in(['manual', 'courier'])],
                'shipping_items.*.tax_inclusive' => ['nullable', 'boolean'],
                'transport_delivery_type' => ['required', Rule::in(array_keys(InvoiceType::deliveryTerms($invoiceType)))],
                'reverse_charge' => [$isInternational ? 'prohibited' : 'nullable', 'boolean'],
                'loading_location_type' => [$usesPortLogistics ? 'required' : 'nullable', Rule::in(['sea', 'air'])],
                'loading_sea_port_id' => [$usesPortLogistics && $request->input('loading_location_type') === 'sea' ? 'required' : 'nullable', 'integer', Rule::exists('sea_ports', 'id')],
                'loading_airport_id' => [$usesPortLogistics && $request->input('loading_location_type') === 'air' ? 'required' : 'nullable', 'integer', Rule::exists('airports', 'id')],
                'discharge_location_type' => [$usesPortLogistics ? 'required' : 'nullable', Rule::in(['sea', 'air'])],
                'discharge_sea_port_id' => [$usesPortLogistics && $request->input('discharge_location_type') === 'sea' ? 'required' : 'nullable', 'integer', Rule::exists('sea_ports', 'id')],
                'discharge_airport_id' => [$usesPortLogistics && $request->input('discharge_location_type') === 'air' ? 'required' : 'nullable', 'integer', Rule::exists('airports', 'id')],
                'final_destination' => ['nullable', 'string', 'max:255'],
                'carrier_tax_number' => ['nullable', 'string', 'max:100'],
                'net_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.999999'],
                'gross_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.999999'],
                'total_volume_cbm' => ['nullable', 'numeric', 'min:0', 'max:99999999.999999'],
                'transport_name' => ['nullable', 'string', 'max:255'],
                'booked_to_name' => ['nullable', 'string', 'max:255'],
                'local_delivery_partner_name' => ['nullable', 'string', 'max:255'],
                'shipping_contact_person' => ['nullable', 'string', 'max:255'],
                'shipping_address' => ['nullable', 'string', 'max:2000'],
                'shipping_village' => ['nullable', 'string', 'max:255'],
                'shipping_district' => ['nullable', 'string', 'max:255'],
                'shipping_postal_code' => ['nullable', 'string', 'max:20'],
                'shipping_phone' => ['nullable', 'string', 'max:50'],
                'shipping_country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
                'shipping_state_id' => ['nullable', 'integer', Rule::exists('states', 'id')],
                'shipping_city_id' => ['nullable', 'integer', Rule::exists('cities', 'id')],
            ]);

            $company = Company::findOrFail($request->integer('company_id'));
            $request->merge(['order_company_code' => $company->code]);

            $storedAttachments = [];
            foreach ([
                'cc_attachments' => ['category' => 'consignee_copy', 'directory' => 'uploads/order-cc-attachments'],
                'order_attachments' => ['category' => 'order_attachment', 'directory' => 'uploads/order-attachments'],
            ] as $input => $config) {
                foreach ($request->file($input, []) as $file) {
                    $storedAttachments[] = [
                        'category' => $config['category'],
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $file->store($config['directory'], 'public'),
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            $firstConsigneeCopy = collect($storedAttachments)->firstWhere('category', 'consignee_copy');
            $request->merge([
                'cc_attached_path' => $firstConsigneeCopy['path'] ?? null,
            ]);

            try {
                $combinedOrder = app(OrderPlacementService::class)->placeFromBackendRequest($request);
                $createdOrders = $combinedOrder->orders()->orderBy('id')->get();
                $firstOrder = $createdOrders->first();

                foreach ($createdOrders as $createdOrder) {
                    foreach ($storedAttachments as $attachment) {
                        $createdOrder->attachments()->create($attachment);
                    }
                }

                flash(translate('Order has been created successfully.'))->success();

                if ($firstOrder) {
                    return redirect()->route('all_orders.show', encrypt($firstOrder->id));
                }

                return redirect()->route('all_orders.index');
            } catch (ValidationException $exception) {
                Storage::disk('public')->delete(collect($storedAttachments)->pluck('path')->all());
                $message = collect($exception->errors())->flatten()->first() ?: translate('Unable to create order.');
                flash($message)->warning();
                return back()->withInput();
            } catch (\Throwable $exception) {
                Storage::disk('public')->delete(collect($storedAttachments)->pluck('path')->all());
                report($exception);
                flash(translate('Unable to create order. Please verify the form and try again.'))->error();
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
            'mode' => $request->input('fod_mode', 'surface'),
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
        $order = Order::with([
            'user.user_details',
            'orderDetails',
            'transport',
            'bookedTo',
            'localDeliveryPartner',
            'loadingSeaPort',
            'loadingAirport',
            'dischargeSeaPort',
            'dischargeAirport',
            'attachments',
            'shipment',
        ])->findOrFail($id);
        $invoiceType = InvoiceType::forUser($order->user);
        $currentShippingMethod = optional($order->shipment)->shipping_method_id
            ? ShippingMethod::find($order->shipment->shipping_method_id)
            : null;
        if (!$currentShippingMethod && filled($order->shipping_by)) {
            $currentShippingMethod = ShippingMethod::where('slug', $order->shipping_by)->first();
        }
        $shippingMethods = ShippingMethod::where('is_active', 1)->orderBy('name')->get();
        if ($currentShippingMethod && !$shippingMethods->contains('id', $currentShippingMethod->id)) {
            $shippingMethods->push($currentShippingMethod);
        }
        $courierConfigurationLocked = $order->shipping_choice === 'courier'
            && filled(optional($order->shipment)->shipping_id)
            && optional($order->shipment)->status !== 'error';
        $transports = Transport::active()->orderBy('name')->get();
        $bookedToOptions = BookedTo::active()->orderBy('name')->get();
        $localDeliveryPartners = LocalDeliveryPartner::active()->orderBy('name')->get();
        $seaPorts = SeaPort::where('status', 1)->orderBy('country')->orderBy('name')->get();
        $airports = Airport::where('status', 1)->orderBy('country')->orderBy('name')->get();
        extract($this->orderFormStaffOptions([
            $order->sales_executive_id ?: $order->sales_person_id,
            $order->packed_by,
            $order->checked_by,
            $order->billing_by,
        ]));
        $sellAmount = (float) $order->orderDetails->sum('shipping_cost');

        return view('backend.sales.edit', compact(
            'order',
            'invoiceType',
            'shippingMethods',
            'currentShippingMethod',
            'courierConfigurationLocked',
            'transports',
            'bookedToOptions',
            'localDeliveryPartners',
            'seaPorts',
            'airports',
            'salesPeople',
            'packedStaff',
            'checkedStaff',
            'billingStaff',
            'sellAmount'
        ));
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
        $order = Order::with(['orderDetails', 'attachments', 'shipment'])->findOrFail($id);
        $invoiceType = InvoiceType::forUser($order->user);
        $shippingChoice = $order->shipping_choice ?: 'transport';
        $courierConfigurationLocked = $shippingChoice === 'courier'
            && filled(optional($order->shipment)->shipping_id)
            && optional($order->shipment)->status !== 'error';
        $usesPortLogistics = $shippingChoice === 'transport'
            && in_array($request->input('fod_mode', $order->fod_mode), ['sea', 'air'], true);

        if ($request->hasFile('cc_attachments')) {
            $request->merge(['consignee_copy_status' => 'attached']);
        }

        $validated = $request->validate([
            'payment_type' => ['required', Rule::in(array_keys(InvoiceType::paymentTerms($invoiceType)))],
            'shipping_method_id' => ['nullable', 'integer', Rule::exists('shipping_methods', 'id')->where(fn ($query) => $query->where('is_active', 1))],
            'courier_service' => ['nullable', 'string', 'max:191'],
            'transport_id' => ['nullable', 'integer', Rule::exists('transports', 'id')],
            'transport_delivery_type' => ['required', Rule::in(array_merge(array_keys(InvoiceType::deliveryTerms($invoiceType)), ['transport_godown']))],
            'booked_to_id' => ['nullable', 'integer', Rule::exists('booked_to', 'id')],
            'local_delivery_partner_id' => ['nullable', 'integer', Rule::exists('local_delivery_partners', 'id')],
            'consignee_copy_status' => ['required', Rule::in(['attached', 'not_attached'])],
            'cc_attachments' => ['nullable', 'array', 'max:20'],
            'cc_attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv', 'max:10240'],
            'order_attachments' => ['nullable', 'array', 'max:20'],
            'order_attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv', 'max:10240'],
            'fod_mode' => ['nullable', Rule::in(['surface', 'air', 'sea'])],
            'transport_surface_mode' => ['nullable', Rule::in(['road', 'train'])],
            'freight_type' => ['nullable', Rule::in(['pre_paid', 'to_pay', 'fod'])],
            'shipping_cost_type' => ['required', Rule::in(['by_seller', 'free_shipping'])],
            'sell_amount' => ['required_if:shipping_cost_type,by_seller', 'nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'reverse_charge' => [InvoiceType::isDomestic($invoiceType) ? 'nullable' : 'prohibited', 'boolean'],
            'loading_location_type' => [$usesPortLogistics ? 'required' : 'nullable', Rule::in(['sea', 'air'])],
            'loading_sea_port_id' => [$usesPortLogistics && $request->input('loading_location_type') === 'sea' ? 'required' : 'nullable', 'integer', Rule::exists('sea_ports', 'id')],
            'loading_airport_id' => [$usesPortLogistics && $request->input('loading_location_type') === 'air' ? 'required' : 'nullable', 'integer', Rule::exists('airports', 'id')],
            'discharge_location_type' => [$usesPortLogistics ? 'required' : 'nullable', Rule::in(['sea', 'air'])],
            'discharge_sea_port_id' => [$usesPortLogistics && $request->input('discharge_location_type') === 'sea' ? 'required' : 'nullable', 'integer', Rule::exists('sea_ports', 'id')],
            'discharge_airport_id' => [$usesPortLogistics && $request->input('discharge_location_type') === 'air' ? 'required' : 'nullable', 'integer', Rule::exists('airports', 'id')],
            'final_destination' => ['nullable', 'string', 'max:255'],
            'carrier_tax_number' => ['nullable', 'string', 'max:100'],
            'cases' => ['nullable', 'integer', 'min:0'],
            'weight_grams' => ['nullable', 'numeric', 'min:0', 'max:99999999999.999'],
            'length_cm' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_with:width_cm,height_cm'],
            'width_cm' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_with:length_cm,height_cm'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'required_with:length_cm,width_cm'],
            'attached_file_name' => ['nullable', 'string', 'max:255'],
            'additional_info' => ['nullable', 'string', 'max:2000'],
            'po_number' => ['nullable', 'string', 'max:255'],
            'po_date' => ['nullable', 'date'],
            'lr_number' => ['nullable', 'string', 'max:255'],
            'lr_date' => ['nullable', 'date'],
            'net_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.999999'],
            'gross_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.999999'],
            'total_volume_cbm' => ['nullable', 'numeric', 'min:0', 'max:99999999.999999'],
            'sales_executive_id' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'user_id')->where(function ($query) use ($order) {
                    $query->where('status', 1)
                        ->orWhere('user_id', $order->sales_executive_id ?: $order->sales_person_id);
                }),
            ],
            'packed_by' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'user_id')->where(function ($query) use ($order) {
                    $query->where('status', 1)->orWhere('user_id', $order->packed_by);
                }),
            ],
            'checked_by' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'user_id')->where(function ($query) use ($order) {
                    $query->where('status', 1)->orWhere('user_id', $order->checked_by);
                }),
            ],
            'billing_by' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'user_id')->where(function ($query) use ($order) {
                    $query->where('status', 1)->orWhere('user_id', $order->billing_by);
                }),
            ],
        ]);

        if ($shippingChoice === 'transport'
            && ($validated['fod_mode'] ?? $order->fod_mode) === 'surface'
            && empty($validated['transport_surface_mode'])) {
            throw ValidationException::withMessages([
                'transport_surface_mode' => translate('Please select Road or Train for Surface transport.'),
            ]);
        }

        if ($shippingChoice === 'transport' && empty($validated['transport_id'])) {
            throw ValidationException::withMessages([
                'transport_id' => translate('Please select a transport provider.'),
            ]);
        }

        if ($shippingChoice === 'transport' && empty($validated['booked_to_id'])) {
            throw ValidationException::withMessages([
                'booked_to_id' => translate('Please select a booked-to destination.'),
            ]);
        }

        if ($shippingChoice === 'local' && empty($validated['local_delivery_partner_id'])) {
            throw ValidationException::withMessages([
                'local_delivery_partner_id' => translate('Please select a local delivery partner.'),
            ]);
        }

        if ($shippingChoice === 'courier' && !$courierConfigurationLocked) {
            if (empty($validated['shipping_method_id'])) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => translate('Please select an active courier provider.'),
                ]);
            }
            if (empty($validated['courier_service'])) {
                throw ValidationException::withMessages([
                    'courier_service' => translate('Please select a courier service.'),
                ]);
            }
        }

        if ($shippingChoice === 'transport' && !empty($validated['booked_to_id']) && !empty($validated['transport_id'])) {
            $bookedToMatchesTransport = BookedTo::whereKey($validated['booked_to_id'])
                ->where('transport_id', $validated['transport_id'])
                ->exists();
            if (!$bookedToMatchesTransport) {
                throw ValidationException::withMessages([
                    'booked_to_id' => translate('The booked-to destination does not belong to the selected transport.'),
                ]);
            }
        }

        $hasConsigneeCopy = $order->attachments->where('category', 'consignee_copy')->isNotEmpty()
            || filled($order->cc_attached_path)
            || $request->hasFile('cc_attachments');
        if ($validated['consignee_copy_status'] === 'attached' && !$hasConsigneeCopy) {
            throw ValidationException::withMessages([
                'cc_attachments' => translate('Please attach at least one consignee copy file.'),
            ]);
        }

        $storedAttachments = [];
        foreach ([
            'cc_attachments' => ['category' => 'consignee_copy', 'directory' => 'uploads/order-cc-attachments'],
            'order_attachments' => ['category' => 'order_attachment', 'directory' => 'uploads/order-attachments'],
        ] as $input => $config) {
            foreach ($request->file($input, []) as $file) {
                $storedAttachments[] = [
                    'category' => $config['category'],
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $file->store($config['directory'], 'public'),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        try {
            DB::transaction(function () use ($order, $validated, $storedAttachments, $shippingChoice, $courierConfigurationLocked, $usesPortLogistics, $invoiceType) {
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                $details = OrderDetail::where('order_id', $lockedOrder->id)->lockForUpdate()->get();
                $oldShipping = (float) $details->sum('shipping_cost');
                $newShipping = $validated['shipping_cost_type'] === 'free_shipping'
                    ? 0.0
                    : (float) ($validated['sell_amount'] ?? 0);

                foreach ($details as $detail) {
                    $detail->shipping_cost = 0;
                    $detail->save();
                }
                $paidDetail = $details->first(fn ($detail) => !(bool) ($detail->is_scheme ?? false));
                if ($paidDetail) {
                    $paidDetail->shipping_cost = $newShipping;
                    $paidDetail->save();
                }

                $lockedOrder->payment_type = $validated['payment_type'];
                $lockedOrder->transport_id = $shippingChoice === 'transport' ? ($validated['transport_id'] ?? null) : null;
                $lockedOrder->booked_to_id = $shippingChoice === 'transport' ? ($validated['booked_to_id'] ?? null) : null;
                $lockedOrder->local_delivery_partner_id = $shippingChoice === 'local' ? ($validated['local_delivery_partner_id'] ?? null) : null;
                $lockedOrder->shipping_by = $shippingChoice === 'transport'
                    ? optional(Transport::find($validated['transport_id'] ?? null))->name
                    : ($shippingChoice === 'local'
                        ? optional(LocalDeliveryPartner::find($validated['local_delivery_partner_id'] ?? null))->name
                        : $lockedOrder->shipping_by);
                if ($shippingChoice === 'courier' && !$courierConfigurationLocked) {
                    $courierMethod = ShippingMethod::where('is_active', 1)
                        ->findOrFail($validated['shipping_method_id']);
                    $lockedOrder->shipping_by = $courierMethod->slug;
                    $lockedOrder->shipping_courier_id = $validated['courier_service'];

                    if ($lockedOrder->shipment
                        && (!filled($lockedOrder->shipment->shipping_id) || $lockedOrder->shipment->status === 'error')) {
                        $lockedOrder->shipment->shipping_method_id = $courierMethod->id;
                        $lockedOrder->shipment->save();
                    }
                }
                $deliveryType = ($validated['transport_delivery_type'] ?? null) === 'transport_godown'
                    ? 'transport_warehouse'
                    : ($validated['transport_delivery_type'] ?? null);
                $lockedOrder->transport_delivery_type = $deliveryType;
                $lockedOrder->fod_mode = $shippingChoice === 'transport' ? ($validated['fod_mode'] ?? null) : null;
                $lockedOrder->transport_mode = $lockedOrder->fod_mode;
                $lockedOrder->transport_surface_mode = $shippingChoice === 'transport'
                    && ($validated['fod_mode'] ?? null) === 'surface'
                    ? ($validated['transport_surface_mode'] ?? null)
                    : null;
                $lockedOrder->consignee_copy_status = $validated['consignee_copy_status'];
                $lockedOrder->freight_type = $validated['freight_type'] ?? null;
                $lockedOrder->freight_paid = ($validated['freight_type'] ?? null) === 'pre_paid';
                $lockedOrder->free_shipping = $validated['shipping_cost_type'] === 'free_shipping';
                $lockedOrder->reverse_charge = InvoiceType::isDomestic($invoiceType)
                    ? (bool) ($validated['reverse_charge'] ?? false)
                    : null;
                $lockedOrder->loading_location_type = $usesPortLogistics ? ($validated['loading_location_type'] ?? null) : null;
                $lockedOrder->loading_sea_port_id = $usesPortLogistics && ($validated['loading_location_type'] ?? null) === 'sea'
                    ? ($validated['loading_sea_port_id'] ?? null)
                    : null;
                $lockedOrder->loading_airport_id = $usesPortLogistics && ($validated['loading_location_type'] ?? null) === 'air'
                    ? ($validated['loading_airport_id'] ?? null)
                    : null;
                $lockedOrder->discharge_location_type = $usesPortLogistics ? ($validated['discharge_location_type'] ?? null) : null;
                $lockedOrder->discharge_sea_port_id = $usesPortLogistics && ($validated['discharge_location_type'] ?? null) === 'sea'
                    ? ($validated['discharge_sea_port_id'] ?? null)
                    : null;
                $lockedOrder->discharge_airport_id = $usesPortLogistics && ($validated['discharge_location_type'] ?? null) === 'air'
                    ? ($validated['discharge_airport_id'] ?? null)
                    : null;
                $lockedOrder->final_destination = $usesPortLogistics ? ($validated['final_destination'] ?? null) : null;
                $lockedOrder->carrier_tax_number = $validated['carrier_tax_number'] ?? null;
                $lockedOrder->cases = $validated['cases'] ?? null;
                $lockedOrder->weight_grams = $validated['weight_grams'] ?? null;
                $lockedOrder->weight_kg = isset($validated['weight_grams'])
                    ? (float) $validated['weight_grams'] / 1000
                    : null;
                $lockedOrder->length_cm = $validated['length_cm'] ?? null;
                $lockedOrder->width_cm = $validated['width_cm'] ?? null;
                $lockedOrder->height_cm = $validated['height_cm'] ?? null;
                $lockedOrder->attached_file_name = $validated['attached_file_name'] ?? null;
                $lockedOrder->additional_info = $validated['additional_info'] ?? null;
                $lockedOrder->po_number = $validated['po_number'] ?? null;
                $lockedOrder->po_date = $validated['po_date'] ?? null;
                $lockedOrder->lr_number = $validated['lr_number'] ?? null;
                $lockedOrder->lr_date = $validated['lr_date'] ?? null;
                $lockedOrder->net_weight_kg = $validated['net_weight_kg'] ?? null;
                $lockedOrder->gross_weight_kg = $validated['gross_weight_kg'] ?? null;
                $lockedOrder->total_volume_cbm = $validated['total_volume_cbm'] ?? null;
                $lockedOrder->sales_person_id = $validated['sales_executive_id'] ?? null;
                $lockedOrder->sales_executive_id = $validated['sales_executive_id'] ?? null;
                $lockedOrder->packed_by = $validated['packed_by'] ?? null;
                $lockedOrder->checked_by = $validated['checked_by'] ?? null;
                $lockedOrder->billing_by = $validated['billing_by'] ?? null;
                $lockedOrder->grand_total = max(0, (float) $lockedOrder->grand_total - $oldShipping + $newShipping);

                if ($validated['consignee_copy_status'] === 'not_attached') {
                    $lockedOrder->cc_attached_path = null;
                } elseif (!$lockedOrder->cc_attached_path && !empty($storedAttachments)) {
                    $lockedOrder->cc_attached_path = $storedAttachments[0]['path'];
                }
                $lockedOrder->save();

                if ($lockedOrder->combined_order_id) {
                    $combinedOrder = CombinedOrder::whereKey($lockedOrder->combined_order_id)->lockForUpdate()->first();
                    if ($combinedOrder) {
                        $combinedOrder->grand_total = max(0, (float) $combinedOrder->grand_total - $oldShipping + $newShipping);
                        $combinedOrder->save();
                    }
                }

                foreach ($storedAttachments as $attachment) {
                    $lockedOrder->attachments()->create($attachment);
                }
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(collect($storedAttachments)->pluck('path')->all());
            throw $exception;
        }

        flash(translate('Order has been updated successfully.'))->success();

        return redirect()->route('all_orders.show', encrypt($order->id));
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

    /**
     * Use Staff Master role/designation data for all order staff selectors.
     * Operational selectors are restricted to their exact configured role.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    protected function orderFormStaffOptions(array $selectedUserIds = []): array
    {
        $selectedUserIds = array_values(array_filter(array_map('intval', $selectedUserIds)));
        $staffMaster = Staff::with(['user', 'role'])
            ->whereHas('user')
            ->where(function ($query) use ($selectedUserIds) {
                $query->where('status', 1);

                if (!empty($selectedUserIds)) {
                    $query->orWhereIn('user_id', $selectedUserIds);
                }
            })
            ->get()
            ->sortBy(fn ($staff) => strtolower((string) optional($staff->user)->name))
            ->values();
        $staffFor = function (string $pattern) use ($staffMaster) {
            $matched = $staffMaster->filter(function ($staff) use ($pattern) {
                $staffArea = strtolower(trim(implode(' ', [
                    $staff->designation,
                    optional($staff->role)->name,
                ])));

                return (bool) preg_match($pattern, $staffArea);
            })->values();

            if ($matched->isEmpty()) {
                return $staffMaster;
            }

            return $matched
                ->concat($staffMaster->whereNotIn('user_id', $matched->pluck('user_id')))
                ->values();
        };
        $staffForRole = function (string $roleName) use ($staffMaster) {
            return $staffMaster
                ->filter(function ($staff) use ($roleName) {
                    return strcasecmp(trim((string) optional($staff->role)->name), $roleName) === 0;
                })
                ->values();
        };

        return [
            'salesPeople' => $staffFor('/sales|business development|marketing/i'),
            'packedStaff' => $staffForRole('Packing'),
            'checkedStaff' => $staffForRole('Checking'),
            'billingStaff' => $staffForRole('Billing'),
        ];
    }
}
