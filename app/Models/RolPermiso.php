<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $table = 'roles_permisos';
    public $timestamps = true;


    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'id',
        'rol_id',
        'permiso_id',
        'created_at',
        'updated_at'
    ];
}
