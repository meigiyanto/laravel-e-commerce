/**
 * =========================================================
 * CART BADGE
 * =========================================================
 */

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
