<?php

return [
    'general' => [
        'name' => 'E-handel',
        'description' => 'Butikknavn, firmainfo, kontaktdetaljer og admin-varslings-e-poster',
    ],
    'currencies' => [],
    'products' => [
        'search_type_filter_options' => [],
        'search' => [],
        'product_options' => [
            'options' => [],
        ],
    ],
    'product_search' => [
        'name' => 'Produktsøk',
        'description' => 'Søkeadferd, filtre etter kategori/merke/tag/attributter og prisintervallfiltrering',
    ],
    'digital_product' => [
        'name' => 'Digitale produkter',
        'description' => 'Nedlastbare produkter, lisenskoder, gjestekasse for digitale varer og automatisk fullføring',
    ],
    'product_review' => [
        'name' => 'Produktanmeldelser',
        'description' => 'Kundeanmeldelser, vurderingsvisning, bildeopplasting og godkjenningskrav',
    ],
    'shopping' => [
        'name' => 'Handlekurv',
        'description' => 'Handlekurv, ønskeliste, sammenlign, ordresporing, hurtigkjøp-knapp og opplasting av betalingsbevis',
        'form' => [
            'payment_proof_payment_methods' => 'Betalingsmetoder som krever betalingsbevis',
            'payment_proof_payment_methods_helper' => 'Velg hvilke betalingsmetoder som skal tillate kunder å laste opp betalingsbevis. Brukes vanligvis for manuelle betalingsmetoder som postoppkrav og bankoverføring.',
        ],
        'product_quantity_input' => [],
        'display_product_quantity_in_cart_page' => [],
    ],
    'checkout' => [
        'name' => 'Utsjekking',
        'description' => 'Innstillinger for utsjekking',
    ],
    'return' => [
        'name' => 'Returinnstillinger',
        'description' => 'Innstillinger for produktretur',
    ],
    'invoice' => [
        'name' => 'Faktura',
        'description' => 'Firmadetaljer på fakturaer, fakturanummerering, PDF-skrifter og stempelinnstillinger',
    ],
    'tax' => [
        'name' => 'Mva',
        'description' => 'Innstillinger for mva',
        'display_product_price_including_taxes' => 'Vis produktpris inkludert mva?',
        'form' => [
            'display_product_price_including_taxes' => 'Legg til mva på viste priser',
            'display_product_price_including_taxes_helper' => 'Legger automatisk til mva på produktpriser i butikken. F.eks. vises et $100 produkt med 10% mva som $110. Ikke nødvendig hvis \"Pris inkluderer mva\" allerede er aktivert på individuelle produkter.',
            'display_checkout_tax_information' => 'Vis skattemelding under hvert produkt i kassen',
            'display_checkout_tax_information_helper' => 'Vis skattedetaljer (f.eks. \"MVA 10%\") ved siden av hvert produkt i kassen.',
        ],
    ],
    'customer' => [
        'name' => 'Kunder',
        'description' => 'Registrering, e-postverifisering, påloggingsalternativer, kontosletting og profilfelter',
    ],
    'shipping' => [
        'name' => 'Frakt',
        'description' => 'Fraktregler, gratis fraktadferd og visningsrekkefølge for fraktalternativer',
    ],
    'webhook' => [
        'name' => 'Webhook',
        'description' => 'Innstillinger for Webhook',
    ],
    'tracking' => [
        'name' => 'Sporing',
        'description' => 'Facebook Pixel, Google Tag Manager hendelser og Google Ads konverteringssporing',
    ],
    'marketplace' => [
        'name' => 'Markedsplass',
        'description' => 'Multi-leverandør innstillinger, provisjoner, selgerregistrering og utbetalingskonfigurasjon',
    ],
    'standard_and_format' => [
        'name' => 'Standarder og formater',
        'description' => 'Standarder og formater som brukes til å beregne ting som produktpriser, fraktvekt og så videre.',
    ],
    'store_locators' => [],
    'flash_sale' => [
        'name' => 'Lynsal g',
        'description' => 'Innstillinger for lynsal g',
        'show_sale_count_left' => 'Vis antall salg igjen?',
        'show_sale_count_left_description' => 'Vis antall salg igjen på lynsalgs-siden?',
    ],

    'abandoned_cart' => [
        'name' => 'Giỏ hàng bị bỏ rơi',
        'description' => 'Khôi phục doanh số bị mất bằng cách gửi lời nhắc tự động cho khách hàng để lại sản phẩm trong giỏ',
        'panel_description' => 'Thiết lập lời nhắc tự động cho khách hàng bỏ rơi giỏ hàng',
        'how_it_works' => [
            'title' => 'Cách hoạt động',
            'step1_title' => 'Khách hàng thêm sản phẩm',
            'step1_description' => 'Khách hàng thêm sản phẩm vào giỏ hàng và cung cấp địa chỉ email.',
            'step2_title' => 'Giỏ hàng bị bỏ rơi',
            'step2_description' => 'Nếu không có giao dịch mua trong ngưỡng thời gian đã đặt, giỏ hàng được đánh dấu là bị bỏ rơi.',
            'step3_title' => 'Gửi email nhắc nhở',
            'step3_description' => 'Email nhắc nhở tự động được gửi để khuyến khích khách hàng hoàn tất việc mua hàng.',
            'step4_title' => 'Khôi phục doanh số',
            'step4_description' => 'Khách hàng quay lại hoàn tất đơn hàng và giỏ hàng được đánh dấu là đã khôi phục.',
        ],
        'form' => [
            'enable' => 'Bật theo dõi giỏ hàng bị bỏ rơi',
            'enable_helper' => 'Khi được bật, hệ thống sẽ theo dõi giỏ hàng bị bỏ rơi và cho phép bạn gửi email khôi phục cho khách hàng.',
            'timing_section' => 'Cài đặt thời gian',
            'email_section' => 'Cài đặt email nhắc nhở',
            'cleanup_section' => 'Dọn dẹp dữ liệu',
            'time_threshold' => 'Ngưỡng thời gian (giờ)',
            'time_threshold_helper' => 'Giỏ hàng được coi là bị bỏ rơi sau số giờ không hoạt động này. Khuyến nghị: 1-24 giờ.',
            'send_email' => 'Gửi email khôi phục',
            'send_email_helper' => 'Tự động gửi email khôi phục cho khách hàng có giỏ hàng bị bỏ rơi.',
            'max_reminders' => 'Số lượng email nhắc nhở',
            'max_reminders_helper' => 'Số lượng email nhắc nhở gửi cho mỗi giỏ hàng bị bỏ rơi. Mỗi email có thể được tùy chỉnh trong Mẫu Email.',
            'email_delay' => 'Gửi sau (giờ)',
            'email_discount' => 'Giảm giá (%)',
            'email_1_title' => 'Email #1 - Nhắc nhở thân thiện',
            'email_1_description' => 'Lời nhắc nhẹ nhàng đầu tiên để khuyến khích khách hàng quay lại.',
            'email_1_delay_helper' => 'Số giờ sau khi giỏ hàng bị bỏ rơi để gửi email đầu tiên. Khuyến nghị: 1-2 giờ.',
            'email_2_title' => 'Email #2 - Kèm ưu đãi',
            'email_2_description' => 'Lời nhắc thứ hai với ưu đãi giảm giá để thúc đẩy mua hàng.',
            'email_2_delay_helper' => 'Số giờ sau khi giỏ hàng bị bỏ rơi để gửi email thứ hai. Khuyến nghị: 24 giờ.',
            'email_2_discount_helper' => 'Phần trăm giảm giá để cung cấp trong email này. Đặt 0 nếu không giảm giá.',
            'email_3_title' => 'Email #3 - Cơ hội cuối',
            'email_3_description' => 'Lời nhắc cuối cùng với tính cấp bách và ưu đãi tốt nhất để chốt đơn.',
            'email_3_delay_helper' => 'Số giờ sau khi giỏ hàng bị bỏ rơi để gửi email cuối cùng. Khuyến nghị: 72 giờ.',
            'email_3_discount_helper' => 'Phần trăm giảm giá cuối cùng. Thường cao hơn email #2.',
            'cleanup_days' => 'Dọn dẹp sau (ngày)',
            'cleanup_days_helper' => 'Tự động xóa bản ghi giỏ hàng bị bỏ rơi cũ hơn số ngày này.',
            'cronjob_not_setup' => '<strong>Cronjob chưa được cấu hình!</strong> Nhắc nhở giỏ hàng bị bỏ rơi yêu cầu cronjob được cấu hình đúng để hoạt động. <a href=":url">Thiết lập cronjob ngay</a>.',
            'cronjob_not_running' => '<strong>Cronjob không chạy!</strong> Cronjob dường như đã dừng. Vui lòng kiểm tra cấu hình máy chủ của bạn. <a href=":url">Xem trạng thái cronjob</a>.',
            'cronjob_working' => '<strong>Cronjob đang chạy đúng!</strong> Hoạt động cuối: :time. Nhắc nhở giỏ hàng bị bỏ rơi của bạn sẽ được xử lý tự động.',
            'email_setup_warning' => '<strong>Quan trọng:</strong> Đảm bảo cài đặt email của bạn được cấu hình đúng trong <a href=":url">Cài đặt → Email</a>. Gửi email test để xác minh mọi thứ hoạt động trước khi bật tính năng này.',
        ],
    ],
];
