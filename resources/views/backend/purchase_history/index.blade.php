@extends('backend.layouts.app')

@section('content')
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
        <div class="card-header border-0 pb-0">
            <div class="w-100">
                <form action="{{ route('admin.purchase_history.index') }}" method="GET">
                    <div class="row gutters-10">
                        <div class="col-md-4 mb-3">
                            <label class="mb-1 text-muted text-uppercase fs-10">{{ translate('Global search') }}</label>
                            <input type="text" class="form-control" name="search" value="{{ $search }}"
                                   placeholder="{{ translate('Serial / Order / Invoice / SKU / Salesman / State / City') }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="mb-1 text-muted text-uppercase fs-10">{{ translate('Product SKU') }}</label>
                            <input type="text" class="form-control" name="product_sku" value="{{ request('product_sku') }}"
                                   placeholder="{{ translate('SKU') }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="mb-1 text-muted text-uppercase fs-10">{{ translate('Salesman Name') }}</label>
                            <input type="text" class="form-control" name="sales_man_name" value="{{ request('sales_man_name') }}"
                                   placeholder="{{ translate('Salesman') }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="mb-1 text-muted text-uppercase fs-10">{{ translate('Order date range') }}</label>
                            <input type="text" class="form-control aiz-date-range" name="order_date_range"
                                   value="{{ request('order_date_from') && request('order_date_to') ? request('order_date_from').' to '.request('order_date_to') : '' }}"
                                   data-time-picker="false" data-format="YYYY-MM-DD"
                                   placeholder="{{ translate('YYYY-MM-DD to YYYY-MM-DD') }}">
                            <input type="hidden" name="order_date_from" id="order_date_from" value="{{ request('order_date_from') }}">
                            <input type="hidden" name="order_date_to" id="order_date_to" value="{{ request('order_date_to') }}">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="mb-1 text-muted text-uppercase fs-10">{{ translate('Sort') }}</label>
                            <div class="d-flex">
                                <select class="form-control aiz-selectpicker mr-1" name="sort_by">
                                    <option value="order_date" @if($sortBy=='order_date') selected @endif>{{ translate('Order Date') }}</option>
                                    <option value="serial_number" @if($sortBy=='serial_number') selected @endif>{{ translate('Serial Number') }}</option>
                                    <option value="order_number" @if($sortBy=='order_number') selected @endif>{{ translate('Order Number') }}</option>
                                    <option value="invoice_number" @if($sortBy=='invoice_number') selected @endif>{{ translate('Invoice Number') }}</option>
                                    <option value="product_sku" @if($sortBy=='product_sku') selected @endif>{{ translate('Product SKU') }}</option>
                                    <option value="sales_man_name" @if($sortBy=='sales_man_name') selected @endif>{{ translate('Salesman Name') }}</option>
                                    <option value="final_amount" @if($sortBy=='final_amount') selected @endif>{{ translate('Final Amount') }}</option>
                                </select>
                                <select class="form-control aiz-selectpicker" name="sort_dir">
                                    <option value="asc" @if($sortDir=='asc') selected @endif>{{ translate('Asc') }}</option>
                                    <option value="desc" @if($sortDir=='desc') selected @endif>{{ translate('Desc') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="las la-filter mr-1"></i>{{ translate('Apply Filters') }}
                        </button>
                        <div class="d-flex align-items-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary mr-2"
                                    data-toggle="modal"
                                    data-target="#purchase-history-import-modal">
                                <i class="las la-file-import mr-1"></i>{{ translate('Import party wise sheets') }}
                            </button>
                            <a href="{{ route('admin.purchase_history.export', request()->query()) }}"
                               class="btn btn-sm btn-outline-success">
                                <i class="las la-file-excel mr-1"></i>{{ translate('Export') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table aiz-table mb-0 table-bordered">
                    <thead>
                    <tr>
                        <th>{{ translate('Serial Number') }}</th>
                        <th>{{ translate('Order Date') }}</th>
                        <th>{{ translate('Order Number') }}</th>
                        <th>{{ translate('Invoice Number') }}</th>
                        <th>{{ translate('Product SKU') }}</th>
                        <th class="text-right">{{ translate('Quantity') }}</th>
                        <th class="text-right">{{ translate('Sale Rate') }}</th>
                        <th class="text-right">{{ translate('Final Amount') }}</th>
                        <th>{{ translate('Salesman Name') }}</th>
                        <th>{{ translate('State') }}</th>
                        <th>{{ translate('City') }}</th>
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($purchaseHistory as $history)
                        <tr>
                            <td>{{ $history->serial_number }}</td>
                            <td>{{ $history->order_date }}</td>
                            <td>{{ $history->order_number }}</td>
                            <td>{{ $history->invoice_number }}</td>
                            <td>{{ $history->product_sku }}</td>
                            <td class="text-right">{{ $history->quantity }}</td>
                            <td class="text-right">{{ $history->sale_rate }}</td>
                            <td class="text-right">{{ $history->final_amount }}</td>
                            <td>{{ $history->sales_man_name }}</td>
                            <td>{{ $history->state }}</td>
                            <td>{{ $history->city }}</td>
                            <td class="text-right">
                                <a href="javascript:void(0);"
                                   class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                   onclick="show_purchase_history_detail('{{ route('admin.purchase_history.show', $history->id) }}')"
                                   title="{{ translate('View Details') }}">
                                    <i class="las la-eye"></i>
                                </a>

                                {{-- <a href="{{ route('admin.purchase_history.edit', $history->id) }}"
                                   class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                   title="{{ translate('Edit') }}">
                                    <i class="las la-edit"></i>
                                </a> --}}

                                {{-- <button type="button"
                                        class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                        onclick="confirm_delete_purchase_history('{{ route('admin.purchase_history.destroy', $history->id) }}', '{{ $history->serial_number }}', '{{ $history->order_number }}', '{{ $history->invoice_number }}', '{{ $history->product_sku }}')"
                                        title="{{ translate('Delete') }}">
                                    <i class="las la-trash"></i>
                                </button> --}}
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

