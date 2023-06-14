@extends('layouts.default') @section('content')
<style>
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
            <b>
                <?= $titulo ?>
            </b>
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
                            <form method="post" action="#">
                                @csrf
                                <div class="input-group">
                                    <input type="text" autocomplete="off" v-model="datosDesembolso.RFHA" name="txtFiltro" maxlength="20" class="form-control form-control-sm" placeholder="Número de línea">
                                    <div class="input-group-append">
                                        <button @click="btnBuscarLineaSap_click" class="btn btn-secondary btn-sm" type="button">
                                            <span v-if="spinners.buscarLineaSap" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span <i class="fa fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="form-row" v-if='disableEnableElements.verDetalleLinea'>

                    <div class="col-12">
                        <div class="form-group">
                            <h5> <span class="badge badge-secondary">Detalle de la línea SAP</span></h5>
                            <table class="table table-bordered table-striped">

                                <tbody>
                                    <tr>
                                        <td><b>Línea</b></td>
                                        <td colspan="3">@{{ datosDesembolso.info_linea.RFHA }}</td>
                                    </tr>

                                    <tr>
                                        <td><b>Fecha inicio</b></td>
                                        <td>@{{ datosDesembolso.info_linea.DBLFZ }}</td>
                                        <td><b>Fecha fin</b></td>
                                        <td>@{{ datosDesembolso.info_linea.DELFZ }}</td>
                                    </tr>

                                    <tr>
                                        <td><b>Banco</b></td>
                                        <td>@{{ datosDesembolso.info_linea.KONTRH }}
                                        <td><b>Monto</b></td>
                                        <td>@{{ datosDesembolso.info_linea.IMPORTE_LIMITE | currency }} </td>
                                    </tr>

                                </tbody>

                            </table>
                        </div>
                    </div>
                </div>


                <div class="form-row" v-if="disableEnableElements.verDesembolsos">
                    <div class="col-12">
                        <div class="form-group">
                            <h5> <span class="badge badge-secondary">Desembolsos de la línea</span></h5>
                            <div class="table-responsive" style="border: 1px solid #E8E8E8; background-color: #F8F8F8; max-height:250px; overflow: auto;">
                                <table class="table table-striped table-bordered tableFixed" style="word-wrap: break-word;">
                                    <thead>
                                        <tr>
                                            <th scope="col">Desembolso</th>
                                            <th scope="col">Fecha inicio</th>
                                            <th scope="col">Fecha fin</th>
                                            <th scope="col">Tasa referencia</th>
                                            <th scope="col">Spread</th>
                                            <th scope="col">Monto desembolso</th>
                                            <th scope="col">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in datosDesembolso.desembolsos">
                                            <td>@{{ item.RFHA }}</td>
                                            <td>@{{ item.DBLFZ }}</td>
                                            <td>@{{ item.DELFZ }}</td>
                                            <td>@{{ item.TPINTREF1 }}</td>
                                            <td>@{{ item.TPINTREF2 }}</td>
                                            <td>@{{ item.BBLFZ }}</td>
                                            <td>
                                                <button @click='btnVerDetalleDesembolso_click(item)' data-toggle="tooltip" title="Ver detalle del desembolso" class="btn btn-outline-secondary btn-sm">

                                                    <i class="fa fa-bars"></i>
                                                </button>
                                            </td>
                                        </tr>


                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="text-center" v-if="spinners.verDetalleDesembolso" style="margin-bottom: 2%;">
                    <div class="spinner-border " style="width: 3rem; height: 3rem;" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <!-- <div class="form-row" v-if='datosDesembolso.flujoCajaDesembolso.length > 0'> -->
                <div class="form-row" v-if="disableEnableElements.verProyecciones">
                    <div class="col-12">
                        <div class="form-group">
                            <h5> <span class="badge badge-secondary">Flujo de caja @{{ tituloFlujoCaja }} </span></h5>
                            <div class="table-responsive" style="border: 1px solid #E8E8E8; background-color: #F8F8F8; max-height:450px; overflow: auto;">
                                <table class="table table-striped table-bordered tableFixed" style="word-wrap: break-word;">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Fecha</th>
                                            <th scope="col">Concepto</th>
                                            <th scope="col">Amortización</th>
                                            <th scope="col">Interes</th>


                                            <th scope="col">Total Ser. deuda</th>
                                            <th scope="col">Saldo deuda</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in datosDesembolso.flujoCajaDesembolso">
                                            <td>@{{ index }}</td>
                                            <td>@{{ item.DFAELL }}</td>
                                            <td>@{{ item.SFHAZBA }} - @{{ item.DESC_SFHAZBA }}</td>
                                            <td>@{{ item.AMORT_CAPITAL | currency }}</td>
                                            <td>@{{ item.INTERES | currency }}</td>
                                            <!-- <td>@{{ item.INT_VALUE }}</td> -->
                                            <td>@{{ item.SERV_DEUDA | currency }}</td>
                                            <td>@{{ item.SALDO_DEUDA | currency }}</td>
                                        </tr>
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row" v-if="disableEnableElements.verProyecciones">
                    <!--  v-if='datosDesembolso.proyecciones.length > 0' -->

                    <div class="col-12">
                        <div class="form-group">
                            <h5> <span class="badge badge-secondary">Proyecciones de la línea</span></h5>

                            <div class="table-responsive" style="border: 1px solid #E8E8E8; background-color: #F8F8F8; max-height:450px; overflow: auto;">
                                <table class="table table-striped table-bordered tableFixed" style="word-wrap: break-word;">
                                    <thead>
                                        <tr>
                                            <th scope="col" style="width: 5%;">#</th>
                                            <th scope="col" style="width: 10%;">Fecha</th>
                                            <th scope="col" style="width:12%;">Tasa ref.</th>
                                            <th scope="col">SPREAD</th>
                                            <th scope="col">DÍAS</th>
                                            <th scope="col">TASA NOMINAL </th>
                                            <th scope="col">TASA EFECTIVA</th>
                                            <th scope="col">VALOR $</th>

                                        </tr>
                                    </thead>
                                    <tbody>

                                        <tr v-for="(item, index) in datosDesembolso.proyecciones">
                                            <td scope="form-row">@{{ index }}</td>
                                            <td scope="form-row">@{{ item.IRA_DATE }}</td>

                                            <!-- TASA REF -->
                                            <td style="width: 15%;">
                                                <input type="number" :disabled="item.ENABLE == false" v-model="item.INT_VALUE" @keypress='validarMaxLength($event, 6)' class="form-control form-control-sm" @change="cambioValores(item)" />
                                            </td>
                                            <!-- SPREAD -->
                                            <td style="width: 15%;"><input :disabled="item.ENABLE == false" type="number" v-model="item.TPINTREF2" @keypress='validarMaxLength($event, 6)' class="form-control form-control-sm" @change="cambioValores(item)" />
                                            </td>
                                            <!-- NUM_DIAS_TASA_REF -->
                                            <td style="width: 15%;"><input :disabled="item.ENABLE == false" type="number" v-model="item.NUM_DIAS_TASA_REF" @keypress='validarMaxLength($event, 3)' class="form-control form-control-sm" @change="cambioValores(item)" />
                                            </td>
                                            <td><input type="text" v-model="item.TASA_NTV" class="form-control form-control-sm" :disabled="true" /></td>
                                            <td><input type="text" v-model="item.TASA_EA_TV" :disabled="true" class="form-control form-control-sm" /></td>
                                            <td>@{{ item.VALOR | currency }}</td>

                                        </tr>

                                    </tbody>

                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <hr />
                <div class="form-row">
                    <div class="col-8">
                        <div class="form-group">
                            <button v-if="disableEnableElements.btnActualizarProyecciones" type="button" @click="enviarProyeccionesCambiadas()" class="btn btn-success btn-sm">
                                <span v-if="spinners.actualizarProyecciones" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                Actualizar
                            </button>
                           <!-- <button type="button" onClick="window.location.reload();" class="btn btn-success btn-sm">Recargar</button> -->
                            <button type="button" v-if="disableEnableElements.verBtnGenerarExcel" @click="generarExcel_click" class="btn btn-success btn-sm">Generar
                                Excel</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>




    </div>

