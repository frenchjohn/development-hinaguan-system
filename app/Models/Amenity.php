<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amenity extends Model
{
    use HasFactory;

    protected $table = 'amenities';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'amenities_name',
        'daytime_price',
        'nighttime_price',
        'original_daytime_price',
        'original_nighttime_price',
        'additional_per_head',
        'minimum_capacity',
        'maximum_capacity',
        'description',
        'image',
        'status',
        'sale_percentage',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function benefits()
    {
        return $this->hasOne(AmenityBenefit::class, 'amenity_id', 'id');
    }

    public function benefit()
    {
        return $this->hasOne(AmenityBenefit::class, 'amenity_id', 'id');
    }
}
