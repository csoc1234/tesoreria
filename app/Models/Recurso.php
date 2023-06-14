<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recurso extends Model
{
    use HasFactory;

    public $incrementing = true;
    protected $primaryKey = 'id';
    protected $table = 'recursos';
    public $timestamps = true;

      /* protected $fillable = [
		'make','model'
    ]; 
    
    protected $hidden = [
		'make','model'
	];
    
    */
}
