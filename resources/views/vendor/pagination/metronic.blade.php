@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last    = $paginator->lastPage();

        // 1 2 3 4 5 … 13  |  1 … 6 7 8 … 13  |  1 … 9 10 11 12 13
        if ($last <= 7) {
            $pages = range(1, $last);
        } elseif ($current <= 4) {
            $pages = [1, 2, 3, 4, 5, '...', $last];
        } elseif ($current >= $last - 3) {
            $pages = [1, '...', $last - 4, $last - 3, $last - 2, $last - 1, $last];
        } else {
            $pages = [1, '...', $current - 1, $current, $current + 1, '...', $last];
        }
    @endphp

    <nav aria-label="Navigasi halaman">
        <ul class="pagination mb-0">
            {{-- Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya">&lsaquo;</a>
                </li>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($pages as $page)
                @if ($page === '...')
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link">&hellip;</span></li>
                @elseif ($page == $current)
                    <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
                @endif
            @endforeach

            {{-- Berikutnya --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif