<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CreditoBanco;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CreditosBancosController extends Controller
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

    public function ajaxCargarCreditosBancosPorCreditoId(Request $request, $creditoId)
    {
        $data = [];
        $data['success'] = false;
        $statusCode = 200;

        try {

            $listado = [];

            $listado = CreditoBanco::with('banco')
            ->where('credito_id', $creditoId)
            ->get()
            ->toArray();

            for ($i = 0; $i < count($listado); $i++) :
                $listado[$i]['valor'] = floatval($listado[$i]['valor']);
            endfor;

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
