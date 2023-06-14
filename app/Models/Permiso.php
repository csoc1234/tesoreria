<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'permisos';
    public $timestamps = true;

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'roles_permisos', 'permiso_id', 'rol_id');
    }
}
