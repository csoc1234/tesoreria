<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
    /*public function ajaxCargarRegistros($tabla)
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
    } */

    public function ajaxCargarRegistros(Request $request, $tabla)
    {
        $data = [];
        $data['success'] = false;
        $statusCode = 200;

        try {
            $listado = [];
            if (isset($request->filtro)) {
                $valor = $request->valor;
                $columna = $request->columna;
                $listado = DB::table($tabla)->where($columna, $valor)->get();
            } else {
                $listado = DB::table($tabla)->get();
            }

            $data['listado'] = $listado;
            $data['success'] = true;
            $data['message'] = 'Registro(s) cargado(s) correctamente';
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['message'] = 'Error: ' . $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxCargarRoles()
    {
        $data = [];
        $data['success'] = false;
        $statusCode = 200;

        try {
            $listado = [];
            $query = DB::table('roles');
            $query->where('visible', '=', 1);
            $listado =  $query->get();

            $data['listado'] = $listado->toArray();;
            $data['success'] = true;
            $data['message'] = 'Registro(s) cargado(s) correctamente';
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['message'] = 'Error: ' . $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }
}
