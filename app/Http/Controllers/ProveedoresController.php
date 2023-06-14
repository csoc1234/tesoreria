<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
//use App\Models\User;

class ProveedoresController extends Controller
{
    /**
     * Registrar usuarios.
     *
     * @param  string  $tipo
     * @return \Illuminate\View\View
     */
    public function add()
    {

        return view('proveedores.form_proveedor', ['tipo' => 'add']);
    }
}
