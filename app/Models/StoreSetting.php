<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['currency_code', 'currency_symbol', 'tax_rate', 'shipping_cost'])]
class StoreSetting extends Model
{
    protected $attributes = [
        'currency_code' => 'EUR',
        'currency_symbol' => '€',
        'tax_rate' => 0,
        'shipping_cost' => 0,
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:4',
            'shipping_cost' => 'decimal:2',
        ];
    }

    /**
     * The single settings row, created with defaults on first access.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
