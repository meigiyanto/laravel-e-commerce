<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BillingService
{
    public function createRenewalInvoice(Subscription $subscription): Invoice
    {
        return DB::transaction(function () use ($subscription) {
            $locked = Subscription::query()
                ->lockForUpdate()
                ->with('plan')
                ->findOrFail($subscription->id);

            $existing = Invoice::query()
                ->where('subscription_id', $locked->id)
                ->where('status', '!=', 'paid')
                ->where('due_at', '>', now())
                ->latest()
                ->first();

            if ($existing) {
                return $existing;
            }

            $invoice = Invoice::create([
                'tenant_id' => $locked->tenant_id,
                'subscription_id' => $locked->id,
                'invoice_number' => 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'status' => 'open',
                'currency' => $locked->currency,
                'subtotal' => $locked->amount,
                'tax_amount' => 0,
                'total' => $locked->amount,
                'issued_at' => now(),
                'due_at' => now()->addDays(3),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => 'Subscription '.$locked->plan->name,
                'quantity' => 1,
                'unit_price' => $locked->amount,
                'total' => $locked->amount,
            ]);

            return $invoice;
        });
    }
}
