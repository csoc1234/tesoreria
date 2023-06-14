<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TipoVinculacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use App\Http\Requests\BancoRequest;
use App\Helpers\Utilidades;

class TipoVinculacionesController extends Controller
{
    private $pageLimit = 10;
    private $Model = 'App\\Models\\TipoVinculacion';

    public function index(Request $request)
    {

        Gate::authorize('check-authorization', PERMISO_PARAMETRIZAR_DATOS);

        //FILTROS
        $filtroTitulo = '';
        $filtrar = false;

        if (isset($request->filtrar)) :
            $filtrar = true;
        endif;

        $filtroTitulo = (!empty($request->input('titulo')) ? $request->input('titulo') : '');
        $arrayOrWhere = [];
        if ($filtrar) :
            if (!empty($filtroTitulo)) :
                $arrayOrWhere[] = ['id', '=', $filtroTitulo];
                $arrayOrWhere[] = ['descripcion', 'LIKE', '%' . $filtroTitulo . '%'];
            endif;
        endif;

        $tiposVinculacion =  $this->Model::obtenerListado([], $arrayOrWhere)
            ->skip(0)
            ->take($this->pageLimit)
            ->orderBy('id', 'DESC')
            //  ->withQueryString()
            ->paginate($this->pageLimit)
            ->withQueryString();

        //  dd($bancos);
        return view('tipos_vinculacion.listado_tipos_vinculacion', [
            'tiposVinculacion' => $tiposVinculacion,
            'filtroTitulo' => $filtroTitulo
        ]);
    }

    public function ajaxGuardarRegistro(BancoRequest $request)
    {
        $statusCode = 201;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {

            Gate::authorize('check-authorization', PERMISO_PARAMETRIZAR_DATOS);

            $requestData = $request->all();
            Utilidades::cleanString($requestData);
            // $modelo = 'App\\Models\\' . $this->Model;

            $registro = $this->Model::create($requestData);

            if (!$registro) :
                throw new \Exception('Error al intentar guardar el registro');
            endif;

            Utilidades::saveAdutoria('TIPOS_VINCULACION', 'CREAR_TIPO_VINCULACION', 'tipos_vinculacion', $registro->id);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 401;
            $data['success'] = false;

            $message = Utilidades::getAjaxUnauthorizedMessage($ex->getMessage());

            if (empty($message)) :
                $data['message'] = $ex->getMessage();
            else :
                $data['message'] = $message;
            endif;
        }


        return response()->json($data, $statusCode);
    }

    public function ajaxEditarRegistro($id = null, BancoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro editado correctamente';

        //  Gate::authorize('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO);
        // dd($x);

        DB::beginTransaction();

        try {

            Gate::authorize('check-authorization', PERMISO_PARAMETRIZAR_DATOS);

            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            //  $modelo = 'App\\Models\\' . $this->Model;

            $registro = $this->Model::whereId($id)->update($requestData);

            if (!$registro) :
                throw new \Exception('Error al intentar editar el registro');
            endif;

            Utilidades::saveAdutoria('TIPOS_VINCULACION', 'EDITAR_TIPO_VINCULACION', 'tipos_vinculacion', $id);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 401;
            $data['success'] = false;

            $message = Utilidades::getAjaxUnauthorizedMessage($ex->getMessage());

            if (empty($message)) :
                $data['message'] = $ex->getMessage();
            else :
                $data['message'] = $message;
            endif;
        }


        return response()->json($data, $statusCode);
    }

    public function ajaxBorrarRegistro($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro borrado correctamente';

        DB::beginTransaction();

        try {

            Gate::authorize('check-authorization', PERMISO_PARAMETRIZAR_DATOS);

            $tipoVinculacion = $this->Model::whereId($id);
            $jsonData = json_encode($tipoVinculacion->get()->toArray());

            $tipoVinculacion->delete();
            // $result->delete();
            // dd($result);
            /* if (!$result) :
                throw new \Exception('Error: no fue posible borrar el registro');
            endif; */
            Utilidades::saveAdutoria(
                'TIPOS_VINCULACION',
                'BORRAR_TIPO_VINCULACION',
                'tipos_vinculacion',
                $id,
                $jsonData
            );

            DB::commit();
        } catch (\PDOException $ex) {
            DB::rollBack();
            $errorInfo = '';
            $statusCode = 400;
            if (is_object($ex)) {
                $errorInfo = $ex->errorInfo;
                $errorCode = $errorInfo[1];
                $errorMsg = $errorInfo[2];

                if ($errorCode === 1451) {
                    $msg = 'No puede borrar el registro seleccionado, ';
                    $msg .= 'está referenciado/relacionado en otros registros';
                    $data['message'] = $msg;
                } else {
                    $data['message'] = $errorMsg;
                }
            } else {
                $data['message'] = $ex->getMessage();
            }

            //http_response_code(400);
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 401;
            $data['success'] = false;

            $message = Utilidades::getAjaxUnauthorizedMessage($ex->getMessage());

            if (empty($message)) :
                $data['message'] = $ex->getMessage();
            else :
                $data['message'] = $message;
            endif;
        }


        return response()->json($data, $statusCode);
    }
}
