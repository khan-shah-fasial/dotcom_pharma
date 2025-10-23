@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class="row align-items-center">
        <div class="col-auto">
            <h1 class="h3">{{ translate('All User and Cart') }}</h1>
        </div>
    </div>
</div>
<br>

<div class="card">
    <form class="" id="sort_users" action="{{ route('list_user_and_cart') }}" method="GET">
        <div class="card-header row gutters-5">
            <div class="col">
                <h5 class="mb-md-0 h6">{{ translate('All User and Cart') }}</h5>
            </div>
            <div class="col-lg-2">
                <div class="form-group mb-0">
                    <input type="text" class="form-control" name="user_name" value="{{ request()->user_name }}" placeholder="{{ translate('Filter by user name') }}">
                </div>
            </div>
            <div class="col-lg-2">
                <div class="form-group mb-0">
                    <input type="text" class="aiz-date-range form-control" value="{{ request()->date }}" name="date" placeholder="{{ translate('Filter by date') }}" data-format="DD-MM-Y" data-separator=" to " data-advanced-range="true" autocomplete="off">
                </div>
            </div>
            <div class="col-auto">
                <div class="form-group mb-0">
                    <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                </div>
            </div>
            <div class="col-auto">
                <div class="form-group mb-0">
                    <a href="{{ route('list_user_and_cart') }}" class="btn btn-primary">{{ translate('Reset') }}</a>
                </div>
            </div>
        </div>
    
        <div class="card-body">
            <table class="table aiz-table mb-0">
                <thead>
                    <tr>
                        <th>{{ translate('Sr No.') }}</th>
                        <th>{{ translate('Name') }}</th>
                        <th>{{ translate('Email') }}</th>
                        <th>{{ translate('Phone') }}</th>
                        <th>{{ translate('Cart Details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($usersWithCartDetails as $index => $user)
                        <tr>
                            <td>{{ $usersWithCartDetails->firstItem() + $index }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone }}</td>
                            <td>
                                <ul>
                                    @foreach($user->carts as $cart)
                                        <li>
                                            <a href="{{ route('product', $cart->product->slug) }}" target="_blank" title="{{ translate('View') }}">{{ $cart->product->name }} </a> <b>x {{ $cart->quantity }}</b> <br>  Added At : {{ $cart->created_at->format('Y-m-d') }}
                                        </li>
                                        <br>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="aiz-pagination">
                {{ $usersWithCartDetails->appends(request()->input())->links() }}
            </div>
        </div>
    </form>
</div>

@endsection

@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                $('.check-one:checkbox').each(function() {
                    this.checked = true;                        
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;                       
                });
            }
        });

        function sort_users(el) {
            $('#sort_users').submit();
        }
    </script>
@endsection
