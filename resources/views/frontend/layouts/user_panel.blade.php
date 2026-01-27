@extends('frontend.layouts.app')
@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-md-flex align-items-start">
			@include('frontend.inc.user_side_nav')
			<div class="aiz-user-panel">
				@yield('panel_content')
            </div>
        </div>
    </div>
</section>
@endsection
@yield('style')