<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'roles';
    public $timestamps = true;

    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'id',
        'descripcion',
        'permisos_json',
      //  'created_at',
        'updated_at'
    ];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id');
    }

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }
}
