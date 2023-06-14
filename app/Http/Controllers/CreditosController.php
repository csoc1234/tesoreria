<?php

namespace App\Http\Controllers;

use App\Models\Credito;
use App\Models\Desembolso;
use App\Helpers\Utilidades;
use App\Models\CreditoBanco;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\CreditoRequest;

use App\Http\Requests\DesembolsoRequest;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Http\Requests\CreditoBancoRequest;
use SAPNWRFC\Connection as SapConnection;
use SAPNWRFC\Exception as SapException;

use Illuminate\Support\Facades\File;

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
        try {
            Gate::authorize('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO);

            $tipoAccion = 'ADD';
            return view('creditos.form_creditos', ['tipoAccion' => $tipoAccion, 'credito' => null]);
        } catch (\Exception $ex) {
            // dd($ex->getMessage());

            if ($ex->getMessage() == 'This action is unauthorized.') :
                return Utilidades::adminUnauthorizedLogout();
            endif;

            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }
    }

    public function edit($id = null)
    {
        $credito = null;

        try {
            Gate::authorize('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO);

            $credito = Credito::obtenerCredito($id);

            if (empty($credito)) :
                Utilidades::msgBox('El crédito que intenta editar no existe', "error");
                return Redirect::route('creditos_simulados');
                //return redirect()->route('listadoCreditosSimulados');
            endif;


            $tipoAccion = 'EDIT';
            $credito['valor'] = floatval($credito['valor']);

            return view('creditos.form_creditos', ['tipoAccion' => $tipoAccion, 'credito' => $credito]);
        } catch (\Exception $ex) {
            // dd($ex->getMessage());

            if ($ex->getMessage() == 'This action is unauthorized.') :
                return Utilidades::adminUnauthorizedLogout();
            endif;

            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }
    }

    public function listadoCreditosReales(Request $request)
    {
        $creditos = null;

        try {
            Gate::authorize('check-authorization', PERMISO_VER_CREDITOS_SAP);

            // Utilidades::msgBox($request, 'Hola', 'success');

            // dd($tipo);
            $method = $request->method();
            $creditos = [];
            $tipoCredito = 1; //REALES
            $titulo = "Listado de créditos SAP";

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
        } catch (\Exception $ex) {
            // dd($ex->getMessage());

            if ($ex->getMessage() == 'This action is unauthorized.') :
                return Utilidades::adminUnauthorizedLogout();
            endif;

            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }
    }
    public function buscarLineaCreditoSap(Request $request)
    {
        //  Gate::authorize('check-authorization', PERMISO_LISTAR_CREDITOS_SIMULADOS);
        $creditos = [];
        $tipoCredito = 2; //SIMULADOS
        $titulo = "Líneas de crédito SAP";

        try {
            Gate::authorize('check-authorization', PERMISO_VER_CREDITOS_SAP);

            return view('creditos.buscar_linea_credito_sap', [
                'creditos' => $creditos,
                'titulo' => $titulo,
                'tipoCredito' => $tipoCredito
            ]);
        } catch (\Exception $ex) {
            // dd($ex->getMessage());

            if ($ex->getMessage() == 'This action is unauthorized.') :
                return Utilidades::adminUnauthorizedLogout();
            endif;

            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }

        // return view('creditos.buscar_linea_credito_sap', compact('creditos', 'titulo', 'filasDetalle', 'tipoCredito'));
    }

    private function obtenerNumDiasTasaRef($val)
    {
        $val = trim($val);
        switch ($val):
            case 'IBR 3M':
                return 3 * 30;
            case 'IBR 1M':
                return 1 * 30;
        endswitch;

        $msg = 'La tasa de referencia "' . $val;
        $msg .= '" es desconocida para la aplicación. ';
        $msg .= 'Debe consultar al administrador del sistema.';
        throw new \Exception($msg);
        //return 0;
    }


    public function ajaxObtenerInfoLineaSap($numLinea = 0)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Información de la línea cargada correctamente';

        try {
            Gate::authorize('check-authorization', PERMISO_VER_CREDITOS_SAP);

            if (empty($numLinea) or $numLinea == 'undefined' or $numLinea == 'null') :
                throw new \Exception('Debe enviar el número de la línea');
            endif;

            $arrayDatos = [];

            /* BUSCAR LA LÍNEA EN SAP*/
            if (TEST_LOCAL == 0) :
                $arrayDatos = $this->getSapData($numLinea);
                /* BUSCAR LA LÍNEA LOCALMENTE USANDO ARCHIVOS JSON*/
            elseif (TEST_LOCAL == 1) :
                $arrayDatos = $this->getSapDataLocalJson($numLinea);
            endif;

            /* VALIDAR LA CONEXIÓN A SAP */
            $this->validarConexionSap($arrayDatos);

            /* ASIGNAR INFO DE LA LÍNEA */
            $this->setInfoLinea($arrayDatos);

            /* DESEMBOLSOS DE LA LINEA */
            $this->setDesembolsosLinea($arrayDatos);

            /* INTERES */
            $arrayIntereses = $arrayDatos['T_REVISINTER'];

            /* FLUJO DE CAJA */
            foreach ($arrayDatos['T_FLUJOCAJA'] as $index => $value) :
                $fechaCuota = $value['DFAELL'];
                $interes = $this->buscarInteresCuota($fechaCuota, $arrayIntereses);

                $tasaInteres = '';
                //$valorInteres = '';
                if (!empty($interes)) :
                    $tasaInteres = $interes['INT_VALUE']; /* TASA_INTERES */
                endif;

                $date = '';
                if (!empty($interes['IRA_DATE'])) :
                    $date = $interes['IRA_DATE']; /* FECHA DE LA TASA_INTERES */
                endif;

                $DFAELL = $arrayDatos['T_FLUJOCAJA'][$index]['DFAELL'];
                $arrayDatos['T_FLUJOCAJA'][$index]['DFAELL'] = date('Y-m-d', strtotime($DFAELL));
                $arrayDatos['T_FLUJOCAJA'][$index]['ID'] = Utilidades::getRandomId();
                $arrayDatos['T_FLUJOCAJA'][$index]['IRA_DATE'] = $date;
                $arrayDatos['T_FLUJOCAJA'][$index]['INT_VALUE'] = $tasaInteres; /* TASA_INTERES */

            endforeach;


            if (!isset($_SESSION)) :
                session_start();
            endif;

            $_SESSION['arrayDatosLinea'] = $arrayDatos;

            $data['DATOS_LINEA'] = $arrayDatos;
            /*  } catch (\Exception $ex) {
            $statusCode = 400;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
            // $data['message1'] = $ex;
        } */
        } catch (\Exception $ex) {
            //DB::rollback();
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

    private function validarConexionSap($arrayDatos)
    {
        $msg = '';

        if (empty($arrayDatos['S_DEUDAGRAL'])) :
            $msg = 'No fue posible acceder a la información de la línea. ';
            $msg .= 'Debe verificar la conexión al servidor';

            throw new \Exception($msg);
        endif;

        if (empty(trim($arrayDatos['S_DEUDAGRAL']['RFHA']))) :
            throw new \Exception('No se registra información asociada a la línea enviada');
        endif;
    }

    private function setInfoLinea(&$arrayDatos)
    {
        $arrayDatos['INFO_LINEA']['RFHA'] = $arrayDatos['S_DEUDAGRAL']['RFHA']; //NÚMERO DE LÍNEA
        // $arrayDatos['INFO_LINEA']['RFHA'] = '611516840'; //NÚMERO DE LÍNEA
        $arrayDatos['INFO_LINEA']['DBLFZ'] = date('Y-m-d', strtotime($arrayDatos['S_DEUDAGRAL']['DBLFZ'])); //FECHA INICIO LÍNEA
        $arrayDatos['INFO_LINEA']['DELFZ'] = date('Y-m-d', strtotime($arrayDatos['S_DEUDAGRAL']['DELFZ'])); //FECHA FIN LÍNEA
        $arrayDatos['INFO_LINEA']['KONTRH'] = $arrayDatos['S_DEUDAGRAL']['KONTRH']; //FECHA FIN LÍNEA
        $arrayDatos['INFO_LINEA']['IMPORTE_LIMITE'] = $arrayDatos['S_DEUDAGRAL']['IMPORTE_LIMITE']; //MONTO DE LA LINEA
    }

    private function setDesembolsosLinea(&$arrayDatos)
    {
        foreach ($arrayDatos['T_DEUDAPUBL'] as $index => $value) :
            $arrayDatos['T_DEUDAPUBL'][$index]['DBLFZ'] = date('Y-m-d', strtotime($value['DBLFZ']));
            $arrayDatos['T_DEUDAPUBL'][$index]['DELFZ'] = date('Y-m-d', strtotime($value['DELFZ']));
            $BBLFZ = floatval(trim($value['BBLFZ'])) * 100;
            $arrayDatos['T_DEUDAPUBL'][$index]['BBLFZ'] = $this->fijarFormatoDinero($BBLFZ);
        endforeach;

        $arrayDatos['DESEMBOLSOS'] = $arrayDatos['T_DEUDAPUBL'];
    }

    private function buscarInteresCuota($fechaCuota, $arrayIntereses)
    {
        $interes = [];
        foreach ($arrayIntereses as $index => $value) {
            if ($value['IRA_DATE'] == $fechaCuota) :
                $interes = $value;
                return $value;
               // break;
            endif;
        }

        return $interes;
    }


    private function obtenerFlujoCajaDesembolso($numDesembolso, $arrayDatosLinea)
    {
        $data = [];

        foreach ($arrayDatosLinea['T_FLUJOCAJA'] as $index => $flujo) :
            if (trim($numDesembolso) == trim($flujo['RFHA'])) :
                /* MULTIPLICAR POR 100 PARA QUE LOS VALORES COINCIDAN CON SAP*/
                $flujo['BZBETR'] = floatval(trim($flujo['BZBETR'])) * 100;
                $data['T_FLUJOCAJA'][] = $flujo;
            endif;
        endforeach;

        if (!empty($data)) :

            usort($data['T_FLUJOCAJA'], function ($item1, $item2) {
                //dd($item1);
                return $item1['DFAELL'] <=> $item2['DFAELL'];
            });

            $sort = array();
            foreach ($data['T_FLUJOCAJA'] as $k => $v) {
                $sort['DFAELL'][$k] = $v['DFAELL'];
                $sort['SFHAZBA'][$k] = $v['SFHAZBA'];
            }
            # sort by DFAELL asc and then SFHAZBA desc
            array_multisort($sort['DFAELL'], SORT_ASC, $sort['SFHAZBA'], SORT_DESC, $data['T_FLUJOCAJA']);

        endif;

        return $data;
    }
    public function ajaxObtenerDetalleDesembolsoSap($numDesembolso = 0)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Detalle cargado correctamente';

        try {
            Gate::authorize('check-authorization', PERMISO_VER_CREDITOS_SAP);

            if (empty($numDesembolso)) :
                throw new \Exception('Error: debe enviar el número del desembolso');
            endif;

            if (!isset($_SESSION)) :
                session_start();
            endif;

            $arrayDatosLinea = $_SESSION['arrayDatosLinea'];
            $arrayDatosDesembolso = [];
            $conIntereses = 0;
            // $infoDesembolso = $arrayDatos['DESEMBOLSOS'][0]['INFO_DESEMBOLSO'];
            $indexProy = 0;

            // dd($arrayDatosLinea);


            $infoDesembolso = $this->obtenerDetallesDesembolsoSap($numDesembolso);
            $arrayDatosDesembolso = $this->obtenerFlujoCajaDesembolso($numDesembolso, $arrayDatosLinea);
            //dd($arrayDatosDesembolso);

            //  dd($arrayDatosDesembolso['T_FLUJOCAJA']);
            /* FILTRO SOLO CUOTAS DESEMBOLSO */
            foreach ($arrayDatosDesembolso['T_FLUJOCAJA'] as $index => $flujo) :

                $concepto = '';
                $saldoDeuda = 0;
                $capital = 0;
                $totalServDeuda = 0;
                $interes = 0;

                if (intval($flujo['SFHAZBA']) == 1105) : //DESEMBOLSO

                    $desembolso = floatval($flujo['BZBETR']);
                    $saldoDeuda = $desembolso - $capital;
                    $flujo['SALDO_DEUDA'] = $this->redondearResultado($saldoDeuda);
                    $flujo['AMORT_CAPITAL'] = $this->redondearResultado(0);
                    $totalServDeuda = $interes + $capital;
                    $flujo['SERV_DEUDA'] = $this->redondearResultado($totalServDeuda);
                    $flujo['INTERES'] = $interes;


                    $date = '';
                    if (!empty($flujo['IRA_DATE'])) :
                        $date = date('Y-m-d', strtotime($flujo['IRA_DATE']));
                    endif;

                    // dd($flujo['SALDO_DEUDA']);


                    /* PROYECCIONES */
                    $arrayDatosDesembolso['PROYECCIONES'][] = [
                        'ID' => Utilidades::getRandomId(),
                        'INDEX' => $indexProy,
                        'ID_CUOTA' => $flujo['ID'],
                        'IRA_DATE' => $date, //FECHA DEL INTERES
                        'INT_VALUE' => $this->redondearResultado($flujo['INT_VALUE']), //TASA DE INTERES
                        'TPINTREF2' => $this->redondearResultado($infoDesembolso['TPINTREF2']), //SPREAD
                        'NUM_DIAS_TASA_REF' => $this->obtenerNumDiasTasaRef($infoDesembolso['TPINTREF1']),
                        'TASA_NTV' => 0, //$this->obtenerTasaNominal(floatval($flujo['INT_VALUE']), floatval($infoDesembolso['TPINTREF2'])),
                        'TASA_EA_TV' => 0,
                        'SALDO_DEUDA_ANT' => $desembolso,
                        //'VALOR' => $this->fijarFormatoDinero(0)
                        'VALOR' => 0,
                        'ENABLE' => true
                    ];

                    $indexProy++;

                    //   dd($arrayDatosDesembolso['PROYECCIONES']);

                elseif (intval($flujo['SFHAZBA']) == 1201) : //INTERES

                    // dd($arrayDatosDesembolso);
                    $cuotaAnterior = $this->obtenerCuotaAnterior($arrayDatosDesembolso, $index);
                    $proyAnterior = $this->obtenerProyeAnterior($arrayDatosDesembolso['PROYECCIONES']);

                    // dd($proyAnterior['NUM_DIAS_TASA_REF']);
                    //dd($cuotaAnterior['SALDO_DEUDA']);
                    // $concepto = 'INTERES';
                    $concepto = $flujo['DESC_SFHAZBA'];
                    $interes = floatval($flujo['BZBETR']); //VALOR PAGADO DE INTERES
                    $saldoDeuda = 0;
                    if (isset($cuotaAnterior['SALDO_DEUDA'])) :
                        // dd($flujo['SALDO_DEUDA']);
                        $saldoDeuda = $cuotaAnterior['SALDO_DEUDA'];
                    endif;


                //  $saldoDeuda = $saldoDeuda; //floatval($cuotaAnterior['SALDO_DEUDA']);
                $totalServDeuda = $interes + $capital;
                $totalServDeuda = $this->redondearResultado($totalServDeuda);
                $tasaInteresFilaAnterior = floatval($proyAnterior['INT_VALUE']);
                $tasaInteresFilaActual = floatval($flujo['INT_VALUE']);
                $spread = floatval($infoDesembolso['TPINTREF2']);
                $conIntereses++;

                //tasaNominal es lo mismo que tasa periodica
                $numDias = $this->obtenerNumDiasTasaRef($infoDesembolso['TPINTREF1']);

                // dd($infoDesembolso['TPINTREF1']);

                $tasaNominal = $this->obtenerTasaNominal(
                    $tasaInteresFilaAnterior,
                    $spread
                );


                $numPeriodosAnno = $this->obtenerNumPerAnno($numDias);
                // dd($tasaNominal);
                $tasaPeriodica = $this->obtenerTasaPeriodica($tasaNominal, $numPeriodosAnno);

                //dd($tasaNominal);
                $tasaEfectivaAnual = $this->obtenerTasaEfecAnual($tasaPeriodica, $numDias);

                //  dd($proyAnterior);

                //NUM DIAS
                $date = '';
                if (!empty($flujo['IRA_DATE'])) :
                    $date = date('Y-m-d', strtotime(trim($flujo['IRA_DATE'])));
                    //  $date =trim($flujo['IRA_DATE']);
                endif;


                /* PROYECCIONES */
                $arrayDatosDesembolso['PROYECCIONES'][] = [
                    'ID' => 1,
                    'INDEX' => $indexProy,
                    'ID_CUOTA' => $flujo['ID'],
                    'IRA_DATE' => $date, //FECHA DEL INTERES
                        //'INT_VALUE' =>  4.343, // $this->redondearResultado($flujo['INT_VALUE']), //VALOR DEL INTERES o VALOR TASA REF
                    'INT_VALUE' => $this->redondearResultado($tasaInteresFilaActual), //VALOR DEL INTERES o VALOR TASA REF
                    'TPINTREF2' => $this->redondearResultado($spread), //SPREAD
                        //'TPINTREF2' => 4.95, //$this->redondearResultado($infoDesembolso['TPINTREF2']), //SPREAD
                    'NUM_DIAS_TASA_REF' => $numDias,
                    //'NUM_DIAS_TASA_REF' => 90, //$numDias,
                    'SALDO_DEUDA_ANT' => floatval($saldoDeuda),
                        //      'NUM_DIAS_TASA_REF_ANT' => floatval($proyAnterior['NUM_DIAS_TASA_REF']),
                        //    'TASA_REF_ANT' => floatval($proyAnterior['INT_VALUE']),
                        //    'SPREAD_ANT' => floatval($proyAnterior['TPINTREF2']),
                        //    'TASA_EA_TV_DEC_ANT' => floatval($cuotaAnterior['TASA_EA_TV_DEC']),
                    'TASA_NTV' => $this->redondearResultado($tasaNominal) . '%',
                    'TASA_NTV_DEC' => $tasaNominal, //SIN DECIMAL Y SIN PORCENTAJE PAR ACTUALIZAR
                    'TASA_EA_TV' => ($this->redondearResultado($tasaEfectivaAnual, 3) * 100) . ' %',
                    'TASA_EA_TV_DEC' => $tasaEfectivaAnual, //SIN DECIMAL Y SIN PORCENTAJE PAR ACTUALIZAR
                    //'VALOR' =>  $this->fijarFormatoDinero($this->redondearResultado($interes))
                    'VALOR' => $this->redondearResultado($interes),
                    'ENABLE' => true //$this->obtenerInteresProyectadoEstandar(floatval('84.000.000'), ($tasaNominal/100), $numDias )
                ];

                if ($indexProy === 2) :
                    //dd($arrayDatosDesembolso['PROYECCIONES']); exit;
                endif;

                // dd($index);

                $indexProy++;

                elseif (intval($flujo['SFHAZBA']) == 1130) : //AMORTIZACIÓN
                    $cuotaAnterior = $this->obtenerCuotaAnterior($arrayDatosDesembolso, $index);
                    // dd($arrayDatosDesembolso);
                    $concepto = $flujo['DESC_SFHAZBA'];
                    $capital = floatval($flujo['BZBETR']);
                    $totalServDeuda = $this->redondearResultado($interes + $capital);
                    $saldoDeuda = floatval($cuotaAnterior['SALDO_DEUDA']) - $capital;
                    $saldoDeuda = $this->redondearResultado($saldoDeuda);

                elseif (intval($flujo['SFHAZBA']) == 1210) : //CUOTAS DIFERIDAS

                    $cuotaAnterior = $this->obtenerCuotaAnterior($arrayDatosDesembolso, $index);
                    $saldoDeuda = floatval($cuotaAnterior['SALDO_DEUDA']) - $capital;
                    $saldoDeuda = $this->redondearResultado($saldoDeuda);
                    $concepto = $flujo['DESC_SFHAZBA'];
                endif;

                $arrayDatosDesembolso['T_FLUJOCAJA'][$index]['CONCEPTO'] = $concepto;
                $arrayDatosDesembolso['T_FLUJOCAJA'][$index]['SALDO_DEUDA'] = $saldoDeuda;
                $arrayDatosDesembolso['T_FLUJOCAJA'][$index]['AMORT_CAPITAL'] = $capital;
                $arrayDatosDesembolso['T_FLUJOCAJA'][$index]['INTERES'] = $interes;
                $arrayDatosDesembolso['T_FLUJOCAJA'][$index]['SERV_DEUDA'] = $totalServDeuda;

            endforeach;

            if (!isset($_SESSION)) :
                session_start();
            endif;

            $cantidadProyecciones = count($arrayDatosDesembolso['PROYECCIONES']);
            /* DESHABILITAR LA ULTIMA PROYECCIÓN, PARA QUE NO SE PUEDA EDITAR */
            $arrayDatosDesembolso['PROYECCIONES'][$cantidadProyecciones - 1]['ENABLE'] = false;
            $_SESSION['arrayDatosDesembolso'] = $arrayDatosDesembolso;

            // echo json_encode($arrayDatosDesembolso);
            //  dd($arrayDatosDesembolso['PROYECCIONES']);
            return response()->json($arrayDatosDesembolso, $statusCode);
            // die();
        } catch (\Exception $ex) {
            //DB::rollback();
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

    private function obtenerDetallesDesembolsoSap($numDesembolso)
    {
        if (!isset($_SESSION)) :
            session_start();
        endif;

        $arrayDatosLinea = $_SESSION['arrayDatosLinea'];

        //  dd($arrayDatosLinea);

        $arrayDesembolsos = $arrayDatosLinea['T_DEUDAPUBL'];
        foreach ($arrayDesembolsos as $index => $value) :
            if (trim($numDesembolso) == trim($value['RFHA'])) :
                return $value;
            endif;
        endforeach;

        return [];
    }


    private function redondearResultado($valor, $decimales = 5)
    {
        return round($valor, $decimales);
    }

    private function obtenerCuotaAnterior($arrayDatos, $indexActual)
    {
        // $count = count()
        //   dd($arrayDatos);
        // return $arrayDatos['DESEMBOLSOS'][0]['FLUJO_DESEMBOLSO'][$indexActual  - 1];
        return $arrayDatos['T_FLUJOCAJA'][$indexActual - 1];
    }

    private function obtenerProyeAnterior($arrayDatos)
    {
        if (count($arrayDatos) > 1) :
            //dd($arrayDatos[$indexActual  - 1]);
            return $arrayDatos[count($arrayDatos) - 1];
        endif;
        // dd($arrayDatos[0]);
        return $arrayDatos[0];
    }

    public function listadoCreditosSimulados(Request $request)
    {
        try {
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

                $creditos = Credito::obtenerCreditos($arrayAndWhere, $arrayOrWhere)
                    ->orderBy('id', 'DESC')
                    ->take($this->pageLimit);
            else :
                $condiciones = [
                    ['tipo_credito_id', '=', $tipoCredito]
                ];
                $creditos = Credito::obtenerCreditos($condiciones)
                    // ->where('tipo_credito_id', $tipoCredito)
                    ->orderBy('id', 'DESC')
                    ->take($this->pageLimit);

            endif;

            $creditos = $creditos->paginate($this->pageLimit);
            $filasDetalle = $this->obtenerFilasDetalle($creditos->toArray()['data']);

            return view('creditos.listado_creditos', compact('creditos', 'titulo', 'filasDetalle', 'tipoCredito'));
        } catch (\Exception $ex) {
            // dd($ex->getMessage());

            if ($ex->getMessage() == 'This action is unauthorized.') :
                return Utilidades::adminUnauthorizedLogout();
            endif;

            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }
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
        try {
            // dd('Holaa');

            // Gate::authorize('check-authorization', PERMISO_BORRAR_CREDITO_SIMULADO);

            return view('creditos.form_desembolso', ['creditoBancoId' => $creditoBancoId]);
        } catch (\Exception $ex) {
            // dd($ex->getMessage());

            if ($ex->getMessage() == 'This action is unauthorized.') :
                return Utilidades::adminUnauthorizedLogout();
            endif;

            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }
    }

    public function desembolsos($creditoBancoId = 0)
    {
        Gate::authorize('check-authorization', PERMISO_VER_LISTADO_DESEMBOLSOS_SIMULADOS);

        try {
            return view('creditos.listado_desembolsos', ['creditoBancoId' => $creditoBancoId]);
        } catch (\Exception $ex) {
            return redirect()
                ->back()
                ->with('error', '' . $ex->getMessage());
        }
    }

    /* PETICIONES AJAX */

    public function ajaxGuardarCreditoSimuladoBanco(CreditoBancoRequest $request)
    {
        $statusCode = 201;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_CREAR_CREDITO_BANCARIO_SIMULADO);

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
                $errorMsg = 'Error: el crédito seleccionado tiene cupo por: $' ;
                $errorMsg .= number_format($valorQueSePuedePrestar, 2);
                throw new \Exception($errorMsg);
            endif;

            $requestData['tipo_credito_id'] = CREDITO_SIMULADO; //SIMULADO
            $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));

            $creditoBanco = CreditoBanco::create($requestData);

            $this->actualizarValorPrestadoCredito($credito['id']);

            if (!$creditoBanco) :
                throw new \Exception('Error al intentar crear el crédito');
            endif;

            unset($datosCredito['created_at']);
            unset($datosCredito['updated_at']);

            Utilidades::saveAdutoria(
                'CREDITOS_BANCOS',
                'CREAR_CREDITO_BANCO_SIMULADO',
                'creditos_bancos',
                $creditoBanco->id
            );

            //DB::rollBack();
            DB::commit();
            $data['item'] = $requestData;
            $data['credito'] = $datosCredito;
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

    public function ajaxEditarCreditoSimuladoBanco(CreditoBancoRequest $request, $id)
    {
        $statusCode = 201;
        $data['success'] = true;
        $data['message'] = 'Registro editado correctamente';

        DB::beginTransaction();

        try {
            // dd(PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO);

            Gate::authorize('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO);
            //dd($x);

            $requestData = $request->all();
            Utilidades::cleanString($requestData);

            Desembolso::where('credito_banco_id', $id)->delete();


            $requestData['fecha_inicio'] = date('Y-m-d', strtotime($requestData['fecha_inicio']));
            $requestData['valor_prestado'] = 0;


            //  dd($requestData);

            $creditoBanco = CreditoBanco::whereId($id)->update($requestData);

            if (!$creditoBanco) :
                DB::rollback();
                throw new \Exception('Error: no fue posible editar el crédito');
            endif;

            //ACTUALIZAR VALOR PRESTADO DEL CREDITO
            $this->actualizarValorPrestadoCredito($requestData['credito_id']);

            $creditoBanco = CreditoBanco::where('id', $id)->first();

            /* AUDITORIA */
            Utilidades::saveAdutoria('CREDITOS_BANCOS', 'EDITAR_CREDITO_BANCO_SIMULADO', 'creditos_bancos', $id);

            DB::commit();
            //dd()
            // $creditoBanco['id'] = $id;
            $data['item'] = $creditoBanco;
            $data['credito'] = $creditoBanco;
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

    public function ajaxBorrarCreditoSimuladoBanco($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Crédito borrado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_BORRAR_CREDITO_BANCARIO_SIMULADO);

            $condiciones = [
                ['credito_banco_id', $id]
            ];

            Desembolso::borrarDesembolso($condiciones);

            $creditoBanco = CreditoBanco::obtenerCreditoBanco($id);

            if (CreditoBanco::borrarCreditoBanco($id) == 0) :
                throw new \Exception('Error: el crédito no fue borrado');
            endif;

            //ACTUALIZAR EL VALOR PRESTADO DEL CREDITO # 1
            $creditoId = $creditoBanco['credito_id'];
            $this->actualizarValorPrestadoCredito($creditoId);


            /* AUDITORIA */
            Utilidades::saveAdutoria('CREDITOS_BANCOS', 'BORRAR_CREDITO_BANCO_SIMULADO', 'creditos_bancos', $creditoBanco->id);

            DB::commit();

            $data['credito_banco_id'] = $id;
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
            Gate::authorize('check-authorization', PERMISO_BORRAR_DESEMBOLSO_SIMULADO);

            $result = Desembolso::where('credito_banco_id', $id)->delete();

            if ($result === 0) :
                $data['message'] = 'No hay registros para borrar';
            else :
                $result = CreditoBanco::whereId($id)->update(['valor_prestado' => 0]);
                if ($result === 0) :
                    throw new \Exception('Error: no fue posible actualizar el valor usado del crédito');
                endif;

            /* AUDITORIA */
            Utilidades::saveAdutoria(
                'CREDITOS_DESEMBOLSOS',
                'BORRAR_CREDITOS_DESEMBOLSOS_SIMULADOS',
                'creditos_desembolsos',
                $id
            );

            endif;

            DB::commit();

            $data['credito_banco_id'] = $id;
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

    public function ajaxVerificarDesembolso($creditoId = null)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro verificado correctamente';
        $data['es_primer_desembolso'] = false;

        try {
            if (empty($creditoId)) :
                throw new \Exception('Error: debe enviar el ID del crédito');
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
            // DB::rollback();
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



    public function ajaxGuardarCreditoSimulado(CreditoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO);

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
            Utilidades::saveAdutoria('CREDITOS', 'CREAR_CREDITO_SIMULADO', 'creditos', $registro->id);
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

    public function ajaxEditarCreditoSimulado($id = null, CreditoRequest $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro editado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_EDITAR_CREDITO_SIMULADO);

            $requestData = $request->all();
            Utilidades::cleanString($requestData);
            $creditoId = $requestData['id'];
            $requestData['fecha'] = date('Y-m-d', strtotime($requestData['fecha']));
            $totalUsado = $this->obtenerTotalUsadoCredito($creditoId);

            if (floatval($requestData['valor']) < floatval($totalUsado)) :
                $msg = 'Error: El valor del crédito no puede ser inferior al valor usado actualmente: ' . $this->fijarFormatoDinero($totalUsado);
                throw new \Exception($msg);
            endif;

            // $requestData['created_at'] = date('Y-m-d H:i:s');
            $requestData['updated_at'] = date('Y-m-d H:i:s');

            $registro = Credito::whereId($id)->update($requestData);

            if (!$registro) :
                throw new \Exception('Error al intentar editar el registro');
            endif;

            //AUDITORIA
            Utilidades::saveAdutoria('CREDITOS', 'EDITAR_CREDITO_SIMULADO', 'creditos', $id);

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

    public function ajaxBorrarCreditoSimulado($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Registro borrado correctamente';

        DB::beginTransaction();

        try {
            Gate::authorize('check-authorization', PERMISO_BORRAR_CREDITO_SIMULADO);

            $credito = Credito::whereId($id)->first();

            if (empty($credito)) :
                throw new \Exception('Error: el crédito que intenta borrar no existe');
            endif;

            $credito->update(['estado' => 'Borrado']);

            //AUDITORIA
            Utilidades::saveAdutoria('CREDITOS', 'BORRAR_CREDITO_SIMULADO', 'creditos', $id);
            DB::commit();

            // dd($creditosBancos->toArray());

            /*
            $credito = Credito::with(['creditosbancos' => function ($query) {
            //$query->select('id');
            }, 'creditosbancos.desembolsos' => function ($query) {
            //$query->select('id');
            }])->get();

            //$credito = Credito::with(['creditosbancos:id', 'creditosbancos.desembolsos:id'])->get();

            echo "<pre>";
            print_r($credito->toArray());
            exit;

            if (!$credito->delete()):
                throw new \Exception('Error al intentar borrar el registro');
            endif; */
        } catch (\PDOException $ex) {
            DB::rollback();

            $errorInfo = '';

            if (is_object($ex)) :

                $errorInfo = $ex->errorInfo;
                $errorCode = $errorInfo[1];
                $errorMsg = $errorInfo[2];

                if ($errorCode === 1451) :
                    $msg = 'Error: no puede borrar el crédito, ';
                    $msg .= 'está relacionado con otros registros';
                    $data['message'] = $msg;

                else :
                    $data['message'] = $errorMsg;
                endif;

            else :
                $data['message'] = $ex->getMessage();
            endif;

            $data['success'] = false;
            //  http_response_code(400);
            $statusCode = 400;
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

    /* private function borrarCreditosBancos($creditoBancoId)
    {
        $condiciones = [];
        $condiciones[] = ['credito_id', '=', $creditoBancoId];
        $creditosBancos = CreditoBanco::obtenerCreditosBancosPorCondiciones($condiciones, [], ['id']);
        $arrayCreditosBancosIds = [];
        if (!empty($creditosBancos)) :
            foreach ($creditosBancos as $index => $value) :
                $arrayCreditosBancosIds[] = $value->id;
            endforeach;
            CreditoBanco::whereIn('id', $arrayCreditosBancosIds)->delete();
        endif;
        return $arrayCreditosBancosIds;
    }

    private function borrarDesembolsos($arrayCreditosBancosIds)
    {
        $condiciones = [];
        $condiciones[] = ['credito_id', '=', $creditoBancoId];
        $creditosBancos = CreditoBanco::obtenerCreditosBancosPorCondiciones($condiciones, [], ['id']);
        $arrayCreditosBancosIds = [];
        if (!empty($creditosBancos)) :
            foreach ($creditosBancos as $index => $value) :
                $arrayCreditosBancosIds[] = $value->id;
            endforeach;
            CreditoBanco::whereIn('id', $arrayCreditosBancosIds)->delete();
        endif;
        return $arrayCreditosBancosIds;
    }*/

    public function ajaxObtenerNumDesembolsos($id)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Verificación correcta';

        try {
            $data['num_desembolsos'] = Desembolso::where('credito_banco_id', $id)->count();
            // $data['num_desembolsos']
        } catch (\Exception $ex) {
            // DB::rollback();
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


    public function ajaxGuardarDesembolsoSimulado(DesembolsoRequest $request)
    {
        $statusCode = 201; //201
        $data['success'] = true;
        $data['message'] = 'Registro guardado correctamente';

        DB::beginTransaction();

        try {
            $requestData = $request->all();
            Utilidades::cleanString($requestData);


            Gate::authorize('check-authorization', PERMISO_CREAR_DESEMBOLSO_SIMULADO);

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

                $requestData['fecha_fin'] = $data['fecha_fin'] = $primerDesembolso['fecha_fin'];

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

            /* AUDITORIA */
            Utilidades::saveAdutoria('DESEMBOLSOS', 'CREAR_DESEMBOLSO_SIMULADO', 'desembolsos', $desembolso->id);

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
            Gate::authorize('check-authorization', PERMISO_EDITAR_DESEMBOLSO_SIMULADO);

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

            $esIndependiente = $desembolso['es_independiente'];

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

            /* AUDITORIA */
            Utilidades::saveAdutoria(
                'CREDITOS_DESEMBOLSOS',
                'EDITAR_CREDITO_DESEMBOLSO_SIMULADO',
                'creditos',
                $desembolsoId
            );

            $data['desembolso'] = $requestData;
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
            Gate::authorize('check-authorization', PERMISO_BORRAR_DESEMBOLSO_SIMULADO);

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


            $data['desembolso'] = $desembolso;

            /* AUDITORIA */
            Utilidades::saveAdutoria('CREDITOS_DESEMBOLSOS', 'BORRAR_CREDITO_DESEMBOLSO_SIMULADO', 'creditos_desembolsos', $desembolso->id);

            //DB::rollback();
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

        return $ids;
    }
    private function actualizaValorPrestadoCredBanco($creditoBancoId)
    {
        $totalPrestadoCreditoBanco = $this->obtenerTotalUsadoCreditoBancario($creditoBancoId);
        $result = CreditoBanco::whereId($creditoBancoId)
            ->update(['valor_prestado' => $totalPrestadoCreditoBanco]);
        if (!$result) :
            throw new \Exception('Error al intentar actualizar el valor prestado del crédito');
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
            'id_cuota' => $randomId,
            'numero' => $numero,
            'fecha' => $fechaFinal,
            'concepto' => $concepto,
            'amort_capital' => $amortizacionCapital,
            'amort_capital_round' => $this->fijarFormatoDinero($amortizacionCapital, $decimales),
            'interes_pagado' => $interesPagado,
            'total_serv_deuda' => $this->fijarFormatoDinero($totalSerDeuda, $decimales),
            'interes_proyectado' => $this->fijarFormatoDinero($interesesProyectado, $decimales),
            //'interes_proyectado' => round($interesesProyectado),
            'saldo_deuda' => $saldoDeuda,
            //PÁGINA https://www.w3schools.com/php/phptryit.asp?filename=tryphp_func_string_number_format
            'saldo_deuda_round' => $this->fijarFormatoDinero($saldoDeuda, $decimales),
            'cambio_valores' => $cambioValores,
            'es_periodo_gracia' => $esPeriodoGracia
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
            'num_dias' => $numDiasFinal,
            'tasa_efectiva_anual' => round(($tasaEfectivaAnual * 100), 3) . '%',
            'tasa_efectiva_anual_decimal' => $tasaEfectivaAnual,
            'anno' => date('Y', strtotime($fecha)),
            'valor' => $this->fijarFormatoDinero($interesesProyectado, 0),
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
            $fecha1 = $desembolso['fecha_inicio'];

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

        //  dd($numCuotasPerGracia);
        $amortizacionCapital = $saldoDeuda / $numCuotasSinPerGracia;

        //dd($amortizacionCapital);

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
                $tasaEfectivaAnual,
                $truncarTasaEa
            );


            $cuotas = $arrayCuotasProyecciones['cuotas'];
            $proyecciones = $arrayCuotasProyecciones['proyecciones'];

            //PROCESO ESPECIAL
        else :

            //  dd($separarCapitalInteres);

            $arrayCuotasProyecciones = $this->obtenerArrayCuotasProyeccionesEspecial(
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
            );


            $cuotas = $arrayCuotasProyecciones['cuotas'];
            $proyecciones = $arrayCuotasProyecciones['proyecciones'];

            // dd($cuotas);

        endif;

        //  dd($cuotas);
        return [
            'cuotas' => $cuotas,
            'proyecciones' => $proyecciones
        ];
    }



    /*
     private function obtenerDiasDiferenciaCuotaCapitalInteres($fecha1, $fecha2)
     {
     $fechaCuotaInteres =  $fecha1;
     $fechaCuotaCapital = $fecha2;
     // dd($fechaCuotaInteres);
     //  dd($fechaCuotaCapital);
     $fechaCuotaCapital = date_create($fechaCuotaCapital); //FECHA PARADA PRIMER DESEMBOLSO
     $fechaCuotaInteres = date_create($fechaCuotaInteres); //FECHA INICIO OTRO DESEMBOLSO
     return date_diff($fechaCuotaInteres, $fechaCuotaCapital)->days - 1;
     } */


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
        $tasaEfectivaAnual,
        $truncarTasaEa = false
    ) {
        $meses = 0;
        $cuotas = [];
        $proyecciones = [];
        $ultimoIndexCuotaGracia = 0;
        $mesGenerados = [];
        //$mesPiloto =  intval(date('m', strtotime($fecha1)));
        $mesPiloto = $mesGenerados[0] = intval(date('m', strtotime($fecha1)));



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

            $mesGenerados[] = $mesfechaGenerada;
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

                // if(!$truncarTasaEa):
                $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                    $saldoDeuda,
                    $tasaNominalPorce,
                    $diasDiferencia
                );
                /*else: //USAR LA TASA EFECTIVA ANUAL TRUNCADA
                 $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                 $saldoDeuda,
                 $tasaEfectivaAnual,
                 $diasDiferencia
                 );
                 endif;*/

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
                $totalSerDeuda = $interesesProyectado + 0;
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
                    $saldoDeuda = $cuotas[$i - 1]['saldo_deuda']; //8
                endif;


            $interesesProyectado = 0;

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


            $totalSerDeuda = $interesesProyectado + $amortizacionCapital;
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
            $proyecciones[$i]['id_proyeccion'] = Utilidades::getRandomId();

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
        //dd($fecha2);

        $fecha1 = date_create($fecha1);
        $fecha2 = date_create($fecha2);

        $diff = date_diff($fecha2, $fecha1);
        //  dd($diff);
        return $diff;
    }

    private function obtenerInteresProyectadoPorTipo(
        $truncarTasaEa,
        $saldoDeuda,
        $tasaNominalPorce,
        $tasaEfectivaAnual,
        $diasDiffCapInteres
    ) {
        $interesesProyectado = 0;

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
        $fecha1, //ES LA FECHA EN QUE INICIA EL DESEMBOLSO
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


        $mesPiloto = $mesGenerados[0] = intval(date('m', strtotime($fecha1)));

        // dd($mesPiloto);

        /*
         MES DE INICIO DE LA FECHA DEL OTRO DESEMBOLSO
         */
        $mesPilotoInteres = $mesGeneradosInteres[0] = intval(date('m', strtotime($fechaInicioOtroDesembolso)));

        $fechaInteres = $fechaInicioOtroDesembolso;

        $contadorCapitalInteres = 0;
        $proyecciones = [];

        $cuotasCapital = [];
        $cuotasInteres = [];

        $numeroProyeccion = 1;

        $indexCuotasCapital = 0;
        $indexCuotasInteres = 0;
        $interesProyectado1 = 0;
        $interesProyectado2 = 0;

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

            /*
             OBTENER FECHAS DE LAS CUOTAS,
             EN EL PRIMER CICLO SE DEBE GENERAR LA FECHA INICIAL
             */
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

            /*
             MES SIGUIENTE DE CADA CUOTA,
             A PARTIR DEL SIGUIENTE CICLO YA SE AGREGARÓN 3 MESES
             */
            if ($i > 0) :
                $mesPiloto = intval($mesGenerados[$i]);
                $mesPilotoInteres = intval($mesGeneradosInteres[$i]);
            endif;

            $mesGenerados[] = $mesfechaGenerada;
            $mesGeneradosInteres[] = $mesfechaInteres;




            $randomId = Utilidades::getRandomId();
            $randomIdCuotaInteres = Utilidades::getRandomId();
            //  $dias = $numDiasCreditoBanco;
            // dd($numDias);
            // endif;



            /* FECHA FINAL CUOTAS DE INTERES */
            //  $fechaFinalInteres = $annofechaInteres . '-' . $mesfechaInteres . '-' . $diafechaInteres;

            $fechaFinalInteres = $this->obtenerFechaFinal(
                $mesPilotoInteres,
                $annofechaInteres,
                $numMesesSumar,
                $diafechaInteres
            );

            //GENERAR LA FECHA PARA LA CUOTA SIGUIENTE
            /* $tempFechaFinalInteres = $this->obtenerFechaFinal(
             $mesPilotoInteres,
             $annofechaInteres,
             ($numMesesSumar + $numMesesSumar),
             $diafechaInteres
             ); */

            //    dd($numCuotasPerGracia);

            //CUOTAS PERIODO GRACIA
            if ($i < $numCuotasPerGracia) : //



                $interesProyectado = 0;


                /* //PARA LAS CUOTAS DE INTERES SIEMPRE SE USAN LOS DÍAS DEL CREDITO CUANDO ES CAPITAL E INTERES SEPADOS */

                $interesProyectado = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    $saldoDeuda,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $numDiasCreditoBanco
                );

                $diasDiffCuotaInteres = $numDiasCreditoBanco;

                //dd($numDias);
                $totalSerDeuda = $interesProyectado + 0;

                $numero = $i + 1;
                $cuotas[$i] = $this->obtenerCuotas(
                    $randomIdCuotaInteres,
                    $numero,
                    $fechaFinalInteres,
                    'Intereses',
                    0, //AMORTIZACIÓN CAPIAL
                    0, //INTERES PAGADO
                    $totalSerDeuda,
                    $interesProyectado,
                    $saldoDeuda, //EN LAS CUOTAS DE PERIODO DE GRACIA  EL SALDO DE LA DEUDA SIEMPRE ES EL MISMO
                    false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                    true, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                    0 //DECIMALES
                );

                $cuotas[$i]['interes_proyectado'] = $this->fijarFormatoDinero(floatval($interesProyectado), 0);
                $cuotas[$i]['num_dias'] = $diaProyeccionPerGracia = $numDiasCreditoBanco;
                $cuotas[$i]['tipo_cuota'] = 'INTERES';

                $ultimoIndexCuotaGracia = $i;

                //CUOTAS SIN PERIODO GRACIA
            else :

                /* FECHA FINAL CUOTAS DESPUES PERIODO GRACIA */

                /* if ($tieneFechaOtroDesembolso === true) :
                 $fechaFinal = $fecha1;
                 else :*/

                //FECHA PARA LAS CUOTAS DE CAPITAL
                $fechaFinal = $this->obtenerFechaFinal(
                    $mesPiloto,
                    $annofechaGenerada,
                    $numMesesSumar,
                    $diaVerificado
                );


                /*
                 SE OBTIENE EL SALDO DE LA DEUDA
                 */

                if ($i == ($ultimoIndexCuotaGracia + 1)) : // PRIMERA CUOTA DESPUES DEL PERIODO DE GRACIA
                    $saldoDeuda = $cuotas[$ultimoIndexCuotaGracia]['saldo_deuda']; //CUOTA ANTERIOR

                    // CUOTAS DESPUES DE LA PRIMERA FUERA DEL PERIODO DE GRACIA
                elseif ($i > ($ultimoIndexCuotaGracia + 1)) :

                    //SALDO DEUDA ANTERIOR
                    $saldoDeuda = $cuotasCapital[$indexCuotasCapital]['saldo_deuda'];


                endif;

            if ($contadorCapitalInteres == 0) :
                $contadorCapitalInteres = $i + 1;
            endif;


            $interesProyectado = 0;

            $totalSerDeuda = $interesProyectado + $amortizacionCapital;

                //PARA CUOTAS DE CAPITAL
            $saldoDeuda = $saldoDeuda - $amortizacionCapital; //SALDO CUOTA ANTERIOR

            //  $numero = $i + 1;

            $cuotaCapital = $this->obtenerCuotas(
                $randomId,
                $contadorCapitalInteres, //NÚMERO ASIGNADO
                $fechaFinal,
                'Capital',
                $amortizacionCapital, //AMORTIZACIÓN CAPIAL
                0, //INTERES PAGADO
                $totalSerDeuda,
                $interesProyectado, //INTERES PROYECTADO
                $saldoDeuda,
                false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                false, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                0 //DECIMALES
            );

            $cuotaInteres['tipo_cuota'] = 'CAPITAL';
            $cuotaCapital['saldo_deuda_inter_proy'] = $saldoDeuda + $amortizacionCapital;

            $contadorCapitalInteres++;

            /* EL INTERES PROYECTADO SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN,
             NO CON LOS DÍAS DEL CREDITO */

            /* $interesesProyectado = $this->obtenerInteresProyectadoPorTipo(
             $truncarTasaEa,
             /*
             //PARA LAS CUOTAS DESPUES DEL PERIODO DE GRACIA
             EL INTERES PROYECTADO SE DEBE CALUCULAR CON EL SALDO DE LA FILA ACTUAL,
             POR ESO SE SUMA LA AMORTIZACIÓN A CAPITAL AL SALDO DE LA DEDUDA,
             LO CUAL DA COMO RESULTADO EL SALDO DE LA FILA ACTUAL
             $saldoDeuda,
             $tasaNominalPorce,
             $tasaEfectivaAnual,
             $diasDiffCapInteres
             ); */

            //0 = $amortizacionCapital
            $totalSerDeuda = $interesProyectado + 0;

            $cuotaInteres = $this->obtenerCuotas(
                $randomIdCuotaInteres, //TIENE QUE SER ID DIFERENTE
                $contadorCapitalInteres, //NÚMERO ASIGNADO
                $fechaFinalInteres,
                'Intereses',
                0, //AMORTIZACIÓN CAPIAL
                0, //INTERES PAGADO
                $totalSerDeuda,
                0, //INTERES PROYECTO
                $saldoDeuda,
                false, //CAMBIO DE VALORES (PARA SABER SI UNA FILA CAMBIO)
                false, //PARA DETERMINAR SI LA CUOTA ES DE PERIODO DE GRACIA
                0 //DECIMALES
            );

            $cuotaInteres['tipo_cuota'] = 'INTERES';
            $cuotaInteres['saldo_deuda_inter_proy'] = $saldoDeuda;


            // PRIMERA CUOTA DESPUES DEL PERIODO DE GRACIA
            if ($i == ($ultimoIndexCuotaGracia + 1)) :

                //   dd($cuotas[$ultimoIndexCuotaGracia]);

                $ultimaCuotaPerGracia = $cuotas[$ultimoIndexCuotaGracia];

                //CAPITAL
                $diasDiffCuotaCapital = $this->obtenerDiferenciaFechas(
                    $ultimaCuotaPerGracia['fecha'],
                    $fechaFinal
                )->days;

                $cuotaCapital['num_dias_diferencia'] = $diasDiffCuotaCapital;
                $saldoDeudaInterProy1 = $cuotaCapital['saldo_deuda_inter_proy'];

                $interesProyectado1 = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    $saldoDeudaInterProy1,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $diasDiffCuotaCapital
                );

                //INTERES
                $diasDiffCuotaInteres = $this->obtenerDiferenciaFechas(
                    $cuotaCapital['fecha'], //CUOTA ACTUAL GENERADA
                    $cuotaInteres['fecha'] //CUOTA ACTUAL GENERADA
                )->days;

                $cuotaInteres['num_dias_diferencia'] = $diasDiffCuotaInteres;

                $saldoDeudaInterProy2 = $cuotaInteres['saldo_deuda_inter_proy'];

                $interesProyectado2 = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    $saldoDeudaInterProy2,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $diasDiffCuotaInteres
                );

                $cuotaInteres['interes_proy_1'] = $interesProyectado1;
                $cuotaInteres['interes_proy_2'] = $interesProyectado2;

                //CONSOLIDAR EL INTERES PROYECTADO EN LA CUOTA DE CAPITAL
                $interesProyectado = $interesProyectado1 + $interesProyectado2;
                $cuotaInteres['interes_proyectado'] = $this->fijarFormatoDinero(floatval($interesProyectado), 0);
                //   dd($cuotaInteres);

                // $indexCuotasCapital++;
                // $indexCuotasInteres++;

                // dd($cuotaInteres);

            else : // CUOTAS DESPUES DE LA PRIMERA DE PERIODO DE GRACIA


                //   dd($cuotasInteres);
                //CAPITAL
                $diasDiffCuotaCapital = $this->obtenerDiferenciaFechas(
                    $cuotaCapital['fecha'], //CUOTA ACTUAL DE CAPITAL GENERADA
                    $cuotasInteres[$indexCuotasInteres]['fecha'] //CUOTA INTERES GENERADA
                )->days;

                $cuotaCapital['num_dias_diferencia'] = $diasDiffCuotaCapital;
                $saldoDeudaInterProy1 = $cuotaCapital['saldo_deuda_inter_proy'];



                $interesProyectado1 = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    $saldoDeudaInterProy1,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $diasDiffCuotaCapital
                );

                //INTERES
                $diasDiffCuotaInteres = $this->obtenerDiferenciaFechas(
                    $cuotaCapital['fecha'], //CUOTA ACTUAL
                    $cuotaInteres['fecha'] //CUOTA ACTUAL
                )->days;

                $cuotaInteres['num_dias_diferencia'] = $diasDiffCuotaInteres;
                $saldoDeudaInterProy2 = $cuotaInteres['saldo_deuda_inter_proy'];

                $interesProyectado2 = $this->obtenerInteresProyectadoPorTipo(
                    $truncarTasaEa,
                    $saldoDeudaInterProy2,
                    $tasaNominalPorce,
                    $tasaEfectivaAnual,
                    $diasDiffCuotaInteres
                );


                $cuotaInteres['interes_proy_1'] = $interesProyectado1;
                $cuotaInteres['interes_proy_2'] = $interesProyectado2;

                //CONSOLIDAR EL INTERES PROYECTADO EN LA CUOTA DE CAPITAL
                $interesProyectado = $interesProyectado1 + $interesProyectado2;
                $cuotaInteres['interes_proyectado'] = $this->fijarFormatoDinero(floatval($interesProyectado), 0);


                $indexCuotasCapital++;
                $indexCuotasInteres++;

            endif;

            //AGREGAR LOS DÍAS DE DIFERENCIA

            $cuotas[] = $cuotasCapital[] = $cuotaCapital;
            $cuotas[] = $cuotasInteres[] = $cuotaInteres;

            //  $cuotaInteres


            //  //CON LA RESTA DE -1


            if ($i == 8) :
                //    dd($cuotasCapital);
            endif;

            $contadorCapitalInteres++;


            endif;



            /*   if ($numeroProyeccion == 0) :
             $numeroProyeccion = $i;
             endif;
             $numeroProyeccion++; */


            //GENERAR LAS PROYECCIONES

            if ($i > $ultimoIndexCuotaGracia) : // PROYECCIONES DESPUES DE LA PRIMERA DE PERIODO DE GRACIA

                $proyeccionId = Utilidades::getRandomId();
                $numDiasFinal = $cuotaCapital['num_dias_diferencia'];

                $numCuotaInteres = $cuotaInteres['numero'];

                $proyeccion = $this->obtenerProyecciones(
                    $randomIdCuotaInteres, //LAS PROYECCIONES SIEMRE APUNTAN A UNA CUOTA DE INTERES
                    $numeroProyeccion,
                    $tasaRefValor,
                    $tasaNominal,
                    $tasaNominalPorce,
                    $spread,
                    $tasaPeriodica,
                    $numDiasFinal,
                    $tasaEfectivaAnual,
                    $fecha,
                    $interesProyectado1
                );

                $proyeccion['saldo_deuda_inter_proy'] = $saldoDeudaInterProy1;
                $proyeccion['id_proyeccion'] = $proyeccionId;
                $proyeccion['anno'] = $proyeccion['anno'] . ' # ' . $numCuotaInteres;
                $proyecciones[] = $proyeccion;

                $numDiasFinal = $cuotaInteres['num_dias_diferencia'];
                $proyeccionId = Utilidades::getRandomId();

                $numeroProyeccion++;

                $proyeccion = $this->obtenerProyecciones(
                    $randomIdCuotaInteres, //LAS PROYECCIONES SIEMRE APUNTAN A UNA CUOTA DE INTERES
                    $numeroProyeccion,
                    $tasaRefValor,
                    $tasaNominal,
                    $tasaNominalPorce,
                    $spread,
                    $tasaPeriodica,
                    $numDiasFinal,
                    $tasaEfectivaAnual,
                    $fecha,
                    $interesProyectado2
                );

                $proyeccion['saldo_deuda_inter_proy'] = $saldoDeudaInterProy2;
                $proyeccion['anno'] = $proyeccion['anno'] . ' # ' . $numCuotaInteres;
                $proyeccion['id_proyeccion'] = $proyeccionId;
                $proyecciones[] = $proyeccion;

                // $numeroProyeccion ++;
            else :

                $numDiasFinal = $diaProyeccionPerGracia; //PROYECCIONES PERIODO DE GRACIA

                $proyeccionId = Utilidades::getRandomId();

                $proyeccion = $this->obtenerProyecciones(
                    $randomIdCuotaInteres,
                    $numeroProyeccion,
                    $tasaRefValor,
                    $tasaNominal,
                    $tasaNominalPorce,
                    $spread,
                    $tasaPeriodica,
                    $diaProyeccionPerGracia,
                    $tasaEfectivaAnual,
                    $fecha,
                    $interesProyectado
                );

                $proyeccion['saldo_deuda_inter_proy'] = $saldoDeuda;
                $proyeccion['id_proyeccion'] = $proyeccionId;
                $proyecciones[] = $proyeccion;

            endif;


            $numeroProyeccion++;

            /*
             ESTO SOLO SE REQUIERE PARA LA PRIMERA CUOTA,
             PARA LAS OTRAS CUOTAS SE SUMAN EL NÚMERO DE MESES CORRESPONDIENTE
             */
            $tieneFechaOtroDesembolso = false;
        endfor;

        //BORRAR LA ULTIMA CUOTA
        unset($cuotas[count($cuotas) - 1]);

        //BORRAR LAS DOS ULTIMAS PROYECCIONES
        unset($proyecciones[count($proyecciones) - 1]);
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



    private function obtenerInteresProyectadoTruncado(
        $saldoDeuda,
        $tasaEfectivaAnualTruncada,
        $numDias
    ) {
        $base = 1 + $tasaEfectivaAnualTruncada;
        //  $base = $base * 100;
        $exp = $numDias / 365; //ANTES ESTABA 360
        $result = pow($base, $exp) - 1;

        $interesesProyectado = $saldoDeuda * $result;

        //  $interesesProyectado = $interesesProyectado;

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

    private function obtenerTasaEfectivaAnual($tasaPeriodica, $numDias)
    {
        $base = 1 + $tasaPeriodica;
        $exp = (360 / $numDias); //AQUI ES DONDE USA LOS DIAS DIFERENTES
        // dd($tasaPeriodica);
        $tasaEfectivaAnual = pow($base, $exp) - 1;
        return $tasaEfectivaAnual; //SI ES OTRO DESEMBOLSO
    }

    private function obtenerTasaEfectivaAnualTruncada($tasaNtv, $numDias)
    {
        $tasaNtv = $tasaNtv / 100;
        $base = 1 + ($tasaNtv * ($numDias / 360));
        $exp = (365 / $numDias);
        $tasaEfectivaAnual = pow($base, $exp) - 1;

        /*
         $base = ($tasaNtv / (360 / $numDias) + 1);
         $exp = (365 / $numDias);
         $tasaEfectivaAnual = pow($base, $exp) - 1; */




        // dd($this->truncate($tasaEfectivaAnual, 6) * 100);
        //return $tasaEfectivaAnual;
        return $tasaEfectivaAnual;
    }

    /**
     * @example truncate(-1.49999, 2); // returns -1.49
     * @example truncate(.49999, 3); // returns 0.499
     * @param float $val
     * @param int f
     * @return float
     */
    public function truncate($val, $f = "0")
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
            $fechaCuotaParadaDesembolso1 = $cuotasPriDesem[$indexCuotas + 1]['fecha'];
        } catch (\Exception $e) {
            $fechaCuotaParadaDesembolso1 = null;
        }
        return $fechaCuotaParadaDesembolso1;
    }

    private function obtenerUltimoDiaMes($fechaVerificada)
    {
        $fecha = new \DateTime($fechaVerificada);
        $fecha->modify('last day of this month');
        return $fecha->format('d');
    }

    public function cutNum($num, $precision = 2)
    {
        return floor($num) . substr(str_replace(floor($num), '', $num), 0, $precision + 1);
    }
    private function obtenerVariablesOtroDesembolso($desembolso, $primerDesembolso)
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


        // dd($contadorCuotasPerGracia);

        //  dd($numCuotasSinPerGracia);
        $data = [
            'fechaInicioOriginalOtroDesembolso' => $fechaInicioOriginalOtroDesembolso,
            //DIFERENCIA DE DÍAS DE PAGO PRIMERA CUOTA OTRO DESEMBOLSO Y FECHA OTRO DESEMBOLSO
            'diasDiferencia' => $numDiasDiferencia,
            'fechaPrimeraCuota' => $fechaCuotaParadaDesembolso1,
            'diaPagoOtrosCuotas' => $diaPagoOtrosCuotas,
            'numCuotasDescartadas' => $contadorCuotasDescartadas,
            'numCuotasPerGraciaDescartadas' => $contadorCuotasDescartadasPerGracia,
            // 'ubicarCuotaPeriodoGracia' => $ubicarCuotaPeriodoGracia,
            'numcuotasPriDesem' => $numcuotasPriDesem,
            'numCuotasOtroDesembolso' => $numCuotasOtroDesembolso,
            'numCuotasPerGraciaNuevo' => $contadorCuotasPerGracia,
            'fechaInicioPrimerDesembolso' => $fechaInicioPrimerDeselbolso,
            'numCuotasSinPerGracia' => $numCuotasSinPerGracia
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

        $annoDia = $annofechaGenerada . '-' . $mesfechaGenerada;
        ;

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
        $numCuotas = $numAnnos * 12;
        $numCuotas = $numCuotas / $diasEnMes;
        return $numCuotas;
    }

    //OBTENER EL NÚMERO DE CUOTAS DEL PERIODO DE GRACIA
    private function obtenerNumCuoPerGracia($perGracia, $diasEnMes)
    {
        $periodoGraciaEnMeses = intval($perGracia);
        // dd($periodoGraciaEnMeses);
        $numCuotasPerGracia = $periodoGraciaEnMeses / $diasEnMes;
        return $numCuotasPerGracia;
    }

    //OBTENER EL NÚMERO DE MESES A AGREGAR A CADA FECHA
    private function obtenerMesesSumar($numDias)
    {
        $numMesesSumar = $numDias / 30;
        return $numMesesSumar;
    }


    //OBTENER EL NÚMERO DE PERIODOS DEL AÑOS
    private function obtenerNumPerAnno($numDias)
    {
        //dd($numDias);
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
        // dd($numDiasCredito);
        $base = 1 + $tasaPeriodica;
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
            $desembolso['created_at'] = date('d-m-Y', strtotime($desembolso['created_at']));
            $desembolso['updated_at'] = date('d-m-Y', strtotime($desembolso['updated_at']));
            $desembolso['valor'] = intval($desembolso['valor']);

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
            $data['proyecciones'] = $arrayValoresDesembolso['proyecciones']; //$proyecciones;
            $data['creditoBanco'] = $arrayValoresDesembolso['creditoBanco']; //$creditoBanco;
        } catch (\Exception $ex) {
            //  DB::rollback();
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
        elseif ($primerDesembolso['id'] == $desembolso['id']) :
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
            $encabezado['A1'] = ['text' => 'CRÉDITO', 'bold' => true];
            $encabezado['B1'] = ['text' => $creditoBanco['descripcion'], 'bold' => false];

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
            $encabezado['D3'] = ['text' => $tipo, 'bold' => false];

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
            $cuotas = $arrayValoresDesembolso['cuotas'];
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
            $totalFilas = $totalFilas + 10;
            $totalFilas = $totalFilas + count($cuotas);

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
            $proyecciones = $arrayValoresDesembolso['proyecciones'];

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
            //  DB::rollback();
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

    public function ajaxGenerarExcelDesembolsoSap(Request $request)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Excel generado correctamente';

        try {
            $desembolso = null; // Desembolso::find($desembolsoId);
            $requestData = $request->all();
            //  $img = json_decode($requestData['file']);

            if (empty($requestData['num_desembolso'])) :
                $msg = 'Error: debe enviar el número del desembolso';
                throw new \Exception($msg);
            endif;

            if (empty($request->file('file'))) :
                $msg = 'Error: debe enviar los datos del reporte a generar';
                throw new \Exception($msg);
            endif;


            $numDesembolso = $requestData['num_desembolso'];
            $objBlobFile = $request->file('file');

            // $x = base64_decode($objBlobFile);

            // dd($numDesembolso);

            // echo base64_encode($objBlobFile);
            //$ext = $objBlobFile->getMimeType();
            // dd($ext);
            // $ext = $this->getShortFileExtension($ext);

            //$documentName = 'user_document_file_' . $user->id . '.' . $ext;
            /*
             AQUÍ SE GUARDA EL ARCHIVO JSON EN LA RUTA storage/app/reportes_creditos_json/
             */
            $path = 'reportes_creditos_json';
            $documentName = Utilidades::getRandomInt(16) . '.json';
            $originalFile = $objBlobFile->storeAs($path, $documentName);

            /*
             VERIFICACIÓN DE QUE EL ARCHIVO JSON CON LOS DATOS SE HAYA
             GUARDADO CORRECTAMENTE
             */
            $jsonFile = storage_path() . '/app/' . $originalFile;

            if (!File::exists($jsonFile)) :
                $msg = 'Error: ocurrió un error de escritura al tratar de ';
                $msg .= 'generar el reporte. ';
                $msg .= 'Debe consultar al administrador del sistema';
                throw new \Exception($msg);
            endif;

            /* LECTURA DEL ARCHIVO JSON CON LOS DATOS DEL REPORTE */
            $jsonReportData = Utilidades::getFile($originalFile);

            //dd($jsonReportData);

            if (empty($jsonReportData)) :
                throw new \Exception('Error: no hay datos para generar el reporte');
            endif;

            /*$arrayValoresDesembolso = $this->obtenerValoresDesembolso($desembolso);
             $creditoBanco = $arrayValoresDesembolso['creditoBanco']; */
            $jsonReportData = json_decode($jsonReportData, true);
            // dd($jsonReportData['RFHA']);

            $totalFilas = 0;

            $encabezado = [];

            //ENCABEZADO
            $encabezado['A1'] = ['text' => 'LÍNEA', 'bold' => true];

            /* LÍNEA DESEMBOLSO*/
            $encabezado['B1'] = ['text' => $jsonReportData['RFHA'], 'bold' => false];

            /* FECHA INICIO DESEMBOLSO*/
            $encabezado['A2'] = ['text' => 'FECHA INICIO DESEMBOLSO', 'bold' => true];
            $encabezado['B2'] = ['text' => $jsonReportData['info_linea']['DBLFZ'], 'bold' => false];

            /* FECHA FIN DESEMBOLSO*/
            $encabezado['C2'] = ['text' => 'FECHA FIN DESEMBOLSO', 'bold' => true];
            $encabezado['D2'] = ['text' => $jsonReportData['info_linea']['DELFZ'], 'bold' => false];


            $encabezado['A3'] = ['text' => 'VALOR DESEMBOLSO', 'bold' => true];
            $encabezado['B3'] = ['text' => $jsonReportData['info_linea']['IMPORTE_LIMITE'], 'bold' => false, 'format_money' => true];
            $encabezado['C3'] = ['text' => 'BANCO', 'bold' => true];
            $encabezado['D3'] = ['text' => $jsonReportData['info_linea']['KONTRH'], 'bold' => false];
            /* $tipo = '';
             if ($desembolso['es_independiente']) :
             $tipo = 'SI';
             else :
             $tipo = 'NO';
             endif;*/


            $encabezado['A4'] = ['text' => 'FECHA DEL REPORTE', 'bold' => true];
            $encabezado['B4'] = ['text' => date('Y-m-d H:i'), 'bold' => false];

            /* $encabezado['C4'] = ['text' => 'FECHA DE ACTUALIZACIÓN', 'bold' => true];
             $encabezado['D4'] = ['text' => $desembolso['updated_at'], 'bold' => false];*/


            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->setActiveSheetIndex(0);

            $tituloHoja = 'CUOTAS_PROYECCIONES_DESEMBOLSO_';
            $objPHPExcel->getActiveSheet()->setTitle($tituloHoja);

            //ESCRIBIR ENCABEZADOS
            Utilidades::setCellValue($objPHPExcel, $encabezado);


            /**************************** CUOTAS *************************************  */
            $tituloCuotas = [
                "A6" => ['text' => "FLUJO DE CAJA, DESEMBOLSO: " . $jsonReportData['numDesembolso'], 'bold' => true],
            ];
            Utilidades::setCellValue($objPHPExcel, $tituloCuotas);

            $filaCabeceraCuotas = 8;
            $cabeceraCuotas = [
                "A" . $filaCabeceraCuotas => ['text' => "NÚMERO DE CUOTA", 'bold' => true],
                "B" . $filaCabeceraCuotas => ['text' => "FECHA", 'bold' => true],
                "C" . $filaCabeceraCuotas => ['text' => "CONCEPTO", 'bold' => true],
                "D" . $filaCabeceraCuotas => ['text' => "AMORTIZACIÓN CAPITAL", 'bold' => true],
                "E" . $filaCabeceraCuotas => ['text' => "INTERES PROYECTADO", 'bold' => true],
                //"F" . $filaCabeceraCuotas => ['text' => "INTERES PAGADO", 'bold' => true],
                "F" . $filaCabeceraCuotas => ['text' => "TOTAL SERVICIO DEUDA", 'bold' => true],
                "G" . $filaCabeceraCuotas => ['text' => "SALDO DEUDA", 'bold' => true]
            ];


            //ESCRIBIR CABECERAS
            Utilidades::setCellValue($objPHPExcel, $cabeceraCuotas);

            $inicio = 9; //FILA DE INICIO
            $sleep = 0;
            $cuotas = $jsonReportData['flujoCajaDesembolso'];

            //  dd($jsonReportData['flujoCajaDesembolso']);
            $lstCuotas = [];
            foreach ($cuotas as $index => $value) :

                $lstCuotas["A" . $inicio] = ['text' => $index]; //NÚMERO DE CUOTA
                $lstCuotas["B" . $inicio] = ['text' => trim($value['DFAELL'])]; //FECHA DE LA CUOTA
                $lstCuotas["C" . $inicio] = ['text' => trim($value['DESC_SFHAZBA'])]; //CONCEPTO
                $lstCuotas["D" . $inicio] = ['text' => trim($value['AMORT_CAPITAL']), 'format_money' => true]; //AMORTIZACIÓN A CAPITAL
                $lstCuotas["E" . $inicio] = ['text' => trim($value['INTERES']), 'format_money' => true]; //INTERES PROYECTADO
                //   $lstCuotas["F" . $inicio] = ['text' => $jsonReportData['interes_pagado']]; //INTERES PAGADO
                $lstCuotas["F" . $inicio] = ['text' => trim($value['SERV_DEUDA']), 'format_money' => true]; //TOTAL SERVICIO DEUDA
                $lstCuotas["G" . $inicio] = ['text' => trim($value['SALDO_DEUDA']), 'format_money' => true]; //SALDO DEUDA

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
            $totalFilas = $totalFilas + 10;
            $totalFilas = $totalFilas + count($cuotas);

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
                "F" . $filaCabeceraProyecciones => ['text' => 'TASA NONIMAL', 'bold' => true],
                "G" . $filaCabeceraProyecciones => ['text' => 'TASA EFECTIVA', 'bold' => true],
                "H" . $filaCabeceraProyecciones => ['text' => "VALOR", 'bold' => true]
            ];

            Utilidades::setCellValue($objPHPExcel, $cabeceraProyecciones);

            /**************************** PROYECCIONES *************************************  */
            $inicio = $totalFilas + 3; //FILA DE INICIO
            $sleep = 0;
            $proyecciones = $jsonReportData['proyecciones'];

            // dd($filaCabeceraProyecciones);

            // dd($proyecciones);
            $lstProyecciones = [];
            foreach ($proyecciones as $index => $value) :

                $lstProyecciones["A" . $inicio] = ['text' => $index]; //NÚMERO DE CUOTA
                $lstProyecciones["B" . $inicio] = ['text' => $value['IRA_DATE']]; //FECHA DE LA CUOTA
                $lstProyecciones["C" . $inicio] = ['text' => $value['INT_VALUE']]; //CONCEPTO
                $lstProyecciones["D" . $inicio] = ['text' => $value['TPINTREF2']]; //AMORTIZACIÓN A CAPITAL
                $lstProyecciones["E" . $inicio] = ['text' => $value['NUM_DIAS_TASA_REF']]; //INTERES PROYECTADO
                $lstProyecciones["F" . $inicio] = ['text' => $value['TASA_NTV']]; //INTERES PAGADO
                $lstProyecciones["G" . $inicio] = ['text' => $value['TASA_EA_TV']]; //TOTAL SERVICIO DEUDA
                $lstProyecciones["H" . $inicio] = ['text' => $value['VALOR'], 'format_money' => true]; //SALDO DEUDA

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
            $nombreArchivo = 'FLUJO_' . $jsonReportData['numDesembolso'];


            $writer->save(public_path() . '/reportes/' . $nombreArchivo . ".xlsx");
            $data['success'] = true;
            $data['archivoExcel'] = URL::asset('reportes/' . $nombreArchivo . '.xlsx');

            File::delete($jsonFile);
        } catch (\Exception $ex) {
            //  DB::rollback();
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

    public function ajaxGuardarActualizarProyeCuotasLineaSap(Request $request)
    {
        $data = [];


        $requestData = $request->all();

        $arrayProyEnviadas = $requestData['proyecciones'];

        //dd($arrayProyEnviadas);

        $arrrayProyActualizadas = [];

        foreach ($arrayProyEnviadas as $index => $item) :

            $indiceActual = $item['INDEX'];
            $proySiguiente = $this->obtenerProySiguiente($indiceActual);
            $proyAnterior = $this->obtenerProySiguiente($indiceActual);

            $tasaInteres = floatval($item['INT_VALUE']);
            $spread = floatval($item['TPINTREF2']);
            $tasaNominal = $this->obtenerTasaNominal(
                $tasaInteres,
                $spread
            );

            $saldoDeuda = floatval($proySiguiente['SALDO_DEUDA_ANT']);
            // dd($proySiguiente);

            $numDias = intval($item['NUM_DIAS_TASA_REF']);
            $numPeriodosAnno = $this->obtenerNumPerAnno($numDias);
            $tasaPeriodica = $this->obtenerTasaPeriodica($tasaNominal, $numPeriodosAnno);
            $tasaEfectivaAnual = $this->obtenerTasaEfecAnual($tasaPeriodica, $numDias);
            $interesesProyectado = $this->obtenerInteresProyectadoEstandar(
                $saldoDeuda,
                ($tasaNominal / 100),
                $numDias
            );


            $arrrayProyActualizadas[] = [
                "ID" => $proySiguiente['ID'],
                'INDEX' => $proySiguiente['INDEX'],
                "ID_CUOTA" => $proySiguiente['ID_CUOTA'],
                "IRA_DATE" => $proySiguiente['IRA_DATE'], // "20180201",
                "INT_VALUE" => $proySiguiente['INT_VALUE'], //"13.43",
                "TPINTREF2" => $proySiguiente['TPINTREF2'], //49.5,
                "NUM_DIAS_TASA_REF" => $proySiguiente['NUM_DIAS_TASA_REF'], //90,
                'TASA_NTV' => $this->redondearResultado($tasaNominal) . '%',
                'TASA_NTV_DEC' => $tasaNominal, //SIN DECIMAL Y SIN PORCENTAJE PAR ACTUALIZAR
                // "TASA_EA_TV" =>  $proySiguiente['TASA_EA_TV'],
                'TASA_EA_TV' => ($this->redondearResultado($tasaEfectivaAnual, 3) * 100) . ' %',
                'TASA_EA_TV_DEC' => $tasaEfectivaAnual, //SIN DECIMAL Y SIN PORCENTAJE PAR ACTUALIZAR
                "SALDO_DEUDA_ANT" => $proySiguiente['SALDO_DEUDA_ANT'], //84000000,
                "VALOR" => $interesesProyectado,
                "cambio_valores" => false
            ];



        endforeach;


        // $this->actualizarArrayDatosLinea($arrrayProyActualizadas);

        //dd($arrrayProyActualizadas);

        $statusCode = 200;

        $data['proyecciones'] = $arrrayProyActualizadas;
        $data['success'] = true;
        return response()->json($data, $statusCode);
    }

    /* private function actualizarArrayDatosLinea($arrrayProyActualizadas)
     {
     if (!isset($_SESSION)) :
     session_start();
     endif;
     $arrayDatosLinea = $_SESSION['arrayDatosLinea'];
     foreach ($arrrayProyActualizadas as $indexPro => $item) :
     foreach ($arrayDatosLinea[] as $index => $item) :
     endforeach;
     endforeach;
     dd($arrayDatos);
     }*/

    /*
     actualizarProyecciones(arrayProyeccionesActualizadas) {
     arrayProyeccionesActualizadas.forEach((item, index) => {
     this.datosDesembolso.proyecciones.forEach((proye, indx) => {
     if (item.INDEX == proye.INDEX) {
     proye.TASA_EA_TV = item.TASA_EA_TV;
     proye.TASA_EA_TV_DEC = item.TASA_EA_TV_DEC;
     proye.TASA_NTV = item.TASA_NTV;
     proye.TASA_NTV_DEC = item.TASA_NTV_DEC;
     proye.VALOR = item.VALOR;
     return;
     }
     });
     });
     },
     actualizarCuotas(arrayProyeccionesActualizadas) {
     arrayProyeccionesActualizadas.forEach((item, index) => {
     this.datosDesembolso.flujoCajaDesembolso.forEach((cuota, indx) => {
     if (item.ID_CUOTA == cuota.ID) {
     cuota.INTERES = item.VALOR;
     //SERVICIO DEUDA
     cuota.SERV_DEUDA = item.VALOR
     console.log({cuotaActualizada: cuota});
     return;
     }
     });
     });
     }
     */

    private function obtenerProySiguiente($indiceActual)
    {
        if (!isset($_SESSION)) :
            session_start();
        endif;

        $arrayDatosLinea = $_SESSION['arrayDatosDesembolso'];
        $arrayProyecciones = $arrayDatosLinea['PROYECCIONES'];

        return $arrayProyecciones[$indiceActual + 1];
    }

    private function obtenerProyAnterior($indiceActual)
    {
        if (!isset($_SESSION)) :
            session_start();
        endif;

        $arrayDatosLinea = $_SESSION['arrayDatosDesembolso'];
        $arrayProyecciones = $arrayDatosLinea['PROYECCIONES'];

        return $arrayProyecciones[$indiceActual - 1];
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

            //ACTUALIZAR VALORES
            $indexProyeCambiadas = 0;
            for ($indexProyeCambiadas; $indexProyeCambiadas < count($proyeCambiadas); $indexProyeCambiadas++) :
                if (
                    $proyeDesembolso[$index]['id_cuota'] === $proyeCambiadas[$indexProyeCambiadas]['id_cuota'] &&
                    $proyeDesembolso[$index]['numero'] === $proyeCambiadas[$indexProyeCambiadas]['numero']
                ) :
                    $proyeCambiada = $proyeCambiadas[$indexProyeCambiadas];
                    $proyeDesembolso[$index]['num_dias'] = $proyeCambiada['num_dias'];
                    $proyeDesembolso[$index]['spread'] = $proyeCambiada['spread'];
                    $proyeDesembolso[$index]['tasa_ref_valor'] = $proyeCambiada['tasa_ref_valor'];
                    $proyeDesembolso[$index]['cambio_valores'] = true;
                    break;
                endif;
            endfor;

            //ACTUALIZAR VALORES
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

        //NÚMERO DE DÍAS DEL CREDITO
        $numDiasCredito = $creditoBanco['num_dias']; //90 o 30

        //  dd($proyeCambiadas);
        //RECORRER LAS CUOTAS
        for ($i = 0; $i < count($proyeCambiadas); $i++) :

            $proyeCambiada = $proyeCambiadas[$i];

            //VERIFICAR SI LA PROYECCIÓN DE LA CUOTA CAMBIO
            // $cuotaId = $cuotasDesembolso[$i]['id_cuota'];

            $arrayProyecciones = $this->obtenerProyeccionesCuota(
                // $cuotasDesembolso[$i]['id_cuota'],
                $proyeCambiada['id_cuota'], //PROYECCION CAMBIADA
                $proyeDesembolso //PROYECCIONES DEL DESEMBOLSO
            );

            // dd($cuotasDesembolso);
            //  dd($cuotaId);
            //dd($proyeDesembolso);
            // dd($cuotasDesembolso);
            //SI CAMBIÓ
            if (!empty($arrayProyecciones)) :

                //  $interesesProyectado = 0;

                $sumaInteresProyectado = 0;

                //   dd($arrayProyecciones);

                foreach ($arrayProyecciones as $index => $proyeccion) :

                    $intPro = $this->obtenerInteresProyectadoCuota(
                        $numDiasCredito,
                        $truncarTasaEa,
                        $proyeccion
                    );

                    //ACTUALIZAR EL INTERES PROYECTADO
                    $proyeccion['valor'] = $intPro;
                    $sumaInteresProyectado = $sumaInteresProyectado + $intPro;

                    /* if ($index == 0) :
                     dd($proyeccion);
                     endif;*/

                    //ACTUALIZAR LA PROYECCIÓN
                    $this->actualizarProyeccion(
                        $proyeDesembolso,
                        $proyeccion,
                        $numDiasCredito, //CUIDADO => PARA OBTENER EL NÚMERO DE PERIODOS DEL AÑO
                        $truncarTasaEa
                    );

                endforeach;

                // dd($cuotasDesembolso);

                $this->actualizarCuota(
                    $proyeCambiada['id_cuota'],
                    $cuotasDesembolso,
                    $sumaInteresProyectado //NUEVO INTERES PROYECTADO DE LA CUOTA
                    /* $saldoDeuda,
     $proyeccion,
     $truncarTasaEa,
     $proyeccRepetidas */    //PARA SACAR LA PROYECCIÓN REPETIDA
                );

                //  dd($arrayProyecciones);


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
        &$proyecciones, //PROYECCIONES DEL DESEMBOLSO
        $proyeccion, //PROYECCIÓN A ACTUALIZAR
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

            $numDias = intval($proyeccion['num_dias']);
            /* LA TASA EFECTIVA ANUAL SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN PARA TRUNCAR */

            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnualTruncada(
                $tasaNominal,
                // $numDiasProyeccion
                $numDias
            );

            // dd($numDias);
        endif;

        //dd($truncarTasaEa);

        //  dd($tasaPeriodica);

        //  dd($tasaEfectivaAnual);
        // dd( $proyeccion['tasa_ref_valor']);

        // dd($tasaEfectivaAnual);
        // $proyeccion['numero'] =  $proyeccion['numero'];
        $proyeccion['spread'] = $proyeccion['spread'];
        $proyeccion['num_dias'] = $proyeccion['num_dias'];
        $proyeccion['tasa_nominal_decimal'] = $tasaNominal;
        $proyeccion['tasa_nominal'] = $tasaNominal . '%';
        $proyeccion['tasa_efectiva_anual_decimal'] = $tasaEfectivaAnual;

        $tasaEfectivaAnual = $tasaEfectivaAnual * 100;
        //$tasaEfectivaAnual = $tasaEfectivaAnual * 100;


        //  dd($truncarTasaEa);
        if ($truncarTasaEa == true) :
            $tasaEfectivaAnual = $this->truncate($tasaEfectivaAnual, 4);
        else :
            $tasaEfectivaAnual = round($tasaEfectivaAnual, 3);
        endif;

        $proyeccion['tasa_efectiva_anual'] = $tasaEfectivaAnual . '%';

        $proyeccion['valor'] = $this->fijarFormatoDinero(floatval($proyeccion['valor']), 0);

        if ($proyeccion['anno'] == '2021') :
            // dd($proyeccion);
        endif;

        foreach ($proyecciones as $index => $proye) :
            // dd($proye);
            if ($proye['id_proyeccion'] == $proyeccion['id_proyeccion']) :
                $proyecciones[$index] = $proyeccion;
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
        $saldoDeuda = $proyeccion['saldo_deuda_inter_proy'];
        $spread = $proyeccion['spread'];

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
            /* LA TASA EFECTIVA ANUAL TRUNCADA SE CALCULA CON LOS DÍAS DE LA PROYECCIÓN */
            $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnualTruncada(
                $tasaNominal,
                $numDiasProyeccion
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
        $cuotaId,
        &$cuotas,
        $sumaInteresProyectado
    ) {
        foreach ($cuotas as $index => $cuota) :
            // dd($cuota);
            if ($cuotaId == $cuota['id_cuota']) :
                $cuotas[$index]['interes_proyectado'] = $this->fijarFormatoDinero($sumaInteresProyectado, 0);
                $totalSerDeuda = $sumaInteresProyectado + $cuota['amort_capital'];
                $cuotas[$index]['total_serv_deuda'] = $this->fijarFormatoDinero($totalSerDeuda, 0);
            endif;
        endforeach;
    }

    private function obtenerProyeccionesCuota($cuotaId, $proyecciones)
    {
        // dd($proyecciones);
        $arrayProyecciones = [];
        for ($i = 0; $i < count($proyecciones); $i++) :
            if ($proyecciones[$i]['id_cuota'] === $cuotaId) :
                $arrayProyecciones[] = $proyecciones[$i];
            endif;
        endfor;

        return $arrayProyecciones;
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


    public function test_sap(Request $request, $linea = '611517070')
    {
        /* if (! extension_loaded('sapnwrfc')) {
         echo ('Extension "sapnwrfc" not loaded. Please see https://github.com/piersharding/php-sapnwrfc#installation');
         } exit; */

        /*  $config = [
         'ashost' => '172.16.7.76',
         'sysnr'  => '00',
         'client' => '710',
         'user' => 'HVALENCI',
         'passwd' => 'PACHITA111',
         'show_errors' => true,
         'debug' => true,
         ];*/

        try {
            $c = new SapConnection(CONFIG_SAP_CONNECTION);

            $BUKRS = CONFIG_SAP_BUKRS; //CONFIG_SAP["GVAL"];
            $RFHA = $linea; //CONFIG_SAP["611516840"];
            $f = $c->getFunction(CONFIG_SAP_TRANSACTION);
            $result = $f->invoke([
                'BUKRS' => $BUKRS,
                'RFHA' => $RFHA,
                'S_DEUDAGRAL' => array(),
                'T_DEUDAPUBL' => array(),
                'T_FLUJOCAJA' => array(),
                'T_REVISINTER' => array(),
            ]);

            // echo "<pre>";
            //echo json_encode($result); // devuelve el resultado de sap
            echo "<pre>";
            print_r($result);
        } catch (SapException $ex) {
            echo 'Exception: ' . $ex->getMessage() . PHP_EOL;
        }
    }

    public function getSapData($RFHA)
    {
        $arrayData = [];
        try {
            $c = new SapConnection(CONFIG_SAP_CONNECTION);

            $BUKRS = CONFIG_SAP_BUKRS; //CONFIG_SAP["GVAL"];
            $RFHA = $RFHA; //CONFIG_SAP["611516840"];
            $f = $c->getFunction(CONFIG_SAP_TRANSACTION);
            $arrayData = $f->invoke([
                'BUKRS' => $BUKRS,
                'RFHA' => $RFHA,
                'S_DEUDAGRAL' => array(),
                'T_DEUDAPUBL' => array(),
                'T_FLUJOCAJA' => array(),
                'T_REVISINTER' => array(),
            ]);

            //  echo "<pre>";
            //  print_r($result); // devuelve el resultado de sap
        } catch (SapException $ex) {
            echo 'Exception: ' . $ex->getMessage() . PHP_EOL;
        }

        return $arrayData;
    }

    public function getSapDataLocalJson($RFHA)
    {
        $path = 'creditos_json/' . $RFHA . '.json';

        $jsonFile = storage_path() . '/app/' . $path;
        if (!File::exists($jsonFile)) :
            $msg = 'No existen datos asociados al número de línea ingresado ';
            throw new \Exception($msg);
        endif;

        $file = Utilidades::getFile($path);
        $jsonArray = [];
        if (!empty($file)) :
            $jsonArray = json_decode($file, true);
        endif;
        return $jsonArray;
    }
}