</div>

<script type="application/javascript">
    const vueApp = new Vue({
        el: '#vueapp',
        // mixins: [],
        mixins: [myMixin, Vue2Filters.mixin],
        data: {
            formCargado: false,

            spinners: {
                buscarLineaSap: false,
                verDetalleDesembolso: false,
                actualizarProyecciones: false,
                verDetalleLinea: false
            },
            datosDesembolso: {
                numDesembolso: '',
                info: '',
                desembolsos: [],
                flujoCajaDesembolso: [],
                proyecciones: []
            },
            disableEnableElements: {
                btnActualizarProyecciones: false,
                verDesembolsos: false,
                verProyecciones: false,
                verBtnGenerarExcel: false
            },

            tituloFlujoCaja: ''

        },
        components: {
            vuejsDatepicker
        },

        methods: {

            cambioValores(item) {
                item['cambio_valores'] = true;
                console.log({
                    cambioValores: item
                });
            },

            generarExcel_click() {
                console.log({
                    datosDesembolso: this.datosDesembolso
                });
                const controller = 'creditos/ajaxGenerarExcelDesembolsoSap';
                const datos = this.datosDesembolso;
                const data = new FormData();
                data.append('num_desembolso', this.datosDesembolso.numDesembolso);
                data.append('file', new Blob([JSON.stringify(datos)], {
                    type: 'application/json'
                }));
                // data.append('file', JSON.stringify(datos));
                this.enviarDatos(data, controller)
                    .then(response => {
                        const data = response.data;
                        if (data.success && !_.isEmpty(data.archivoExcel)) {
                            window.open(data.archivoExcel, '_blank').focus();
                            // this.errors = {};
                        } else {
                            this.showMsgBoxOne(data.message);
                        }
                    }).catch(error => {
                        this.stopSpinners();
                        const msg = 'Error al intentar recuperar información de la línea';
                        const errors = this.errorHandling(error, msg);
                        this.showMsgBoxOne(errors);
                        console.log(errors);
                    });

                return false;
            },

            stopSpinners() {
                this.spinners.buscarLineaSap = false;
                this.spinners.verDetalleDesembolso = false;
                this.spinners.actualizarProyecciones = false;
            },

            btnBuscarLineaSap_click() {
                this.spinners.buscarLineaSap = true;
                this.disableEnableElements.verDesembolsos = false;
                this.disableEnableElements.verProyecciones = false;
                this.disableEnableElements.verDetalleLinea = false;
                this.disableEnableElements.verBtnGenerarExcel = false;
                this.disableEnableElements.btnActualizarProyecciones = false;

                const numLinea = this.datosDesembolso.RFHA;
                if (_.isEmpty(numLinea)) {
                    this.stopSpinners();
                    this.showMsgBoxOne('Debe ingresar un número de línea');
                    return;
                }
                const controlador = "creditos/ajaxObtenerInfoLineaSap/" + numLinea;
                this.cargarDatos(controlador, {}).
                then(response => {
                    const data = response.data;

                    this.cargarInfoDesembolso(data);
                    this.disableEnableElements.btnActualizarProyecciones = false;
                    this.disableEnableElements.verDesembolsos = true;
                    this.disableEnableElements.verDetalleLinea = true;
                    // this.showMsgBoxOk('El credito no tiene desembolsos registrados');
                    this.stopSpinners();
                }).catch(error => {
                    this.disableEnableElements.verDesembolsos = false;
                    this.stopSpinners();

                    const errors = this.errorHandling(error, 'Error al intentar recuperar información de la línea');
                    let msg = '';
                    if (errors == undefined) {
                        msg += errors.msg;
                    } else {
                        msg += 'Error: no fue posible obtener la información de la línea desde SAP. ';
                        msg += 'Debe consultar al administrador del sistema.';
                    }

                    //let
                    this.showMsgBoxOne(msg);
                    console.log(errors);
                });

                return false;
            },

            btnVerDetalleDesembolso_click(item) {
                console.log({
                    item: item
                });
                this.disableEnableElements.btnActualizarProyecciones = false;
                this.spinners.verDetalleDesembolso = true;
                this.disableEnableElements.verProyecciones = false;
                this.disableEnableElements.verBtnGenerarExcel = false;
                const numDesembolso = item.RFHA;

                this.datosDesembolso.numDesembolso = numDesembolso;

                if (!numDesembolso) {
                    alert('Debe especificar un número de desembolso');
                    return false;
                }
                const controlador = "creditos/ajaxObtenerDetalleDesembolsoSap/" + numDesembolso;
                this.cargarDatos(controlador, {}).
                then(response => {
                    const data = response.data;
                    this.cargarInfoDetalleDesembolso(data);
                    this.disableEnableElements.btnActualizarProyecciones = true;
                    this.disableEnableElements.verProyecciones = true;
                    this.disableEnableElements.verBtnGenerarExcel = true;
                    this.tituloFlujoCaja = numDesembolso;
                    // this.cargarInfoDesembolso(data);
                    // this.showMsgBoxOk('El credito no tiene desembolsos registrados');
                    this.stopSpinners();
                }).catch(error => {
                    this.disableEnableElements.verProyecciones = false;
                    this.stopSpinners();
                    //console.log(error);
                    const errors = this.errorHandling(error, 'Error al intentar recuperar información de la línea');
                    let msg = '';
                    if (errors == undefined) {
                        msg += errors.msg;
                    } else {
                        msg += 'Error: no fue posible obtener la información de la línea desde SAP. ';
                        msg += 'Debe consultar al administrador del sistema.';
                    }
                    this.showMsgBoxOne(errors);
                    console.log(errors);
                });

                return false;
            },

            /*  btnVerDetalleDesembolso_click(id) {
                  const arrayFlujoCaja = this.datosDesembolso.desembolsos[0].FLUJO_DESEMBOLSO;
                  this.datosDesembolso.flujoCajaDesembolso = arrayFlujoCaja;

                  console.log({
                      FlujoCajaDesembolso: arrayFlujoCaja
                  });
              }, */

            cargarInfoDesembolso(data) {
                this.datosDesembolso.desembolsos = data.DATOS_LINEA.DESEMBOLSOS;
                // this.datosDesembolso.flujoCajaDesembolso = data.DESEMBOLSOS[0].FLUJO_DESEMBOLSO;
                this.datosDesembolso.info_linea = data.DATOS_LINEA.INFO_LINEA;
                //  this.datosDesembolso.proyecciones = data.PROYECCIONES;



            },

            cargarInfoDetalleDesembolso(data) {
                // this.datosDesembolso.desembolsos = data.DESEMBOLSOS;
                this.datosDesembolso.flujoCajaDesembolso = data.T_FLUJOCAJA;
                //  this.datosDesembolso.info_linea = data.INFO_LINEA;
                this.datosDesembolso.proyecciones = data.PROYECCIONES;
                this.disableEnableElements.verBtnGenerarExcel = false;
                this.disableEnableElements.btnActualizarProyecciones = true;

            },

            gotoUrl(item) {
                console.log({
                    item: item
                });
                // this.redirectUrl
            },


            obtenerFilasCambiadas(listado) {
                /*  console.log({obtenerFilasCambiadas: listado });
                  const filasCambiadas1 = Object.values(listado).filter(function(item) {
                      console.log({itemCambiado: item });
                      if(item.cambio_valores == true) {
                      return item;
                      }
                  }); */

                const filasCambiadas = listado.filter(item => item.cambio_valores == true);
                return filasCambiadas;
            },


            enviarProyeccionesCambiadas() {

                this.spinners.actualizarProyecciones = true;

                const cuotasCambiadas = this.obtenerFilasCambiadas(this.datosDesembolso.flujoCajaDesembolso);
                const proyeccionesCambiadas = this.obtenerFilasCambiadas(this.datosDesembolso.proyecciones);


                const datos = {
                    proyecciones: proyeccionesCambiadas,
                    cuotas: cuotasCambiadas,
                    RFHA: Number(this.datosDesembolso.info_linea.RFHA)
                };

                console.log({
                    datosEnviados: datos
                });


                const controlador = 'creditos/ajaxGuardarActualizarProyeCuotasLineaSap';

                this.enviarDatos(datos, controlador, 'POST')
                    .then(response => {
                        const data = response.data;
                        this.stopSpinners();
                        this.actualizarProyecciones(data.proyecciones);
                        this.actualizarCuotas(data.proyecciones);
                    }).catch(error => {
                        this.stopSpinners();
                        console.log(error);
                    });

                return false;
            },

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

                        if (item.ID_CUOTA == cuota.ID && item.cambio_valores == true) {
                            cuota.INTERES = item.VALOR;
                            //SERVICIO DEUDA
                            cuota.SERV_DEUDA = item.VALOR
                            console.log({
                                cuotaActualizada: cuota
                            });
                            return;
                        }
                    });
                });
            }

        },
        mounted() {
            //EVENTO LANZADO TAN PRONTO CARGA EL FORMULARIO
            window.addEventListener('load', () => {

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
