<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class GenerosController extends Controller
{
    public function ajaxCargarGeneros($tabla)
    {
        $data = [];
        $data['success'] = false;
        $statusCode = 200;

        try {

            $listado = DB::table($tabla)->get();
            $data['listado'] = $listado;
            $data['success'] = true;
            $data['message'] = 'Registro(s) cargado(s) correctamente';

        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['message'] = 'Error: ' . $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }
}
