@extends('backend.layouts.app')

@section('content')
    <style>
        .purchase-history-sheet {
            min-width: 1900px;
            table-layout: fixed;
            color: #111;
            font-size: 11px;
        }
        .purchase-history-sheet th,
        .purchase-history-sheet td {
            border-color: #222 !important;
            padding: 6px 5px !important;
            text-align: center;
            vertical-align: middle !important;
            white-space: normal;
            overflow-wrap: anywhere;
        }
        .purchase-history-sheet th {
            font-family: Georgia, serif;
            font-weight: 700;
            line-height: 1.15;
        }
        .purchase-history-sheet .cell-lines span {
            display: block;
            min-height: 16px;
        }
        .purchase-history-sheet .party-cell {
            text-align: left;
        }
    </style>
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('Purchase History') }}</h1>
        </div>
    </div>

    @if(session('purchase_history_error_log_available'))
        <div class="alert alert-warning mb-3 d-flex justify-content-between align-items-center">
            <div>
                {{ translate('Some rows failed during the last import.') }}
            </div>
            <a href="{{ route('admin.purchase_history.error_log') }}"
               class="btn btn-sm btn-outline-primary"
               target="_blank">
                {{ translate('Open error report') }}
            </a>
        </div>
    @endif

    <div class="card">
        <form action="{{ route('admin.purchase_history.index') }}" method="GET" id="purchase-history-filters">
            @php
                $filtersApplied = collect([
                    $search,
                    request('account'),
                    request('product_sku'),
                    request('sales_man_name'),
                    request('order_date_from'),
                    request('order_date_to'),
                ])->contains(fn ($value) => $value !== null && $value !== '');
            @endphp
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
                <div class="mb-2">
                    <h5 class="mb-0 h6">{{ translate('Purchase History Records') }}</h5>
                    @if($filtersApplied)
                        <span class="badge badge-info mt-2">{{ translate('Filters applied') }}</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center">
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                            data-target="#purchaseHistoryFilterModal">
                        {{ translate('Open Filters') }}
                    </button>
                    <a href="{{ route('admin.purchase_history.index') }}" class="btn btn-danger mr-2 mb-2">
                        {{ translate('Reset') }}
                    </a>
                    <button type="button" class="btn btn-outline-primary mr-2 mb-2" data-toggle="modal"
                            data-target="#purchase-history-import-modal">
                        <i class="las la-file-import mr-1"></i>{{ translate('Import party wise sheets') }}
                    </button>
                    <a href="{{ route('admin.purchase_history.export', request()->query()) }}"
                       class="btn btn-outline-success mr-2 mb-2">
                        <i class="las la-file-excel mr-1"></i>{{ translate('Export Excel') }}
                    </a>
                    <a href="{{ route('admin.purchase_history.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="btn btn-success mb-2">
                        <i class="las la-download mr-1"></i>{{ translate('Fast Export CSV') }}
                    </a>
                </div>
            </div>

            <div class="modal fade" id="purchaseHistoryFilterModal" tabindex="-1" role="dialog"
                 aria-labelledby="purchaseHistoryFilterModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="purchaseHistoryFilterModalLabel">
                                {{ translate('Filter Purchase History') }}
                            </h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="row gutters-5">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="search">{{ translate('Global Search') }}</label>
                                    <input type="text" class="form-control" id="search" name="search" value="{{ $search }}"
                                           placeholder="{{ translate('Serial / Order / Invoice / SKU / Salesman / State / City') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="account">{{ translate('Account') }}</label>
                                    <input type="text" class="form-control" id="account" name="account"
                                           value="{{ request('account') }}"
                                           placeholder="{{ translate('Account ID / Company Name / User Name') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="product_sku">{{ translate('Product SKU') }}</label>
                                    <select class="form-control aiz-selectpicker" id="product_sku" name="product_sku"
                                            data-live-search="true" data-placeholder="{{ translate('All SKUs') }}">
                                        <option value="">{{ translate('All SKUs') }}</option>
                                        @foreach($skuOptions as $sku)
                                            <option value="{{ $sku }}" @if(request('product_sku') === $sku) selected @endif>{{ $sku }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="sales_man_name">{{ translate('Salesman Name') }}</label>
                                    <input type="text" class="form-control" id="sales_man_name" name="sales_man_name"
                                           value="{{ request('sales_man_name') }}" placeholder="{{ translate('Salesman') }}">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label" for="order_date_range">{{ translate('Order Date Range') }}</label>
                                    <input type="text" class="form-control aiz-date-range" id="order_date_range"
                                           name="order_date_range"
                                           value="{{ request('order_date_from') && request('order_date_to') ? request('order_date_from').' to '.request('order_date_to') : '' }}"
                                           data-time-picker="false" data-format="YYYY-MM-DD"
                                           placeholder="{{ translate('YYYY-MM-DD to YYYY-MM-DD') }}">
                                    <input type="hidden" name="order_date_from" id="order_date_from" value="{{ request('order_date_from') }}">
                                    <input type="hidden" name="order_date_to" id="order_date_to" value="{{ request('order_date_to') }}">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label" for="sort_by">{{ translate('Sort By') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_by" name="sort_by">
                                        <option value="order_date" @if($sortBy=='order_date') selected @endif>{{ translate('Order Date') }}</option>
                                        <option value="serial_number" @if($sortBy=='serial_number') selected @endif>{{ translate('Serial Number') }}</option>
                                        <option value="order_number" @if($sortBy=='order_number') selected @endif>{{ translate('Order Number') }}</option>
                                        <option value="invoice_number" @if($sortBy=='invoice_number') selected @endif>{{ translate('Invoice Number') }}</option>
                                        <option value="product_sku" @if($sortBy=='product_sku') selected @endif>{{ translate('Product SKU') }}</option>
                                        <option value="sales_man_name" @if($sortBy=='sales_man_name') selected @endif>{{ translate('Salesman Name') }}</option>
                                        <option value="final_amount" @if($sortBy=='final_amount') selected @endif>{{ translate('Final Amount') }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label class="form-label" for="sort_dir">{{ translate('Direction') }}</label>
                                    <select class="form-control aiz-selectpicker" id="sort_dir" name="sort_dir">
                                        <option value="asc" @if($sortDir=='asc') selected @endif>{{ translate('Ascending') }}</option>
                                        <option value="desc" @if($sortDir=='desc') selected @endif>{{ translate('Descending') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ translate('Apply Filters') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table mb-0 table-bordered purchase-history-sheet">
                    <thead>
                    <tr>
                        <th style="width: 55px">{{ translate('Sr.No') }}</th>
                        <th style="width: 125px">{{ translate('Ac.No') }}<br>{{ translate('Name') }}</th>
                        <th style="width: 210px">{{ translate('Party Name') }}<br>{{ translate('Area, Town') }}<br>{{ translate('District') }}</th>
                        <th style="width: 135px">{{ translate('State') }}<br>{{ translate('Pincode') }}<br>{{ translate('Country') }}</th>
                        <th style="width: 135px">{{ translate('Order Date') }}<br>{{ translate('Order.No') }}<br>{{ translate('SalesMan') }}</th>
                        <th style="width: 120px">{{ translate('Date') }}<br>{{ translate('Series') }}<br>{{ translate('Bill') }}</th>
                        <th style="width: 220px">{{ translate('SKU') }}<br>{{ translate('Product') }}<br>{{ translate('Pack Size') }}</th>
                        <th style="width: 145px">{{ translate('Batch') }}<br>{{ translate('Expiry') }}<br>{{ translate('Mfd By') }}</th>
                        <th style="width: 75px">{{ translate('Qty') }}<br>{{ translate('Free') }}<br>{{ translate('Total') }}</th>
                        <th style="width: 90px">{{ translate('Sale Rate') }}<br>{{ translate('Disc%') }}<br>{{ translate('MRP') }}</th>
                        <th style="width: 95px">{{ translate('Taxable') }}<br>{{ translate('GST') }}<br>{{ translate('Total') }}</th>
                        <th style="width: 80px">{{ translate('Tax Code') }}<br>{{ translate('GST%') }}</th>
                        <th style="width: 160px">{{ translate('Transport') }}<br>{{ translate('Booked To') }}<br>{{ translate('Case') }}</th>
                        <th style="width: 125px">{{ translate('L.R.No') }}<br>{{ translate('LR Date') }}<br>{{ translate('Late By') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($purchaseHistory as $history)
                        @php
                            $customer = $history->customerDetails;
                            $product = $history->productStock?->product;
                            $quantity = (float) str_replace(',', '', (string) ($history->quantity ?? 0));
                            $free = (float) str_replace(',', '', (string) ($history->free ?? 0));
                        @endphp
                        <tr>
                            <td>{{ $purchaseHistory->firstItem() + $loop->index }}</td>
                            <td class="cell-lines">
                                <span>{{ $customer?->crm_id ?? $history->ac_number }}</span>
                                <span>{{ $customer?->user?->name }}</span>
                            </td>
                            <td class="cell-lines party-cell">
                                <span>{{ $customer?->company_name }}</span>
                                <span>{{ collect([$customer?->post_business, $customer?->businessCity?->name])->filter()->implode(', ') }}</span>
                                <span>{{ $customer?->district_business }}</span>
                            </td>
                            <td class="cell-lines">
                                <span>{{ $customer?->businessState?->name }}</span>
                                <span>{{ $customer?->pincode_business }}</span>
                                <span>{{ $customer?->businessCountry?->name }}</span>
                            </td>
                            <td class="cell-lines"><span>{{ $history->order_date }}</span><span>{{ $history->order_number }}</span><span>{{ $history->sales_man_code ?: $history->sales_man_name }}</span></td>
                            <td class="cell-lines"><span>{{ $history->invoice_date }}</span><span>{{ $history->invoice_series }}</span><span>{{ $history->invoice_number }}</span></td>
                            <td class="cell-lines"><span>{{ $history->product_sku }}</span><span>{{ $product?->name }}</span><span>{{ $history->packing }}</span></td>
                            <td class="cell-lines"><span>{{ $history->batch_number }}</span><span>{{ $history->expiry_date }}</span><span>{{ $product?->brand?->name }}</span></td>
                            <td class="cell-lines"><span>{{ $history->quantity }}</span><span>{{ $history->free }}</span><span>{{ $quantity + $free }}</span></td>
                            <td class="cell-lines"><span>{{ $history->sale_rate }}</span><span>{{ $history->discount }}</span><span>{{ $history->mrp_rate }}</span></td>
                            <td class="cell-lines"><span>{{ $history->taxable_amount }}</span><span>{{ $history->gst_amount }}</span><span>{{ $history->final_amount }}</span></td>
                            <td class="cell-lines"><span>{{ $history->tax_code }}</span><span>{{ $history->gst_percentage }}</span></td>
                            <td class="cell-lines"><span>{{ $history->transport }}</span><span>{{ $history->book_to }}</span><span>{{ $history->case_value }}</span></td>
                            <td class="cell-lines">
                                <span>
                                    <a href="javascript:void(0);" onclick="show_purchase_history_detail('{{ route('admin.purchase_history.show', $history->id) }}')" title="{{ translate('View Details') }}">
                                        {{ $history->lr_number }}
                                    </a>
                                </span>
                                <span>{{ $history->lr_date }}</span>
                                <span>{{ $history->late_by }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="100" class="text-center">{{ translate('No records found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="aiz-pagination mt-3">
                {{ $purchaseHistory->links() }}
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchase-history-detail-modal" tabindex="-1" role="dialog" aria-labelledby="purchaseHistoryDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ translate('Purchase History Details') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="purchase-history-detail-body"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchase-history-import-modal" tabindex="-1" role="dialog" aria-labelledby="purchaseHistoryImportLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseHistoryImportLabel">{{ translate('Import party wise sheets') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.purchase_history.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">
                            {{ translate('Upload a CSV or Excel file with a header row matching the party wise sheet columns.') }}
                        </p>
                        <div class="form-group">
                            <label class="mb-1">{{ translate('File (CSV, XLSX, XLS)') }}</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" name="file" id="purchase_history_import_file_modal" required>
                                <label class="custom-file-label" for="purchase_history_import_file_modal">{{ translate('Choose file') }}</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ translate('Import') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="purchase-history-delete-modal" tabindex="-1" role="dialog" aria-labelledby="purchaseHistoryDeleteLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="purchaseHistoryDeleteLabel">{{ translate('Delete Purchase History Record') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ translate('Close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="purchase-history-delete-form" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-body">
                        <p class="mb-3">
                            {{ translate('Are you sure you want to delete this record? This action cannot be undone.') }}
                        </p>
                        <ul class="list-unstyled mb-0 small">
                            <li><strong>{{ translate('Serial') }}:</strong> <span id="ph-delete-serial">-</span></li>
                            <li><strong>{{ translate('Order') }}:</strong> <span id="ph-delete-order">-</span></li>
                            <li><strong>{{ translate('Invoice') }}:</strong> <span id="ph-delete-invoice">-</span></li>
                            <li><strong>{{ translate('SKU') }}:</strong> <span id="ph-delete-sku">-</span></li>
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                            {{ translate('Cancel') }}
                        </button>
                        <button type="submit" class="btn btn-danger">
                            {{ translate('Delete') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function show_purchase_history_detail(url) {
            $('#purchase-history-detail-body').html('');
            $('#purchase-history-detail-modal').modal('show');
            $.get(url, function (html) {
                $('#purchase-history-detail-body').html(html);
            }).fail(function () {
                $('#purchase-history-detail-body').html('<div class="alert alert-danger mb-0">{{ translate('Failed to load details.') }}</div>');
            });
        }

        function confirm_delete_purchase_history(url, serial, orderNo, invoiceNo, sku) {
            $('#purchase-history-delete-form').attr('action', url);
            $('#ph-delete-serial').text(serial || '-');
            $('#ph-delete-order').text(orderNo || '-');
            $('#ph-delete-invoice').text(invoiceNo || '-');
            $('#ph-delete-sku').text(sku || '-');
            $('#purchase-history-delete-modal').modal('show');
        }

        $(document).on('change', '.aiz-date-range', function () {
            var val = $(this).val();
            var fromField = $('#order_date_from');
            var toField = $('#order_date_to');
            fromField.val('');
            toField.val('');
            if (val && val.indexOf(' to ') !== -1) {
                var parts = val.split(' to ');
                fromField.val(parts[0]);
                toField.val(parts[1]);
            }
        });

        // Update custom-file label text when a file is selected (import modal)
        $(document).on('change', '#purchase_history_import_file_modal', function () {
            var fileName = $(this).val().split('\\').pop();
            if (fileName) {
                $(this).siblings('.custom-file-label').addClass('selected').text(fileName);
            }
        });
    </script>
@endsection

