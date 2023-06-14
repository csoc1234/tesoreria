@extends('layouts.default') @section('content')
<div class="card" id="vueapp" v-cloak>
    <div class="card-header">
        <b>
            Perfil de usuario

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
            <h5> <span class="badge badge-secondary">Datos básicos</span></h5>
            <div class="form-row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtIdentificacion"><b>Identificación *</b></label>
                        <input :disabled="true" type="text" autocomplete="off" maxlength="15" class="form-control form-control-sm" v-model="usuario.identificacion" id="txtIdentificacion" placeholder="">
                        <span v-if="errors?.identificacion" class="custom_error">@{{ errors . identificacion }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtNombres"><b>Nombres *</b></label>
                        <input type="text" autocomplete="off" maxlength="140" class="form-control form-control-sm" v-model="usuario.nombres" id="txtNombres" placeholder="">
                        <span v-if="errors?.nombres" class="custom_error">@{{ errors . nombres }}</span>
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtApellidos"><b>Apellidos *</b></label>
                        <input type="text" autocomplete="off" maxlength="140" class="form-control form-control-sm" id="txtApellidos" v-model="usuario.apellidos" placeholder="">
                        <span v-if="errors?.apellidos" class="custom_error">@{{ errors . apellidos }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="cboGenero"><b>Genero *</b></label>
                        <select class="form-control form-control-sm" v-model="usuario.genero_id">
                            <option value="0" selected>Seleccione una opción</option>
                            <option v-for="(item, index) in listados.generos" :value="item.id">
                                @{{ item . descripcion }}
                            </option>
                        </select>
                        <span v-if="errors?.genero_id" class="custom_error">@{{ errors . genero_id }}</span>
                    </div>
                </div>

            </div>

            <div class="form-row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtTelefono"><b>Teléfono</b></label>
                        <input type="number" autocomplete="off" @keypress='validarMaxLengthInt($event, 7)'  maxlength="7" class="form-control form-control-sm" id="txtTelefono" v-model="usuario.telefono" placeholder="">
                        <span v-if="errors?.telefono" maxlength="6" class="custom_error">@{{ errors . telefono }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtCelular"><b>Celular *</b></label>
                        <input type="number" autocomplete="off" @keypress='validarMaxLengthInt($event, 10)' maxlength="10" class="form-control form-control-sm" id="txtCelular" v-model="usuario.celular" placeholder="">
                        <span v-if="errors?.celular" class="custom_error">@{{ errors . celular }}</span>
                    </div>
                </div>
            </div>

            <div class="form-row">

                <div class="col-4">
                    <div class="form-group">
                        <label for="txtEmail"><b>Email *</b></label>
                        <input type="email" maxlength="140" @keypress='validarMaxLength($event, 140)' autocomplete="off" class="form-control form-control-sm" id="txtEmail" v-model="usuario.email" placeholder="Email">
                        <span v-if="errors?.email" class="custom_error">@{{ errors . email }}</span>
                    </div>
                </div>

                <div class="col-4">
                    <div class="form-group">
                        <label for="txtDireccion"><b>Dirección </b></label>
                        <input type="text" maxlength="500" autocomplete="off" class="form-control form-control-sm" id="txtDireccion" v-model="usuario.direccion" placeholder="">
                        <span v-if="errors?.direccion" class="custom_error">@{{ errors . direccion }}</span>
                    </div>
                </div>
            </div>

            <div class="form-row">


                <div class="col-4">
                    <div class="form-group">
                        <label for="txtPassword"><b>Nueva contraseña (opcional)</b></label>
                        <input type="password" maxlength="15" autocomplete="off" class="form-control form-control-sm" v-model="usuario.password" id="txtPassword" placeholder="">
                        <span v-if="errors?.password" class="custom_error">@{{ errors . password }}</span>
                    </div>

                </div>
            </div>


            <div class="form-row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="cboTipoVinculacion"><b>Tipo de vinculación *</b></label>
                        <select :disabled="true" class="form-control form-control-sm" v-model="usuario.tipo_vinculacion_id">
                            <option value="0" selected>Seleccione una opción</option>
                            <option v-for="(item, index) in listados.tipoVinculaciones" :value="item.id">
                                @{{ item . descripcion }}
                            </option>
                        </select>
                        <span v-if="errors?.tipo_vinculacion_id" class="custom_error">@{{ errors . tipo_vinculacion_id }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="cboTipoVinculacion"><b>Rol *</b></label>
                        <select :disabled="true" class="form-control form-control-sm" v-model="usuario.rol_id">
                            <option value="0" selected>Seleccione una opción</option>
                            <option v-for="(item, index) in listados.roles" :value="item.id">
                                @{{ item . descripcion }}
                            </option>
                        </select>
                        <span v-if="errors?.rol_id" class="custom_error">@{{ errors . rol_id }}</span>
                    </div>
                </div>
            </div>

            <div class="form-row">
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtFechaInicioContrato"><b>Fecha inicio vinculación *</b></label>
                        <vuejs-datepicker format="dd-MM-yyyy" :disabled="true" input-class="form-control form-control-sm" v-model="usuario.fecha_inicio_vinculacion" id="txtFechaInicioContrato" placeholder=""></vuejs-datepicker>
                        <span v-if="errors?.fecha_inicio_vinculacion" class="custom_error">@{{ errors . fecha_inicio_vinculacion }}</span>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label for="txtFechaFinContrato"><b>Fecha fin vinculación *</b></label>
                        <vuejs-datepicker format="dd-MM-yyyy" :disabled="true" input-class="form-control form-control-sm" v-model="usuario.fecha_fin_vinculacion" id="txtFechaFinContrato" placeholder=""></vuejs-datepicker>
                        <span v-if="errors?.fecha_fin_vinculacion" class="custom_error">@{{ errors . fecha_fin_vinculacion }}</span>
                    </div>
                </div>
            </div>
            <hr />
            <div class="form-row">
                <div class="col-12">
                    <div class="form-group">
                        <button name="btnGuardar" class="btn btn-success btn-sm" @click="editarPerfil_click">
                            <span v-if="spinners.enviarDatos" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Guardar cambios
                        </button>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<script type="application/javascript">
    const vueData = new Vue({
        el: '#vueapp',
        // mixins: [],
        mixins: [myMixin, Vue2Filters.mixin],
        data: {
            //CAMPOS DEL FORMULARIO
            usuario: {
                id: <?= !empty($usuario['id']) ?  "'" . $usuario['id'] . "'"  : "'0'" ?>,
                identificacion: <?= !empty($usuario['identificacion']) ?  "'" . $usuario['identificacion'] . "'"  : "''" ?>,
                nombres: <?= !empty($usuario['nombres']) ?  "'" . $usuario['nombres'] . "'"  : "''" ?>,
                apellidos: <?= !empty($usuario['apellidos']) ?  "'" . $usuario['apellidos'] . "'"  : "''" ?>,
                direccion: <?= !empty($usuario['direccion']) ?  "'" . $usuario['direccion'] . "'"  : "''" ?>,
                telefono: <?= !empty($usuario['telefono']) ?  "'" . $usuario['telefono'] . "'"  : "''" ?>,
                email: <?= !empty($usuario['email']) ?  "'" . $usuario['email'] . "'"  : "''" ?>,
                genero_id: <?= !empty($usuario['genero_id']) ?  "'" . $usuario['genero_id'] . "'"  : "'0'" ?>,
                rol_id: <?= !empty($usuario['rol_id']) ?  "'" . $usuario['rol_id'] . "'"  : "'0'" ?>,
                password: '',
                estado: <?= !empty($usuario['estado']) ? "'" . $usuario['estado'] . "'" : "'1'" ?>,
                celular: <?= !empty($usuario['celular']) ?  "'" . $usuario['celular'] . "'"  : "''" ?>,
                tipo_vinculacion_id: <?= !empty($usuario['tipo_vinculacion_id']) ?  "'" . $usuario['tipo_vinculacion_id'] . "'"  : "'0'" ?>,
                fecha_inicio_vinculacion: <?= !empty($usuario['fecha_inicio_vinculacion']) ?  "'" . $usuario['fecha_inicio_vinculacion'] . "'"  : "''" ?>,
                fecha_fin_vinculacion: <?= !empty($usuario['fecha_fin_vinculacion']) ?  "'" . $usuario['fecha_fin_vinculacion'] . "'"  : "''" ?>,
            },
            spinners: {
                enviarDatos: false
            },
            listados: {
                generos: [],
                tipoVinculaciones: [],
                permisos: [],
                roles: []
            },
            tipoPeticion: '',
            errors: {},
            formCargado: false
        },
        components: {
            vuejsDatepicker
        },

        methods: {

            enviarForm(ruta, method = 'POST') {
                //INICIAR SPINNER
                this.spinners.enviarDatos = true;
                // const ruta = 'usuarios/ajaxGuardarUsuario';
                this.enviarDatos(this.usuario, ruta, method)
                    .then(response => {
                        const data = response.data;
                        this.showMsgBoxOne(data.message);
                        this.errors = {};
                    })
                    .catch(error => {

                        const errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showAlertMsg(errorMsg, 'error');

                        this.spinners.enviarDatos = false;
                    }).finally(() => {
                        this.spinners.enviarDatos = false;
                    });

                    return false;
            },

         /*   eventoGuardarUsuario() {
                const ruta = 'usuarios/ajaxGuardarUsuario';
                this.enviarForm(ruta);
            },
            eventoEditarUsuario() {
                const ruta = 'usuarios/ajaxEditarUsuario/' + this.usuario.id;
                this.enviarForm(ruta, 'PUT');
            }, */

            editarPerfil_click() {
                const ruta = 'usuarios/ajaxEditarPerfil/' + this.usuario.id;
                this.enviarForm(ruta, 'PUT');
            },

            cargarGeneros() {
                this.cargarRegistros('generos', 'generos');
            },

            cargarPermisos() {
                this.cargarRegistros('roles', 'roles');
            },
            cargarRegistros(tabla, arreglo) {
                const ruta = 'general/ajaxCargarRegistros/' + tabla;
                const params = {};
                this.cargarDatos(ruta, params)
                    .then(response => {
                        this.listados[arreglo] = response.data.listado;
                    })
                    .catch(error => {
                        if (error.response !== undefined) {
                            const data = error.response.data;
                            alert('Error: ' + data.message);
                        } else {
                            alert('Error al intentar cargar el listado de ' + tabla);
                        }
                    }).finally(() => {

                    });

                    return false;
            },



            cargarTiposVinculacion() {
                const ruta = 'general/ajaxCargarRegistros/tipo_vinculacion'
                const params = {};
                this.cargarDatos(ruta, params)
                    .then(response => {
                        this.listados.tipoVinculaciones = response.data.listado;
                    })
                    .catch(error => {
                        if (error.response !== undefined) {
                            const data = error.response.data;
                            alert('Error: ' + data.message);
                        } else {
                            alert('Error al intentar cargar el listado de ' + tabla);
                        }
                    }).finally(() => {

                    });

                    return false;
            }


        },
        mounted() {
            //EVENTO LANZADO TAN PRONTO CARGA EL FORMULARIO
            window.addEventListener('load', () => {
                //Cargar generos
                this.cargarGeneros();
                this.cargarTiposVinculacion();
                this.cargarPermisos();

                this.usuario.fecha_inicio_vinculacion = moment(this.usuario.fecha_inicio_vinculacion).format('L');
                this.usuario.fecha_fin_vinculacion = moment(this.usuario.fecha_fin_vinculacion).format('L');
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
