<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Credito;
use App\Models\CreditoBanco;
use App\Helpers\Utilidades;
use App\Models\Desembolso;
//use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Ramsey\Uuid\Type\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; //Here

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SimuladoresController extends Controller
{
    private $pageLimit = 10;
    private $cuotas = [];
    private $proyecciones = [];

    //private $who = App\Models\Credito::class;

    //  private  class1 = App\Models\Credito::class;


    public function desembolso($creditoBancoId)
    {
        return view('simulador.form_desembolso', ['creditoBancoId' => $creditoBancoId]);
    }

    public function desembolsos($creditoBancoId = 0)
    {
        return view('simulador.listado_desembolsos', ['creditoBancoId' => $creditoBancoId]);
    }

    public function index(Request $request)
    {

        // Utilidades::msgBox($request, 'Hola', 'success');

        $method = $request->method();
        $creditos = null;

        if ($request->isMethod('post')) {
            $filtro = $request->input('txtFiltro');
            $creditos = Credito::where('num_ordenanza', 'LIKE', '%' . $filtro . '%')
                ->where('tipo_credito_id', 2)
                // ->orWhere('linea', 'LIKE', '%' . $filtro . '%')
                ->paginate($this->pageLimit);
        } else {
            $creditos = Credito::where('tipo_credito_id', 2)->paginate($this->pageLimit);
        }

        $tipoCredito = 'SIMULADO';
        $titulo = "Listado de crÉditos simulados";
        return view('simulador.listado_creditos', compact('creditos', 'titulo', 'tipoCredito'));
    }





    public function ajaxObtenerDesembolso(Request $request, $desembolsoId)
    {
        $statusCode = 200;
        $data['success'] = true;
        $data['message'] = 'Cuotas generadas correctamente';

        try {
            // $requestData = $desembolso = $request;
            // $creditoBancoId = $requestData['credito_banco_id'];
            // $creditoBanco = CreditoBanco::find($creditoBancoId);

            $desembolso = Desembolso::find($desembolsoId);

            if (empty($desembolso)) :
                throw new \Exception('Error: el desembolso que intenta obtener no existe');
            endif;


            //SI PASA ESTE FILTRO ES PORQUE YA TIENE CUOTAS CALCULADAS, SIN IMPORTAR SI ES PRIMERO O SEGUNDO
            // if (!empty($desembolso['cuotas_json']) && !empty($desembolso['proyecciones_json'])) :
            $cuotas = json_decode($desembolso['cuotas_json'], true);
            $proyecciones = json_decode($desembolso['proyecciones_json'], true);
            // goto BanderaEnviarCuotas; //SALTAR A ENVIAR CUOTAS Y PROYECCIONES
            // endif;


            /*   $creditoBanco = CreditoBanco::find($desembolso['credito_banco_id']);
            $primerDesembolso = Desembolso::where('credito_banco_id', $desembolso['credito_banco_id'])->first();

            // dd( $primerDesembolso->toArray());

            $esPrimerDesembolso = false;
            if ($desembolso['id'] === $primerDesembolso['id']) :
                $esPrimerDesembolso = true;
            endif;

            // dd($esPrimerDesembolso);

            if ($esPrimerDesembolso) :
                $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones($desembolso, $creditoBanco);
            else :

                // $cuotasPrimerDesembolso = json_decode($primerDesembolso['cuotas_json'], true);

                $arrayCuotasProyecciones = $this->obtenerCuotasProyecciones(
                    $desembolso,
                    $creditoBanco,
                    false,
                    $primerDesembolso
                );
            endif;*/



            //$cuotas = $arrayCuotasProyecciones['cuotas'];
            //$proyecciones = $arrayCuotasProyecciones['proyecciones'];


            // BanderaEnviarCuotas:
            $data['desembolso'] = $desembolso;
            $data['cuotas'] = $cuotas;
            $data['proyecciones'] = $proyecciones;

            $request->session()->put('cuotas_' . $desembolsoId, $cuotas);
            $request->session()->put('proyecciones_' . $desembolsoId, $proyecciones);

            /* $datos['cuotas_json'] = json_encode($cuotas); //json_decode
            $datos['proyecciones_json'] = json_encode($proyecciones);

            //$desembolso ->up
            Desembolso::whereId($desembolso['id'])->update($datos);

            if (!$desembolso) :
                throw new \Exception('Error al intentar generar el desembolso');
            endif;
 */

            //    Session::put('key', 'value');

            //  $request->session()->put('cuotas', $cuotas);
            //  $request->session()->put('proyecciones', $proyecciones);
        } catch (\Exception $ex) {
            $statusCode = 500;
            $data['success'] = false;
            $data['message'] = $ex->getMessage();
            $data['message1'] = $ex;
        }

        return response()->json($data, $statusCode);
    }


    private function obtenerFechaFinal($mesPiloto, $annofechaGenerada, $numMesesSumar, $diaVerificado)
    {
        $verificacion = $mesPiloto + $numMesesSumar - 12; // 0
        $mesfechaGenerada = $mesPiloto + $numMesesSumar; //1+3 = 4
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
    private function actualizarCuota(
        $cuota,
        $saldoDeuda,
        $spread,
        //$numDias,
        // $amortizacionCapital,
        $proyeccion
    ) {

        $tasaNtv = $proyeccion['tasa_ref_valor'] + $proyeccion['spread'];
        $tasaNtv = $tasaNtv / 100;

        //dd($proyeccion['num_dias']);
        $numDiasProyeccion = $proyeccion['num_dias'];


        $interesesProyectado = $saldoDeuda * $tasaNtv * $numDiasProyeccion;
        $interesesProyectado = $interesesProyectado / 360;

        // dd($proyeccion['tasa_efectiva_anual_decimal']);

        $cuota['interes_proyectado'] = $this->fijarFormatoDinero($interesesProyectado);
        $totalSerDeuda = $interesesProyectado +  $cuota['amort_capital'];

        $cuota['total_serv_deuda'] =  $this->fijarFormatoDinero($totalSerDeuda);

        // $saldoDeuda = $saldoCuotaAneterior;
        $saldoDeuda = $saldoDeuda -  $cuota['amort_capital'];
        $cuota['saldo_deduda'] = $saldoDeuda;

        return $cuota;
    }

    private function actualizarProyeccion(
        $spread,
        $numDiasCredito,
        // $tasaRefValor,
        $proyeccion,
        $interesesProyectado
    ) {

        $tasaNtv = $proyeccion['tasa_ref_valor'] +  $proyeccion['spread'];
        $tasaNtv = $tasaNtv;
        //$tasaNtvPorc = $tasaNtv / 100;

        //dd($tasaNtv);

        //OBTENER PERIODOS DEL AÑO
        $numPeriodosAnno = $numDiasCredito / 30;
        $numPeriodosAnno = 12 / $numPeriodosAnno;



        //TASA PERIODICA
        $tasaPeriodica = $tasaNtv / $numPeriodosAnno;
        $tasaPeriodica = $tasaPeriodica / 100;


        //NÚMERO DE DIAS DE LA PROYECCIÓN
        //$numDiasProyeccion = $proyeccion['num_dias'];

        //TASA EFECTIVA ANUAL
        $base =  1 + $tasaPeriodica;
        $exp = (360 / $numDiasCredito);
        $tasaEfectivaAnual = pow($base, $exp) - 1;
        // dd( $proyeccion['tasa_ref_valor']);

        // dd($tasaEfectivaAnual);
        $proyeccion['spread'] =  $proyeccion['spread'];
        $proyeccion['num_dias'] =  $proyeccion['num_dias'];
        $proyeccion['tasa_nominal_decimal'] =  $tasaNtv;
        $proyeccion['tasa_nominal'] =  $tasaNtv . '%';
        $proyeccion['tasa_efectiva_anual_decimal'] = $tasaEfectivaAnual;
        $tasaEfectivaAnual = $tasaEfectivaAnual * 100;
        $proyeccion['tasa_efectiva_anual'] = round($tasaEfectivaAnual, 3) . '%';
        $proyeccion['valor'] = $interesesProyectado;
        return $proyeccion;
    }


    private function obtenerProyeccionesCuota($cuotaId, $proyecciones)
    {
        // dd($proyecciones);
        for ($i = 0; $i < count($proyecciones); $i++) {
            if ($proyecciones[$i]['id_cuota'] === $cuotaId) {
                // dd($proyecciones[$i]);
                return $proyecciones[$i];
            }
        }

        return [];
    }

    private function guardarActualizarCuotas(
        $proyeCambiadas,
        $cuotasCambiadas,
        $desembolsoId,
        $guardar = false
    ) {
        $data = [];
        $statusCode = 200;
        //  $proyeCambiadas = $request->proyecciones;
        // $cuotasCambiadas = $request->cuotas;
        // $desembolsoId = $request->desembolso_id;
        // $itemsCambiados = json_decode($proyeCambiadas, true);


        $desembolso = Desembolso::find($desembolsoId);

        if (empty($desembolso)) :
            throw new \Exception('Error: el desembolso enviado no existe');
        endif;

        $cuotasDesembolso = json_decode($desembolso['cuotas_json'], true);
        $proyeDesembolso = json_decode($desembolso['proyecciones_json'], true);
        $proyeCambiadas = json_decode($proyeCambiadas, true);
        $cuotasCambiadas = json_decode($cuotasCambiadas, true);
        // $proyecciones = $request->session()->get('proyecciones');
        // $cuotasEnviadas = $request->session()->get('cuotas');
        // dd($desembolsoId);
        // dd($cuotasEnviadas);

        //  dd($cuotasCambiadas);

        $nuevasProyecciones = [];
        for ($index = 0; $index < count($proyeDesembolso); $index++) {

            $indexProyeCambiadas = 0;
            for ($indexProyeCambiadas; $indexProyeCambiadas < count($proyeCambiadas); $indexProyeCambiadas++) {
                if ($proyeDesembolso[$index]['id_cuota'] === $proyeCambiadas[$indexProyeCambiadas]['id_cuota']) {
                    $nuevasProyecciones[] = $proyeCambiadas[$indexProyeCambiadas];
                    break;
                }
            }

            $indexCuotasCambiadas = 0;
            for ($indexCuotasCambiadas; $indexCuotasCambiadas < count($cuotasCambiadas); $indexCuotasCambiadas++) {
                if ($cuotasDesembolso[$index]['id_cuota'] === $cuotasCambiadas[$indexCuotasCambiadas]['id_cuota']) {
                    $cuotasDesembolso[$index]['interes_pagado'] = $cuotasCambiadas[$indexCuotasCambiadas]['interes_pagado'];
                    break;
                }
            }

            // $cuotasDesembolso[$index]['interes_pagado'] =  $cuotasCambiadas[$index]['interes_pagado'];
        }

        //  dd( $cuotasDesembolso);

        // $desembolso = Desembolso::find($desembolsoId);

        /*if (empty($desembolso)) :
            throw new \Exception('Error: el desembolso que intenta obtener no existe');
        endif;*/

        $creditoBanco = CreditoBanco::find($desembolso['credito_banco_id']);

        //Número de días
        $numDiasCredito = $creditoBanco['num_dias']; //90 o 30


        $fecha1 = date_create($creditoBanco['fecha_inicio']);
        $fecha2 = date_create($creditoBanco['fecha_fin']);
        $diff = date_diff($fecha1, $fecha2);

        //Número de años
        $numAnnos = $diff->y;

        //NÚMERO CUOTAS
        $numCuotas =  $numAnnos * 12;
        $diasConvertidosEnMes = $numDiasCredito / 30;
        $numCuotas = $numCuotas / $diasConvertidosEnMes;

        //PERIODO DE GRACIA
        $periodoGraciaEnMeses = $creditoBanco['periodo_gracia'] * 12;
        $numCuotasPeriodoGracia = $periodoGraciaEnMeses / $diasConvertidosEnMes;

        //NÚMERO DE CUOTAS SIN PERIODO DE GRACIA
        $numCuotasSinPeriodoGracia = $numCuotas - $numCuotasPeriodoGracia;

        $saldoDeuda = $desembolso['valor'];

        $amortizacionCapital = $saldoDeuda  / $numCuotasSinPeriodoGracia;

        //SPREAD
        $spread = $creditoBanco['spread'];



        //RECORRER LAS CUOTAS
        // $cuotas = $cuotasDesembolso;

        //    $proyeccionesEnvidas
        for ($i = 0; $i < count($cuotasDesembolso); $i++) {

            // dd($cuotas[$i]);

            //CUOTAS PERIODO GRACIA
            if ($i > 0) {
                if ($i < $numCuotasPeriodoGracia) { //3
                    $saldoDeuda = $cuotasDesembolso[$i - 1]['saldo_deuda'];
                } else {
                    $saldoDeuda = $cuotasDesembolso[$i - 1]['saldo_deuda'] - $amortizacionCapital; //FALTABA AMORTIZACIÓN A CAPITAL
                }
            }



            $proyeccion = $this->obtenerProyeccionesCuota($cuotasDesembolso[$i]['id_cuota'], $nuevasProyecciones);



            if (!empty($proyeccion)) {

                //$numDiasProyeccion = $proyeccion['num_dias'];


                $cuotasDesembolso[$i] = $this->actualizarCuota(
                    $cuotasDesembolso[$i],
                    $saldoDeuda,
                    $spread,
                    // $numDias,
                    //  $amortizacionCapital,
                    $proyeccion
                );


                $proyeDesembolso[$i] =  $this->actualizarProyeccion(
                    $spread,
                    $numDiasCredito,
                    $proyeccion,
                    $cuotasDesembolso[$i]['interes_proyectado']
                );

                /* $proyecciones[$i] = $this->actualizarProyeccion(
                    $spread,
                    $numDias,
                    $proyeccion
                ); */
                //   }

                // dd($cuotas[$i]);
            }
            // }

            // $ultimoIndexCuotaGracia = $i;
        }



        $data['cuotas'] = $cuotasDesembolso;
        $data['proyecciones'] = $proyeDesembolso;

        if ($guardar) :
            $datosJson['cuotas_json'] = json_encode($data['cuotas']); //json_decode
            $datosJson['proyecciones_json'] = json_encode($data['proyecciones']); ////json_decode

            $desembolso = Desembolso::whereId($desembolsoId)->update($datosJson);
            if (!$desembolso) :
                throw new \Exception('Error al intentar actualizar guardar las cuotas');
            endif;
        endif;

        return $data;
    }

    public function ajaxGuardarActualizarCuotas(Request $request)
    {

        $data = [];
        $statusCode = 200;
        $proyeCambiadas = $request->proyecciones;
        $cuotasCambiadas = $request->cuotas;
        $guardarCambios = $request->guardar_cambios;
        $desembolsoId = $request->desembolso_id;

        // dd($guardarCambios);
        $arrayCuotasProyecciones = $this->guardarActualizarCuotas(
            $proyeCambiadas,
            $cuotasCambiadas,
            $desembolsoId,
            $guardarCambios
        );

        $data['cuotas'] = $arrayCuotasProyecciones['cuotas'];
        $data['proyecciones'] = $arrayCuotasProyecciones['proyecciones'];

        Session::put('cuotas_' . $desembolsoId, $data['cuotas']);
        Session::put('proyecciones_' . $desembolsoId, $data['proyecciones']);

        $data['success'] = true;
        return response()->json($data, $statusCode);
    }


    public function ajaxActualizarCuotas1(Request $request)
    {
        $data = [];
        $statusCode = 200;
        $proyeCambiadas = $request->proyecciones;
        $cuotasCambiadas = $request->cuotas;
        $desembolsoId = $request->desembolso_id;
        // $itemsCambiados = json_decode($proyeCambiadas, true);


        $desembolso = Desembolso::find($desembolsoId);

        if (empty($desembolso)) :
            throw new \Exception('Error: el desembolso enviado no existe');
        endif;

        $cuotasDesembolso = json_decode($desembolso['cuotas_json'], true);
        $proyeDesembolso = json_decode($desembolso['proyecciones_json'], true);
        $proyeCambiadas = json_decode($proyeCambiadas, true);
        $cuotasCambiadas = json_decode($cuotasCambiadas, true);
        // $proyecciones = $request->session()->get('proyecciones');
        // $cuotasEnviadas = $request->session()->get('cuotas');
        // dd($desembolsoId);
        // dd($cuotasEnviadas);

        //  dd($cuotasCambiadas);

        $nuevasProyecciones = [];
        for ($index = 0; $index < count($proyeDesembolso); $index++) {

            $indexProyeCambiadas = 0;
            for ($indexProyeCambiadas; $indexProyeCambiadas < count($proyeCambiadas); $indexProyeCambiadas++) {
                if ($proyeDesembolso[$index]['id_cuota'] === $proyeCambiadas[$indexProyeCambiadas]['id_cuota']) {
                    $nuevasProyecciones[] = $proyeCambiadas[$indexProyeCambiadas];
                    break;
                }
            }

            $indexCuotasCambiadas = 0;
            for ($indexCuotasCambiadas; $indexCuotasCambiadas < count($cuotasCambiadas); $indexCuotasCambiadas++) {
                if ($cuotasDesembolso[$index]['id_cuota'] === $cuotasCambiadas[$indexCuotasCambiadas]['id_cuota']) {
                    $cuotasDesembolso[$index]['interes_pagado'] = $cuotasCambiadas[$indexCuotasCambiadas]['interes_pagado'];
                    break;
                }
            }

            // $cuotasDesembolso[$index]['interes_pagado'] =  $cuotasCambiadas[$index]['interes_pagado'];
        }

        //  dd( $cuotasDesembolso);

        // $desembolso = Desembolso::find($desembolsoId);

        /*if (empty($desembolso)) :
            throw new \Exception('Error: el desembolso que intenta obtener no existe');
        endif;*/

        $creditoBanco = CreditoBanco::find($desembolso['credito_banco_id']);

        //Número de días
        $numDiasCredito = $creditoBanco['num_dias']; //90 o 30


        $fecha1 = date_create($creditoBanco['fecha_inicio']);
        $fecha2 = date_create($creditoBanco['fecha_fin']);
        $diff = date_diff($fecha1, $fecha2);

        //Número de años
        $numAnnos = $diff->y;

        //NÚMERO CUOTAS
        $numCuotas =  $numAnnos * 12;
        $diasConvertidosEnMes = $numDiasCredito / 30;
        $numCuotas = $numCuotas / $diasConvertidosEnMes;

        //PERIODO DE GRACIA
        $periodoGraciaEnMeses = $creditoBanco['periodo_gracia'] * 12;
        $numCuotasPeriodoGracia = $periodoGraciaEnMeses / $diasConvertidosEnMes;

        //NÚMERO DE CUOTAS SIN PERIODO DE GRACIA
        $numCuotasSinPeriodoGracia = $numCuotas - $numCuotasPeriodoGracia;

        $saldoDeuda = $desembolso['valor'];

        $amortizacionCapital = $saldoDeuda  / $numCuotasSinPeriodoGracia;

        //SPREAD
        $spread = $creditoBanco['spread'];



        //RECORRER LAS CUOTAS
        // $cuotas = $cuotasDesembolso;

        //    $proyeccionesEnvidas
        for ($i = 0; $i < count($cuotasDesembolso); $i++) {

            // dd($cuotas[$i]);

            //CUOTAS PERIODO GRACIA
            if ($i > 0) {
                if ($i < $numCuotasPeriodoGracia) { //3
                    $saldoDeuda = $cuotasDesembolso[$i - 1]['saldo_deuda'];
                } else {
                    $saldoDeuda = $cuotasDesembolso[$i - 1]['saldo_deuda'] - $amortizacionCapital; //FALTABA AMORTIZACIÓN A CAPITAL
                }
            }



            $proyeccion = $this->obtenerProyeccionesCuota($cuotasDesembolso[$i]['id_cuota'], $nuevasProyecciones);



            if (!empty($proyeccion)) {

                //$numDiasProyeccion = $proyeccion['num_dias'];


                $cuotasDesembolso[$i] = $this->actualizarCuota(
                    $cuotasDesembolso[$i],
                    $saldoDeuda,
                    $spread,
                    // $numDias,
                    //  $amortizacionCapital,
                    $proyeccion
                );


                $proyeDesembolso[$i] =  $this->actualizarProyeccion(
                    $spread,
                    $numDiasCredito,
                    $proyeccion,
                    $cuotasDesembolso[$i]['interes_proyectado']
                );

                /* $proyecciones[$i] = $this->actualizarProyeccion(
                    $spread,
                    $numDias,
                    $proyeccion
                ); */
                //   }

                // dd($cuotas[$i]);
            }
            // }

            $ultimoIndexCuotaGracia = $i;
        }



        $data['cuotas'] = $cuotasDesembolso;
        $data['proyecciones'] = $proyeDesembolso;

        // dd($proyecciones);
        $request->session()->put('cuotas_' . $desembolsoId, $cuotasDesembolso);
        $request->session()->put('proyecciones_' . $desembolsoId, $proyeDesembolso);

        $data['success'] = true;
        return response()->json($data, $statusCode);
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
            $statusCode = 500;
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
        return $tasaEfectivaAnual; //SI OTRO DESEMBOLSO
    }

    private function obtenerInteresProyectado($saldoDeuda, $tasaNtvPorce,  $numDias)
    {
        $interesesProyectado = $saldoDeuda * $tasaNtvPorce * $numDias;
        $interesesProyectado = $interesesProyectado / 360;
        return $interesesProyectado;
    }

    /* private function obtenerVariables($desembolso, $creditoBanco)
    {
        //FECHA INICIO
        // $requestData['fecha_inicio'] = date('Y-m-d', strtotime($desembolso['fecha_inicio']));
        //FECHA FIN
        // $requestData['fecha_fin'] = date('Y-m-d', strtotime($desembolso['fecha_fin']));

        $diaFecha1 = date('d', strtotime($desembolso['fecha_inicio'])); //2021-06-18
        $mesFecha1 = date('m', strtotime($desembolso['fecha_inicio']));

        $diaFecha2 = date('d', strtotime($desembolso['fecha_fin']));
        $mesFecha2 = date('m', strtotime($desembolso['fecha_fin']));


        $fecha1 = date_create($desembolso['fecha_inicio']); //FECHA INICIO
        $fecha2 = date_create($desembolso['fecha_fin']); //FECHA FIN
        $diff = date_diff($fecha1, $fecha2);

        //Número de días
        $numDias = $creditoBanco['num_dias']; //90 o 30 //NO AFECTA

        //Número de años
        $numAnnos = $diff->y;

        // dd($numAnnos);

        //NÚMERO CUOTAS
        $numCuotas =  $numAnnos * 12;
        $diasConvertidosEnMes = $numMesesSumar = $numDias / 30;
        //  $diasConvertidosEnMes = intval($diasConvertidosEnMes);
        $numCuotas = $numCuotas / $diasConvertidosEnMes; //NO OTRO DESEMBOLSO

        //PERIODO DE GRACIA SE ESTA CAPTURANDO EN MESES
        $periodoGraciaEnMeses = $creditoBanco['periodo_gracia'];
        $numCuotasPeriodoGracia = $periodoGraciaEnMeses / $diasConvertidosEnMes; //NO OTRO DESEMBOLSO

        //NÚMERO DE CUOTAS SIN PERIODO DE GRACIA
        $numCuotasSinPeriodoGracia = $numCuotas - $numCuotasPeriodoGracia; //NO OTRO DESEMBOLSO

        // dd($numCuotasPeriodoGracia);
        //SPREAD
        $spread = $creditoBanco['spread']; //SI OTRO DESEMBOLSO

        //TASA DE REFERENCIA
        $tasaRef = $creditoBanco['tasa_ref']; //SI OTRO DESEMBOLSO

        //VALOR DE LA TASA REFERENCIA
        $tasaRefValor = $creditoBanco['tasa_ref_valor']; //SI OTRO DESEMBOLSO

        //TASA NTV - TASA NOMINAL
        $tasaNtv = $tasaRefValor + $spread; //SI OTRO DESEMBOLSO
        $tasaNtv = $tasaNtv; //SI OTRO DESEMBOLSO

        //OBTENER EL NÚMERO DE PERIODOS DEL AÑO
        $numPeriodosAnno = $numDias / 30;
        $numPeriodosAnno = 12 / $numPeriodosAnno; //SI OTRO DESEMBOLSO

        //TASA PERIODICA
        $tasaPeriodica = $tasaNtv / $numPeriodosAnno;
        $tasaPeriodica = $tasaPeriodica / 100;
        $tasaPeriodica = $tasaPeriodica;  //SI OTRO DESEMBOLSO

        //TASA EFECTIVA ANUAL
        $base =  1 + $tasaPeriodica;
        $exp = (360 / $numDias);
        $tasaEfectivaAnual = pow($base, $exp) - 1;
        $tasaEfectivaAnual = $tasaEfectivaAnual; //SI OTRO DESEMBOLSO
        //VALOR DESEMBOLSADO
        $valorDesembolso = $saldoDeuda = $desembolso['valor']; //SI OTRO DESEMBOLSO


        //AMORTIZACIÓN CAPITAL
        $amortizacionCapital = 0;

        $amortizacionCapital = $saldoDeuda  / $numCuotasSinPeriodoGracia;
        // $amortizacionCapital = floatval($amortizacionCapital);
        $amortizacionCapital = $amortizacionCapital;
        // $tasaEfectivaAnual = round($tasaEfectivaAnual, 3);
        //  dd($tasaEfectivaAnual);
        $tasaNtvPorce = $tasaNtv / 100; //CONVERTIDO A PORCENTAJE
        // $tasaNtvPorce = $tasaNtvPorce; //CONVERTIDO A DECIMALES

        return [
            'fecha1' => $desembolso['fecha_inicio'],
            'fecha2' => $desembolso['fecha_fin'],
            'diaFecha1' => $diaFecha1,
            'mesFecha1' => $mesFecha1,
            'diaFecha2' => $diaFecha2,
            'mesFecha2' => $mesFecha2,
            'numAnnos' => $numAnnos,
            'numMesesSumar' => $numMesesSumar,
            'diasConvertidosEnMes' => $diasConvertidosEnMes,
            'numCuotas' => $numCuotas,
            'periodoGraciaEnMeses' => $periodoGraciaEnMeses,
            'numCuotasPeriodoGracia' => $numCuotasPeriodoGracia,
            'numCuotasSinPeriodoGracia' => $numCuotasSinPeriodoGracia,
            'spread' => $spread,
            'tasaRef' => $tasaRef,
            'tasaRefValor' => $tasaRefValor,
            'tasaNtv' => $tasaNtv,
            'numPeriodosAnno' => $numPeriodosAnno,
            'tasaPeriodica' => $tasaPeriodica,
            'tasaEfectivaAnual' => $tasaEfectivaAnual,
            'valorDesembolso' => $valorDesembolso,
            'amortizacionCapital' => $amortizacionCapital,
            'tasaNtvPorce' => $tasaNtvPorce,
            'tasaNtv' => $tasaNtv,
            'numDias' => $numDias,
            'saldoDeuda' => $saldoDeuda
        ];
    }*/

    function validateDate($date, $format = 'Y-m-d')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    private function validarFecha($anno, $mes, $dia)
    {
        return \checkdate($mes, $dia, $anno);
    }

    private function obtenerCuotasProyecciones(
        $desembolso,
        $creditoBanco,
        $esPrimerDesembolso = true,
        $cuotasPrimerDesembolso = []
    ) {

        // dd($creditoBanco);

        // $contadorCuotasDescartadas = 0;
        //  $ubicarCuotaPeriodoGracia = false;
        $numCuotas = 0;
        $numCuotasPeriodoGracia = 0;
        $saldoDeuda = 0;
        $numCuotasSinPeriodoGracia = 0;
        $numDias = $creditoBanco['num_dias']; //90 o 30

        $tieneFechaOtroDesembolso = false;
        // dd($otroDesembolso);
        // dd($esPrimerDesembolso);
        if ($esPrimerDesembolso === false) :




            $arrayVariablesOtroDesembolso = $this->obtenerVariablesOtroDesembolso(
                $desembolso,
                //  $creditoBanco,
                $cuotasPrimerDesembolso
            );

            // dd($arrayVariablesOtroDesembolso);

            // $fechaPrimeraCuota = $arrayVariablesOtroDesembolso['fechaPrimeraCuota'];
            // $contadorCuotasDescartadas = $arrayVariablesOtroDesembolso['contadorCuotasDescartadas'];
            // $ubicarCuotaPeriodoGracia = $arrayVariablesOtroDesembolso['ubicarCuotaPeriodoGracia'];
            $numCuotas = $arrayVariablesOtroDesembolso['numCuotasOtroDesembolso'];
            $numCuotasPeriodoGracia = $arrayVariablesOtroDesembolso['numCuotasPeriodoGraciaNuevo'];
            // $saldoDeuda = $desembolso['valor'];
            // $desembolso['fecha_inicio'] = $arrayVariablesOtroDesembolso['fechaPrimeraCuota'];
            $numCuotasSinPeriodoGracia = $arrayVariablesOtroDesembolso['numCuotasSinPeriodoGracia'];

            //FECHA DE LA PRIMERA CUOTA
            $fecha1 = $arrayVariablesOtroDesembolso['fechaPrimeraCuota'];

            //DIA DE PAGO DE CADA CUOTA
            $diaFecha1 = $arrayVariablesOtroDesembolso['diaPagoOtrosCuotas'];

            $tieneFechaOtroDesembolso = true;

            $diasDiferencia = $arrayVariablesOtroDesembolso['diasDiferencia'];

        else : //PRIMER DESEMBOLSO


            //DIA DE PAGO DE CADA CUOTA
            $diaFecha1 = date('d', strtotime($desembolso['fecha_inicio']));

            //$fechaInicioDesembolso = date_create($desembolso['fecha_inicio']); //10-julio-2024
            //$fechaFinDesembolso = date_create($desembolso['fecha_fin']);


            //Número de años SE DEBE OBTENER DEL CREDITO
            $fecha1 = date_create($creditoBanco['fecha_inicio']);
            $fecha2 = date_create($creditoBanco['fecha_fin']);
            $diff = date_diff($fecha1, $fecha2);

            $numAnnos = $diff->y;
            $numCuotas =  $numAnnos * 12;


            //NÚMERO CUOTAS
            $numCuotas =  $numAnnos * 12;
            $diasConvertidosEnMes = $numDias / 30;
            $numCuotas = $numCuotas / $diasConvertidosEnMes;

            //  $numCuotas =  $numAnnos * 12;
            // $diasConvertidosEnMes = $numMesesSumar = $numDias / 30;

            //PERIODO DE GRACIA
            $periodoGraciaEnMeses = intval($creditoBanco['periodo_gracia']);
            $numCuotasPeriodoGracia = $periodoGraciaEnMeses / $diasConvertidosEnMes;

            // dd($periodoGraciaEnMeses);
            //NÚMERO DE CUOTAS SIN PERIODO DE GRACIA
            $numCuotasSinPeriodoGracia = $numCuotas - $numCuotasPeriodoGracia;

            //FECHA DE INICIO DEL PRIMER DESEMBOLSO
            $fecha1 =  $desembolso['fecha_inicio'];

        //$diasCredito = $arrayVariablesOtroDesembolso['diasDiferencia'];
        //$diaFecha1 = intval($arrayVariables['diaFecha1']);
        // dd($numCuotasPeriodoGracia);
        //$diasConvertidosEnMes = $numDias / 30;
        //$numCuotas = $numCuotas / $diasConvertidosEnMes;

        //  $numCuotasSinPeriodoGracia = $numCuotas - $numCuotasPeriodoGracia;

        // $saldoDeuda = $desembolso['valor'];

        // $amortizacionCapital = $saldoDeuda  / $numCuotasSinPeriodoGracia;
        endif;

        // $arrayVariables = $this->obtenerVariables($desembolso, $creditoBanco);

        //SPREAD
        $spread = $creditoBanco['spread']; //SI OTRO DESEMBOLSO

        //TASA DE REFERENCIA
        $tasaRef = $creditoBanco['tasa_ref']; //SI OTRO DESEMBOLSO

        //VALOR DE LA TASA REFERENCIA
        $tasaRefValor = $creditoBanco['tasa_ref_valor'];

        //TASA NTV - TASA NOMINAL
        $tasaNtv = $tasaRefValor + $spread; //SI OTRO DESEMBOLSO
        $tasaNtv = $tasaNtv; //SI OTRO DESEMBOLSO

        //CONVERTIDO A PORCENTAJE
        $tasaNtvPorce = $tasaNtv / 100;

        //OBTENER EL NÚMERO DE PERIODOS DEL AÑO
        $numPeriodosAnno = $numMesesSumar =  $numDias / 30;
        $numPeriodosAnno = 12 / $numPeriodosAnno; //SI OTRO DESEMBOLSO


        //TASA PERIODICA
        $tasaPeriodica = $tasaNtv / $numPeriodosAnno;
        $tasaPeriodica = $tasaPeriodica / 100;

        //  dd($numMesesSumar);

        // $numMesesSumar= $numDias / 30;

        // dd($numCuotas);




        $saldoDeuda = $desembolso['valor'];



        //  $tasaNtvPorce = $arrayVariables['tasaNtvPorce'];
        // $numDias = $arrayVariables['numDias']; //90 o 30



        //   $numMesesSumar = $arrayVariables['numMesesSumar'];

        //$tasaRefValor = $arrayVariables['tasaRefValor'];
        /// $tasaNtv = $arrayVariables['tasaNtv'];
        // $spread = $arrayVariables['spread'];


        // $tasaPeriodica = $arrayVariables['tasaPeriodica'];
        // $tasaEfectivaAnual = $arrayVariables['tasaEfectivaAnual'];
        //TASA EFECTIVA ANUAL
        $tasaEfectivaAnual = $this->obtenerTasaEfectivaAnual(
            $tasaPeriodica,
            $numDias
        );

        // dd($numDias);


        //dd($fecha1);
        // dd($numCuotasSinPeriodoGracia);
        //$tasaRef = $arrayVariables['tasaRef'];
        //dd($numCuotas);
        //PRIMER DESEMBOLSO
        /* if (!$otroDesembolso) :
            $numCuotasPeriodoGracia = $arrayVariables['numCuotasPeriodoGracia'];
            $numCuotasSinPeriodoGracia = $arrayVariables['numCuotasSinPeriodoGracia'];
            $numCuotas = $arrayVariables['numCuotas']; //NÚMERO CUOTAS TOTAL
        endif; */

        // dd($numCuotasSinPeriodoGracia);
        //INTERES PROYECTADO PARA LAS CUOTAS DE PERIODO DE GRACIA


        //$amortizacionCapital =
        // dd($interesesProyectado);
        //ARREGLO PARA ALMACENAS LOS MESES GENERADOS
        $mesGenerados = [];


        /*MES DE LA CUOTA ANTERIOR PARA APLICAR EL SIGUIENTE CALCULO
         $mesPiloto = ES IGUAL AL MES DE INICIO, ejemplo: 11
        MES_INICIO = ES EL MES DE CADA CUOTA
        MES_INICIO = 11 + 3 = 14 - 12 = 2 => 2
        MES_INICIO = 2 + 3 = 5 - 12 = -7 => 5
        MES_INICIO = 5 + 3 = 8 - 12 = -4 => 8
        MES_INICIO = 8 + 3 = 11 - 12 = -1 => 11
        */
        //TENER EN CUENTA QUE YA OCUPO LA POSICIÓN 0
        $mesPiloto =  $mesGenerados[0] = intval(date('m', strtotime($fecha1))); //10 julio 2021

        //CONTADOR DE MESES
        $meses = 0;

        $cuotas = [];
        $proyecciones = [];
        $ultimoIndexCuotaGracia = 0;

        $amortizacionCapital = $saldoDeuda  / $numCuotasSinPeriodoGracia;

        //dd($numCuotasSinPeriodoGracia);
        //dd($numMesesSumar);
        for ($i = 0; $i < $numCuotas; $i++) {


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

            // dd($tieneFechaOtroDesembolso);

            if ($tieneFechaOtroDesembolso === true) :

                $interesesProyectado = $this->obtenerInteresProyectado(
                    $saldoDeuda,
                    $tasaNtvPorce,
                    $diasDiferencia
                );

            //  dd($diasDiferencia);
            //TASA EFECTIVA ANUAL
            /*$tasaEfectivaAnualCuota1 = $this->obtenerTasaEfectivaAnual(
                        $tasaPeriodica,
                        $diasDiferencia
                    ); */

            else :
                $interesesProyectado = $this->obtenerInteresProyectado(
                    $saldoDeuda,
                    $tasaNtvPorce,
                    $numDias
                );

            // dd($numDias);
            endif;

            // dd($interesesProyectado);
            //CUOTAS PERIODO GRACIA
            if ($i < $numCuotasPeriodoGracia) { //



                //dd($numDias);
                $totalSerDeuda  = $interesesProyectado + 0;

                $cuotas[$i] = [
                    'id_cuota' => $randomId,
                    'numero' => $i + 1,
                    'fecha' => $fechaFinal,
                    'amort_capital' => 0,
                    'amort_capital_round' => '$' . number_format(0, 3, ".", "."),
                    'interes_pagado' => 0,
                    'concepto' => 'Intereses',
                    'total_serv_deuda' => $this->fijarFormatoDinero($totalSerDeuda),
                    'interes_proyectado' =>  $this->fijarFormatoDinero($interesesProyectado),
                    'saldo_deuda' => $saldoDeuda,
                    'saldo_deuda_round' => $this->fijarFormatoDinero($saldoDeuda),
                    'cambio_valores' => false,
                    'es_periodo_gracia' => true
                ];

                //  dd( $cuotas[$i]);

                $ultimoIndexCuotaGracia = $i;
            } else { //CUOTAS SIN PERIODO GRACIA

                if ($i == ($ultimoIndexCuotaGracia + 1)) { // 7
                    $saldoDeuda = $cuotas[$ultimoIndexCuotaGracia]['saldo_deuda'];
                    // $saldoDeuda = round($cuotas[$ultimoIndexCuotaGracia]['saldo_deuda'],3,PHP_ROUND_HALF_DOWN);
                } elseif ($i > ($ultimoIndexCuotaGracia + 1)) { // 9 > 8
                    $saldoDeuda =  $cuotas[$i - 1]['saldo_deuda']; //8
                }


                $interesesProyectado =  0;


                if ($tieneFechaOtroDesembolso === true) :

                    $interesesProyectado = $this->obtenerInteresProyectado(
                        $saldoDeuda,
                        $tasaNtvPorce,
                        $diasDiferencia
                    );

                //TASA EFECTIVA ANUAL
                /* $tasaEfectivaAnualCuota1 = $this->obtenerTasaEfectivaAnual(
                        $tasaPeriodica,
                        $diasDiferencia
                    );*/


                else :
                    $interesesProyectado = $this->obtenerInteresProyectado(
                        $saldoDeuda,
                        $tasaNtvPorce,
                        $numDias
                    );
                endif;


                $totalSerDeuda  = $interesesProyectado + $amortizacionCapital;

                $saldoDeuda = $saldoDeuda - $amortizacionCapital;

                $cuotas[$i] = [
                    'id_cuota' => $randomId,
                    'numero' => $i + 1,
                    'fecha' => $fechaFinal,
                    'concepto' => 'Capital + intereses',
                    'amort_capital' => $amortizacionCapital,
                    'amort_capital_round' =>  $this->fijarFormatoDinero($amortizacionCapital),
                    'interes_pagado' => 0,
                    'total_serv_deuda' => $this->fijarFormatoDinero($totalSerDeuda),
                    'interes_proyectado' =>  $this->fijarFormatoDinero($totalSerDeuda),
                    'saldo_deuda' => $saldoDeuda,
                    //PÁGINA https://www.w3schools.com/php/phptryit.asp?filename=tryphp_func_string_number_format
                    'saldo_deuda_round' => $this->fijarFormatoDinero($saldoDeuda),
                    'cambio_valores' => false,
                    'es_periodo_gracia' => false
                ];
            }

            $numDiasFinal = 0;
            if ($tieneFechaOtroDesembolso) :
                $numDiasFinal = $diasDiferencia;
            else :
                $numDiasFinal = $numDias;
            endif;


            // $tasaEfectivaAnual;


            $proyecciones[] = [
                'id_cuota' => $randomId,
                'numero' => $i + 1,
                'tasa_ref_valor' => $tasaRefValor,
                'tasa_nominal' => $tasaNtv . '%',
                'tasa_nominal_decimal' => $tasaNtvPorce,
                'spread' => $spread,
                'tasa_periodica' => $tasaPeriodica,
                'num_dias' =>  $numDiasFinal,
                'tasa_efectiva_anual' => round(($tasaEfectivaAnual * 100), 3) . '%',
                'tasa_efectiva_anual_decimal' => $tasaEfectivaAnual,
                'anno' => date('Y', strtotime($fecha)),
                'valor' =>  $this->fijarFormatoDinero($interesesProyectado),
                'cambio_valores' => false
            ];

            $tieneFechaOtroDesembolso = false;
        }


        //dd($cuotas);
        return [
            'cuotas' => $cuotas,
            'proyecciones' => $proyecciones
        ];
    }
    function cutAfterDot($number, $afterDot = 2)
    {
        $a = $number * pow(10, $afterDot);
        $b = floor($a);
        $c = pow(10, $afterDot);
        // echo "a $a, b $b, c $c<br/>";
        return $b / $c;
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
        //  $numCuotasPeriodoGracia = 0;

        $cuotasPrimerDesembolso = json_decode($primerDesembolso['cuotas_json'], true);
        $fechaInicioPrimerDeselbolso = $primerDesembolso['fecha_inicio'];

        // dd($cuotasPrimerDesembolso);
        $numCuotasPrimerDesembolso = count($cuotasPrimerDesembolso);

        /*foreach ($cuotasPrimerDesembolso as $key => $value) :

            //FILTRAR LA FECHA DE CADA CUOTA Y SABER SI ES PERIODO DE GRACIA
            $fechasPrimerDesembolso[] = [
                'fecha' => $value['fecha'],
                'es_periodo_gracia' => $value['es_periodo_gracia'],
            ];

            //CONTAR EL NÚMERO DE CUOTAS DE PERIODO DE GRACIA
            if ($value['es_periodo_gracia']) :
                $numCuotasPeriodoGracia++; //12
            endif;

        endforeach; */

        //FECHA INICIO OTRO DESEMBOLSO
        $fechaInicioOriginalOtroDesembolso = $desembolso['fecha_inicio'];
        //FECHA DE INICIO DEL OTRO DESEMBOLSO
        $fechaDesembolsoActual = strtotime($desembolso['fecha_inicio']);
        //OBTENER LA FECHA DE PARA DEL DESEMBOLSO 1
        $fechaCuotaParadaDesembolso1 = null;
        //CONTAR LAS CUOTAS DESCARTADAS
        $contadorCuotasDescartadas = 0;
        $contadorCuotasDescartadasPerGracia = 0;
        //PARA DETERMINAR SI LA CUOTA DE PARADA ES DE GRACIA O SOLO INTERES MAS CAPITAL
        // $ubicarCuotaPeriodoGracia = false;

        //   $fechaCuotaParadaDesembolso1 = $value['fecha'];
        /* $x = date('Y-m-d',$fechaDesembolsoActual);
        dd($x); */

        //12
        $fechaParada = null;
        //  $numCuotasPeriodoGraciaOriginal = $numCuotasPeriodoGracia;

        //  $fechasGeneradasPrimerDesembolso = [];

        //  $indiceFechaParada = 0;
        $contadorCuotasPerGracia = 0;
        //  $fechaParadaEncontrada = false;


        //CICLO PARA ENCONTRAR LA FECHA DE PARADA

        $indexCuotasEncontradas = 0;
        $indexCuotas = 0;
        // dd($fechaInicioOriginalOtroDesembolso);

        //dd($cuotasPrimerDesembolso[0]['fecha']);
        $fechaCuotaParadaDesembolso1 = $cuotasPrimerDesembolso[0]['fecha'];
        foreach ($cuotasPrimerDesembolso as $index => $value) :

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
                if ($indexCuotas < $numCuotasPrimerDesembolso) :
                    //  dd($indexCuotas);
                    //$fechaCuotaParadaDesembolso1 =  $cuotasPrimerDesembolso[$indexCuotas + 1]['fecha'];
                    $fechaCuotaParadaDesembolso1 = $this->obtenerFechaParada(
                        $cuotasPrimerDesembolso,
                        $indexCuotas
                    );
                /* try {
                        $fechaCuotaParadaDesembolso1 =  $cuotasPrimerDesembolso[$indexCuotas + 1]['fecha'];
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
                    $numCuotasPeriodoGracia--;
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
                $numCuotasPeriodoGracia = $numCuotasPeriodoGracia - $contadorCuotasDescartadas;
            endif;

            //  dd($fechaCuotaParadaDesembolso1);
        } */
        // $numCuotasPeriodoGracia = 6;



        //DIA DE PAGO OTRAS CUOTAS
        $diaPagoOtrosCuotas = date('d', strtotime($fechaCuotaParadaDesembolso1));
        // $fechaInicioOtroDesembolso =
        $fecha1 = date_create($fechaCuotaParadaDesembolso1); //FECHA PARADA PRIMER DESEMBOLSO
        $fecha2 = date_create($fechaInicioOriginalOtroDesembolso); //FECHA INICIO OTRO DESEMBOLSO

        $diff = date_diff($fecha1, $fecha2);

        //Número de días de diferencia
        // $numDiasDiferencia = $diff->days + 1;
        $numDiasDiferencia = $diff->days;

        $numCuotasOtroDesembolso = $numCuotasPrimerDesembolso - $contadorCuotasDescartadas; //38
        // $numCuotasPeriodoGracia = $numCuotasPeriodoGracia - $contadorCuotasDescartadasPerGracia;
        $contadorCuotasPerGracia = $contadorCuotasPerGracia - $contadorCuotasDescartadasPerGracia;
        // dd($numCuotasOtroDesembolso);
        $numCuotasSinPeriodoGracia = $numCuotasOtroDesembolso - $contadorCuotasPerGracia;


        // dd($contadorCuotasPerGracia);
        $data = [
            'fechaInicioOriginalOtroDesembolso' => $fechaInicioOriginalOtroDesembolso,
            //DIFERENCIA DE DÍAS DE PAGO PRIMERA CUOTA OTRO DESEMBOLSO Y FECHA OTRO DESEMBOLSO
            'diasDiferencia' =>  $numDiasDiferencia,
            'fechaPrimeraCuota' => $fechaCuotaParadaDesembolso1,
            'diaPagoOtrosCuotas' => $diaPagoOtrosCuotas,
            'numCuotasDescartadas' => $contadorCuotasDescartadas,
            'numCuotasPerGraciaDescartadas' => $contadorCuotasDescartadasPerGracia,
            // 'ubicarCuotaPeriodoGracia' => $ubicarCuotaPeriodoGracia,
            'numCuotasPrimerDesembolso' => $numCuotasPrimerDesembolso,
            'numCuotasOtroDesembolso' => $numCuotasOtroDesembolso,
            'numCuotasPeriodoGraciaNuevo' => $contadorCuotasPerGracia,
            'fechaInicioPrimerDesembolso' => $fechaInicioPrimerDeselbolso,
            'numCuotasSinPeriodoGracia' =>  $numCuotasSinPeriodoGracia
        ];

        return $data;
        //dd($data);
    }

    private function fijarFormatoDinero(
        $valor,
        $decimales = 2,
        $separador1 = ',',
        $separador2 = '.'
    ) {

        return '$' . number_format($valor, $decimales, "$separador1", "$separador2");
    }

    private function obtenerFechaParada($cuotasPrimerDesembolso, $indexCuotas)
    {
        $fechaCuotaParadaDesembolso1 = null;

        try {
            $fechaCuotaParadaDesembolso1 =  $cuotasPrimerDesembolso[$indexCuotas + 1]['fecha'];
        } catch (\Exception $e) {
            $fechaCuotaParadaDesembolso1 = null;
        }
        return  $fechaCuotaParadaDesembolso1;
    }
    /*

$fecha1 = date_create($requestData['fecha_inicio']);
$fecha2 = date_create($requestData['fecha_fin']);
$diff = date_diff($fecha1, $fecha2);

//Número de días
$numDias = $requestData['num_dias'];
//Número de años
$numAnnos = $diff->y;
//Número de cuotas
$numCuotas = $numAnnos * 365 / $numDias;
$numCuotas = intval($numCuotas);
//SPREAD
$spread = $requestData['spread'];
//TASA DE REFERENCIA
$tasaRef = $requestData['tasa_ref'];
//VALOR DE LA TASA REFERENCIA
$tasaRefValor = $requestData['tasa_ref_valor'];
//TASA NTV
$tasaNtv = $tasaRefValor + $spread;

//dd($tasaNtv);
//TASA EFECTIVA ANUAL
$base = ((1 + $tasaNtv) * ($numDias / 360));
$exp = (365 / $numDias) - 1;
$tasaEfectivaAnual = pow($base, $exp);

// dd($tasaEfectivaAnual);

$cuotas = [];
$proyecciones = [];
$dias = 0;
for ($i = 0; $i <= $numCuotas; $i++) {
$dias = $dias + 90;
$fecha = date('Y-m-d', strtotime($requestData['fecha_inicio'] . ' + ' . $dias . ' days'));
$cuotas[] = ['numCuota' => $i, 'fecha' => $fecha];

$proyecciones[] = [
'spred' => $spread,
'tasa_ref' => $tasaRef,
'tasa_ref_valor' => $tasaRefValor,
'dias' => $numDias,
'tasa_nominal' => $tasaNtv,
'tasa_efectiva_anual' => $tasaEfectivaAnual,
'anno' => date('Y', strtotime($fecha))
];
}

dd($proyecciones);

 */
}
