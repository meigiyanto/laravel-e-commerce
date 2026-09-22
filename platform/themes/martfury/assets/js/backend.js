(function($) {
    'use strict'

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    })

    window.botbleCookieNewsletter = (() => {

        const COOKIE_VALUE = 1
        const COOKIE_NAME = 'botble_cookie_newsletter'
        const COOKIE_DOMAIN = $('div[data-session-domain]').data('session-domain')
        const COOKIE_MODAL = $('#subscribe')
        const COOKIE_MODAL_TIME = COOKIE_MODAL.data('time')

        function newsletterWithCookies(expirationInDays) {
            setCookie(COOKIE_NAME, COOKIE_VALUE, expirationInDays)
        }

        function cookieExists(name) {
            return document.cookie.split('; ').indexOf(name + '=' + COOKIE_VALUE) !== -1
        }

        function hideCookieDialog() {
            if (!cookieExists(COOKIE_NAME) && $('#dont_show_again').is(':checked')) {
                newsletterWithCookies(3)
            } else {
                newsletterWithCookies(1 / 24)
            }
        }

        function setCookie(name, value, expirationInDays) {
            const date = new Date()
            date.setTime(date.getTime() + (expirationInDays * 24 * 60 * 60 * 1000))
            document.cookie = name + '=' + value
                + ';expires=' + date.toUTCString()
                + ';domain=' + COOKIE_DOMAIN
                + ';path=/'
        }

        if (!cookieExists(COOKIE_NAME)) {
            setTimeout(function() {
                if (COOKIE_MODAL.length > 0) {
                    COOKIE_MODAL.addClass('active')
                    $('body').css('overflow', 'hidden')
                }
            }, COOKIE_MODAL_TIME)
        }

        return {
            newsletterWithCookies: newsletterWithCookies,
            hideCookieDialog: hideCookieDialog,
        }
    })()

    let showError = message => {
        Theme.showError(message)
    }

    let showSuccess = message => {
        Theme.showSuccess(message)
    }

    let handleError = data => {
        Theme.handleError(data)
    }

    window.showAlert = (messageType, message) => {
        if (messageType === 'alert-danger') {
            messageType = 'error'
        }

        if (messageType === 'alert-success') {
            messageType = 'success'
        }

        Theme.showNotice(messageType, message)
    }

    let isRTL = $('body').prop('dir') === 'rtl'

    $(document).ready(function() {
        window.onBeforeChangeSwatches = function(data, $attrs) {
            const $product = $attrs.closest('.ps-product--detail')
            const $form = $product.find('.add-to-cart-form')

            $product.find('.error-message').hide()
            $product.find('.success-message').hide()
            $product.find('.number-items-available').html('').hide()

            if (data && data.attributes) {
                $form.find('button[type=submit]').prop('disabled', true).addClass('btn-disabled')
            }
        }

        $(document).ready(function() {
            let originalOnChangeSwatchesSuccess = window.onChangeSwatchesSuccess

            window.onChangeSwatchesSuccess = function(res, $attrs) {
                if (originalOnChangeSwatchesSuccess && typeof originalOnChangeSwatchesSuccess === 'function') {
                    originalOnChangeSwatchesSuccess(res, $attrs)
                }

                if (res && !res.error && res.data) {

                    const data = res.data
                    $('.hidden-product-id').val(data.id)

                    const $product = $('.ps-product--header-sticky')

                    $product.find('[data-bb-value="product-price"]').text(data.display_sale_price)

                    if (data.sale_price !== data.price) {
                        $product.find('[data-bb-value="product-original-price"]').text(data.display_price).show()
                    } else {
                        $product.find('[data-bb-value="product-original-price"]').hide()
                    }
                }
            }
        })

        $('.ps-panel--sidebar').show()
        $('.ps-popup').show()
        $('.menu--product-categories .menu__content').show()

        $('.ps-popup__close').on('click', function(e) {
            e.preventDefault()
            $(this)
                .closest('.ps-popup')
                .removeClass('active')
            $('body').css('overflow', 'auto')

            window.botbleCookieNewsletter.hideCookieDialog()
        })

        $('#subscribe').on('click', function(event) {
            if (!$(event.target).closest('.ps-popup__content').length) {
                $(this).removeClass('active')
                $('body').css('overflow-y', 'auto')

                window.botbleCookieNewsletter.newsletterWithCookies(30)
                $('.ps-popup').removeClass('active').hide()
            }
        })

        $(document).on('click', '.newsletter-form button[type=submit]', function(event) {
            event.preventDefault()
            event.stopPropagation()

            let _self = $(this)

            _self.addClass('button-loading')

            $.ajax({
                type: 'POST',
                cache: false,
                url: _self.closest('form').prop('action'),
                data: new FormData(_self.closest('form')[0]),
                contentType: false,
                processData: false,
                success: res => {
                    _self.removeClass('button-loading')

                    if (typeof refreshRecaptcha !== 'undefined') {
                        refreshRecaptcha()
                    }

                    if (!res.error) {
                        window.botbleCookieNewsletter.newsletterWithCookies(30)
                        _self.closest('form').find('input[type=email]').val('')
                        showSuccess(res.message)
                        setTimeout(() => {
                            _self.closest('.modal-body').find('button[data-bs-dismiss="modal"]').trigger('click')
                            $('.ps-popup').removeClass('active').hide()
                        }, 2000)
                    } else {
                        showError(res.message)
                    }
                },
                error: res => {
                    if (typeof refreshRecaptcha !== 'undefined') {
                        refreshRecaptcha()
                    }
                    _self.removeClass('button-loading')
                    handleError(res)
                },
            })
        })

        $(document).on('click', '.ps-form--download-app button[type=submit]', event => {
            event.preventDefault()
            let _self = $(event.currentTarget)
            _self.addClass('button-loading')

            $.ajax({
                url: _self.closest('form').prop('action'),
                data: _self.closest('form').serialize(),
                type: 'POST',
                success: res => {
                    if (res.error) {
                        _self.removeClass('button-loading')
                        Theme.showError(res.message)
                        return false
                    }

                    Theme.showSuccess(res.message)
                    _self.removeClass('button-loading')
                },
                error: res => {
                    _self.removeClass('button-loading')
                    handleError(res, _self.closest('form'))
                },
            })
        })

        const $layoutShop = $('.ps-layout--shop')
        if ($layoutShop.length > 0) {
            $(document).on('click', '#products-filter-sidebar', function(e) {
                e.preventDefault()
                $layoutShop.find('.ps-layout__left').toggleClass('active')
            })

            $('.ps-layout__left .ps-filter__header .ps-btn--close').on(
                'click',
                function(e) {
                    e.preventDefault()
                    $layoutShop.find('.ps-layout__left').toggleClass('active')
                },
            )
            $(document).on('click', '.ps-layout__left .screen-darken', function(e) {
                e.preventDefault()
                $layoutShop.find('.ps-layout__left').toggleClass('active')
            })
            $('.ps-select-shop-sort').on('change', function(e) {
                $(document).find('.bb-product-form-filter').find('input[name=sort-by]').val($(e.currentTarget).val())
                $(document).find('.bb-product-form-filter').trigger('submit')
            })
        }

        const $formSearch = $(document).find('.bb-product-form-filter')
        let isReadySubmitTrigger = true
        $formSearch.on('change', '.widget_shop .product-filter-item', function() {
            if (isReadySubmitTrigger) {
                $(this).closest('form').trigger('submit')
            }
        })

        $(document).on('click', '.ps-shopping .products-layout li:not(.active) a', function(e) {
            e.preventDefault()
            const $this = $(e.currentTarget)
            $this.closest('ul').find('li').removeClass('active')
            $this.closest('li').addClass('active')
            $(document).find('.bb-product-form-filter').find('input[name=layout]').val($this.data('layout'))
            $(document).find('.bb-product-form-filter').trigger('submit')
        })

        $(document).on('click', '.js-add-to-wishlist-button', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'POST',
                success: res => {

                    if (res.error) {
                        _self.removeClass('button-loading')
                        Theme.showError(res.message)
                        return false
                    }

                    Theme.showSuccess(res.message)

                    $('.btn-wishlist span i').text(res.data.count)

                    _self.removeClass('button-loading').removeClass('js-add-to-wishlist-button').addClass('js-remove-from-wishlist-button active')
                },
                error: res => {
                    _self.removeClass('button-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.js-remove-from-wishlist-button', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'DELETE',
                success: res => {

                    if (res.error) {
                        _self.removeClass('button-loading')
                        Theme.showError(res.message)
                        return false
                    }

                    Theme.showSuccess(res.message)

                    $('.btn-wishlist span i').text(res.data.count)

                    _self.closest('tr').remove()
                    _self.removeClass('button-loading').removeClass('js-remove-from-wishlist-button active').addClass('js-add-to-wishlist-button')
                },
                error: res => {
                    _self.removeClass('button-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.js-add-to-compare-button', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'POST',
                success: res => {

                    if (res.error) {
                        _self.removeClass('button-loading')
                        Theme.showError(res.message)
                        return false
                    }

                    Theme.showSuccess(res.message)

                    $('.btn-compare span i').text(res.data.count)

                    _self.removeClass('button-loading').removeClass('js-add-to-compare-button').addClass('js-remove-from-compare-button active')
                },
                error: res => {
                    _self.removeClass('button-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.js-remove-from-compare-button', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'DELETE',
                success: res => {

                    if (res.error) {
                        _self.removeClass('button-loading')
                        Theme.showError(res.message)
                        return false
                    }

                    _self.removeClass('button-loading').addClass('js-add-to-compare-button').removeClass('js-remove-from-compare-button active')

                    Theme.showSuccess(res.message)

                    $('.btn-compare span i').text(res.data.count)

                    $('.ps-table--compare').load(window.location.href + ' .ps-table--compare > *')
                },
                error: res => {
                    _self.removeClass('button-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.ps-product .ps-product__actions .add-to-cart-button', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.prop('disabled', true).addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'POST',
                data: {
                    id: _self.data('id'),
                },
                dataType: 'json',
                success: res => {
                    _self.prop('disabled', false).removeClass('button-loading').addClass('active')

                    if (res.error) {
                        Theme.showError(res.message)
                        if (res.data.next_url !== undefined) {
                            setTimeout(() => {
                                window.location.href = res.data.next_url
                            }, 500);
                        }

                        return false
                    }

                    Theme.showSuccess(res.message)

                    if (res.data.next_url !== undefined) {
                        window.location.href = res.data.next_url
                    } else {
                        $.ajax({
                            url: window.siteUrl + '/ajax/cart',
                            method: 'GET',
                            success: response => {
                                if (!response.error) {
                                    $('.ps-cart--mobile').html(response.data.html)
                                    $('.btn-shopping-cart span i').text(response.data.count)

                                    if (typeof Theme.lazyLoadInstance !== 'undefined') {
                                        Theme.lazyLoadInstance.update()
                                    }
                                }
                            },
                        })
                    }
                },
                error: res => {
                    _self.prop('disabled', false).removeClass('button-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.remove-cart-item', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.closest('.ps-product--cart-mobile').addClass('content-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'GET',
                success: res => {
                    _self.closest('.ps-product--cart-mobile').removeClass('content-loading')

                    if (res.error) {
                        Theme.showError(res.message)
                        return false
                    }

                    $.ajax({
                        url: window.siteUrl + '/ajax/cart',
                        method: 'GET',
                        success: response => {
                            if (!response.error) {
                                $('.ps-cart--mobile').html(response.data.html)
                                $('.btn-shopping-cart span i').text(response.data.count)
                                Theme.showSuccess(res.message)

                                if (typeof Theme.lazyLoadInstance !== 'undefined') {
                                    Theme.lazyLoadInstance.update()
                                }
                            }
                        },
                    })
                },
                error: res => {
                    _self.closest('.ps-product--cart-mobile').removeClass('content-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.remove-cart-button', function(event) {
            event.preventDefault()
            let _self = $(this)

            _self.closest('.ps-table--shopping-cart').addClass('content-loading')

            $.ajax({
                url: _self.data('url'),
                method: 'GET',
                success: function(res) {

                    if (res.error) {
                        Theme.showError(res.message)
                        return false
                    }

                    $('.ps-shopping-cart').load(window.location.href + ' .ps-shopping-cart > *', function() {
                        _self.closest('.ps-table--shopping-cart').removeClass('content-loading')
                        Theme.showSuccess(res.message)
                    })

                    $.ajax({
                        url: window.siteUrl + '/ajax/cart',
                        method: 'GET',
                        success: response => {
                            if (!response.error) {
                                $('.ps-cart--mobile').html(response.data.html)
                                $('.btn-shopping-cart span i').text(response.data.count)

                                if (typeof Theme.lazyLoadInstance !== 'undefined') {
                                    Theme.lazyLoadInstance.update()
                                }
                            }
                        },
                    })
                },
                error: res => {
                    _self.closest('.ps-table--shopping-cart').removeClass('content-loading')
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.add-to-cart-form button[type=submit]', function(event) {
            event.preventDefault()
            event.stopPropagation()

            let _self = $(this)

            if (!$('.hidden-product-id').val()) {
                _self.prop('disabled', true).addClass('btn-disabled')
                return
            }

            _self.prop('disabled', true).addClass('btn-disabled').addClass('button-loading')

            _self.closest('form').find('.error-message').hide()
            _self.closest('form').find('.success-message').hide()

            let $form = _self.closest('form')

            let data = $form.serializeArray()
            data.push({ name: 'checkout', value: _self.prop('name') === 'checkout' ? 1 : 0 })

            $.ajax({
                type: 'POST',
                url: $form.prop('action'),
                data: $.param(data),
                success: res => {
                    _self.prop('disabled', false).removeClass('btn-disabled').removeClass('button-loading')

                    if (res.error) {
                        _self.removeClass('button-loading')
                        Theme.showError(res.message)

                        if (res.data.next_url !== undefined) {
                            window.location.href = res.data.next_url
                        }

                        return false
                    }

                    Theme.showSuccess(res.message)

                    if (res.data.next_url !== undefined) {
                        window.location.href = res.data.next_url
                    } else {
                        $.ajax({
                            url: window.siteUrl + '/ajax/cart',
                            method: 'GET',
                            success: function(response) {
                                if (!response.error) {
                                    $('.ps-cart--mobile').html(response.data.html)
                                    $('.btn-shopping-cart span i').text(response.data.count)

                                    if (typeof Theme.lazyLoadInstance !== 'undefined') {
                                        Theme.lazyLoadInstance.update()
                                    }
                                }
                            },
                        })
                    }
                },
                error: res => {
                    _self.prop('disabled', false).removeClass('btn-disabled').removeClass('button-loading')
                    handleError(res, _self.closest('form'))
                },
            })
        })

        function ajaxUpdateCart(_self) {
            _self.closest('.ps-table--shopping-cart').addClass('content-loading')

            $.ajax({
                type: 'POST',
                cache: false,
                url: _self.closest('form').prop('action'),
                data: new FormData(_self.closest('form')[0]),
                contentType: false,
                processData: false,
                success: res => {
                    if (res.error) {
                        Theme.showError(res.message)
                        _self.closest('.ps-table--shopping-cart').removeClass('content-loading')
                        _self.closest('.product__qty').find('.qty-input').val(res.data.count).prop('placeholder', res.data.count)
                        return false
                    }

                    $('.ps-section--shopping').load(window.location.href + ' .ps-section--shopping > *', function () {
                        if (typeof Theme.lazyLoadInstance !== 'undefined') {
                            Theme.lazyLoadInstance.update()
                        }
                    })

                    $.ajax({
                        url: window.siteUrl + '/ajax/cart',
                        method: 'GET',
                        success: response => {
                            if (!response.error) {
                                _self.closest('.ps-table--shopping-cart').removeClass('content-loading')
                                $('.ps-cart--mobile').html(response.data.html)
                                $('.btn-shopping-cart span i').text(response.data.count)
                                Theme.showSuccess(res.message)

                                if (typeof Theme.lazyLoadInstance !== 'undefined') {
                                    Theme.lazyLoadInstance.update()
                                }
                            }
                        },
                        error: res => {
                            _self.closest('.ps-table--shopping-cart').removeClass('content-loading')
                            Theme.showError(res.message)
                        },
                    })
                },
                error: res => {
                    _self.closest('.ps-table--shopping-cart').removeClass('content-loading')
                    Theme.showError(res.message)
                },
            })
        }

        $(document).on('change', '.submit-form-on-change', function() {
            $(this).closest('form').submit()
        })

        $('.form-coupon-wrapper .coupon-code').keypress(event => {
            if (event.keyCode === 13) {
                $('.apply-coupon-code').trigger('click')
                event.preventDefault()
                event.stopPropagation()
                return false
            }
        })

        $(document).on('click', '.btn-apply-coupon-code', event => {
            event.preventDefault()
            let _self = $(event.currentTarget)
            _self.prop('disabled', true).addClass('btn-disabled').addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                type: 'POST',
                data: {
                    coupon_code: _self.closest('.form-coupon-wrapper').find('.coupon-code').val(),
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                success: res => {
                    if (!res.error) {
                        $('.ps-section--shopping').load(window.location.href + '?applied_coupon=1 .ps-section--shopping > *', function() {
                            _self.prop('disabled', false).removeClass('btn-disabled').removeClass('button-loading')
                            Theme.showSuccess(res.message)
                        })
                    } else {
                        Theme.showError(res.message)
                        _self.prop('disabled', false).removeClass('btn-disabled').removeClass('button-loading')
                    }
                },
                error: data => {
                    Theme.handleError(data)
                    _self.prop('disabled', false).removeClass('btn-disabled').removeClass('button-loading')
                },
            })
        })

        $(document).on('click', '.btn-remove-coupon-code', event => {
            event.preventDefault()
            let _self = $(event.currentTarget)
            let buttonText = _self.text()
            _self.text(_self.data('processing-text'))

            $.ajax({
                url: _self.data('url'),
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                },
                success: res => {
                    if (!res.error) {
                        $('.ps-section--shopping').load(window.location.href + ' .ps-section--shopping > *', function() {
                            _self.text(buttonText)
                        })
                    } else {
                        Theme.showError(res.message)
                        _self.text(buttonText)
                    }
                },
                error: data => {
                    Theme.handleError(data)

                    _self.text(buttonText)
                },
            })
        })

        $(document).on('click', '.js-quick-view-button', event => {
            event.preventDefault()
            let _self = $(event.currentTarget)
            _self.addClass('button-loading')

            $.ajax({
                url: _self.data('url'),
                type: 'GET',
                success: res => {
                    if (!res.error) {
                        $('#product-quickview .ps-product--quickview').html(res.data)

                        setTimeout(function() {
                            if (typeof EcommerceApp !== 'undefined') {
                                EcommerceApp.initProductGallery(true)
                            }
                        }, 200)

                        $('#product-quickview').modal('show')

                        if (typeof Theme.lazyLoadInstance !== 'undefined') {
                            Theme.lazyLoadInstance.update()
                        }

                        document.dispatchEvent(new CustomEvent('ecommerce.quick-view.initialized'))
                    }
                    _self.removeClass('button-loading')
                },
                error: () => {
                    _self.removeClass('button-loading')
                },
            })
        })

        $(document).on('click', '.header__extra.btn-shopping-cart', function(e) {
            e.preventDefault()
        })

        $(document).on('click', '.product__qty .up', function(event) {
            event.preventDefault()
            event.stopPropagation()
            let currentVal = parseInt($(this).closest('.product__qty').find('.qty-input').val(), 10)
            $(this).closest('.product__qty').find('.qty-input').val(currentVal + 1).prop('placeholder', currentVal + 1).trigger('change')

            if ($(this).closest('.ps-table--shopping-cart').length) {
                ajaxUpdateCart($(this))
            }
        })

        $(document).on('click', '.product__qty .down', function(event) {
            event.preventDefault()
            event.stopPropagation()
            let currentVal = parseInt($(this).closest('.product__qty').find('.qty-input').val(), 10)
            if (currentVal > 1) {
                $(this).closest('.product__qty').find('.qty-input').val(currentVal - 1).prop('placeholder', currentVal - 1).trigger('change')
            }

            if ($(this).closest('.ps-table--shopping-cart').length) {
                ajaxUpdateCart($(this))
            }
        })

        $(document).on('change', '.ps-table--shopping-cart .product__qty .qty-input', function() {
            ajaxUpdateCart($(this))
        })

        $(document).on('change', '.product-category-select', function() {
            $('.product-cat-label').text($.trim($(this).find('option:selected').text()))
        })

        $('.product-cat-label').text($.trim($('.product-category-select option:selected').text()))

        let searchTimeout = null
        $('.input-search-product')
            .on('keydown', function() {
                $(this).closest('form').find('.ps-panel--search-result').html('').removeClass('active')
            })
            .on('keyup', function() {
                const k = $(this).val()
                if (k) {
                    const $form = $(this).closest('form')
                    $form.find('.spinner-icon').show()
                    clearTimeout(searchTimeout)
                    searchTimeout = setTimeout(function() {
                        $.ajax({
                            type: 'GET',
                            url: $form.data('ajax-url'),
                            data: $form.serialize(),
                            success: res => {
                                if (!res.error && res.data !== '') {
                                    $form.find('.ps-panel--search-result').html(res.data).addClass('active')
                                } else {
                                    $form.find('.ps-panel--search-result').html('').removeClass('active')
                                }

                                $form.find('.spinner-icon').hide()

                                if (typeof Theme.lazyLoadInstance !== 'undefined') {
                                    Theme.lazyLoadInstance.update()
                                }
                            },
                            error: () => {
                                $form.find('.spinner-icon').hide()
                            },
                        })
                    }, 500)
                }
            })

        $('.rating_wrap > a ').on('click', function(e) {
            e.preventDefault()
            const target = $(this).attr('href')
            $('.ps-tab-list li').removeClass('active')
            $('.ps-tab-list li > a[href="' + target + '"]').closest('li').addClass('active')

            $(target).addClass('active')
            $(target)
                .siblings('.ps-tab')
                .removeClass('active')
            $(target).closest('.ps-tab-root').find('li').removeClass('active')
            $(target).closest('.ps-tab-root').find('li a[href="' + target + '"]').closest('li').addClass('active')

            $('html, body').animate(
                {
                    scrollTop: ($(target).offset().top - $('.header--product .navigation').height() - 165) + 'px',
                },
                800,
            )
        })

        if (jQuery().mCustomScrollbar) {
            $(document).find('.ps-custom-scrollbar').mCustomScrollbar({
                theme: 'dark',
                scrollInertia: 0,
            })
        }

        $(document).on('click', '.header--product .add-to-cart-button, .navigation--mobile-product .add-to-cart-button', e => {
            e.preventDefault()
            e.stopPropagation()

            const $this = $(e.currentTarget)

            $this.addClass('button-loading')

            setTimeout(function() {
                let target = '.ps-product--detail .ps-product__info .add-to-cart-form button[name=' + $this.prop('name') + '].add-to-cart-button'

                $(document).find(target).trigger('click')

                $this.removeClass('button-loading')
            }, 200)
        })

        $(document).on('click', '.ps-product-list .nav-item a', function(e) {
            e.preventDefault()
            const _self = $(e.currentTarget)

            if (_self.hasClass('active')) {
                return
            }

            const $tabContent = _self.closest('.ps-product-list')

            $tabContent.find('.nav-item a').removeClass('active')

            _self.addClass('active')

            const $loading = $tabContent.find('.loading-spinner')

            const $productList = $tabContent.find('.ps-carousel--nav')

            $productList.html('')

            $loading.removeClass('d-none')

            $.ajax({
                url: _self.data('url'),
                method: 'GET',
                success: (res) => {

                    if (res.error) {
                        Theme.showError(res.message)
                        return false
                    }

                    const $target = $tabContent.find('.owl-slider')

                    $target.trigger('destroy.owl.carousel')

                    $tabContent.find('.ps-carousel--nav').html(res.data)

                    owlCarouselConfig($target)

                    $loading.addClass('d-none')

                    if (typeof Theme.lazyLoadInstance !== 'undefined') {
                        Theme.lazyLoadInstance.update()
                    }
                },
                error: (res) => {
                    Theme.showError(res.message)
                },
            })
        })

        $(document).on('click', '.toggle-show-more', function (event) {
            event.preventDefault()

            $('#store-short-description').fadeOut()

            $(this).addClass('d-none')

            $('#store-content').removeClass('d-none').slideDown(500)

            $('.toggle-show-less').removeClass('d-none')
        })

        $(document).on('click', '.toggle-show-less', function (event) {
            event.preventDefault()

            $(this).addClass('d-none')

            $('#store-content').slideUp(500).addClass('d-none')

            $('#store-short-description').fadeIn()

            $('.toggle-show-more').removeClass('d-none')
        })

        const $productListing = $(document).find('.ps-products-listing');

        document.addEventListener('ecommerce.product-filter.before', () => {
            $productListing.find('.loading').show()
        })

        document.addEventListener('ecommerce.product-filter.success', (event) => {
            $('.ps-shopping .products-found').html(event.detail.data.message)
        })
    })

    function owlCarouselConfig(target) {
        if (target.length > 0) {
            target.each(function() {
                let el = $(this),
                    dataAuto = el.data('owl-auto'),
                    dataLoop = el.data('owl-loop'),
                    dataSpeed = el.data('owl-speed'),
                    dataGap = el.data('owl-gap'),
                    dataNav = el.data('owl-nav'),
                    dataDots = el.data('owl-dots'),
                    dataAnimateIn = el.data('owl-animate-in')
                        ? el.data('owl-animate-in')
                        : '',
                    dataAnimateOut = el.data('owl-animate-out')
                        ? el.data('owl-animate-out')
                        : '',
                    dataDefaultItem = el.data('owl-item'),
                    dataItemXS = el.data('owl-item-xs'),
                    dataItemSM = el.data('owl-item-sm'),
                    dataItemMD = el.data('owl-item-md'),
                    dataItemLG = el.data('owl-item-lg'),
                    dataItemXL = el.data('owl-item-xl'),
                    dataNavLeft = el.data('owl-nav-left')
                        ? el.data('owl-nav-left')
                        : '<i class=\'icon-chevron-left\'></i>',
                    dataNavRight = el.data('owl-nav-right')
                        ? el.data('owl-nav-right')
                        : '<i class=\'icon-chevron-right\'></i>',
                    duration = el.data('owl-duration'),
                    datamouseDrag =
                        el.data('owl-mousedrag') === 'on'
                if (
                    target.children('div, span, a, img, h1, h2, h3, h4, h5, h5')
                        .length >= 2
                ) {
                    el.addClass('owl-carousel').owlCarousel({
                        rtl: isRTL,
                        animateIn: dataAnimateIn,
                        animateOut: dataAnimateOut,
                        margin: dataGap,
                        autoplay: dataAuto,
                        autoplayTimeout: dataSpeed,
                        autoplayHoverPause: true,
                        loop: dataLoop,
                        nav: dataNav,
                        mouseDrag: datamouseDrag,
                        touchDrag: true,
                        autoplaySpeed: duration,
                        navSpeed: duration,
                        dotsSpeed: duration,
                        dragEndSpeed: duration,
                        navText: [dataNavLeft, dataNavRight],
                        dots: dataDots,
                        items: dataDefaultItem,
                        responsive: {
                            0: {
                                items: dataItemXS,
                            },
                            480: {
                                items: dataItemSM,
                            },
                            768: {
                                items: dataItemMD,
                            },
                            992: {
                                items: dataItemLG,
                            },
                            1200: {
                                items: dataItemXL,
                            },
                            1680: {
                                items: dataDefaultItem,
                            },
                        },
                    })
                }
            })
        }
    }

})(jQuery)
