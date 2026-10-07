import * as bootstrap from 'bootstrap';

/**
 * =========================================================
 * MOBILE STICKY NAVBAR & BACK TO TOP
 * =========================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    const mobileTopbar = document.querySelector(
        '.store-mobile-topbar'
    );

    const backToTop = document.querySelector(
        '.store-back-to-top'
    );

    if (!mobileTopbar && !backToTop) {
        return;
    }

    /*
     * Mobile sticky navbar muncul
     * setelah user scroll lebih dari 120px.
     */
    const mobileScrollThreshold = 120;

    /*
     * Back to top muncul
     * setelah user scroll lebih dari 300px.
     */
    const backToTopThreshold = 300;

    let ticking = false;

    function updateScrollUI() {
        const scrollY =
            window.scrollY ||
            window.pageYOffset ||
            0;

        const isMobile =
            window.innerWidth <= 767.98;

        /*
         * Mobile sticky top navbar
         */
        if (mobileTopbar) {
            mobileTopbar.classList.toggle(
                'is-visible',
                isMobile &&
                scrollY > mobileScrollThreshold
            );
        }

        /*
         * Back to top
         */
        if (backToTop) {
            backToTop.classList.toggle(
                'is-visible',
                scrollY > backToTopThreshold
            );
        }

        ticking = false;
    }

    function requestScrollUpdate() {
        if (ticking) {
            return;
        }

        window.requestAnimationFrame(
            updateScrollUI
        );

        ticking = true;
    }

    /*
     * Scroll
     */
    window.addEventListener(
        'scroll',
        requestScrollUpdate,
        {
            passive: true,
        }
    );

    /*
     * Resize
     */
    window.addEventListener(
        'resize',
        requestScrollUpdate
    );

    /*
     * Back to top click
     */
    if (backToTop) {
        backToTop.addEventListener(
            'click',
            function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            }
        );
    }

    /*
     * Initial state
     */
    updateScrollUI();
});

/**
 * =========================================================
 * BACK TO TOP
 * =========================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    const mobileTopbar = document.querySelector(
        '.store-mobile-topbar'
    );

    const backToTop = document.querySelector(
        '.store-back-to-top'
    );

    if (!mobileTopbar && !backToTop) {
        return;
    }


    /*
     * Navbar mobile mulai muncul
     * setelah scroll 120px.
     */
    const mobileScrollThreshold = 120;


    /*
     * Back to top mulai muncul
     * setelah scroll 300px.
     */
    const backToTopThreshold = 300;


    let ticking = false;


    /**
     * Update tampilan berdasarkan
     * posisi scroll.
     */
    function updateScrollUI() {
        const scrollY =
            window.scrollY ||
            window.pageYOffset;


        /*
         * MOBILE STICKY NAVBAR
         */
        if (mobileTopbar) {
            mobileTopbar.classList.toggle(
                'is-visible',
                window.innerWidth <= 767.98 &&
                scrollY > mobileScrollThreshold
            );
        }


        /*
         * BACK TO TOP
         */
        if (backToTop) {
            backToTop.classList.toggle(
                'is-visible',
                scrollY > backToTopThreshold
            );
        }


        ticking = false;
    }


    /**
     * Gunakan requestAnimationFrame
     * agar event scroll tetap ringan.
     */
    function requestScrollUpdate() {
        if (!ticking) {
            window.requestAnimationFrame(
                updateScrollUI
            );

            ticking = true;
        }
    }


    /*
     * Scroll listener.
     */
    window.addEventListener(
        'scroll',
        requestScrollUpdate,
        {
            passive: true,
        }
    );


    /*
     * Resize listener.
     *
     * Penting ketika user berpindah
     * dari mobile ke desktop.
     */
    window.addEventListener(
        'resize',
        requestScrollUpdate
    );


    /**
     * BACK TO TOP ACTION
     */
    if (backToTop) {
        backToTop.addEventListener(
            'click',
            function () {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            }
        );
    }


    /*
     * Jalankan sekali saat halaman
     * pertama kali dibuka.
     */
    updateScrollUI();
});

