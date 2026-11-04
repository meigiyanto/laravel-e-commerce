<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire subscriptions past their billing period';

    public function handle(): int
    {
        Subscription::query()
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
                Subscription::STATUS_CANCELLED,
            ])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '<=', now())
                        ->where('status', Subscription::STATUS_TRIALING);
                })->orWhere(function ($q) {
                    $q->whereNotNull('current_period_ends_at')
                        ->where('current_period_ends_at', '<=', now());
                })->orWhere(function ($q) {
                    $q->whereNotNull('ends_at')
                        ->where('ends_at', '<=', now());
                });
            })
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription) {
                        $locked = Subscription::query()
                            ->lockForUpdate()
                            ->find($subscription->id);

                        if (!$locked || $locked->status === Subscription::STATUS_EXPIRED) {
                            return;
                        }

                        $locked->update([
                            'status' => Subscription::STATUS_EXPIRED,
                        ]);
                    });
                }
            });

        $this->info('Subscription expiration completed.');

        return self::SUCCESS;
    }
}
