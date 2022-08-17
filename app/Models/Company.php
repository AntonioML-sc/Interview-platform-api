<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;

class Company extends Model
{
    use HasFactory, Uuids;

    protected $primaryKey = 'id';

    // relationships

    public function positions()
    {
        return $this->hasMany(Position::class);
    }
}