/**
 * =========================================================
 * MOBILE CART DRAWER
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
    const cartDrawer = document.getElementById(
        'storeMobileCartDrawer'
    );

    if (!cartDrawer) {
        return;
    }

    cartDrawer
        .querySelectorAll('.store-mobile-cart-quantity-form')
        .forEach(function (form) {

            const input = form.querySelector(
                '.store-mobile-cart-quantity-input'
            );

            const decreaseButton = form.querySelector(
                '[data-mobile-cart-decrease]'
            );

            const increaseButton = form.querySelector(
                '[data-mobile-cart-increase]'
            );

            if (!input) {
                return;
            }

            function getQuantity() {
                const value = parseInt(
                    input.value,
                    10
                );

                return Number.isNaN(value)
                    ? 1
                    : value;
            }

            function getMax() {
                const value = parseInt(
                    input.getAttribute('max'),
                    10
                );

                return Number.isNaN(value)
                    ? Infinity
                    : value;
            }

            function submitQuantity() {
                const quantity = Math.min(
                    Math.max(getQuantity(), 1),
                    getMax()
                );

                input.value = quantity;

                form.submit();
            }

            if (decreaseButton) {
                decreaseButton.addEventListener(
                    'click',
                    function () {
                        const quantity =
                            getQuantity();

                        if (quantity <= 1) {
                            return;
                        }

                        input.value =
                            quantity - 1;

                        submitQuantity();
                    }
                );
            }

            if (increaseButton) {
                increaseButton.addEventListener(
                    'click',
                    function () {
                        const quantity =
                            getQuantity();

                        const max =
                            getMax();

                        if (quantity >= max) {
                            return;
                        }

                        input.value =
                            quantity + 1;

                        submitQuantity();
                    }
                );
            }

            input.addEventListener(
                'change',
                function () {
                    submitQuantity();
                }
            );
        });
});

/**
 * =========================================================
 * MOBILE SEARCH DRAWER
 * =========================================================
 */
document.addEventListener('DOMContentLoaded', function () {
    const searchDrawer = document.getElementById(
        'storeMobileSearchDrawer'
    );

    const searchInput = document.getElementById(
        'storeMobileSearchInput'
    );

    if (!searchDrawer || !searchInput) {
        return;
    }

    const clearButton = searchDrawer.querySelector(
        '[data-mobile-search-clear]'
    );

    function updateClearButton() {
        if (!clearButton) {
            return;
        }

        clearButton.hidden = searchInput.value.trim() === '';
    }

    searchInput.addEventListener(
        'input',
        updateClearButton
    );

    updateClearButton();

    if (clearButton) {
        clearButton.addEventListener('click', function () {
            searchInput.value = '';
            updateClearButton();
            searchInput.focus();
        });
    }

    const searchForm = searchDrawer.querySelector(
        '.store-mobile-search-form'
    );

    if (searchForm) {
        searchForm.addEventListener(
            'submit',
            function (event) {
                const value = searchInput.value.trim();

                if (value === '') {
                    event.preventDefault();
                    searchInput.focus();
                    return;
                }

                searchInput.value = value;
            }
        );
    }

    const observer = new MutationObserver(
        function () {
            const isOpen =
                searchDrawer.getAttribute('aria-hidden') === 'false';

            if (!isOpen) {
                return;
            }

            window.setTimeout(function () {
                searchInput.focus();

                const value = searchInput.value;

                if (value) {
                    searchInput.setSelectionRange(
                        value.length,
                        value.length
                    );
                }
            }, 80);
        }
    );

    observer.observe(searchDrawer, {
        attributes: true,
        attributeFilter: ['aria-hidden'],
    });
});

/**
 * =========================================================
 * MOBILE CATEGORY ACCORDION
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
    const categoryToggles = document.querySelectorAll(
        '.store-mobile-category-toggle'
    );

    if (!categoryToggles.length) {
        return;
    }

    categoryToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const targetId = toggle.getAttribute(
                'aria-controls'
            );

            if (!targetId) {
                return;
            }

            const target =
                document.getElementById(targetId);

            if (!target) {
                return;
            }

            const isExpanded =
                toggle.getAttribute('aria-expanded') === 'true';

            /*
             * Tutup category lain.
             */
            categoryToggles.forEach(function (otherToggle) {
                if (otherToggle === toggle) {
                    return;
                }

                const otherTargetId =
                    otherToggle.getAttribute('aria-controls');

                const otherTarget =
                    document.getElementById(otherTargetId);

                otherToggle.setAttribute(
                    'aria-expanded',
                    'false'
                );

                if (otherTarget) {
                    otherTarget.setAttribute(
                        'aria-hidden',
                        'true'
                    );
                }
            });

            /*
             * Toggle category aktif.
             */
            toggle.setAttribute(
                'aria-expanded',
                String(!isExpanded)
            );

            target.setAttribute(
                'aria-hidden',
                String(isExpanded)
            );
        });
    });
});

