<?php

namespace App\Models\Concerns;

use App\Support\BlindIndex;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Mantiene allineate le colonne blind-index (hash) ai relativi attributi cifrati e
 * offre lo scope {@see scopeWhereBlind} per i lookup esatti.
 *
 * Il modello deve dichiarare:
 *   protected array $blindIndexes = ['email' => 'email_hash', ...];
 */
trait HasBlindIndexes
{
    protected static function bootHasBlindIndexes(): void
    {
        static::saving(function (Model $model) {
            foreach ($model->getBlindIndexes() as $attribute => $hashColumn) {
                $model->{$hashColumn} = BlindIndex::hash($model->{$attribute});
            }
        });
    }

    public function getBlindIndexes(): array
    {
        return $this->blindIndexes ?? [];
    }

    public function scopeWhereBlind(Builder $query, string $attribute, ?string $value): Builder
    {
        $column = $this->getBlindIndexes()[$attribute] ?? ($attribute.'_hash');

        return $query->where($column, BlindIndex::hash($value));
    }
}
