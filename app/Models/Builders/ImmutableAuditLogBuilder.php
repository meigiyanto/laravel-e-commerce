<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

class ImmutableAuditLogBuilder extends Builder
{
    private function rejectMutation(): never
    {
        throw new LogicException('Platform audit logs are immutable.');
    }

    public function update(array $values): int
    {
        $this->rejectMutation();
    }

    public function delete(): mixed
    {
        $this->rejectMutation();
    }

    public function forceDelete(): mixed
    {
        $this->rejectMutation();
    }

    public function increment($column, $amount = 1, array $extra = []): int
    {
        $this->rejectMutation();
    }

    public function decrement($column, $amount = 1, array $extra = []): int
    {
        $this->rejectMutation();
    }

    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        $this->rejectMutation();
    }

    public function __call($method, $parameters)
    {
        if (in_array($method, [
            'truncate',
            'updateOrInsert',
            'incrementEach',
            'decrementEach',
        ], true)) {
            $this->rejectMutation();
        }

        return parent::__call($method, $parameters);
    }
}