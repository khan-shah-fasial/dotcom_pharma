@extends('frontend.layouts.app')

@section('content')
<style>
    .all-categories-page {
        padding: 40px 0;
        background: #f8f9fa;
        min-height: 70vh;
    }
    
    .main-categories-grid {
        display: flex;
        flex-direction: column;
        gap: 50px;
        margin-top: 30px;
    }
    
   
   
    .main-category-card.accordion-style.active {
        
        background: #faf5ff;
    }
    
    .main-category-card.always-expanded {
    
        background: #f8f9fa;
        width: 100%;
        max-width: 100%;
        margin: 0;
    }
    
    .main-category-card.always-expanded .subcategories-container {
        display: block;
    }
    
    .main-category-card.always-expanded .expand-icon {
        display: none;
    }
   
    .main-category-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .main-category-icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px;
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
    }
    
    .main-category-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .main-category-info {
        flex: 1;
    }
    
    .main-category-title {
        font-size: 24px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 5px 0;
    }
    
    .main-category-subtitle {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
    }
    
    .expand-icon {
        position: absolute;
        top: 30px;
        right: 30px;
        font-size: 20px;
        color: #6b7280;
        transition: transform 0.3s ease;
    }
    
    .main-category-card.active .expand-icon {
        transform: rotate(180deg);
    }
    
    .subcategories-container {
        display: none;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #e5e7eb;
    }
    
    .main-category-card.active .subcategories-container {
        display: block;
    }
    
    .subcategories-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        align-items: start;
    }
    
    .subcategory-card {
        background: #fff;
        border-radius: 8px;
        padding: 15px;
        border: 1px solid #e5e7eb;
        transition: all 0.2s ease;
        text-decoration: none;
        display: block;
        position: relative;
        align-self: start;
    }
    
    .subcategory-card:hover {
        background: #ffffff;
        border-color: #7c3aed;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }
    
    .subcategory-card.has-children {
        cursor: pointer;
    }
    
    .subcategory-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    
    .subcategory-main {
        display: flex;
        align-items: center;
        gap: 12px;
        flex: 1;
        min-width: 0;
    }
    
    .subcategory-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 6px;
        flex-shrink: 0;
    }
    
    .subcategory-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .subcategory-info {
        flex: 1;
    }
    
    .subcategory-title {
        font-size: 15px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .subcategory-count {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }
    
    .subcategory-expand-icon {
        font-size: 14px;
        color: #6b7280;
        transition: transform 0.3s ease;
        margin-left: 10px;
        flex-shrink: 0;
    }
    
    .subcategory-card.expanded .subcategory-expand-icon {
        transform: rotate(180deg);
    }
    
    .sub-subcategories-container {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        margin-top: 5px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        z-index: 100;
        padding: 10px;
        min-width: 100%;
        max-height: 300px;
        overflow-y: auto;
        overflow-x: hidden;
    }
    
    .subcategory-card.expanded .sub-subcategories-container {
        display: block;
    }
    
    .subcategory-card.expanded {
        z-index: 10;
        border-color: #7c3aed;
    }
    
    .subcategory-card.has-children {
        position: relative;
    }
    
    .sub-subcategories-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    /* Custom scrollbar for dropdown */
    .sub-subcategories-container::-webkit-scrollbar {
        width: 6px;
    }
    
    .sub-subcategories-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .sub-subcategories-container::-webkit-scrollbar-thumb {
        background: #7c3aed;
        border-radius: 10px;
    }
    
    .sub-subcategories-container::-webkit-scrollbar-thumb:hover {
        background: #6d28d9;
    }
    
    .sub-subcategory-item {
        padding: 10px 15px;
        background: #ffffff;
        border-radius: 6px;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.2s ease;
        gap: 10px;
    }
    
    .sub-subcategory-main {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        flex: 1;
    }
    
    .sub-subcategory-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
        flex-shrink: 0;
    }
    
    .sub-subcategory-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .sub-subcategory-item:hover {
        border-color: #7c3aed;
        background: #f9fafb;
        text-decoration: none;
    }
    
    .sub-subcategory-name {
        font-size: 14px;
        font-weight: 500;
        color: #1f2937;
    }
    
    .sub-subcategory-count {
        font-size: 12px;
        color: #6b7280;
    }
    
    .breadcrumb-section {
        background: #f8f9fa;
    }
    
    .breadcrumb-section .breadcrumb {
        margin-bottom: 0;
        font-size: 13px;
    }
    
    .breadcrumb-section .breadcrumb-item {
        font-size: 13px;
    }
    
    .breadcrumb-section .breadcrumb-item a {
        font-size: 13px;
    }
    
    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 10px;
    }
</style>

<!-- Breadcrumb -->
<section class="breadcrumb-section">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('home') }}" class="text-reset">{{ translate('Home') }}</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    {{ translate('All Categories') }}
                </li>
            </ol>
        </nav>
    </div>
</section>

