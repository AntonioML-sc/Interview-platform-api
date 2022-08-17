<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

use App\Traits\Uuids;

class PositionSkill extends Pivot
{
    use HasFactory, Uuids;

    protected $primaryKey = 'id';
}
