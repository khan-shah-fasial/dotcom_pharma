<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\BookedTo;
use App\Models\Company;
use App\Models\Country;
use App\Models\LocalDeliveryPartner;
use App\Models\SeaPort;
use App\Models\ShippingMethod;
use App\Models\Staff;
use App\Models\Transport;
use App\Support\InvoiceType;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UiPreviewController extends Controller
{
    public function index()
    {
        return view('backend.previews.index', [
            'previews' => $this->catalog(),
        ]);
    }

    public function show(Request $request, string $preview)
    {
        $item = $this->catalog()[$preview] ?? null;
        if (!$item) {
            throw new NotFoundHttpException();
        }

        return view($item['view'], array_merge(
            $item['data']($request),
            [
                'previewSlug' => $preview,
                'previewTitle' => $item['title'],
                'previews' => $this->catalog(),
            ]
        ));
    }

    protected function catalog(): array
    {
        return [
            'shipping-form' => [
                'title' => 'Order shipping form',
                'description' => 'Excel-aligned Additional Details and Shipping grouping. Frontend only; nothing is saved.',
                'view' => 'backend.previews.shipping_form',
                'data' => fn (Request $request) => $this->shippingFormData($request),
            ],
        ];
    }

    protected function shippingFormData(Request $request): array
    {
        $staffMap = function ($row) {
            return [
                'id' => $row->user_id,
                'name' => trim(optional($row->user)->name . ($row->designation ? ' - ' . $row->designation : '')),
            ];
        };
        $staffMaster = Staff::with(['user', 'role'])
            ->where('status', 1)
            ->whereHas('user')
            ->get()
            ->sortBy(fn ($row) => strtolower((string) optional($row->user)->name))
            ->values();
        $fallbackStaff = collect([
            ['id' => 1, 'name' => 'Ravi Kumar - Sales'],
            ['id' => 2, 'name' => 'Anita Shah - Dispatch'],
            ['id' => 3, 'name' => 'Mehul Patel - Accounts'],
        ]);
        $mapStaff = fn ($rows) => $rows->map($staffMap)->filter(fn ($row) => $row['name'] !== '')->values();
        $salesPeople = $mapStaff($staffMaster->filter(function ($staff) {
            return (bool) preg_match('/sales|business development|marketing/i', trim(implode(' ', [$staff->designation, optional($staff->role)->name])));
        }));
        $packedStaff = $mapStaff($staffMaster->filter(fn ($staff) => strcasecmp((string) optional($staff->role)->name, 'Packing') === 0));
        $checkedStaff = $mapStaff($staffMaster->filter(fn ($staff) => strcasecmp((string) optional($staff->role)->name, 'Checking') === 0));
        $billingStaff = $mapStaff($staffMaster->filter(fn ($staff) => strcasecmp((string) optional($staff->role)->name, 'Billing') === 0));
        $allStaff = $mapStaff($staffMaster);
        if ($allStaff->isEmpty()) {
            $allStaff = $fallbackStaff;
        }
        if ($salesPeople->isEmpty()) {
            $salesPeople = $allStaff;
        }
        if ($packedStaff->isEmpty()) {
            $packedStaff = $allStaff;
        }
        if ($checkedStaff->isEmpty()) {
            $checkedStaff = $allStaff;
        }
        if ($billingStaff->isEmpty()) {
            $billingStaff = $allStaff;
        }

        $transports = Transport::active()->orderBy('name')->get()->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'mode' => $row->mode ?: 'surface',
        ])->values();

        if ($transports->isEmpty()) {
            $transports = collect([
                ['id' => 11, 'name' => 'VRL Logistics', 'mode' => 'surface'],
                ['id' => 12, 'name' => 'Gati Surface', 'mode' => 'surface'],
                ['id' => 21, 'name' => 'Maersk Line', 'mode' => 'sea'],
                ['id' => 22, 'name' => 'MSC Shipping', 'mode' => 'sea'],
                ['id' => 31, 'name' => 'Qatar Airways Cargo', 'mode' => 'air'],
                ['id' => 32, 'name' => 'Emirates SkyCargo', 'mode' => 'air'],
            ]);
        }

        $bookedToOptions = BookedTo::active()->orderBy('name')->get()->map(fn ($row) => [
            'id' => $row->id,
            'transport_id' => $row->transport_id,
            'name' => $row->name,
        ])->values();

        if ($bookedToOptions->isEmpty()) {
            $bookedToOptions = collect([
                ['id' => 101, 'transport_id' => 11, 'name' => 'Ahmedabad'],
                ['id' => 102, 'transport_id' => 11, 'name' => 'Surat'],
                ['id' => 103, 'transport_id' => 12, 'name' => 'Jaipur'],
                ['id' => 201, 'transport_id' => 21, 'name' => 'Jebel Ali'],
                ['id' => 301, 'transport_id' => 31, 'name' => 'DOH'],
            ]);
        }

        $shippingMethods = ShippingMethod::where('is_active', 1)->orderBy('name')->get()->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ])->values();

        if ($shippingMethods->isEmpty()) {
            $shippingMethods = collect([
                ['id' => 1, 'name' => 'Delhivery', 'slug' => 'delhivery'],
                ['id' => 2, 'name' => 'Blue Dart', 'slug' => 'bluedart'],
            ]);
        }

        $localDeliveryPartners = LocalDeliveryPartner::active()->orderBy('name')->get()->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->name,
        ])->values();

        if ($localDeliveryPartners->isEmpty()) {
            $localDeliveryPartners = collect([
                ['id' => 41, 'name' => 'City Rider'],
                ['id' => 42, 'name' => 'Same Day Local'],
            ]);
        }

        $mapLocation = function ($row, string $codeKey) {
            return [
                'id' => (string) $row->id,
                'country_id' => (string) ($row->country_id ?: ''),
                'country' => $row->country ?: 'Unknown',
                'country_key' => strtolower(trim((string) ($row->country ?: 'unknown'))),
                'name' => $row->name,
                'code' => $row->{$codeKey} ?? '',
                'details' => $row->toArray(),
            ];
        };

        $seaPorts = SeaPort::where('status', 1)->orderBy('country')->orderBy('name')->get()
            ->map(fn ($row) => $mapLocation($row, 'un_locode'))->values();
        $airports = Airport::where('status', 1)->orderBy('country')->orderBy('name')->get()
            ->map(fn ($row) => $mapLocation($row, 'iata'))->values();

        if ($seaPorts->isEmpty()) {
            $seaPorts = collect([
                ['id' => '1', 'country_id' => '', 'country' => 'India', 'country_key' => 'india', 'name' => 'Nhava Sheva (JNPT)', 'code' => 'INNSA', 'details' => []],
                ['id' => '2', 'country_id' => '', 'country' => 'United Arab Emirates', 'country_key' => 'uae', 'name' => 'Jebel Ali', 'code' => 'AEJEA', 'details' => []],
            ]);
        }
        if ($airports->isEmpty()) {
            $airports = collect([
                ['id' => '1', 'country_id' => '', 'country' => 'India', 'country_key' => 'india', 'name' => 'Mumbai (BOM)', 'code' => 'BOM', 'details' => []],
                ['id' => '2', 'country_id' => '', 'country' => 'United Arab Emirates', 'country_key' => 'uae', 'name' => 'Dubai (DXB)', 'code' => 'DXB', 'details' => []],
            ]);
        }

        $companies = Company::orderBy('company_name')->get(['id', 'code', 'company_name']);
        $countries = Country::where('status', 1)->orderBy('name')->get(['id', 'name']);

        return [
            'salesPeople' => $salesPeople,
            'packedStaff' => $packedStaff,
            'checkedStaff' => $checkedStaff,
            'billingStaff' => $billingStaff,
            'transports' => $transports,
            'bookedToOptions' => $bookedToOptions,
            'shippingMethods' => $shippingMethods,
            'courierServices' => [
                'delhivery' => ['Surface', 'Express'],
                'bluedart' => ['Domestic Priority', 'Dart Plus'],
            ],
            'localDeliveryPartners' => $localDeliveryPartners,
            'seaPorts' => $seaPorts,
            'airports' => $airports,
            'companies' => $companies,
            'countries' => $countries,
            'domesticDeliveryTerms' => InvoiceType::DOMESTIC_DELIVERY_TERMS,
            'internationalDeliveryTerms' => InvoiceType::INTERNATIONAL_DELIVERY_TERMS,
            'domesticPaymentTerms' => InvoiceType::DOMESTIC_PAYMENT_TERMS,
            'internationalPaymentTerms' => InvoiceType::INTERNATIONAL_PAYMENT_TERMS,
            'airCargoTypes' => [
                'general_cargo' => 'General Cargo',
                'express_cargo' => 'Express Cargo',
                'perishable_cargo' => 'Perishable Cargo',
                'temperature_controlled' => 'Temperature Controlled',
                'dangerous_goods' => 'Dangerous Goods',
                'live_animals' => 'Live Animals',
                'valuable_cargo' => 'Valuable Cargo',
                'oversized_cargo' => 'Oversized Cargo',
            ],
        ];
    }
}
