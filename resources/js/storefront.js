/**
 * PRODUCT QUICK VIEW
 *
 */
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('storeProductQuickView');
    const el = (id) => document.getElementById(id);
    const placeholder = modalElement?.dataset.placeholder || '/storage/products/placeholder.png';
    const formatRupiah = (value) => 'Rp ' + new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0
    }).format(Number(value || 0));
    const setText = (id, value) => {
        const node = el(id);
        if (node) {
            node.textContent = value ?? '';
        }
    }

    function makeElement(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function renderVariants(variants) {
        const container = el('storeQuickViewVariants');
        container.replaceChildren();
        const activeVariants = variants.filter(variant => variant.status === 'active');
        setText('storeQuickViewVariantCount', activeVariants.length + ' varian');
        if (!activeVariants.length) {
            container.append(makeElement('p', 'store-quick-view-empty', 'Belum ada varian aktif untuk produk ini.'));
            return;
        }
        activeVariants.forEach(function(variant) {
            const card = makeElement('article', 'store-quick-view-variant');
            const top = makeElement('div', 'store-quick-view-variant-top');
            const name = makeElement('h4', 'store-quick-view-variant-name', variant.name || 'Varian');
            const stock = Number(variant.stock || 0);
            const stockNode = makeElement('span', stock > 0 ? 'store-quick-view-stock is-available' : 'store-quick-view-stock is-empty', stock > 0 ? stock + ' tersedia' : 'Stok habis');
            top.append(name, stockNode);
            card.append(top);
            
            if (variant.attributes?.length) {
                const attributes = makeElement('div', 'store-quick-view-attributes');
                variant.attributes.forEach(function(attribute) {
                    const row = makeElement('div', 'store-quick-view-attribute');
                    row.append(makeElement('span', '', attribute.name || 'Atribut'));
                    const valueWrap = makeElement('strong', 'store-quick-view-attribute-value');
                    if (attribute.hex_color) {
                        const swatch = makeElement('span', 'store-quick-view-color-swatch');
                        swatch.style.backgroundColor = attribute.hex_color;
                        swatch.setAttribute('aria-hidden', 'true');
                        valueWrap.append(swatch);
                    }
                    valueWrap.append(document.createTextNode(attribute.value || '-'));
                    row.append(valueWrap);
                    attributes.append(row);
                });
                card.append(attributes);
            }
            
            const meta = makeElement('div', 'store-quick-view-variant-meta');
            meta.append(makeElement('span', '', 'SKU: ' + (variant.sku || '-')), makeElement('strong', '', formatRupiah(variant.price)));
            if (variant.compare_at_price && Number(variant.compare_at_price) > Number(variant.price)) {
                meta.append(makeElement('del', 'store-quick-view-old-price', formatRupiah(variant.compare_at_price)));
            }
            card.append(meta);
            container.append(card);
        });
    }

    function renderSpecifications(specifications) {
        const container = el('storeQuickViewSpecifications');
        container.replaceChildren();
        if (!specifications.length) {
            container.append(makeElement('p', 'store-quick-view-empty', 'Belum ada spesifikasi tambahan.'));
            return;
        }
        const table = makeElement('table', 'table table-striped table-bordered text-nowrap');
        const thead = makeElement('thead');
        const tbody = makeElement('tbody');
        const headerRow = makeElement('tr');
        headerRow.append(makeElement('th', '', 'Fitur / Spesifikasi'), makeElement('th', '', 'Keterangan'));
        thead.append(headerRow);
        specifications.forEach(function(specification) {
            // Pindahkan pembuatan baris (tr) ke DALAM loop agar setiap spesifikasi punya baris sendiri
            const row = makeElement('tr');
            // Kolom Nama Spesifikasi
            row.append(makeElement('th', 'store-quick-view-spec-name', specification.name || 'Spesifikasi'));
            // Kolom Nilai Spesifikasi
            row.append(makeElement('td', 'store-quick-view-spec-value', specification.value || '-'));
            // Masukkan baris ke dalam tbody
            tbody.append(row);
        });
        table.append(thead);
        table.append(tbody);
        console.log(table);
        container.append(table);
    }
    if (!modalElement) {
        return;
    }
    modalElement.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (!button) return;
        const variants = JSON.parse(button.dataset.productVariants || '[]');
        setText('storeQuickViewName', button.dataset.productName);
        setText('storeQuickViewCategory', button.dataset.productCategory);
        setText('storeQuickViewDescription', button.dataset.productDescription);
        setText('storeQuickViewPrice', formatRupiah(button.dataset.productPrice));
        const rating = Number(button.dataset.productRating || 0);
        const reviewCount = Number(button.dataset.productReviews || 0);
        setText('storeQuickViewRating', rating.toFixed(1));
        setText('storeQuickViewReviews', reviewCount > 0 ? '(' + reviewCount + ' ulasan)' : '(Belum ada ulasan)')
        const image = el('storeQuickViewImage');
        image.src = button.dataset.productImage || placeholder;
        image.alt = button.dataset.productName || 'Gambar produk';
        image.onerror = function() {
            this.onerror = null;
            this.src = placeholder;
        };
        const detailLink = el('storeQuickViewDetailLink');
        if (detailLink) {
            detailLink.href = button.dataset.productUrl || '#';
        }
        renderVariants(variants);
        renderSpecifications(JSON.parse(button.dataset.productSpecifications || '[]'));
    });
});
/**
 * =========================================================
 * COMPARE TABLE HORIZONTAL NAVIGATION
 * =========================================================
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.store-compare').forEach(function(compare) {
        const scroller = compare.querySelector('[data-compare-scroll]');
        const buttons = compare.querySelectorAll('[data-compare-direction]');
        const controls = compare.querySelector('.store-compare-controls');
        const hint = compare.querySelector('.store-compare-hint');
        if (!scroller || !buttons.length) {
            return;
        }

        function updateCompareControls() {
            const maxScroll = scroller.scrollWidth - scroller.clientWidth;
            const canScroll = maxScroll > 1;
            if (controls) {
                controls.hidden = !canScroll;
            }
            if (hint) {
                hint.hidden = !canScroll;
            }
            buttons.forEach(function(button) {
                const direction = button.dataset.compareDirection;
                button.disabled = !canScroll || (direction === 'left' ? scroller.scrollLeft <= 1 : scroller.scrollLeft >= maxScroll - 1);
            });
        }
        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                const direction = button.dataset.compareDirection;
                const distance = Math.max(180, Math.round(scroller.clientWidth * 0.75));
                scroller.scrollBy({
                    left: direction === 'left' ? -distance : distance,
                    behavior: 'smooth'
                });
            });
        });
        scroller.addEventListener('scroll', updateCompareControls, {
            passive: true
        });
        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(updateCompareControls);
            resizeObserver.observe(scroller);
        } else {
            window.addEventListener('resize', updateCompareControls);
        }
        updateCompareControls();
    });
});
/**
 * =========================================================
 * MOBILE TOPBAR & BACK TO TOP
 * =========================================================
 */
