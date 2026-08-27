@props(['paginator', 'routeName', 'pagedRouteName', 'routeParams' => []])

@php
    $pageUrl = fn (int $page) => $page === 1
        ? route($routeName, $routeParams)
        : route($pagedRouteName, [...$routeParams, 'page' => $page]);
@endphp

@if ($paginator->lastPage() > 1)
    <nav class="d-flex justify-content-center" aria-label="Pagination">
        <ul class="pagination">
            <li class="page-item @if ($paginator->onFirstPage()) disabled @endif">
                @if ($paginator->onFirstPage())
                    <span class="page-link">Previous</span>
                @else
                    <a class="page-link" href="{{ $pageUrl($paginator->currentPage() - 1) }}">Previous</a>
                @endif
            </li>

            @for ($i = 1; $i <= $paginator->lastPage(); $i++)
                <li class="page-item @if ($i === $paginator->currentPage()) active @endif">
                    <a class="page-link" href="{{ $pageUrl($i) }}">{{ $i }}</a>
                </li>
            @endfor

            <li class="page-item @if (! $paginator->hasMorePages()) disabled @endif">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $pageUrl($paginator->currentPage() + 1) }}">Next</a>
                @else
                    <span class="page-link">Next</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
