<?php

namespace App\Traits;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEntity
{
    protected static function bootBelongsToEntity(): void
    {
        // Auto-scope all queries to current entity
        static::addGlobalScope('entity', function (Builder $builder) {
            if ($entityId = session('current_entity_id')) {
                $builder->where($builder->getModel()->getTable() . '.entity_id', $entityId);
            }
        });

        // Auto-set entity_id on create
        static::creating(function ($model) {
            if (!$model->entity_id && $entityId = session('current_entity_id')) {
                $model->entity_id = $entityId;
            }
        });
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function scopeAllEntities(Builder $query): Builder
    {
        return $query->withoutGlobalScope('entity');
    }

    public function scopeForEntity(Builder $query, int $entityId): Builder
    {
        return $query->withoutGlobalScope('entity')->where('entity_id', $entityId);
    }
}