/**
 * =========================================================
 * MOBILE DRAWER SYSTEM
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
    const drawerTriggers = document.querySelectorAll(
        '[data-mobile-drawer]'
    );

    const drawers = document.querySelectorAll(
        '.store-mobile-drawer'
    );

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

        document.body.classList.add(
            'store-mobile-drawer-open'
        );

        activeDrawer = drawer;
        activeTrigger = trigger;

        const closeButton = drawer.querySelector(
            '[data-mobile-drawer-close]'
        );

        if (closeButton) {
            window.setTimeout(function () {
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

        if (!document.querySelector(
            '.store-mobile-drawer[aria-hidden="false"]'
        )) {
            document.body.classList.remove(
                'store-mobile-drawer-open'
            );
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
    drawerTriggers.forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            const drawerId =
                trigger.getAttribute('aria-controls');

            if (!drawerId) {
                return;
            }

            const drawer =
                document.getElementById(drawerId);

            if (!drawer) {
                return;
            }

            const isOpen =
                drawer.getAttribute('aria-hidden') === 'false';

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
    drawers.forEach(function (drawer) {
        const closeButton = drawer.querySelector(
            '[data-mobile-drawer-close]'
        );

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                closeActiveDrawer();
            });
        }

        /**
         * -----------------------------------------------------
         * CLICK OVERLAY
         * -----------------------------------------------------
         */
        drawer.addEventListener('click', function (event) {
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
    document.addEventListener('keydown', function (event) {
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
    drawers.forEach(function (drawer) {
        drawer.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                closeDrawer(drawer, activeTrigger);
            });
        });
    });
});

/**
 * =========================================================
 * DESKTOP DEPARTMENT MENU
 * ========================================================= */
