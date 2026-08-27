@extends('layouts.app')

@section('title', $category->name)

@section('content')
    <h1 class="h3 mb-1">{{ $category->name }}</h1>

    @if ($category->description)
        <p class="text-muted mb-4">{{ $category->description }}</p>
    @endif

    @if ($products->isEmpty())
        <p class="text-muted">No products in this category yet.</p>
    @else
        <div class="row g-3">
            @foreach ($products as $product)
                @include('shop.products._card', ['product' => $product])
            @endforeach
        </div>

        <div class="mt-4">
            @include('shop.partials._pagination', [
                'paginator' => $products,
                'routeName' => 'shop.categories.show',
                'pagedRouteName' => 'shop.categories.show.page',
                'routeParams' => ['category' => $category->slug],
            ])
        </div>
    @endif
@endsection
