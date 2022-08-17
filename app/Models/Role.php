<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Uuids;

class Role extends Model
{
    use HasFactory;
    use Uuids;

    protected $primaryKey = 'id';

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
