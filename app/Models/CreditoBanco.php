<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use phpDocumentor\Reflection\Types\Self_;

class CreditoBanco extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'creditos_bancos';
    public $timestamps = true;

    //CAMPOS QUE NO APARECERAN EN CONSULTAS
    protected $hidden = [];

    //CAMPOS QUE NO SE PUEDEN MODIFICAR
    protected $guarded = ['id'];

    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'credito_id',
        'banco_id',
        'linea',
        'descripcion',
        'spread',
        'fecha_inicio',
        // 'fecha_fin',
        'num_annos',
        'periodo_gracia',
        'valor',
        'num_dias',
        'tasa_ref',
        'tipo_credito_id',
        'tasa_ref_valor'
    ];

    public function credito()
    {
        return $this->belongsTo(Credito::class);
    }

    public function banco()
    {
        return $this->belongsTo(Banco::class);
    }

    public static function obtenerCreditoBanco($id)
    {
        return self::firstWhere('id', $id);
    }
    public static function obtenerCreditos($condiciones)
    {
        return self::where($condiciones);
    }

    public function desembolsos()
    {
        return $this->hasMany(Desembolso::class);
    }

    public static function borrarCreditoBanco($id)
    {
        return self::where('id', $id)->delete();
    }

    public static function obtenerCreditosBancosPorCondiciones($arrayAndWhere, $arrayOrWhere = [], $fields = [])
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

        $query = $query->where($arrayAndWhere)->get();

        return $query;
    }
}
