<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
//use App\Models\User;

class RecursosController extends Controller
{
    /**
     * Registrar usuarios.
     *
     * @param  string  $tipo
     * @return \Illuminate\View\View
     */
    public function add()
    {

        return view('recursos.form_recurso', ['tipo' => 'add']);
    }
}
