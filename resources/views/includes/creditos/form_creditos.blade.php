@extends('layouts.default') @section('content')
<div id="vueapp" v-cloak>
    <div class="card">
        <div class="card-header">
            <b v-if="tipoAccion=='EDIT'">Editar crédito</b>
            <b v-if="tipoAccion=='ADD'">Registrar crédito</b>
        </div>

        <div class="card-body">
            <div class="text-center" v-if="!formCargado">
                <div class="spinner-border " style="width: 3rem; height: 3rem;" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>

            @include('elements.alertMsg')
            @include('elements.alertMsgJs')
            <!-- DATOS GENERALES -->
            <h5> <span class="badge badge-secondary">Datos del crédito a simular</span></h5>
            <div class="form-row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <b class="text-info" style="font-size: 13px;">Los campos marcados con * son obligatorios</b>
                    </div>
                </div>
            </div>
            <!-- FILA  -->
            <div class="form-row">
                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtNumOrdenanza"><b>Ordenanza *</b></label>
                        <input type="text" class="form-control form-control-sm" @keypress="validarMaxLength($event, 25)" v-model="credito.num_ordenanza" id="txtNumOrdenanza" name="txtNumOrdenanza" maxlength="25" placeholder="Número de ordenanza" />
                        <span v-if="errors?.num_ordenanza" class="custom_error">@{{ errors . num_ordenanza }}</span>
                    </div>
                </div>

                <!-- <div class="col-4">
                    <div class="form-group">
                        <label for="txtLinea"><b>Linea *</b></label>
                        <input type="text" class="form-control form-control-sm" id="txtLinea" v-model="credito.linea" name="txtLinea" maxlength="15" placeholder="Número/Linea" />
                        <span v-if="errors?.linea" class="custom_error">@{{ errors . linea }}</span>
                    </div>
                </div> -->
            </div>
            <!-- FILA  -->
            <div class="form-row">

                <div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtDescripcion"><b>Descripción</b></label>
                        <textarea class="form-control form-control-sm" id="txtDescripcion" @keypress="validarMaxLength($event, 500)" v-model="credito.descripcion" name="txtDescripcion" maxlength="500" placeholder=""></textarea>
                        <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                    </div>
                </div>

            </div>

            <!-- FILA -->
            <div class="form-row">
                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtValor"><b>Monto ($) *</b></label>
                        <input type="number" class="form-control form-control-sm" id="txtValor" @keypress="validarMaxLengthInt($event, 25)" v-model="credito.valor" name="txtValor" maxlength="15" placeholder="Valor total" /> @{{ credito.valor | currency }}

                        <span v-if="errors?.valor" class="custom_error"> <br />@{{ errors . valor }}</span>

                    </div>
                </div>
                <div class="col-2">
                    <div class="form-group">
                        <label for="txtFecha"><b>Fecha *</b></label>
                        <!-- <input type="date" class="form-control form-control-sm" id="txtFecha" v-model="credito.fecha" name="txtFecha" /> -->
                        <vuejs-datepicker format="dd-MM-yyyy" v-model='credito.fecha' input-class="form-control form-control-sm"></vuejs-datepicker>
                        <span v-if="errors?.fecha" class="custom_error">@{{ errors . fecha }}</span>
                    </div>
                </div>

            </div>
            <!-- FILA -->
            <div class="form-row">
                <div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label for="txtValor"><b>Estado </b></label>
                        <select class="form-control form-control-sm" v-model="credito.estado">
                            <option value="Activo">Activo</option>
                            <option value="Bloqueado">Bloqueado</option>
                        </select>
                        <span v-if="errors?.estado" class="custom_error"> <br />@{{ errors . estado }}</span>
                    </div>
                </div>

            </div>

            <hr />
            <!-- FILA -->
            <div class="form-row">
                <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                    @if(Gate::check('check-authorization', PERMISO_CREAR_CREDITO_SIMULADO))
                    <button v-if="tipoAccion=='ADD'" name="btnGuardar" class="btn btn-success btn-sm" @click="guardarCredito_click">
                        <span v-if="spinners.enviarDatos" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Guardar
                    </button>
                    @endif
                    @if(Gate::check('check-authorization', PERMISO_EDITAR_CREDITO_BANCARIO_SIMULADO))
                    <button v-if="tipoAccion=='EDIT'" name="btnEditar" class="btn btn-success btn-sm" @click="editarCredito_click">
                        <span v-if="spinners.enviarDatos" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Editar
                    </button>
                    @endif

                    @if(Gate::check('check-authorization', PERMISO_LISTAR_CREDITOS_SIMULADOS))
                    <a href="{{ route('creditos_simulados') }}" class="btn btn-secondary btn-sm">Listado créditos</a>
                    @endif
                </div>

            </div>



        </div>
    </div>
</div>
<script type="text/javascript">
    var vueApp = new Vue({
        el: '#vueapp',
        mixins: [myMixin, Vue2Filters.mixin],
        data: {

            spinners: {
                enviarDatos: false
            },

            credito: {
                id: 0,
                num_ordenanza: '',
                descripcion: '',
                valor: 0,
                fecha: '',
                estado: 'Activo'
            },

            tipoAccion: <?= "'" . $tipoAccion . "'"  ?>,
            formCargado: false,
            errors: {}
        },
        components: {
            vuejsDatepicker
        },
        methods: {
            limpiarFormulario() {
                this.credito.num_ordenanza = '';
                //  this.credito.linea = '';
                this.credito.descripcion = '';
                this.credito.valor = 0;
                this.credito.fecha = '';
            },

            guardarCredito_click() {
                const ruta = 'creditos/ajaxGuardarCreditoSimulado'
                const datos = this.credito;
                this.enviarDatosFormulario(ruta, datos);
            },

            editarCredito_click() {
                const ruta = 'creditos/ajaxEditarCreditoSimulado/' + this.credito.id;
                const datos = this.credito;
                this.enviarDatosFormulario(ruta, datos, 'PUT');
            },

            enviarDatosFormulario(ruta, datos, method = 'POST') {
                //INICIAR SPINNER
                this.spinners.enviarDatos = true;

                this.enviarDatos(datos, ruta, method)
                    .then(response => {
                        const data = response.data;
                        this.showMsgBoxOne(data.message);
                        if (this.tipoAccion === 'ADD') {
                            this.limpiarFormulario();
                        }
                        this.errors = {};
                    })
                    .catch(error => {
                        const msg = this.errorHandling(
                            error,
                            'Error al intentar realizar la operación'
                        );
                        this.showAlertMsg(msg, 'error');
                        this.spinners.enviarDatos = false;
                    }).finally(() => {
                        this.spinners.enviarDatos = false;
                    });
            },
        },
        mounted() {
            window.addEventListener('load', () => {
                if (this.tipoAccion === 'EDIT') {
                    const credito = <?= !empty($credito) ? $credito  : "''" ?>;
                    this.credito.id = credito.id;
                    this.credito.num_ordenanza = credito.num_ordenanza;
                    this.credito.descripcion = credito.descripcion;
                    this.credito.valor = credito.valor;
                    this.credito.fecha = moment(credito.fecha).format('L');
                    this.credito.estado = credito.estado;
                }
                this.formCargado = true;
            });


        }
    });
</script>

@endsection
