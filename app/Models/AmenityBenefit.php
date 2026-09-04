<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmenityBenefit extends Model
{
    use HasFactory;

    protected $table = 'amenities_benefits';

    protected $fillable = [
        'amenity_id',
        'is_aircon',
        'free_entrance',
        'free_pool',
    ];

    protected $casts = [
        'is_aircon' => 'boolean',
        'free_entrance' => 'boolean',
        'free_pool' => 'boolean',
    ];

    public function amenity()
    {
        return $this->belongsTo(Amenity::class, 'amenity_id', 'id');
    }
}
