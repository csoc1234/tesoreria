<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditoRequest;
use App\Http\Requests\CreditoBancoRequest;
use App\Models\Credito;
use App\Models\CreditoBanco;
use App\Models\Desembolso;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Helpers\Utilidades;
//use Illuminate\Support\Facades\Redirect;
use App\Http\Requests\DesembolsoRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\URL;

class CreditosController extends Controller
{
    private $pageLimit = 10;
    /**
     * Registrar usuarios.
     *
     * @param  string  $tipo
     * @return \Illuminate\View\View
     */
    public function add()
    {
        Gate::authorize('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO);
        //if(!$verificacion):

        // endif;
        // dd($verificacion);

        $tipoAccion = 'ADD';
        return view('creditos.form_creditos', ['tipoAccion' => $tipoAccion, 'credito' => null]);
    }

    public function edit($id = null)
    {
        Gate::authorize('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO);

        $credito = Credito::obtenerCredito($id);

        if (empty($credito)) :
            Utilidades::msgBox('El credito que intenta editar no existe', "error");
            return Redirect::route('creditos_simulados');
        //return redirect()->route('listadoCreditosSimulados');
        endif;

        $tipoAccion = 'EDIT';
        $credito['valor'] = floatval($credito['valor']);
        return view('creditos.form_creditos', ['tipoAccion' => $tipoAccion, 'credito' => $credito]);
    }

    public function listadoCreditosReales(Request $request)
    {

        Gate::authorize('check-authorization', PERMISO_VER_CREDITOS_SAP);

        // Utilidades::msgBox($request, 'Hola', 'success');

        // dd($tipo);
        $method = $request->method();
        $creditos = [];
        $tipoCredito = 1; //REALES
        $titulo = "Listado de créditos reales";

        if ($request->isMethod('post')) :
            $filtro = $request->input('txtFiltro');

            $condiciones = [
                ['num_ordenanza', 'LIKE', '%' . $filtro . '%'],
                ['tipo_credito_id', '=', $tipoCredito]
            ];
        // $creditos = Credito::obtenerCreditos($condiciones)->paginate($this->pageLimit);
        else :
            $condiciones = [
                ['tipo_credito_id', '=', $tipoCredito]
            ];

        endif;

        $creditos = Credito::obtenerCreditos($condiciones)
            ->take($this->pageLimit)
            ->orderBy('id', 'DESC')
            ->paginate($this->pageLimit);

        return view('creditos.listado_creditos', [
            'creditos' => $creditos,
            'titulo' => $titulo,
            'tipoCredito' => $tipoCredito
        ]);
    }

    public function listadoCreditosSimulados(Request $request)
    {
        Gate::authorize('check-authorization', PERMISO_LISTAR_CREDITOS_SIMULADOS);

        //  $method = $request->method();
        $creditos = [];
        $tipoCredito = 2; //SIMULADOS
        $titulo = "Listado de créditos simulados";

        if ($request->isMethod('post')) :
            $filtro = $request->input('txtFiltro');

            $arrayOrWhere = [
                ['num_ordenanza', 'LIKE', '%' . $filtro . '%'],
                ['id', 'LIKE', '%' . $filtro . '%']
            ];

            $arrayAndWhere = [
                ['tipo_credito_id', '=', $tipoCredito]
            ];

            $creditos = Credito::query()
                ->obtenerCreditos($arrayAndWhere, $arrayOrWhere)
                ->orderBy('id', 'DESC')
                ->take($this->pageLimit);
        else :
            $condiciones = [
                ['tipo_credito_id', '=', $tipoCredito]
            ];
            $creditos = Credito::query()
                ->where('tipo_credito_id', $tipoCredito)
                ->orderBy('id', 'DESC')
                ->take($this->pageLimit);

        endif;

        $creditos =  $creditos->paginate($this->pageLimit);
        $filasDetalle = $this->obtenerFilasDetalle($creditos->toArray()['data']);


        return view('creditos.listado_creditos', compact('creditos', 'titulo', 'filasDetalle', 'tipoCredito'));
    }

    private function obtenerFilasDetalle($listado)
    {
        $datos = [];
        foreach ($listado as $index => $fila) :

            $datos[$index]['Descripción'] = $fila['descripcion'];
            $datos[$index]['Ordenanza'] = $fila['num_ordenanza'];
            $datos[$index]['Valor del credito'] = $this->fijarFormatoDinero($fila['valor']);
            $datos[$index]['Valor usado'] = $this->fijarFormatoDinero($fila['valor_prestado']);
            $datos[$index]['Valor pagado'] = $this->fijarFormatoDinero($fila['valor_pagado']);
            $datos[$index]['Fecha'] = date('d-m-Y', strtotime($fila['fecha']));
            $datos[$index]['Fecha registro'] = date('d-m-Y', strtotime($fila['created_at']));
            $datos[$index]['Estado'] = $fila['estado'];
        endforeach;

        return $datos;
    }
    /*public function index($tipo=null, Request $request)
    {

        $method = $request->method();
        $creditos = null;

        if ($request->isMethod('post')) {
            $filtro = $request->input('txtFiltro');
            $creditos = Credito::where('num_ordenanza', 'LIKE', '%' . $filtro . '%')
                ->where('tipo_credito_id', 1)
                ->orWhere('linea', 'LIKE', '%' . $filtro . '%')
                ->paginate($this->pageLimit);
        } else {
            $creditos = Credito::where('tipo_credito_id', 1)->paginate($this->pageLimit);
        }

        $tipoCredito = 'REAL';
        $titulo = "Listado de creditos registrados en SAP";
        return view('creditos.listado_creditos', compact('creditos', 'titulo', 'tipoCredito'));
    }*/

    /* public function desembolsos()
    {

        return view('creditos.listado_desembolsos', []);
    } */


    public function desembolso($creditoBancoId)
    {
        return view('creditos.form_desembolso', ['creditoBancoId' => $creditoBancoId]);
    }

    public function desembolsos($creditoBancoId = 0)
    {
        return view('creditos.listado_desembolsos', ['creditoBancoId' => $creditoBancoId]);
    }

    /* PETICIONES AJAX */

    public function ajaxGuardarCreditoSimuladoBanco(CreditoBancoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            //OBTENER EL CREDITO
            $credito = Credito::obtenerCredito($requestData['credito_id']);
            $datosCredito = $credito->toArray();

            //VALOR ENVIADO DESDE EL FORMULARIO
            $valorEnviado = $requestData['valor'];
            //CUPO DEL CREDITO
            $valorCredito = $datosCredito['valor'];
            //CUPO DEL CREDITO PRESTADO A LA FECHA
            $valorCreditoPrestado = $datosCredito['valor_prestado'];

            //VALOR QUE SE PUEDE PRESTAR
            $valorQueSePuedePrestar = $valorCredito - $valorCreditoPrestado;

            if ($valorEnviado > $valorQueSePuedePrestar) :
                $errorMsg = 'Error: el credito seleccionado tiene cupo por: $' . number_format($valorQueSePuedePrestar, 2);
                throw new \Exception($errorMsg);
            endif;

            $requestData['tipo_credito_id'] = CREDITO_SIMULADO; //SIMULADO
            $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));
            $creditoBanco = CreditoBanco::create($requestData);

            $this->actualizarValorPrestadoCredito($credito['id']);

            if (!$creditoBanco) :
                throw new \Exception('Error al intentar crear el credito');
            endif;

            unset($datosCredito['created_at']);
            unset($datosCredito['updated_at']);

            //DB::rollBack();
            DB::commit();
            $data['item'] = $requestData;
            $data['credito'] = $datosCredito;
        } catch (\Exception $ex) {

            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxEditarCreditoSimuladoBanco($id, CreditoBancoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro editado correctamente';

        DB::beginTransaction();

        try {

            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            Desembolso::where('credito_banco_id', $id)->delete();


            $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));

            //  dd($requestData);

            $creditoBanco = CreditoBanco::whereId($id)->update($requestData);

            if (!$creditoBanco) :
                DB::rollback();
                throw new \Exception('Error: no fue posible editar el credito');
            endif;

            //ACTUALIZAR VALOR PRESTADO DEL CREDITO
            $this->actualizarValorPrestadoCredito($requestData['credito_id']);

            $creditoBanco = CreditoBanco::where('id', $id)->first();
            DB::commit();
            //dd()
            // $creditoBanco['id'] = $id;
            $data['item'] = $creditoBanco;
            $data['credito'] = $creditoBanco;
        } catch (\Exception $ex) {

            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxBorrarCreditoSimuladoBanco($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Credito borrado correctamente';

        DB::beginTransaction();

        try {

            $condiciones = [
                ['credito_banco_id', $id]
            ];
            Desembolso::borrarDesembolso($condiciones);

            $creditoBanco = CreditoBanco::obtenerCreditoBanco($id);

            if (CreditoBanco::borrarCreditoBanco($id) == 0) :
                throw new \Exception('Error: el credito no fue borrado');
            endif;

            //ACTUALIZAR EL VALOR PRESTADO DEL CREDITO # 1
            $creditoId = $creditoBanco['credito_id'];
            $this->actualizarValorPrestadoCredito($creditoId);

            DB::commit();

            $data['credito_banco_id'] = $id;
        } catch (\Exception $ex) {

            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function actualizarValorPrestadoCredito($creditoId)
    {
        $totalPrestadoCredito = $this->obtenerTotalUsadoCredito($creditoId);

        //  dd($totalPrestadoCredito);

        $arrayCredito['valor_prestado'] = intval($totalPrestadoCredito);
        if (!Credito::whereId($creditoId)->update($arrayCredito)) :
            throw new \Exception('Error: no fue posible actualizar el valor prestado');
        endif;
    }

    public function ajaxBorrarDesembolsosCreditoBanco($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Desembolsos borrados correctamente';



        DB::beginTransaction();

        try {

            $result = Desembolso::where('credito_banco_id', $id)->delete();
            if ($result === 0) :
                $data['message'] = 'No hay registros para borrar';
            else :
                $result = CreditoBanco::whereId($id)->update(['valor_prestado' => 0]);
                if ($result === 0) :
                    throw new \Exception('Error: no fue posible actualizar el valor usado del credito');
                endif;
            endif;

            DB::commit();
            $data['credito_banco_id'] = $id;
        } catch (\Exception $ex) {

            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxVerificarDesembolso($creditoId = null)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro verificado correctamente';
        $data['es_primer_desembolso'] = false;

        try {
            if (empty($creditoId)) :
                throw new \Exception('Error: debe enviar el ID del credito');
            endif;

            //  $esIndependiente = false;
            //  $numDesembolsos = Desembolso::where('credito_banco_id', $creditoId)->count();
            $creditoBanco = CreditoBanco::obtenerCreditoBanco($creditoId);
            $desembolso = Desembolso::obtenerPriDesCredito($creditoId);
            $primerDesembolso = Desembolso::obtenerPriDesCredito($creditoId);

            $fechaFin = null;
            $esPrimerDesembolso = false;

            if (!empty($primerDesembolso)) :
                $fechaFin = $primerDesembolso['fecha_fin'];
            //  $esIndependiente = $primerDesembolso['es_independiente'];
            else :
                $esPrimerDesembolso = true;
            endif;
            // dd($primerDesembolso);


            /*if (empty($numDesembolsos)) :
                $data['es_primer_desembolso'] = true;
            endif; */
            //  $data['es_independiente'] = $esIndependiente;
            $data['credito_banco'] = $creditoBanco;
            $data['es_primer_desembolso'] = $esPrimerDesembolso;
            $data['fecha_fin'] = $fechaFin;
        } catch (\Exception $ex) {

            //  DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }



    public function ajaxGuardarCreditoSimulado(CreditoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';
        DB::beginTransaction();
        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);
            // $this->guardarEditarCredito($requestData);
            $requestData['fecha'] = date('Y-m-d', strtotime($requestData['fecha']));
            $requestData['tipo_credito_id'] = 2; //SIMULADO

            $requestData['created_at'] = date('Y-m-d H:i:s');
            $requestData['updated_at'] = date('Y-m-d H:i:s');

            $registro = Credito::create($requestData);

            if (!$registro) :
                throw new \Exception('Error al intentar guardar el registro');
            endif;

            //AUDITORIA
            Utilidades::saveAdutoria('CREDITOS', 'REGISTRAR_CREDITO', 'creditos', $registro->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxEditarCreditoSimulado($id = null, CreditoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro editado correctamente';

        Gate::authorize('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO);
        // dd($x);

        DB::beginTransaction();
        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);
            $creditoId = $requestData['id'];
            $requestData['fecha'] = date('Y-m-d', strtotime($requestData['fecha']));
            $totalUsado = $this->obtenerTotalUsadoCredito($creditoId);

            if (floatval($requestData['valor']) < floatval($totalUsado)) :
                $msg = 'Error: El valor del credito no puede ser inferior al valor usado actualmente: ' . $this->fijarFormatoDinero($totalUsado);
                throw new \Exception($msg);
            endif;

            // $requestData['created_at'] = date('Y-m-d H:i:s');
            $requestData['updated_at'] = date('Y-m-d H:i:s');

            $registro = Credito::whereId($id)->update($requestData);

            if (!$registro) :
                throw new \Exception('Error al intentar editar el registro');
            endif;

            //AUDITORIA
            Utilidades::saveAdutoria('CREDITOS', 'EDITAR_CREDITO', 'creditos', $id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxBorrarCreditoSimulado($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro borrado correctamente';
        DB::beginTransaction();
        try {
            $credito = Credito::whereId($id);
            if (!$credito->delete()) :
                throw new \Exception('Error al intentar borrar el registro');
            endif;
        } catch (\PDOException $ex) {
            $errorInfo = '';

            if (is_object($ex)) :

                $errorInfo = $ex->errorInfo;
                $errorCode = $errorInfo[1];
                $errorMsg = $errorInfo[2];

                if ($errorCode === 1451) {
                    $msg = 'Error: no puede borrar el credito, ';
                    $msg .= 'está relacionado con otros registros';
                    $data['message'] = $msg;
                } else {
                    $data['message'] = $errorMsg;
                }
            else :
                $data['message'] = $ex->getMessage();
            endif;

            $data['success'] = false;
            //  http_response_code(400);
            $statusCode = 400;

            //AUDITORIA
            Utilidades::saveAdutoria('CREDITOS', 'BORRAR_CREDITO', 'creditos', $id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    public function ajaxObtenerNumDesembolsos($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Verificación correcta';

        try {
            $data['num_desembolsos'] = Desembolso::where('credito_banco_id', $id)->count();
            // $data['num_desembolsos']
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function obtenerTotalUsadoCredito($id)
    {

        $condiciones = [['credito_id', $id]];
        $creditoBancos = CreditoBanco::obtenerCreditos($condiciones)->get()->toArray();

        // dd($creditoBancos);
        if (empty($creditoBancos)) :
            return 0;
        endif;

        $totalUsado = 0;
        foreach ($creditoBancos as $index => $value) :
            $totalUsado += $value['valor'];
        endforeach;

        return $totalUsado;
        //dd($creditoBancos);
    }
    /*private function guardarEditarCredito($requestData, $tipo = 'ADD', $id = 0)
    {
        $requestData['tipo_credito_id'] = 2; //SIMULADO
        $requestData['fecha']  = date('Y-m-d', strtotime($requestData['fecha']));

        $msgError = '';
        // $msgExito = '';
        if ($tipo === 'ADD') :
            $$msgError = 'Error al intentar crear el registro';
            //  $msgExito = 'Registro guardado correctamente';
            $registro = Credito::create($requestData);
        else :
            $msgError = 'Error al intentar editar el registro';
            //   $msgExito = 'Registro editar correctamente';
            $registro = Credito::whereId($id)->update($requestData);
        endif;

        if (!$registro) :
            throw new \Exception($msgError);
        endif;

        //return $msgExito;
    } */

    public function ajaxGuardarDesembolsoSimulado(DesembolsoRequest $request)
    {
        $statusCode = 201;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            //   dd($requestData);

            $creditoId = $requestData['credito_banco_id'];
            // $numDesembolsos = Desembolso::where('credito_banco_id', $desembolso['credito_banco_id'])->count();
            $creditoBanco = CreditoBanco::obtenerCreditoBanco($creditoId);

            $primerDesembolso = Desembolso::obtenerPriDesCredito($creditoId);
            $esIndependiente = (bool)$requestData['es_independiente'];
            $separarCapitalInteres = (bool)$requestData['separar_interes_capital'];
            $truncarTasaEa = (bool)$requestData['truncar_tasa_ea'];

            // dd($creditoBanco->toArray());
            // $creditoBanco
            //SI ES INDEPENDIENTE O ES PRIMER DESEMBOLSO
            if ($esIndependiente || empty($primerDesembolso)) :

                //MODIFICAR EL CREDITO DEL BANCO CON LOS VALORES ENVIADOS SI ES INDEPENDIENTE
                if ($esIndependiente) :
                    $this->modificarCreditoBancoSimulado($creditoBanco, $requestData);
                endif;

                $numAnnosCredito = $creditoBanco['num_annos'];

                //MODIFICAR LA FECHA FINAL
                $requestData['fecha_fin'] = $data['fecha_fin'] = date(
                    'Y-m-d',
                    strtotime($requestData['fecha_inicio'] . ' + ' . $numAnnosCredito . ' years')
                );


                //VALIDAR QUE LA FECHA DE INICIO SEA MENOR QUE LA FECHA DE FIN
                if ($this->validarFecha1Fecha2(
                    date('Y-m-d', strtotime($requestData['fecha_inicio'])),
                    $requestData['fecha_fin']
                )) :
                    throw new \Exception('Error: la fecha inicial debe ser menor que la fecha final');
                endif;

                $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones(
                    $requestData,
                    $creditoBanco,
                    true,
                    [],
                    $truncarTasaEa,
                    $separarCapitalInteres
                );

            //A PARTIR DEL SEGUNDO DESEMBOLSO
            elseif (!empty($primerDesembolso)) :

                $requestData['fecha_fin'] =  $data['fecha_fin'] = $primerDesembolso['fecha_fin'];

                //VALIDAR QUE LA FECHA DE INICIO SEA MENOR QUE LA FECHA DE FIN
                if ($this->validarFecha1Fecha2(
                    date('Y-m-d', strtotime($requestData['fecha_inicio'])),
                    $requestData['fecha_fin']
                )) :
                    throw new \Exception('Error: la fecha inicial debe ser menor que la fecha final');
                endif;

                $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones(
                    $requestData,
                    $creditoBanco,
                    false,
                    $primerDesembolso,
                    $truncarTasaEa,
                    $separarCapitalInteres
                );

            endif;

            //VALOR DEL DESEMBOLSO ENVIADO
            $valorDesemEnviado = $requestData['valor'];
            //VALOR DEL CREDITO USADO
            $valorUsadoCreditoBanco = $this->obtenerTotalUsadoCreditoBancario($creditoId);
            //CUPO DISPONIBLE
            $cupoDisponible = $creditoBanco['valor'] - $valorUsadoCreditoBanco;

            if ($valorDesemEnviado > $cupoDisponible) :
                $msg = 'Error: el valor enviado: ' . $this->fijarFormatoDinero($valorDesemEnviado);
                $msg .= ' excede el cupo disponible: ';
                $msg .= $this->fijarFormatoDinero($cupoDisponible);
                throw new \Exception($msg);
            endif;


            //FORMATEAR FECHAS A FORMATO AÑO-MES-DIA
            $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));
            //  $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));

            //CÓDIFICACIÓN DE LAS CUOTAS EN FORMATO JSON
            $requestData['cuotas_json'] = json_encode($arrayCuotasProyecciones['cuotas']);
            $requestData['proyecciones_json'] = json_encode($arrayCuotasProyecciones['proyecciones']);

            if ($esIndependiente) :
                $condiciones = $requestData;
                unset($condiciones['id']);
                unset($condiciones['cuotas_json']); //QUITAR CUOTAS DE LAS CONDICIONES
                unset($condiciones['proyecciones_json']); //QUITAR PROYECCIONES DE LAS CONDICIONES
                $requestData['condiciones_json'] = json_encode($condiciones);
            endif;

            $requestData['separar_interes_capital'] = $separarCapitalInteres;
            $requestData['truncar_tasa_ea'] = $truncarTasaEa;

            //NO EXISTE EN LA TABLA
            if (isset($requestData['es_primer_desembolso'])) :
                unset($requestData['es_primer_desembolso']);
            endif;

            $desembolso = Desembolso::create($requestData);
            if (!$desembolso) :
                throw new \Exception('Error al intentar crear el desembolso');
            endif;

            //ACTUALIZAR EL VALOR PRESTADO DEL CREDITO BANCARIO
            $this->actualizaValorPrestadoCredBanco($creditoBanco['id']);
            //DB::rollback();
            //  dd($arrayCuotasProyecciones['cuotas']);
            //  $data['desembolso'] = $desembolso;
            //DB::rollback();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function modificarCreditoBancoSimulado(&$creditoBanco, $nuevosValores)
    {
        $creditoBanco->spread = $nuevosValores['spread'];
        $creditoBanco->num_annos = $nuevosValores['num_annos'];
        $creditoBanco->fecha_inicio = date(
            'Y-m-d',
            strtotime($nuevosValores['fecha_inicio'])
        );
        $creditoBanco->periodo_gracia = $nuevosValores['periodo_gracia'];
        $creditoBanco->tasa_ref_valor = $nuevosValores['tasa_ref_valor'];
        $creditoBanco->num_dias = $nuevosValores['num_dias'];
    }

    private function validarFecha1Fecha2($fecha1, $fecha2)
    {
        // dd($fecha2);
        if (strtotime($fecha1) > strtotime($fecha2)) :
            return true;
        endif;
        return false;
    }
    public function ajaxEditarDesembolsoSimulado(DesembolsoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro editado correctamente';

        DB::beginTransaction();

        try {

            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            $creditoId = $requestData['credito_banco_id'];
            $desembolsoId = $requestData['id'];

            //PRIMER DESEMBOLSO
            $primerDesembolso = Desembolso::obtenerPriDesCredito($desembolsoId);

            //DESEMBOLSO ENVIADO
            $desembolso = Desembolso::obtenerDesembolsoPorId($desembolsoId);

            if (empty($desembolso)) :
                throw new \Exception('Error: el desembolso enviado no existe');
            endif;

            $esIndependiente =  $desembolso['es_independiente'];

            //VERIFICAR SI ES PRIMER DESEMBOLSO
            $esPrimerDesembolso = false;

            // dd($esPrimerDesembolso);

            if (!empty($primerDesembolso) && (intval($desembolsoId) === intval($primerDesembolso['id']))) :

                $esPrimerDesembolso = true;

                //OBTENER LOS IDS DE LOS DESEMBOLSOS A BORRAR (NO INDEPENDIENTES)
                $ids = $this->obtenerIdsDesembolsosCredBanco($creditoId);

                //EXCLUIR EL ID DEL DESEMBOLSO ACTUAL
                $tempIds = [];
                foreach ($ids as $value) :
                    if (intval($value) !== intval($desembolsoId)) :
                        $tempIds[] = $value;
                    endif;
                endforeach;



                //BORRAR LOS DESEMBOLSOS ASOCIADOS AL PRIMER DESEMBOLSO
                if (!empty($tempIds)) :
                    $result = Desembolso::whereIn('id', $tempIds)->delete();
                    if (!$result) :
                        $msg = 'Error: no fue posible borrar los desembolsos ';
                        $msg = 'asociados al primer "primer desembolso"';
                        throw new \Exception($msg);
                    endif;
                endif;

            endif;

            /* PONER EN 0 EL SALDO DEL DESEMBOLSO
            PARA QUE NO SE CUENTE COMO UN DESEMBOLSO REALIZADO */

            Desembolso::whereId($desembolsoId)->update(['valor' => 0]);



            //CREDITO DEL DESEMBOLSO
            $creditoBanco = CreditoBanco::obtenerCreditoBanco($creditoId);

            //SI ES INDEPENDIENTE SE DEBE MOFICICAR LAS CONDICIONES DEL CREDITO
            if ($esIndependiente) {

                $this->modificarCreditoBancoSimulado($creditoBanco, $requestData);

                $condiciones = [
                    'spread' => $requestData['spread'],
                    'tasa_ref' => $requestData['tasa_ref'],
                    'tasa_ref_valor' => $requestData['tasa_ref_valor'],
                    'num_dias' => intval($requestData['num_dias']),
                    'num_annos' => intval($requestData['num_annos']),
                    'periodo_gracia' => $requestData['periodo_gracia']
                ];

                $requestData['condiciones_json'] = json_encode($condiciones);
            }

            //MODIFICAR DEL DESEMBOLSO
            $this->modificarRequestDesembolso($requestData);

            $numAnnosCredito = $creditoBanco['num_annos'];

            //FECHA FINAL AGREGANDO EL NÚMERO DE AÑOS DEL CREDITO
            $requestData['fecha_fin'] = $data['fecha_fin'] = date(
                'Y-m-d',
                strtotime($requestData['fecha_inicio'] . ' + ' . $numAnnosCredito . ' years')
            );

            $valorDesemEnviado = $requestData['valor'];

            $valorUsadoCreditoBanco = $this->obtenerTotalUsadoCreditoBancario($creditoId);
            $cupoDisponible = $creditoBanco['valor'] - $valorUsadoCreditoBanco;

            // dd($valorUsadoCreditoBanco);

            if ($valorDesemEnviado > $cupoDisponible) :
                $msg = 'Error: el valor enviado: ' . $this->fijarFormatoDinero($valorDesemEnviado);
                $msg .= ' excede el cupo disponible: ';
                $msg .= $this->fijarFormatoDinero($cupoDisponible);
                throw new \Exception($msg);
            endif;


            //CREACIÓN DE UN DESEMBOLSO INDEPENDIENTE
            if ($requestData['es_independiente'] || $esPrimerDesembolso) :

                $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones(
                    $requestData,
                    $creditoBanco
                );
            else :


                /* if ($esPrimerDesembolso) :
                    //CREACIÓN DEL PRIMER DESEMBOLSO
                    $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones(
                        $requestData,
                        $creditoBanco
                    );
                else : */
                //OBTENCIÓN DEL PRIMER DESEMBOLSO DEL CREDITO
                $primerDesembolso = Desembolso::obtenerPriDesCredito($creditoId);
                //CREACIÓN DE OTROS DESEMBOLSOS
                $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones(
                    $requestData,
                    $creditoBanco,
                    false,
                    $primerDesembolso
                );
            //endif;

            endif;


            //FORMATEAR FECHAS A FORMATO AÑO-MES-DIA
            $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));
            $requestData['fecha_fin'] = date('Y-m-d', strtotime($requestData['fecha_fin']));

            //CÓDIFICACIÓN DE LAS CUOTAS EN FORMATO JSON
            $requestData['cuotas_json'] = json_encode($arrayCuotasProyecciones['cuotas']);
            $requestData['proyecciones_json'] = json_encode($arrayCuotasProyecciones['proyecciones']);


            // unset($requestData['cuotas_json']);
            // unset($requestData['proyecciones_json']);



            //EL DESEMBOLSO NO TIENE ESTOS CAMPOS
            /*  unset($requestData['spread']);
             unset($requestData['tasa_ref']);
             unset($requestData['tasa_ref_valor']);
             unset($requestData['num_dias']);
             unset($requestData['num_annos']);
             unset($requestData['periodo_gracia']);
             unset($requestData['notas']); */

            //ACTUALIZAR EL DESEMBOLSO
            //  dd($requestData);
            $desembolso = Desembolso::whereId($desembolsoId)->update($requestData);
            //$desembolso = $desembolso->update($requestData);

            if (!$desembolso) :
                throw new \Exception('Error al intentar crear el desembolso');
            endif;

            //ACTUALIZAR EL VALOR PRESTADO DEL CREDITO BANCARIO
            $this->actualizaValorPrestadoCredBanco($creditoBanco['id']);


            unset($requestData['cuotas_json']);
            unset($requestData['proyecciones_json']);

            $requestData['cuotas'] = $arrayCuotasProyecciones['cuotas'];
            $requestData['proyecciones'] = $arrayCuotasProyecciones['proyecciones'];

            $data['desembolso'] = $requestData;
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function modificarRequestDesembolso(&$requestData)
    {
        unset($requestData['spread']);
        unset($requestData['tasa_ref']);
        unset($requestData['tasa_ref_valor']);
        unset($requestData['num_dias']);
        unset($requestData['num_annos']);
        unset($requestData['periodo_gracia']);
        unset($requestData['notas']);
    }

    public function ajaxBorrarDesembolsoSimulado($id = 0)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro borrado correctamente';

        DB::beginTransaction();

        try {

            if (!is_numeric($id)) :
                throw new \Exception('Error: debe enviar un ID valido');
            endif;
            //$requestData = $request->all();

            $desembolso = Desembolso::obtenerDesembolsoPorId($id);

            if (empty($desembolso)) :
                throw new \Exception('Error: el desembolso que intenta borrar no existe');
            endif;

            $primerDesembolso = Desembolso::obtenerPriDesCredito($desembolso['credito_banco_id']);
            $creditoBancoId = $desembolso['credito_banco_id'];
            $desembolsoId = $desembolso['id'];
            $creditoBanco = CreditoBanco::obtenerCreditoBanco($creditoBancoId);


            //VERIFICAR SI ES PRIMER DESEMBOLSO
            if (intval($desembolsoId) === intval($primerDesembolso['id'])) :

                //OBTENER LOS IDS DE LOS DESEMBOLSOS A BORRAR
                $ids = $this->obtenerIdsDesembolsosCredBanco($creditoBancoId);

                //EXCLUIR EL ID DEL DESEMBOLSO ACTUAL
                $tempIds = [];
                foreach ($ids as $value) :
                    if (intval($value) !== intval($desembolsoId)) :
                        $tempIds[] = $value;
                    endif;
                endforeach;

                //BORRAR LOS DESEMBOLSOS ASOCIADOS AL PRIMER DESEMBOLSO
                if (!empty($tempIds)) :
                    $result = Desembolso::whereIn('id', $tempIds)->delete();
                    if (!$result) :
                        $msg = 'Error: no fue posible borrar los desembolsos ';
                        $msg = 'asociados al primer "primer desembolso"';
                        throw new \Exception($msg);
                    endif;
                endif;

            endif;



            //BORRAR EL DESEMBOLSO
            Desembolso::whereId($desembolsoId)->delete();

            //ACTUALIZAR EL VALOR PRESTADO DEL CREDITO BANCARIO
            $this->actualizaValorPrestadoCredBanco($creditoBanco['id']);


            $data['desembolso'] =  $desembolso;

            //DB::rollback();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollback();
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function obtenerIdsDesembolsosCredBanco($creditoBancoId)
    {
        $condiciones = [
            ['credito_banco_id', '=', $creditoBancoId],
            ['es_independiente', '=', true]
        ];
        $ids = [];
        $desembolsos = Desembolso::obtenerDesembolsos($condiciones, ['id']);

        foreach ($desembolsos as $value) :
            $ids[] = $value['id'];
        endforeach;

        //dd($ids);

        return  $ids;
    }
    private function actualizaValorPrestadoCredBanco($creditoBancoId)
    {
        $totalPrestadoCreditoBanco = $this->obtenerTotalUsadoCreditoBancario($creditoBancoId);
        $result = CreditoBanco::whereId($creditoBancoId)
            ->update(['valor_prestado' => $totalPrestadoCreditoBanco]);
        if (!$result) :
            throw new \Exception('Error al intentar actualizar el valor prestado del credito');
        endif;
    }
    private function obtenerTotalUsadoCreditoBancario($id)
    {
        $condiciones = [['credito_banco_id', $id]];
        $desembolsos = Desembolso::obtenerDesembolsos($condiciones)->toArray();

        // dd($creditoBancos);
        if (empty($desembolsos)) :
            return 0;
        endif;

        $totalUsado = 0;
        foreach ($desembolsos as $index => $value) :
            $totalUsado += $value['valor'];
        endforeach;

        return $totalUsado;
    }
    /*

    private function obtenerCuotasPeriodoGracia(
        $randomId,
        $numero,
        $fechaFinal,
        $totalSerDeuda,
        $interesesProyectado,
        $saldoDeuda
    ) {
        return [
            'id_cuota' => $randomId,
            'numero' => $numero,
            'fecha' => $fechaFinal,
            'amort_capital' => 0,
            'amort_capital_round' =>  $this->fijarFormatoDinero(0), //'$' . number_format(0, 3, ".", "."),
            'interes_pagado' => 0,
            'concepto' => 'Intereses',
            'total_serv_deuda' => $this->fijarFormatoDinero($totalSerDeuda, 0),
            'interes_proyectado' =>  $this->fijarFormatoDinero($interesesProyectado, 0),
            //    'interes_proyectado' =>  round($interesesProyectado, 0),
            'saldo_deuda' => $saldoDeuda,
            'saldo_deuda_round' => $this->fijarFormatoDinero($saldoDeuda, 0),
            'cambio_valores' => false,
            'es_periodo_gracia' => true
        ];
    } */

    private function obtenerCuotas(
        $randomId,
        $numero,
        $fechaFinal,
        $concepto = '',
        $amortizacionCapital,
        $interesPagado,
        $totalSerDeuda,
        $interesesProyectado,
        $saldoDeuda,
        $cambioValores = false,
        $esPeriodoGracia = false,
        $decimales = 0
    ) {
        return [
            'id_cuota' =>  $randomId,
            'numero' => $numero,
            'fecha' => $fechaFinal,
            'concepto' =>  $concepto,
            'amort_capital' => $amortizacionCapital,
            'amort_capital_round' =>  $this->fijarFormatoDinero($amortizacionCapital,  $decimales),
            'interes_pagado' => $interesPagado,
            'total_serv_deuda' => $this->fijarFormatoDinero($totalSerDeuda,  $decimales),
            'interes_proyectado' =>  $this->fijarFormatoDinero($interesesProyectado,  $decimales),
            //'interes_proyectado' => round($interesesProyectado),
            'saldo_deuda' => $saldoDeuda,
            //PÁGINA https://www.w3schools.com/php/phptryit.asp?filename=tryphp_func_string_number_format
            'saldo_deuda_round' => $this->fijarFormatoDinero($saldoDeuda,  $decimales),
            'cambio_valores' => $cambioValores,
            'es_periodo_gracia' =>  $esPeriodoGracia
        ];
    }

    private function obtenerProyecciones(
        $randomId,
        $numero,
        $tasaRefValor,
        $tasaNominal,
        $tasaNominalPorce,
        $spread,
        $tasaPeriodica,
        $numDiasFinal,
        $tasaEfectivaAnual,
        $fecha,
        $interesesProyectado
    ) {

        return [
            'id_cuota' => $randomId,
            'numero' => $numero,
            'tasa_ref_valor' => floatval($tasaRefValor),
            'tasa_nominal' => $tasaNominal . '%',
            'tasa_nominal_decimal' => $tasaNominalPorce,
            'spread' => floatval($spread),
            'tasa_periodica' => $tasaPeriodica,
            'num_dias' =>  $numDiasFinal,
            'tasa_efectiva_anual' => round(($tasaEfectivaAnual * 100), 3) . '%',
            'tasa_efectiva_anual_decimal' => $tasaEfectivaAnual,
            'anno' => date('Y', strtotime($fecha)),
            'valor' =>  $this->fijarFormatoDinero($interesesProyectado, 0),
            'cambio_valores' => false
        ];
    }



    private function obtenerCuotasProyecciones(
        $desembolso, //Desembolso
        $creditoBanco, //Credito(2) del banco
        $esPriDesem = true, //Variable para indicar si es otro desembolso (no el primero)
        $cuotasPriDesem = [], //Cuotas primer desembolso
        $truncarTasaEa = false,
        $separarCapitalInteres = false
    ) {

        $numCuotas = 0; //Número de cuotas general
        $numCuotasPerGracia = 0; //Número de cuotas periodo de gracia
        $saldoDeuda = 0; //Saldo de la deuda
        $numCuotasSinPerGracia = 0; //Número de cuotas sin periodo de gracia
        $numDiasCreditoBanco = $creditoBanco['num_dias']; //Número de días del credito
        $numAnnos = $creditoBanco['num_annos']; //NÚMERO DE AÑOS DEL CREDITO
        $tieneFechaOtroDesembolso = false; //Variable para indicar si el desembolso tiene otra fecha
        $fechaInicioOtroDesembolso = null;

        //  dd($esPriDesem);

        if ($esPriDesem === false) :

            $arrayVarsOtroDesem = $this->obtenerVariablesOtroDesembolso(
                $desembolso,
                $cuotasPriDesem
            );

            $numCuotas = $arrayVarsOtroDesem['numCuotasOtroDesembolso'];
            $numCuotasPerGracia = $arrayVarsOtroDesem['numCuotasPerGraciaNuevo'];
            $numCuotasSinPerGracia = $arrayVarsOtroDesem['numCuotasSinPerGracia'];


            //dd($numCuotasSinPerGracia);
            //FECHA DE LA PRIMERA CUOTA
            $fecha1 = $arrayVarsOtroDesem['fechaPrimeraCuota'];

            //DIA DE PAGO DE CADA CUOTA
            $diaFecha1 = $arrayVarsOtroDesem['diaPagoOtrosCuotas'];

            //PARA SABER SI TIENE FECHA DE OTRO DESEMBOLSO
            $tieneFechaOtroDesembolso = true;


            //diasDiferencia = DIAS DE DIFERENCIA ENTRE FECHA INICIAL DE LOS OTROS DESEMBOLSOS Y EL PRIMER DESEMBOLSO
            $diasDiferencia = $arrayVarsOtroDesem['diasDiferencia'];

            $fechaInicioOtroDesembolso = $arrayVarsOtroDesem['fechaInicioOriginalOtroDesembolso'];

        else : //PRIMER DESEMBOLSO


            //DIA DE PAGO DE CADA CUOTA
            $diaFecha1 = date('d', strtotime($desembolso['fecha_inicio']));


            //$diasConvertidosEnMes = $numDias / 30;
            $diasConvertidosEnMes = $this->obtenerDiasEnMeses($numDiasCreditoBanco);

            // dd($diasConvertidosEnMes);
            //NÚMERO CUOTAS
            // $numCuotas =  $numAnnos * 12;
            // $numCuotas = $numCuotas / $diasConvertidosEnMes;
            $numCuotas = $this->obtenerNumCuotas($numAnnos, $diasConvertidosEnMes);

            //dd($numAnnos);

            //CUOTAS PERIODO DE GRACIA
            // $periodoGraciaEnMeses = intval($creditoBanco['periodo_gracia']);
            // $numCuotasPerGracia = $periodoGraciaEnMeses / $diasConvertidosEnMes;
            $numCuotasPerGracia = $this->obtenerNumCuoPerGracia(
                $creditoBanco['periodo_gracia'],
                $diasConvertidosEnMes
            );

            // dd($periodoGraciaEnMeses);
            //NÚMERO DE CUOTAS SIN PERIODO DE GRACIA
            $numCuotasSinPerGracia = $numCuotas - $numCuotasPerGracia;

            //FECHA DE INICIO DEL PRIMER DESEMBOLSO
            $fecha1 =  $desembolso['fecha_inicio'];

            //SI ES PRIMER DESEMBOLSO LOS DÍAS DE DIFERENCIA ES EL NÚMERO DE DIAS DEL CREDITO
            $diasDiferencia = $numDiasCreditoBanco;

        endif;

        // $arrayVariables = $this->obtenerVariables($desembolso, $creditoBanco);

        //SPREAD
        $spread = $creditoBanco['spread'];

        //TASA DE REFERENCIA
        $tasaRef = $creditoBanco['tasa_ref'];

        //VALOR DE LA TASA REFERENCIA
        $tasaRefValor = $creditoBanco['tasa_ref_valor'];

        //TASA NOMINAL / TASA NTV
        // $tasaNominal = $tasaRefValor + $spread;
        $tasaNominal = $this->obtenerTasaNominal($tasaRefValor, $spread);

        //TASA NOMINAL CONCERTIDA ENPORCENTAJE
        $tasaNominalPorce = $tasaNominal / 100;

        //OBTENER EL NÚMERO DE PERIODOS DEL AÑO
        // $numPeriodosAnno = $numMesesSumar =  $numDias / 30;
        // $numPeriodosAnno = 12 / $numPeriodosAnno;
        $numPeriodosAnno = $this->obtenerNumPerAnno($numDiasCreditoBanco);

        //OBTENER EL NÚMERO MESES A SUMAR A CADA CUOTA
        $numMesesSumar = $this->obtenerMesesSumar($numDiasCreditoBanco);

        // dd($numPeriodosAnno);
        //TASA PERIODICA
        // $tasaPeriodica = $tasaNominal / $numPeriodosAnno;
        // $tasaPeriodica = $tasaPeriodica / 100;
        $tasaPeriodica = $this->obtenerTasaPeriodica($tasaNominal, $numPeriodosAnno);
        //TASA PERIODICA EN PORCENTAJE
        // $tasaPeriodica = $tasaPeriodica / 100;

        //SALDO DE LA DEUDA
        $saldoDeuda = $desembolso['valor'];

        if ($truncarTasaEa == false) :
            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnual(
                $tasaPeriodica,
                $numDiasCreditoBanco
            );
        else :
            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnualTruncada(
                $tasaNominal,
                $numDiasCreditoBanco
            );
        endif;



        //  $mesGenerados = [];

        /*
            TENER EN CUENTA QUE YA OCUPO LA POSICIÓN 0,
            MES DE REFERENCIA PARA CALCULAR LAS FECHAS DE CADA CUOTA
        */
        //  $mesPiloto =  $mesGenerados[0] = intval(date('m', strtotime($fecha1)));

        //CONTADOR DE MESES
        //  $meses = 0;


        //  dd($numCuotasSinPerGracia);

        $cuotas = [];
        $proyecciones = [];
        //  $ultimoIndexCuotaGracia = 0;
        $amortizacionCapital = $saldoDeuda  / $numCuotasSinPerGracia;

        //dd($numCuotasSinPerGracia);
        //dd($numMesesSumar);


        //PROCESO ESTANDAR
        if ($separarCapitalInteres == false) :

            $arrayCuotasProyecciones = $this->obtenerArrayCuotasProyeccionesEstandar(
                $numCuotas,
                $fecha1,
                $tieneFechaOtroDesembolso,
                $numMesesSumar,
                $diaFecha1,
                $saldoDeuda,
                $tasaNominalPorce,
                $diasDiferencia,
                $numDiasCreditoBanco,
                $numCuotasPerGracia,
                $amortizacionCapital,
                $tasaRefValor,
                $tasaNominal,
                $spread,
                $tasaPeriodica,
                $tasaEfectivaAnual
            );

            $cuotas = $arrayCuotasProyecciones['cuotas'];
            $proyecciones = $arrayCuotasProyecciones['proyecciones'];

        //PROCESO ESPECIAL
        else :

            //  dd($separarCapitalInteres);

            $arrayCuotasProyecciones =  $this->obtenerArrayCuotasProyeccionesEspecial(
                $numCuotas,
                $fecha1,
                $tieneFechaOtroDesembolso,
                $numMesesSumar,
                $diaFecha1,
                $saldoDeuda,
                $tasaNominalPorce,
                $diasDiferencia,
                $numDiasCreditoBanco,
                $numCuotasPerGracia,
                $amortizacionCapital,
                $tasaRefValor,
                $tasaNominal,
                $spread,
                $tasaPeriodica,
                $tasaEfectivaAnual,
                $fechaInicioOtroDesembolso
            );

            $cuotas = $arrayCuotasProyecciones['cuotas'];
            $proyecciones = $arrayCuotasProyecciones['proyecciones'];

        endif;




        //dd($cuotas);
        return [
            'cuotas' => $cuotas,
            'proyecciones' => $proyecciones
        ];
    }




    private function obtenerDiasDiferenciaCuotaCapitalInteres($fecha1, $fecha2)
    {
        $fechaCuotaInteres =  $fecha1;
        $fechaCuotaCapital = $fecha2;
        // dd($fechaCuotaInteres);

        //  dd($fechaCuotaCapital);

        $fechaCuotaCapital = date_create($fechaCuotaCapital); //FECHA PARADA PRIMER DESEMBOLSO
        $fechaCuotaInteres = date_create($fechaCuotaInteres); //FECHA INICIO OTRO DESEMBOLSO


        return date_diff($fechaCuotaInteres, $fechaCuotaCapital)->days - 1;
    }


    private function obtenerArrayCuotasProyeccionesEstandar(
        $numCuotas,
        $fecha1,
        $tieneFechaOtroDesembolso,
        $numMesesSumar,
        $diaFecha1,
        $saldoDeuda,
        $tasaNominalPorce,
        $diasDiferencia,
        $numDiasCreditoBanco,
        $numCuotasPerGracia,
        $amortizacionCapital,
        $tasaRefValor,
        $tasaNominal,
        $spread,
        $tasaPeriodica,
        $tasaEfectivaAnual
    ) {

        $meses = 0;
        $cuotas = [];
        $proyecciones = [];
        $ultimoIndexCuotaGracia = 0;
        $mesGenerados = [];
        //$mesPiloto =  intval(date('m', strtotime($fecha1)));
        $mesPiloto =  $mesGenerados[0] = intval(date('m', strtotime($fecha1)));

        for ($i = 0; $i < $numCuotas; $i++) :

            if ($tieneFechaOtroDesembolso === true) :
                $fecha = date('Y-m-d', strtotime($fecha1));
            else :
                //SUMA DE LOS MESES
                $meses = $meses + $numMesesSumar; //3 YA NO 90 NI 30
                //FECHA GENERADA
                $fecha = date('Y-m-d', strtotime($fecha1 . ' + ' . $meses . ' months'));
            endif;


            $mesfechaGenerada = date('m', strtotime($fecha));
            $annofechaGenerada = date('Y', strtotime($fecha));
            $diaVerificado = $diaFecha1;

            if ($i > 0) :
                $mesPiloto = intval($mesGenerados[$i]); //1+3 = 4
            endif;

            $mesGenerados[] =  $mesfechaGenerada;
            if ($tieneFechaOtroDesembolso === true) :
                $fechaFinal = $fecha1;
            // $tieneFechaOtroDesembolso = false;
            else :
                $fechaFinal = $this->obtenerFechaFinal(
                    $mesPiloto,
                    $annofechaGenerada,
                    $numMesesSumar,
                    $diaVerificado
                );
            endif;

            $randomId = Utilidades::getRandomId();

            /* SI TIENE FECHA DE OTRO DESEMBOLSO, INDICA QUE NO ES UN PRIMER DESEMBOLSO*/
            if ($tieneFechaOtroDesembolso === true) :

                /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

                $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                    $saldoDeuda,
                    $tasaNominalPorce,
                    $diasDiferencia
                );

            else :

                /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            EN ESTE CASO COMO ES UN PRIMER DESEMBOLSO DE UTILIZAN LOS DÍAS DEL CREDITO */


                $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                    $saldoDeuda,
                    $tasaNominalPorce,
                    $numDiasCreditoBanco
                );

            // dd($numDias);
            endif;

            //CUOTAS PERIODO GRACIA
            if ($i < $numCuotasPerGracia) : //

                //dd($numDias);
                $totalSerDeuda  = $interesesProyectado + 0;
                $numero = $i + 1;
                $cuotas[$i] = $this->obtenerCuotas(
                    $randomId,
                    $numero,
                    $fechaFinal,
                    'Intereses',
                    0, //AMORTIZACIÓN CAPIAL
                    0, //INTERES PAGADO
                    $totalSerDeuda,
                    $interesesProyectado,
                    $saldoDeuda,
                    false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                    true, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                    0 //DECIMALES
                );

                $ultimoIndexCuotaGracia = $i;

            //CUOTAS SIN PERIODO GRACIA
            else :

                //ES LA PRIMERA CUOTA DE CAPITAL E INTERES
                if ($i == ($ultimoIndexCuotaGracia + 1)) : // 7
                    $saldoDeuda = $cuotas[$ultimoIndexCuotaGracia]['saldo_deuda'];
                // $saldoDeuda = round($cuotas[$ultimoIndexCuotaGracia]['saldo_deuda'],3,PHP_ROUND_HALF_DOWN);
                elseif ($i > ($ultimoIndexCuotaGracia + 1)) : // 9 > 8
                    $saldoDeuda =  $cuotas[$i - 1]['saldo_deuda']; //8
                endif;


                $interesesProyectado =  0;

                /*
                    SI TIENE FECHA DE OTRO DESEMBOLSO ES PORQUE NO ES UN PRIMER DESEMBOLSO
                */
                if ($tieneFechaOtroDesembolso === true) :

                    /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

                    $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                        $saldoDeuda,
                        $tasaNominalPorce,
                        $diasDiferencia
                    );

                else :

                    /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            COMO ES UN PRIMER DESEMBOLSO SE USAN LOS DÍAS DEL CREDITO */

                    $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                        $saldoDeuda,
                        $tasaNominalPorce,
                        $numDiasCreditoBanco
                    );

                endif;


                $totalSerDeuda  = $interesesProyectado + $amortizacionCapital;
                $saldoDeuda = $saldoDeuda - $amortizacionCapital;
                $numero = $i + 1;

                $cuotas[] = $this->obtenerCuotas(
                    $randomId,
                    $numero,
                    $fechaFinal,
                    'Capital + intereses',
                    $amortizacionCapital, //AMORTIZACIÓN CAPIAL
                    0, //INTERES PAGADO
                    $totalSerDeuda,
                    $interesesProyectado,
                    $saldoDeuda,
                    false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                    false, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                    0 //DECIMALES
                );
            endif;

            $numDiasFinal = 0;
            if ($tieneFechaOtroDesembolso) :
                $numDiasFinal = $diasDiferencia;
            else :
                $numDiasFinal = $numDiasCreditoBanco;
            endif;

            $numero = $i + 1;
            $proyecciones[] = $this->obtenerProyecciones(
                $randomId,
                $numero,
                $tasaRefValor,
                $tasaNominal,
                $tasaNominalPorce,
                $spread,
                $tasaPeriodica,
                $numDiasFinal,
                $tasaEfectivaAnual,
                $fecha,
                $interesesProyectado

            );

            $proyecciones[$i]['saldo_deuda'] = $saldoDeuda;

            //PARA EL PERIODO DE GRACIA
            if ($i < $numCuotasPerGracia) :
                $proyecciones[$i]['saldo_deuda_inter_proy'] = $saldoDeuda; //SALDO DEUDA ANTERIOR
            else :
                $proyecciones[$i]['saldo_deuda_inter_proy'] = $saldoDeuda + $amortizacionCapital; //SALDO DEDUDA ANTERIOR
            endif;

            $tieneFechaOtroDesembolso = false;

        endfor;

        return ['cuotas' => $cuotas, 'proyecciones' => $proyecciones];
    }

    private function obtenerDiferenciaFechas($fecha1, $fecha2)
    {

        $fecha1 = date_create($fecha1);
        $fecha2 = date_create($fecha2);

        $diff = date_diff($fecha2, $fecha1);
        // dd($diff);
        return  $diff;
    }

    private function obtenerInteresProyectadoPorTipo(
        $truncarTasaEa,
        $saldoDeuda,
        $tasaNominalPorce,
        $tasaEfectivaAnual,
        $diasDiffCapInteres
    ) {

        $interesesProyectado  = 0;

        if ($truncarTasaEa == false) :

            /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

            $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                $saldoDeuda,
                $tasaNominalPorce,
                $diasDiffCapInteres
            );

        //PROCESO TRUNCADO
        else :

            /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

            $interesesProyectado = $this->obtenerInteresProyectadoTruncado(
                $saldoDeuda,
                $tasaEfectivaAnual,
                $diasDiffCapInteres
            );
        endif;

        return $interesesProyectado;
    }

    private function obtenerArrayCuotasProyeccionesEspecial(
        $numCuotas,
        $fecha1,
        $tieneFechaOtroDesembolso,
        $numMesesSumar,
        $diaFecha1,
        $saldoDeuda,
        $tasaNominalPorce,
        $diasDiferencia,
        $numDiasCreditoBanco,
        $numCuotasPerGracia,
        $amortizacionCapital,
        $tasaRefValor,
        $tasaNominal,
        $spread,
        $tasaPeriodica,
        $tasaEfectivaAnual,
        $fechaInicioOtroDesembolso,
        $truncarTasaEa = false
    ) {

        $acumuladorMeses = 0;
        $acumuladorMesesInteres = $numMesesSumar;
        // $acumuladorMesesInteres = 0;
        $cuotas = [];
        $ultimoIndexCuotaGracia = 0;
        $mesGenerados = [];

        $mesPiloto =  $mesGenerados[0] = intval(date('m', strtotime($fecha1)));

        $mesPilotoInteres =  $mesGeneradosInteres[0] = intval(date('m', strtotime($fechaInicioOtroDesembolso)));

        $fechaInteres = $fechaInicioOtroDesembolso;



        /* $mesPilotoInteres = $mesGeneradosInteres[0] =
            date('m', strtotime($fechaInicioOtroDesembolso . ' + ' . $acumuladorMesesInteres . ' months')); */

        // $mesPiloto = intval(date('m', strtotime($fecha1)));
        $contadorCapitalInteres = 0;
        $proyecciones = [];

        $cuotasCapital = [];
        $cuotasInteres = [];
        $diasDiff = [];
        $diasDiffCapInteres = $numDiasCreditoBanco;
        $totalInteres01 = 0;
        //   $saldoDeudaConAmortizacion = $saldoDeuda;
        $numeroProyeccion = 0;

        $saldoDeudaConAmortizacionRepetida = 0;
        $indexCuotaRepetida = 0;
        $saldoDeudaCuotaRepetida = 0;

        for ($i = 0; $i < $numCuotas; $i++) :
            /*
              ONTENER LA FECHA INICIAL DEL DESEMBOLSO.
              SI TIENE FECHA DE OTRO DESEMBOLSO (INDICA QUE NO ES EL PRIMERO).
              LA FECHA DE INICIO DE LA PRIMERA CUOTA ES IGUAL A LA FECHA DE PARADA DEL PRIMER DESEMBOLSO,
              RESTANDO CUOTAS DE SER NECESARIO.
              PARA LAS CUOTAS RESTANTES SE SUMA EL NÚMERO DE MESES
             */

            /* if ($tieneFechaOtroDesembolso === true) :
                $fecha = date('Y-m-d', strtotime($fecha1));
            else :
                //SUMA DE LOS MESES
                $meses = $meses + $numMesesSumar; //3 YA NO 90 NI 30
                //FECHA GENERADA
                $fecha = date('Y-m-d', strtotime($fecha1 . ' + ' . $meses . ' months'));
            endif; */

            // if ($i == 0) :
            if ($tieneFechaOtroDesembolso === true) :
                $fecha = date('Y-m-d', strtotime($fecha1));
            //$fechaInteres = date('Y-m-d', strtotime($fechaInicioOtroDesembolso . ' + ' . $numMesesSumar . ' months'));

            else :
                //SUMA DE LOS MESES
                $acumuladorMeses = $acumuladorMeses + $numMesesSumar; //3 YA NO 90 NI 30

                //FECHA GENERADA
                $fecha = date('Y-m-d', strtotime($fecha1 . ' + ' . $acumuladorMeses . ' months'));


            endif;
            // $acumuladorMesesInteres = 9;

            // $acumuladorMesesInteres = $acumuladorMesesInteres + $numMesesSumar;
            $fechaInteres = date('Y-m-d', strtotime($fechaInteres . ' + ' . $acumuladorMesesInteres . ' months'));



            /* MES DE LA FECHA DE INICIAL */
            $mesfechaGenerada = date('m', strtotime($fecha));

            /* MES FECHA INTERESES */
            $mesfechaInteres = date('m', strtotime($fechaInteres));

            /* AÑO DE LA FECHA DE INICIAL */
            $annofechaGenerada = date('Y', strtotime($fecha));

            /* AÑO FECHA INTERESES */
            $annofechaInteres = date('Y', strtotime($fechaInteres));

            /* DIA DE PAGO DE CADA CUOTA REGULAR,
            INDEPENDIENTEMENTE DE SI ES INTERES O CAPITAL  */
            $diaVerificado = $diaFecha1;

            /* DÍA FECHA INTERESES */
            $diafechaInteres = date('d', strtotime($fechaInteres));

            //   dd($diaVerificado);

            /* MES SIGUIENTE DE CADA CUOTA */
            if ($i > 0) :
                $mesPiloto = intval($mesGenerados[$i]); //1+3 = 4
                $mesPilotoInteres = intval($mesGeneradosInteres[$i]);
            endif;

            $mesGenerados[] =  $mesfechaGenerada;
            $mesGeneradosInteres[] =  $mesfechaInteres;





            $randomId = Utilidades::getRandomId();
            $randomIdCuotaInteres = Utilidades::getRandomId();
            //  $dias = $numDiasCreditoBanco;
            // dd($numDias);
            // endif;

            $interesesProyectado = 0;


            /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

            $interesesProyectado  = $this->obtenerInteresProyectadoPorTipo(
                $truncarTasaEa,
                $saldoDeuda,
                $tasaNominalPorce,
                $tasaEfectivaAnual,
                $diasDiffCapInteres
            );

            /* FECHA FINAL CUOTAS DE INTERES */
            //  $fechaFinalInteres = $annofechaInteres . '-' . $mesfechaInteres . '-' . $diafechaInteres;

            $fechaFinalInteres = $this->obtenerFechaFinal(
                $mesPilotoInteres,
                $annofechaInteres,
                $numMesesSumar,
                $diafechaInteres
            );


            //CUOTAS PERIODO GRACIA
            if ($i < $numCuotasPerGracia) : //

                //dd($numDias);
                $totalSerDeuda  = $interesesProyectado + 0;

                $numero = $i + 1;
                $cuotas[$i] = $this->obtenerCuotas(
                    $randomIdCuotaInteres,
                    $numero,
                    $fechaFinalInteres,
                    'Intereses',
                    0, //AMORTIZACIÓN CAPIAL
                    0, //INTERES PAGADO
                    $totalSerDeuda,
                    $interesesProyectado,
                    $saldoDeuda, //EN LAS CUOTAS DE PERIODO DE GRACIA  EL SALDO DE LA DEUDA SIEMPRE ES EL MISMO
                    false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                    true, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                    0 //DECIMALES
                );


                $ultimoIndexCuotaGracia = $i;

            //CUOTAS SIN PERIODO GRACIA
            else :

                /* FECHA FINAL CUOTAS DESPUES PERIODO GRACIA */
                //if ($i === 0) :
                if ($tieneFechaOtroDesembolso === true) :
                    $fechaFinal = $fecha1;
                else :
                    $fechaFinal = $this->obtenerFechaFinal(
                        $mesPiloto,
                        $annofechaGenerada,
                        $numMesesSumar,
                        $diaVerificado
                    );
                endif;


                /*
                    SE OBTIENE EL SALDO DE LA DEUDA
                */

                if ($i == ($ultimoIndexCuotaGracia + 1)) : // PRIMERA CUOTA DESPUES DEL PERIODO DE GRACIA
                    $saldoDeuda = $cuotas[$ultimoIndexCuotaGracia]['saldo_deuda']; //CUOTA ANTERIOR
                    // $saldoDeuda = round($cuotas[$ultimoIndexCuotaGracia]['saldo_deuda'],3,PHP_ROUND_HALF_DOWN);

                    $ultimaCuotaPerGracia = $cuotas[$ultimoIndexCuotaGracia];

                    //NÚMERO DE DÍAS ENTRE LA ULTIMA CUOTA DE INTERES Y LA PRIMERA CUOTA DE CAPITAL

                    //    dd($ultimaCuotaPerGracia['fecha']);
                    /*$diasDiffCapInteres = $this->obtenerDiasDiferenciaCuotaCapitalInteres(
                        $ultimaCuotaPerGracia['fecha'],
                        $fechaFinal //PRIMERA FECHA DE CAPITAL
                    ); */ //23

                    $diasDiffCapInteres = $this->obtenerDiferenciaFechas(
                        $ultimaCuotaPerGracia['fecha'],
                        $fechaFinal
                    );
                    $diasDiffCapInteres = $diasDiffCapInteres->days;


                    /* $now = strtotime($ultimaCuotaPerGracia['fecha']); // or your date as well
                        $your_date = strtotime($fechaFinal);
                        $datediff =  $your_date - $now;
                        dd(round($datediff / (60 * 60 * 24))); */

                    /*  $date1 = new \DateTime( $ultimaCuotaPerGracia['fecha']);
                    $date2 = $date1->diff(new \DateTime( $fechaFinal));
                                            dd($date2->d);
                                            */
                    // dd($diasDiffCapInteres);
                    // $diasDiffCapInteres = $diasDiffCapInteres - 1;

                    $totalInteres01  = $this->obtenerInteresProyectadoPorTipo(
                        $truncarTasaEa,
                        $saldoDeuda,
                        $tasaNominalPorce,
                        $tasaEfectivaAnual,
                        $diasDiffCapInteres
                    );

                    $diasDiff[] = $diasDiffCapInteres;


                // $saldoDeudaConAmortizacion = $saldoDeuda;

                //   dd($saldoDeudaConAmortizacion);

                elseif ($i > ($ultimoIndexCuotaGracia + 1)) : // CUOTAS DESPUES DE LA PRIMERA DE PERIODO DE GRACIA

                    // dd($cuotasCapital[$i]);
                    //SALDO DEUDA ANTERIOR
                    $saldoDeuda =  $cuotasCapital[$i - 1]['saldo_deuda'];

                    //   dd($saldoDeuda);

                    //dd($i);
                    if (($i - 1) == ($ultimoIndexCuotaGracia + 1)) :

                        /*
                            DÍAS DE DIFERENCIA ENTRE LA PRIMERA CUOTA DE CAPITAL E INTERES,
                            LUEGO DEL PERIODO DE GRACIA
                        */




                        /*  $diasDiffCapInteres = $this->obtenerDiasDiferenciaCuotaCapitalInteres(
                            $cuotasCapital[$i - 1]['fecha'], //CUOTA DE CAPITAL
                            $cuotasInteres[$i - 1]['fecha'] // CUOTA DE INTERES
                        ); */ //67

                        $diasDiffCapInteres = $this->obtenerDiferenciaFechas(
                            $cuotasCapital[$i - 1]['fecha'],
                            $cuotasInteres[$i - 1]['fecha']
                        );
                        $diasDiffCapInteres = $diasDiffCapInteres->days;

                        //  dd($diasDiffCapInteres);



                        $totalInteres01  = $totalInteres01 + $this->obtenerInteresProyectadoPorTipo(
                            $truncarTasaEa,
                            $saldoDeuda,
                            $tasaNominalPorce,
                            $tasaEfectivaAnual,
                            $diasDiffCapInteres
                        );

                        $diasDiff[] = $diasDiffCapInteres;

                    endif;

                    //PARA CUOTAS DE INTERES
                    //  $saldoDeudaConAmortizacion = $saldoDeuda + $amortizacionCapital;
                    $diasDiffCapInteres = $numDiasCreditoBanco;

                endif;

                if ($contadorCapitalInteres == 0) :
                    $contadorCapitalInteres = $i + 1;
                endif;


                $interesesProyectado =  0;


                //   dd($interesesProyectado);

                $totalSerDeuda  = $interesesProyectado + $amortizacionCapital;

                //PARA CUOTAS DE CAPITAL
                $saldoDeuda = $saldoDeuda - $amortizacionCapital; //SALDO CUOTA ANTERIOR

                //  $numero = $i + 1;

                $cuotaCapital =  $this->obtenerCuotas(
                    $randomId,
                    $contadorCapitalInteres, //NÚMERO ASIGNADO
                    $fechaFinal,
                    'Capital',
                    $amortizacionCapital, //AMORTIZACIÓN CAPIAL
                    0, //INTERES PAGADO
                    $totalSerDeuda,
                    0, //INTERES PROYECTADO
                    $saldoDeuda,
                    false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                    false, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                    0 //DECIMALES
                );

                $cuotas[] = $cuotasCapital[$i] = $cuotaCapital;

                $contadorCapitalInteres++;

                /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

                $interesesProyectado = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    /*
                    //PARA LAS CUOTAS DESPUES DEL PERIODO DE GRACIA
                    EL INTERES PROYECTADO SE DEBE CALUCULAR CON EL SALDO DE LA FILA ACTUAL,
                    POR ESO SE SUMA LA AMORTIZACIÓN A CAPITAL AL SALDO DE LA DEDUDA,
                    LO CUAL DA COMO RESULTADO EL SALDO DE LA FILA ACTUAL
                    */
                    $saldoDeuda,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $diasDiffCapInteres
                );

                //0 = $amortizacionCapital
                $totalSerDeuda  = $interesesProyectado + 0;

                $cuotaInteres = $this->obtenerCuotas(
                    $randomIdCuotaInteres, //TIENE QUE SER ID DIFERENTE
                    $contadorCapitalInteres, //NÚMERO ASIGNADO
                    $fechaFinalInteres,
                    'Intereses',
                    0, //AMORTIZACIÓN CAPIAL
                    0, //INTERES PAGADO
                    $totalSerDeuda,
                    $interesesProyectado,
                    $saldoDeuda,
                    false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                    false, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                    0 //DECIMALES
                );

                $cuotas[] = $cuotasInteres[$i] = $cuotaInteres;

                $contadorCapitalInteres++;


            endif;

            $numDiasFinal = $diasDiffCapInteres;

            if ($numeroProyeccion == 0) :
                $numeroProyeccion = $i;
            endif;

            $numeroProyeccion++;

            $proyecciones[] = $this->obtenerProyecciones(
                $randomIdCuotaInteres,
                $numeroProyeccion,
                $tasaRefValor,
                $tasaNominal,
                $tasaNominalPorce,
                $spread,
                $tasaPeriodica,
                $numDiasFinal,
                $tasaEfectivaAnual,
                $fecha,
                $interesesProyectado

            );

            //CUOTAS PERIODO GRACIA
            if ($i < $numCuotasPerGracia) : //
                $proyecciones[$i]['cuota_repetida'] = false;
                $proyecciones[$i]['saldo_deuda'] = floatval($saldoDeuda);
                $proyecciones[$i]['saldo_deuda_inter_proy'] = floatval($saldoDeuda);
            else :
                $proyecciones[$i]['cuota_repetida'] = false;
                $proyecciones[$i]['saldo_deuda'] = floatval($saldoDeuda);
                $proyecciones[$i]['saldo_deuda_inter_proy'] = floatval($saldoDeuda + $amortizacionCapital);
            endif;


            //PROYECCIÓN PARA LA CUOTA REPETIDA
            if ($i == ($ultimoIndexCuotaGracia + 1)) :
                //   dd($diasDiff);

                //PROYECCIÓN REPETIDA # 1
                $numeroProyeccion++;
                $proyecciones[] = $this->obtenerProyecciones(
                    $randomIdCuotaInteres,
                    $numeroProyeccion,
                    $tasaRefValor,
                    $tasaNominal,
                    $tasaNominalPorce,
                    $spread,
                    $tasaPeriodica,
                    $numDiasFinal,
                    $tasaEfectivaAnual,
                    $fecha,
                    $interesesProyectado
                );

                //saldoDeudaConAmortizacionRepetida DE LA CUOTA SIGUIENTE (LA CUOTA REPETIDA)
                // $saldoDeudaConAmortizacionRepetida = $saldoDeudaConAmortizacion - $amortizacionCapital;

                $proyecciones[$ultimoIndexCuotaGracia + 1]['cuota_repetida'] = true;
                $proyecciones[$ultimoIndexCuotaGracia + 1]['orden'] = 1;
                $proyecciones[$ultimoIndexCuotaGracia + 1]['saldo_deuda'] =  floatval($saldoDeuda);

                /* PARA LA PRIMERA CUOTA REPETIDA EL SALDO DE LA DEUDA PARA CALCULAR,
                 EL INTERES PROYECTADO DEBE TENER LA SUMA DE AMORTIZACIÓN A CAPITAL,
                 PARA LAS DEMAS PROYECCIONES EL SALDO DE LA DEUDA Y EL  SALDO DE LA DEUDA PARA CALCULAR
                 EL INTERES PROYECTADO DEBE SER EL MISMO */

                $proyecciones[$ultimoIndexCuotaGracia + 1]['saldo_deuda_inter_proy'] = floatval($saldoDeuda + $amortizacionCapital);


                //PARA CALCULAR LA SEGUNDA PROYECCIÓN REPEDIDA
                $indexCuotaRepetida = $i + 1;
                $saldoDeudaCuotaRepetida = $saldoDeuda;


            endif;

            /*
            SEGUNDA CUOTA REPETIDA
            GENERACIÓN DE LA CUOTA REPETIDA
            SI YA PASO LA CUOTA REPETIDA,
            CAMBIAR VALORES DE LA CUOTA REPETIDA CON EL NÚMERO DE DIAS DE DIFERENCIA $diasDiff[1] */

            if ($i == ($ultimoIndexCuotaGracia + 2)) :

                /*
                    CALCULO DEL INTERES PROYECTADO PARA LA CUOTA REPETIDA
                     EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO
                */
                $interesesProyectado = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    $saldoDeudaCuotaRepetida,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $diasDiff[1]
                );

                /*
                PARA LA CUOTA REPETIDA EL SALDO DE LA DEUDA
                Y EL SALDO DE LA DEUDA PARA CALCULAR EL INTERES PROYECTADO ES IGUAL
               */

                //   dd($saldoDeudaCuotaRepetida + $amortizacionCapital);


                $proyecciones[$indexCuotaRepetida]['cuota_repetida'] = true;
                $proyecciones[$indexCuotaRepetida]['orden'] = 2;
                $proyecciones[$indexCuotaRepetida]['saldo_deuda'] =  floatval($saldoDeudaCuotaRepetida);
                $proyecciones[$indexCuotaRepetida]['saldo_deuda_inter_proy'] = floatval($saldoDeudaCuotaRepetida);
                $proyecciones[$indexCuotaRepetida]['num_dias'] = $diasDiff[1];
                $proyecciones[$indexCuotaRepetida]['valor'] = $this->fijarFormatoDinero($interesesProyectado, 0);

            // dd($proyecciones[$indexCuotaRepetida]);
            endif;

            /*
            ESTO SOLO SE REQUIERE PARA LA PRIMERA CUOTA,
            PARA LAS OTRAS CUOTAS SE SUMAN EL NÚMERO DE MESES CORRESPONDIENTE
            */
            $tieneFechaOtroDesembolso = false;
        endfor;

        //INTERES PROYECTADO CONSOLIDADO EN LA CUOTA DONDE SE DEBEN SUMAR
        $cuotas[$ultimoIndexCuotaGracia + 2]['interes_proyectado'] = $this->fijarFormatoDinero($totalInteres01, 0);

        //TOTAL SERVICIO DE LA DEUDA CONSOLIDADO EN LA CUOTA DONDE SE DEBEN SUMAR
        $cuotas[$ultimoIndexCuotaGracia + 2]['total_serv_deuda'] = $this->fijarFormatoDinero($totalInteres01, 0);

        //   $proyecciones[$ultimoIndexCuotaGracia + 2]['valor'] = $this->fijarFormatoDinero($totalInteres01, 0);
        // dd($proyecciones);
        // dd($totalInteres01);

        //LA ULTIMA CUOTA DE INTERES NO SE REQUIERE (NO MOSTRAR)
        unset($cuotas[count($cuotas) - 1]);
        unset($proyecciones[count($proyecciones) - 1]);

        return [
            'cuotas' => $cuotas,
            'proyecciones' => $proyecciones
        ];
    }

    private function obtenerInteresProyectadoEstandar(
        $saldoDeuda,
        $tasaNominalPorce,
        $numDias
    ) {
        $interesesProyectado = $saldoDeuda * $tasaNominalPorce * $numDias;
        $interesesProyectado = $interesesProyectado / 360;
        return $interesesProyectado;
    }

    /*  private function obtenerTipoInteres(
        $truncarTasaEa,
        $saldoDeuda,
        $tasaNominalPorce,
        $tasaEfectivaAnual,
        $diasDiffCapInteres
    ) {

        $interesesProyectado  = 0;

        if ($truncarTasaEa == false) :
            $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                $saldoDeuda,
                $tasaNominalPorce,
                $diasDiffCapInteres
            );

        //PROCESO TRUNCADO
        else :
            $interesesProyectado = $this->obtenerInteresProyectadoTruncado(
                $saldoDeuda,
                $tasaEfectivaAnual,
                $diasDiffCapInteres
            );
        endif;

        return $interesesProyectado;
    } */

    private function obtenerInteresProyectadoTruncado(
        $saldoDeuda,
        $tasaEfectivaAnualTruncada,
        $numDias
    ) {

        $base = 1 + $tasaEfectivaAnualTruncada;
        //  $base = $base * 100;
        $exp = $numDias / 360;
        $result = pow($base, $exp) - 1;
        $interesesProyectado = $saldoDeuda * $result;
        // dd($interesesProyectado);
        // $interesesProyectado = $interesesProyectado / 360;
        $interesesProyectado = $this->truncate($interesesProyectado, 6);

        return $interesesProyectado;
    }

    public function ajaxGuardarJson(Request $request)
    {

        $desembolsoId = $request->desembolsoId;

        $cuotas = $request->session()->get('cuotas_' . $desembolsoId);
        $proyecciones = $request->session()->get('proyecciones_' . $desembolsoId);


        //dd($cuotas);

        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Cuotas guardadas correctamente';

        // $cuotasJson = json_encode($cuotas);
        // $proyeccionesJson = json_encode($proyecciones);

        try {
            $datos['cuotas_json'] = json_encode($cuotas); //json_decode
            $datos['proyecciones_json'] = json_encode($proyecciones); ////json_decode

            //json_decode($json, true);

            // $desembolso = Desembolso::find($desembolsoId);
            // dd($desembolso);

            $desembolso = Desembolso::whereId($desembolsoId)->update($datos);
            if (!$desembolso) :
                throw new \Exception('Error al intentar actualizar guardar las cuotas');
            endif;


            $data['desembolso'] = $desembolso;
            $data['cuotas'] = $cuotas;
            $data['proyecciones'] = $proyecciones;
            //    Session::put('key', 'value');

        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
        }

        return response()->json($data, $statusCode);
    }

    private function obtenerTasaEfectivaAnual($tasaPeriodica, $numDias)
    {
        $base =  1 + $tasaPeriodica;
        $exp = (360 / $numDias); //AQUI ES DONDE USA LOS DIAS DIFERENTES
        // dd($tasaPeriodica);
        $tasaEfectivaAnual = pow($base, $exp) - 1;
        return $tasaEfectivaAnual; //SI ES OTRO DESEMBOLSO
    }

    private function obtenerTasaEfectivaAnualTruncada($tasaNtv, $numDias)
    {
        //tasaNtv = 8.239
        //numDias = 90
        $tasaNtv = $tasaNtv / 100;
        $base = ($tasaNtv / (360 / $numDias) + 1);
        $exp = (365 / $numDias);
        $tasaEfectivaAnual = pow($base, $exp) - 1;
        // dd($this->truncate($tasaEfectivaAnual, 6));
        return $this->truncate($tasaEfectivaAnual, 6);
    }

    /**
     * @example truncate(-1.49999, 2); // returns -1.49
     * @example truncate(.49999, 3); // returns 0.499
     * @param float $val
     * @param int f
     * @return float
     */
    function truncate($val, $f = "0")
    {
        if (($p = strpos($val, '.')) !== false) {
            $val = floatval(substr($val, 0, $p + 1 + $f));
        }
        return $val;
    }

    private function fijarFormatoDinero(
        $valor,
        $decimales = 2,
        $separador1 = ',',
        $separador2 = '.'
    ) {

        return '$' . number_format($valor, $decimales, "$separador1", "$separador2");
    }

    private function obtenerFechaParada($cuotasPriDesem, $indexCuotas)
    {
        $fechaCuotaParadaDesembolso1 = null;

        try {
            $fechaCuotaParadaDesembolso1 =  $cuotasPriDesem[$indexCuotas + 1]['fecha'];
        } catch (\Exception $e) {
            $fechaCuotaParadaDesembolso1 = null;
        }
        return  $fechaCuotaParadaDesembolso1;
    }

    private function obtenerUltimoDiaMes($fechaVerificada)
    {
        $fecha = new \DateTime($fechaVerificada);
        $fecha->modify('last day of this month');
        return $fecha->format('d');
    }

    function cutNum($num, $precision = 2)
    {
        return floor($num) . substr(str_replace(floor($num), '', $num), 0, $precision + 1);
    }
    private function obtenerVariablesOtroDesembolso($desembolso,   $primerDesembolso)
    {
        // $fechasPrimerDesembolso = [];
        //  $numCuotasPerGracia = 0;

        $cuotasPriDesem = json_decode($primerDesembolso['cuotas_json'], true);
        $fechaInicioPrimerDeselbolso = $primerDesembolso['fecha_inicio'];

        // dd($cuotasPriDesem);
        $numcuotasPriDesem = count($cuotasPriDesem);


        //FECHA INICIO OTRO DESEMBOLSO
        $fechaInicioOriginalOtroDesembolso = $desembolso['fecha_inicio'];
        //FECHA DE INICIO DEL OTRO DESEMBOLSO
        $fechaDesembolsoActual = strtotime($desembolso['fecha_inicio']);
        //OBTENER LA FECHA DE PARA DEL DESEMBOLSO 1
        $fechaCuotaParadaDesembolso1 = null;
        //CONTAR LAS CUOTAS DESCARTADAS
        $contadorCuotasDescartadas = 0;
        $contadorCuotasDescartadasPerGracia = 0;


        //12
        $fechaParada = null;

        $contadorCuotasPerGracia = 0;
        //  $fechaParadaEncontrada = false;


        //CICLO PARA ENCONTRAR LA FECHA DE PARADA

        $indexCuotasEncontradas = 0;
        $indexCuotas = 0;
        // dd($fechaInicioOriginalOtroDesembolso);

        //dd($cuotasPriDesem[0]['fecha']);
        $fechaCuotaParadaDesembolso1 = $cuotasPriDesem[0]['fecha'];
        foreach ($cuotasPriDesem as $index => $value) :

            //  if ($index > 0) {
            // $fechasGeneradasPrimerDesembolso[] =  strtotime($value['fecha']);
            // }

            $fechaParada = strtotime($value['fecha']);
            //  $fechaParadaMesDia = date('Y-m', strtotime($value['fecha']));
            //$fechaDesMesDia = date('Y-m-d', $fechaDesembolsoActual);


            //fecha desembolso 20-12-2020

            /*
                1- 18-06-2021 *  => 0 //default
                2- 18-09-2021 *
                3- 18-12-2021 *
                4- 18-03-2022 *
                5- 18-06-2022 *
                6- 18-09-2022 *
                7- 18-12-2022  - encontrada



            */

            //PARADA 18-12-2022




            if ($fechaDesembolsoActual > $fechaParada) { //strtotime($value['fecha'])
                //GENERAL
                $contadorCuotasDescartadas++; //6

                //SOLO PARA SABER PERIODO GRACIA
                if ($value['es_periodo_gracia']) :
                    $contadorCuotasDescartadasPerGracia++; //2
                //$ubicarCuotaPeriodoGracia = true;
                endif;
                //40
                if ($indexCuotas < $numcuotasPriDesem) :
                    //  dd($indexCuotas);
                    //$fechaCuotaParadaDesembolso1 =  $cuotasPriDesem[$indexCuotas + 1]['fecha'];
                    $fechaCuotaParadaDesembolso1 = $this->obtenerFechaParada(
                        $cuotasPriDesem,
                        $indexCuotas
                    );
                /* try {
                        $fechaCuotaParadaDesembolso1 =  $cuotasPriDesem[$indexCuotas + 1]['fecha'];
                    } catch (\Exception $e) {
                        $fechaCuotaParadaDesembolso1 = null;
                    }*/

                endif;

                $indexCuotasEncontradas++;
            }

            $indexCuotas++;

            /*if ($fechaParadaEncontrada === true) {
                $fechaCuotaParadaDesembolso1 =  date('Y-m-d', $fechaParada);
                $fechaParadaEncontrada = false;
            } */

            //CONTADOR TOTAL CUOTAS PERIODO GRACIA
            if ($value['es_periodo_gracia']) :
                $contadorCuotasPerGracia++;
            endif;

        //  $contadorCuotasDescartadas++; */

        //PROBAR EN DETALLE
        /* if ($fechaParadaEncontrada && !$value['es_periodo_gracia']) :
                break;
            endif; */

        //$contadorCuotasDescartadas++;

        /* if ($fechaParada > $fechaDesembolsoActual) :

                if ($value['es_periodo_gracia']) :
                    $ubicarCuotaPeriodoGracia = true;
                endif;

                //PARA CAUNDO EL MES Y EL AÑO SON IGUALES
                /* if (strtotime($fechaParadaMesDia) !== strtotime($fechaDesMesDia)) :
                    $numCuotasPerGracia--;
                endif;

                //FECHA DE PARADA ENCONTRADA
                $fechaCuotaParadaDesembolso1 = $value['fecha'];

                $indiceFechaParada = $index;
                // $x = date('Y-m-d', strtotime($value['fecha']));
                //  dd($x);

                break;
            endif;
            $contadorCuotasDescartadas++; */
        endforeach;


        // dd($contadorCuotasDescartadas);

        // dd($fechaCuotaParadaDesembolso1);

        if (empty($fechaCuotaParadaDesembolso1) && $indexCuotasEncontradas > 0) :
            $msg = 'Error: no fue posible encontrar fecha para calcular el desembolso';
            throw new \Exception($msg);
        endif;


        //REVISAR ESTA PARTE
        /*if ($indiceFechaParada > 0) {
            $indiceFechaParada--;
            $fechaAnteriorParada =  $fechasGeneradasPrimerDesembolso[$indiceFechaParada];

            $fechaDesembolsoActualMesA = date('Y-m', $fechaDesembolsoActual);
            $fechaAnteriorParadaMesA = date('Y-m',  $fechaAnteriorParada);

            if (
                $fechaDesembolsoActual >  $fechaAnteriorParada &&
                strtotime($fechaDesembolsoActualMesA) !== strtotime($fechaAnteriorParadaMesA)
            ) :
                $numCuotasPerGracia = $numCuotasPerGracia - $contadorCuotasDescartadas;
            endif;

            //  dd($fechaCuotaParadaDesembolso1);
        } */
        // $numCuotasPerGracia = 6;



        //DIA DE PAGO OTRAS CUOTAS
        $diaPagoOtrosCuotas = date('d', strtotime($fechaCuotaParadaDesembolso1));
        // $fechaInicioOtroDesembolso =
        $fecha1 = date_create($fechaCuotaParadaDesembolso1); //FECHA PARADA PRIMER DESEMBOLSO
        $fecha2 = date_create($fechaInicioOriginalOtroDesembolso); //FECHA INICIO OTRO DESEMBOLSO

        $diff = date_diff($fecha1, $fecha2);

        //Número de días de diferencia
        // $numDiasDiferencia = $diff->days + 1;
        $numDiasDiferencia = $diff->days;

        $numCuotasOtroDesembolso = $numcuotasPriDesem - $contadorCuotasDescartadas; //38
        // $numCuotasPerGracia = $numCuotasPerGracia - $contadorCuotasDescartadasPerGracia;
        $contadorCuotasPerGracia = $contadorCuotasPerGracia - $contadorCuotasDescartadasPerGracia;
        // dd($numCuotasOtroDesembolso);
        $numCuotasSinPerGracia = $numCuotasOtroDesembolso - $contadorCuotasPerGracia;


        //  dd($numCuotasSinPerGracia);
        $data = [
            'fechaInicioOriginalOtroDesembolso' => $fechaInicioOriginalOtroDesembolso,
            //DIFERENCIA DE DÍAS DE PAGO PRIMERA CUOTA OTRO DESEMBOLSO Y FECHA OTRO DESEMBOLSO
            'diasDiferencia' =>  $numDiasDiferencia,
            'fechaPrimeraCuota' => $fechaCuotaParadaDesembolso1,
            'diaPagoOtrosCuotas' => $diaPagoOtrosCuotas,
            'numCuotasDescartadas' => $contadorCuotasDescartadas,
            'numCuotasPerGraciaDescartadas' => $contadorCuotasDescartadasPerGracia,
            // 'ubicarCuotaPeriodoGracia' => $ubicarCuotaPeriodoGracia,
            'numcuotasPriDesem' => $numcuotasPriDesem,
            'numCuotasOtroDesembolso' => $numCuotasOtroDesembolso,
            'numCuotasPerGraciaNuevo' => $contadorCuotasPerGracia,
            'fechaInicioPrimerDesembolso' => $fechaInicioPrimerDeselbolso,
            'numCuotasSinPerGracia' =>  $numCuotasSinPerGracia
        ];

        return $data;
        //dd($data);
    }

    private function obtenerFechaFinal(
        $mesPiloto,
        $annofechaGenerada,
        $numMesesSumar,
        $diaVerificado
    ) {
        $verificacion = $mesPiloto + $numMesesSumar - 12;
        $mesfechaGenerada = $mesPiloto + $numMesesSumar;

        //  dd($mesfechaGenerada);

        if ($verificacion <= 0) :
            $mesfechaGenerada = intval($mesfechaGenerada);
        else :
            $mesfechaGenerada = intval($verificacion);
        endif;

        $annoDia = $annofechaGenerada . '-' . $mesfechaGenerada;;

        $result = $this->validarFecha(
            intval($annofechaGenerada),
            intval($mesfechaGenerada),
            intval($diaVerificado)
        );

        if ($result === false) :
            $diaVerificado = intval($this->obtenerUltimoDiaMes($annoDia));
        endif;

        $fechaFinal = $annofechaGenerada . '-' . $mesfechaGenerada . '-' . $diaVerificado;
        $fechaFinal = explode('-', $fechaFinal);

        $fechaFinal = $fechaFinal[0] . '-' . $fechaFinal[1] . '-' . $fechaFinal[2];
        $fechaFinal = date('Y-m-d', strtotime($fechaFinal));

        return $fechaFinal;
    }

    private function validarFecha($anno, $mes, $dia)
    {
        return \checkdate($mes, $dia, $anno);
    }

    //CONVERTIR DÍAS EN MESES
    private function obtenerDiasEnMeses($numDias)
    {
        return $numDias / 30;
    }

    //OBTENER EL NÚMERO DE CUOTAS DEL CREDITO
    private function obtenerNumCuotas($numAnnos, $diasEnMes)
    {
        $numCuotas =  $numAnnos * 12;
        $numCuotas = $numCuotas / $diasEnMes;
        return $numCuotas;
    }

    //OBTENER EL NÚMERO DE CUOTAS DEL PERIODO DE GRACIA
    private function obtenerNumCuoPerGracia($perGracia, $diasEnMes)
    {
        $periodoGraciaEnMeses = intval($perGracia);
        $numCuotasPerGracia = $periodoGraciaEnMeses / $diasEnMes;
        return $numCuotasPerGracia;
    }

    //OBTENER EL NÚMERO DE MESES A AGREGAR A CADA FECHA
    private function obtenerMesesSumar($numDias)
    {
        $numMesesSumar =  $numDias / 30;
        return $numMesesSumar;
    }


    //OBTENER EL NÚMERO DE PERIODOS DEL AÑOS
    private function obtenerNumPerAnno($numDias)
    {
        $numPeriodosAnno = $numDias / 30;
        $numPeriodosAnno = 12 / $numPeriodosAnno;
        return $numPeriodosAnno;
    }

    //OBTENER TASA PERIODICA
    private function obtenerTasaPeriodica($tasaNominal, $numPeriodosAnno)
    {
        $tasaPeriodica = $tasaNominal / $numPeriodosAnno;
        $tasaPeriodica = $tasaPeriodica / 100;
        return $tasaPeriodica;
    }

    //OBTENER TASA NOMINAL / TASA NTV
    private function obtenerTasaNominal($tasaRefValor, $spread)
    {
        $tasaNominal = $tasaRefValor + $spread;
        // $tasaNominal = $tasaNominal;
        return $tasaNominal;
    }

    //OBTENER AMORTIZACIÓN A CAPITAL
    /*  private function obtenerAmortCapital($saldoDeuda, $numCuotasSinPerGracia)
    {
        return $saldoDeuda  / $numCuotasSinPerGracia;
    } */

    //OBTENER TASA EFECTIVA ANUAL
    private function obtenerTasaEfecAnual($tasaPeriodica, $numDiasCredito)
    {
        $base =  1 + $tasaPeriodica;
        $exp = (360 / $numDiasCredito);
        $tasaEfectivaAnual = pow($base, $exp) - 1;
        return $tasaEfectivaAnual;
    }

    public function ajaxObtenerDesembolso($desembolsoId)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Cuotas generadas correctamente';

        try {
            // $requestData = $desembolso = $request;


            $desembolso = Desembolso::find($desembolsoId);

            if (empty($desembolso)) :
                throw new \Exception('Error: el desembolso que intenta obtener no existe');
            endif;

            //dd($desembolso);

            $desembolso = $desembolso->toArray();

            $arrayValoresDesembolso = $this->obtenerValoresDesembolso($desembolso);
            // $desembolso->created_at =  date('d-m-Y', strtotime($desembolso->created_at));
            //  $desembolso = $desembolso->toArray();
            unset($desembolso['cuotas_json']);
            unset($desembolso['proyecciones_json']);
            $desembolso['created_at'] =  date('d-m-Y', strtotime($desembolso['created_at']));
            $desembolso['updated_at'] =  date('d-m-Y', strtotime($desembolso['updated_at']));
            $desembolso['valor'] =  intval($desembolso['valor']);

            if ($desembolso['es_independiente']) :
                // $condicionesJson = $desembolso['condiciones_json'];
                $desembolso['condiciones_json'] = json_decode($desembolso['condiciones_json'], true);
            endif;
            // $desembolso['fecha_fin'] =  date('d-m-Y', strtotime($desembolso['fecha_fin']));
            //  dd($desembolso);
            $data['es_primer_desembolso'] = $arrayValoresDesembolso['esPrimerDesembolso']; // $esPrimerDesembolso;
            $data['desembolso'] = $desembolso;
            $data['cuotas'] = $arrayValoresDesembolso['cuotas']; //$cuotas;
            $data['titulo_col_tasa_efectiva'] = $arrayValoresDesembolso['titulo_col_tasa_efectiva']; //$cuotas;
            $data['titulo_col_tasa_nominal'] = $arrayValoresDesembolso['titulo_col_tasa_nominal']; //$cuotas;
            $data['proyecciones'] = $arrayValoresDesembolso['proyecciones'];  //$proyecciones;
            $data['creditoBanco'] =  $arrayValoresDesembolso['creditoBanco']; //$creditoBanco;
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
            $data['message1'] = $ex;
        }

        return response()->json($data, $statusCode);
    }

    private function obtenerValoresDesembolso($desembolso)
    {
        //  $desembolso = $desembolso->toArray();

        $creditoBancoId = $desembolso['credito_banco_id'];

        $creditoBanco = CreditoBanco::find($creditoBancoId);

        $cuotas = json_decode($desembolso['cuotas_json'], true);
        $proyecciones = json_decode($desembolso['proyecciones_json'], true);

        if ($desembolso['es_independiente']) :
            $condiciones = json_decode($desembolso['condiciones_json'], true);
            $arrayTiposTasa = $this->obtenerTipoTasa($condiciones['num_dias']);
        else :
            $arrayTiposTasa = $this->obtenerTipoTasa($creditoBanco['num_dias']);
        endif;

        // $data['titulo_col_tasa_nominal'] = $arrayTiposTasa['tipoTasaNom'];
        // $data['titulo_col_tasa_efectiva'] = $arrayTiposTasa['tipoTasaEfe'];

        $esPrimerDesembolso = false;
        $primerDesembolso = Desembolso::obtenerPriDesCredito($creditoBancoId);

        if (empty($primerDesembolso)) :
            $esPrimerDesembolso = true;
        elseif ($primerDesembolso['id'] ==  $desembolso['id']) :
            $esPrimerDesembolso = true;
        endif;

        // dd($desembolso);
        //MODIFICAR EL FORMATO DE LA FECHA DE CREACIÓN
        $desembolso['created_at'] = date('d-m-Y', strtotime($desembolso['created_at']));
        $desembolso['id'] = $desembolso['id'];
        $desembolso['valor'] = floatval($desembolso['valor']);

        return [
            'esPrimerDesembolso' => $esPrimerDesembolso,
            'cuotas' => $cuotas,
            'proyecciones' => $proyecciones,
            'creditoBanco' => $creditoBanco,
            'titulo_col_tasa_nominal' => $arrayTiposTasa['tipoTasaNom'],
            'titulo_col_tasa_efectiva' => $arrayTiposTasa['tipoTasaEfe'],
        ];
    }

    public function ajaxGenerarExcelDesembolso($desembolsoId)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Excel generado correctamente';

        try {

            $desembolso = Desembolso::find($desembolsoId);

            if (empty($desembolso)) :
                throw new \Exception('Error: el desembolso que intenta obtener no existe');
            endif;

            $arrayValoresDesembolso = $this->obtenerValoresDesembolso($desembolso);

            $creditoBanco = $arrayValoresDesembolso['creditoBanco'];

            $totalFilas = 0;

            $encabezado = [];
            //ENCABEZADO
            $encabezado['A1'] = ['text' => 'CREDITO', 'bold' => true];
            $encabezado['B1'] =  ['text' => $creditoBanco['descripcion'], 'bold' => false];

            $encabezado['A2'] = ['text' => 'FECHA INICIO DESEMBOLSO', 'bold' => true];
            $encabezado['B2'] = ['text' => $desembolso['fecha_inicio'], 'bold' => false];
            $encabezado['C2'] = ['text' => 'FECHA FIN DESEMBOLSO', 'bold' => true];
            $encabezado['D2'] = ['text' => $desembolso['fecha_fin'], 'bold' => false];


            $encabezado['A3'] = ['text' => 'VALOR DESEMBOLSO', 'bold' => true];
            $encabezado['B3'] = ['text' => $desembolso['valor'], 'bold' => false, 'format_money' => true];
            $encabezado['C3'] = ['text' => 'ES INDEPENDIENTE', 'bold' => true];

            $tipo = '';
            if ($desembolso['es_independiente']) :
                $tipo = 'SI';
            else :
                $tipo = 'NO';
            endif;
            $encabezado['D3'] = ['text' =>  $tipo, 'bold' => false];

            $encabezado['A4'] = ['text' => 'FECHA DE CREACIÓN', 'bold' => true];
            $encabezado['B4'] = ['text' => $desembolso['created_at'], 'bold' => false];

            $encabezado['C4'] = ['text' => 'FECHA DE ACTUALIZACIÓN', 'bold' => true];
            $encabezado['D4'] = ['text' => $desembolso['updated_at'], 'bold' => false];


            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->setActiveSheetIndex(0);

            $tituloHoja = 'CUOTAS_PROYECCIONES';
            $objPHPExcel->getActiveSheet()->setTitle($tituloHoja);

            //ESCRIBIR ENCABEZADOS
            Utilidades::setCellValue($objPHPExcel, $encabezado);


            /**************************** CUOTAS *************************************  */
            $tituloCuotas = [
                "A6" => ['text' => "CUOTAS", 'bold' => true],
            ];
            Utilidades::setCellValue($objPHPExcel, $tituloCuotas);

            $filaCabeceraCuotas = 8;
            $cabeceraCuotas = [
                "A" . $filaCabeceraCuotas => ['text' => "NÚMERO DE CUOTA", 'bold' => true],
                "B" . $filaCabeceraCuotas => ['text' => "FECHA", 'bold' => true],
                "C" . $filaCabeceraCuotas => ['text' => "CONCEPTO", 'bold' => true],
                "D" . $filaCabeceraCuotas => ['text' => "AMORTIZACIÓN CAPITAL", 'bold' => true],
                "E" . $filaCabeceraCuotas => ['text' => "INTERES PROYECTADO", 'bold' => true],
                "F" . $filaCabeceraCuotas => ['text' => "INTERES PAGADO", 'bold' => true],
                "G" . $filaCabeceraCuotas => ['text' => "TOTAL SERVICIO DEUDA", 'bold' => true],
                "H" . $filaCabeceraCuotas => ['text' => "SALDO DEUDA", 'bold' => true]
            ];


            //ESCRIBIR CABECERAS
            Utilidades::setCellValue($objPHPExcel, $cabeceraCuotas);

            $inicio = 9; //FILA DE INICIO
            $sleep = 0;
            $cuotas =  $arrayValoresDesembolso['cuotas'];
            $lstCuotas = [];
            foreach ($cuotas as $index => $value) :

                $lstCuotas["A" . $inicio] = ['text' => $value['numero']]; //NÚMERO DE CUOTA
                $lstCuotas["B" . $inicio] = ['text' => $value['fecha']]; //FECHA DE LA CUOTA
                $lstCuotas["C" . $inicio] = ['text' => $value['concepto']]; //CONCEPTO
                $lstCuotas["D" . $inicio] = ['text' => $value['amort_capital_round']]; //AMORTIZACIÓN A CAPITAL
                $lstCuotas["E" . $inicio] = ['text' => $value['interes_proyectado']]; //INTERES PROYECTADO
                $lstCuotas["F" . $inicio] = ['text' => $value['interes_pagado']]; //INTERES PAGADO
                $lstCuotas["G" . $inicio] = ['text' => $value['total_serv_deuda']]; //TOTAL SERVICIO DEUDA
                $lstCuotas["H" . $inicio] = ['text' => $value['saldo_deuda_round']]; //SALDO DEUDA

                //DAR UN ALIVIO AL SERVIDOR
                if ($sleep === 100) :
                    sleep(2);
                    $sleep = 0;
                endif;

                $sleep++;
                $inicio++;

            endforeach;


            //ESCRIBIR CUOTAS
            Utilidades::setCellValue($objPHPExcel, $lstCuotas);

            /**************************** FIN CUOTAS ****************************************  */

            /**************************** PROYECCIONES  *************************************  */
            $totalFilas =  $totalFilas  + 10;
            $totalFilas =  $totalFilas  + count($cuotas);

            $filaTituloProyecciones = $totalFilas;
            $tituloProyecciones = [
                "A" . $filaTituloProyecciones => ['text' => "PROYECCIONES", 'bold' => true],
            ];

            Utilidades::setCellValue($objPHPExcel, $tituloProyecciones);



            $filaCabeceraProyecciones = $totalFilas + 2;
            $cabeceraProyecciones = [
                "A" . $filaCabeceraProyecciones => ['text' => "NÚMERO", 'bold' => true],
                "B" . $filaCabeceraProyecciones => ['text' => "AÑO", 'bold' => true],
                "C" . $filaCabeceraProyecciones => ['text' => "TASA DE REFERENCIA", 'bold' => true],
                "D" . $filaCabeceraProyecciones => ['text' => "SPREAD", 'bold' => true],
                "E" . $filaCabeceraProyecciones => ['text' => "DÍAS", 'bold' => true],
                "F" . $filaCabeceraProyecciones => ['text' => $arrayValoresDesembolso['titulo_col_tasa_nominal'], 'bold' => true],
                "G" . $filaCabeceraProyecciones => ['text' => $arrayValoresDesembolso['titulo_col_tasa_efectiva'], 'bold' => true],
                "H" . $filaCabeceraProyecciones => ['text' => "VALOR", 'bold' => true]
            ];

            Utilidades::setCellValue($objPHPExcel, $cabeceraProyecciones);

            /**************************** PROYECCIONES *************************************  */
            $inicio = $totalFilas + 3; //FILA DE INICIO
            $sleep = 0;
            $proyecciones =  $arrayValoresDesembolso['proyecciones'];

            // dd($filaCabeceraProyecciones);

            // dd($proyecciones);
            $lstProyecciones = [];
            foreach ($proyecciones as $index => $value) :

                $lstProyecciones["A" . $inicio] = ['text' => $value['numero']]; //NÚMERO DE CUOTA
                $lstProyecciones["B" . $inicio] = ['text' => $value['anno']]; //FECHA DE LA CUOTA
                $lstProyecciones["C" . $inicio] = ['text' => $value['tasa_ref_valor']]; //CONCEPTO
                $lstProyecciones["D" . $inicio] = ['text' => $value['spread']]; //AMORTIZACIÓN A CAPITAL
                $lstProyecciones["E" . $inicio] = ['text' => $value['num_dias']]; //INTERES PROYECTADO
                $lstProyecciones["F" . $inicio] = ['text' => $value['tasa_nominal']]; //INTERES PAGADO
                $lstProyecciones["G" . $inicio] = ['text' => $value['tasa_efectiva_anual']]; //TOTAL SERVICIO DEUDA
                $lstProyecciones["H" . $inicio] = ['text' => $value['valor']]; //SALDO DEUDA

                //DAR UN ALIVIO AL SERVIDOR
                if ($sleep === 100) :
                    sleep(2);
                    $sleep = 0;
                endif;

                $sleep++;
                $inicio++;

            endforeach;


            //ESCRIBIR PROYECCIONES
            Utilidades::setCellValue($objPHPExcel, $lstProyecciones);
            /**************************** FIN PROYECCIONES ****************************************  */


            //ANCHO DE LAS COLUMNAS
            Utilidades::setWidth($objPHPExcel, 30);

            $writer = new Xlsx($objPHPExcel);
            $nombreArchivo = 'CUOTAS_PROYECCIONES';


            $writer->save(public_path() . '/reportes/' . $nombreArchivo . ".xlsx");
            $data['success'] = true;
            $data['archivoExcel'] = URL::asset('reportes/' . $nombreArchivo . '.xlsx');
        } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
            $data['message1'] = $ex;
        }

        return response()->json($data, $statusCode);
    }



    public function ajaxGuardarActualizarCuotas(Request $request)
    {

        $data = [];
        $statusCode = 200;
        $proyeCambiadas = $request->proyecciones;
        $cuotasCambiadas = $request->cuotas;
        $guardarCambios = $request->guardar_cambios;
        $desembolsoId = $request->desembolso_id;

        // dd($proyeCambiadas);


        $arrayCuotasProyecciones = $this->guardarActualizarCuotas(
            $proyeCambiadas,
            $cuotasCambiadas,
            $desembolsoId,
            $guardarCambios
        );

        $data['cuotas'] = $arrayCuotasProyecciones['cuotas'];
        $data['proyecciones'] = $arrayCuotasProyecciones['proyecciones'];

        // Session::put('cuotas_' . $desembolsoId, $data['cuotas']);
        // Session::put('proyecciones_' . $desembolsoId, $data['proyecciones']);

        $data['success'] = true;
        return response()->json($data, $statusCode);
    }

    private function guardarActualizarCuotas(
        $proyeCambiadas,
        $cuotasCambiadas,
        $desembolsoId,
        $guardar = false
    ) {
        $data = [];
        $statusCode = 200;

        $desembolso = Desembolso::find($desembolsoId);

        if (empty($desembolso)) :
            throw new \Exception('Error: el desembolso enviado no existe');
        endif;

        $cuotasDesembolso = json_decode($desembolso['cuotas_json'], true);
        $proyeDesembolso = json_decode($desembolso['proyecciones_json'], true);
        $proyeCambiadas = json_decode($proyeCambiadas, true);
        $cuotasCambiadas = json_decode($cuotasCambiadas, true);
        $truncarTasaEa = $desembolso['truncar_tasa_ea'];

        // dd($cuotasDesembolso);

        $nuevasProyecciones = [];

        for ($index = 0; $index < count($proyeDesembolso); $index++) :

            $indexProyeCambiadas = 0;
            for ($indexProyeCambiadas; $indexProyeCambiadas < count($proyeCambiadas); $indexProyeCambiadas++) :
                if (
                    $proyeDesembolso[$index]['id_cuota'] === $proyeCambiadas[$indexProyeCambiadas]['id_cuota'] &&
                    $proyeDesembolso[$index]['numero'] === $proyeCambiadas[$indexProyeCambiadas]['numero']
                ) :
                    $nuevasProyecciones[] = $proyeCambiadas[$indexProyeCambiadas];
                    $proyeDesembolso[$index]['cambio_valores'] = true;
                    break;
                endif;
            endfor;

            $indexCuotasCambiadas = 0;
            for ($indexCuotasCambiadas; $indexCuotasCambiadas < count($cuotasCambiadas); $indexCuotasCambiadas++) :
                if ($cuotasDesembolso[$index]['id_cuota'] === $cuotasCambiadas[$indexCuotasCambiadas]['id_cuota']) :
                    $cuotasDesembolso[$index]['interes_pagado'] = $cuotasCambiadas[$indexCuotasCambiadas]['interes_pagado'];
                    $cuotasDesembolso[$index]['cambio_valores'] = true;
                    break;
                endif;
            endfor;

        endfor;

        $creditoBanco = CreditoBanco::find($desembolso['credito_banco_id']);

        //Número de días
        $numDiasCredito = $creditoBanco['num_dias']; //90 o 30

        /* $fecha1 = date_create($creditoBanco['fecha_inicio']);
        $fecha2 = date_create($creditoBanco['fecha_fin']);
        $diff = date_diff($fecha1, $fecha2);*/

        //Número de años
        $numAnnos = $creditoBanco['num_annos'];

        //NÚMERO CUOTAS
        //   $numCuotas =  $numAnnos * 12;
        // $diasConvertidosEnMes = $numDiasCredito / 30;
        $diasConvertidosEnMes = $this->obtenerDiasEnMeses($numDiasCredito);
        // $numCuotas = $numCuotas / $diasConvertidosEnMes;
        /*  $numCuotas = $this->obtenerNumCuotas(
            $numAnnos,
            $diasConvertidosEnMes
        ); */

        //NUM CUOTAS PERIODO GRACIA
        $numCuotasPeriodoGracia = $this->obtenerNumCuoPerGracia(
            $creditoBanco['periodo_gracia'],
            $diasConvertidosEnMes
        );

        //NÚMERO DE CUOTAS SIN PERIODO DE GRACIA
        //  $numCuotasSinPerGracia = $numCuotas - $numCuotasPeriodoGracia;

        $saldoDeuda = $desembolso['valor'];
        // $saldoDeudaProyeccion = 0;
        //  $amortizacionCapital = $this->obtenerAmortCapital($saldoDeuda, $numCuotasSinPerGracia);
        // $ultimoIndexCuotaGracia = 0;
        //SPREAD
        //      $spread = $creditoBanco['spread'];
        //   $ultimoIndexCuotaGracia = 0;
        //RECORRER LAS CUOTAS
        for ($i = 0; $i < count($cuotasDesembolso); $i++) :
            // for ($i = 0; $i < count($cuotasDesembolso)-1; $i++) :


            if ($i > 0) :
                //SALDO DE LAS CUOTAS PERIODO GRACIA
                //  if ($i < $numCuotasPeriodoGracia) :
                $saldoDeuda = $cuotasDesembolso[$i - 1]['saldo_deuda'];
            //   $ultimoIndexCuotaGracia = $i;
            // $ultimoIndexCuotaGracia = $i;
            //    else :
            //  else : // 9 > 8
            //    $saldoDeuda = $cuotasDesembolso[$i - 1]['saldo_deuda']; // - $amortizacionCapital;
            // $saldoDeudaProyeccion = $proyeDesembolso[$i]['saldo_deuda'];
            // endif;
            endif;


            //VERIFICAR SI LA PROYECCIÓN DE LA CUOTA CAMBIO
            $cuotaId = $cuotasDesembolso[$i]['id_cuota'];

            $proyeccion = $this->obtenerProyeccionesCuota(
                // $cuotasDesembolso[$i]['id_cuota'],
                $cuotaId,
                $nuevasProyecciones //PROYECCIONES CAMBIADAS
            );


           // dd($proyeccion);
            //dd($proyeDesembolso);
            // dd($cuotasDesembolso);
            //SI CAMBIÓ
            if (!empty($proyeccion)) :

                $interesesProyectado = 0;

                //  dd( $nuevasProyecciones);

                $proyeccRepetidas = [];

                //INTERES PARA CUOTAS REPETIDAS
                if (isset($proyeccion['cuota_repetida']) && $proyeccion['cuota_repetida']) :


                    $proyeccRepetidas = $this->obtenerProyeccionesRepetidas(
                        $cuotaId,
                        $nuevasProyecciones, //PROYECCIONES CAMBIADAS
                        $proyeDesembolso //PROYECCIONES DEL DESEMBOLSO
                    );

                    $intProTemp = 0;

                    //  dd($proyeccRepetidas);

                    foreach ($proyeccRepetidas as $index => $proy) :

                        $intPro = $this->obtenerInteresProyectadoCuota(
                            $numDiasCredito,
                            $truncarTasaEa,
                            $proy
                        );

                        //   dd($intPro);
                        //ACTUALIZAR EL VALOR DE LA PROYECCIÓN (INTERES PROYECTADO)
                        //  if ($proyeccion['numero'] == $proy['numero']) :

                        // $proyeccion['valor'] = $intPro;
                        $proy['valor'] = $intPro;
                        //ACTUALIZAR LA PROYECCIÓN
                        $this->actualizarProyeccion(
                            $proyeDesembolso,
                            $proy,
                            $numDiasCredito, //PARA OBTENER EL NÚMERO DE PERIODOS DEL AÑO
                            $truncarTasaEa
                        );


                        //SUMATORIO DEL INTERES PROYECTO PARA LAS CUOTAS REPETIDAS
                        $intProTemp = $intProTemp +  $intPro;




                    endforeach;

                    $interesesProyectado = $intProTemp;

                //INTERES PARA CUOTAS INDIVIDUALES
                else :

                    $interesesProyectado = $this->obtenerInteresProyectadoCuota(
                        /*
                        $numDiasCredito PARA OBTENER EL NÚMERO DE PERIODOS DEL AÑO Y LA
                        $tasaEfectivaAnual
                        */
                        $numDiasCredito,
                        $truncarTasaEa,
                        $proyeccion
                    );


                    //  dd($proyeccion);

                    //ACTUALIZAR EL VALOR DE LA PROYECCIÓN (INTERES PROYECTADO)
                    $proyeccion['valor'] =  $interesesProyectado;

                    //ACTUALIZAR LA PROYECCIÓN
                    $this->actualizarProyeccion(
                        /*
                        LISTADO DE PROYECCIONES DEL DESEMBOLSO
                        */
                        $proyeDesembolso,
                        $proyeccion,
                        /*
                        $numDiasCredito PARA OBTENER EL NÚMERO DE PERIODOS DEL AÑO Y LA
                        $tasaEfectivaAnual
                        */
                        $numDiasCredito,
                        $truncarTasaEa
                    );

                endif;

                //ACTUALIZAR LA CUOTA
                $cuotasDesembolso[$i] = $this->actualizarCuota(
                    $cuotasDesembolso[$i], //CUOTA A ACTUALIZAR
                    $interesesProyectado //NUEVO INTERES PROYECTADO DE LA CUOTA
                    /* $saldoDeuda,
                    $proyeccion,
                    $truncarTasaEa,
                    $proyeccRepetidas */ //PARA SACAR LA PROYECCIÓN REPETIDA
                );

            //  dd($proyeccion);

            //INTERES PROYECTADO DE LA CUOTA
            // $interesProye = $cuotasDesembolso[$i]['interes_proyectado'];

            // dd($proyeDesembolso);

            // dd($i);
            //  if($i==8):
            //   dd($proyeCambiadas);
            //endif;

            //ACTUALIZAR LA PROYECCIÓN
            /* $this->actualizarProyeccion(
                    $proyeDesembolso,
                    $proyeccion,
                    $numDiasCredito, //PARA OBTENER EL NÚMERO DE PERIODOS DEL AÑO
                    $truncarTasaEa
                ); */

            endif;
        endfor;

        $data['cuotas'] = $cuotasDesembolso;
        $data['proyecciones'] = $proyeDesembolso;

        if ($guardar) :
            $datosJson['cuotas_json'] = json_encode($data['cuotas']); //json_decode
            $datosJson['proyecciones_json'] = json_encode($data['proyecciones']); ////json_decode
            $datosJson['updated_at'] = date('Y-m-d H:i:s');

            Desembolso::whereId($desembolsoId)->update($datosJson);
        /* if (!$desembolso) :
                throw new \Exception('Error al intentar actualizar guardar las cuotas');
            endif;*/

        endif;

        return $data;
    }

    private function obtenerProyeccionesRepetidas($cuotaId, $proyeCambiadas, $proyecciones)
    {
        //OBTENER LAS PROYECCIONES REPETIDAS, BUSCANDO EN LAS PROYECCIONES
        $proyeccRepetidas = [];

        $index = 0;
        $until = count($proyecciones);
        for ($index; $index < $until; $index++) :
            if ($proyecciones[$index]['id_cuota'] == $cuotaId) :
                $proyeccRepetidas[] = $proyecciones[$index];
            endif;
        endfor;

        //BUSCAR LAS PROYECCIONES REPETIDAS EN LAS PROYECCIONES CAMBIADAS
        $index = 0;
        $until = count($proyeccRepetidas);

        for ($index; $index < $until; $index++) :

            $until2 = count($proyeCambiadas);
            $index2 = 0;

            for ($index2; $index2 < $until2; $index2++) :

                if (
                    $proyeccRepetidas[$index]['numero'] == $proyeCambiadas[$index2]['numero'] &&
                    $proyeccRepetidas[$index]['id_cuota'] == $proyeCambiadas[$index2]['id_cuota']
                ) :
                    $proyeccRepetidas[$index] = $proyeCambiadas[$index2];
                    break;
                endif;

            endfor;
        endfor;

        return $proyeccRepetidas;
    }

    private function actualizarProyeccion(
        &$proyecciones,
        $proyeccion,
        $numDiasCredito,
        // $intProyeCuota, //INTERES PROYECTADO DE LA CUOTA
        $truncarTasaEa = false
    ) {


        /* $tasaNominal = $proyeccion['tasa_ref_valor'] +  $proyeccion['spread'];
        $tasaNominal = $tasaNominal; */

        $tasaNominal = $this->obtenerTasaNominal(
            $proyeccion['tasa_ref_valor'],
            $proyeccion['spread']
        );


        //$tasaNominalPorc = $tasaNominal / 100;

        // dd($numDiasCredito);

        //OBTENER PERIODOS DEL AÑO

        /*$numPeriodosAnno = $numDiasCredito / 30;
        $numPeriodosAnno = 12 / $numPeriodosAnno; */
        $numPeriodosAnno = $this->obtenerNumPerAnno($numDiasCredito);

        //  dd($numPeriodosAnno);

        //TASA PERIODICA
        /*$tasaPeriodica = $tasaNominal / $numPeriodosAnno;
        $tasaPeriodica = $tasaPeriodica / 100; */
        $tasaPeriodica = $this->obtenerTasaPeriodica($tasaNominal, $numPeriodosAnno);
        // $tasaPeriodica = $tasaPeriodica / 100;

        //NÚMERO DE DIAS DE LA PROYECCIÓN
        // $numDiasProyeccion = $proyeccion['num_dias'];

        //TASA EFECTIVA ANUAL
        /*$base =  1 + $tasaPeriodica;
        $exp = (360 / $numDiasCredito);
        $tasaEfectivaAnual = pow($base, $exp) - 1;*/

        //dd($numDiasProyeccion);

        /*
            LA TASA EFECTIVA ANUAL SE CALCULA CON EL NÚMERO DE DÍAS DEL CREDITO
         */
        if ($truncarTasaEa == false) :

            /* LA TASA EFECTIVA ANUAL SE CALCULA CON LOS DÍAS DEL CREDITO,
            NO CON LOS DÍAS DE LA PROYECCIÓN */

            $tasaEfectivaAnual = $this->obtenerTasaEfecAnual(
                $tasaPeriodica,
                //$numDiasProyeccion
                $numDiasCredito
            );

        //  dd($tasaEfectivaAnual);
        else :

            /* LA TASA EFECTIVA ANUAL SE CALCULA CON LOS DÍAS DEL CREDITO,
            NO CON LOS DÍAS DE LA PROYECCIÓN */

            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnualTruncada(
                $tasaNominal,
                // $numDiasProyeccion
                $numDiasCredito
            );

        // dd($tasaEfectivaAnual);
        endif;

        //  dd($tasaPeriodica);

        //  dd($tasaEfectivaAnual);
        // dd( $proyeccion['tasa_ref_valor']);

        // dd($tasaEfectivaAnual);
        // $proyeccion['numero'] =  $proyeccion['numero'];
        $proyeccion['spread'] =  $proyeccion['spread'];
        $proyeccion['num_dias'] =  $proyeccion['num_dias'];
        $proyeccion['tasa_nominal_decimal'] =  $tasaNominal;
        $proyeccion['tasa_nominal'] =  $tasaNominal . '%';
        $proyeccion['tasa_efectiva_anual_decimal'] = $tasaEfectivaAnual;
        $tasaEfectivaAnual = $tasaEfectivaAnual * 100;
        $proyeccion['tasa_efectiva_anual'] = round($tasaEfectivaAnual, 3) . '%';
        $proyeccion['valor'] = $this->fijarFormatoDinero(floatval($proyeccion['valor']), 0);

        //  dd($proyeccion);

        foreach ($proyecciones as $index => $proye) :
            if ($proye['numero'] == $proyeccion['numero']) :
                $proyecciones[$index] =  $proyeccion;
                break;
            endif;
        endforeach;
        //  return $proyeccion;
    }

    private function obtenerInteresProyectadoCuota(
        $numDiasCredito,
        $truncarTasaEa,
        $proyeccion
    ) {


        $tasaEfectivaAnual = 0;
        $tasaNominalPorce = 0;
        $interesesProyectado = 0;


        //  dd($proyeccion['saldo_deuda_inter_proy']);


        $tasaRefValor = $proyeccion['tasa_ref_valor'];
        $saldoDeuda =  $proyeccion['saldo_deuda_inter_proy'];
        $spread =  $proyeccion['spread'];

        // dd($proyeccion['saldo_deuda_inter_proy']);
        $tasaNominal = $this->obtenerTasaNominal($tasaRefValor, $spread);

        $numDiasProyeccion = $proyeccion['num_dias'];

        /* $interesesProyectado = $saldoDeuda * $tasaNominal * $numDiasProyeccion;
        $interesesProyectado = $interesesProyectado / 360; */

        /*  if ($truncarTasaEa === false) :
            $tasaEfectivaAnual = $this->obtenerTasaEfecAnual(
                $tasaPeriodica,
                $numDiasCredito
            );
        else :
            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnualTruncada(
                $tasaNominal,
                $numDiasCredito
            );
        endif; */

        //DETERMINAR SI LA CUOTA ES REPEDITA
        //  $proyeccionRepetida = $proyeccion['cuota_repetida'];




        //INTERES PROYECTADO ESPECIAL
        if ($truncarTasaEa == true) :
            /* LA TASA EFECTIVA ANUAL SE CALCULA CON LOS DÍAS DEL CREDITO,
            NO CON LOS DÍAS DE LA PROYECCIÓN */
            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnualTruncada(
                $tasaNominal,
                $numDiasCredito
            );

        /*
            $interesesProyectado = $this->obtenerInteresProyectadoTruncado(
                $saldoDeuda,
                $tasaEfectivaAnual,
                $numDiasProyeccion
            ); */

        //INTERES PROYECTADO NORMAL
        else :

            //TASA NOMINAL CONVERTIDA EN PORCENTAJE TASA NTV
            $tasaNominalPorce = $tasaNominal / 100;

        /* $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                $saldoDeuda,
                $tasaNominal,
                $numDiasProyeccion
            ); */
        endif;


        /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
            NO CON LOS DÍAS DEL CREDITO */

        // dd($saldoDeuda);
        $interesesProyectado = $this->obtenerInteresProyectadoPorTipo(
            $truncarTasaEa,
            $saldoDeuda,
            $tasaNominalPorce,
            $tasaEfectivaAnual,
            $numDiasProyeccion
        );


       // dd($interesesProyectado);
        return $interesesProyectado;
    }
    private function actualizarCuota(
        $cuota,
        //  $saldoDeuda,
        //  $proyeccion,
        // $truncarTasaEa,
        $interesesProyectado
        //  $proyeccionesRepetidas = []
    ) {



        // $saldoDeuda = $cuota





        $cuota['interes_proyectado'] = $this->fijarFormatoDinero($interesesProyectado, 0);
        $totalSerDeuda = $interesesProyectado +  $cuota['amort_capital'];

        $cuota['total_serv_deuda'] =  $this->fijarFormatoDinero($totalSerDeuda, 0);

        // $saldoDeuda = $saldoDeuda -  $cuota['amort_capital'];
        // $cuota['saldo_deduda'] = $saldoDeuda;
        // $cuota['saldo_deuda_round'] = $this->fijarFormatoDinero($saldoDeuda, 0);
        return $cuota;
    }

    private function obtenerProyeccionesCuota($cuotaId, $proyecciones)
    {
        for ($i = 0; $i < count($proyecciones); $i++) :
            if ($proyecciones[$i]['id_cuota'] === $cuotaId) :
                return $proyecciones[$i];
            endif;
        endfor;

        return [];
    }


    private function obtenerTipoTasa($dias)
    {
        $tipoTasa = '';
        $tipoTasaEfe = '';

        switch ($dias) {
            case 30:
                $tipoTasa = 'TASA NMV';
                $tipoTasaEfe = 'TASA EA MV';
                break;
            case 60:
                $tipoTasa = 'TASA NBV';
                $tipoTasaEfe = 'TASA EA BV';
                break;
            case 90:
                $tipoTasa = 'TASA NTV';
                $tipoTasaEfe = 'TASA EA TV';
                break;
            case 120:
                $tipoTasa = 'TASA NCV';
                $tipoTasaEfe = 'TASA EA CV';
                break;
            case 180:
                $tipoTasa = 'TASA NSV';
                $tipoTasaEfe = 'TASA EA SV';
                break;
        }

        return [
            'tipoTasaNom' => $tipoTasa,
            'tipoTasaEfe' => $tipoTasaEfe
        ];
    }
}
