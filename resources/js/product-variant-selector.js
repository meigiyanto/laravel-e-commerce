document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.product-purchase-form');
    const dataNode = document.getElementById('productVariantData');

    if (!form || !dataNode) return;

    let variants;

    try {
        variants = JSON.parse(dataNode.textContent || '[]');
    } catch {
        console.error('Data varian produk tidak valid.');
        return;
    }

    const selector = document.getElementById('productVariantSelector');
    const variantInput = form.querySelector('[name="product_variant_id"]');
    const quantityInput = form.querySelector('[name="quantity"]');
    const priceNode = document.querySelector('.product-detail-price');
    const stockNode = document.querySelector('.product-stock');
    const feedback = document.getElementById('productVariantFeedback');
    const submitButton = form.querySelector('[type="submit"]');

    if (!variantInput || !quantityInput || !variants.length) return;

    const selected = {};
    const formatPrice = value =>
        'Rp ' + new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0
        }).format(Number(value || 0));

    const attributeNames = [
        ...new Set(
            variants.flatMap(variant =>
                (variant.attributes || [])
                    .map(attribute => attribute.name)
                    .filter(Boolean)
            )
        )
    ];

    const isColorAttribute = name => /warna|color/i.test(name);

    function findMatchingVariant() {
        if (!attributeNames.every(name => selected[name] !== undefined)) {
            return null;
        }

        return variants.find(variant => {
            const attributes = variant.attributes || [];

            return attributes.length === attributeNames.length &&
                attributeNames.every(name =>
                    attributes.some(attribute =>
                        attribute.name === name &&
                        String(attribute.value) === selected[name]
                    )
                );
        }) || null;
    }

    function updateVariant() {
        const variant = findMatchingVariant();

        if (!variant) {
            variantInput.value = '';
            quantityInput.value = '1';
            quantityInput.max = '1';
            quantityInput.disabled = false;

            if (priceNode) priceNode.textContent = 'Pilih varian';
            if (stockNode) stockNode.textContent = 'Stok ditampilkan setelah varian dipilih.';

            if (feedback) {
                const missing = attributeNames.filter(
                    name => selected[name] === undefined
                );

                feedback.textContent = missing.length
                    ? 'Pilih ' + missing.join(', ') + ' terlebih dahulu.'
                    : 'Kombinasi opsi ini tidak tersedia.';
            }

            if (submitButton) submitButton.disabled = true;
            return;
        }

        const stock = Math.max(0, Number(variant.stock || 0));

        variantInput.value = String(variant.id);
        quantityInput.max = String(Math.max(1, stock));
        quantityInput.value = '1';
        quantityInput.disabled = stock < 1;

        if (priceNode) {
            priceNode.textContent = formatPrice(variant.price);
        }

        if (stockNode) {
            stockNode.textContent = stock > 0
                ? `${stock} unit tersedia`
                : 'Stok varian habis';
        }

        if (feedback) {
            feedback.textContent = stock > 0
                ? `${variant.name} dipilih.`
                : 'Varian ini sedang habis.';
        }

        if (submitButton) submitButton.disabled = stock < 1;
    }

    if (selector) {
        attributeNames.forEach(name => {
            const group = [...selector.querySelectorAll('[data-options-for]')]
                .find(node => node.dataset.optionsFor === name);

            if (!group) return;

            const uniqueValues = new Map();

            variants.forEach(variant => {
                (variant.attributes || [])
                    .filter(attribute => attribute.name === name)
                    .forEach(attribute => {
                        uniqueValues.set(String(attribute.value), attribute);
                    });
            });

            uniqueValues.forEach(attribute => {
                const value = String(attribute.value);
                const button = document.createElement('button');

                button.type = 'button';
                button.className = 'product-variant-option';
                button.dataset.value = value;
                button.setAttribute('aria-pressed', 'false');

                if (isColorAttribute(name) && attribute.hex_color) {
                    const swatch = document.createElement('span');

                    swatch.className = 'product-variant-swatch';
                    swatch.style.backgroundColor = attribute.hex_color;
                    swatch.setAttribute('aria-hidden', 'true');

                    button.append(swatch);
                }

                button.append(document.createTextNode(value));

                button.addEventListener('click', () => {
                    selected[name] = value;

                    group.querySelectorAll('.product-variant-option')
                        .forEach(option => {
                            const active = option.dataset.value === value;

                            option.classList.toggle('is-selected', active);
                            option.setAttribute(
                                'aria-pressed',
                                active ? 'true' : 'false'
                            );
                        });

                    updateVariant();
                });

                group.append(button);
            });
        });

        if (submitButton) submitButton.disabled = true;
    } else {
        const variant = variants.find(
            item => String(item.id) === variantInput.value
        ) || variants[0];

        if (variant) {
            variantInput.value = String(variant.id);
            quantityInput.max = String(Math.max(1, Number(variant.stock || 0)));

            if (submitButton) {
                submitButton.disabled = Number(variant.stock || 0) < 1;
            }
        }
    }

    form.addEventListener('submit', event => {
        if (!variantInput.value) {
            event.preventDefault();

            if (feedback) {
                feedback.textContent = 'Pilih varian sebelum menambahkan ke keranjang.';
            }
        }
    });
});
