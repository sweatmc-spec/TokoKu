@php
    $isVisible = $module->isVisibleFor(auth()->user());
@endphp

@if ($isVisible)
    @if (is_null($module->parent_id) && $module->children->isNotEmpty())
        {{-- Heading label di atas grup top-level, kalau dia punya anak --}}
        <div class="menu-item pt-5">
            <div class="menu-content">
                <span class="menu-heading fw-bold text-uppercase fs-7">{{ $module->name }}</span>
            </div>
        </div>
    @endif

    @if ($module->children->isEmpty())
        {{-- Leaf module: link langsung --}}
        <div class="menu-item">
            <a class="menu-link {{ $module->active_pattern && request()->is($module->active_pattern) ? 'active' : '' }}"
               href="{{ $module->route ? route($module->route) : url(rtrim($module->active_pattern ?? $module->slug, '*')) }}">
                @if ($module->icon)
                    <span class="menu-icon"><i class="ki-outline {{ $module->icon }} fs-2"></i></span>
                @else
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                @endif
                <span class="menu-title">{{ $module->name }}</span>
            </a>
        </div>
    @else
        {{-- Modul dengan anak: accordion --}}
        <div data-kt-menu-trigger="click" class="menu-item here show menu-accordion">
            <span class="menu-link">
                @if ($module->icon)
                    <span class="menu-icon"><i class="ki-outline {{ $module->icon }} fs-2"></i></span>
                @else
                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                @endif
                <span class="menu-title">{{ $module->name }}</span>
                <span class="menu-arrow"></span>
            </span>
            <div class="menu-sub menu-sub-accordion">
                @foreach ($module->children as $child)
                    @include('partials.menu-item', ['module' => $child])
                @endforeach
            </div>
        </div>
    @endif
@endif