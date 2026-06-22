<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\Admin\Report\EarningReportController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AizUploadController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\BlogCategoryController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandBulkUploadController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\CarrierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\CustomAlertController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPackageController;
use App\Http\Controllers\CustomerProductController;
use App\Http\Controllers\DigitalProductController;
use App\Http\Controllers\DynamicPopupController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\FinancialArchiveController;
use App\Http\Controllers\FlashDealController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LeadActivitySubStatusController;
use App\Http\Controllers\LeadActivityTypeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadDepartmentController;
use App\Http\Controllers\FormEnquiryController;
use App\Http\Controllers\MarketingController;
use App\Http\Controllers\MeasurementPointsController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTypeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PickupPointController;
use App\Http\Controllers\ProductBulkUploadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductQueryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SellerWithdrawRequestController;
use App\Http\Controllers\SizeChartController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TransportController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\Admin\GiftController as AdminGiftController;
use App\Http\Controllers\Admin\GiftRequestController;
use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\RequestDocController;
use App\Http\Controllers\PurchaseHistoryReportController;
use App\Http\Controllers\BookedToController;
use App\Http\Controllers\LocalDeliveryPartnerController;
use Illuminate\Support\Facades\Storage;

/*
  |--------------------------------------------------------------------------
  | Admin Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register admin routes for your application. These
  | routes are loaded by the RouteServiceProvider within a group which
  | contains the "web" middleware group. Now create something great!
  |
 */
//Update Routes
Route::controller(UpdateController::class)->group(function () {
    Route::post('/update', 'step0')->name('update');
    Route::get('/update/step1', 'step1')->name('update.step1');
    Route::get('/update/step2', 'step2')->name('update.step2');
    Route::get('/update/step3', 'step3')->name('update.step3');
    Route::post('/purchase_code', 'purchase_code')->name('update.code');
});