document.addEventListener('DOMContentLoaded', function() {
    const mobileTopbar = document.querySelector('.store-mobile-topbar');
    const backToTop = document.querySelector('.store-back-to-top');
    const mobileBreakpoint = window.matchMedia('(max-width: 575.98px)');
    const mobileScrollThreshold = 120;
    const backToTopThreshold = 300;
    const directionThreshold = 6;
    let ticking = false;
    let lastScrollY = Math.max(0, window.scrollY || window.pageYOffset || 0);

    function updateScrollUI() {
        const scrollY = Math.max(0, window.scrollY || window.pageYOffset || 0);
        // Mobile topbar
        if (mobileTopbar) {
            if (!mobileBreakpoint.matches) {
                mobileTopbar.classList.remove('is-hidden');
            } else if (scrollY <= mobileScrollThreshold) {
                mobileTopbar.classList.remove('is-hidden');
            } else {
                const difference = scrollY - lastScrollY;
                if (difference > directionThreshold) {
                    mobileTopbar.classList.add('is-hidden');
                } else if (difference < -directionThreshold) {
                    mobileTopbar.classList.remove('is-hidden');
                }
            }
        }
        // Back to Top tetap independen dari mobile topbar.
        if (backToTop) {
            backToTop.classList.toggle('is-visible', scrollY > backToTopThreshold);
        }
        lastScrollY = scrollY;
        ticking = false;
    }

    function requestScrollUpdate() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(updateScrollUI);
    }
    window.addEventListener('scroll', requestScrollUpdate, {
        passive: true
    });
    window.addEventListener('resize', requestScrollUpdate);
    mobileBreakpoint.addEventListener('change', requestScrollUpdate);
    if (backToTop) {
        backToTop.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        });
    }
    updateScrollUI();
});

