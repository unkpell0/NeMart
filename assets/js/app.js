/* =========================================================================
   NeMart — JavaScript vanilla: menu, dialog, carousel, tab, stepper,
   keranjang, dan simulasi pembayaran.
   ========================================================================= */
(function () {
    'use strict';

    var $ = function (selector, scope) { return (scope || document).querySelector(selector); };
    var $$ = function (selector, scope) { return Array.prototype.slice.call((scope || document).querySelectorAll(selector)); };
    var rp = function (value) { return 'Rp' + Math.round(value).toLocaleString('id-ID'); };

    /* ------------------------------------------------------------- menus */
    function closeMenus(except) {
        $$('[data-menu-toggle]').forEach(function (btn) {
            var menu = document.getElementById(btn.getAttribute('data-menu-toggle'));
            if (!menu || menu === except) { return; }
            menu.hidden = true;
            btn.setAttribute('aria-expanded', 'false');
        });
    }

    $$('[data-menu-toggle]').forEach(function (btn) {
        var menu = document.getElementById(btn.getAttribute('data-menu-toggle'));
        if (!menu) { return; }
        btn.addEventListener('click', function (event) {
            event.stopPropagation();
            var willOpen = menu.hidden;
            closeMenus(menu);
            menu.hidden = !willOpen;
            btn.setAttribute('aria-expanded', String(willOpen));
        });
    });

    document.addEventListener('click', function (event) {
        if (!event.target.closest('.menu-wrap')) { closeMenus(null); }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenus(null);
            closeSidebar();
        }
    });

    /* ------------------------------------------------------------ header */
    var header = $('#siteHeader');
    if (header) {
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ----------------------------------------------------------- sidebar */
    var sidebar = $('#sellerSidebar') || $('#adminSidebar');
    var overlay = $('[data-sidebar-close]');
    var sidebarToggle = $('[data-sidebar-toggle]');

    function openSidebar() {
        if (!sidebar) { return; }
        sidebar.classList.add('is-open');
        if (overlay) { overlay.classList.add('is-open'); }
        if (sidebarToggle) { sidebarToggle.setAttribute('aria-expanded', 'true'); }
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        if (!sidebar) { return; }
        sidebar.classList.remove('is-open');
        if (overlay) { overlay.classList.remove('is-open'); }
        if (sidebarToggle) { sidebarToggle.setAttribute('aria-expanded', 'false'); }
        document.body.style.overflow = '';
    }
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('is-open')) { closeSidebar(); } else { openSidebar(); }
        });
    }
    if (overlay) { overlay.addEventListener('click', closeSidebar); }
    if (sidebar) {
        sidebar.addEventListener('click', function (event) {
            if (window.innerWidth <= 980 && event.target.closest('a')) { closeSidebar(); }
        });
    }

    /* ------------------------------------------------------------ dialog */
    function openDialog(id) {
        var dialog = document.getElementById(id);
        if (!dialog) { return; }
        if (typeof dialog.showModal === 'function') { dialog.showModal(); } else { dialog.setAttribute('open', ''); }
        document.body.style.overflow = 'hidden';
    }
    function closeDialog(dialog) {
        if (!dialog) { return; }
        if (typeof dialog.close === 'function') { dialog.close(); } else { dialog.removeAttribute('open'); }
        document.body.style.overflow = '';
    }
    $$('[data-dialog-open]').forEach(function (btn) {
        btn.addEventListener('click', function () { openDialog(btn.getAttribute('data-dialog-open')); });
    });
    $$('[data-dialog-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { closeDialog(btn.closest('dialog')); });
    });
    $$('dialog').forEach(function (dialog) {
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) { closeDialog(dialog); }
        });
        dialog.addEventListener('close', function () { document.body.style.overflow = ''; });
    });

    /* ----------------------------------------------------------- carousel */
    var hero = $('[data-hero]');
    if (hero) {
        var slides = $$('.hero__slide', hero);
        var dots = $$('[data-hero-dot]', hero);
        var index = 0;
        var timer = null;

        var show = function (next) {
            if (slides.length === 0) { return; }
            index = (next + slides.length) % slides.length;
            slides.forEach(function (slide, i) {
                slide.classList.toggle('is-active', i === index);
                slide.setAttribute('aria-hidden', String(i !== index));
                slide.inert = i !== index;
            });
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-selected', String(i === index));
            });
        };
        var start = function () {
            stop();
            if (slides.length > 1) {
                timer = window.setInterval(function () { show(index + 1); }, 5000);
            }
        };
        var stop = function () { if (timer) { window.clearInterval(timer); timer = null; } };

        dots.forEach(function (dot, i) {
            dot.addEventListener('click', function () { show(i); start(); });
        });
        var prev = $('[data-hero-prev]', hero);
        var next = $('[data-hero-next]', hero);
        if (prev) { prev.addEventListener('click', function () { show(index - 1); start(); }); }
        if (next) { next.addEventListener('click', function () { show(index + 1); start(); }); }
        hero.addEventListener('mouseenter', stop);
        hero.addEventListener('mouseleave', start);
        hero.addEventListener('focusin', stop);
        show(0);
        start();
    }

    /* --------------------------------------------------------------- tab */
    $$('[data-tabs]').forEach(function (group) {
        var buttons = $$('[data-tab]', group);
        var scope = document.getElementById(group.getAttribute('data-tabs'));
        buttons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var key = button.getAttribute('data-tab');
                buttons.forEach(function (b) {
                    var active = b === button;
                    b.classList.toggle('is-active', active);
                    b.setAttribute('aria-selected', String(active));
                });
                if (scope) {
                    $$('.tab-panel', scope).forEach(function (panel) {
                        panel.classList.toggle('is-active', panel.getAttribute('data-panel') === key);
                    });
                }
            });
        });
    });

    /* ----------------------------------------------------------- stepper */
    $$('[data-stepper]').forEach(function (stepper) {
        var input = $('input', stepper);
        if (!input) { return; }
        var min = parseInt(input.getAttribute('min') || '1', 10);
        var max = parseInt(input.getAttribute('max') || '999', 10);
        var sync = function () {
            var value = parseInt(input.value || '1', 10);
            if (isNaN(value)) { value = min; }
            value = Math.max(min, Math.min(max, value));
            input.value = String(value);
            $$('[data-step]', stepper).forEach(function (button) {
                var dir = parseInt(button.getAttribute('data-step'), 10);
                button.disabled = (dir < 0 && value <= min) || (dir > 0 && value >= max);
            });
            input.dispatchEvent(new CustomEvent('stepper:change', { bubbles: true, detail: { value: value } }));
        };
        $$('[data-step]', stepper).forEach(function (button) {
            button.addEventListener('click', function () {
                var value = parseInt(input.value || '1', 10) + parseInt(button.getAttribute('data-step'), 10);
                input.value = String(value);
                sync();
            });
        });
        input.addEventListener('change', sync);
        sync();
    });

    /* ------------------------------------------------------ keranjang pilih */
    var cartPage = $('[data-cart-page]');
    if (cartPage) {
        var checkboxes = $$('[data-cart-check]', cartPage);
        var master = $('[data-cart-check-all]', cartPage);
        var countEl = $('#cartSelectedCount');
        var subtotalEl = $('#cartSelectedSubtotal');
        var checkoutBtn = $('#cartCheckoutBtn');

        var recalc = function () {
            var count = 0;
            var subtotal = 0;
            var selected = 0;
            checkboxes.forEach(function (box) {
                var row = box.closest('[data-cart-row]');
                var isChecked = box.checked;
                if (row) { row.classList.toggle('is-unchecked', !isChecked); }
                if (isChecked) {
                    selected += 1;
                    count += parseInt(row.getAttribute('data-qty') || '0', 10);
                    subtotal += parseInt(row.getAttribute('data-price') || '0', 10) * parseInt(row.getAttribute('data-qty') || '0', 10);
                }
            });
            if (countEl) { countEl.textContent = String(count); }
            if (subtotalEl) { subtotalEl.textContent = rp(subtotal); }
            if (checkoutBtn) {
                checkoutBtn.disabled = selected === 0;
                checkoutBtn.classList.toggle('is-disabled', selected === 0);
            }
            if (master) {
                master.checked = selected === checkboxes.length && checkboxes.length > 0;
                master.indeterminate = selected > 0 && selected < checkboxes.length;
            }
        };

        checkboxes.forEach(function (box) { box.addEventListener('change', recalc); });
        if (master) {
            master.addEventListener('change', function () {
                checkboxes.forEach(function (box) { box.checked = master.checked; });
                recalc();
            });
        }
        recalc();
    }

    /* ------------------------------------------------------- checkout ongkir */
    var checkout = $('[data-checkout]');
    if (checkout) {
        var subtotal = parseInt(checkout.getAttribute('data-subtotal') || '0', 10);
        var service = parseInt(checkout.getAttribute('data-service') || '0', 10);
        var shippingPerStore = parseInt(checkout.getAttribute('data-shipping') || '0', 10);
        var storeCount = parseInt(checkout.getAttribute('data-stores') || '1', 10);
        var shippingEl = $('#coShipping');
        var totalEl = $('#coTotal');
        var payAmount = $('#payAmount');
        var payMethodName = $('#payMethodName');
        var pickupPanel = $('#coPickupPanel');
        var deliveryPanel = $('#coDeliveryPanel');
        var deliveryNotes = $('#coDeliveryNotes');

        var updateCheckout = function () {
            var selected = $('input[name="handover"]:checked', checkout);
            var isDelivery = selected && selected.value === 'delivery';
            var shipping = isDelivery ? shippingPerStore * storeCount : 0;
            var total = subtotal + service + shipping;
            if (shippingEl) { shippingEl.textContent = shipping === 0 ? 'Gratis' : rp(shipping); }
            if (totalEl) { totalEl.textContent = rp(total); }
            if (payAmount) { payAmount.textContent = rp(total); }
            if (pickupPanel) { pickupPanel.hidden = isDelivery; }
            if (deliveryPanel) { deliveryPanel.hidden = !isDelivery; }
            if (deliveryNotes) {
                deliveryNotes.required = isDelivery;
                if (!isDelivery) { deliveryNotes.value = ''; }
            }
        };

        $$('input[name="handover"]', checkout).forEach(function (radio) {
            radio.addEventListener('change', updateCheckout);
        });
        $$('input[name="payment_method"]', checkout).forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (payMethodName) { payMethodName.textContent = radio.value; }
            });
        });
        updateCheckout();
    }

    /* ---------------------------------------------------------- ulasan modal */
    $$('[data-review-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var dialog = document.getElementById('reviewDialog');
            if (!dialog) { return; }
            var orderField = $('#reviewOrderId', dialog);
            var productField = $('#reviewProductId', dialog);
            var nameEl = $('#reviewProductName', dialog);
            if (orderField) { orderField.value = button.getAttribute('data-order-id') || ''; }
            if (productField) { productField.value = button.getAttribute('data-product-id') || ''; }
            if (nameEl) { nameEl.textContent = button.getAttribute('data-product-name') || ''; }
            var comment = $('#reviewComment', dialog);
            if (comment) { comment.value = ''; }
            var five = $('#reviewStar5', dialog);
            if (five) { five.checked = true; }
            openDialog('reviewDialog');
        });
    });

    /* ------------------------------------------ modal pembatalan / penolakan */
    $$('[data-cancel-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var idField = $('#cancelOrderId');
            var codeEl = $('#cancelOrderCode');
            var actionField = $('#cancelAction');
            var reason = $('#cancelReason');
            if (idField) { idField.value = button.getAttribute('data-order-id') || ''; }
            if (codeEl) { codeEl.textContent = button.getAttribute('data-order-code') || '—'; }
            if (actionField) { actionField.value = button.getAttribute('data-cancel-action') || 'cancel'; }
            if (reason) { reason.value = ''; }
            openDialog('cancelDialog');
        });
    });

    $$('[data-reject-open]').forEach(function (button) {
        button.addEventListener('click', function () {
            var idField = $('#rejectStoreId');
            var nameEl = $('#rejectStoreName');
            var reason = $('#rejectReason');
            if (idField) { idField.value = button.getAttribute('data-store-id') || ''; }
            if (nameEl) { nameEl.textContent = button.getAttribute('data-store-name') || 'Toko'; }
            if (reason) { reason.value = ''; }
            openDialog('rejectDialog');
        });
    });

    /* --------------------------------------------------------- konfirmasi */
    $$('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (!window.confirm(element.getAttribute('data-confirm'))) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    });

    /* --------------------------------------------------------------- flash */
    $$('[data-dismiss="flash"]').forEach(function (button) {
        button.addEventListener('click', function () {
            var flash = button.closest('.flash');
            if (flash) { flash.remove(); }
        });
    });

    /* -------------------------------------------------- auto submit select */
    $$('[data-autosubmit]').forEach(function (select) {
        select.addEventListener('change', function () {
            if (select.form) { select.form.submit(); }
        });
    });
})();
