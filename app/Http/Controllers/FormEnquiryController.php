<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\FormEnquiry;
use App\Models\Product;
use App\Models\State;
use App\Models\Upload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FormEnquiryController extends Controller
{
    public function create(Request $request)
    {
        $countries = get_active_countries();
        $defaultCountryId = (int) $request->session()->get('country_id', 0);
        $defaultCountryId = $countries->contains('id', $defaultCountryId)
            ? $defaultCountryId
            : (int) optional($countries->first())->id;
        $States = State::orderBy('name')->where('country_id', 101)->get(['id', 'name']);

        $codes = [
            'enquiry'    => $this->previewFormCode('enquiry'),
            'suggestion' => $this->previewFormCode('suggestion'),
        ];

        $undertakingSample = get_setting('undertaking_form_sample'); // upload id expected
        
        // Get the type from query parameter (enquiry or suggestion)
        $defaultType = $request->input('type', 'enquiry');
        if (!in_array($defaultType, ['enquiry', 'suggestion'])) {
            $defaultType = 'enquiry';
        }

        return view('frontend.form_enquiry', [
            'gov_state'          => $States,
            'countries'          => $countries,
            'defaultCountryId'   => $defaultCountryId,
            'today'              => now()->toDateString(),
            'nextCodes'          => $codes,
            'undertakingSample'  => $undertakingSample,
            'defaultType'        => $defaultType,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type'           => 'required|in:enquiry,suggestion',
            'category'       => 'required|in:human,veterinary',
            'domestic_type'  => 'required|in:domestic,govt_supply,exports,third_party,loan_licence',
            'product_name'   => 'required|string|max:255',
            'product_id'     => 'nullable|exists:products,id',
            'contact_person' => 'required|string|max:255',
            'mobile_number'  => 'required|string|max:30',
            'email'          => 'nullable|email|max:255',
            'common_gst_no'  => 'nullable|string|max:20',
            'common_aadhar_no' => 'nullable|string|max:30',
        ]);

        $data = $request->all();
        $data['form_code'] = $this->nextFormCode($validated['type']);
        $data['form_date'] = now()->toDateString();

        $data['product_categories'] = $this->normalizeTagify($request->input('product_categories'));

        // Handle uploads (multiple inputs to Upload model, stored on default disk e.g., s3)
        $fileFields = [
            'composition_files',
            'gov_tender_files',
            'gov_required_docs',
            'gov_authorisation_files',
            'export_iec_files',
            'export_design_files',
            'export_required_docs',
            'export_authorisation_files',
            'tp_trademark_files',
            'tp_undertaking_files',
            'tp_drug_approval_files',
            'tp_design_files',
            'loan_trademark_files',
            'loan_undertaking_files',
            'loan_drug_approval_files',
            'loan_design_files',
            'common_product_photos',
            'common_product_list_files',
            'common_drug_licence_files',
            'common_gst_files',
            'common_aadhar_files',
            'visiting_card_files',
        ];

        foreach ($fileFields as $field) {
            $data[$field] = $this->storeUploads($request, $field);
        }

        DB::transaction(function () use ($data, &$enquiry) {
            $enquiry = FormEnquiry::create([
                // essentials
                'type'           => $data['type'],
                'form_code'      => $data['form_code'],
                'form_date'      => $data['form_date'],
                'category'       => $data['category'],
                'domestic_type'  => $data['domestic_type'],
                // product info
                'product_id'         => $data['product_id'] ?? null,
                'product_name'       => $data['product_name'] ?? null,
                'drug_role'          => $data['drug_role'] ?? null,
                'product_categories' => $data['product_categories'] ?? null,
                'product_group'      => $data['product_group'] ?? null,
                'brand_name'         => $data['brand_name'] ?? null,
                'composition_text'   => $data['composition_text'] ?? null,
                'description_text'   => $data['description_text'] ?? null,
                'composition_files'  => $data['composition_files'] ?? null,
                'pack_size'          => $data['pack_size'] ?? null,
                'quantity'           => $data['quantity'] ?? null,
                // govt supply
                'gov_tender_no'         => $data['gov_tender_no'] ?? null,
                'gov_state_id'          => $data['gov_state_id'] ?? null,
                'gov_department'        => $data['gov_department'] ?? null,
                'gov_start_date'        => $data['gov_start_date'] ?? null,
                'gov_end_date'          => $data['gov_end_date'] ?? null,
                'gov_tender_files'      => $data['gov_tender_files'] ?? null,
                'gov_required_docs'     => $data['gov_required_docs'] ?? null,
                'gov_authorisation_files' => $data['gov_authorisation_files'] ?? null,
                // exports
                'export_country_id'        => $data['export_country_id'] ?? null,
                'export_iec_files'         => $data['export_iec_files'] ?? null,
                'export_design_files'      => $data['export_design_files'] ?? null,
                'export_required_docs'     => $data['export_required_docs'] ?? null,
                'export_authorisation_files' => $data['export_authorisation_files'] ?? null,
                // third party
                'tp_brand_name'         => $data['tp_brand_name'] ?? null,
                'tp_trademark_files'    => $data['tp_trademark_files'] ?? null,
                'tp_undertaking_files'  => $data['tp_undertaking_files'] ?? null,
                'tp_drug_approval_files'=> $data['tp_drug_approval_files'] ?? null,
                'tp_design_files'       => $data['tp_design_files'] ?? null,
                // loan licence
                'loan_brand_name'        => $data['loan_brand_name'] ?? null,
                'loan_trademark_files'   => $data['loan_trademark_files'] ?? null,
                'loan_undertaking_files' => $data['loan_undertaking_files'] ?? null,
                'loan_drug_approval_files'=> $data['loan_drug_approval_files'] ?? null,
                'loan_design_files'      => $data['loan_design_files'] ?? null,
                // common files
                'common_product_photos'   => $data['common_product_photos'] ?? null,
                'common_product_list_files' => $data['common_product_list_files'] ?? null,
                'common_drug_licence_files' => $data['common_drug_licence_files'] ?? null,
                'common_gst_no'            => $data['common_gst_no'] ?? null,
                'common_gst_files'         => $data['common_gst_files'] ?? null,
                'common_aadhar_no'         => $data['common_aadhar_no'] ?? null,
                'common_aadhar_files'      => $data['common_aadhar_files'] ?? null,
                'special_instruction'      => $data['special_instruction'] ?? null,
                // company
                'company_name'       => $data['company_name'] ?? null,
                'company_address'    => $data['company_address'] ?? null,
                'company_post'       => $data['company_post'] ?? null,
                'company_district'   => $data['company_district'] ?? null,
                'company_state_id'   => $data['company_state_id'] ?? null,
                'company_pincode'    => $data['company_pincode'] ?? null,
                'company_country_id' => $data['company_country_id'] ?? null,
                'contact_person'     => $data['contact_person'] ?? null,
                'designation'        => $data['designation'] ?? null,
                'mobile_country_code'=> $data['mobile_country_code'] ?? null,
                'mobile_number'      => $data['mobile_number'] ?? null,
                'email'              => $data['email'] ?? null,
                'website'            => $data['website'] ?? null,
                'visiting_card_files'=> $data['visiting_card_files'] ?? null,
            ]);
        });

        flash(translate('Your request has been submitted successfully. Reference: ') . $data['form_code'])->success();

        return redirect()->route('form_enquiry.create');
    }

    public function products(Request $request): JsonResponse
    {
        $categorySlug = strtolower((string) $request->input('category', ''));
        $search       = trim((string) $request->input('q', ''));

        // Map slug to root category record
        $categoryName = $categorySlug === 'human' ? 'Human' : ($categorySlug === 'veterinary' ? 'Veterinary' : null);
        $categoryId   = null;
        if ($categoryName) {
            $categoryId = Category::whereRaw('LOWER(name) = ?', [strtolower($categoryName)])
                ->value('id');
        }

        $productIds = Product::query()->pluck('id'); // fallback to all
        if ($categoryId) {
            $productIds = DB::table('product_categories')
                ->where('category_id', $categoryId)
                ->pluck('product_id');
            // keep empty collection if none match
        }

        $products = filter_products(Product::query())
            ->whereIn('id', $productIds)
            ->select('id', 'name', 'drug_name', 'description', 'role_label', 'brand_id', 'group_id', 'thumbnail_img', 'contents')
            ->with(['brand:id,name', 'main_group:id,name', 'categories:id,name,parent_id'])
            ->latest();

        if ($search !== '') {
            $products->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('drug_name', 'like', '%' . $search . '%');
            });
        }

        $items = $products->get()->map(function ($product) {
            $sections = $this->extractContentSections($product->contents);
            return [
                'id'        => $product->id,
                'name'      => $product->name,
                'drug_name' => $product->drug_name,
                'role'      => $product->role_label,
                'group'     => $product->main_group?->name,
                'brand'     => $product->brand?->name,
                'categories'=> $product->categories->pluck('name')->filter()->values(),
                'composition' => $sections['composition'],
                'description' => $this->htmlToPlainText((string) ($product->description ?? '')),
                'image'     => $product->thumbnail_img ? uploaded_asset($product->thumbnail_img) : null,
            ];
        });

        return response()->json($items);
    }

    public function productDetails(Product $product): JsonResponse
    {
        $product->load(['brand:id,name', 'main_group:id,name', 'categories:id,name']);
        $sections = $this->extractContentSections($product->contents);

        return response()->json([
            'id'         => $product->id,
            'name'       => $product->name,
            'drug_name'  => $product->drug_name,
            'role'       => $product->role_label,
            'group'      => $product->main_group?->name,
            'brand'      => $product->brand?->name,
            'categories' => $product->categories->pluck('name')->filter()->values(),
            'composition' => $sections['composition'],
            'description' => $this->htmlToPlainText((string) ($product->description ?? '')),
            'image'      => $product->thumbnail_img ? uploaded_asset($product->thumbnail_img) : null,
        ]);
    }

    public function gstDetails(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gst_no' => ['required', 'regex:/^[0-9A-Z]{15}$/i'],
        ]);

        $gstNo = strtoupper((string) $validated['gst_no']);
        $response = function_exists('fetchGstinDetails') ? fetchGstinDetails($gstNo) : null;

        if (!$response) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch GST details at the moment.',
            ], 422);
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid GST response payload.',
            ], 422);
        }

        $statusCode = (int) ($decoded['status_code'] ?? 200);
        $payload = $decoded['data'] ?? $decoded;
        if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
            $payload = $payload['data'];
        }

        if ($statusCode !== 200 || !is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => (string) ($decoded['message'] ?? 'GST details not found.'),
            ], 422);
        }

        $principal = (array) data_get($payload, 'contact_details.principal', []);
        $companyName = $this->firstNonEmpty($payload, ['business_name', 'legal_name']);

        $addressLine = $this->buildAddressFromPayload($payload);
        $parsedAddress = $this->parseIndianAddressParts($addressLine);

        $contactPerson = null;
        $promoters = data_get($payload, 'promoters', []);
        if (is_array($promoters) && !empty($promoters[0]) && is_scalar($promoters[0])) {
            $contactPerson = trim((string) $promoters[0]);
        }

        $designation = $this->firstNonEmpty($payload, ['constitution_of_business']);

        $mobileNumber = $this->onlyDigits($this->firstNonEmpty($principal, ['mobile']));
        if ($mobileNumber !== null && strlen($mobileNumber) > 10) {
            $mobileNumber = substr($mobileNumber, -10);
        }

        $email = $this->firstNonEmpty($principal, ['email']);
        $district = $parsedAddress['district'] ?? null;
        $post = $parsedAddress['post'] ?? null;
        $pincode = $parsedAddress['pincode'] ?? null;
        $stateName = $parsedAddress['state'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'GST details fetched.',
            'data' => [
                'common_gst_no' => $gstNo,
                'company_name' => $companyName,
                'company_address' => $addressLine,
                'company_district' => $district,
                'company_post' => $post,
                'company_pincode' => $pincode,
                'company_state_name' => $stateName,
                'contact_person' => $contactPerson,
                'designation' => $designation,
                'mobile_country_code' => $mobileNumber ? '+91' : null,
                'mobile_number' => $mobileNumber,
                'email' => $email,
            ],
        ]);
    }

    protected function extractContentSections($rawContents): array
    {
        $rows = $this->decodeContentsRows($rawContents);
        $compositionParts = [];
        foreach ($rows as $row) {
            $title = strtolower(trim((string) ($row['title'] ?? '')));
            $content = $this->htmlToPlainText((string) ($row['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            if (str_contains($title, 'composition')) {
                $compositionParts[] = $content;
                continue;
            }
        }

        return [
            'composition' => $compositionParts ? implode("\n\n", $compositionParts) : null,
            'description' => null,
        ];
    }

    protected function decodeContentsRows($rawContents): array
    {
        if (!is_string($rawContents) || trim($rawContents) === '') {
            return [];
        }

        $decoded = json_decode($rawContents, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            $normalized = preg_replace('/([{,]\s*)([A-Za-z_][A-Za-z0-9_]*)\s*:/', '$1"$2":', $rawContents);
            $normalized = preg_replace('/,(\s*[}\]])/', '$1', (string) $normalized);
            $decoded = json_decode((string) $normalized, true);
        }

        if (!is_array($decoded)) {
            return [];
        }

        if (array_key_exists('title', $decoded) || array_key_exists('content', $decoded)) {
            return [$decoded];
        }

        return array_values(array_filter($decoded, fn ($row) => is_array($row)));
    }

    protected function htmlToPlainText(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $text = str_ireplace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", (string) $text);

        return trim((string) $text);
    }

    protected function normalizeTagify(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $list = collect($decoded)
                ->pluck('value')
                ->filter()
                ->map(fn ($v) => trim((string) $v))
                ->filter()
                ->unique()
                ->values();

            return $list->implode(',');
        }

        return $value;
    }

    protected function firstNonEmpty(array $source, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($source, $path);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    protected function buildAddressFromPayload(array $payload): ?string
    {
        $singleLineAddress = $this->firstNonEmpty($payload, [
            'contact_details.principal.address',
            'address',
        ]);
        if ($singleLineAddress) {
            return $singleLineAddress;
        }
        return null;
    }

    protected function parseIndianAddressParts(?string $address): array
    {
        $address = trim((string) $address);
        if ($address === '') {
            return [];
        }

        $pincode = null;
        if (preg_match('/\b(\d{6})\b/', $address, $match)) {
            $pincode = $match[1];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
        if (count($parts) < 2) {
            return ['pincode' => $pincode];
        }

        $state = null;
        $district = null;
        $post = null;

        if (count($parts) >= 2) {
            $state = $parts[count($parts) - 2] ?? null;
        }
        if (count($parts) >= 3) {
            $district = $parts[count($parts) - 3] ?? null;
        }
        if (count($parts) >= 4) {
            $post = $parts[count($parts) - 4] ?? null;
        }

        return [
            'state' => $state ?: null,
            'district' => $district ?: null,
            'post' => $post ?: null,
            'pincode' => $pincode,
        ];
    }

    protected function onlyDigits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);
        return $digits !== '' ? $digits : null;
    }

    protected function previewFormCode(string $type): string
    {
        $prefix = $type === 'suggestion' ? 'S-' : 'E-';
        $serial = str_pad($this->nextSerialForType($type), 5, '0', STR_PAD_LEFT);
        return $prefix . $serial;
    }

    protected function nextFormCode(string $type): string
    {
        return $this->previewFormCode($type);
    }

    protected function nextSerialForType(string $type): int
    {
        $prefix = $type === 'suggestion' ? 'S-' : 'E-';
        $maxSerial = 0;

        FormEnquiry::where('type', $type)
            ->whereNotNull('form_code')
            ->pluck('form_code')
            ->each(function ($code) use ($prefix, &$maxSerial) {
                $value = trim((string) $code);
                if ($value === '' || stripos($value, $prefix) !== 0) {
                    return;
                }

                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)/i', $value, $match)) {
                    $serial = (int) $match[1];
                    if ($serial > $maxSerial) {
                        $maxSerial = $serial;
                    }
                }
            });

        return $maxSerial + 1;
    }

    protected function rootCategoryFor(string $category): ?Category
    {
        if ($category === '') {
            return null;
        }

        $root = Category::whereRaw('LOWER(name) = ?', [$category])->first();
        if ($root) {
            return $root;
        }

        // fallback: use known parent ids from header config if set
        $settingKey = $category === 'human' ? 'header_nav_menu_human' : 'header_nav_menu_veterinary';
        $ids = json_decode(get_setting($settingKey), true) ?? [];
        $firstId = $ids[0] ?? null;
        return $firstId ? Category::find($firstId) : null;
    }

    protected function storeUploads(Request $request, string $field): ?string
    {
        if (!$request->hasFile($field)) {
            return null;
        }

        $dir  = 'uploads/enquiry/' . now()->format('Y/m');
        $ids  = [];

        foreach ((array) $request->file($field) as $file) {
            if (!$file->isValid()) {
                continue;
            }
            $uploadRequest = Request::create('/aiz-uploader/upload', 'POST', [
                'is_hidden'        => true,
                'upload_dir'       => $dir,
                'return_upload_id' => true,
            ]);
            $uploadRequest->files->set('aiz_file', $file);

            $response = app(\App\Http\Controllers\AizUploadController::class)->upload($uploadRequest);
            $uploadId = null;
            if ($response instanceof JsonResponse) {
                $payload = $response->getData(true);
                $uploadId = $payload['upload_id'] ?? null;
            }

            if ($uploadId) {
                $ids[] = $uploadId;
            }
        }

        return $ids ? implode(',', $ids) : null;
    }

    // ---------------- Admin ----------------
    public function adminIndex(Request $request)
    {
        $query = FormEnquiry::query()->orderBy('created_at', 'desc');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('domestic_type')) {
            $query->where('domestic_type', $request->domestic_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('form_code', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            });
        }

        $enquiries = $query->paginate(20);

        return view('backend.form_enquiries.index', [
            'enquiries' => $enquiries,
            'filters' => $request->only(['type', 'domestic_type', 'search']),
        ]);
    }

    public function adminShow(FormEnquiry $formEnquiry)
    {
        return view('backend.form_enquiries.show', [
            'item' => $formEnquiry,
        ]);
    }
}
