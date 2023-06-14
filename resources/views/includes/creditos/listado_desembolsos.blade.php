@extends('layouts.default') @section('content')
    <style>
       /* .vdp-datepicker__calendar {
            position: fixed;
        }

        [v-cloak] {
            display: none;
        }
*/
    </style>
    <div id="vueapp" v-cloak>
        <div class="card">
            <div class="card-header">
                <b>Listado de desembolsos </b>
            </div>
            <div class="card-body">

                @include('elements.alertMsg')
                @include('elements.alertMsgJs')

                <div class="form-row">

                    <div class="col-4">
                        <div class="form-group">
                            <select class="form-control form-control-sm" v-model="datosDesembolso.id"
                                @change="seleccionarDesembolso_change">
                                <option value="0">Seleccione un item</option>
                                <option v-for="item in listados.desembolsos" :value="item.id"> @{{ item . descripcion }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div v-if="spinners.cargandoDatos" class="spinner-border" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                </div>

                <div class="form-row" v-if="mostrarDatos">

                    <div class="col-10">
                        <div class="form-group">
                            <h5> <span class="badge badge-secondary">Información del desembolso</span></h5>
                            <table class="table table-bordered table-striped">

                                <tbody>
                                    <tr>
                                        <td><b>Credito</b></td>
                                        <td colspan="3">@{{ creditoBanco . descripcion }}</td>
                                    </tr>

                                    <tr>
                                        <td><b>Fecha inicio desembolso</b></td>
                                        <td>@{{ encabezado . fecha_inicio }}</td>
                                        <td><b>Fecha fin desembolso</b></td>
                                        <td>@{{ encabezado . fecha_fin }}</td>
                                    </tr>

                                    <tr>
                                        <td><b>Valor desembolso</b></td>
                                        <td>@{{ (encabezado . valor) | currency }} </td>
                                        <td><b>Independiente</b></td>
                                        <td v-if="encabezado.es_independiente==0">No</td>
                                        <td v-if="encabezado.es_independiente==1">Si</td>
                                    </tr>

                                    <tr>
                                        <td><b>Fecha creación</b></td>
                                        <td>@{{ encabezado . created_at }}</td>
                                        <td><b>Fecha actualización</b></td>
                                        <td>@{{ encabezado . updated_at }}</td>
                                    </tr>

                                </tbody>

                            </table>
                        </div>

                    </div>

                </div>

                <div class="form-row" v-if="mostrarDatos">

                    <div class="col-12">
                        <h5> <span class="badge badge-secondary">Cuotas</span></h5>
                        <div class="form-group">

                            <div class="table-responsive"
                                style="border: 1px solid #E8E8E8; background-color: #F8F8F8; height:450px; overflow: auto;">
                                <table class="table table-striped table-bordered tableFixed" style="word-wrap: break-word;">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="width: 5%;">#</th>
                                            <th scope="col" style="width: 10%;">Fecha</th>
                                            <th scope="col">Concepto</th>
                                            <th scope="col">Amortización capital</th>
                                            <th scope="col">Interes proyectado</th>
                                            <th scope="col" style="width:12%;">Interes pagado</th>
                                            <th scope="col">Total servicio deuda</th>
                                            <th scope="col">Saldo deuda</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <tr v-for="(item, index) in listados.cuotas">
                                            <td scope="form-row">@{{ item . numero }}</td>
                                            <td>@{{ item . fecha }}</td>
                                            <td>@{{ item . concepto }}</td>
                                            <td>@{{ item . amort_capital_round }} </td>
                                            <td>@{{ item . interes_proyectado }}</td>
                                            <td><input type="number" maxlength="15" v-model="item.interes_pagado"
                                                    @keypress='validarMaxLengthInt($event, 15)'
                                                    @change="cambioValores(item)" class="form-control form-control-sm" />
                                                @{{ (item . interes_pagado) | currency }}
                                            </td>
                                            <td>@{{ item . total_serv_deuda }}</td>
                                            <td>@{{ item . saldo_deuda_round }}</td>
                                        </tr>

                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="form-row" v-if="mostrarDatos">

                    <div class="col-12">
                        <div class="form-group">
                            <h5> <span class="badge badge-secondary">Proyecciones</span></h5>



                            <div class="table-responsive"
                                style="border: 1px solid #E8E8E8; background-color: #F8F8F8; height:450px; overflow: auto;">
                                <table class="table table-striped table-bordered tableFixed" style="word-wrap: break-word;">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="width: 5%;">#</th>
                                            <th scope="col" style="width: 10%;">Año</th>
                                            <th scope="col" style="width:12%;">Tasa ref.</th>
                                            <th scope="col">SPREAD</th>
                                            <th scope="col">DÍAS</th>
                                            <th scope="col">@{{ titulo_col_tasa_nominal }} </th>
                                            <th scope="col">@{{ titulo_col_tasa_efectiva }}</th>
                                            <th scope="col">VALOR $</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in listados.proyecciones">
                                            <td scope="form-row">@{{ item . numero }}</td>
                                            <td><b>@{{ item . anno }}</b></td>
                                            <td style="width: 15%;">
                                                <input type="number" v-model="item.tasa_ref_valor"
                                                    @keypress='validarMaxLength($event, 6)'
                                                    class="form-control form-control-sm" @change="cambioValores(item)" />
                                            </td>
                                            <td style="width: 15%;"><input type="number" v-model="item.spread"
                                                    @keypress='validarMaxLength($event, 6)'
                                                    class="form-control form-control-sm" @change="cambioValores(item)" />
                                            </td>
                                            <td style="width: 15%;"><input type="number" v-model="item.num_dias"
                                                    @keypress='validarMaxLength($event, 3)'
                                                    class="form-control form-control-sm" @change="cambioValores(item)" />
                                            </td>
                                            <td><input type="text" v-model="item.tasa_nominal"
                                                    class="form-control form-control-sm" :disabled="true" /></td>
                                            <td><input type="text" v-model="item.tasa_efectiva_anual" :disabled="true"
                                                    class="form-control form-control-sm" /></td>
                                            <td>@{{ item . valor }}</td>

                                        </tr>

                                    </tbody>

                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <hr v-if="mostrarDatos" />
                <div class="form-form-row" v-if="mostrarDatos">

                    <div class="input-group">

                        @can('check-authorization', 'PERMISO_CREAR_DESEMBOLSO_SIMULADO')
                            <button class="btn btn-success btn-sm" @click="guardarCuotasProyecciones">
                                <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.guardar"></i>
                                Guardar</button>

                            <button style="margin-left:4px;" class="btn btn-secondary btn-sm"
                                @click="atualizarCuotasProyecciones">
                                <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.actualizar"></i>
                                Actualizar
                            </button>
                        @endcan

                        <div class="dropdown" style="margin-left:4px;">

                            <button type="button" class="btn btn-secondary btn-sm dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-spinner fa-spin fa-1x" v-if="spinners.ejecutarAccion"></i>
                                Acciones
                            </button>

                            <div class="dropdown-menu">
                                <button class="dropdown-item" @click="generarExcelDesembolso_click">Generar Excel</button>

                                @can('check-authorization', 'PERMISO_EDITAR_DESEMBOLSO_SIMULADO')
                                    <button class="dropdown-item" @click="verModalFormularioDesembolso('editar')">Editar
                                        desembolso</button>
                                @endcan

                                @can('check-authorization', 'PERMISO_CREAR_DESEMBOLSO_SIMULADO')
                                    <button class="dropdown-item" @click="verModalFormularioDesembolso('nuevo')">Nuevo
                                        desembolso</button>
                                @endcan

                                @can('check-authorization', 'PERMISO_BORRAR_DESEMBOLSO_SIMULADO')
                                    <button class="dropdown-item" @click="borrarDesembolso_click">Borrar
                                        desembolso</button>
                                @endcan

                                @can('check-authorization', 'PERMISO_VER_LISTADO_DESEMBOLSOS_SIMULADOS')
                                    <a class="dropdown-item" href="{{ route('creditos_simulados') }}">Listar creditos</a>
                                @endcan

                            </div>

                        </div>

                    </div>


                </div>

            </div>
        </div>

        <!-- MODAL FORM DESEMBOLSOS  -->
        @include('includes.creditos.modal_form_desembolso')
    </div>

    <script type="application/javascript">
        const vueApp = new Vue({
            el: '#vueapp',
            // mixins: [],
            mixins: [myMixin, Vue2Filters.mixin],
            data: {
                formCargado: false,
                listados: {
                    desembolsos: [],
                    cuotas: [],
                    proyecciones: []
                },
                desembolsoActual: null,
                mostrarDatos: false,
                spinners: {
                    guardar: false,
                    actualizar: false,
                    generarExcel: false,
                    cargandoDatos: false,
                    editarDesembolso: false,
                    guardaDesembolso: false,
                    borrarDesembolso: false,
                    ejecutarAccion: false
                },
                encabezado: {
                    //credito_banco_id: 0,
                    valor: 0,
                    fecha_inicio: '',
                    fecha_fin: '',
                    descripcion: '',
                    // tipo_desembolso: 0,
                    es_independiente: false,
                    // num_annos:
                },
                datosDesembolso: {
                    id: 0,
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
                    // credito_id: 0,
                    separar_interes_capital: false,
                    truncar_tasa_ea: false
                },
                //  esIndependiente: false,
                mostrarBtnEditarBorrar: false, //MODAL FORM DESEMBOLSOS
                editarDesembolso: false,
                cambiosGuardados: false,
                fechaFinDesembolso: '',
                credito_banco_id: <?= !empty($creditoBancoId) ? "'" . $creditoBancoId . "'" : "'0'" ?>,
                desembolso_id: 0,
                titulo_col_tasa_nominal: '',
                titulo_col_tasa_efectiva: '',
                creditoBanco: null,
            },
            components: {
                vuejsDatepicker
            },

            methods: {
                verificarEsIndependiente_click(e) {
                    this.limpiarModalDesembolso();
                   /* console.log({
                        datosDesembolso: this.datosDesembolso
                    }); */
                    this.datosDesembolso.es_independiente = e.target.checked;
                    if (this.datosDesembolso.es_independiente === false) {
                        this.verificarDesembolso(this.datosDesembolso.credito_banco_id);
                        this.datosDesembolso.fecha_fin = this.fechaFinDesembolso;
                    } else {
                        this.datosDesembolso.fecha_fin = '';
                    }

                    this.datosDesembolso.fecha_inicio = '';
                },


                guardarDesembolso_click() {
                    const ruta = 'creditos/ajaxGuardarDesembolsoSimulado'
                    // this.datosDesembolso.fecha_inicio = moment(this.datosDesembolso.fecha_inicio).format(moment.HTML5_FMT.DATE);

                    //ASIGNAR EL CREDITO DEL BANCO
                    this.datosDesembolso.credito_banco_id = this.desembolsoActual.credito_banco_id;

                    const datos = this.datosDesembolso;
                    this.spinners.guardaDesembolso = true;
                    this.enviarDatos(datos, ruta)
                        .then(response => {
                            const data = response.data;
                            this.errors = {};
                            this.spinners.guardaDesembolso = false;
                            //VOLVER A CARGAR LOS CREDITOS
                            this.ajaxCargarRegistros(
                                'creditos_desembolsos',
                                'desembolsos',
                                true,
                                this.datosDesembolso.credito_banco_id, //CREDITO DEL BANCO
                                'credito_banco_id'
                            );
                            this.$bvModal.hide('modalFormularioDesembolso');
                            this.showMsgBoxOk(data.message);
                        }).catch(error => {
                            console.log(error);
                            this.spinners.guardaDesembolso = false;
                            let errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                            this.showMsgBoxOk(errorMsg, 'Error');
                        });
                },

                editarDesembolso_click() {
                    let msg = '¿Está seguro(a)? ';
                    msg += 'se perderan todos los cambios realizados. Adicional, ';
                    msg += 'si es el primer desembolso se perderan todos los "desembolsos" siguientes.';
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
                            }) */
                            if (value == true) {
                                // const controlador = 'creditos/ajaxBorrarCreditoSimuladoBanco/' + this.creditoBanco.id;
                                setTimeout(() => {
                                    this.editDesembolso(); //CREDITO BANCARIOS
                                    // this.refrescarListadoCreditosBancosPorCreditoId();
                                }, 0);

                            }
                        })
                        .catch(err => {
                            // An error occurred
                        });
                },

                editDesembolso() {
                    const ruta = 'creditos/ajaxEditarDesembolsoSimulado'
                    // this.datosDesembolso.fecha_inicio = moment(this.datosDesembolso.fecha_inicio).format(moment.HTML5_FMT.DATE);
                    delete this.datosDesembolso.es_primer_desembolso;

                    const datos = this.datosDesembolso;
                    this.spinners.editarDesembolso = true;
                    this.enviarDatos(datos, ruta, 'PUT')
                        .then(response => {
                            const data = response.data;

                          /*  console.log({
                                dataResponse: data
                            }); */

                            this.listados.cuotas = data.desembolso.cuotas;
                            this.listados.proyecciones = data.desembolso.proyecciones;

                            //ACTUALIZAR EL datosDesembolso ACTUAL
                            this.desembolsoActual = data.desembolso;
                            this.showMsgBoxOk(data.message);
                            this.spinners.editarDesembolso = false;

                            //ACTUALIZAR EL DESEMBOLSO
                            this.obtenerDesembolso(data.desembolso.id);

                            //ACTUALIZAR EL LISTADO DE DESEMBOLSOS
                            this.ajaxCargarRegistros(
                                'creditos_desembolsos',
                                'desembolsos',
                                true,
                                data.desembolso.credito_banco_id,
                                'credito_banco_id'
                            );

                        }).catch(error => {
                            console.log(error);
                            this.spinners.editarDesembolso = false;
                            let errorMsg = this.errorHandling(error,
                                'Error al intentar realizar la operación');
                            this.showMsgBoxOk(errorMsg, 'Error');
                        });
                },
                borrarDesembolso_click() {
                    let msg = '¿Está seguro? ';
                    // msg += 'se perderan todos los cambios realizados. Adicional, ';
                    msg += 'si es el primer desembolso se perderan todos los "desembolsos" siguientes.';
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
                                setTimeout(() => {
                                    //BORRAR datosDesembolso
                                    this.borrarDesembolso(this.desembolsoActual.id);
                                }, 0);
                            }
                        })
                        .catch(err => {
                            // An error occurred
                        });
                },

                borrarDesembolso(id) {
                    const ruta = 'creditos/ajaxBorrarDesembolsoSimulado/' + id;

                    this.spinners.borrarDesembolso = true;
                    const datos = this.datosDesembolso;

                    this.enviarDatos(datos, ruta, 'DELETE')
                        .then(response => {
                            const data = response.data;

                            this.showMsgBoxOk(data.message);

                            this.datosDesembolso.id = 0;

                            this.mostrarDatos = false;
                            // this.formCargado = false;
                            //ACTUALIZAR EL LISTADO DE DESEMBOLSOS
                            this.ajaxCargarRegistros(
                                'creditos_desembolsos',
                                'desembolsos',
                                true,
                                data.desembolso.credito_banco_id,
                                'credito_banco_id'
                            );
                            this.$bvModal.hide('modalFormularioDesembolso');
                        }).catch(error => {
                            console.log(error);
                            this.spinners.editarDesembolso = false;
                            let errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                            this.showMsgBoxOk(errorMsg, 'Error');
                        });
                },

                seleccionarFechaInicial(fechaSeleccionada) {
                    // const tempFecha = fecha;
                    /*console.log({
                        fechaSeleccionada: fechaSeleccionada
                    }); */
                    if (this.datosDesembolso.es_primer_desembolso || this.datosDesembolso.es_independiente) {
                        const tempFecha = _.cloneDeep(fechaSeleccionada);
                        /*console.log({
                            numAnnos: this.datosDesembolso.num_annos
                        }); */
                        const fechaFinal = this.agregarAnnos(tempFecha, Number(this.datosDesembolso.num_annos));
                        this.datosDesembolso.fecha_fin = fechaFinal;
                        /* console.log({
                             fechaFinal: fechaFinal
                         }); */
                    }
                },

                agregarAnnos(dt, n) {
                    /*  console.log({
                          dt: dt
                      }); */
                    dt = new Date(dt);
                    return new Date(dt.setFullYear(dt.getFullYear() + n));
                },

                //EVENTO PARA GESTIÓNAR EL CAMBIO DE NÚMERO DE AÑOS
                cambiarNumAnnos() {
                   /* console.log({
                        cambiarNumAnnos: this.datosDesembolso
                    });*/

                    if (this.datosDesembolso.es_primer_desembolso || this.datosDesembolso.es_independiente) {

                        /*if (!this.datosDesembolso.num_annos || Number(this.datosDesembolso.num_annos) <= 0) {
                            this.datosDesembolso.num_annos = 1;
                        }*/

                        const numAnnos = Number(this.datosDesembolso.num_annos);
                        const fechaInicio = this.datosDesembolso.fecha_inicio;

                       /* console.log({
                            fechaInicio: fechaInicio
                        }); */

                        if (fechaInicio && numAnnos > 0) {
                            const fechaFinal = this.agregarAnnos(fechaInicio, numAnnos);
                            this.datosDesembolso.fecha_fin = fechaFinal;
                        }
                    }
                },

                guardarCuotasProyecciones() {
                    this.spinners.guardar = true;
                    this.guardarActualizarCuotasProyecciones(true);
                },

                atualizarCuotasProyecciones() {
                    this.spinners.actualizar = true;
                    this.guardarActualizarCuotasProyecciones(false);
                },

                pararSpinners() {
                    this.spinners.guardar = false;
                    this.spinners.actualizar = false;
                    this.spinners.generarExcel = false;
                    this.spinners.cargandoDatos = false;
                    this.spinners.ejecutarAccion = false;
                },


                //CAMBIOS
                cambioValores(item) {
                   /* console.log({
                        cambioValores: item
                    }); */
                    item['cambio_valores'] = true;
                },

                cargarDesembolso(datosDesembolso) {
                    this.desembolsoActual = datosDesembolso;
                   /* console.log({
                        datosDesembolso: datosDesembolso
                    }); */
                    this.datosDesembolso.id = datosDesembolso.id;
                    this.datosDesembolso.banco_id = datosDesembolso.banco_id;
                    this.datosDesembolso.credito_banco_id = datosDesembolso.credito_banco_id;
                    this.datosDesembolso.valor = datosDesembolso.valor;
                    //   console.log({fechaInicio: datosDesembolso.fecha_inicio });
                    this.datosDesembolso.fecha_inicio = moment(datosDesembolso.fecha_inicio).format('L');
                    this.datosDesembolso.fecha_fin = this.fechaFinDesembolso = moment(datosDesembolso.fecha_fin)
                        .format('L');
                    this.datosDesembolso.descripcion = datosDesembolso.descripcion;
                    this.datosDesembolso.tipo_desembolso = datosDesembolso.tipo_desembolso;
                    this.datosDesembolso.es_independiente = datosDesembolso.es_independiente;
                    //  console.log({condiciones: datosDesembolso.condiciones_json });
                    this.datosDesembolso.truncar_tasa_ea = datosDesembolso.truncar_tasa_ea;
                    this.datosDesembolso.separar_interes_capital = datosDesembolso.separar_interes_capital;
                    this.datosDesembolso.tipo_desembolso = datosDesembolso.tipo_desembolso;

                    //CONDICIONES DEL CREDITO INDEPENDIENTE
                    if (datosDesembolso.es_independiente) {
                        const condicionesJson = datosDesembolso.condiciones_json;
                        this.datosDesembolso.num_dias = condicionesJson.num_dias;
                        this.datosDesembolso.num_annos = condicionesJson.num_annos;
                        this.datosDesembolso.periodo_gracia = condicionesJson.periodo_gracia;
                        this.datosDesembolso.spread = condicionesJson.spread;
                        this.datosDesembolso.tasa_ref = condicionesJson.tasa_ref;
                        this.datosDesembolso.tasa_ref_valor = condicionesJson.tasa_ref_valor;
                    }

                    /*console.log({
                        desembolsoCargado: datosDesembolso
                    }); */
                },
                limpiarModalDesembolso(datosDesembolso) {
                    //  this.desembolsoActual = datosDesembolso;
                    this.datosDesembolso.id = 0;
                    // this.datosDesembolso.credito_banco_id = '';
                    this.datosDesembolso.valor = '';
                    this.datosDesembolso.fecha_inicio = '';
                    this.datosDesembolso.fecha_fin = '';
                    this.datosDesembolso.descripcion = '';
                    this.datosDesembolso.tipo_desembolso = '';
                    this.datosDesembolso.es_independiente = false;
                    //  this.datosDesembolso.num_annos = '';
                    this.datosDesembolso.separar_interes_capital = false;
                    this.datosDesembolso.truncar_tasa_ea = false;
                },

                verModalFormularioDesembolso(tipo) {
                   // console.log({verModalFormularioDesembolso: 'verModalFormularioDesembolso'});
                    if (tipo === 'editar') {
                        this.cargarDesembolso(this.desembolsoActual);
                        // this.editarDesembolso = true;
                        this.mostrarBtnEditarBorrar = true;
                    } else { //NUEVO
                        // this.creditoBanco = credito;
                        this.errors = {};
                        //const controlador = "creditos/ajaxVerificarDesembolso/" + this.desembolsoActual.credito_banco_id;
                        this.limpiarModalDesembolso();
                        // this.cargarDatos(controlador, {}).
                        this.verificarDesembolso(this.desembolsoActual.credito_banco_id);
                        // this.limpiarModalDesembolso();
                        this.mostrarBtnEditarBorrar = false;
                    }

                    this.errors = {};

                    this.$bvModal.show('modalFormDatosDesembolso');
                },

                verificarDesembolso(creditoBancoId) {
                    const controlador = "creditos/ajaxVerificarDesembolso/" + creditoBancoId;
                    this.cargarDatos(controlador, {}).
                    then(response => {
                        const data = response.data;
                        const creditoBanco = data.credito_banco;

                        //LOS OTROS DATOS SE CARGAN AL MOMENTO DE ABRIR EL MODAL
                        this.datosDesembolso.credito_banco_id = this.desembolsoActual.credito_banco_id;
                        this.datosDesembolso.fecha_fin = this.fechaFinDesembolso = moment(data.fecha_fin)
                            .format(
                                'L');

                        this.datosDesembolso.periodo_gracia = creditoBanco.periodo_gracia;
                        this.datosDesembolso.es_primer_desembolso = data.es_primer_desembolso;
                        this.datosDesembolso.num_annos = creditoBanco.num_annos;
                    }).catch(error => {
                        let errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showMsgBoxOk(errorMsg, 'Error');
                    });
                },

                guardarActualizarCuotasProyecciones(guardarCambios = false) {

                   /* console.log({
                        lstProyecciones: this.listados.proyecciones
                    }); */

                    const proyeCambiadas = Object.values(this.listados.proyecciones).filter(function(item) {
                        return item['cambio_valores'] == true;
                    });

                    const cuotasCambiadas = Object.values(this.listados.cuotas).filter(function(item) {
                        return item['cambio_valores'] == true;
                    });

                    const controlador = 'creditos/ajaxGuardarActualizarCuotas';

                   /* console.log({
                        valorGuardarCambios: guardarCambios
                    }); */
                    const datos = {
                        proyecciones: JSON.stringify(proyeCambiadas),
                        cuotas: JSON.stringify(cuotasCambiadas),
                        guardar_cambios: Boolean(guardarCambios),
                        desembolso_id: Number(this.desembolso_id)
                    }

                    this.enviarCuotasProyecciones(datos, controlador);


                },

                enviarCuotasProyecciones(datos, controlador) {


                    this.enviarDatos(datos, controlador, 'POST')
                        .then(response => {
                            const data = response.data;

                            if (data.success == true) {
                                this.listados.cuotas = [];
                                this.listados.cuotas = data.cuotas;
                                this.listados.proyecciones = [];
                                this.listados.proyecciones = data.proyecciones;
                            }
                            setTimeout(() => {
                                this.pararSpinners();
                            }, 600);
                        }).catch(error => {
                            /*  this.spinners.cargandoDatos = false;
                              this.mostrarDatos = false; */
                            this.pararSpinners();
                            console.log(error);
                        });
                },

                seleccionarDesembolso_change(event) {
                    const id = event.target.value;
                    if (id == 0 || id == '' || id == undefined || id.length == 0) {
                        this.desembolso_id = 0
                        return false;
                    }
                    this.mostrarDatos = false;
                    this.spinners.cargandoDatos = true;
                    this.desembolso_id = id;

                    this.obtenerDesembolso(id);
                },

                generarExcelDesembolso_click(event) {

                   /* console.log({
                        generarExcelDesembolso_click: 'generarExcelDesembolso_click'
                    }); */
                    // const id = event.target.value;
                    if (_.isEmpty(this.desembolso_id)) {
                        this.showMsgBoxOne('Debe seleccionar un datosDesembolso');
                        this.desembolso_id = 0
                        return false;
                    }

                    this.spinners.ejecutarAccion = true;
                    const path = "creditos/ajaxGenerarExcelDesembolso/" + this.desembolso_id;
                    this.cargarDatos(path, {})
                        .then(response => {
                            // console.log({response: response });
                            const data = response.data;
                            if (data.success && !_.isEmpty(data.archivoExcel)) {
                                window.open(data.archivoExcel, '_blank').focus();
                                // this.errors = {};
                            } else {
                                this.showMsgBoxOne(data.message);
                            }

                            this.pararSpinners();
                        }).catch(error => {
                            this.pararSpinners();
                        });
                    // this.obtenerDesembolso(id);
                },

                cargarEncabezado(datosDesembolso) {
                    // this.encabezado.valor = datosDesembolso.valor;
                  /*  console.log({
                        cargarEncabezado: datosDesembolso
                    }); */
                    this.encabezado.fecha_inicio = datosDesembolso.fecha_inicio;
                    this.encabezado.fecha_fin = datosDesembolso.fecha_fin;
                    this.encabezado.valor = datosDesembolso.valor;
                    this.encabezado.es_independiente = datosDesembolso.es_independiente;
                    this.encabezado.created_at = datosDesembolso.created_at;
                    this.encabezado.updated_at = datosDesembolso.updated_at;
                },

                obtenerDesembolso(id) {
                    const controlador = 'creditos/ajaxObtenerDesembolso/' + id;
                    this.cargarDatos(controlador)
                        .then(response => {
                            const data = response.data;
                            this.listados.cuotas = data.cuotas;
                            this.listados.proyecciones = data.proyecciones;
                            this.titulo_col_tasa_nominal = data.titulo_col_tasa_nominal;
                            this.titulo_col_tasa_efectiva = data.titulo_col_tasa_efectiva;
                            this.creditoBanco = data.creditoBanco;
                            //this.creditoBanco
                            //  this.desembolsoActual = data.datosDesembolso;
                            this.cargarDesembolso(data.desembolso);
                            this.cargarEncabezado(data.desembolso);

                            setTimeout(() => {
                                this.spinners.cargandoDatos = false;
                                this.mostrarDatos = true;
                            }, 600);

                        }).catch(error => {
                            this.spinners.cargandoDatos = false;
                            this.mostrarDatos = false;
                            console.log(error);
                        });
                },

                limpiarDatosCreditosBancos() {
                    this.emprestito.banco_id = 0;
                    this.emprestito.valor = 0;
                    this.emprestito.fecha = '';
                    this.emprestito.notas = '';
                },

                /* eventoMostrarModalCreditosBancos(credito) {
                     this.creditoBanco = credito;
                     this.limpiarDatosCreditosBancos();
                     this.emprestito.credito_id = credito.id;
                     this.listados.creditosBancos = [];
                     this.ajaxCargarRegistros('creditos_bancos', 'creditosBancos', true, credito.id, 'credito_id');
                     this.$bvModal.show('modalPrestamosBancos');
                 }, */

                increment: function(valor) {
                    vueApp.creditoBanco.valor_prestado = valor;
                    this.$emit('increment')
                },
                guardarCreditoBanco() {
                    const controlador = 'creditos/ajaxGuardarCreditoSimuladoBanco';
                    const datos = this.emprestito;
                    try {
                        this.enviarDatos(datos, controlador)
                            .then(response => {
                                const data = response.data;
                               /* console.log({
                                    data: data
                                }); */
                                if (data.success === true) {
                                    /*console.log({
                                        valorPrestado: data.credito.valor_prestado
                                    }); */
                                    //vueApp.creditoBanco.valor_prestado = data.credito.valor_prestado;
                                    //   this.$emit(vueApp.creditoBanco, 'valor_prestado', data.credito.valor_prestado);
                                    //  this.$emit(vueApp.creditoBanco.valor_prestado, data.credito.valor_prestado);
                                    //vueApp.increment(data.credito.valor_prestado);
                                    this.showMsgBoxOne(data.message);
                                    this.limpiarDatosCreditosBancos();
                                    this.errors = {};
                                }
                            }).catch(error => {
                                let errorMsg = this.errorHandling(error,
                                    'Error al intentar realizar la operación');
                                this.showMsgBoxOne(errorMsg, 'Error');
                            });
                    } catch (error) {
                        console.error(error);
                    }
                }


            },
            mounted() {

                this.mostrarDatos = false;
                //EVENTO LANZADO TAN PRONTO CARGA EL FORMULARIO
                window.addEventListener('load', () => {

                    this.ajaxCargarRegistros('bancos', 'bancos');

                    if (this.credito_banco_id > 0) {
                        this.ajaxCargarRegistros('creditos_desembolsos', 'desembolsos', true, this
                            .credito_banco_id, 'credito_banco_id');
                    } else {
                        this.ajaxCargarRegistros('creditos_desembolsos', 'desembolsos');
                    }


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