<!-- All Categories Page -->
<section class="all-categories-page">
    <div class="container">
        <h1 class="page-title">{{ translate('All Categories') }}</h1>
        
        <div class="main-categories-grid">
            @foreach($categoriesData as $categoryData)
                @php
                    $mainCategory = $categoryData['category'];
                    $categoryName = strtolower($mainCategory->getTranslation('name'));
                    $isVeterinaryOrHuman = (stripos($categoryName, 'veterinary') !== false || stripos($categoryName, 'human') !== false);
                    $mainCategoryIcon = isset($mainCategory->catIcon->file_name)
                        ? my_asset($mainCategory->catIcon->file_name)
                        : static_asset('assets/img/placeholder.jpg');
                @endphp
                
                <div class="main-category-card {{ $isVeterinaryOrHuman ? 'always-expanded' : 'accordion-style' }}" 
                     data-category-id="{{ $mainCategory->id }}">
                    <div class="main-category-header">
                        <div class="main-category-icon">
                            <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                 data-src="{{ $mainCategoryIcon }}"
                                 alt="{{ $mainCategory->getTranslation('name') }}"
                                 class="lazyload"
                                 onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                        </div>
                        <div class="main-category-info">
                            <h2 class="main-category-title">{{ $mainCategory->getTranslation('name') }}</h2>
                            <p class="main-category-subtitle">
                                {{ $categoryData['subcategory_count'] }} {{ translate('subcategories') }} · {{ $categoryData['total_products'] }} {{ translate('products') }}
                            </p>
                        </div>
                    </div>
                    @if(!$isVeterinaryOrHuman)
                        <i class="las la-chevron-down expand-icon"></i>
                    @endif
                    
                    <div class="subcategories-container {{ $isVeterinaryOrHuman ? '' : '' }}">
                        <div class="subcategories-grid">
                            @foreach($mainCategory->childrenCategories as $subcategory)
                                @php
                                    $hasSubSubcategories = $subcategory->childrenCategories->count() > 0;
                                    $subcategoryIcon = isset($subcategory->catIcon->file_name)
                                        ? my_asset($subcategory->catIcon->file_name)
                                        : static_asset('assets/img/placeholder.jpg');
                                @endphp
                                
                                @if($hasSubSubcategories)
                                    <div class="subcategory-card has-children" data-subcategory-id="{{ $subcategory->id }}">
                                        <div class="subcategory-header">
                                            <div class="subcategory-main">
                                                <div class="subcategory-icon">
                                                    <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                         data-src="{{ $subcategoryIcon }}"
                                                         alt="{{ $subcategory->getTranslation('name') }}"
                                                         class="lazyload"
                                                         onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                </div>
                                                <div class="subcategory-info">
                                                    <h3 class="subcategory-title">{{ $subcategory->getTranslation('name') }}</h3>
                                                    <p class="subcategory-count">{{ translate('Products') }} ({{ $subcategory->product_count ?? 0 }})</p>
                                                </div>
                                            </div>
                                            <i class="las la-chevron-down subcategory-expand-icon"></i>
                                        </div>
                                        
                                        <div class="sub-subcategories-container">
                                            <div class="sub-subcategories-list">
                                                @foreach($subcategory->childrenCategories as $subSubcategory)
                                                    @php
                                                        $subSubcategoryIcon = isset($subSubcategory->catIcon->file_name)
                                                            ? my_asset($subSubcategory->catIcon->file_name)
                                                            : static_asset('assets/img/placeholder.jpg');
                                                    @endphp
                                                    <a href="{{ route('products.category', $subSubcategory->slug) }}" class="sub-subcategory-item">
                                                        <span class="sub-subcategory-main">
                                                            <span class="sub-subcategory-icon">
                                                                <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                                     data-src="{{ $subSubcategoryIcon }}"
                                                                     alt="{{ $subSubcategory->getTranslation('name') }}"
                                                                     class="lazyload"
                                                                     onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                            </span>
                                                            <span class="sub-subcategory-name">{{ $subSubcategory->getTranslation('name') }}</span>
                                                        </span>
                                                        <span class="sub-subcategory-count">({{ $subSubcategory->product_count ?? 0 }})</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <a href="{{ route('products.category', $subcategory->slug) }}" class="subcategory-card">
                                        <div class="subcategory-header">
                                            <div class="subcategory-main">
                                                <div class="subcategory-icon">
                                                    <img src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                         data-src="{{ $subcategoryIcon }}"
                                                         alt="{{ $subcategory->getTranslation('name') }}"
                                                         class="lazyload"
                                                         onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                </div>
                                                <div class="subcategory-info">
                                                    <h3 class="subcategory-title">{{ $subcategory->getTranslation('name') }}</h3>
                                                    <p class="subcategory-count">{{ translate('Products') }} ({{ $subcategory->product_count ?? 0 }})</p>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle main category card clicks - Only for accordion-style cards (not Veterinary/Human)
        const accordionCards = document.querySelectorAll('.main-category-card.accordion-style');
        
        accordionCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                // Don't toggle if clicking on subcategory or sub-subcategory
                if (e.target.closest('.subcategory-card') || e.target.closest('.sub-subcategory-item')) {
                    return;
                }
                
                // Toggle active class independently (accordion style - multiple can be open)
                this.classList.toggle('active');
            });
        });
        
        // Handle subcategory card clicks (for expanding sub-subcategories)
        const subcategoryCards = document.querySelectorAll('.subcategory-card.has-children');
        
        subcategoryCards.forEach(function(card) {
            card.addEventListener('click', function(e) {
                // Don't toggle if clicking on sub-subcategory link
                if (e.target.closest('.sub-subcategory-item')) {
                    return;
                }
                
                // Prevent event from bubbling to main category card
                e.stopPropagation();
                
                // Toggle expanded class
                this.classList.toggle('expanded');
            });
        });
    });
</script>
@endsection
