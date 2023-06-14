@extends('layouts.default') @section('content')
<div id="vueapp" v-cloak>
    <div class="card">
        <div class="card-header">
            <b>Crear desembolso</b>
        </div>

        <div class="card-body">
            @include('elements.alertMsg')
            @include('elements.alertMsgJs')
            <!-- DATOS GENERALES -->
            <h5> <span class="badge badge-secondary">Datos del desembolso</span></h5>

            <!-- FILA  -->
            <div class="form-row">
                <div class="col-3">
                    <div class="form-group">
                        <label for="txtDescripcion"><b>Descripción</b></label>
                        <input type="text" v-model="desembolso.descripcion" placeholder="Ej: Desembolso 1" class="form-control form-control-sm" id="txtDescripcion" name="txtDescripcion" />
                        <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                    </div>
                </div>
            </div>


            <!-- FILA  -->
            <div class="form-row">
                <div class="col-2">
                    <div class="form-group">
                        <label for="txtFechaInicio"><b>Fecha inicio (mes/día/año) *</b></label>
                        <input type="date" class="form-control form-control-sm" v-model="desembolso.fecha_inicio" id="txtFechaInicio" name="txtFechaInicio" />
                        <span v-if="errors?.fecha_inicio" class="custom_error">@{{ errors . fecha_inicio }}</span>
                    </div>
                </div>

                <div class="col-2">
                    <div class="form-group">
                        <label for="txtFechaInicio"><b>Fecha fin (mes/día/año) *</b></label>
                        <input type="date" class="form-control form-control-sm" v-model="desembolso.fecha_fin" id="txtFechaFin" name="txtFechaFin" />
                        <span v-if="errors?.fecha_fin" class="custom_error">@{{ errors . fecha_fin }}</span>
                    </div>
                </div>

            </div>
            <!-- FILA  -->
            <div class="form-row">

                <div class="col-2">
                    <div class="form-group">
                        <label for="txtValorDesembolso"><b>Valor desembolso *</b></label>
                        <input type="text" class="form-control form-control-sm" v-model="desembolso.valor" id="txtValorDesembolso" name="txtValorDesembolso" />
                        <span v-if="errors?.valor" class="custom_error">@{{ errors . valor }}</span> @{{ desembolso.valor | currency }}
                    </div>
                </div>



            </div>


            <hr />
            <!-- FILA -->
            <div class="form-row">
                <div class="col-10">
                    <button name="btnGuardar" class="btn btn-success btn-sm" @click="eventoGuardarDesembolso">
                        <span v-if="spinners.enviarDatos" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        Generar
                    </button>
                    <button @click="redirectUrl('simuladores/desembolsos/'+desembolso.emprestito_id)" class="btn btn-secondary btn-sm">Listado</button>
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

            desembolso: {
                descripcion: '',
                fecha_inicio: '',
                fecha_fin: '',
                valor: '',
                credito_banco_id: <?= !empty($creditoBancoId) ?  "'" . $creditoBancoId . "'"  : "'0'" ?>,
            },

            errors: {}
        },
        methods: {

            eventoGuardarCredito() {
                const ruta = 'simuladores/ajaxGuardarCreditoSimulado'
                const datos = this.credito;
                this.enviarDatosFormulario(ruta, datos);
            },


            enviarDatosFormulario(ruta, datos, method = 'POST') {
                //INICIAR SPINNER
                this.spinners.enviarDatos = true;
                // const ruta = 'usuarios/ajaxGuardarUsuario';
                this.enviarDatos(datos, ruta, method)
                    .then(response => {
                        const data = response.data;
                        this.showAlertMsg(data.message);
                        this.errors = {};
                    })
                    .catch(error => {

                        if (error.response !== undefined) {
                            const data = error.response.data;
                            console.log({
                                error: data.errors
                            });
                            if (error.response.status === 422) {
                                vueApp.errors = data.errors;
                            }

                        } else {
                            alert('Error al intentar realizar la operación');
                        }

                        this.spinners.enviarDatos = false;
                    }).finally(() => {
                        this.spinners.enviarDatos = false;
                    });
            },
            eventoGuardarDesembolso() {
                const ruta = 'simuladores/ajaxCalcularCuotasDesembolso'
                const datos = this.desembolso;
                this.enviarDatos(datos, ruta)
                    .then(response => {
                        const data = response.data;
                        this.showAlertMsg(data.message);
                        this.errors = {};
                    }).catch(error => {
                        if (error.response !== undefined) {
                            const data = error.response.data;
                            console.log({
                                error: data.errors
                            });
                            if (error.response.status === 422) {
                                vueApp.errors = data.errors;
                            }

                        } else {
                            alert('Error al intentar realizar la operación');
                        }
                    });
            }
        },
        mounted() {
            //  alert('Hola');
        }
    });
</script>

@endsection
