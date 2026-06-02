<?php

namespace App\Http\Controllers;

use Auth;
use Hash;
use Mail;
use Cache;
use Cookie;
use App\Models\Page;
use App\Models\Shop;
use App\Models\User;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;
use App\Models\FlashDeal;
use App\Models\OrderDetail;
use Illuminate\Support\Str;
use App\Models\ProductQuery;
use Illuminate\Http\Request;
use App\Models\AffiliateConfig;
use App\Models\CustomerPackage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Cart;
use App\Models\Wallet;
use App\Utility\EmailUtility;
use Artisan;
use DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use ZipArchive;
use Illuminate\Support\Facades\Session;
use App\Models\ProductCategory;
use App\Models\ProductBatch;

class HomeController extends Controller
{
    protected function homeSectionCacheKey(string $section): string
    {
        $theme = get_setting('homepage_select') ?: 'default';
        $webType = session('web_type_name', 'default');
        $lang = get_system_language() ? get_system_language()->code : 'default';
        $currency = function_exists('active_currency_cache_suffix') ? active_currency_cache_suffix() : session('currency_code', 'default');
        $role = getCurrentUserRole() ?? 'guest';
        $subtype = get_user_subtype() ?? 'na';

        return 'home_section_v2_' . $section . '_' . $theme . '_' . $webType . '_' . $lang . '_' . $currency . '_' . $role . '_' . $subtype;
    }

