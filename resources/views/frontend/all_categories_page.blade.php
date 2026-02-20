@extends('frontend.layouts.app')

@section('content')

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
                
                // Close all other expanded subcategory cards
                subcategoryCards.forEach(function(otherCard) {
                    if (otherCard !== card) {
                        otherCard.classList.remove('expanded');
                    }
                });
                
                // Toggle expanded class for current card
                this.classList.toggle('expanded');
            });
        });
    });
</script>
@endsection
