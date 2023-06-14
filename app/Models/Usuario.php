<?php

namespace App\Models;

use App\Helpers\Utilidades;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

//use Illuminate\Database\Eloquent\Model;

class Usuario extends Authenticatable
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'usuarios';
    public $timestamps = true;

    //CAMPOS QUE NO APARECERAN EN CONSULTAS
    protected $hidden = [
        'password'
    ];

    protected $dateFormat = 'Y-m-d';

    //CAMPOS QUE NO SE PUEDEN MODIFICAR
    protected $guarded = ['id'];

    //CAMPOS QUE SE PUEDEN MODIFICAR
    protected $fillable = [
        'tipo_vinculacion_id',
        'genero_id',
        'identificacion',
        'rol_id',
        'nombres',
        'apellidos',
        'telefono',
        'celular',
        'email',
        'perfil',
        'direccion',
        'fecha_inicio_vinculacion',
        'fecha_fin_vinculacion',
        'password',
        'estado',
        'created_at',
        'updated_at'
    ];

    /* public static function boot()
     {
         parent::boot();

         self::creating(function($model){
             // ... code here
             dd($model);
         });

         self::created(function($model){
             // ... code here
         });

         self::updating(function($model){
             // ... code here
         });

         self::updated(function($model){
             // ... code here
         });

         self::deleting(function($model){
             // ... code here
         });

         self::deleted(function($model){
             // ... code here
         });
     } */

    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id', 'id');
    }


    // Name mutator
   /* public function setFechaInicioVinculacionAttribute($value)
    {
       // dd($value);
        $this->attributes['fecha_inicio_vinculacion'] = date('Y-m-d', strtotime($value));
    }

    public function setFechaFinVinculacionAttribute($value)
    {
        $this->attributes['fecha_fin_vinculacion'] = date('Y-m-d', strtotime($value));
    } */

   /* public function getFechaInicioVinculacionAttribute($value)
    {
       // dd($value);
        $this->attributes['fecha_inicio_vinculacion'] = date('Y-m-d', strtotime($value));
    }

    public function getFechaFinVinculacionAttribute($value)
    {
        $this->attributes['fecha_fin_vinculacion'] = date('Y-m-d', strtotime($value));
    } */

    public function getRolAttribute()
    {
        return $this->rol()->first();
    }

    public static function obtenerUsuarioPorCondiciones(
        $andConditions,
        $orConditions = []
    ) {
        $query = self::query();

        if (!empty($andConditions)):
            $query = $query->where($andConditions);
        endif;

        if (!empty($orConditions)):
            $query = $query->orWhere($orConditions);
        endif;

        $query = $query->first();

        //dd($query);

        return $query;
    }

    public static function obtenerListadoUsuarios($fields, $filtro, $pageLimit)
    {
        $userId = Utilidades::getUserId();

        $query = self::query();
        $query =$query->select($fields);

        if (!empty($filtro)) :
            $query = $query->where(
                function ($q) use ($filtro) {
                    $q->where('identificacion', 'LIKE', '%' . $filtro . '%');
                    $q->orWhere('nombres', 'LIKE', '%' . $filtro . '%');
                }
            );
        endif;

        $query =$query->where('rol_id', '!=', ROL_ADMINISTRADOR);
        $query =$query->where('id', '!=', $userId);
        $query =$query->orderBy('id', 'DESC');
        $query =$query->take($pageLimit);

        return $query;
    }

    public function getPermisosAttribute()
    {
        $rol = $this->rol()->first();
        //  dd($rol);
        $permissions = Rol::whereId(intval($rol->id))
            //->select('permisos.slug')
            ->with('permisos')
            ->first()->toArray();

        return  $permissions['permisos'];
    }
}