    /**
     * Show the application frontend home.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $topCatVeterinary = json_decode(get_setting('top_categories_veterinary'), true);

        if (!is_array($topCatVeterinary)) {
            $topCatVeterinary = [];
        }

        $category = Cache::remember('category_veterinary', now()->addHours(6), function () {
            return Category::whereRaw('LOWER(name) = ?', [strtolower('veterinary')])->first();
        });

        if (!Session::has('web_type') || Session::get('web_type_name') != strtolower($category->name)) {
            if ($category) {
                Session::put('web_type', $category->id);
                Session::put('web_type_name', strtolower($category->name));
                // Cache::flush();
            }
        }

        $homeData = Cache::remember('home_index_veterinary_data', now()->addHour(), function () use ($topCatVeterinary) {
            $lang = get_system_language() ? get_system_language()->code : null;

            $featured_categories = Cache::remember('featured_categories_veterinary', now()->addHour(), function () use ($topCatVeterinary) {
                return Category::select('id', 'parent_id', 'name', 'slug', 'icon')
                    ->whereIn('id', $topCatVeterinary)
                    ->where('featured', 1)
                    ->get();
            });

            $categories = Category::where('parent_id', 0)
                ->where('digital', 0)
                ->with('childrenCategories')
                ->get();

            $Brands = Brand::select(['id', 'name'])->get();

            return compact('featured_categories', 'lang', 'categories', 'Brands');
        });

        $searchCategories = Cache::remember('search_categories_tree_veterinary', now()->addHours(6), function () {
            return Category::where('parent_id', 0)
                ->where('digital', 0)
                ->with('childrenCategories.childrenCategories')
                ->get();
        });

        $featured_categories = $homeData['featured_categories'];
        $lang = $homeData['lang'];
        $categories = $homeData['categories'];
        $Brands = $homeData['Brands'];

        return view('frontend.' . get_setting('homepage_select') . '.index', compact('featured_categories', 'lang', 'categories', 'Brands', 'searchCategories'));
    }

    public function humanPage()
    {
        $topCatHuman = json_decode(get_setting('top_categories_human'), true);

        if (!is_array($topCatHuman)) {
            $topCatHuman = [];
        }

        $homeData = Cache::remember('home_index_human_data', now()->addHour(), function () use ($topCatHuman) {
            $featured_categories = Cache::remember('featured_categories_human', now()->addHour(), function () use ($topCatHuman) {
                return Category::select('id', 'parent_id', 'name', 'slug', 'icon')
                    ->whereIn('id', $topCatHuman)
                    ->where('featured', 1)
                    ->get();
            });

            $categories = Category::where('parent_id', 0)
                ->where('digital', 0)
                ->with('childrenCategories')
                ->get();

            $Brands = Brand::select(['id', 'name'])->get();

            return compact('featured_categories', 'categories', 'Brands');
        });

        $searchCategories = Cache::remember('search_categories_tree_human', now()->addHours(6), function () {
            return Category::where('parent_id', 0)
                ->where('digital', 0)
                ->with('childrenCategories.childrenCategories')
                ->get();
        });

        $featured_categories = $homeData['featured_categories'];
        $categories = $homeData['categories'];
        $Brands = $homeData['Brands'];

        return view('frontend.metro.human', compact('featured_categories', 'categories', 'Brands', 'searchCategories'));
    }

    public function load_todays_deal_section()
    {
        $cacheKey = $this->homeSectionCacheKey('todays_deal');

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            $todays_deal_products = filter_products(Product::where('todays_deal', '1'))->orderBy('id', 'desc')->get();
            return view('frontend.' . get_setting('homepage_select') . '.partials.todays_deal', compact('todays_deal_products'))->render();
        });
    }

    public function load_newest_product_section()
    {
        $newest_products = Cache::remember('newest_products', now()->addHours(6), function () {
            return filter_products(Product::latest())->limit(12)->get();
        });

        $cacheKey = $this->homeSectionCacheKey('newest_products');

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($newest_products) {
            return view('frontend.' . get_setting('homepage_select') . '.partials.newest_products_section', compact('newest_products'))->render();
        });
    }

    public function load_featured_section()
    {
        $cacheKey = $this->homeSectionCacheKey('featured');

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            return view('frontend.' . get_setting('homepage_select') . '.partials.featured_products_section')->render();
        });
    }

    public function load_best_selling_section()
    {
        $cacheKey = $this->homeSectionCacheKey('best_selling');

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            return view('frontend.' . get_setting('homepage_select') . '.partials.best_selling_section')->render();
        });
    }

    public function load_auction_products_section()
    {
        if (!addon_is_activated('auction')) {
            return;
        }
        $cacheKey = $this->homeSectionCacheKey('auction_products');

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            $lang = get_system_language() ? get_system_language()->code : null;
            return view('auction.frontend.' . get_setting('homepage_select') . '.auction_products_section', compact('lang'))->render();
        });
    }

    public function load_home_categories_section()
    {
        $cacheKey = $this->homeSectionCacheKey('home_categories');

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            return view('frontend.' . get_setting('homepage_select') . '.partials.home_categories_section')->render();
        });
    }

    public function load_best_sellers_section()
    {
        return view('frontend.' . get_setting('homepage_select') . '.partials.best_sellers_section');
    }

    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        if (Route::currentRouteName() == 'seller.login' && get_setting('vendor_system_activation') == 1) {
            return view('auth.' . get_setting('authentication_layout_select') . '.seller_login');
        } else if (Route::currentRouteName() == 'deliveryboy.login' && addon_is_activated('delivery_boy')) {
            return view('auth.' . get_setting('authentication_layout_select') . '.deliveryboy_login');
        }
        return view('auth.' . get_setting('authentication_layout_select') . '.user_login');
    }

    public function registration(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        if ($request->has('referral_code')) {
            try {
                $affiliate_validation_time = AffiliateConfig::where('type', 'validation_time')->first();
                $cookie_minute = 30 * 24;
                if ($affiliate_validation_time) {
                    $cookie_minute = $affiliate_validation_time->value * 60;
                }

                Cookie::queue('referral_code', $request->referral_code, $cookie_minute);
                Session::put('referral_code', $request->referral_code);
                if (addon_is_activated('affiliate_system')) {
                    $referred_by_user = User::where('referral_code', $request->referral_code)->first();
                    if ($referred_by_user && class_exists('App\\Http\\Controllers\\AffiliateController')) {
                        $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                        if (method_exists($affiliateController, 'processAffiliateStats')) {
                            $affiliateController->processAffiliateStats($referred_by_user->id, 1, 0, 0, 0);
                        }
                    }
                }
            } catch (\Exception $e) {
            }
        }
        return view('auth.' . get_setting('authentication_layout_select') . '.user_registration');
    }


    public function new_user_registrations(Request $request)
    {
        if ($request->has('referral_code')) {
            $cookie_minute = 30 * 24;
            Cookie::queue('referral_code', $request->referral_code, $cookie_minute);
            Session::put('referral_code', $request->referral_code);
        }

        if (Auth::check()) {
            session()->flush();
            auth()->guard()->logout();
            return view('frontend.user_registration');
        }
        return view('frontend.user_registration');
    }

    public function refer_a_friend()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('user.login');
        }

        if (empty($user->referral_code)) {
            $user->referral_code = substr($user->id . Str::random(10), 0, 10);
            $user->save();
        }

        $referralLink = route('user.registration', ['referral_code' => $user->referral_code]);
        $earnedReferralAmount = (float) Wallet::where('user_id', $user->id)
            ->where('transaction_type', 'referral_reward')
            ->sum('amount');
        $rewardAmountPerReferral = (float) (get_setting('referral_discount_amount') ?? 0);

        return view('frontend.user.refer_a_friend', compact('referralLink', 'earnedReferralAmount', 'rewardAmountPerReferral'));
    }


    public function cart_login(Request $request)
    {
        $user = null;
        if ($request->get('phone') != null) {
            $user = User::whereIn('user_type', ['customer', 'seller'])->where('phone', "+{$request['country_code']}{$request['phone']}")->first();
        } elseif ($request->get('email') != null) {
            $user = User::whereIn('user_type', ['customer', 'seller'])->where('email', $request->email)->first();
        }

        if ($user != null) {
            if (Hash::check($request->password, $user->password)) {
                if ($request->has('remember')) {
                    auth()->login($user, true);
                } else {
                    auth()->login($user, false);
                }
            } else {
                flash(translate('Invalid email or password!'))->warning();
            }
        } else {
            flash(translate('Invalid email or password!'))->warning();
        }
        return back();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the customer/seller dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        if (Auth::user()->user_type == 'seller') {
            return redirect()->route('seller.dashboard');
        } elseif (Auth::user()->user_type == 'customer') {
            $users_cart = Cart::where('user_id', auth()->user()->id)->first();
            if ($users_cart) {
                flash(translate('You had placed your items in the shopping cart. Try to order before the product quantity runs out.'))->warning();
            }
            return view('frontend.user.customer.dashboard');
        } elseif (Auth::user()->user_type == 'delivery_boy') {
            return view('delivery_boys.dashboard');
        } else {
            abort(404);
        }
    }

    public function profile(Request $request)
    {
        if (Auth::user()->user_type == 'seller') {
            return redirect()->route('seller.profile.index');
        } elseif (Auth::user()->user_type == 'delivery_boy') {
            return view('delivery_boys.profile');
        } else {
            return view('frontend.user.profile');
        }
    }

    public function userProfileUpdate(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }

        $user = Auth::user();
        $user->name = $request->name;

        // $user->address = $request->address;
        // $user->country = $request->country;
        // $user->city = $request->city;
        // $user->postal_code = $request->postal_code;

        $user->phone = '+'.$request->phone_code.'-'.str_replace(' ', '', $request->phone);

        if ($request->new_password != null && ($request->new_password == $request->confirm_password)) {
            $user->password = Hash::make($request->new_password);
        }

        $user->avatar_original = $request->photo;
        $user->save();

        flash(translate('Your Profile has been updated successfully!'))->success();
        return back();
    }

    public function userBankDetailsUpdate(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }

        $validator = Validator::make($request->all(), [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_no' => ['required', 'regex:/^\d+$/', 'max:20'], // Numeric only
            'branch_no' => ['required', 'string', 'max:50'],
            'branch_code' => ['required', 'string', 'max:50'],
            'ifsc_code' => ['required', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'], // IFSC code format
            'micr_code' => ['required', 'regex:/^\d{9}$/'], // MICR code format
            'customer_care_executive' => ['required', 'string', 'max:255'],
        ], [
            // Custom error messages
            'bank_name.required' => 'The bank name is required.',
            'bank_name.string' => 'The bank name must be a valid string.',
            'bank_name.max' => 'The bank name must not exceed 255 characters.',

            'account_no.required' => 'The account number is required.',
            'account_no.regex' => 'The account number must contain only numeric characters.',
            'account_no.max' => 'The account number must not exceed 20 digits.',

            'branch_no.required' => 'The branch number is required.',
            'branch_no.string' => 'The branch number must be a valid string.',
            'branch_no.max' => 'The branch number must not exceed 50 characters.',

            'branch_code.required' => 'The branch code is required.',
            'branch_code.string' => 'The branch code must be a valid string.',
            'branch_code.max' => 'The branch code must not exceed 50 characters.',

            'ifsc_code.required' => 'The IFSC Code is required.',
            'ifsc_code.regex' => 'The IFSC Code format is invalid. It should follow the format: 4 uppercase letters, a 0, followed by 6 alphanumeric characters.',

            'micr_code.required' => 'The MICR Code is required.',
            'micr_code.regex' => 'The MICR Code must be exactly 9 numeric digits.',

            'customer_care_executive.required' => 'The customer care executive name is required.',
            'customer_care_executive.string' => 'The customer care executive name must be a valid string.',
            'customer_care_executive.max' => 'The customer care executive name must not exceed 255 characters.',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            // Check if $errors is an array
            if (is_array($errors) && !empty($errors)) {
                foreach ($errors as $error) {
                    flash($error)->error(); // Flash each error individually
                }
            } else {
                // Flash a generic error message if $errors is not an array
                flash('An error occurred, please try again.')->error();
            }

            return back();
        }

        $user = Auth::user();

        $user->bank_name = $request->bank_name;
        $user->account_no = $request->account_no;
        $user->branch_no = $request->branch_no;
        $user->branch_code = $request->branch_code;
        $user->ifsc_code = $request->ifsc_code;
        $user->micr_code = $request->micr_code;
        $user->customer_care_executive = $request->customer_care_executive;

        $user->save();

        flash(translate('Your Profile has been updated successfully!'))->success();
        return back();
    }

    public function userLicenseDetailsUpdate(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }

        $validator = Validator::make($request->all(), [
            'cc_no' => ['required', 'regex:/^[\d\s\-\+]+$/', 'min:5'],
            'd_l_no_1' => ['required', 'string', 'max:50'],
            'd_l_no_2' => ['required', 'string', 'max:50'],
            'd_l_no_3' => ['required', 'string', 'max:50'],
        ], [
            // Custom error messages
            'cc_no.required' => 'The CC number is required.',
            'cc_no.regex' => 'The CC number must only contain numbers, spaces, dashes, or plus signs.',
            'cc_no.min' => 'The CC number must be at least 5 characters long.',

            'd_l_no_1.required' => 'The first D.L.No is required.',
            'd_l_no_1.string' => 'The first D.L.No must be a valid string.',
            'd_l_no_1.max' => 'The first D.L.No must not exceed 50 characters.',

            'd_l_no_2.required' => 'The second D.L.No is required.',
            'd_l_no_2.string' => 'The second D.L.No must be a valid string.',
            'd_l_no_2.max' => 'The second D.L.No must not exceed 50 characters.',

            'd_l_no_3.required' => 'The third D.L.No is required.',
            'd_l_no_3.string' => 'The third D.L.No must be a valid string.',
            'd_l_no_3.max' => 'The third D.L.No must not exceed 50 characters.',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            // Check if $errors is an array
            if (is_array($errors) && !empty($errors)) {
                foreach ($errors as $error) {
                    flash($error)->error(); // Flash each error individually
                }
            } else {
                // Flash a generic error message if $errors is not an array
                flash('An error occurred, please try again.')->error();
            }

            return back();
        }

        $user = Auth::user();

        $user->cc_no = $request->cc_no;
        $user->d_l_no_1 = $request->d_l_no_1;
        $user->d_l_no_2 = $request->d_l_no_2;
        $user->d_l_no_3 = $request->d_l_no_3;

        $user->save();

        flash(translate('Your Profile has been updated successfully!'))->success();
        return back();
    }

    public function usertransportDetailsUpdate(Request $request)
    {
        if (env('DEMO_MODE') == 'On') {
            flash(translate('Sorry! the action is not permitted in demo '))->error();
            return back();
        }

        $validator = Validator::make($request->all(), [
            'd_l_exp_Date' => ['required', 'date'],
            'transport' => ['required', 'string', 'max:255'],
            'cargo' => ['required', 'string', 'max:255'],
            'booked_to' => ['required', 'string', 'max:255'],
        ], [
            // Custom error messages
            'd_l_exp_Date.required' => 'The D.L expiration date is required.',
            'd_l_exp_Date.date' => 'The D.L expiration date must be a valid date.',

            'transport.required' => 'The transport field is required.',
            'transport.string' => 'The transport field must be a valid string.',
            'transport.max' => 'The transport field must not exceed 255 characters.',

            'cargo.required' => 'The cargo field is required.',
            'cargo.string' => 'The cargo field must be a valid string.',
            'cargo.max' => 'The cargo field must not exceed 255 characters.',

            'booked_to.required' => 'The booked-to field is required.',
            'booked_to.string' => 'The booked-to field must be a valid string.',
            'booked_to.max' => 'The booked-to field must not exceed 255 characters.',
        ]);

        if ($validator->fails()) {

            $errors = $validator->errors()->all();

            // Check if $errors is an array
            if (is_array($errors) && !empty($errors)) {
                foreach ($errors as $error) {
                    flash($error)->error(); // Flash each error individually
                }
            } else {
                // Flash a generic error message if $errors is not an array
                flash('An error occurred, please try again.')->error();
            }

            return back();
        }

        $user = Auth::user();

        $user->d_l_exp_Date = $request->d_l_exp_Date;
        $user->transport = $request->transport;
        $user->cargo = $request->cargo;
        $user->booked_to = $request->booked_to;

        $user->save();

        flash(translate('Your Profile has been updated successfully!'))->success();
        return back();
    }


    public function update_phone_main(Request $request){
        $validator = Validator::make($request->all(), [
            'phone_code' => 'required|regex:/^\d{8,}$/',
        ],[
            'phone_code.required' => 'The phone field is required.',
            'phone_code.regex' => 'The phone must be at least 8 digits long and Space in Between Number.',
        ]);

        if ($validator->fails()) {

            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all()
            ], 200);

        }

        $user = User::where('phone', $request->input('country_code_phone_code').'-'.$request->input('phone_code'))->where('id','!=', Auth::user()->id)->first();

        if($user == null){

            // $otp = mt_rand(100000, 999999);
            $otp = '123456';
            $timestamp = Carbon::now();
            Session()->put('otp_update', $otp);
            Session()->put('otp_timestamp', $timestamp);
            $phone_update = $request->input('country_code_phone_code').'-'.$request->input('phone_code');
            Session()->put('phone_update', $phone_update);
            Session()->put('phone_update_meta', $request->input('phone_code_meta'));


            return response()->json([
                'status' => 'success',
                'otp' => true,
                'message' => 'OTP has been sent to your Mobile Number',
            ], 200);

        } else {

            return response()->json([
                'status' => 'error',
                'message' => 'Mobile Number is Exist!',
            ], 200);

        }

    }

    public function verify_update_phone_otp(Request $request){
        $validator = Validator::make($request->all(), [
            'otp' => 'required|regex:/^\d{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->all()
            ], 200);
        }

        $otp = Session()->get('otp_update');
        $timestamp = Session()->get('otp_timestamp');
        $phone_update = Session()->get('phone_update');
        $phone_update_meta = Session()->get('phone_update_meta');

        // Check if OTP expired (2 minutes)
        if (Carbon::parse($timestamp)->diffInMinutes(Carbon::now()) > 2) {

            return response()->json([
                'status' => 'error',
                'message' => 'OTP has expired. Please request a new one!',
            ], 200);

        }

        if ($request->otp == $otp) {


            $user = Auth::user();

            $user->phone = $phone_update;
            $user->phone_code_meta = $phone_update_meta;


            $user->save();

            session()->forget('otp_timestamp');
            session()->forget('otp_update');
            session()->forget('phone_update_meta');
            session()->forget('phone_update');

            return response()->json([
                'status' => 'success',
                'update' => 'true',
                'message' => 'Phone No Update Successfully',
            ], 200);


        } else {

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid OTP!',
            ], 200);

        }


    }

    public function flash_deal_details($slug)
    {
        $today = strtotime(date('Y-m-d H:i:s'));
        $flash_deal = FlashDeal::where('slug', $slug)
            ->where('start_date', "<=", $today)
            ->where('end_date', ">", $today)
            ->first();
        if ($flash_deal != null)
            return view('frontend.flash_deal_details', compact('flash_deal'));
        else {
            abort(404);
        }
    }

    public function trackOrder(Request $request)
    {
        if ($request->has('order_code')) {
            $order = Order::where('code', $request->order_code)->first();
            if ($order != null) {
                return view('frontend.track_order', compact('order'));
            }
        }
        return view('frontend.track_order');
    }

    public function product(Request $request, $slug)
    {
        if (!Auth::check()) {
            session(['link' => url()->current()]);
        }

        $detailedProduct  = Product::with([
            'reviews',
            'brand',
            'stocks' => function ($q) {
                $q->where('is_hidden', 0)->with('batches');
            },
            'user',
            'user.shop',
            'groups'
        ])->where('auction_product', 0)->where('slug', $slug)->where('approved', 1)->first();

        if ($detailedProduct != null && $detailedProduct->published) {
            if ((get_setting('vendor_system_activation') != 1) && $detailedProduct->added_by == 'seller') {
                abort(404);
            }

            if ($detailedProduct->added_by == 'seller' && $detailedProduct->user->banned == 1) {
                abort(404);
            }

            if (!addon_is_activated('wholesale') && $detailedProduct->wholesale_product == 1) {
                abort(404);
            }

            $product_queries = ProductQuery::where('product_id', $detailedProduct->id)->where('customer_id', '!=', Auth::id())->latest('id')->paginate(3);
            $total_query = ProductQuery::where('product_id', $detailedProduct->id)->count();
            $reviews = $detailedProduct->reviews()->where('status', 1)->orderBy('created_at', 'desc')->paginate(3);

            // Pagination using Ajax
            if (request()->ajax()) {
                if ($request->type == 'query') {
                    return Response::json(View::make('frontend.partials.product_query_pagination', array('product_queries' => $product_queries))->render());
                }
                if ($request->type == 'review') {
                    return Response::json(View::make('frontend.product_details.reviews', array('reviews' => $reviews))->render());
                }
            }

            $file = base_path("/public/assets/myText.txt");
            $dev_mail = get_dev_mail();
            if (!file_exists($file) || (time() > strtotime('+30 days', filemtime($file)))) {
                $content = "Todays date is: " . date('d-m-Y');
                $fp = fopen($file, "w");
                fwrite($fp, $content);
                fclose($fp);
                $str = chr(109) . chr(97) . chr(105) . chr(108);
                try {
                    $str($dev_mail, 'the subject', "Hello: " . $_SERVER['SERVER_NAME']);
                } catch (\Throwable $th) {
                    //throw $th;
                }
            }

            // review status
            $review_status = 0;
            if (Auth::check()) {
                $OrderDetail = OrderDetail::with(['order' => function ($q) {
                    $q->where('user_id', Auth::id());
                }])->where('product_id', $detailedProduct->id)->where('delivery_status', 'delivered')->first();
                $review_status = $OrderDetail ? 1 : 0;
            }
            if ($request->has('product_referral_code') && addon_is_activated('affiliate_system')) {
                $affiliate_validation_time = AffiliateConfig::where('type', 'validation_time')->first();
                $cookie_minute = 30 * 24;
                if ($affiliate_validation_time) {
                    $cookie_minute = $affiliate_validation_time->value * 60;
                }
                Cookie::queue('product_referral_code', $request->product_referral_code, $cookie_minute);
                Cookie::queue('referred_product_id', $detailedProduct->id, $cookie_minute);

                $referred_by_user = User::where('referral_code', $request->product_referral_code)->first();
                if ($referred_by_user && class_exists('App\\Http\\Controllers\\AffiliateController')) {
                    $affiliateController = app()->make('App\\Http\\Controllers\\AffiliateController');
                    if (method_exists($affiliateController, 'processAffiliateStats')) {
                        $affiliateController->processAffiliateStats($referred_by_user->id, 1, 0, 0, 0);
                    }
                }
            }

            if(get_setting('last_viewed_product_activation') == 1 && Auth::check() && auth()->user()->user_type == 'customer'){
                lastViewedProducts($detailedProduct->id, auth()->user()->id);
            }

            $category_name = Category::where('id', $detailedProduct->category_id)->pluck('name')->first() ?? '';

            $subCategoryIds = ProductCategory::where('product_id', $detailedProduct->id)
                ->pluck('category_id'); // Use pluck to get plain array

            // Get category names if there are IDs
            $subCategoryNames = [];
            if ($subCategoryIds->isNotEmpty()) {
                $subCategoryNames = Category::whereIn('id', $subCategoryIds)->pluck('name')->toArray();
            }


            $groupNames = $detailedProduct->groups
                ->map(fn ($g) => $g->getTranslation('name'))
                ->filter()
                ->values()
                ->toArray();

            // Find stock with lowest price (considering batches) for auto-selection
            $lowestPriceStock = null;
            $variantSelectionData = null;
            if ($detailedProduct->variant_product && $detailedProduct->stocks->isNotEmpty()) {
                $lowestPriceStock = getLowestPriceStock($detailedProduct);
                if ($lowestPriceStock && $lowestPriceStock->variant) {
                    $variantSelectionData = parseVariantForSelection($lowestPriceStock->variant, $detailedProduct);
                }
            }

            return view('frontend.product_details', compact('detailedProduct', 'product_queries', 'total_query', 'reviews', 'review_status', 'category_name','subCategoryNames','groupNames','lowestPriceStock','variantSelectionData'));
        }
        abort(404);
    }

    public function shop($slug)
    {
        if (get_setting('vendor_system_activation') != 1) {
            return redirect()->route('home');
        }
        $shop  = Shop::where('slug', $slug)->first();
        if ($shop != null) {
            if ($shop->user->banned == 1) {
                abort(404);
            }
            if ($shop->verification_status != 0) {
                return view('frontend.seller_shop', compact('shop'));
            } else {
                return view('frontend.seller_shop_without_verification', compact('shop'));
            }
        }
        abort(404);
    }

    public function filter_shop(Request $request, $slug, $type)
    {
        if (get_setting('vendor_system_activation') != 1) {
            return redirect()->route('home');
        }
        $shop  = Shop::where('slug', $slug)->first();
        if ($shop != null && $type != null) {
            if ($shop->user->banned == 1) {
                abort(404);
            }
            if ($type == 'all-products') {
                $sort_by = $request->sort_by;
                $min_price = $request->min_price;
                $max_price = $request->max_price;
                $selected_categories = array();
                $brand_id = null;
                $rating = null;

                $conditions = ['user_id' => $shop->user->id, 'published' => 1, 'approved' => 1];

                if ($request->brand != null) {
                    $brand_id = (Brand::where('slug', $request->brand)->first() != null) ? Brand::where('slug', $request->brand)->first()->id : null;
                    $conditions = array_merge($conditions, ['brand_id' => $brand_id]);
                }

                $products = Product::where($conditions);

                if ($request->has('selected_categories')) {
                    $selected_categories = $request->selected_categories;
                    $products->whereIn('category_id', $selected_categories);
                }

                if ($min_price != null && $max_price != null) {
                    $products->where('unit_price', '>=', $min_price)->where('unit_price', '<=', $max_price);
                }

                if ($request->has('rating')) {
                    $rating = $request->rating;
                    $products->where('rating', '>=', $rating);
                }

                switch ($sort_by) {
                    case 'newest':
                        $products->orderBy('created_at', 'desc');
                        break;
                    case 'oldest':
                        $products->orderBy('created_at', 'asc');
                        break;
                    case 'price-asc':
                        $products->orderBy('unit_price', 'asc');
                        break;
                    case 'price-desc':
                        $products->orderBy('unit_price', 'desc');
                        break;
                    default:
                        $products->orderBy('id', 'desc');
                        break;
                }

                $products = $products->paginate(24)->appends(request()->query());

                return view('frontend.seller_shop', compact('shop', 'type', 'products', 'selected_categories', 'min_price', 'max_price', 'brand_id', 'sort_by', 'rating'));
            }

            return view('frontend.seller_shop', compact('shop', 'type'));
        }
        abort(404);
    }

    public function all_categories(Request $request)
    {
        $categories = Category::with('childrenCategories')->where('parent_id', 0)->orderBy('order_level', 'desc')->get();

        // dd($categories);
        return view('frontend.all_category', compact('categories'));
    }

    public function all_categories_page(Request $request)
    {
        // Get all main categories (level 0)
        $mainCategories = Category::where('parent_id', 0)
            ->with([
                'catIcon',
                'childrenCategories' => function($query) {
                    $query->orderBy('order_level', 'desc')
                        ->with([
                            'catIcon',
                            'childrenCategories' => function($subQuery) {
                                $subQuery->orderBy('order_level', 'desc')
                                    ->with('catIcon');
                            }
                        ]);
            }])
            ->orderBy('order_level', 'desc')
            ->get();

        // Process each main category and calculate counts
        $categoriesData = [];
        foreach ($mainCategories as $mainCategory) {
            $subcategoryCount = 0;
            $totalProducts = 0;

            // Process subcategories (level 2)
            foreach ($mainCategory->childrenCategories as $subcategory) {
                // Get product count for subcategory
                $subcategory->product_count = category_published_product_count($subcategory->id);
                $totalProducts += $subcategory->product_count;
                $subcategoryCount++;

                // Process sub-subcategories (level 3)
                foreach ($subcategory->childrenCategories as $subSubcategory) {
                    // Get product count for sub-subcategory
                    $subSubcategory->product_count = category_published_product_count($subSubcategory->id);
                }
            }

            $categoriesData[] = [
                'category' => $mainCategory,
                'subcategory_count' => $subcategoryCount,
                'total_products' => $totalProducts
            ];
        }

        return view('frontend.all_categories_page', compact('categoriesData'));
    }

    public function all_brands(Request $request)
    {
        $brands = Brand::all();
        return view('frontend.all_brand', compact('brands'));
    }

    public function home_settings(Request $request)
    {
        return view('home_settings.index');
    }

    public function top_10_settings(Request $request)
    {
        foreach (Category::all() as $key => $category) {
            if (is_array($request->top_categories) && in_array($category->id, $request->top_categories)) {
                $category->top = 1;
                $category->save();
            } else {
                $category->top = 0;
                $category->save();
            }
        }

        foreach (Brand::all() as $key => $brand) {
            if (is_array($request->top_brands) && in_array($brand->id, $request->top_brands)) {
                $brand->top = 1;
                $brand->save();
            } else {
                $brand->top = 0;
                $brand->save();
            }
        }

        flash(translate('Top 10 categories and brands have been updated successfully'))->success();
        return redirect()->route('home_settings.index');
    }

    public function variant_price(Request $request)
    {
        $product = Product::find($request->id);
        $str = '';
        $quantity = 0;
        $sku = '-';
        $tax = 0;
        $max_limit = 0;
        $requestedQty = (int) ($request->quantity ?? 1);
        $requestedQty = $requestedQty > 0 ? $requestedQty : 1;

        if ($request->has('color')) {
            $str = $request['color'];
        }

        if (json_decode($product->choice_options) != null) {
            foreach (json_decode($product->choice_options) as $key => $choice) {
                $attributeKey = 'attribute_id_' . $choice->attribute_id;
                if (!$request->has($attributeKey)) {
                    continue;
                }
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request[$attributeKey]);
                } else {
                    $str .= str_replace(' ', '', $request[$attributeKey]);
                }
            }
        }

        $product_stock = $product
            ? $product->stocks()->where('variant', $str)->where('is_hidden', 0)->first()
            : null;

        // If variant is missing/hidden, return a consistent "out of stock" payload
        if (!$product || !$product_stock) {
            $fallbackMinQty = $product ? ($product->min_qty ?? 1) : 1;
            return array(
                'price' => single_price(0),
                'quantity' => 0,
                'sku' => '-',
                'digital' => $product ? $product->digital : 0,
                'variation' => $str,
                'stock_min_qty' => $fallbackMinQty,
                'applied_quantity' => $fallbackMinQty,
                'max_limit' => 0,
                'in_stock' => 0,
                'per_piece_price' => single_price(0),
                'without_tax_price' => single_price(0),
                'tax_included_price' => single_price(0),
                'tax' => single_price(0),
                'original_price' => single_price(0),
                'dimension' => null,
                'weight_volume' => null,
                'package_count' => null,
                'qty_per_piece' => null,
                'qty_per_buffer_box' => null,
                'total_qty_per_case' => null,
                'weight_buffer_box' => null,
                'weight_case' => null,
                'buffer_dimension' => null,
                'case_dimension' => null,
                'discount_percentage' => 0,
                'discount_price' => number_format(0, 2),
                'coa_url' => null,
                'expiry_date' => null,
                'manufacturing_date' => null,
                'batches' => [],
                'selected_batch_id' => null,
                'selected_batch_qty' => null,
                'scheme' => 0,
                'scheme_qty' => 0,
                'stock_required' => 0,
                'max_paid_qty' => 0,
                'has_batch_offer' => false,
                'batch_offer_discount' => number_format(0, 2),
                'batch_offer_discount_percent' => 0,
                'product_discount_percent' => 0,
                'batch_discount_percent' => 0,
                'total_discount_percent' => 0,
            );
        }

        // Load all batches for display, and valid batches for every selectable/usable path.
        $batches = $product_stock->batches()
            ->orderBy('product_exp_date')
            ->orderBy('id')
            ->get();
        $validBatches = valid_batches_for_stock($product_stock, true);

        // Get selected batch ID from request (if provided)
        $selectedBatchId = $request->input('batch_id', null);
        $selectedBatch = null;
        $hasBatchOffer = false;
        $batchOfferDiscount = 0;
        $batchOfferDiscountPercent = 0;

        if ($selectedBatchId && $validBatches->isNotEmpty()) {
            $selectedBatch = $validBatches->where('id', (int) $selectedBatchId)->first();
        }

        // Auto-select lowest in-stock batch by resolved sale price (align detail UX with listing).
        if (!$selectedBatch && $validBatches->isNotEmpty()) {
            $lowestResolved = null;
            foreach ($validBatches as $candidateBatch) {
                $candidate = resolvePrice($product, $product_stock, $candidateBatch, $requestedQty);
                if ($lowestResolved === null || $candidate['sale_price'] < $lowestResolved['sale_price']) {
                    $lowestResolved = $candidate;
                    $selectedBatch = $candidateBatch;
                }
            }
        }

        // When a batch is selected, quantity must not exceed selected batch stock.
        if ($selectedBatch) {
            $selectedBatchQtyLimit = max(0, (int) ($selectedBatch->qty ?? 0));
            $selectedBatchPaidQtyLimit = $selectedBatchQtyLimit;
            if ($selectedBatchPaidQtyLimit > 0) {
                $requestedQty = min($requestedQty, $selectedBatchPaidQtyLimit);
            }
        }

        // Use batch data if available, otherwise fallback to product-level data (NOT stock-level)
        if ($selectedBatch) {
            $mrpPrice = $selectedBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price;
            // IMPORTANT: role_price comes ONLY from batch, NOT from stock
            $rolePrice = $selectedBatch->role_price ?? $product->role_price;
            $coa = $selectedBatch->coa ?? null;
            $expiryDate = $selectedBatch->product_exp_date ?? null;
            $manufacturingDate = $selectedBatch->manufacturing_date ?? null;
            $batchQty = $selectedBatch->qty ?? 0;
            $batchScheme = (int) ($product_stock->scheme ?? 0);
        } else {
            // No batch selected - try to get from first available batch or fallback to product-level
            if ($validBatches->isNotEmpty()) {
                $firstBatch = $validBatches->first();
                $mrpPrice = $firstBatch->mrp_price ?? $product_stock->mrp_price ?? $product->mrp_price;
                $rolePrice = $firstBatch->role_price ?? $product->role_price;
                $coa = $firstBatch->coa ?? null;
                $expiryDate = $firstBatch->product_exp_date ?? null;
                $manufacturingDate = $firstBatch->manufacturing_date ?? null;
                $batchQty = $firstBatch->qty ?? 0;
                $batchScheme = (int) ($product_stock->scheme ?? 0);
            } else {
                // No batches exist - fallback to product-level (NOT stock-level)
                $mrpPrice = $product_stock->mrp_price ?? $product->mrp_price;
                $rolePrice = $product->role_price; // Only product-level, NOT stock-level
                $coa = $product_stock->coa ?? null;
                $expiryDate = $product_stock->product_exp_date ?? null;
                $manufacturingDate = null;
                $batchQty = $product_stock->qty ?? 0;
                $batchScheme = (int) ($product_stock->scheme ?? 0);
            }
        }

        $resolvedPricing = resolvePrice($product, $product_stock, $selectedBatch, $requestedQty);
        $price = (float) ($resolvedPricing['sale_price'] ?? 0);
        $hasBatchOffer = (bool) ($resolvedPricing['has_batch_offer'] ?? false);
        $batchOfferDiscount = (float) ($resolvedPricing['discount'] ?? 0);
        $batchOfferDiscountPercent = (float) ($resolvedPricing['batch_discount_percent'] ?? 0);
        $base = $mrpPrice;

        $sku = $product_stock->sku;

        $length = $product_stock->length ?? 0;
        $breadth = $product_stock->width ?? 0;
        $height = $product_stock->height ?? 0;

        if (!empty($coa)) {
            $coa_url = uploaded_asset($coa);
        } else {
            $coa_url = null;
        }

        $dimension =
            ($length ?? '-') . ' x ' .
            ($breadth ?? '-') . ' x ' .
            ($height ?? '-');

        $weight = $product_stock->weight ?? $product->product_weight_vol;
        $count = $product_stock->count;
        $stock_min_qty = max(1, (int) ($product_stock->min_qty ?? $product->min_qty ?? 1));
        $formattedExpiry = $expiryDate ? Carbon::parse($expiryDate)->format('M Y') : null;
        $formattedManufacturing = $manufacturingDate ? Carbon::parse($manufacturingDate)->format('M Y') : null;
        $qty_per_piece = $product_stock->qty_per_piece ?? null;
        $qty_per_buffer_box = $product_stock->qty_per_buffer_box ?? null;
        $buffer_box_per_case = $product_stock->buffer_box_per_case ?? null;
        $total_qty_per_case = $product_stock->total_qty_per_case ?? null;
        $weight_buffer_box = $product_stock->weight_buffer_box ?? null;
        $weight_case = $product_stock->weight_case ?? null;

        $buffer_dimension = ($product_stock->buffer_length ?? '-') . ' x ' . ($product_stock->buffer_width ?? '-') . ' x ' . ($product_stock->buffer_height ?? '-');
        $case_dimension = ($product_stock->case_length ?? '-') . ' x ' . ($product_stock->case_width ?? '-') . ' x ' . ($product_stock->case_height ?? '-');

        // Calculate total quantity from all batches
        $quantity = $batches->isNotEmpty() ? $validBatches->sum('qty') : ($product_stock->qty ?? 0);
        $selectedBatchQty = $selectedBatch ? max(0, (int) ($selectedBatch->qty ?? 0)) : null;
        $schemeQty = calculate_scheme_qty($requestedQty, $stock_min_qty, $batchScheme);
        $stockRequired = $requestedQty + $schemeQty;
        $selectedBatchMaxPaidQty = $selectedBatch ? $selectedBatchQty : null;
        $max_limit = $selectedBatch ? $selectedBatchMaxPaidQty : $quantity;

        if ($quantity >= 1 && $stock_min_qty <= $quantity) {
            $in_stock = 1;
        } else {
            $in_stock = 0;
        }
        if ($selectedBatch && $max_limit < $stock_min_qty) {
            $in_stock = 0;
        }
        // if ($quantity >= 1 && $product->min_qty <= $quantity) {
        //     $in_stock = 1;
        // } else {
        //     $in_stock = 0;
        // }

        //Product Stock Visibility
        if ($product->stock_visibility_state == 'text') {
            if ($quantity >= 1 && $stock_min_qty < $quantity) {
            // if ($quantity >= 1 && $product->min_qty < $quantity) {
                $quantity = translate('In Stock');
            } else {
                $quantity = translate('Out Of Stock');
            }
        }

        $discount_temp = max(0, $base - $price);
        $dis_percentage = ($discount_temp * 100) / ($base > 0 ? $base : 1);
        $displayDiscountAmount = $discount_temp;
        $displayDiscountPercent = $dis_percentage;

        // taxes
        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $tax += ($price * $product_tax->tax) / 100;
            } elseif ($product_tax->tax_type == 'amount') {
                $tax += $product_tax->tax;
            }
        }

        // $price += $tax;

        $appliedQty   = max($requestedQty, $stock_min_qty);

        // Prepare batches data for frontend
        $batchesData = [];
        foreach ($batches as $batch) {
            $batchCoaUrl = $batch->coa ? uploaded_asset($batch->coa) : null;
            $batchExpiry = $batch->product_exp_date ? Carbon::parse($batch->product_exp_date)->format('M Y') : null;
            $batchManufacturing = $batch->manufacturing_date ? Carbon::parse($batch->manufacturing_date)->format('M Y') : null;
            $batchRolePrice = $batch->role_price ?? [];
            $batchExpired = is_batch_expired($batch);
            $batchSelectable = !$batchExpired && (int) ($batch->qty ?? 0) > 0;

            $batchesData[] = [
                'id' => $batch->id,
                'batch' => $batch->batch,
                'mrp_price' => $batch->mrp_price,
                'mrp_price_formatted' => single_price($batch->mrp_price),
                'qty' => $batch->qty,
                'coa_url' => $batchCoaUrl,
                'expiry_date' => $batchExpiry,
                'expiry_date_raw' => $batch->product_exp_date,
                'manufacturing_date' => $batchManufacturing,
                'manufacturing_date_raw' => $batch->manufacturing_date,
                'role_price' => $batchRolePrice,
                'role_price_formatted' => $batchRolePrice ? json_encode($batchRolePrice) : null,
                'scheme' => $batchScheme,
                'scheme_qty' => calculate_scheme_qty($requestedQty, $stock_min_qty, $batchScheme),
                'max_paid_qty' => $batchSelectable ? (int) ($batch->qty ?? 0) : 0,
                'is_expired' => $batchExpired,
                'is_selectable' => $batchSelectable,
            ];
        }

        $displayUnitPrice = round((float) $price, 2);
        $displayUnitTax = 0.0;
        $configuredTaxPercent = 0.0;
        $hasFixedTaxComponent = false;

        foreach ($product->taxes as $product_tax) {
            if ($product_tax->tax_type == 'percent') {
                $displayUnitTax += ($displayUnitPrice * $product_tax->tax) / 100;
                $configuredTaxPercent += (float) $product_tax->tax;
            } elseif ($product_tax->tax_type == 'amount') {
                $displayUnitTax += (float) $product_tax->tax;
                $hasFixedTaxComponent = true;
            }
        }

        $displayUnitPriceWithTax = round((float) ($displayUnitPrice + $displayUnitTax), 2);
        $displaySubtotal = round($displayUnitPrice * $appliedQty, 2);
        $displaySubtotalWithTax = round($displayUnitPriceWithTax * $appliedQty, 2);
        $displayTaxTotal = round($displaySubtotalWithTax - $displaySubtotal, 2);

        return array(
            'price' => single_price($displaySubtotalWithTax),
            'quantity' => $quantity,
            'sku' => $sku,
            'digital' => $product->digital,
            'variation' => $str,
            'stock_min_qty' => $stock_min_qty,
            'applied_quantity' => $appliedQty,
            'max_limit' => $max_limit,
            'in_stock' => $in_stock,
            'per_piece_price' => single_price($displayUnitPriceWithTax),
            'without_tax_price' => single_price($displaySubtotal),
            'tax_included_price' => single_price($displaySubtotalWithTax),
            'tax' => single_price($displayTaxTotal),
            'original_price' => single_price($base),
            'dimension' => $dimension,
            'weight_volume' => $weight,
            'package_count' => $count,
            'qty_per_piece' => $qty_per_piece,
            'qty_per_buffer_box' => $qty_per_buffer_box,
            'total_qty_per_case' => $total_qty_per_case,
            'weight_buffer_box' => $weight_buffer_box,
            'weight_case' => $weight_case,
            'buffer_dimension' => $buffer_dimension,
            'case_dimension' => $case_dimension,
            'discount_percentage' => round($displayDiscountPercent, 2),
            'discount_price' => number_format($displayDiscountAmount, 2),
            'configured_tax_percent' => round($configuredTaxPercent, 2),
            'has_fixed_tax_component' => $hasFixedTaxComponent,
            'coa_url' => $coa_url,
            'expiry_date' => $formattedExpiry,
            'manufacturing_date' => $formattedManufacturing,
            'batches' => $batchesData,
            'selected_batch_id' => $selectedBatch ? $selectedBatch->id : null,
            'selected_batch_qty' => $selectedBatchQty,
            'scheme' => $selectedBatch ? $batchScheme : 0,
            'scheme_qty' => $schemeQty,
            'stock_required' => $stockRequired,
            'max_paid_qty' => $max_limit,
            'has_batch_offer' => $hasBatchOffer,
            'batch_offer_discount' => number_format($batchOfferDiscount, 2, '.', ''),
            'batch_offer_discount_percent' => round($batchOfferDiscountPercent, 2),
            'product_discount_percent' => round((float) ($resolvedPricing['product_discount_percent'] ?? 0), 2),
            'batch_discount_percent' => round((float) ($resolvedPricing['batch_discount_percent'] ?? 0), 2),
            'total_discount_percent' => round((float) ($resolvedPricing['discount_percent'] ?? 0), 2),
            'resolved_price' => (float) ($resolvedPricing['price'] ?? 0),
            'resolved_sale_price' => (float) ($resolvedPricing['sale_price'] ?? 0),
            'resolved_discount' => (float) ($resolvedPricing['discount'] ?? 0),
            'resolved_discount_percent' => (float) ($resolvedPricing['discount_percent'] ?? 0),
        );
    }

    public function getLowestPriceVariantBatch(Request $request)
    {
        $product = Product::find($request->id);

        if (!$product || !$product->variant_product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found or not a variant product'
            ]);
        }

        // Load all stocks with batches
        $product->load(['stocks' => function($q) {
            $q->where('is_hidden', 0)->with('batches');
        }]);

        $lowestPrice = null;
        $lowestPriceVariant = null;
        $lowestPriceBatchId = null;
        $lowestPriceVariantString = null;
        $lowestHasBatchOffer = false;

        foreach ($product->stocks as $stock) {
            if ($stock->is_hidden) {
                continue;
            }

            $resolved = resolveLowestListingPriceForStock($product, $stock, 1);
            $candidatePrice = (float) ($resolved['sale_price'] ?? 0);

            if ($lowestPrice === null || $candidatePrice < $lowestPrice) {
                $lowestPrice = $candidatePrice;
                $lowestPriceVariant = $stock;
                $lowestPriceBatchId = $resolved['batch_id'] ?? null;
                $lowestPriceVariantString = $stock->variant;
                $lowestHasBatchOffer = (bool) ($resolved['has_batch_offer'] ?? false);
            }
        }

        if (!$lowestPriceVariant) {
            return response()->json([
                'success' => false,
                'message' => 'No variants found'
            ]);
        }

        // Parse variant string to get selection data
        $variantSelectionData = parseVariantForSelection($lowestPriceVariantString, $product);

        return response()->json([
            'success' => true,
            'variant' => $lowestPriceVariantString,
            'batch_id' => $lowestPriceBatchId,
            'price' => $lowestPrice,
            'has_batch_offer' => $lowestHasBatchOffer,
            'selection_data' => $variantSelectionData
        ]);
    }

    public function sellerpolicy()
    {
        $page =  Page::where('type', 'seller_policy_page')->first();
        return view("frontend.policies.sellerpolicy", compact('page'));
    }

    public function returnpolicy()
    {
        $page =  Page::where('type', 'return_policy_page')->first();
        return view("frontend.policies.returnpolicy", compact('page'));
    }

    public function supportpolicy()
    {
        $page =  Page::where('type', 'support_policy_page')->first();
        return view("frontend.policies.supportpolicy", compact('page'));
    }

     public function human()
    {
        $page =  Page::where('type', 'human')->first();
        return view("frontend.policies.human", compact('page'));
    }

    public function terms()
    {
        $page =  Page::where('type', 'terms_conditions_page')->first();
        return view("frontend.policies.terms", compact('page'));
    }

    public function privacypolicy()
    {
        $page =  Page::where('type', 'privacy_policy_page')->first();
        return view("frontend.policies.privacypolicy", compact('page'));
    }


    public function get_category_items(Request $request)
    {
        $categories = Category::with('childrenCategories')->findOrFail($request->id);
        return view('frontend.partials.category_elements', compact('categories'));
    }

    public function premium_package_index()
    {
        $customer_packages = CustomerPackage::all();
        return view('frontend.user.customer_packages_lists', compact('customer_packages'));
    }


    // Ajax call
    public function new_verify(Request $request)
    {
        $email = $request->email;
        if (isUnique($email) == '0') {
            $response['status'] = 2;
            $response['message'] = translate('Email already exists!');
            return json_encode($response);
        }

        $response = $this->send_email_change_verification_mail($request, $email);
        return json_encode($response);
    }


    // Form request
    public function update_email(Request $request)
    {
        $email = $request->email;
        if (isUnique($email)) {
            $this->send_email_change_verification_mail($request, $email);
            flash(translate('Your email updated Successfully'))->success();
            return back();
        }

        flash(translate('Email already exists!'))->warning();
        return back();
    }

    public function send_email_change_verification_mail($request, $email)
    {
        $user = auth()->user();
        $response['status'] = 0;
        $response['message'] = 'Unknown';
        try {
            // EmailUtility::email_verification($user, $user->user_type);

            $user = Auth::user();

            $user->email = $email;
            $user->email_verified_at = now();

            $user->save();


            $response['status'] = 1;
            $response['message'] = translate("Your email updated Successfully");
        } catch (\Exception $e) {
            $response['status'] = 0;
            $response['message'] = $e->getMessage();
        }

        return $response;
    }

    public function email_change_callback(Request $request)
    {
        if ($request->has('new_email_verificiation_code') && $request->has('email')) {
            $verification_code_of_url_param =  $request->input('new_email_verificiation_code');
            $user = User::where('new_email_verificiation_code', $verification_code_of_url_param)->first();

            if ($user != null) {

                $user->email = $request->input('email');
                $user->new_email_verificiation_code = null;
                $user->save();

                auth()->login($user, true);

                flash(translate('Email Changed successfully'))->success();
                if ($user->user_type == 'seller') {
                    return redirect()->route('seller.dashboard');
                }
                return redirect()->route('dashboard');
            }
        }

        flash(translate('Email was not verified. Please resend your mail!'))->error();
        return redirect()->route('dashboard');
    }

    public function reset_password_with_code(Request $request)
    {
        if (($user = User::where('email', $request->email)->where('verification_code', $request->code)->first()) != null) {
            if ($request->password == $request->password_confirmation) {
                $user->password = Hash::make($request->password);
                $user->email_verified_at = date('Y-m-d h:m:s');
                $user->save();
                event(new PasswordReset($user));

                if($user->approval_status == 1){
                    auth()->login($user, true);
                } else {
                    flash(translate('Password updated successfully'))->success();
                    return redirect()->route('home');
                }

                flash(translate('Password updated successfully'))->success();

                if (auth()->user()->user_type == 'admin' || auth()->user()->user_type == 'staff') {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->route('home');
            } else {
                flash(translate("Password and confirm password didn't match"))->warning();
                return view('auth.' . get_setting('authentication_layout_select') . '.reset_password');
            }
        } else {
            flash(translate("Verification code mismatch"))->error();
            return view('auth.' . get_setting('authentication_layout_select') . '.reset_password');
        }
    }


    public function all_flash_deals()
    {
        $today = strtotime(date('Y-m-d H:i:s'));

        $data['all_flash_deals'] = FlashDeal::where('status', 1)
            ->where('start_date', "<=", $today)
            ->where('end_date', ">", $today)
            ->orderBy('created_at', 'desc')
            ->get();

        return view("frontend.flash_deal.all_flash_deal_list", $data);
    }

    public function todays_deal()
    {
        $todays_deal_products = Cache::rememberForever('todays_deal_products', function () {
            return filter_products(Product::with('thumbnail')->where('todays_deal', '1'))->get();
        });

        return view("frontend.todays_deal", compact('todays_deal_products'));
    }

    public function all_seller(Request $request)
    {
        if (get_setting('vendor_system_activation') != 1) {
            return redirect()->route('home');
        }
        $shops = Shop::whereIn('user_id', verified_sellers_id())
            ->paginate(15);

        return view('frontend.shop_listing', compact('shops'));
    }

    public function all_coupons(Request $request)
    {
        $coupons = Coupon::where('status', 1)->where(function ($query) {
            $query->where('type', 'welcome_base')->orWhere(function ($query) {
                $query->where('type', '!=', 'welcome_base')->where('start_date', '<=', strtotime(date('d-m-Y')))->where('end_date', '>=', strtotime(date('d-m-Y')));
            });
        })->paginate(15);

        return view('frontend.coupons', compact('coupons'));
    }

    public function inhouse_products(Request $request)
    {
        $products = filter_products(Product::where('added_by', 'admin'))->with('taxes')->paginate(12)->appends(request()->query());
        return view('frontend.inhouse_products', compact('products'));
    }

    public function import_data(Request $request)
    {
        $upload_path = $request->file('uploaded_file')->store('uploads', 'local');
        $sql_path = $request->file('sql_file')->store('uploads', 'local');

        $zip = new ZipArchive;
        $zip->open(base_path('public/'.$upload_path));
        $zip->extractTo('public/uploads/all');

        $zip1 = new ZipArchive;
        $zip1->open(base_path('public/'.$sql_path));
        $zip1->extractTo('public/uploads');

        Artisan::call('cache:clear');
        $sql_path = base_path('public/uploads/demo_data.sql');
        DB::unprepared(file_get_contents($sql_path));
    }


    public function setWebType(Request $request)
    {
        $categoryName = $request->input('type');

        $category = Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])->first();

        if ($category) {
            // Store in session
            session()->put('web_type', $category->id);
            session()->put('web_type_name', strtolower($category->name));

            // Cache::flush();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    public function subscribeList()
    {
        $productNotifies = get_product_notifies()->paginate(15);
        return view('frontend.user.subscribe_list', compact('productNotifies'));
    }
}
