{{--
    Satu baris permission (dipakai baik di dalam modul top-level maupun di dalam sub-grup nested).

    Props:
    - $permission       : object permission (punya ->action, ->name, ->desc)
    - $moduleName       : nama modul pemilik permission ini (untuk judul baris)
    - $rolePermissions  : array nama permission yang sudah dimiliki role (untuk state checked)
    - $rowClass         : class tambahan pada wrapper baris (spacing/border beda antar konteks)

    Semua warna di sini pakai utility class Metronic (text-gray-900, text-muted,
    badge-light-secondary, form-check-solid) supaya otomatis ikut mode terang/gelap.
--}}
<div class="{{ $rowClass ?? 'pg-perm-row' }} d-flex align-items-center justify-content-between gap-5">
    <div class="pg-perm-text">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <span class="pg-perm-title fw-semibold text-gray-900">{{ ucfirst($permission->action) }} {{ $moduleName }}</span>
            <span class="badge badge-light-secondary text-uppercase pg-perm-tag">{{ $permission->action }}</span>
        </div>
        <p class="pg-perm-desc text-muted mb-0">{{ $permission->desc }}</p>
    </div>

    <div class="form-check form-switch form-check-custom form-check-solid pg-switch-lg flex-shrink-0">
        <input type="checkbox"
               name="permissions[]"
               value="{{ $permission->name }}"
               class="form-check-input permission-switch"
               {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
    </div>
</div>