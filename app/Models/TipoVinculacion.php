<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoVinculacion extends Model
{
  use HasFactory;

  public $incrementing = true;
  protected $primaryKey = 'id';
  protected $table = 'tipo_vinculacion';
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


  public static function obtenerListado($arrayAndWhere = [], $arrayOrWhere = [])
  {

    $bancos = Self::orWhere(function ($query) use ($arrayOrWhere, $arrayAndWhere) {

      if (!empty($arrayOrWhere)) :
        $query->where(function ($query) use ($arrayOrWhere) {
          foreach ($arrayOrWhere as $value) :
            $query->orWhere($value[0], $value[1], $value[2]);
          endforeach;
        });
      endif;

      if (!empty($arrayAndWhere)) :
        foreach ($arrayAndWhere as $value) :
          $query->where($value[0], $value[1], $value[2]);
        endforeach;
      endif;
    });

    return  $bancos;
  }
}
