<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'value'])]
class ProductAttribute extends Model
{
    /** @use HasFactory<\Database\Factories\ProductAttributeFactory> */
    use HasFactory;

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'attribute_product');
    }
}
