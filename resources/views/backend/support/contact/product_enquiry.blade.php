@extends('backend.layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 h6">{{ translate('Product Enquiries') }}</h5>
        </div>
        <form id="sort_warranties" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Product Enquiries') }}</h5>
                </div>
    
                <input type="hidden" name="type" value="product">
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="date_from" class="form-label">{{ translate('Search') }}</label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search" value="{{ request('search') }}" placeholder="{{ translate('Type & Enter') }}">
                    </div>
                </div>
                <!-- From Date -->
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="date_from" class="form-label">{{ translate('From Date') }}</label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="{{ request('date_from') }}">
                    </div>
                </div>
    
                <!-- To Date -->
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <label for="date_from" class="form-label">{{ translate('To Date') }}</label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mt-5 d-flex">
                        <button type="submit" class="btn btn-primary me-2 mx-1">
                            <i class="las la-search"></i> {{ translate('Search') }}
                        </button>
                        <a href="{{ url()->current() }}?type=product" class="btn btn-secondary">
                            <i class="las la-sync"></i> {{ translate('Reset') }}
                        </a>
                    </div>
                </div>
            </div>
        </form>
        <div class="card-body">
            <table class="table aiz-table mb-0 " cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ translate('Product Name') }}</th>
                        <th>{{ translate('Name') }}</th>
                        <th >{{ translate('Email') }}</th>
                        <th data-breakpoints="lg">{{ translate('Phone') }}</th>
                        <th data-breakpoints="lg">{{ translate('Message') }}</th>
                        {{-- <th data-breakpoints="lg">{{ translate('Pincode') }}</th> --}}
                        <th data-breakpoints="lg">{{ translate('Url') }}</th>
                        <th data-breakpoints="lg">{{ translate('Date') }}</th>
                        {{-- <th data-breakpoints="lg">{{ translate('Reply') }}</th> --}}
                        {{-- <th>{{ translate('status') }}</th> --}}
                        <th class="text-right">{{ translate('Options') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contacts as $key => $contact)
                        <tr>
                            <td>{{ translate($key + 1) }}</td>
                            <td>{{ $contact->product->name }}</td>
                            <td>{{ $contact->name }}</td>
                            <td>{{ $contact->email }}</td>
                            <td>{{ $contact->phone }}</td>
                            <td>{{ Str::limit($contact->content, 100) }}</td>
                            {{-- <td>{{ $contact->pincode }}</td> --}}
                            <td>
                                <a href="{{ $contact->url }}" target="_blank">
                                    Page Visit
                                </a>
                            </td>
                            <td>{{ date('d-m-Y', strtotime($contact->created_at)) }}</td>
                            {{-- <td>{{ Str::limit($contact->reply, 100) }}</td> --}}
                            {{-- <td>
                                <span
                                    class="badge badge-inline {{ $contact->reply == null ? 'badge-warning' : 'badge-success'  }}">
                                    {{ $contact->reply == null ? translate('Not Replied') : translate('Replied')}}
                                </span>
                            </td> --}}
                            <td class="text-right">
                                <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                    href="javascript:void(1)" onclick="showQuery({{ $contact->id }})"
                                    title="{{ translate('View') }}">
                                    <i class="las la-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $contacts->appends(request()->input())->links() }}
            </div>
        </div>
    </div>
@endsection

@section('modal')
    @include('modals.delete_modal')
    <!-- Query Modal -->
	<div class="modal fade" id="query_modal">
	    <div class="modal-dialog">
	        <div class="modal-content" id="query-modal-content">

	        </div>
	    </div>
	</div>
    <!-- Reply Modal -->
    <div class="modal fade" id="reply_modal">
        <div class="modal-dialog">
            <div class="modal-content" id="reply-modal-content">

            </div>
        </div>
    </div>
@endsection
@section('script')
    <script type="text/javascript">
        function showQuery(id){
            $.post("{{ route('contact.query_modal') }}",{_token:'{{ @csrf_token() }}', id:id}, function(data){
                $('#query_modal #query-modal-content').html(data);
                $('#query_modal').modal('show');
            });
        }
        function showReplyModal(id){
            $.post("{{ route('contact.reply_modal') }}",{_token:'{{ @csrf_token() }}', id:id}, function(data){
                $('#reply_modal #reply-modal-content').html(data);
                $('#reply_modal').modal('show');
                $('#query_modal').modal('hide');
            });
        }
    </script>
@endsection
