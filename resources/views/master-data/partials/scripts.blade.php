{{-- Script bersama: submit modal via AJAX, loading spinner, disable tombol, tampilkan error validasi --}}
<script>
$(function () {

    const spinner = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>';

    function setLoading($form, isLoading) {
        const $btn = $form.find('button[type="submit"]');

        if (isLoading) {
            if (!$btn.data('original')) $btn.data('original', $btn.html());
            $btn.prop('disabled', true)
                .html(spinner + ($btn.data('loading-text') || 'Memproses...'));
        } else {
            $btn.prop('disabled', false).html($btn.data('original'));
        }

        // Tombol Batal / X ikut dikunci selama request berjalan
        $form.closest('.modal').find('[data-bs-dismiss="modal"]').prop('disabled', isLoading);
    }

    function clearErrors($form) {
        $form.find('[data-error-for]').text('');
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.form-alert').addClass('d-none').text('');
    }

    $(document).on('submit', 'form.ajax-form', function (e) {
        e.preventDefault();

        const $form = $(this);
        if ($form.data('submitting')) return;   // cegah klik ganda

        $form.data('submitting', true);
        setLoading($form, true);
        clearErrors($form);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',                     // _method (PUT/DELETE) ikut di serialize()
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                // Tombol tetap terkunci sampai halaman pindah/reload (pesan sukses dari session flash)
                const $btn = $form.find('button[type="submit"]');
                $btn.html(spinner + 'Berhasil, memuat ulang...');

                if (res && res.redirect) {
                    window.location.href = res.redirect;   // mis. setelah hapus dari halaman detail
                } else {
                    window.location.reload();
                }
            },
            error: function (xhr) {
                $form.data('submitting', false);
                setLoading($form, false);

                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, messages) {
                        const field = key.split('.')[0];            // category_ids.0 -> category_ids
                        const $msg  = $form.find('[data-error-for="' + field + '"]');
                        if (!$msg.text()) $msg.text(messages[0]);
                        $form.find('[name="' + field + '"], [name="' + field + '[]"]').addClass('is-invalid');
                    });
                    return;
                }

                const message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Terjadi kesalahan. Silakan coba lagi.';
                $form.find('.form-alert').removeClass('d-none').text(message);
            }
        });
    });

    // Modal hapus: isi nama & action dari tombol yang diklik
    $('#deleteModal').on('show.bs.modal', function (e) {
        const $btn = $(e.relatedTarget);
        $(this).find('form').attr('action', $btn.data('url'));
        $('#deleteName').text($btn.data('name'));
    });

    // Bersihkan state saat modal ditutup
    $('.modal').on('hidden.bs.modal', function () {
        const $form = $(this).find('form.ajax-form');
        clearErrors($form);
        $form.data('submitting', false);
        setLoading($form, false);
    });
});
</script>
