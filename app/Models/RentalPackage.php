<?php

namespace App\Models;

use Database\Factories\RentalPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'package_price', 'is_active'])]
class RentalPackage extends Model
{
    /** @use HasFactory<RentalPackageFactory> */
    use HasFactory;

    /**
     * @return HasMany<RentalPackageItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RentalPackageItem::class);
    }

    /**
     * @return HasMany<RentalItem, $this>
     */
    public function rentalItems(): HasMany
    {
        return $this->hasMany(RentalItem::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'package_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
