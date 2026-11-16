@props(['paginator' => null])

@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator)
    {!! $paginator->withQueryString()->links() !!}
@endif
