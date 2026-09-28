@php
    // Hitung total permission di modul ini + semua turunannya (untuk badge jumlah).
    $countPermissions = function ($mod) use (&$countPermissions) {
        $total = $mod->permissions->count();
        foreach ($mod->children as $child) {
            $total += $countPermissions($child);
        }
        return $total;
    };
    $totalCount = $countPermissions($module);

    $isNested = $isNested ?? false;

    // $accent HARUS salah satu warna semantik Bootstrap/Metronic (primary, warning, success,
    // info, danger, dst) supaya class bg-light-{accent} / text-{accent} / badge-light-{accent}
    // di bawah ini valid dan otomatis mengikuti tema terang/gelap Metronic.
    $accent = $accent ?? 'primary';
    $subAccentPalette = ['primary', 'warning', 'success', 'info', 'danger'];
@endphp

@if ($isNested)
    {{-- ===== Sub-modul (bersarang) — box terpisah di dalam card modul induk ===== --}}
    <div class="pg-subgroup bg-light border border-gray-300 rounded-3" data-permission-group>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pg-subgroup-header pb-4 mb-4 border-bottom border-gray-300">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <span class="badge badge-light-{{ $accent }} text-uppercase fw-bold pg-subgroup-badge">{{ $module->name }}</span>
                <span class="text-muted pg-subgroup-count">{{ $totalCount }} {{ $totalCount === 1 ? 'permission' : 'permissions' }}</span>
            </div>
            <label class="d-flex align-items-center gap-3 mb-0">
                <span class="text-muted pg-switch-label-sm">Pilih Semua</span>
                <div class="form-check form-switch form-check-custom form-check-solid pg-switch-sm">
                    <input type="checkbox" class="form-check-input select-all-toggle">
                </div>
            </label>
        </div>

        <div class="d-flex flex-column">
            @foreach ($module->permissions as $permission)
                @include('roles.partials.permission-row', [
                    'permission'      => $permission,
                    'moduleName'      => $module->name,
                    'rolePermissions' => $rolePermissions,
                    'rowClass'        => 'pg-perm-row' . ($loop->first ? '' : ' border-top border-gray-300'),
                ])
            @endforeach
        </div>

        @if ($module->children->isNotEmpty())
            <div class="pg-subgroup-list mt-5">
                @foreach ($module->children as $child)
                    @include('roles.partials.permission-group', [
                        'module'          => $child,
                        'rolePermissions' => $rolePermissions,
                        'isNested'        => true,
                        'accent'          => $subAccentPalette[$loop->index % count($subAccentPalette)],
                    ])
                @endforeach
            </div>
        @endif
    </div>
@else
    {{-- ===== Modul level atas — card standar Metronic (otomatis ikut tema) ===== --}}
    <div class="card pg-module-card shadow-sm" data-permission-group>
        <div class="card-header pg-module-card-header d-flex align-items-center justify-content-between flex-wrap gap-4">
            <div class="d-flex align-items-center gap-4">
                @if ($module->icon)
                    <div class="pg-icon-badge badge-light-{{ $accent }}">
                        <i class="ki-outline {{ $module->icon }}"></i>
                    </div>
                @endif
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="pg-module-title fw-bold text-gray-900 mb-0">{{ $module->name }}</h2>
                        <span class="badge badge-light-{{ $accent }} pg-module-badge-count">
                            {{ $totalCount }} {{ $totalCount === 1 ? 'permission' : 'permissions' }}
                        </span>
                    </div>
                </div>
            </div>
            <label class="d-flex align-items-center gap-3 mb-0">
                <span class="text-muted pg-switch-label">Pilih Semua</span>
                <div class="form-check form-switch form-check-custom form-check-solid pg-switch-lg">
                    <input type="checkbox" class="form-check-input select-all-toggle">
                </div>
            </label>
        </div>

        <div class="card-body pg-module-card-body">
            @foreach ($module->permissions as $permission)
                @include('roles.partials.permission-row', [
                    'permission'      => $permission,
                    'moduleName'      => $module->name,
                    'rolePermissions' => $rolePermissions,
                    'rowClass'        => 'pg-perm-row' . (! $loop->last || $module->children->isNotEmpty() ? ' border-bottom border-gray-200' : ''),
                ])
            @endforeach

            @if ($module->children->isNotEmpty())
                <div class="pg-subgroup-list {{ $module->permissions->isNotEmpty() ? 'pt-5' : '' }}">
                    @foreach ($module->children as $child)
                        @include('roles.partials.permission-group', [
                            'module'          => $child,
                            'rolePermissions' => $rolePermissions,
                            'isNested'        => true,
                            'accent'          => $subAccentPalette[$loop->index % count($subAccentPalette)],
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif