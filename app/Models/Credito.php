<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Credito extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'creditos';
    public $timestamps = true;

    //CAMPOS QUE NO APARECERAN EN CONSULTAS
    protected $hidden = [];

    //CAMPOS QUE NO SE PUEDEN MODIFICAR
    protected $guarded = ['id'];

    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'tipo_credito_id',
        // 'linea',
        'num_ordenanza',
        'descripcion',
        'valor',
        'valor_prestado',
        'valor_pagado',
        'fecha',
        'descripcion',
        'estado',
        'created_at',
        'updated_at'
    ];

    public static function obtenerCredito($id)
    {
        return self::find($id);
    }

    public function creditosbancos()
    {
        return $this->hasMany(CreditoBanco::class);
    }



    public static function obtenerCreditos($arrayAndWhere, $arrayOrWhere = [])
    {

        $query = self::query();

        if (!empty($arrayOrWhere)):
            $query = $query->orWhere(function ($query) use ($arrayOrWhere) {
                foreach ($arrayOrWhere as $value):
                    $query->orWhere($value[0], $value[1], $value[2]);
                endforeach;
            });
        endif;

        if (!empty($fields)):
            $query = $query->select($fields);
        endif;

        $query = $query->where('estado','=','Activo');
        $query = $query->where($arrayAndWhere);
        //dd($query->toArray());
        return $query;

        //FUNCIONA
        /* $creditos = Credito::orWhere(function ($query) use ($arrayOrWhere) {
            foreach ($arrayOrWhere as $value) :
             //   dd($value);
                $query->orWhere($value[0], $value[1], $value[2]);
            endforeach;
        })->where(function ($query) use ($arrayAndWhere) {
            foreach ($arrayAndWhere as $value) :
               // dd($value);
                $query->where($value[0], $value[1], $value[2]);
            endforeach;
        })->where($arrayAndWhere); */

       /* $creditos = Credito::orWhere(function ($query) use ($arrayOrWhere) {
            foreach ($arrayOrWhere as $value) :
                $query->orWhere($value[0], $value[1], $value[2]);
            endforeach;
        })->where($arrayAndWhere);
        return  $creditos; */
    }


}
