@extends('frontend.layouts.user_panel')

@section('panel_content')
<style>
    .coming-soon-wrap {
        min-height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        padding: 60px 15px;
    }
    .coming-card {
        max-width: 640px;
        width: 100%;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        box-shadow: 0 20px 50px rgba(31, 41, 55, 0.08);
        padding: 32px;
        text-align: center;
    }
    .coming-icon {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: #eef2ff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #6366f1;
        font-size: 42px;
        margin-bottom: 16px;
    }
    .coming-title {
        font-size: 28px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 10px;
    }
    .coming-text {
        color: #4b5563;
        font-size: 16px;
        margin-bottom: 0;
    }
</style>

<section class="coming-soon-wrap">
    <div class="coming-card">
        <div class="coming-icon">
            <i class="las la-hourglass-half"></i>
        </div>
        <h1 class="coming-title">{{ translate('Coming Soon') }}</h1>
        <p class="coming-text">
            {{ translate('We are putting the final touches on this page. Please check back shortly.') }}
        </p>
    </div>
</section>
@endsection
