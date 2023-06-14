<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recurso1 extends Model
{   
    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'recursos';
    public $timestamps = true;

   /* protected $fillable = [
		'make','model'
	]; */
}