window.formatCartRupiah = function (value) {
    return 'Rp ' + Number(value ?? 0).toLocaleString('id-ID');
};

window.syncCartPage = function (data, itemKey) {
    const key = String(data.cart_item_key ?? itemKey ?? '');

    if (key) {
        const row = Array.from(
            document.querySelectorAll('.store-cart-item')
        ).find(element => element.dataset.cartItemKey === key);

        if (row) {
            const input = row.querySelector('.quantity-input');
            const subtotal = row.querySelector('.cart-item-subtotal');

            if (input && data.quantity != null) {
                input.value = data.quantity;
            }

            if (subtotal && data.subtotal != null) {
                subtotal.textContent = window.formatCartRupiah(data.subtotal);
            }
        }
    }

    if (data.total != null) {
        document.querySelectorAll('.cart-total, .cart-grand-total')
            .forEach(element => {
                element.textContent = window.formatCartRupiah(data.total);
            });
    }

    if (data.item_count != null) {
        document.querySelectorAll('.cart-item-count')
            .forEach(element => {
                element.textContent = data.item_count;
            });
    }

    if (typeof window.updateCartBadge === 'function'
        && data.cart_count != null) {
        window.updateCartBadge(data.cart_count);
    }
};
/**
 * MOBILE CART DRAWER — AJAX QUANTITY
 */
