/**
 * ========================================================= * CART
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
