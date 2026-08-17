<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParkRule extends Model
{
    use HasFactory;

    protected $table = 'park_rules';

    protected $fillable = [
        'rule_name',
        'rule_descriptions',
    ];
}
