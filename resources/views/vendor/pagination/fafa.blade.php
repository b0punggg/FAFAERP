@php
    $hasTotal = method_exists($paginator, 'total');
    $total = $hasTotal ? $paginator->total() : null;
    $first = $paginator->firstItem();
    $last = $paginator->lastItem();
@endphp

@if ($total === null || $total > 0)
    <div class="fafa-pagination">
        @if ($hasTotal && $first !== null && $last !== null)
            <p class="fafa-pagination-summary">
                Menampilkan
                <strong>{{ number_format($first, 0, ',', '.') }}</strong>–<strong>{{ number_format($last, 0, ',', '.') }}</strong>
                dari
                <strong>{{ number_format($total, 0, ',', '.') }}</strong>
                data
            </p>
        @endif

        @if ($paginator->hasPages())
            <nav class="fafa-pagination-nav" aria-label="Navigasi halaman">
                <ul class="pagination mb-0">
                    @if ($paginator->onFirstPage())
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link" aria-hidden="true"><i class="fa fa-chevron-left" aria-hidden="true"></i><span class="d-none d-sm-inline"> Sebelumnya</span></span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fa fa-chevron-left" aria-hidden="true"></i><span class="d-none d-sm-inline"> Sebelumnya</span></a>
                        </li>
                    @endif

                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    @if ($paginator->hasMorePages())
                        <li class="page-item">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"><span class="d-none d-sm-inline">Berikutnya </span><i class="fa fa-chevron-right" aria-hidden="true"></i></a>
                        </li>
                    @else
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link" aria-hidden="true"><span class="d-none d-sm-inline">Berikutnya </span><i class="fa fa-chevron-right" aria-hidden="true"></i></span>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
@endif
