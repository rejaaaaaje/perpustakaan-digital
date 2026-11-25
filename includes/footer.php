<!-- ==============================================
     GLOBAL CONFIRM POPUP — support link & submit form
     ============================================== -->
<style>
    .confirm-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(3px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .confirm-overlay.show { display: flex; }

    .confirm-box {
        background: #fff;
        width: 100%;
        max-width: 360px;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        transform: translateY(12px);
        opacity: 0;
        transition: all .2s ease;
        font-family: inherit;
    }
    .confirm-overlay.show .confirm-box { transform: translateY(0); opacity: 1; }

    .confirm-icon {
        width: 44px; height: 44px; border-radius: 50%;
        background: #eef5f1; color: #0f5132;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; margin-bottom: 12px;
    }
    .confirm-icon.warn { background: #fef3c7; color: #b45309; }
    .confirm-icon.danger { background: #fee2e2; color: #b91c1c; }

    .confirm-title { font-size: 16px; font-weight: 700; color: #111827; margin-bottom: 4px; }
    .confirm-desc { font-size: 13.5px; color: #6b7280; line-height: 1.5; margin-bottom: 20px; }
    .confirm-actions { display: flex; gap: 8px; justify-content: flex-end; }

    .confirm-btn {
        padding: 9px 18px; font-size: 13.5px; font-weight: 600;
        border-radius: 7px; border: none; cursor: pointer;
        font-family: inherit; transition: background .15s;
    }
    .confirm-btn.cancel { background: #f1f5f9; color: #475569; }
    .confirm-btn.cancel:hover { background: #e2e8f0; }

    .confirm-btn.ok { background: #0f5132; color: #fff; }
    .confirm-btn.ok:hover { background: #146c43; }
    .confirm-btn.ok.warn { background: #b45309; }
    .confirm-btn.ok.warn:hover { background: #92400e; }
    .confirm-btn.ok.danger { background: #b91c1c; }
    .confirm-btn.ok.danger:hover { background: #991b1b; }
</style>

<div class="confirm-overlay" id="confirmPopup">
    <div class="confirm-box">
        <div class="confirm-icon" id="confirmIcon"><i class="bi bi-question-lg"></i></div>
        <div class="confirm-title" id="confirmTitle">Konfirmasi</div>
        <div class="confirm-desc" id="confirmDesc">Apakah Anda yakin?</div>
        <div class="confirm-actions">
            <button type="button" class="confirm-btn cancel" id="confirmCancel">Batal</button>
            <button type="button" class="confirm-btn ok" id="confirmOk">Ya, Lanjutkan</button>
        </div>
    </div>
</div>

<script>
(function () {
    var popup   = document.getElementById('confirmPopup');
    if (!popup) return;

    var iconEl  = document.getElementById('confirmIcon');
    var titleEl = document.getElementById('confirmTitle');
    var descEl  = document.getElementById('confirmDesc');
    var okBtn   = document.getElementById('confirmOk');
    var cancelBtn = document.getElementById('confirmCancel');
    var pendingAction = null;

    function showPopup(opts) {
        var type = opts.type || 'default';
        var iconHtml = opts.icon || '<i class="bi bi-question-lg"></i>';

        iconEl.className = 'confirm-icon' + (type === 'warn' ? ' warn' : type === 'danger' ? ' danger' : '');
        iconEl.innerHTML = iconHtml;
        titleEl.textContent = opts.title || 'Konfirmasi';
        descEl.textContent  = opts.message || 'Apakah Anda yakin?';
        okBtn.textContent   = opts.okText || 'Ya, Lanjutkan';
        okBtn.className     = 'confirm-btn ok' + (type === 'warn' ? ' warn' : type === 'danger' ? ' danger' : '');
        pendingAction       = opts.onConfirm;

        popup.classList.add('show');
    }

    function hidePopup() { popup.classList.remove('show'); pendingAction = null; }

    okBtn.addEventListener('click', function () {
        var action = pendingAction;
        hidePopup();
        if (typeof action === 'function') action();
    });
    cancelBtn.addEventListener('click', hidePopup);

    popup.addEventListener('click', function (e) { if (e.target === popup) hidePopup(); });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && popup.classList.contains('show')) hidePopup();
    });

    // Helper untuk mengambil atribut data-confirm
    function getConfirmData(el) {
        return {
            type:    el.getAttribute('data-confirm-type') || 'default',
            title:   el.getAttribute('data-confirm-title') || 'Konfirmasi',
            message: el.getAttribute('data-confirm-message') || el.getAttribute('data-confirm'),
            okText:  el.getAttribute('data-confirm-ok') || 'Ya, Lanjutkan',
            icon:    el.getAttribute('data-confirm-icon')
        };
    }

    // 1. Untuk LINK (<a href>) dengan data-confirm
    document.addEventListener('click', function (e) {
        var el = e.target.closest('a[data-confirm]');
        if (!el) return;
        e.preventDefault();

        var data = getConfirmData(el);
        var href = el.getAttribute('href');

        showPopup({
            type: data.type, title: data.title, message: data.message,
            okText: data.okText, icon: data.icon,
            onConfirm: function () {
                if (href && href !== '#') window.location.href = href;
            }
        });
    });

    // 2. Untuk SUBMIT FORM dengan tombol ber-data-confirm
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var submitter = e.submitter; // tombol yang diklik
        if (!submitter || !submitter.hasAttribute('data-confirm')) return;

        // Cegah submit pertama
        e.preventDefault();

        var data = getConfirmData(submitter);
        showPopup({
            type: data.type, title: data.title, message: data.message,
            okText: data.okText, icon: data.icon,
            onConfirm: function () {
                // Submit ulang tanpa konfirmasi lagi
                submitter.removeAttribute('data-confirm');
                form.submit();
            }
        });
    });
})();
</script>   
