@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 w-full pt-3">
        {{-- Left Footer: Per Page Selector & Data Counter Info --}}
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-medium select-none">
            {{-- Page Size Selector --}}
            <div class="flex items-center gap-1.5">
                <span>Show</span>
                <select onchange="location = this.value;" class="px-2.5 py-1 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600 transition-all cursor-pointer shadow-2xs">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ request()->fullUrlWithQuery(['per_page' => $size, 'page' => 1]) }}" {{ request('per_page', 10) == $size ? 'selected' : '' }}>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
                <span>entries per page</span>
            </div>

            <span class="text-slate-300">&vert;</span>

            {{-- Counter Text --}}
            <div>
                <span>Showing</span>
                <span class="font-bold text-slate-900">{{ $paginator->firstItem() ?? 0 }}</span>
                <span>to</span>
                <span class="font-bold text-slate-900">{{ $paginator->lastItem() ?? 0 }}</span>
                <span>of</span>
                <span class="font-bold text-slate-900">{{ $paginator->total() }}</span>
                <span>entries</span>
            </div>
        </div>

        {{-- Right Footer: Complete Pagination Controls --}}
        @if ($paginator->hasPages())
            <div class="inline-flex items-center gap-1 bg-white p-1 rounded-xl border border-slate-200 shadow-2xs shrink-0">
                {{-- First Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed select-none" title="First Page">
                        <x-heroicon-o-chevron-double-left class="w-4 h-4" />
                    </span>
                @else
                    <a href="{{ $paginator->url(1) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors" title="First Page">
                        <x-heroicon-o-chevron-double-left class="w-4 h-4" />
                    </a>
                @endif

                {{-- Previous Page Link --}}
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed select-none" title="Previous Page">
                        <x-heroicon-o-chevron-left class="w-4 h-4" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors" title="Previous Page">
                        <x-heroicon-o-chevron-left class="w-4 h-4" />
                    </a>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="inline-flex items-center justify-center w-8 h-8 text-slate-400 select-none text-xs font-semibold">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-[#0B2545] text-white font-bold text-xs shadow-2xs select-none">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors text-xs font-semibold">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors" title="Next Page">
                        <x-heroicon-o-chevron-right class="w-4 h-4" />
                    </a>
                @else
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed select-none" title="Next Page">
                        <x-heroicon-o-chevron-right class="w-4 h-4" />
                    </span>
                @endif

                {{-- Last Page Link --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->url($paginator->lastPage()) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-colors" title="Last Page">
                        <x-heroicon-o-chevron-double-right class="w-4 h-4" />
                    </a>
                @else
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 cursor-not-allowed select-none" title="Last Page">
                        <x-heroicon-o-chevron-double-right class="w-4 h-4" />
                    </span>
                @endif
            </div>
        @endif
    </div>
@endif
