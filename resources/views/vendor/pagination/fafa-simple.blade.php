@if ($paginator->hasPages())
    <div class="fafa-pagination">
        <p class="fafa-pagination-summary fafa-pagination-summary--simple">Halaman {{ $paginator->currentPage() }}</p>
        <nav class="fafa-pagination-nav" aria-label="Navigasi halaman">
            <ul class="pagination mb-0">
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link"><i class="fa fa-chevron-left"></i><span class="d-none d-sm-inline"> Sebelumnya</span></span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev"><i class="fa fa-chevron-left"></i><span class="d-none d-sm-inline"> Sebelumnya</span></a>
                    </li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next"><span class="d-none d-sm-inline">Berikutnya </span><i class="fa fa-chevron-right"></i></a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link"><span class="d-none d-sm-inline">Berikutnya </span><i class="fa fa-chevron-right"></i></span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
@endif
