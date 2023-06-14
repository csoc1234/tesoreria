<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genero extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'generos';
    public $timestamps = true;

      /* protected $fillable = [
		'make','model'
    ];

    protected $hidden = [
		'make','model'
	];

    */

    protected $fillable = [
		'descripcion'
    ];

    //Obtener el listado
    public static function obtenerListado() {
        return self::all();
    }
}
