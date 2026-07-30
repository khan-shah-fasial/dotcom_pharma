@php
    $prefix = str_repeat('-', (int) $category->level);
@endphp

<li id="{{ $category->id }}">{{ $prefix }}{{ $category->getTranslation('name') }}</li>

@foreach ($category->childrenCategories as $childCategory)
    @include('backend.company.partials.category_item', ['category' => $childCategory])
@endforeach
