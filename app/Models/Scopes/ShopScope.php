<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ShopScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Ne pas filtrer en dehors du contexte HTTP (artisan, queues, tests)
        if (app()->runningInConsole()) {
            return;
        }

        $shopId = request()->attributes->get('shopId');
        if ($shopId) {
            $builder->where($model->getTable() . '.shopId', $shopId);
        }
    }
}
