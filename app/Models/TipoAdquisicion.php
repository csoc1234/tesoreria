<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAdquisicion extends Model
{
  use HasFactory;

  public $incrementing = true;
  protected $primaryKey = 'id';
  protected $table = 'tipo_adquisicion';
  public $timestamps = true;
}