document.addEventListener('DOMContentLoaded', function () {
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

    toggle.addEventListener('click', function (event) {
        event.preventDefault();

        if (department.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    document.addEventListener('click', function (event) {
        if (!department.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
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
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.quantity-form').forEach(function (form) {
        const input = form.querySelector('.quantity-input');
        const minusButton = form.querySelector('.quantity-decrease');
        const plusButton = form.querySelector('.quantity-increase');

        if (!input || !minusButton || !plusButton) {
            return;
        }

        updateButtons(form);

        minusButton.addEventListener('click', function () {
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

        plusButton.addEventListener('click', function () {
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

        input.addEventListener('change', function () {
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
        const minusButton = form.querySelector('.quantity-decrease');
        const plusButton = form.querySelector('.quantity-increase');

        const productId = form.dataset.productId;

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

        if (!csrfToken || !productId) {
            return;
        }

        input.disabled = true;
        minusButton.disabled = true;
        plusButton.disabled = true;

        form.classList.add('opacity-75');

        try {
            const response = await fetch(
                form.action,
                {
                    method: 'POST',

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },

                    body: new URLSearchParams({
                        _token: csrfToken,
                        _method: 'PATCH',
                        quantity: quantity,
                    }),
                }
            );

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(
                    data.message ||
                    'Gagal memperbarui keranjang.'
                );
            }

            input.value = data.quantity;

            /*
             * Update subtotal produk.
             *
             * Subtotal berada di dalam .store-cart-item
             * yang mempunyai data-product-id.
             */
            const cartItem = document.querySelector(
                `.store-cart-item[data-product-id="${productId}"]`
            );

            if (cartItem) {
                const subtotal = cartItem.querySelector(
                    '.cart-item-subtotal'
                );

                if (subtotal) {
                    subtotal.textContent =
                        formatRupiah(data.subtotal);
                }
            }

            /*
             * Update semua total keranjang.
             */
            document
                .querySelectorAll(
                    '.cart-total, .cart-grand-total'
                )
                .forEach(function (element) {
                    element.textContent =
                        formatRupiah(data.total);
                });

            /*
             * Update jumlah item.
             */
            document
                .querySelectorAll('.cart-item-count')
                .forEach(function (element) {
                    element.textContent =
                        data.item_count;
                });

            /*
             * Update cart badge pada navbar.
             */
            if (typeof window.updateCartBadge === 'function') {
                window.updateCartBadge(
                    data.cart_count
                );
            }

            /*
             * Tampilkan notifikasi.
             */
            if (typeof window.showStoreNotification === 'function') {
                window.showStoreNotification(
                    data.message,
                    'success'
                );
            }

        } catch (error) {
            /*
             * Jika AJAX gagal, kembalikan
             * input ke kondisi server sebelumnya.
             */
            window.location.reload();

            return;

        } finally {
            input.disabled = false;
            form.classList.remove('opacity-75');

            updateButtons(form);
        }
    }


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


    function formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString(
            'id-ID'
        );
    }
});

window.updateCartBadge = function (count) {
    const badges = document.querySelectorAll(
        '#cart-count-badge, .store-mobile-bottom .store-badge'
    );

    const value = Number(count) || 0;

    badges.forEach(function (badge) {
        badge.textContent = value;

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

window.showStoreNotification = function (
    message,
    type = 'success'
) {
    let container = document.getElementById(
        'store-notification-container'
    );

    if (!container) {
        container = document.createElement('div');

        container.id =
            'store-notification-container';

        container.className =
            'position-fixed top-0 end-0 p-3';

        container.style.zIndex = '2000';

        document.body.appendChild(container);
    }

    const alert = document.createElement('div');

    alert.className =
        `alert alert-${type} alert-dismissible fade show shadow`;

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

    setTimeout(function () {
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

async function submitStorefrontForm(
    form,
    options = {}
) {
    const button = form.querySelector(
        'button[type="submit"]'
    );

    if (!button) {
        return;
    }

    const originalHtml =
        button.innerHTML;

    button.disabled = true;

    if (options.loadingHtml) {
        button.innerHTML =
            options.loadingHtml;
    }

    try {
        const csrfToken =
            document.querySelector(
                'meta[name="csrf-token"]'
            )?.getAttribute('content');

        const response = await fetch(
            form.action,
            {
                method: form.method || 'POST',

                headers: {
                    Accept:
                        'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest',

                    ...(csrfToken
                        ? {
                              'X-CSRF-TOKEN':
                                  csrfToken,
                          }
                        : {}),
                },

                body:
                    new FormData(form),
            }
        );

        const data =
            await response.json();

        if (!response.ok) {
            throw new Error(
                data.message ||
                options.errorMessage ||
                'Terjadi kesalahan.'
            );
        }

        if (
            typeof options.success ===
            'function'
        ) {
            options.success(data);
        }

    } catch (error) {
        window.showStoreNotification(
            error.message ||
            options.errorMessage ||
            'Terjadi kesalahan.',
            'danger'
        );
    } finally {
        button.disabled = false;

        button.innerHTML =
            originalHtml;
    }
}


/**
 * =========================================================
 * ADD TO CART
 * =========================================================
 */

document.addEventListener(
    'submit',
    function (event) {
        const form =
            event.target.closest(
                '.add-to-cart-form'
            );

        if (!form) {
            return;
        }

        event.preventDefault();

        submitStorefrontForm(
            form,
            {
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

                errorMessage:
                    'Gagal menambahkan produk ke keranjang.',

                success: function (data) {
                    if (
                        typeof data.cart_count !==
                        'undefined'
                    ) {
                        window.updateCartBadge(
                            data.cart_count
                        );
                    }

                    window.showStoreNotification(
                        data.message ||
                        'Produk berhasil ditambahkan ke keranjang.',
                        'success'
                    );
                },
            }
        );
    }
);


/**
 * =========================================================
 * WISHLIST
 * =========================================================
 */

document.addEventListener(
    'submit',
    function (event) {
        const form =
            event.target.closest(
                '.wishlist-form'
            );

        if (!form) {
            return;
        }

        event.preventDefault();

        submitStorefrontForm(
            form,
            {
                loadingHtml: `
                    <span
                        class="spinner-border spinner-border-sm"
                    ></span>
                `,

                errorMessage:
                    'Gagal menambahkan produk ke wishlist.',

                success: function (data) {
                    window.showStoreNotification(
                        data.message ||
                        'Produk ditambahkan ke wishlist.',
                        'success'
                    );
                },
            }
        );
    }
);


/**
 * =========================================================
 * COMPARE
 * =========================================================
 */

document.addEventListener(
    'submit',
    function (event) {
        const form =
            event.target.closest(
                '.compare-form'
            );

        if (!form) {
            return;
        }

        event.preventDefault();

        submitStorefrontForm(
            form,
            {
                loadingHtml: `
                    <span
                        class="spinner-border spinner-border-sm"
                    ></span>
                `,

                errorMessage:
                    'Gagal menambahkan produk ke compare.',

                success: function (data) {
                    window.showStoreNotification(
                        data.message ||
                        'Produk ditambahkan ke compare.',
                        'success'
                    );
                },
            }
        );
    }
);

/**
 * Product Toolbar
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.store-filter-form').forEach(function (form) {
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
                subCategorySelect.querySelectorAll('option[data-category]').forEach(function (option) {
                        option.hidden = true;
                    });
                return;
            }

            /*
             * Category sudah dipilih.
             */
            subCategorySelect.disabled = false;
            let selectedSubCategoryIsValid = false;
            subCategorySelect.querySelectorAll('option[data-category]').forEach(function (option) {
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