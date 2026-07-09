<?php

namespace BbqData\Models;

use BbqData\Contracts\Model;
use Illuminate\Database\Eloquent\Builder;

class HorecaProduct extends Model
{
    protected $table = 'products';

    protected $guarded = ['id', 'type'];

    protected $casts = [
        'price' => 'float',
        'purchase_price' => 'float',
        'vat' => 'float',
        'stock' => 'integer',
        'stock_threshold' => 'integer',
        'portion' => 'integer',
        'post_id' => 'integer',
    ];

    protected $appends = [
        'average_weight',
        'total_weight',
        'total_value',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('horeca', function (Builder $builder) {
            $builder->where('type', 'horeca');
        });

        static::creating(function (HorecaProduct $product) {
            $product->type = 'horeca';
        });
    }

    public function scopeHoreca(Builder $query): Builder
    {
        return $query->where('type', 'horeca');
    }

    public function getAverageWeightAttribute(): ?float
    {
        return $this->portion !== null ? $this->portion / 1000 : null;
    }

    public function getTotalWeightAttribute(): ?float
    {
        return $this->average_weight !== null
            ? $this->stock * $this->average_weight
            : null;
    }

    public function getTotalValueAttribute(): ?float
    {
        if ($this->purchase_price === null) {
            return null;
        }

        if ($this->portion !== null) {
            return $this->total_weight !== null
                ? $this->total_weight * $this->purchase_price
                : null;
        }

        return $this->stock * $this->purchase_price;
    }
}
