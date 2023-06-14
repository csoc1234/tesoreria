<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Desembolso extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'creditos_desembolsos';
    public $timestamps = true;

    //CAMPOS QUE NO APARECERAN EN CONSULTAS
    protected $hidden = [];

    //CAMPOS QUE NO SE PUEDEN MODIFICAR
    protected $guarded = ['id'];

    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'credito_banco_id',
        'valor',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'es_independiente',
        'banco_id',
        'credito_id',
        'condiciones_json',
        'cuotas_json',
        'proyecciones_json',
        'truncar_tasa_ea',
        'separar_interes_capital',
        'created_at',
        'updated_at'
    ];



    public function creditobanco()
    {
        return $this->belongsTo(CreditoBanco::class);
    }

    public static function borrarDesembolso($condiciones)
    {
        return Self::where($condiciones)->delete();
    }

    public static function obtenerPriDesCredito($creditoId)
    {
        return Self::where([
            ['credito_banco_id', '=', $creditoId],
            ['es_independiente', '=', false]
        ])
            ->first();
    }

    public static function obtenerDesembolsoPorId($id)
    {
        return Self::where('id', $id)->first();
    }

    public static function obtenerDesembolsos($condiciones, $fields = [])
    {
        if (!empty($fields)) :
            return Self::where($condiciones)->select($fields)->get();
        else :
            return Self::where($condiciones)->get();
        endif;
    }

    public static function obtenerNumDesemCredBanco($creditoId)
    {
        return Self::where('credito_banco_id', $creditoId)->count();
    }
}
