<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banco extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'bancos';
    public $timestamps = true;

    //CAMPOS QUE NO APARECERAN EN CONSULTAS
    protected $hidden = [];

    //CAMPOS QUE NO SE PUEDEN MODIFICAR
    protected $guarded = ['id'];

    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'descripcion',
        'created_at',
        'updated_at'
    ];

    public function creditosbancos()
    {
        return $this->hasMany(CreditoBanco::class);
    }

    public static function obtenerBancos($arrayAndWhere = [], $arrayOrWhere = [])
    {
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
