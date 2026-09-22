<?php

namespace Botble\Ecommerce\Supports;

use Botble\Ecommerce\Enums\DiscountTargetEnum;
use Botble\Ecommerce\Enums\DiscountTypeEnum;
use Botble\Ecommerce\Models\Discount;
use Botble\Ecommerce\Repositories\Interfaces\DiscountInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DiscountSupport
{
    protected Collection|array $promotions = [];

    public int|string $customerId = 0;

    protected array $productCategoriesCache = [];

    protected array $productCollectionsCache = [];

    protected bool $productCategoriesLoaded = false;

    protected bool $productCollectionsLoaded = false;

    public function __construct()
    {
        if (! is_in_admin() && auth('customer')->check()) {
            $this->setCustomerId(auth('customer')->id());
        }
    }

    public function setCustomerId(int|string $customerId): self
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomerId(): int|string
    {
        return $this->customerId;
    }

    public function promotionForProduct(array $productIds): ?Discount
    {
        if (! $this->promotions) {
            $this->getAvailablePromotions();
        }

        foreach ($this->promotions as $promotion) {
            switch ($promotion->target) {
                case DiscountTargetEnum::SPECIFIC_PRODUCT:
                case DiscountTargetEnum::PRODUCT_VARIANT:
                    foreach ($promotion->products as $product) {
                        if (in_array($product->id, $productIds)) {
                            return $promotion;
                        }
                    }

                    break;

                case DiscountTargetEnum::PRODUCT_COLLECTIONS:
                    $productCollectionIds = $this->getProductCollectionIds($productIds);

                    foreach ($promotion->productCollections as $productCollection) {
                        if (in_array($productCollection->id, $productCollectionIds)) {
                            return $promotion;
                        }
                    }

                    break;

                case DiscountTargetEnum::CUSTOMER:
                    if ($this->customerId) {
                        foreach ($promotion->customers as $customer) {
                            if ($customer->id == $this->customerId) {
                                return $promotion;
                            }
                        }
                    }

                    break;

                case DiscountTargetEnum::PRODUCT_CATEGORIES:
                    $productCategoriesIds = $this->getProductCategoryIds($productIds);

                    foreach ($promotion->productCategories as $productCategories) {
                        if (in_array($productCategories->id, $productCategoriesIds)) {
                            return $promotion;
                        }
                    }

                    break;

                case DiscountTargetEnum::ALL_ORDERS:
                    if ($promotion->product_quantity == 1) {
                        return $promotion;
                    }

                    break;
            }
        }

        return null;
    }

    public function getAvailablePromotions(bool $forProductSingle = true): Collection
    {
        if (! $this->promotions instanceof Collection) {
            $this->promotions = collect();
        }

        if ($this->promotions->isEmpty()) {
            $this->promotions = app(DiscountInterface::class)
                ->getAvailablePromotions(['products', 'customers', 'productCollections', 'productCategories'], $forProductSingle);
        }

        return $this->promotions;
    }

    public function afterOrderPlaced(string $couponCode, int|string|null $customerId = 0): void
    {
        $now = Carbon::now();

        /**
         * @var Discount $discount
         */
        $discount = Discount::query()
            ->where('code', $couponCode)
            ->where('type', DiscountTypeEnum::COUPON)
            ->where('start_date', '<=', $now)
            ->where(function (Builder $query) use ($now): void {
                $query
                    ->whereNull('end_date')
                    ->orWhere('end_date', '>', $now);
            })
            ->first();

        if ($discount) {
            $discount->total_used++;
            $discount->save();

            if (func_num_args() == 1) {
                $customerId = auth('customer')->check() ? auth('customer')->id() : 0;
            }

            if ($discount->target == DiscountTargetEnum::ONCE_PER_CUSTOMER && $customerId) {
                $discount->usedByCustomers()->syncWithoutDetaching([$customerId]);
            }
        }
    }

    public function afterOrderCancelled(string $couponCode, int|string|null $customerId = 0): void
    {
        /**
         * @var Discount $discount
         */
        $discount = Discount::query()
            ->where('code', $couponCode)
            ->where('type', DiscountTypeEnum::COUPON)
            ->first();

        if ($discount) {
            $discount->total_used--;
            $discount->save();

            if (func_num_args() == 1) {
                $customerId = auth('customer')->check() ? auth('customer')->id() : 0;
            }

            if ($discount->target == DiscountTargetEnum::ONCE_PER_CUSTOMER && $customerId) {
                $discount->usedByCustomers()->detach($customerId);
            }
        }
    }

    protected function ensureProductRelationsLoaded(string $type = 'all'): void
    {
        if (in_array($type, ['categories', 'all']) && ! $this->productCategoriesLoaded) {
            $categories = DB::table('ec_product_category_product')
                ->select(['product_id', 'category_id'])
                ->get();

            foreach ($categories as $category) {
                if (! isset($this->productCategoriesCache[$category->product_id])) {
                    $this->productCategoriesCache[$category->product_id] = [];
                }
                $this->productCategoriesCache[$category->product_id][] = $category->category_id;
            }

            $this->productCategoriesLoaded = true;
        }

        if (in_array($type, ['collections', 'all']) && ! $this->productCollectionsLoaded) {
            $collections = DB::table('ec_product_collection_products')
                ->select(['product_id', 'product_collection_id'])
                ->get();

            foreach ($collections as $collection) {
                if (! isset($this->productCollectionsCache[$collection->product_id])) {
                    $this->productCollectionsCache[$collection->product_id] = [];
                }
                $this->productCollectionsCache[$collection->product_id][] = $collection->product_collection_id;
            }

            $this->productCollectionsLoaded = true;
        }
    }

    protected function getProductCategoryIds(array $productIds): array
    {
        $this->ensureProductRelationsLoaded('categories');

        $categoryIds = [];
        foreach ($productIds as $productId) {
            if (isset($this->productCategoriesCache[$productId])) {
                $categoryIds = array_merge($categoryIds, $this->productCategoriesCache[$productId]);
            }
        }

        return array_unique($categoryIds);
    }

    protected function getProductCollectionIds(array $productIds): array
    {
        $this->ensureProductRelationsLoaded('collections');

        $collectionIds = [];
        foreach ($productIds as $productId) {
            if (isset($this->productCollectionsCache[$productId])) {
                $collectionIds = array_merge($collectionIds, $this->productCollectionsCache[$productId]);
            }
        }

        return array_unique($collectionIds);
    }
}
