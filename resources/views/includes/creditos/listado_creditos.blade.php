@extends('layouts.default') @section('content')
<style>
     [v-cloak] {
        display: none;
    }
   /* .vdp-datepicker__calendar {
        position: fixed;
    }

    [v-cloak] {
        display: none;
    } */
</style>
<div id="vueapp" v-cloak>
    <div class="card">
        <div class="card-header">
            <b><?= $titulo ?></b>
        </div>
        <div class="card-body">
            <div class="text-center" v-if="!formCargado">
                <div class="spinner-border " style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>


            @include('elements.alertMsg')
            @include('elements.alertMsgJs')

            <div v-if="formCargado">
                <div class="form-row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <form method="post" action="{{ URL::route('creditos_simulados') }}">
                                @csrf
                                <div class="input-group">
                                    <input type="text" autocomplete="off" name="txtFiltro" maxlength="20" class="form-control form-control-sm" placeholder="Ordenanza o ID">
                                    <div class="input-group-append">
                                        <button class="btn btn-secondary btn-sm" type="submit">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="form-row">

                    <div class="col-12">
                        <div class="form-group">

                            <table class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                       <!-- <th scope="col">ID</th> -->
                                        <th scope="col"># Ordenanza</th>
                                        <th scope="col">Valor crédito</th>
                                        <th scope="col">Total desembolsado</th>
                                        <th scope="col">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $index = 0;
                                    $col = 2;
                                    foreach ($creditos as $credito) :
                                        $detalle = 'detalle' . $index;
                                    ?>

                                        <tr>

                                            <td>
                                                <?= $credito->num_ordenanza ?>
                                            </td>


                                            <td>
                                                {{ <?= $credito->valor ?> | currency }}
                                            </td>
                                            <td>
                                                {{
                                                <?= $credito->valor_prestado  ?> | currency }}
                                            </td>

                                            <td>
                                                <button data-toggle="collapse" data-target="<?= '#' . $detalle ?>" class="btn btn-outline-secondary btn-sm">
                                                    <span class="fa fa-eye"></span>
                                                </button>

                                                @can('check-authorization', PERMISO_CREAR_CREDITO_BANCARIO_SIMULADO)
                                                <button @click='modalFormCreditoBanco_click(<?= $credito ?>)' class="btn btn-outline-secondary btn-sm">
                                                    <span class="fas fa-comment-dollar "></span>
                                                </button>
                                                @endcan

                                                @can('check-authorization', PERMISO_VER_LISTADO_CREDITOS_BANCARIOS_SIMULADOS)
                                                <a href="#" class="btn btn-outline-secondary btn-sm" @click='mostrarModalCreditosBancos(<?= $credito ?>)'>
                                                    <i class="fa fa-list-ol" aria-hidden="true"></i>
                                                </a>
                                                @endcan
                                            </td>
                                        </tr>

                                        <tr class="collapse" id="<?= $detalle ?>">
                                            <td colspan="5">
                                                @include('includes.fila_detalle_general', ['datos' => $filasDetalle[$index], 'col' => $col])
                                                <hr />
                                                <div style="margin-top: 4px;">
                                                    @can('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO)
                                                    <a href="{{ URL::to('creditos/edit/'.$credito->id) }}" data-toggle="tooltip" title="Editar crédito" class="btn btn-outline-secondary btn-sm">
                                                        <i class="far fa-edit"></i>
                                                    </a>
                                                    @endcan

                                                    @can('check-authorization', PERMISO_BORRAR_CREDITO_SIMULADO)
                                                    <button @click="borrarCredito_click(<?= $credito->id ?>)" data-toggle="tooltip" title="Borrar crédito" class="btn btn-outline-secondary btn-sm">
                                                        <i class="far fa-trash-alt"></i>
                                                    </button>
                                                    @endcan

                                                    &nbsp;
                                                    <span v-if="spinners.borrarCredito" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                                </div>
                                            </td>
                                        </tr>


                                    <?php $index++;
                                    endforeach;
                                    ?>

                                </tbody>

                            </table>

                            {{ $creditos->links('pagination::bootstrap-4') }}

                        </div>

                    </div>
                </div>

                <hr />
                <div class="form-row">
                    <div class="col-8">
                        <div class="form-group">
                            @if($tipoCredito === 2 && Gate::check('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO))
                            <a href="{{ URL::to('creditos/add') }}" class="btn btn-success btn-sm">Nuevo crédito</a>
                            @endif
                            <a href="{{ URL::to('creditos/listadoCreditosSimulados') }}" class="btn btn-secondary btn-sm">Refrescar</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>




    </div>

    <!-- MODAL AGREGAR CREDITO BANCARIO -->
    @include('includes.creditos.modal_form_credito_bancario')

    <!-- MODAL FORM DESEMBOLSOS  -->
    @include('includes.creditos.modal_form_desembolso')

    <!-- MODAL LISTADO DE CREDITOS BANCARIOS -->
    @include('includes.creditos.modal_listado_creditos_bancarios')



</div>

<script type="application/javascript">
    const vueApp = new Vue({
        el: '#vueapp',
        // mixins: [],
        mixins: [myMixin, Vue2Filters.mixin],
        data: {
            formCargado: false,
            listados: {
                bancos: [],
                creditoBancos: []
            },

            datosDesembolso: {
                banco_id: 0,
                credito_banco_id: 0,
                credito_id: 0,
                valor: 0,
                descripcion: '',
                spread: '',
                fecha_inicio: '',
                fecha_fin: '',
                periodo_gracia: '',
                tasa_ref: '',
                tasa_ref_valor: 0,
                num_dias: 0,
                num_annos: 0,
                notas: '',
                es_independiente: false,
               // credito_id: 0,
                separar_interes_capital: false,
                truncar_tasa_ea: false
            },

            creditoBanco: {
                banco_id: 0,
                valor: 0,
                descripcion: '',
                spread: '',
                fecha_inicio: '',
                fecha_fin: '',
                periodo_gracia: '',
                tasa_ref: '',
                tasa_ref_valor: 0,
                num_dias: 0,
                num_annos: 0,
                notas: '',
                credito_id: 0
            },

           /* datosDesembolso: {
                credito_banco_id: 0,
                valor: 0,
                fecha_inicio: '',
                fecha_fin: '',
                descripcion: '',
                tipo_datosDesembolso: 0,
                es_independiente: false,
                es_primer_datosDesembolso: false,
                num_annos: '',
                separar_interes_capital: false,
                truncar_tasa_ea: false
            }, */

            modales: {
                tituloCreditoBanco: 'Crear crédito bancario',
                editarCreditoBanco: false,
            },
            mostrarBtnEditarBorrar: false, //MODAL FORM DESEMBOLSOS
            editarDesembolso: false,
            spinners: {
                guardarCredito: false,
                guardaDesembolso: false,
                editarCredito: false,
                cargarCreditosBancos: false,
                borrarCredito: false
            },
            fechaFinDesembolso: '',
            creditoBancoActual: null,
            creditoBancoActual: null,
            tipoCredito: <?= "'" . $tipoCredito . "'"  ?>,

        },
        components: {
            vuejsDatepicker
        },

        methods: {

            limpiarFormCreditoBancario() {
                this.limpiarMsg();
                this.creditoBanco.banco_id = 0;
                this.creditoBanco.valor = 0;
                this.creditoBanco.descripcion = '';
                this.creditoBanco.spread = '';
                this.creditoBanco.fecha_inicio = '';
                // this.creditoBanco.fecha_fin = '';
                this.creditoBanco.periodo_gracia = '';
                this.creditoBanco.tasa_ref = '';
                this.creditoBanco.tasa_ref_valor = 0;
                this.creditoBanco.num_dias = 0;
                this.creditoBanco.notas = '';
                //  this.creditoBanco.credito_id = 0;
                this.creditoBanco.num_annos = '';
            },
           /*limpiarFormDesembolsoIndependiente() {
                this.limpiarMsg();
                this.datosDesembolso.banco_id = 0;
                this.datosDesembolso.valor = 0;
                this.datosDesembolso.descripcion = '';
                this.datosDesembolso.spread = '';
                this.datosDesembolso.fecha_inicio = '';
                // this.creditoBanco.fecha_fin = '';
                this.datosDesembolso.periodo_gracia = '';
                this.datosDesembolso.tasa_ref = '';
                this.datosDesembolso.tasa_ref_valor = 0;
                this.datosDesembolso.num_dias = 0;
                this.datosDesembolso.notas = '';
                //  this.creditoBanco.credito_id = 0;
                this.datosDesembolso.num_annos = '';
            }, */

            btnVerDesembolsos_click(item) {
                const controlador = "creditos/ajaxObtenerNumDesembolsos/" + item.id;
                this.cargarDatos(controlador, {}).
                then(response => {
                    const data = response.data;
                  /*  console.log({
                        daa: data
                    }); */
                    if (Number(data.num_desembolsos) > 0) {
                        this.redirectUrl('creditos/desembolsos/' + item.id);
                    } else {
                        this.showMsgBoxOk('El crédito no tiene desembolsos registrados');
                    }
                }).catch(error => {
                    console.log(error);
                });
                return false;
            },


            cargarDatosCreditoBancario(crédito) {
                this.creditoBanco.banco_id = crédito.banco_id;
                this.creditoBanco.valor = crédito.valor;
                this.creditoBanco.descripcion = crédito.descripcion;
                this.creditoBanco.spread = crédito.spread;
                this.creditoBanco.fecha_inicio = moment(crédito.fecha_inicio).format('L');
                // this.creditoBanco.fecha_fin = '';
                this.creditoBanco.periodo_gracia = crédito.periodo_gracia;
                this.creditoBanco.tasa_ref = crédito.tasa_ref;
                this.creditoBanco.tasa_ref_valor = crédito.tasa_ref_valor;
                this.creditoBanco.num_dias = crédito.num_dias;
                this.creditoBanco.notas = crédito.notas;
                this.creditoBanco.credito_id = crédito.credito_id;
                this.creditoBanco.num_annos = crédito.num_annos;
            },



            gotoUrl(item) {
               /* console.log({
                    item: item
                }); */
                // this.redirectUrl
            },

          /*  verModalFormularioDesembolso(creditoBanco) {

                this.errors = {};
                const controlador = "creditos/ajaxVerificarDesembolso/" + creditoBanco.id;

                this.limpiarFormDesembolso();
                this.cargarDatos(controlador, {}).
                then(response => {
                    console.log(response);
                    const data = response.data;
                    const creditoBanco = data.credito_banco;
                    // this.datosDesembolso.descripcion = 'Desembolso ' + data.num_datosDesembolsos;
                    this.datosDesembolso.credito_banco_id = creditoBanco.id;
                    this.datosDesembolso.num_annos = creditoBanco.num_annos;
                    this.datosDesembolso.fecha_fin = this.fechaFinDesembolso = moment(data.fecha_fin).format('L');
                    this.datosDesembolso.es_primer_datosDesembolso = data.es_primer_datosDesembolso;
                    this.creditoBancoActual = creditoBanco;
                    this.$bvModal.show('modalFormularioDesembolso');

                }).catch(error => {
                    console.log({error: error });
                });

            }, */

            //************************************** EVENTOS DESEMBOLSO

            verModalFormularioDesembolso_click(creditoBanco) {
               // console.log({creditoBanco: creditoBanco });
               /* this.errors = {};
                this.$bvModal.show('modalFormDatosDesembolso');
                this.datosDesembolso.es_independiente = false;
                this.datosDesembolso.banco_id = creditoBanco.banco_id
                this.datosDesembolso.credito_banco_id = creditoBanco.id */


                this.errors = {};
                const controlador = "creditos/ajaxVerificarDesembolso/" + creditoBanco.id;

                this.limpiarFormDesembolso();
                this.cargarDatos(controlador, {}).
                then(response => {
                    //console.log(response);
                    const data = response.data;
                    const creditoBanco = data.credito_banco;
                    // this.datosDesembolso.descripcion = 'Desembolso ' + data.num_datosDesembolsos;
                    this.datosDesembolso.banco_id = creditoBanco.banco_id;
                    this.datosDesembolso.credito_banco_id = creditoBanco.id;
                    this.datosDesembolso.credito_id = creditoBanco.credito_id;
                    this.datosDesembolso.num_annos = creditoBanco.num_annos;
                    this.datosDesembolso.es_primer_desembolso = data.es_primer_desembolso;
                    /*
                        SI ES EL PRIMER DESEMBOLSO Y NO ES INDEPENDIENTE,
                        ASIGNAR COMO FECHA FINAL, LA FECHA DEL PRIMER DESEMBOLSO
                    */

                    if(
                        !this.datosDesembolso.es_primer_desembolso  &&
                        this.datosDesembolso.es_independiente == false
                    ) {
                            this.datosDesembolso.fecha_fin =
                            this.fechaFinDesembolso = moment(data.fecha_fin).format('L');
                    }
                   //
                   // this.datosDesembolso.es_independiente = data.es_independiente;
                    this.datosDesembolso.spread = creditoBanco.spread;
                    this.datosDesembolso.periodo_gracia = creditoBanco.periodo_gracia;
                    this.datosDesembolso.num_dias = creditoBanco.num_dias;
                    this.datosDesembolso.tasa_ref = creditoBanco.tasa_ref;
                    this.datosDesembolso.tasa_ref_valor = creditoBanco.tasa_ref_valor;

                    this.creditoBancoActual = creditoBanco;
                    this.$bvModal.show('modalFormDatosDesembolso');

                }).catch(error => {
                    console.log({error: error });
                });

                return false;

            },
                //EVENTO PARA GESTIÓNAR LA FECHA SELECCIONADA
            seleccionarFechaInicial(fechaSeleccionada) {
                // const tempFecha = fecha;
              //  console.log({fechaSeleccionada: fechaSeleccionada });
                if (this.datosDesembolso.es_primer_desembolso || this.datosDesembolso.es_independiente) {
                    const tempFecha = _.cloneDeep(fechaSeleccionada);
                   // console.log({numAnnos: this.datosDesembolso.num_annos });
                    const fechaFinal = this.agregarAnnos(tempFecha, Number(this.datosDesembolso.num_annos));
                    this.datosDesembolso.fecha_fin = fechaFinal;
                   /* console.log({
                        fechaFinal: fechaFinal
                    }); */
                }
            },


            //EVENTO PARA GESTIÓNAR EL CAMBIO DE NÚMERO DE AÑOS
            cambiarNumAnnos() {
                if (this.datosDesembolso.es_primer_desembolso || this.datosDesembolso.es_independiente) {
                    const numAnnos = Number(this.datosDesembolso.num_annos);
                    const fechaInicio = _.cloneDeep(this.datosDesembolso.fecha_inicio);

                    if(fechaInicio && numAnnos > 0) {
                        const fechaFinal = this.agregarAnnos(fechaInicio, numAnnos);
                        this.datosDesembolso.fecha_fin = fechaFinal;
                    }
                }
            },

            agregarAnnos(dt, n) {
                dt = new Date(dt);
                return new Date(dt.setFullYear(dt.getFullYear() + n));
            },

            verificarEsIndependiente_click(e) {
                this.limpiarFormDesembolso();
                //console.log({verificarEsIndependiente: 'verificarEsIndependiente' });
                this.datosDesembolso.es_independiente = e.target.checked;
                if (this.datosDesembolso.es_independiente === false) {
                    this.datosDesembolso.fecha_fin = this.fechaFinDesembolso;
                } else {
                    this.datosDesembolso.fecha_fin = '';
                }
                this.datosDesembolso.fecha_inicio = '';
            },

            limpiarFormDesembolso() {
                //credito_banco_id: 0,
                this.limpiarMsg();
                this.datosDesembolso.valor = 0;
                this.datosDesembolso.fecha_inicio = '';
                //this.datosDesembolso.fecha_fin = '';

               // console.log({entroAqui: this.datosDesembolso.fecha_fin });
                // fecha_fin: '',
                this.datosDesembolso.descripcion = '';
                this.datosDesembolso.tipo_desembolso = 0; //SIMULADO
                this.datosDesembolso.es_independiente = false;
                this.esIndependiente = false;
                this.datosDesembolso.truncar_tasa_ea = false;
                this.datosDesembolso.separar_interes_capital = false;

            },

            guardarDesembolso_click() {
                const ruta = 'creditos/ajaxGuardarDesembolsoSimulado'
                // this.datosDesembolso.fecha_inicio = moment(this.datosDesembolso.fecha_inicio).format(moment.HTML5_FMT.DATE);
                const datos = this.datosDesembolso;
                this.spinners.guardaDesembolso = true;
                this.enviarDatos(datos, ruta)
                    .then(response => {
                        const data = response.data;
                        if (data.success) {
                            this.limpiarFormDesembolso();
                            this.refrescarListadoCreditosBancosPorCreditoId(this.creditoBancoActual.credito_id);
                         //   this.limpiarFormDesembolsoIndependiente();
                        }
                        this.errors = {};
                        this.spinners.guardaDesembolso = false;
                        this.showMsgBoxOk(data.message);
                        this.$bvModal.hide('modalFormularioDesembolso');
                    }).catch(error => {
                        console.log(error);
                        this.spinners.guardaDesembolso = false;
                        let errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showMsgBoxOk(errorMsg, 'Error');
                    });

                    return false;
            },

            //************************************** FIN EVENTOS DESEMBOLSO

                //this.datosDesembolso.separar_interes_capital = false;
                //this.datosDesembolso. truncar_tasa_ea = false;
                //this.datosDesembolso.credito_banco_id
               /* const controlador = "creditos/ajaxVerificarDesembolso/" + creditoBanco.id;

                this.limpiarFormDesembolso();
                this.cargarDatos(controlador, {}).
                then(response => {
                    console.log(response);
                    const data = response.data;
                    const creditoBanco = data.credito_banco;
                    // this.datosDesembolso.descripcion = 'Desembolso ' + data.num_datosDesembolsos;
                    this.datosDesembolso.credito_banco_id = creditoBanco.id;
                    this.datosDesembolso.num_annos = creditoBanco.num_annos;
                    this.datosDesembolso.fecha_fin = this.fechaFinDesembolso = moment(data.fecha_fin).format('L');
                    this.datosDesembolso.es_primer_datosDesembolso = data.es_primer_datosDesembolso;
                    this.creditoBancoActual = creditoBanco;
                    this.$bvModal.show('modalFormularioDesembolso');

                }).catch(error => {
                    console.log({error: error });
                }); */




          /*  guardarDesembolsoIndependiente_click() {
                const ruta = 'creditos/ajaxGuardarDesembolsoSimulado'
                // this.datosDesembolso.fecha_inicio = moment(this.datosDesembolso.fecha_inicio).format(moment.HTML5_FMT.DATE);
                const datos = this.datosDesembolso;
                this.spinners.guardaDesembolso = true;
                this.enviarDatos(datos, ruta)
                    .then(response => {
                        const data = response.data;
                        if (data.success) {
                          //  this.limpiarFormDesembolso();
                           this.refrescarListadoCreditosBancosPorCreditoId();
                           this.limpiarFormDesembolsoIndependiente();
                        }
                        this.errors = {};
                        this.spinners.guardaDesembolso = false;
                        this.showMsgBoxOk(data.message);
                        this.$bvModal.hide('modalFormDesembolsoIndependiente');
                    }).catch(error => {
                        console.log(error);
                        this.spinners.guardaDesembolso = false;
                        let errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showMsgBoxOk(errorMsg, 'Error');
                    });
            }, */


            mostrarModalCreditosBancos(credito) {
                this.creditoBancoActual = credito;
                this.cargarCreditosBancosPorCreditoId(credito.id);
                this.$bvModal.show('modalCreditosBancarios');
            },


            modalVerDetalleCreditoBanco_click(credito) {
                this.creditoBancoActual = credito;
                this.limpiarFormCreditoBancario();
                this.cargarDatosCreditoBancario(credito);
                this.modales.tituloCreditoBanco = 'Editar crédito';
                this.modales.editarCreditoBanco = true;
                this.$bvModal.show('modalFormCreditoBanco');
            },

            modalFormCreditoBanco_click(credito) {
                this.creditoBancoActual = credito;
                this.limpiarFormCreditoBancario();
                this.creditoBanco.credito_id = credito.id;
                this.listados.creditosBancos = [];
                this.modales.editarCreditoBanco = false;
                this.errors = {};

                // this.cargarDatosCreditoBancario(crédito);
                // this.ajaxCargarRegistros('creditoBancos', 'creditoBancos', true, crédito.id, 'credito_id');
                // this.cargarCreditosBancosPorCreditoId(crédito.id, 'credito_id');
                this.$bvModal.show('modalFormCreditoBanco');
            },

            increment: function(valor) {
                vueApp.creditoBancoActual.valor_prestado = valor;
                this.$emit('increment')
            },

            refrescarListadoCreditosBancosPorCreditoId(creditoId) {
                const controlador = "creditosbancos/ajaxCargarCreditosBancosPorCreditoId/"+creditoId;

                // this.spinners.cargarCreditosBancos = true;
                this.cargarDatos(controlador, {})
                    .then(response => {
                        const data = response.data;
                        this.listados.creditoBancos = data.listado;
                        // this.spinners.cargarCreditosBancos = false;
                    }).catch(error => {
                        //  this.spinners.cargarCreditosBancos = false;
                        console.log({
                            error: error
                        });
                    });

                    return false;
            },
            cargarCreditosBancosPorCreditoId(creditoId) {
                const controlador = "creditosbancos/ajaxCargarCreditosBancosPorCreditoId/"+creditoId;

                this.spinners.cargarCreditosBancos = true;
                this.cargarDatos(controlador, {})
                    .then(response => {
                        const data = response.data;
                        this.listados.creditoBancos = data.listado;
                        this.spinners.cargarCreditosBancos = false;
                    }).catch(error => {
                        this.spinners.cargarCreditosBancos = false;
                        console.log({
                            error: error
                        });
                    });

                    return false;
            },
            borrarDesembolsosCreditoBanco(creditoBancoId) {
                const controlador = 'creditos/ajaxBorrarDesembolsosCreditoBanco/' + creditoBancoId;
                //this.spinners.borrarCreditoBanco = true;

                this.cargarDatos(controlador, {}, 'DELETE')
                    .then(response => {
                        const data = response.data;

                        if (data.success == true) {
                            this.refrescarListadoCreditosBancosPorCreditoId(this.creditoBancoActual.credito_id);
                        }
                        /* if (!datosDesembolsos) {
                             const listado = this.listados.creditoBancos;
                             this.listados.creditoBancos = listado.filter(function(obj) {
                                 return obj.id != data.credito_banco_id;
                             });
                             this.$bvModal.hide('modalFormCreditoBanco');
                         } else {
                             this.refrescarListadoCreditosBancosPorCreditoId();
                         } */

                        this.showMsgBoxOk(data.message);

                    }).catch(error => {
                        this.spinners.borrarCreditoBanco = false;
                        console.log(error);
                        const errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showMsgBoxOk(errorMsg, 'Error', 10000);
                        window.location.href = "#app";
                        window.scroll(0, 0);
                    });

                    return false;
            },

            borrarCreditoBanco(controlador, datosDesembolsos = false) {
                // const controlador = 'creditos/ajaxBorrarCreditoSimulado/' + id;
                this.spinners.borrarCreditoBanco = true;
                this.cargarDatos(controlador, {}, 'DELETE')
                    .then(response => {
                        const data = response.data;

                        this.$bvModal.hide('modalFormCreditoBanco');
                        this.refrescarListadoCreditosBancosPorCreditoId(this.creditoBancoActual.credito_id);

                        /* if (!datosDesembolsos) {
                             const listado = this.listados.creditoBancos;
                             this.listados.creditoBancos = listado.filter(function(obj) {
                                 return obj.id != data.credito_banco_id;
                             });
                             this.$bvModal.hide('modalFormCreditoBanco');
                         } else {
                             this.refrescarListadoCreditosBancosPorCreditoId();
                         } */

                        this.showMsgBoxOk(data.message);

                    }).catch(error => {
                        this.spinners.borrarCreditoBanco = false;
                        console.log(error);
                        const errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showMsgBoxOk(errorMsg, 'Error', 10000);
                        window.location.href = "#app";
                        window.scroll(0, 0);
                    });

                    return false;
            },
            borrarCredito(controlador, reload = true) {
                // const controlador = 'creditos/ajaxBorrarCreditoSimulado/' + id;
                this.spinners.borrarCredito = true;
                this.cargarDatos(controlador, {}, 'DELETE')
                    .then(response => {
                        const data = response.data;
                        this.showAlertMsg(data.message);
                        if (reload) {
                            window.location.reload();
                        }
                    }).catch(error => {
                        console.log()
                        this.spinners.borrarCredito = false;
                        const errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showAlertMsg(errorMsg, 'Error', 10000);
                        window.location.href = "#app";
                        window.scroll(0, 0);
                    });

                    return false;
            },

            borrarDesembolsosCreditoBanco_click(item) {
                this.creditoBancoActual = item;
                let msg = '¿Está seguro? ';
                //  msg += 'Si el crédito tiene datosDesembolsos realizados serán borrados';
                this.$bvModal.msgBoxConfirm(msg, {
                        title: 'Confirmación',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        okTitle: 'Si',
                        cancelTitle: 'No',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        // centered: true
                    })
                    .then(value => {
                       /* console.log({
                            _value: value
                        })*/
                        if (value == true) {
                            //  const controlador = 'creditos/ajaxBorrarDesembolsosCreditoBanco/' + item.id;
                            setTimeout(() => {
                                this.borrarDesembolsosCreditoBanco(item.id); //DESEMBOLSOS
                                // this.refrescarListadoCreditosBancosPorCreditoId();
                            }, 0);

                        }
                    })
                    .catch(err => {
                        // An error occurred
                    });

                    return false;
            },
            borrarCreditoBanco_click() {
                let msg = '¿Está seguro? ';
                msg += 'Si el crédito tiene desembolsos serán borrados';
                this.$bvModal.msgBoxConfirm(msg, {
                        title: 'Confirmación',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        okTitle: 'Si',
                        cancelTitle: 'No',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        // centered: true
                    })
                    .then(value => {
                        /*console.log({
                            _value: value
                        })*/
                        if (value == true) {
                            const controlador = 'creditos/ajaxBorrarCreditoSimuladoBanco/' + this.creditoBancoActual.id;
                            setTimeout(() => {
                                this.borrarCreditoBanco(controlador); //CREDITO BANCARIOS
                                // this.refrescarListadoCreditosBancosPorCreditoId();
                            }, 0);

                        }
                    })
                    .catch(err => {
                        // An error occurred
                    });

                    return false;
            },

            borrarCredito_click(id) {
                let msg = '¿Está seguro(a) ?. ';
                msg += 'Se borraran todos los creditos bancarios y ';
                msg += 'desembolsos asociados a este crédito';
                this.$bvModal.msgBoxConfirm(msg, {
                        title: 'Confirmación',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        okTitle: 'Si',
                        cancelTitle: 'No',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        // centered: true
                    })
                    .then(value => {

                        if (value == true) {
                            const controlador = 'creditos/ajaxBorrarCreditoSimulado/' + id;
                            this.borrarCredito(controlador, true);
                        }
                    })
                    .catch(err => {
                        // An error occurred
                    });

                    return false;
            },
            pararSpinners() {
                this.spinners.guardarCredito = false;
                this.spinners.guardaDesembolso = false;
                this.spinners.editarCredito = false;
                this.spinners.cargarCreditosBancos = false;
                this.spinners.borrarCredito = false;
            },
            editarCreditoBanco_click() {
                let msg = '¿Está seguro? ';
                msg += 'Si el crédito tiene desembolsos realizados serán borrados';
                this.$bvModal.msgBoxConfirm(msg, {
                        title: 'Confirmación',
                        size: 'sm',
                        buttonSize: 'sm',
                        okVariant: 'success',
                        okTitle: 'Si',
                        cancelTitle: 'No',
                        footerClass: 'p-2',
                        hideHeaderClose: false,
                        // centered: true
                    })
                    .then(value => {

                        if (value == true) {
                            const controlador = 'creditos/ajaxEditarCreditoSimuladoBanco/' + this.creditoBancoActual.id;
                            const datos = this.creditoBanco;
                            this.spinners.editarCredito = true;
                            this.enviarDatosCreditoBanco(datos, controlador, 'PUT');
                        }
                    })
                    .catch(err => {
                        // An error occurred
                    });

                    return false;
            },
            guardarCreditoBanco_click() {
                const controlador = 'creditos/ajaxGuardarCreditoSimuladoBanco';
                const datos = this.creditoBanco;
                this.spinners.guardarCredito = true;
                this.enviarDatosCreditoBanco(datos, controlador);
            },

            enviarDatosCreditoBanco(datos, controlador, tipo = 'POST') {
               /* console.log({
                    creditoBancoActual: this.creditoBancoActual
                });*/

                try {
                    this.enviarDatos(datos, controlador, tipo)
                        .then(response => {
                            const data = response.data;

                            if (data.success === true) {

                                if (this.modales.editarCreditoBanco === false) {
                                    this.limpiarFormCreditoBancario();
                                } else {
                                    //ACTUALIZAR EL LISTADO CON LOS CAMBIOS
                                    //const cantidad = Object.size(this.listados.creditosBancos);
                                    const listado = this.listados.creditoBancos;
                                    this.cargarCreditosBancosPorCreditoId(this.creditoBancoActual.credito_id);
                                }
                                this.errors = {};
                                this.spinners.guardarCredito = false;
                                this.showMsgBoxOne(data.message, 'success');
                            }

                            this.pararSpinners();
                        }).catch(error => {
                          //  console.log(error);
                            this.pararSpinners();
                            let errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                            this.showAlertMsgModal(errorMsg, 'Error');
                        });
                } catch (error) {
                    this.pararSpinners();
                   // console.error(error);
                }

                return false;
            }


        },
        mounted() {
            //EVENTO LANZADO TAN PRONTO CARGA EL FORMULARIO
            window.addEventListener('load', () => {

                this.ajaxCargarRegistros('bancos', 'bancos');

                //PARA EL SPINNER
                this.formCargado = true;
            });

            // alert('Hola mundo'+this.$baseUrl);
        },
        updated() {
            // run something after dom has changed by vue

        }
    });
</script>
@endsection