document.addEventListener('click', function(event) {
    const button = event.target.closest('[data-mobile-cart-decrease], [data-mobile-cart-increase]');
    if (!button) return;
    const form = button.closest('.store-mobile-cart-quantity-form');
    if (!form) return;
    event.preventDefault();
    const input = form.querySelector('.store-mobile-cart-quantity-input');
    if (!input || input.disabled) return;
    const min = Number(input.min) || 1;
    const maxValue = Number(input.max);
    const max = Number.isFinite(maxValue) && maxValue > 0 ? maxValue : Infinity;
    const current = Number(input.value) || min;
    const delta = button.hasAttribute('data-mobile-cart-increase') ? 1 : -1;
    const quantity = Math.max(min, Math.min(current + delta, max));
    if (quantity === current) return;
    input.value = quantity;
    updateMobileCartQuantity(form, quantity);
});
document.addEventListener('change', function(event) {
    const input = event.target.closest('.store-mobile-cart-quantity-input');
    if (!input) return;
    const form = input.closest('.store-mobile-cart-quantity-form');
    if (!form || input.disabled) return;
    const min = Number(input.min) || 1;
    const maxValue = Number(input.max);
    const max = Number.isFinite(maxValue) && maxValue > 0 ? maxValue : Infinity;
    const quantity = Math.max(min, Math.min(Number(input.value) || min, max));
    input.value = quantity;
    updateMobileCartQuantity(form, quantity);
});
async function updateMobileCartQuantity(form, quantity) {
    const input = form.querySelector('.store-mobile-cart-quantity-input');
    const buttons = form.querySelectorAll('button');
    const previousQuantity = input.value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    input.disabled = true;
    buttons.forEach(button => button.disabled = true);
    form.classList.add('opacity-75');
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? {
                    'X-CSRF-TOKEN': csrfToken
                } : {}),
            },
            body: new URLSearchParams({
                _token: csrfToken || '',
                _method: 'PATCH',
                quantity: String(quantity),
            }),
            credentials: 'same-origin',
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Gagal memperbarui keranjang.');
        }
        // Sinkronkan halaman Cart jika sedang ditampilkan.
        if (typeof window.syncCartPage === 'function') {
            window.syncCartPage(data, form.dataset.cartItemKey);
        }
        // Render ulang drawer dengan data terbaru dari server.
        if (typeof window.refreshCartDrawer === 'function') {
            await window.refreshCartDrawer();
        }
        window.showStoreNotification(data.message || 'Jumlah produk berhasil diperbarui.', 'success');
    } catch (error) {
        console.error('Cart quantity update failed:', error);
        window.showStoreNotification(error.message || 'Gagal memperbarui keranjang.', 'danger');
        // Jangan biarkan UI menampilkan perubahan yang gagal disimpan.
        if (typeof window.refreshCartDrawer === 'function') {
            await window.refreshCartDrawer();
        } else {
            input.value = previousQuantity;
        }
    } finally {
        input.disabled = false;
        buttons.forEach(button => button.disabled = false);
        form.classList.remove('opacity-75');
    }
}
/**
 * =========================================================
 * MOBILE SEARCH DRAWER
 * =========================================================
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchDrawer = document.getElementById('storeMobileSearchDrawer');
    const searchInput = document.getElementById('storeMobileSearchInput');
    if (!searchDrawer || !searchInput) {
        return;
    }
    const clearButton = searchDrawer.querySelector('[data-mobile-search-clear]');

    function updateClearButton() {
        if (!clearButton) {
            return;
        }
        clearButton.hidden = searchInput.value.trim() === '';
    }
    searchInput.addEventListener('input', updateClearButton);
    updateClearButton();
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            searchInput.value = '';
            updateClearButton();
            searchInput.focus();
        });
    }
    const searchForm = searchDrawer.querySelector('.store-mobile-search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(event) {
            const value = searchInput.value.trim();
            if (value === '') {
                event.preventDefault();
                searchInput.focus();
                return;
            }
            searchInput.value = value;
        });
    }
    const observer = new MutationObserver(function() {
        const isOpen = searchDrawer.getAttribute('aria-hidden') === 'false';
        if (!isOpen) {
            return;
        }
        window.setTimeout(function() {
            searchInput.focus();
            const value = searchInput.value;
            if (value) {
                searchInput.setSelectionRange(value.length, value.length);
            }
        }, 80);
    });
    observer.observe(searchDrawer, {
        attributes: true,
        attributeFilter: ['aria-hidden'],
    });
});
/**
 * =========================================================
 * MOBILE CATEGORY ACCORDION
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function() {
    const categoryToggles = document.querySelectorAll('.store-mobile-category-toggle');
    if (!categoryToggles.length) {
        return;
    }
    categoryToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function() {
            const targetId = toggle.getAttribute('aria-controls');
            if (!targetId) {
                return;
            }
            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            /*
             * Tutup category lain.
             */
            categoryToggles.forEach(function(otherToggle) {
                if (otherToggle === toggle) {
                    return;
                }
                const otherTargetId = otherToggle.getAttribute('aria-controls');
                const otherTarget = document.getElementById(otherTargetId);
                otherToggle.setAttribute('aria-expanded', 'false');
                if (otherTarget) {
                    otherTarget.setAttribute('aria-hidden', 'true');
                }
            });
            /*
             * Toggle category aktif.
             */
            toggle.setAttribute('aria-expanded', String(!isExpanded));
            target.setAttribute('aria-hidden', String(isExpanded));
        });
    });
});
/**
 * =========================================================
 * MOBILE DRAWER SYSTEM
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function() {
    const drawerTriggers = document.querySelectorAll('[data-mobile-drawer]');
    const drawers = document.querySelectorAll('.store-mobile-drawer');
    if (!drawerTriggers.length || !drawers.length) {
        return;
    }
    let activeDrawer = null;
    let activeTrigger = null;

    function openDrawer(drawer, trigger) {
        if (!drawer) {
            return;
        }
        if (activeDrawer && activeDrawer !== drawer) {
            closeDrawer(activeDrawer, activeTrigger);
        }
        drawer.setAttribute('aria-hidden', 'false');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'true');
        }
        document.body.classList.add('store-mobile-drawer-open');
        activeDrawer = drawer;
        activeTrigger = trigger;
        const closeButton = drawer.querySelector('[data-mobile-drawer-close]');
        if (closeButton) {
            window.setTimeout(function() {
                closeButton.focus();
            }, 50);
        }
    }

    function closeDrawer(drawer, trigger) {
        if (!drawer) {
            return;
        }
        drawer.setAttribute('aria-hidden', 'true');
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
        if (activeDrawer === drawer) {
            activeDrawer = null;
            activeTrigger = null;
        }
        if (!document.querySelector('.store-mobile-drawer[aria-hidden="false"]')) {
            document.body.classList.remove('store-mobile-drawer-open');
        }
    }

    function closeActiveDrawer() {
        if (!activeDrawer) {
            return;
        }
        const drawer = activeDrawer;
        const trigger = activeTrigger;
        closeDrawer(drawer, trigger);
        if (trigger) {
            trigger.focus();
        }
    }
    /**
     * ---------------------------------------------------------
     * OPEN DRAWER
     * ---------------------------------------------------------
     */
    drawerTriggers.forEach(function(trigger) {
        trigger.addEventListener('click', function() {
            const drawerId = trigger.getAttribute('aria-controls');
            if (!drawerId) {
                return;
            }
            const drawer = document.getElementById(drawerId);
            if (!drawer) {
                return;
            }
            const isOpen = drawer.getAttribute('aria-hidden') === 'false';
            if (isOpen) {
                closeDrawer(drawer, trigger);
                return;
            }
            openDrawer(drawer, trigger);
        });
    });
    /**
     * ---------------------------------------------------------
     * CLOSE BUTTON
     * ---------------------------------------------------------
     */
    drawers.forEach(function(drawer) {
        const closeButton = drawer.querySelector('[data-mobile-drawer-close]');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                closeActiveDrawer();
            });
        }
        /**
         * -----------------------------------------------------
         * CLICK OVERLAY
         * -----------------------------------------------------
         */
        drawer.addEventListener('click', function(event) {
            if (event.target === drawer) {
                closeActiveDrawer();
            }
        });
    });
    /**
     * ---------------------------------------------------------
     * ESCAPE
     * ---------------------------------------------------------
     */
    document.addEventListener('keydown', function(event) {
        if (event.key !== 'Escape') {
            return;
        }
        closeActiveDrawer();
    });
    /**
     * ---------------------------------------------------------
     * NAVIGATION
     * ---------------------------------------------------------
     */
    drawers.forEach(function(drawer) {
        drawer.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                closeDrawer(drawer, activeTrigger);
            });
        });
    });
});
/**
 * =========================================================
 * DESKTOP DEPARTMENT MENU
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function() {
    const department = document.querySelector('.store-department-dropdown');
    if (!department) {
        return;
    }
    const toggle = department.querySelector('.store-department-toggle');
    const menu = department.querySelector('.store-department-menu');
    if (!toggle || !menu) {
        return;
    }

    function openMenu() {
        department.classList.add('is-open');
        toggle.setAttribute('aria-expanded', 'true');
        menu.setAttribute('aria-hidden', 'false');
    }

    function closeMenu() {
        department.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        menu.setAttribute('aria-hidden', 'true');
    }
    toggle.addEventListener('click', function(event) {
        event.preventDefault();
        if (department.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    document.addEventListener('click', function(event) {
        if (!department.contains(event.target)) {
            closeMenu();
        }
    });
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMenu();
            toggle.focus();
        }
    });
});
/**
 * =========================================================
 * CART
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.quantity-form').forEach(function(form) {
        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-decrease');
        const plusButton = form.querySelector('.quantity-increase');
        if (!input || !minusButton || !plusButton) {
            return;
        }
        updateButtons(form);
        minusButton.addEventListener('click', function() {
            const min = Number(input.min) || 1;
            let value = Number(input.value) || min;
            if (value <= min) {
                return;
            }
            value--;
            input.value = value;
            updateButtons(form);
            updateCartItem(form, value);
        });
        plusButton.addEventListener('click', function() {
            const max = Number(input.max) || Infinity;
            let value = Number(input.value) || 1;
            if (value >= max) {
                return;
            }
            value++;
            input.value = value;
            updateButtons(form);
            updateCartItem(form, value);
        });
        input.addEventListener('change', function() {
            const min = Number(input.min) || 1;
            const max = Number(input.max) || Infinity;
            let value = Number(input.value) || min;
            value = Math.max(min, Math.min(value, max));
            input.value = value;
            updateButtons(form);
            updateCartItem(form, value);
        });
    });
    
    async function updateCartItem(form, quantity) {
        const input = form.querySelector('.quantity-input');
        const buttons = form.querySelectorAll('button');
        const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
        )?.getAttribute('content');
    
        if (!csrfToken || !form.dataset.cartItemKey) {
            window.showStoreNotification(
                'Identitas item keranjang tidak tersedia.',
                'danger'
            );
            return;
        }
    
        input.disabled = true;
        buttons.forEach(button => button.disabled = true);
        form.classList.add('opacity-75');
    
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    _token: csrfToken,
                    _method: 'PATCH',
                    quantity: String(quantity),
                }),
                credentials: 'same-origin',
            });
    
            const data = await response.json();
    
            if (!response.ok || !data.success) {
                throw new Error(
                    data.message || 'Gagal memperbarui keranjang.'
                );
            }
    
            window.syncCartPage(data, form.dataset.cartItemKey);
    
            if (typeof window.refreshCartDrawer === 'function') {
                await window.refreshCartDrawer();
            }
    
            window.showStoreNotification(
                data.message || 'Jumlah produk berhasil diperbarui.',
                'success'
            );
        } catch (error) {
            window.showStoreNotification(
                error.message || 'Gagal memperbarui keranjang.',
                'danger'
            );
    
            // Server adalah sumber kebenaran; pulihkan input yang tampil.
            window.location.reload();
        } finally {
            input.disabled = false;
            buttons.forEach(button => button.disabled = false);
            form.classList.remove('opacity-75');
            updateButtons(form);
        }
    }
    
    window.refreshCartDrawer = async function() {
        const content = document.getElementById('storeMobileCartContent');
        if (!content) {
            return;
        }
        try {
            const response = await fetch('/cart/drawer', {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Gagal memperbarui keranjang.');
            }
            content.innerHTML = data.html;
            window.updateCartBadge(data.cart_count);
        } catch (error) {
            console.error('Cart Drawer refresh failed:', error);
        }
    };

    function updateButtons(form) {
        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-decrease');
        const plusButton = form.querySelector('.quantity-increase');
        if (!input || !minusButton || !plusButton) {
            return;
        }
        const min = Number(input.min) || 1;
        const max = Number(input.max) || Infinity;
        const value = Number(input.value) || min;
        minusButton.disabled = value <= min;
        plusButton.disabled = value >= max;
    }
});

document.addEventListener('submit', async function (event) {
    const form = event.target.closest(
        '.store-cart-remove-form, .store-mobile-cart-remove-form'
    );

    if (!form) return;

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.getAttribute('content');

    if (!button || !csrfToken) return;

    button.disabled = true;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: new FormData(form),
            credentials: 'same-origin',
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Gagal menghapus item keranjang.'
            );
        }

        const row = form.closest(
            '.store-cart-item, .store-mobile-cart-item'
        );

        row?.remove();

        window.syncCartPage(data);

        const pageRows = document.querySelectorAll('.store-cart-item');

        if (document.querySelector('.store-cart-items')
            && pageRows.length === 0) {
            // Agar empty state dan tombol checkout ikut diperbarui.
            window.location.reload();
            return;
        }

        if (typeof window.refreshCartDrawer === 'function') {
            await window.refreshCartDrawer();
        }

        window.showStoreNotification(
            data.message || 'Produk berhasil dihapus dari keranjang.',
            'success'
        );
    } catch (error) {
        window.showStoreNotification(
            error.message || 'Gagal menghapus item keranjang.',
            'danger'
        );

        if (typeof window.refreshCartDrawer === 'function') {
            await window.refreshCartDrawer();
        }
    } finally {
        button.disabled = false;
    }
});

window.updateCartBadge = function(count) {
    const badges = document.querySelectorAll('#cart-count-badge, .store-mobile-bottom-badge');
    const value = Number(count) || 0;
    badges.forEach(function(badge) {
        badge.textContent = value > 99 ? '99+' : value;
        if (value > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    });
};

/**
 * =========================================================
 * STOREFRONT NOTIFICATION
 * =========================================================
 */