Route::get('/admin', [AdminController::class, 'admin_dashboard'])->name('admin.dashboard')->middleware(['auth', 'admin', 'prevent-back-history']);
Route::group(['prefix' => 'admin', 'middleware' => ['auth', 'admin', 'prevent-back-history']], function () {

    // Purchase History Report (admin)
    Route::controller(PurchaseHistoryReportController::class)->group(function () {

        Route::get('/purchase-history-report/error-log', function () {
            $relative = 'purchase_history_import/error.txt';

            // Prefer the file created by the import in storage/app
            if (Storage::disk('local')->exists($relative)) {
                $path = Storage::disk('local')->path($relative);
            } else {
                // Fallback to public path if the file is present there
                $publicPath = public_path($relative);
                if (file_exists($publicPath)) {
                    $path = $publicPath;
                } else {
                    abort(404);
                }
            }

            return response()->file($path, ['Content-Type' => 'text/plain']);
        })->name('admin.purchase_history.error_log');

        Route::post('/purchase-history-report/import', 'import')->name('admin.purchase_history.import');
        Route::get('/purchase-history-report-export', 'export')->name('admin.purchase_history.export');


        Route::get('/purchase-history-report', 'index')->name('admin.purchase_history.index');
        Route::get('/purchase-history-report/{id}', 'show')->name('admin.purchase_history.show');
        Route::get('/purchase-history-report/{id}/edit', 'edit')->name('admin.purchase_history.edit');
        Route::post('/purchase-history-report/{id}', 'update')->name('admin.purchase_history.update');
        Route::delete('/purchase-history-report/{id}', 'destroy')->name('admin.purchase_history.destroy');
    });

    // category
    Route::resource('categories', CategoryController::class);
    Route::controller(CategoryController::class)->group(function () {
        Route::get('/categories/edit/{id}', 'edit')->name('categories.edit');
        Route::get('/categories/destroy/{id}', 'destroy')->name('categories.destroy');
        Route::post('/categories/featured', 'updateFeatured')->name('categories.featured');
        Route::post('/categories/categoriesByType', 'categoriesByType')->name('categories.categories-by-type');

        // category-wise discount set
        Route::get('/categories-wise-product-discount', 'categoriesWiseProductDiscount')->name('categories_wise_product_discount');
    });

    // group
    Route::resource('groups', GroupController::class);
    Route::controller(GroupController::class)->group(function () {
        Route::get('/groups/edit/{id}', 'edit')->name('groups.edit');
        Route::get('/groups/destroy/{id}', 'destroy')->name('groups.destroy');
        Route::post('/groups/featured', 'updateFeatured')->name('groups.featured');
        Route::post('/groups/groupsByType', 'groupsByType')->name('groups.groups-by-type');
    });

    // Brand
    Route::resource('brands', BrandController::class);
    Route::controller(BrandController::class)->group(function () {
        Route::get('/brands/edit/{id}', 'edit')->name('brands.edit');
        Route::get('/brands/destroy/{id}', 'destroy')->name('brands.destroy');
    });

    // Warranty
    Route::resource('warranties', WarrantyController::class);
    Route::controller(WarrantyController::class)->group(function () {
        Route::get('/warranties/edit/{id}', 'edit')->name('warranties.edit');
        Route::get('/warranties/destroy/{id}', 'destroy')->name('warranties.destroy');
    });

    Route::controller(BrandBulkUploadController::class)->group(function () {
        Route::get('/brand-bulk-upload', 'index')->name('brand_bulk_upload.index');
        Route::post('/brand-bulk-upload/store', 'bulk_upload')->name('brand_bulk_upload');
    });

    Route::controller(AdminController::class)->group(function () {
        Route::post('/dashboard/top-category-products-section', 'top_category_products_section')->name('dashboard.top_category_products_section');
        Route::post('/dashboard/inhouse-top-brands', 'inhouse_top_brands')->name('dashboard.inhouse_top_brands');
        Route::post('/dashboard/inhouse-top-categories', 'inhouse_top_categories')->name('dashboard.inhouse_top_categories');
        Route::post('/dashboard/top-sellers-products-section', 'top_sellers_products_section')->name('dashboard.top_sellers_products_section');
        Route::post('/dashboard/top-brands-products-section', 'top_brands_products_section')->name('dashboard.top_brands_products_section');
    });

    // Products
    Route::controller(ProductController::class)->group(function () {
        Route::get('/products/admin', 'admin_products')->name('products.admin');
        Route::get('/products/seller/{product_type}', 'seller_products')->name('products.seller');
        Route::get('/products/all', 'all_products')->name('products.all');
        Route::get('/products/create', 'create')->name('products.create');
        Route::post('/products/store/', 'store')->name('products.store');
        Route::get('/products/admin/{id}/edit', 'admin_product_edit')->name('products.admin.edit');
        Route::get('/products/seller/{id}/edit', 'seller_product_edit')->name('products.seller.edit');
        Route::post('/products/update/{product}', 'update')->name('products.update');
        Route::post('/products/todays_deal', 'updateTodaysDeal')->name('products.todays_deal');
        Route::post('/products/featured', 'updateFeatured')->name('products.featured');
        Route::post('/products/published', 'updatePublished')->name('products.published');
        Route::post('/products/approved', 'updateProductApproval')->name('products.approved');
        Route::post('/products/get_products_by_subcategory', 'get_products_by_subcategory')->name('products.get_products_by_subcategory');
        Route::get('/products/duplicate/{id}', 'duplicate')->name('products.duplicate');
        Route::get('/products/destroy/{id}', 'destroy')->name('products.destroy');
        Route::post('/bulk-product-delete', 'bulk_product_delete')->name('bulk-product-delete');

        Route::post('/products/sku_combination', 'sku_combination')->name('products.sku_combination');
        Route::post('/products/sku_combination_edit', 'sku_combination_edit')->name('products.sku_combination_edit');
        Route::post('/products/add-more-choice-option', 'add_more_choice_option')->name('products.add-more-choice-option');
        Route::post('/product-search', 'product_search')->name('product.search');
        Route::post('/get-selected-products', 'get_selected_products')->name('get-selected-products');
        Route::post('/set-product-discount', 'setProductDiscount')->name('set_product_discount');
        Route::get('/products/role-prices', 'role_prices')->name('products.role_prices'); //price by role

        Route::get('/products/download-product-stock-excel', 'download_stock_Excel')->name('download-product-stock-excel');

        Route::post('/products/price-update/upload', 'upload_excel_update_stock')->name('price-update.upload');
    });

    // Digital Product
    Route::resource('digitalproducts', DigitalProductController::class);
    Route::controller(DigitalProductController::class)->group(function () {
        Route::get('/digitalproducts/edit/{id}', 'edit')->name('digitalproducts.edit');
        Route::get('/digitalproducts/destroy/{id}', 'destroy')->name('digitalproducts.destroy');
        Route::get('/digitalproducts/download/{id}', 'download')->name('digitalproducts.download');
    });

    Route::controller(ProductBulkUploadController::class)->group(function () {
        //Product Export
        Route::get('/product-bulk-export', 'export')->name('product_bulk_export.index');

        //Product Bulk Upload
        Route::get('/product-bulk-upload/index', 'index')->name('product_bulk_upload.index');
        Route::post('/bulk-product-upload', 'bulk_upload')->name('bulk_product_upload');
        Route::get('/product-csv-download/{type}', 'import_product')->name('product_csv.download');
        Route::get('/vendor-product-csv-download/{id}', 'import_vendor_product')->name('import_vendor_product.download');
        Route::group(['prefix' => 'bulk-upload/download'], function () {
            Route::get('/category', 'pdf_download_category')->name('pdf.download_category');
            Route::get('/brand', 'pdf_download_brand')->name('pdf.download_brand');
            Route::get('/seller', 'pdf_download_seller')->name('pdf.download_seller');
        });
    });

    // Note
    Route::resource('note', NoteController::class);
    Route::controller(NoteController::class)->group(function () {
        Route::get('/note/edit/{id}', 'edit')->name('note.edit');
        Route::get('note/delete/{note}', 'destroy')->name('note.delete');
        // Route::post('/get-notes', 'getNotes')->name('get_notes');
    });

    // Financial Archive
    Route::resource('financial-archives', FinancialArchiveController::class);
    Route::controller(FinancialArchiveController::class)->group(function () {
        // Route::get('/financial-archives/edit/{id}', 'edit')->name('financial-archives.edit');
        Route::get('/financial-archives/destroy/{id}', 'destroy')->name('financial-archives.destroy');
        Route::get('/financial-archives/customer/{user}', 'customerArchives')->name('financial-archives.customer');
        Route::post('/financial-archives/customer/store/{user}', 'storeForUser')->name('financial-archives.customer.store');
    });

    // Seller
    Route::resource('sellers', SellerController::class);
    Route::controller(SellerController::class)->group(function () {
        Route::get('/seller/rating-followers', 'index')->name('sellers.rating_followers');
        Route::get('sellers_ban/{id}', 'ban')->name('sellers.ban');
        Route::get('/sellers/destroy/{id}', 'destroy')->name('sellers.destroy');
        Route::post('/bulk-seller-delete', 'bulk_seller_delete')->name('bulk-seller-delete');
        Route::get('/sellers/view/{id}/verification', 'show_verification_request')->name('sellers.show_verification_request');
        Route::get('/sellers/approve/{id}', 'approve_seller')->name('sellers.approve');
        Route::get('/sellers/reject/{id}', 'reject_seller')->name('sellers.reject');
        Route::get('/sellers/login/{id}', 'login')->name('sellers.login');
        Route::post('/sellers/payment_modal', 'payment_modal')->name('sellers.payment_modal');
        Route::post('/sellers/profile_modal', 'profile_modal')->name('sellers.profile_modal');
        Route::post('/sellers/approved', 'updateApproved')->name('sellers.approved');
        Route::post('/sellers/set-commission', 'setSellerBasedCommission')->name('set_seller_based_commission');
        Route::post('/sellers/edit-custom-followers', 'editSellerCustomFollowers')->name('edit_Seller_custom_followers');
    });

    // Seller Payment
    Route::controller(PaymentController::class)->group(function () {
        Route::get('/seller/payments', 'payment_histories')->name('sellers.payment_histories');
        Route::get('/seller/payments/show/{id}', 'show')->name('sellers.payment_history');
    });

    // Seller Withdraw Request
    Route::resource('/withdraw_requests', SellerWithdrawRequestController::class);
    Route::controller(SellerWithdrawRequestController::class)->group(function () {
        Route::get('/withdraw_requests_all', 'index')->name('withdraw_requests_all');
        Route::post('/withdraw_request/payment_modal', 'payment_modal')->name('withdraw_request.payment_modal');
        Route::post('/withdraw_request/message_modal', 'message_modal')->name('withdraw_request.message_modal');
    });

    // Customer
    // Dedicated import route placed before resource to avoid conflicts
    Route::get('customers/import-transport', [CustomerController::class, 'importTransportFromExcel'])->name('customers.import.transport');

    Route::resource('customers', CustomerController::class)
        ->except(['update', 'destroy'])
        ->whereNumber('customer');

    Route::controller(CustomerController::class)->group(function () {
        Route::get('customers-business', 'business_index')->name('customers.business');
        Route::get('customers-business/create', 'createBusiness')->name('customers.business.create');
        Route::post('customers-business', 'storeBusiness')->name('customers.business.store');
        Route::post('/customers/gst-details', 'gstDetails')->name('customers.gst.details');
        Route::get('customers_ban/{customer}', 'ban')->name('customers.ban');
        Route::get('/customers/login/{id}', 'login')->name('customers.login');
        Route::get('/customers/destroy/{id}', 'destroy')->name('customers.destroy');
        Route::get('/customers-import-transport', 'importTransportFromExcel')->name('customers.import.transport.alt');
        Route::get('/customers/location-options', 'locationOptions')->name('customers.location.options');
        Route::post('/bulk-customer-delete', 'bulk_customer_delete')->name('bulk-customer-delete');
        Route::match(['post', 'put', 'patch'], 'customers/update/{id}', 'update')->name('customers.update');

        Route::get('/customers_view/{id}', 'view')->name('customers.view');
        Route::any('/customers_approval', 'approval')->name('customers.approval');

        Route::post('/customers/credit/update','update_credit')->name('customers.credits.update');
    });

    Route::middleware(['auth','can:admin'])->group(function () {
        Route::get('/request-docs', [RequestDocController::class, 'adminIndex'])->name('customers.request-doc.index');
        Route::post('/request-docs/{doc}/approve', [RequestDocController::class, 'approve'])->name('admin.request-doc.approve');
        Route::post('/request-docs/{doc}/disapprove', [RequestDocController::class, 'disapprove'])->name('admin.request-doc.disapprove');

        Route::post('/admin/request-docs/pdf-store', [RequestDocController::class, 'storeBusinessRequestPdfs'])
        ->name('admin.request-doc.pdf.store');

    });

    // Leads
    Route::controller(LeadDepartmentController::class)->group(function () {
        Route::get('/lead-departments', 'index')->name('lead-departments.index');
        Route::post('/lead-departments/categories', 'storeCategory')->name('lead-departments.categories.store');
        Route::get('/lead-departments/categories/{category}/edit', 'editCategory')->name('lead-departments.categories.edit');
        Route::post('/lead-departments/categories/{category}/update', 'updateCategory')->name('lead-departments.categories.update');
        Route::get('/lead-departments/categories/{category}/destroy', 'destroyCategory')->name('lead-departments.categories.destroy');
        Route::post('/lead-departments/categories/update-status', 'updateCategoryStatus')->name('lead-departments.categories.update_status');
        Route::post('/lead-departments/departments', 'storeDepartment')->name('lead-departments.departments.store');
        Route::get('/lead-departments/departments/{department}/edit', 'editDepartment')->name('lead-departments.departments.edit');
        Route::post('/lead-departments/departments/{department}/update', 'updateDepartment')->name('lead-departments.departments.update');
        Route::get('/lead-departments/departments/{department}/destroy', 'destroyDepartment')->name('lead-departments.departments.destroy');
        Route::post('/lead-departments/departments/update-status', 'updateDepartmentStatus')->name('lead-departments.departments.update_status');
    });

    Route::controller(LeadActivityTypeController::class)->group(function () {
        Route::get('/lead-activity-types', 'index')->name('lead-activity-types.index');
        Route::post('/lead-activity-types', 'store')->name('lead-activity-types.store');
        Route::get('/lead-activity-types/{activityType}/edit', 'edit')->name('lead-activity-types.edit');
        Route::post('/lead-activity-types/{activityType}/update', 'update')->name('lead-activity-types.update');
        Route::get('/lead-activity-types/{activityType}/destroy', 'destroy')->name('lead-activity-types.destroy');
        Route::post('/lead-activity-types/update-status', 'updateStatus')->name('lead-activity-types.update_status');
    });

    Route::controller(LeadActivitySubStatusController::class)->group(function () {
        Route::get('/lead-activity-sub-statuses', 'index')->name('lead-activity-sub-statuses.index');
        Route::post('/lead-activity-sub-statuses', 'store')->name('lead-activity-sub-statuses.store');
        Route::get('/lead-activity-sub-statuses/{subStatus}/edit', 'edit')->name('lead-activity-sub-statuses.edit');
        Route::post('/lead-activity-sub-statuses/{subStatus}/update', 'update')->name('lead-activity-sub-statuses.update');
        Route::get('/lead-activity-sub-statuses/{subStatus}/destroy', 'destroy')->name('lead-activity-sub-statuses.destroy');
        Route::post('/lead-activity-sub-statuses/update-status', 'updateStatus')->name('lead-activity-sub-statuses.update_status');
    });

    Route::controller(LeadController::class)->group(function () {
        Route::get('/leads', 'index')->name('leads.index');
        Route::get('/leads/create', 'create')->name('leads.create');
        Route::get('/leads/customer-by-phone', 'customerByPhone')->name('leads.customer_by_phone');
        Route::post('/leads', 'store')->name('leads.store');
        Route::get('/leads/destroy/{lead}', 'destroy')->name('leads.destroy');
        Route::get('/leads/{lead}', 'show')->name('leads.show');
        Route::get('/leads/{lead}/edit', 'edit')->name('leads.edit');
        Route::post('/leads/{lead}/update', 'update')->name('leads.update');
        Route::post('/leads/{lead}/activities', 'storeActivity')->name('leads.activities.store');
        Route::post('/leads/{lead}/activities/{activity}/update', 'updateActivity')->name('leads.activities.update');
        Route::get('/leads/{lead}/activities/{activity}/destroy', 'destroyActivity')->name('leads.activities.destroy');
    });

    // Newsletter
    Route::controller(NewsletterController::class)->group(function () {
        Route::get('/newsletter', 'index')->name('newsletters.index');
        Route::post('/newsletter/send', 'send')->name('newsletters.send');
        Route::post('/newsletter/test/smtp', 'testEmail')->name('test.smtp');
    });

    // Dynamic Popup
    Route::resource('dynamic-popups', DynamicPopupController::class);
    Route::controller(DynamicPopupController::class)->group(function () {
        Route::get('/dynamic-popups/destroy/{id}', 'destroy')->name('dynamic-popups.destroy');
        Route::post('/bulk-dynamic-popup-delete', 'bulk_dynamic_popup_delete')->name('bulk-dynamic-popup-delete');
        Route::post('/dynamic-popups-update-status', 'update_status')->name('dynamic-popups.update-status');
    });

    // Custom Alert
    Route::resource('custom-alerts', CustomAlertController::class);
    Route::controller(CustomAlertController::class)->group(function () {
        Route::get('/custom-alerts/destroy/{id}', 'destroy')->name('custom-alerts.destroy');
        Route::post('/bulk-custom-alerts-delete', 'bulk_custom_alerts_delete')->name('bulk-custom-alerts-delete');
        Route::post('/custom-alerts-update-status', 'update_status')->name('custom-alerts.update-status');
    });

    //Contacts
    Route::controller(ContactController::class)->group(function () {
        Route::get('/contacts', 'index')->name('contacts');
        Route::get('/product-enquiry', 'product_enquiry_index')->name('product_enquiry');
        Route::get('/prescription-enquiry', 'prescription_enquiry_index')->name('prescription_enquiry');
        Route::get('/support-enquiries', 'support_index')->name('support_enquiries');
        Route::post('/contact/query_modal', 'query_modal')->name('contact.query_modal');
        Route::post('/contact/reply_modal', 'reply_modal')->name('contact.reply_modal');
        Route::post('/contact/reply', 'reply')->name('contact.reply');
        Route::post('/support-enquiries/update-status', 'support_update_status')->name('contact.support_update_status');
    });

    // Form Enquiry / Suggestion (frontend page submissions)
    Route::controller(FormEnquiryController::class)->group(function () {
        Route::get('/form-enquiries', 'adminIndex')->name('form_enquiries.index');
        Route::get('/form-enquiries/{formEnquiry}', 'adminShow')->name('form_enquiries.show');
    });

    Route::resource('profile', ProfileController::class);

    // Business Settings
    Route::controller(BusinessSettingsController::class)->group(function () {
        Route::post('/business-settings/update', 'update')->name('business_settings.update');
        Route::post('/business-settings/update/activation', 'updateActivationSettings')->name('business_settings.update.activation');
        Route::post('/payment-activation', 'updatePaymentActivationSettings')->name('payment.activation');
        Route::get('/general-setting', 'general_setting')->name('general_setting.index');
        Route::get('/activation', 'activation')->name('activation.index');
        Route::get('/payment-method', 'payment_method')->name('payment_method.index');
        Route::get('/file_system', 'file_system')->name('file_system.index');
        Route::get('/social-login', 'social_login')->name('social_login.index');
        Route::get('/smtp-settings', 'smtp_settings')->name('smtp_settings.index');
        Route::get('/google-analytics', 'google_analytics')->name('google_analytics.index');
        Route::get('/google-recaptcha', 'google_recaptcha')->name('google_recaptcha.index');
        Route::get('/google-map', 'google_map')->name('google-map.index');
        Route::get('/google-firebase', 'google_firebase')->name('google-firebase.index');

        //Facebook Settings
        Route::get('/facebook-chat', 'facebook_chat')->name('facebook_chat.index');
        Route::post('/facebook_chat', 'facebook_chat_update')->name('facebook_chat.update');
        Route::get('/facebook-comment', 'facebook_comment')->name('facebook-comment');
        Route::post('/facebook-comment', 'facebook_comment_update')->name('facebook-comment.update');
        Route::post('/facebook_pixel', 'facebook_pixel_update')->name('facebook_pixel.update');

        Route::post('/env_key_update', 'env_key_update')->name('env_key_update.update');
        Route::post('/payment_method_update', 'payment_method_update')->name('payment_method.update');
        Route::post('/google_analytics', 'google_analytics_update')->name('google_analytics.update');
        Route::post('/google_recaptcha', 'google_recaptcha_update')->name('google_recaptcha.update');
        Route::post('/google-map', 'google_map_update')->name('google-map.update');
        Route::post('/google-firebase', 'google_firebase_update')->name('google-firebase.update');

        Route::get('/verification/form', 'seller_verification_form')->name('seller_verification_form.index');
        Route::post('/verification/form', 'seller_verification_form_update')->name('seller_verification_form.update');
        Route::get('/vendor_commission', 'vendor_commission')->name('business_settings.vendor_commission');

        //Shipping Configuration
        Route::get('/shipping_configuration', 'shipping_configuration')->name('shipping_configuration.index');
        Route::post('/shipping_configuration/update', 'shipping_configuration_update')->name('shipping_configuration.update');

        // Order Configuration
        Route::get('/order-configuration', 'order_configuration')->name('order_configuration.index');
    });


    //Currency
    Route::controller(CurrencyController::class)->group(function () {
        Route::get('/currency', 'currency')->name('currency.index');
        Route::post('/currency/update', 'updateCurrency')->name('currency.update');
        Route::post('/your-currency/update', 'updateYourCurrency')->name('your_currency.update');
        Route::get('/currency/create', 'create')->name('currency.create');
        Route::post('/currency/store', 'store')->name('currency.store');
        Route::post('/currency/currency_edit', 'edit')->name('currency.edit');
        Route::post('/currency/update_status', 'update_status')->name('currency.update_status');
    });

    //Tax
    Route::resource('tax', TaxController::class);
    Route::controller(TaxController::class)->group(function () {
        Route::get('/tax/edit/{id}', 'edit')->name('tax.edit');
        Route::get('/tax/destroy/{id}', 'destroy')->name('tax.destroy');
        Route::post('tax-status', 'change_tax_status')->name('taxes.tax-status');
    });

    // Language
    Route::resource('/languages', LanguageController::class);
    Route::controller(LanguageController::class)->group(function () {
        Route::post('/languages/{id}/update', 'update')->name('languages.update');
        Route::get('/languages/destroy/{id}', 'destroy')->name('languages.destroy');
        Route::post('/languages/update_rtl_status', 'update_rtl_status')->name('languages.update_rtl_status');
        Route::post('/languages/update-status', 'update_status')->name('languages.update-status');
        Route::post('/languages/key_value_store', 'key_value_store')->name('languages.key_value_store');

        //App Trasnlation
        Route::post('/languages/app-translations/import', 'importEnglishFile')->name('app-translations.import');
        Route::get('/languages/app-translations/show/{id}', 'showAppTranlsationView')->name('app-translations.show');
        Route::post('/languages/app-translations/key_value_store', 'storeAppTranlsation')->name('app-translations.store');
        Route::get('/languages/app-translations/export/{id}', 'exportARBFile')->name('app-translations.export');
    });


    // website setting
    Route::group(['prefix' => 'website'], function () {
        Route::controller(WebsiteController::class)->group(function () {
            Route::get('/footer', 'footer')->name('website.footer');
            Route::get('/header', 'header')->name('website.header');
            Route::get('/appearance', 'appearance')->name('website.appearance');
            Route::get('/select-homepage', 'select_homepage')->name('website.select-homepage');
            Route::get('/authentication-layout-settings', 'authentication_layout_settings')->name('website.authentication-layout-settings');
            Route::get('/pages', 'pages')->name('website.pages');
        });

        // Custom Page
        Route::resource('custom-pages', PageController::class);
        Route::controller(PageController::class)->group(function () {
            Route::get('/custom-pages/edit/{id}', 'edit')->name('custom-pages.edit');
            Route::get('/custom-pages/destroy/{id}', 'destroy')->name('custom-pages.destroy');
        });
    });

    // Staff Roles
    Route::resource('roles', RoleController::class);
    Route::controller(RoleController::class)->group(function () {
        Route::get('/roles/edit/{id}', 'edit')->name('roles.edit');
        Route::get('/roles/destroy/{id}', 'destroy')->name('roles.destroy');

        // Add Permissiom
        Route::post('/roles/add_permission', 'add_permission')->name('roles.permission');
    });

    // Staff
    Route::resource('staffs', StaffController::class);
    Route::get('/staffs/destroy/{id}', [StaffController::class, 'destroy'])->name('staffs.destroy');

    // Flash Deal
    Route::resource('flash_deals', FlashDealController::class);
    Route::controller(FlashDealController::class)->group(function () {
        Route::get('/flash_deals/edit/{id}', 'edit')->name('flash_deals.edit');
        Route::get('/flash_deals/destroy/{id}', 'destroy')->name('flash_deals.destroy');
        Route::post('/flash_deals/update_status', 'update_status')->name('flash_deals.update_status');
        Route::post('/flash_deals/update_featured', 'update_featured')->name('flash_deals.update_featured');
        Route::post('/flash_deals/product_discount', 'product_discount')->name('flash_deals.product_discount');
        Route::post('/flash_deals/product_discount_edit', 'product_discount_edit')->name('flash_deals.product_discount_edit');
    });

    //Subscribers
    Route::controller(SubscriberController::class)->group(function () {
        Route::get('/subscribers', 'index')->name('subscribers.index');
        Route::get('/subscribers/destroy/{id}', 'destroy')->name('subscriber.destroy');
    });

    // Order
    Route::resource('orders', OrderController::class);
    Route::controller(OrderController::class)->group(function () {
        // All Orders
        Route::get('/all_orders', 'all_orders')->name('all_orders.index');
        Route::get('/inhouse-orders', 'all_orders')->name('inhouse_orders.index');
        Route::get('/seller_orders', 'all_orders')->name('seller_orders.index');
        Route::get('/orders_by_pickup_point', 'all_orders')->name('pick_up_point.index');
        Route::get('/unpaid_orders', 'all_orders')->name('unpaid_orders.index');

        Route::get('/orders/{id}/show', 'show')->name('all_orders.show');
        Route::get('/inhouse-orders/{id}/show', 'show')->name('inhouse_orders.show');
        Route::get('/seller_orders/{id}/show', 'show')->name('seller_orders.show');
        Route::get('/orders_by_pickup_point/{id}/show', 'show')->name('pick_up_point.order_show');

        Route::post('/bulk-order-status', 'bulk_order_status')->name('bulk-order-status');

        Route::get('/orders/destroy/{id}', 'destroy')->name('orders.destroy');
        Route::post('/bulk-order-delete', 'bulk_order_delete')->name('bulk-order-delete');

        Route::get('/orders/destroy/{id}', 'destroy')->name('orders.destroy');
        Route::post('/orders/details', 'order_details')->name('orders.details');
        Route::post('/orders/update_delivery_status', 'update_delivery_status')->name('orders.update_delivery_status');
        Route::post('/orders/update_payment_status', 'update_payment_status')->name('orders.update_payment_status');
        Route::post('/orders/update_tracking_code', 'update_tracking_code')->name('orders.update_tracking_code');

        //Delivery Boy Assign
        Route::post('/orders/delivery-boy-assign', 'assign_delivery_boy')->name('orders.delivery-boy-assign');

        // Order bulk export
        Route::get('/order-bulk-export', 'orderBulkExport')->name('order-bulk-export');

        // 
        Route::post('order-payment-notification', 'unpaid_order_payment_notification_send')->name('unpaid_order_payment_notification');
    });

    Route::post('/pay_to_seller', [CommissionController::class, 'pay_to_seller'])->name('commissions.pay_to_seller');

    //Reports
    Route::controller(ReportController::class)->group(function () {
        Route::get('/in_house_sale_report', 'in_house_sale_report')->name('in_house_sale_report.index');
        Route::get('/seller_sale_report', 'seller_sale_report')->name('seller_sale_report.index');
        Route::get('/stock_report', 'stock_report')->name('stock_report.index');
        Route::get('/product-detail-report', 'product_detail_report')->name('product_detail_report.index');
        Route::get('/wish_report', 'wish_report')->name('wish_report.index');
        Route::get('/user_search_report', 'user_search_report')->name('user_search_report.index');
        Route::get('/commission-log', 'commission_history')->name('commission-log.index');
        Route::get('/wallet-history', 'wallet_transaction_history')->name('wallet-history.index');

        Route::get('/get-products-by-category', 'getProductsByCategory')->name('get.products.by.category');
        Route::get('/stock-report/filter-options', 'getStockFilterOptions')->name('stock_report.filter_options');
        Route::get('/product-detail-report/filter-options', 'getProductDetailFilterOptions')->name('product_detail_report.filter_options');
    });

    // Earning Report
    Route::group(['prefix' => 'reports'], function () {
        Route::get('/earning-payout-report', [EarningReportController::class, 'index'])->name('earning_payout_report.index');
        Route::post('/earning-payout-report/net-sales', [EarningReportController::class, 'net_sales']);
        Route::post('/earning-payout-report/payouts', [EarningReportController::class, 'payouts']);
        Route::post('/earning-payout-report/sale-analytic', [EarningReportController::class, 'sale_analytic']);
        Route::post('/earning-payout-report/payout-analytic', [EarningReportController::class, 'payout_analytic']);
    });

    // Gifts
    Route::controller(AdminGiftController::class)->group(function () {
        Route::get('/gifts', 'index')->name('gifts.index');
        Route::get('/gifts/create', 'create')->name('gifts.create');
        Route::get('/gifts/{gift}/edit', 'edit')->name('gifts.edit');
        Route::post('/gifts', 'store')->name('gifts.store');
        Route::post('/gifts/{gift}/update', 'update')->name('gifts.update');
        Route::post('/gifts/{gift}/toggle', 'toggleStatus')->name('gifts.toggle');
        Route::post('/gifts/{gift}/delete', 'destroy')->name('gifts.destroy');
    });

    // Gift Requests
    Route::controller(GiftRequestController::class)->group(function () {
        Route::get('/gift-requests', 'index')->name('gift_requests.index');
        Route::post('/gift-requests/approve', 'approve')->name('gift_requests.approve');
        Route::post('/gift-requests/deliver', 'deliver')->name('gift_requests.deliver');
        Route::post('/gift-requests/reject', 'reject')->name('gift_requests.reject');
    });

    //Blog Section
    //Blog cateory
    Route::resource('blog-category', BlogCategoryController::class);
    Route::get('/blog-category/destroy/{id}', [BlogCategoryController::class, 'destroy'])->name('blog-category.destroy');

    // Blog
    Route::resource('blog', BlogController::class);
    Route::controller(BlogController::class)->group(function () {
        Route::get('/blog/destroy/{id}', 'destroy')->name('blog.destroy');
        Route::post('/blog/change-status', 'change_status')->name('blog.change-status');
    });

    //Coupons
    Route::resource('coupon', CouponController::class);
    Route::controller(CouponController::class)->group(function () {
        Route::post('/coupon/update-status', 'updateStatus')->name('coupon.update_status');
        Route::get('/coupon/destroy/{id}', 'destroy')->name('coupon.destroy');

        //Coupon Form
        Route::post('/coupon/get_form', 'get_coupon_form')->name('coupon.get_coupon_form');
        Route::post('/coupon/get_form_edit', 'get_coupon_form_edit')->name('coupon.get_coupon_form_edit');
    });

    //Reviews
    Route::controller(ReviewController::class)->group(function () {
        Route::get('/reviews', 'index')->name('reviews.index');
        Route::post('/reviews/published', 'updatePublished')->name('reviews.published');
        Route::get('/reviews/detail-reviews/{id}', 'detailReviews')->name('detail-reviews');
        Route::get('/reviews/destroy', 'destroy')->name('reviews.destroy');

        Route::get('/custom-review/create/{productId?}', 'customReviewCreate')->name('custom-review.create');
        Route::get('/custom-review/edit/{id}', 'customReviewEdit')->name('custom-review.edit');
        Route::post('/custom-review/update', 'customReviewUpdate')->name('custom-review.update');
        Route::post('/custom-review/get-products', 'getProductByCategory')->name('get-custom-review-product-by-category');
    });

    //Support_Ticket
    Route::controller(SupportTicketController::class)->group(function () {
        Route::get('support_ticket/', 'admin_index')->name('support_ticket.admin_index');
        Route::get('support_ticket/{id}/show', 'admin_show')->name('support_ticket.admin_show');
        Route::post('support_ticket/reply', 'admin_store')->name('support_ticket.admin_store');
    });

    // Email Template
    Route::resource('email-templates', EmailTemplateController::class);
    Route::controller(EmailTemplateController::class)->group(function () {
        Route::get('/email-template/{id}', 'index')->name('email-templates.index');
        Route::post('/email-template/update-status', 'updateStatus')->name('email-template.update-status');
    });

    //Pickup_Points
    Route::resource('pick_up_points', PickupPointController::class);
    Route::controller(PickupPointController::class)->group(function () {
        Route::get('/pick_up_points/edit/{id}', 'edit')->name('pick_up_points.edit');
        Route::get('/pick_up_points/destroy/{id}', 'destroy')->name('pick_up_points.destroy');
    });

    //conversation of seller customer
    Route::controller(ConversationController::class)->group(function () {
        Route::get('conversations', 'admin_index')->name('conversations.admin_index');
        Route::get('conversations/{id}/show', 'admin_show')->name('conversations.admin_show');
    });

    // product Queries show on Admin panel
    Route::controller(ProductQueryController::class)->group(function () {
        Route::get('/product-queries', 'index')->name('product_query.index');
        Route::get('/product-queries/{id}', 'show')->name('product_query.show');
        Route::put('/product-queries/{id}', 'reply')->name('product_query.reply');
    });

    // Product Attribute
    Route::resource('attributes', AttributeController::class);
    Route::controller(AttributeController::class)->group(function () {
        Route::get('/attributes/edit/{id}', 'edit')->name('attributes.edit');
        Route::get('/attributes/destroy/{id}', 'destroy')->name('attributes.destroy');

        //Attribute Value
        Route::post('/store-attribute-value', 'store_attribute_value')->name('store-attribute-value');
        Route::get('/edit-attribute-value/{id}', 'edit_attribute_value')->name('edit-attribute-value');
        Route::post('/update-attribute-value/{id}', 'update_attribute_value')->name('update-attribute-value');
        Route::get('/destroy-attribute-value/{id}', 'destroy_attribute_value')->name('destroy-attribute-value');

        //Colors
        Route::get('/colors', 'colors')->name('colors');
        Route::post('/colors/store', 'store_color')->name('colors.store');
        Route::get('/colors/edit/{id}', 'edit_color')->name('colors.edit');
        Route::post('/colors/update/{id}', 'update_color')->name('colors.update');
        Route::get('/colors/destroy/{id}', 'destroy_color')->name('colors.destroy');
    });

    // Size Chart
    Route::resource('size-charts', SizeChartController::class);
    Route::get('/size-charts/destroy/{id}',  [SizeChartController::class, 'destroy'])->name('size-charts.destroy');
    Route::post('size-charts/get-combination',   [SizeChartController::class, 'get_combination'])->name('size-charts.get-combination');

    // Measurement Points
    Route::resource('measurement-points', MeasurementPointsController::class);
    Route::get('/measurement-points/destroy/{id}',  [MeasurementPointsController::class, 'destroy'])->name('measurement-points.destroy');

    // Addon
    Route::resource('addons', AddonController::class);
    Route::post('/addons/activation', [AddonController::class, 'activation'])->name('addons.activation');

    //Customer Package
    Route::resource('customer_packages', CustomerPackageController::class);
    Route::controller(CustomerPackageController::class)->group(function () {
        Route::get('/customer_packages/edit/{id}', 'edit')->name('customer_packages.edit');
        Route::get('/customer_packages/destroy/{id}', 'destroy')->name('customer_packages.destroy');
    });

    //Classified Products
    Route::controller(CustomerProductController::class)->group(function () {
        Route::get('/classified_products', 'customer_product_index')->name('classified_products');
        Route::post('/classified_products/published', 'updatePublished')->name('classified_products.published');
        Route::get('/classified_products/destroy/{id}', 'destroy_by_admin')->name('classified_products.destroy');
    });

    // Countries
    Route::resource('countries', CountryController::class);
    Route::post('/countries/status', [CountryController::class, 'updateStatus'])->name('countries.status');
    Route::post('/countries/defaults', [CountryController::class, 'updateDefaults'])->name('countries.defaults');
    Route::post('/countries/system-default', [CountryController::class, 'updateSystemDefaultCountry'])->name('countries.system_default');

    // States
    Route::resource('states', StateController::class);
    Route::post('/states/status', [StateController::class, 'updateStatus'])->name('states.status');

    // Carriers
    Route::resource('carriers', CarrierController::class);
    Route::controller(CarrierController::class)->group(function () {
        Route::get('/carriers/destroy/{id}', 'destroy')->name('carriers.destroy');
        Route::post('/carriers/update_status', 'updateStatus')->name('carriers.update_status');
    });

    // Transport delivery masters
    Route::resource('transports', TransportController::class);
    Route::controller(TransportController::class)->group(function () {
        Route::get('/transports/destroy/{id}', 'destroy')->name('transports.destroy');
        Route::post('/transports/update_status', 'updateStatus')->name('transports.update_status');
    });

    Route::resource('booked-to', BookedToController::class);
    Route::controller(BookedToController::class)->group(function () {
        Route::get('/booked-to/destroy/{id}', 'destroy')->name('booked-to.destroy');
        Route::post('/booked-to/update_status', 'updateStatus')->name('booked-to.update_status');
    });

    Route::resource('local-delivery-partners', LocalDeliveryPartnerController::class);
    Route::controller(LocalDeliveryPartnerController::class)->group(function () {
        Route::get('/local-delivery-partners/destroy/{id}', 'destroy')->name('local-delivery-partners.destroy');
        Route::post('/local-delivery-partners/update_status', 'updateStatus')->name('local-delivery-partners.update_status');
    });


    // Zones
    Route::resource('zones', ZoneController::class);
    Route::get('/zones/destroy/{id}', [ZoneController::class, 'destroy'])->name('zones.destroy');

    Route::resource('cities', CityController::class);
    Route::controller(CityController::class)->group(function () {
        Route::get('/cities/edit/{id}', 'edit')->name('cities.edit');
        Route::get('/cities/destroy/{id}', 'destroy')->name('cities.destroy');
        Route::post('/cities/status', 'updateStatus')->name('cities.status');
    });

    Route::view('/system/update', 'backend.system.update')->name('system_update');
    Route::view('/system/server-status', 'backend.system.server_status')->name('system_server');
    Route::view('/system/import-demo-data', 'backend.system.import_demo_data')->name('import_demo_data');

    Route::post('/import-data', [BusinessSettingsController::class, 'import_data'])->name('import_data');

    // uploaded files
    Route::resource('/uploaded-files', AizUploadController::class);
    Route::controller(AizUploadController::class)->group(function () {
        Route::any('/uploaded-files/file-info', 'file_info')->name('uploaded-files.info');
        Route::post('/uploaded-files/{upload}/rename', 'rename')->name('uploaded-files.rename');
        Route::get('/uploaded-files/destroy/{id}', 'destroy')->name('uploaded-files.destroy');
        Route::post('/bulk-uploaded-files-delete', 'bulk_uploaded_files_delete')->name('bulk-uploaded-files-delete');
        Route::get('/all-file', 'all_file');
    });

    Route::controller(NotificationController::class)->group(function () {
        Route::get('/all-notifications', 'adminIndex')->name('admin.all-notifications');
        Route::get('/notifications/dropdown', 'adminDropdown')->name('admin.notifications.dropdown');
        Route::get('/notification-settings', 'notificationSettings')->name('notification.settings');

        Route::post('/notifications/bulk-delete', 'bulkDeleteAdmin')->name('admin.notifications.bulk_delete');
        Route::get('/notification/read-and-redirect/{id}', 'readAndRedirect')->name('admin.notification.read-and-redirect');

        Route::get('/custom-notification', 'customNotification')->name('custom_notification');
        Route::post('/custom-notification/send', 'sendCustomNotification')->name('custom_notification.send');

        Route::get('/custom-notification/history', 'customNotificationHistory')->name('custom_notification.history');
        Route::get('/custom-notifications.delete/{identifier}', 'customNotificationSingleDelete')->name('custom-notifications.delete');
        Route::post('/custom-notifications.bulk_delete', 'customNotificationBulkDelete')->name('custom-notifications.bulk_delete');
        Route::post('/custom-notified-customers-list', 'customNotifiedCustomersList')->name('custom_notified_customers_list');
    });

    Route::resource('notification-type', NotificationTypeController::class);
    Route::controller(NotificationTypeController::class)->group(function () {
        Route::get('/notification-type/edit/{id}', 'edit')->name('notification-type.edit');
        Route::post('/notification-type/update-status', 'updateStatus')->name('notification-type.update-status');
        Route::get('/notification-type/destroy/{id}', 'destroy')->name('notification-type.destroy');
        Route::post('/notification-type/bulk_delete', 'bulkDelete')->name('notifications-type.bulk_delete');
        Route::post('/notification-type.get-default-text', 'getDefaulText')->name('notification_type.get_default_text');
    });

    Route::get('/clear-cache', [AdminController::class, 'clearCache'])->name('cache.clear');

    Route::get('/admin-permissions', [RoleController::class, 'create_admin_permissions']);

    Route::get('/pending-in-carts', [MarketingController::class, 'list_user_and_cart_details'])->name('list_user_and_cart');
});
