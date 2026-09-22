<?php

use Botble\Ecommerce\Models\Currency;
use Botble\Ecommerce\Models\Invoice;
use Botble\Ecommerce\Models\Order;
use Botble\Ecommerce\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

return new class () extends Migration {
    protected Collection $currencies;

    protected ?Currency $defaultCurrency;

    public function up(): void
    {
        try {
            $this->currencies = Currency::query()->get()->keyBy('id');
            $this->defaultCurrency = $this->currencies->firstWhere('is_default', 1);

            $this->fixOrderData();
            $this->fixInvoiceData();
        } catch (Throwable) {
            // Do nothing
        }
    }

    public function down(): void
    {
    }

    protected function fixOrderData(): void
    {
        Order::query()
            ->with('products')
            ->chunk(100, function ($orders): void {
                $productIds = $orders->flatMap(fn ($order) => $order->products->pluck('product_id'))->unique()->filter();
                $productModels = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

                foreach ($orders as $order) {
                    $currency = $this->currencies->get($order->currency_id) ?: $this->defaultCurrency;
                    $decimals = $currency ? (int) $currency->decimals : 2;

                    $subtotal = 0;
                    $taxTotal = 0;

                    foreach ($order->products as $product) {
                        $product->price = round($product->price, $decimals);
                        $lineTotal = round($product->price * $product->qty, $decimals);
                        $subtotal += $lineTotal;

                        if ($product->tax_amount > 0 && $lineTotal > 0) {
                            $productModel = $productModels->get($product->product_id);

                            $taxRate = 0;
                            if ($productModel) {
                                $taxPercentage = $productModel->total_taxes_percentage;
                                if ($taxPercentage && $taxPercentage > 0) {
                                    $taxRate = $taxPercentage / 100;
                                }
                            }

                            if ($taxRate == 0) {
                                $originalLineTotal = $product->getOriginal('price') * $product->qty;
                                if ($originalLineTotal > 0) {
                                    $taxRate = $product->getOriginal('tax_amount') / $originalLineTotal;
                                }
                            }

                            $product->tax_amount = round($lineTotal * $taxRate, $decimals);
                        }
                        $product->save();

                        $taxTotal += $product->tax_amount;
                    }

                    $order->sub_total = $subtotal;
                    $order->tax_amount = $taxTotal;
                    $order->shipping_amount = round($order->shipping_amount, $decimals);
                    $order->discount_amount = round($order->discount_amount, $decimals);

                    $order->amount = round(
                        $subtotal + $taxTotal + $order->shipping_amount - $order->discount_amount,
                        $decimals
                    );

                    $order->save();
                }
            });
    }

    protected function fixInvoiceData(): void
    {
        Invoice::query()->chunk(100, function ($invoices): void {
            $orderIds = $invoices->pluck('reference_id')->unique()->filter();
            $orders = Order::query()->whereIn('id', $orderIds)->get()->keyBy('id');

            foreach ($invoices as $invoice) {
                $order = $orders->get($invoice->reference_id);
                if (! $order) {
                    continue;
                }

                $invoice->sub_total = $order->sub_total;
                $invoice->tax_amount = $order->tax_amount;
                $invoice->shipping_amount = $order->shipping_amount;
                $invoice->discount_amount = $order->discount_amount;
                $invoice->amount = $order->amount;
                $invoice->save();
            }
        });
    }
};
