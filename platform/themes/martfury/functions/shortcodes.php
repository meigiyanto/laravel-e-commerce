<?php

use Botble\Ads\Facades\AdsManager;
use Botble\Ads\Repositories\Interfaces\AdsInterface;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Forms\FieldOptions\CoreIconFieldOption;
use Botble\Base\Forms\FieldOptions\DatePickerFieldOption;
use Botble\Base\Forms\FieldOptions\MediaImageFieldOption;
use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\SelectFieldOption;
use Botble\Base\Forms\FieldOptions\TextareaFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\CoreIconField;
use Botble\Base\Forms\Fields\DatetimeField;
use Botble\Base\Forms\Fields\MediaImageField;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\SelectField;
use Botble\Base\Forms\Fields\TextareaField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\FlashSale;
use Botble\Ecommerce\Models\ProductCategory;
use Botble\Ecommerce\Repositories\Interfaces\ProductInterface;
use Botble\Faq\Models\FaqCategory;
use Botble\Shortcode\Compilers\Shortcode;
use Botble\Shortcode\Forms\ShortcodeForm;
use Botble\Theme\Facades\Theme;
use Botble\Theme\Supports\ThemeSupport;

app()->booted(function (): void {
    ThemeSupport::registerGoogleMapsShortcode();
    ThemeSupport::registerYoutubeShortcode();

    if (is_plugin_active('ecommerce')) {
        add_shortcode(
            'featured-product-categories',
            __('Featured Product Categories'),
            __('Featured Product Categories'),
            function ($shortCode) {
                $categories = get_featured_product_categories(['take' => null]);

                return Theme::partial('short-codes.featured-product-categories', [
                    'title' => $shortCode->title,
                    'categories' => $categories,
                ]);
            }
        );

        shortcode()->setAdminConfig('featured-product-categories', function ($attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                );
        });

        shortcode()->registerLoadingState('featured-product-categories', Theme::getThemeNamespace('partials.short-codes.featured-product-categories-skeleton'));

        add_shortcode('featured-products', __('Featured products'), __('Featured products'), function ($shortCode) {
            $limit = (int) $shortCode->limit ?: 10;
            
            $products = get_featured_products(['take' => $limit]);

            $products = $limit > 1 ? $products : collect([$products]);

            return Theme::partial('short-codes.featured-products', [
                'title' => $shortCode->title,
                'limit' => $shortCode->limit,
                'products' => $products,
            ]);
        });

        shortcode()->setAdminConfig('featured-products', function ($attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                )
                ->add(
                    'limit',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Limit'))
                        ->placeholder(__('Limit'))
                );
        });

        shortcode()->registerLoadingState('featured-products', Theme::getThemeNamespace('partials.short-codes.featured-products-skeleton'));

        add_shortcode('featured-brands', __('Featured Brands'), __('Featured Brands'), function ($shortCode) {
            $brands = get_featured_brands((int) $shortCode->limit ?: 8);

            return Theme::partial('short-codes.featured-brands', [
                'title' => $shortCode->title,
                'brands' => $brands,
            ]);
        });

        shortcode()->setAdminConfig('featured-brands', function ($attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                )
                ->add(
                    'limit',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Number of brands to display'))
                        ->placeholder(__('Number of brands to display'))
                        ->defaultValue(8)
                );
        });

        shortcode()->registerLoadingState('featured-brands', Theme::getThemeNamespace('partials.short-codes.featured-brands-skeleton'));

        add_shortcode(
            'product-collections',
            __('Product Collections'),
            __('Product Collections'),
            function (Shortcode $shortcode) {
                $productCollections = get_product_collections(
                    ['status' => BaseStatusEnum::PUBLISHED],
                    [],
                    ['id', 'name', 'slug']
                );

                if ($productCollections->isEmpty()) {
                    return null;
                }

                $limit = (int) $shortcode->limit ?: 8;

                $products = get_products_by_collections([
                    'collections' => [
                        'by' => 'id',
                        'value_in' => [$productCollections->first()->id],
                    ],
                    'take' => $limit,
                    'with' => EcommerceHelper::withProductEagerLoadingRelations(),
                ]);

                return Theme::partial('short-codes.product-collections', [
                    'title' => $shortcode->title,
                    'productCollections' => $productCollections,
                    'limit' => $limit,
                    'products' => $products,
                ]);
            }
        );

        shortcode()->setAdminConfig('product-collections', function (array $attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                )
                ->add(
                    'limit',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Limit'))
                        ->placeholder(__('Limit'))
                        ->defaultValue(8)
                );
        });

        shortcode()->registerLoadingState('product-collections', Theme::getThemeNamespace('partials.short-codes.product-collections-skeleton'));

        add_shortcode(
            'product-category-products',
            __('Product category products'),
            __('Product category products'),
            function (Shortcode $shortcode) {
                $category = ProductCategory::query()
                    ->where([
                        'status' => BaseStatusEnum::PUBLISHED,
                        'id' => (int) $shortcode->category_id,
                    ])
                    ->with([
                        'children' => function ($query) {
                            return $query
                                ->wherePublished()
                                ->orderBy('order')
                                ->limit(3);
                        },
                    ])
                    ->first();

                if (! $category) {
                    return null;
                }

                $limit = (int) $shortcode->limit ?: 8;

                $products = app(ProductInterface::class)->getProductsByCategories([
                    'categories' => [
                        'by' => 'id',
                        'value_in' => array_merge([$category->id], $category->activeChildren->pluck('id')->all()),
                    ],
                    'take' => $limit,
                ]);

                return Theme::partial('short-codes.product-category-products', compact('category', 'products', 'limit'));
            }
        );

        shortcode()->setAdminConfig('product-category-products', function (array $attributes) {
            $categories = ProductCategory::query()
                ->wherePublished()
                ->orderBy('order')->oldest()
                ->get()
                ->pluck('name', 'id')
                ->all();

            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'category_id',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Select category'))
                        ->choices($categories)
                )
                ->add(
                    'number_of_categories',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Limit number of categories'))
                        ->placeholder(__('Default: 3'))
                        ->defaultValue(3)
                )
                ->add(
                    'limit',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Limit number of products'))
                        ->placeholder(__('Unlimited by default'))
                );
        });

        shortcode()->registerLoadingState('product-category-products', Theme::getThemeNamespace('partials.short-codes.product-category-products-skeleton'));

        add_shortcode('trending-products', __('Trending Products'), __('Trending Products'), function ($shortCode) {
            $products = get_trending_products([
                    'take' => (int) $shortCode->limit ?: 10,
                ]);

            return Theme::partial('short-codes.trending-products', [
                'title' => $shortCode->title,
                'products' => $products,
            ]);
        });

        shortcode()->setAdminConfig('trending-products', function ($attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                )
                ->add(
                    'limit',
                    NumberField::class,
                    NumberFieldOption::make()
                        ->label(__('Limit'))
                        ->placeholder(__('Limit'))
                        ->defaultValue(10)
                );
        });

        shortcode()->registerLoadingState('trending-products', Theme::getThemeNamespace('partials.short-codes.trending-products-skeleton'));

        add_shortcode('flash-sale', __('Flash sale'), __('Flash sale'), function ($shortCode) {
            if (! $shortCode->flash_sale_id) {
                return null;
            }

            $flashSale = FlashSale::query()
                ->notExpired()
                ->where('id', $shortCode->flash_sale_id)
                ->wherePublished()
                ->with([
                    'products' => function ($query) {
                        return $query
                            ->wherePublished()
                            ->with(EcommerceHelper::withProductEagerLoadingRelations());
                    },
                ])
                ->first();

            if (! $flashSale) {
                return null;
            }

            return Theme::partial('short-codes.flash-sale', [
                'title' => $shortCode->title,
                'flashSale' => $flashSale,
            ]);
        });

        shortcode()->setAdminConfig('flash-sale', function ($attributes) {
            $flashSales = FlashSale::query()
                ->wherePublished()
                ->notExpired()
                ->get()
                ->pluck('name', 'id')
                ->all();

            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                )
                ->add(
                    'flash_sale_id',
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Select a flash sale'))
                        ->choices($flashSales)
                );
        });

        shortcode()->registerLoadingState('flash-sale', Theme::getThemeNamespace('partials.short-codes.flash-sale-skeleton'));
    }

    if (is_plugin_active('simple-slider')) {
        add_filter(SIMPLE_SLIDER_VIEW_TEMPLATE, function () {
            return Theme::getThemeNamespace() . '::partials.short-codes.sliders';
        }, 120);

        add_filter(SHORTCODE_REGISTER_CONTENT_IN_ADMIN, function ($data, $key, $attributes) {
            if ($key == 'simple-slider') {
                $ads = app(AdsInterface::class)->getModel()
                    ->where('status', BaseStatusEnum::PUBLISHED)
                    ->notExpired()
                    ->get()
                    ->pluck('name', 'key')
                    ->all();

                $maxAds = 2;

                $form = ShortcodeForm::createFromArray($attributes);

                for ($i = 1; $i <= $maxAds; $i++) {
                    $form->add(
                        'ads_' . $i,
                        SelectField::class,
                        SelectFieldOption::make()
                            ->label(__('Ad :number', ['number' => $i]))
                            ->choices(['' => __('-- select --')] + $ads)
                    );
                }

                return $data . $form->renderForm();
            }

            return $data;
        }, 50, 3);

        function get_ads_keys_from_shortcode(Shortcode $shortcode): array
        {
            $keys = collect($shortcode->toArray())
                ->sortKeys()
                ->filter(function ($value, $key) use ($shortcode) {
                    return Str::startsWith($key, 'ads_') ||
                        ($shortcode->name == 'theme-ads' && Str::startsWith($key, 'key_'));
                });

            return array_filter($keys->toArray() + [$shortcode->ads]);
        }
    }

    if (is_plugin_active('newsletter')) {
        add_shortcode('newsletter-form', __('Newsletter Form'), __('Newsletter Form'), function ($shortCode) {
            return Theme::partial('short-codes.newsletter-form', [
                'title' => $shortCode->title,
                'description' => $shortCode->description,
                'subtitle' => $shortCode->subtitle,
            ]);
        });

        shortcode()->setAdminConfig('newsletter-form', function ($attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                )
                ->add(
                    'subtitle',
                    TextareaField::class,
                    TextareaFieldOption::make()
                        ->label(__('Subtitle'))
                        ->placeholder(__('Subtitle'))
                        ->rows(3)
                )
                ->add(
                    'description',
                    TextareaField::class,
                    TextareaFieldOption::make()
                        ->label(__('Description'))
                        ->placeholder(__('Description'))
                        ->rows(3)
                );
        });
    }

    add_shortcode('download-app', __('Download Apps'), __('Download Apps'), function ($shortCode) {
        return Theme::partial('short-codes.download-app', [
            'title' => $shortCode->title,
            'description' => $shortCode->description,
            'subtitle' => $shortCode->subtitle,
            'screenshot' => $shortCode->screenshot,
            'androidAppUrl' => $shortCode->android_app_url,
            'iosAppUrl' => $shortCode->ios_app_url,
        ]);
    });

    shortcode()->setAdminConfig('download-app', function ($attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
                    ->placeholder(__('Title'))
            )
            ->add(
                'subtitle',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(__('Subtitle'))
                    ->placeholder(__('Subtitle'))
                    ->rows(3)
            )
            ->add(
                'description',
                TextareaField::class,
                TextareaFieldOption::make()
                    ->label(__('Description'))
                    ->placeholder(__('Description'))
                    ->rows(3)
            )
            ->add(
                'screenshot',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Screenshot'))
            )
            ->add(
                'android_app_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Android app URL'))
                    ->placeholder(__('Android app URL'))
            )
            ->add(
                'ios_app_url',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('iOS app URL'))
                    ->placeholder(__('iOS app URL'))
            );
    });

    if (is_plugin_active('faq')) {
        add_shortcode('faq', __('FAQs'), __('FAQs'), function ($shortCode) {
            $categories = FaqCategory::query()
                ->wherePublished()
                ->with([
                    'faqs' => function ($query): void {
                        $query->wherePublished();
                    },
                ])
                ->orderBy('order', 'ASC')->latest()
                ->get();

            return Theme::partial('short-codes.faq', [
                'title' => $shortCode->title,
                'categories' => $categories,
            ]);
        });

        shortcode()->setAdminConfig('faq', function ($attributes) {
            return ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading()
                ->add(
                    'title',
                    TextField::class,
                    TextFieldOption::make()
                        ->label(__('Title'))
                        ->placeholder(__('Title'))
                );
        });
    }

    add_shortcode('site-features', __('Site features'), __('Site features'), function ($shortcode) {
        return Theme::partial('short-codes.site-features', compact('shortcode'));
    });

    shortcode()->setAdminConfig('site-features', function ($attributes) {
        $form = ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading();

        for ($i = 1; $i < 6; $i++) {
            $form->add(
                'icon' . $i,
                CoreIconField::class,
                CoreIconFieldOption::make()
                    ->label(__('Icon :number', ['number' => $i]))
            )
            ->add(
                'title' . $i,
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title :number', ['number' => $i]))
                    ->placeholder(__('Title :number', ['number' => $i]))
            )
            ->add(
                'subtitle' . $i,
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Subtitle :number', ['number' => $i]))
                    ->placeholder(__('Subtitle :number', ['number' => $i]))
            );
        }

        return $form;
    });

    if (is_plugin_active('contact') && defined('CONTACT_FORM_TEMPLATE_VIEW')) {
        add_filter(CONTACT_FORM_TEMPLATE_VIEW, function () {
            return Theme::getThemeNamespace() . '::partials.short-codes.contact-form';
        }, 120);
    }

    add_shortcode('contact-info-boxes', __('Contact info boxes'), __('Contact info boxes'), function ($shortCode) {
        return Theme::partial('short-codes.contact-info-boxes', ['title' => $shortCode->title]);
    });

    shortcode()->setAdminConfig('contact-info-boxes', function ($attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'title',
                TextField::class,
                TextFieldOption::make()
                    ->label(__('Title'))
                    ->placeholder(__('Title'))
            );
    });

    if (is_plugin_active('ads')) {
        add_shortcode('theme-ads', __('Theme ads'), __('Theme ads'), function ($shortCode) {
            $ads = [];
            $attributes = $shortCode->toArray();

            for ($i = 1; $i < 5; $i++) {
                if (isset($attributes['key_' . $i]) && ! empty($attributes['key_' . $i])) {
                    $ad = AdsManager::displayAds((string) $attributes['key_' . $i]);
                    if ($ad) {
                        $ads[] = $ad;
                    }
                }
            }

            $ads = array_filter($ads);

            return Theme::partial('short-codes.theme-ads', compact('ads'));
        });

        shortcode()->setAdminConfig('theme-ads', function ($attributes) {
            $ads = app(AdsInterface::class)->getModel()
                ->where('status', BaseStatusEnum::PUBLISHED)
                ->notExpired()
                ->get()
                ->pluck('name', 'key')
                ->all();

            $form = ShortcodeForm::createFromArray($attributes)
                ->withLazyLoading();

            for ($i = 1; $i < 5; $i++) {
                $form->add(
                    'key_' . $i,
                    SelectField::class,
                    SelectFieldOption::make()
                        ->label(__('Ad :number', ['number' => $i]))
                        ->choices(['' => __('-- Select --')] + $ads)
                        ->searchable()
                );
            }

            return $form;
        });
    }

    add_shortcode('coming-soon', __('Coming soon'), __('Coming soon'), function ($shortCode) {
        return Theme::partial('short-codes.coming-soon', [
            'time' => $shortCode->time,
            'image' => $shortCode->image,
        ]);
    });

    shortcode()->setAdminConfig('coming-soon', function ($attributes) {
        return ShortcodeForm::createFromArray($attributes)
            ->withLazyLoading()
            ->add(
                'time',
                DatetimeField::class,
                DatePickerFieldOption::make()
                    ->label(__('Time'))
                    ->placeholder(__('Time'))
            )
            ->add(
                'image',
                MediaImageField::class,
                MediaImageFieldOption::make()
                    ->label(__('Image'))
            );
    });
});