window.showStoreNotification = function(message, type = 'success') {
    let container = document.getElementById('store-notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'store-notification-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '2000';
        document.body.appendChild(container);
    }
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show shadow`;
    alert.setAttribute('role', 'alert');
    alert.innerHTML = `
    ${message}

    <button
    type="button"
    class="btn-close"
    data-bs-dismiss="alert"
    aria-label="Close"
    ></button>
    `;
    container.appendChild(alert);
    setTimeout(function() {
        if (alert && alert.parentNode) {
            alert.remove();
        }
    }, 4000);
};
/**
 * =========================================================
 * AJAX FORM HELPER
 * =========================================================
 */
async function submitStorefrontForm(form, options = {}) {
    const button = form.querySelector('button[type="submit"]');
    if (!button) {
        return;
    }
    const originalHtml = button.innerHTML;
    button.disabled = true;
    if (options.loadingHtml) {
        button.innerHTML = options.loadingHtml;
    }
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? {
                    'X-CSRF-TOKEN': csrfToken,
                } : {}),
            },
            body: new FormData(form),
        });
        const data = await response.json();
        if (!response.ok) {
            throw new Error(data.message || options.errorMessage || 'Terjadi kesalahan.');
        }
        if (typeof options.success === 'function') {
            options.success(data);
        }
    } catch (error) {
        window.showStoreNotification(error.message || options.errorMessage || 'Terjadi kesalahan.', 'danger');
    } finally {
        button.disabled = false;
        button.innerHTML = originalHtml;
    }
}
/**
 * =========================================================
 * ADD TO CART
 * =========================================================
 */
document.addEventListener('submit', function(event) {
    const form = event.target.closest('.add-to-cart-form');
    if (!form) {
        return;
    }
    event.preventDefault();
    submitStorefrontForm(form, {
        loadingHtml: `
                <span
                class="spinner-border spinner-border-sm"
                role="status"
                aria-hidden="true"
                ></span>

                <span>
                Menambahkan...
                </span>
                `,
        errorMessage: 'Gagal menambahkan produk ke keranjang.',
        success: async function(data) {
            if (typeof data.cart_count !== 'undefined') {
                window.updateCartBadge(data.cart_count);
            }
            await window.refreshCartDrawer();
            window.showStoreNotification(data.message || 'Produk berhasil ditambahkan ke keranjang.', 'success');
        },
    });
});
/**
 * =========================================================
 * WISHLIST
 * =========================================================
 */
document.addEventListener('submit', function(event) {
    const form = event.target.closest('.wishlist-form');
    if (!form) {
        return;
    }
    event.preventDefault();
    submitStorefrontForm(form, {
        loadingHtml: `<span
                class="spinner-border spinner-border-sm"
                ></span>`,
        errorMessage: 'Gagal menambahkan produk ke wishlist.',
        success: function(data) {
            window.showStoreNotification(data.message || 'Produk ditambahkan ke wishlist.', 'success');
        },
    });
});
/**
 * =========================================================
 * COMPARE
 * =========================================================
 */
document.addEventListener('submit', function(event) {
    const form = event.target.closest('.compare-form');
    if (!form) {
        return;
    }
    event.preventDefault();
    submitStorefrontForm(form, {
        loadingHtml: `<span
                class="spinner-border spinner-border-sm"
                ></span>`,
        errorMessage: 'Gagal menambahkan produk ke compare.',
        success: function(data) {
            window.showStoreNotification(data.message || 'Produk ditambahkan ke compare.', 'success');
        },
    });
});
/**
 * Product Toolbar
 */
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.store-filter-form').forEach(function(form) {
        const categorySelect = form.querySelector('[data-filter-category]');
        const subCategorySelect = form.querySelector('[data-filter-subcategory]');
        if (!subCategorySelect) {
            return;
        }

        function syncSubCategories() {
            /*
             * Category page:
             *
             * Tidak mempunyai category select karena
             * category sudah ditentukan oleh URL.
             */
            if (!categorySelect) {
                subCategorySelect.disabled = false;
                return;
            }
            const selectedCategory = categorySelect.value.trim();
            /*
             * Belum memilih category.
             */
            if (selectedCategory === '') {
                subCategorySelect.value = '';
                subCategorySelect.disabled = true;
                subCategorySelect.querySelectorAll('option[data-category]').forEach(function(option) {
                    option.hidden = true;
                });
                return;
            }
            /*
             * Category sudah dipilih.
             */
            subCategorySelect.disabled = false;
            let selectedSubCategoryIsValid = false;
            subCategorySelect.querySelectorAll('option[data-category]').forEach(function(option) {
                const belongsToCategory = option.dataset.category === selectedCategory;
                option.hidden = !belongsToCategory;
                if (option.selected && belongsToCategory) {
                    selectedSubCategoryIsValid = true;
                }
            });
            /*
             * Jika category berubah dan subcategory
             * sebelumnya milik category lain,
             * reset subcategory.
             */
            if (!selectedSubCategoryIsValid) {
                subCategorySelect.value = '';
            }
        }
        if (categorySelect) {
            categorySelect.addEventListener('change', syncSubCategories);
        }
        /*
         * Initial state.
         */
        syncSubCategories();
    });
});