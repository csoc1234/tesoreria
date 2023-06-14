@extends('layouts.default') @section('content')
    <div class="card" id="vueapp" v-cloak>
        <div class="card-header">
            <b> Listado de roles</b>
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

                    <div class="col-6">
                        <div class="form-group">

                            <select class="form-control form-control-sm" @change='seleccionarRol_change($event)'
                                v-model="datosFormulario.rol_id">
                                <option value="0" selected>Seleccione una opción</option>
                                <option v-for="(item, index) in listados.roles" :value="item.id">
                                    @{{ item.descripcion }}
                                </option>
                            </select>
                            <span v-if="errors?.id" class="custom_error">@{{ errors.id }}</span>
                        </div>
                        <div class="text-center" v-if="spinners.cargandoRoles">
                            <div class="spinner-border " style="width: 3rem; height: 3rem;" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>

                    </div>

                </div>
                <div class="form-row">
                    <div class="col-8">
                        <div class="form-group">
                            <table class="table table-striped table-bordered " v-if="listados.permisos.length > 0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Permiso</th>
                                        <th>Activado</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in listados.permisos" :value="item.id">
                                        <td> @{{ index + 1 }}</td>
                                        <td> @{{ item.descripcion }}</td>
                                        <td><input type="checkbox" v-model="item.activado" /></td>

                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <hr />
                <div class="form-row">
                    <div class="col-12">
                        <div class="form-group">

                            <button name="btnGuardar" class="btn btn-success btn-sm" @click="actualizarRolPermisos_click">
                                <span v-if="spinners.actualizarRoles" class="spinner-border spinner-border-sm"
                                    role="status" aria-hidden="true"></span>
                                Guardar cambios
                            </button>
                            <button @click="mostrarModalRolUsuario_click('NUEVO_ROL')" name="btnGuardar"
                                class="btn btn-secondary btn-sm">
                                Nuevo rol
                            </button>
                            <button @click="mostrarModalRolUsuario_click('CAMBIAR_NOMBRE_ROL')" name="btnGuardar"
                                class="btn btn-secondary btn-sm">
                                Cambiar nombre rol
                            </button>
                            <button @click="borrarRol_click()" name="btnGuardar" class="btn btn-secondary btn-sm">
                                <span v-if="spinners.borrarRol" class="spinner-border spinner-border-sm" role="status"
                                    aria-hidden="true"></span>
                                Borrar rol
                            </button>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        @include('includes.usuarios.modal_rol_usuario')
    </div>

    <script type="application/javascript">
    const vueData = new Vue({
        el: '#vueapp',
        // mixins: [],
        mixins: [myMixin, Vue2Filters.mixin],
        data: {
            //CAMPOS DEL FORMULARIO
            datosFormulario: {
                descripcion: '',
                rol_id: 0
            },
            spinners: {
                editarDescripcionRol: false,
                cargandoRoles: false,
                actualizarRoles: false,
                guardarCambios: false,
                guardarRol: false,
                borrarRol: false
            },
            rol_id: 0,
            listados: {
                permisos: [],
                roles: [],
                rolPermisos: []
            },
            tipoPeticion: '',
            errors: {},
            formCargado: false
        },
        components: {
            // vuejsDatepicker
        },

        methods: {
            cargarRoles() {
                // this.cargarRegistros('roles', 'roles');
                const controlador = 'general/ajaxCargarRoles';
                this.cargarRegistros(controlador, 'roles');
            },


            cargarPermisos() {
                // this.cargarRegistros('roles', 'roles');
                const controlador = 'general/ajaxCargarRegistros/permisos';
                this.cargarRegistros(controlador, 'permisos');
            },

            guardarRol_click() {

                if (
                    _.isEmpty(this.datosFormulario.descripcion)
                    ) {
                    const msg = 'Primero debe escribir el nombre del rol';
                    this.errors = {};
                    this.errors.descripcion = msg;
                    //this.showMsgBoxOne(msg, 'Notificación');
                    return false;
                }

                const controlador = 'usuarios/ajaxGuardarRol';
                //const tempPermisos = [];
                //const listado = this.listados.permisos;
                const datos = {
                    descripcion: this.datosFormulario.descripcion,
                };
                this.spinners.guardarRol = true;

                this.enviarDatos(datos, controlador)
                    .then(response => {
                        const data = response.data;
                        const rolGuardado = data.entity;
                        this.showMsgBoxOne(data.message);
                        this.pararSpinners();
                        this.listados.roles.push(rolGuardado);
                        this.$bvModal.hide('modalRolUsuario');
                        this.errors = {};
                    }).catch(error => {
                        this.pararSpinners();
                        const msg = this.errorHandling(
                            error,
                            'Error al intentar realizar la operación'
                        );
                        this.showMsgBoxOne(msg, 'error');
                    });
                    return false;
            },

            /* seleccionarRol_change(event) {
                 const rol_id = event.target.value;

                 console.log({
                     rolSeleccionado: rol_id
                 });

                 if (!_.isEmpty(rol_id)) {
                     this.cargarRolPermisos(rol_id);
                 }

             }, */

            pararSpinners() {
                this.spinners.cargandoRoles = false;
                this.spinners.actualizarRoles = false;
                this.spinners.guardarCambios = false;
                this.spinners.guardarRol = false;
                this.spinners.borrarRol = false;
            },

            seleccionarRol_change(event) {
                const rol_id = event.target.value;
                const descripcion= event.target.options[event.target.selectedIndex].text;

                if (!_.isEmpty(rol_id) && Number(rol_id) > 0) {
                    this.datosFormulario.rol_id = rol_id;
                    this.datosFormulario.descripcion = descripcion;
                    this.spinners.cargandoRoles = true;
                    const controlador = 'usuarios/ajaxObtenerRolPermisos/' + rol_id;
                    this.cargarRegistros(controlador, 'permisos');
                } else {
                    this.listados.permisos = [];
                }

              //  this.listados.permisos = [];
            },

            editarDescripcionRol_click() {

                if (_.isEmpty(this.datosFormulario.rol_id) || Number(this.datosFormulario.rol_id) == 0) {
                    const msg = 'Primero debe seleccionar un rol';
                    this.showMsgBoxOne(msg, 'Notificación');
                    return false;
                }

                const controlador = 'usuarios/ajaxEditarNombreRol/'+this.datosFormulario.rol_id;

                const formData = new FormData();
                formData.append('rol_id', this.datosFormulario.rol_id);
                formData.append('descripcion', this.datosFormulario.descripcion);
                formData.append('_method', 'patch');
                /*const datos = {
                    rol_id: Number(this.datosFormulario.rol_id),
                    descripcion: this.datosFormulario.descripcion
                };*/

               this.spinners.editarDescripcionRol = true;

                this.enviarDatos(formData, controlador)
                    .then(response => {
                        const data = response.data;
                        this.showMsgBoxOne(data.message);
                        this.$bvModal.hide('modalRolUsuario');
                        this.actualizarNombreRol(data.entity)
                        this.pararSpinners();
                    }).catch(error => {
                        this.pararSpinners();
                        const msg = this.errorHandling(
                            error,
                            'Error al intentar realizar la operación'
                        );
                        this.showMsgBoxOne(msg, 'error');

                    });

                    return false;

            },

            actualizarNombreRol(entity) {
                const arr = this.listados.roles;

                const itemIndex = arr.findIndex(
                    item => item.id === entity.id
                );

                if (itemIndex !== -1) {
                  this.listados.roles[itemIndex].descripcion  = entity.descripcion;
                }
            },

            borrarRol_click() {

                if (
                    _.isEmpty(this.datosFormulario.rol_id) ||
                    Number(this.datosFormulario.rol_id) == 0
                    ) {
                    const msg = 'Primero debe seleccionar un rol';
                    this.showMsgBoxOne(msg, 'Notificación');
                    return false;
                }

                    let msg = '¿Está seguro(a)? ';

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
                            console.log({
                                _value: value
                            })
                            if (value == true) {

                                setTimeout(() => {
                                    this.borrarRol();
                                }, 0);

                            }
                        })
                        .catch(err => {
                            // An error occurred
                        });

                        return false;
            },

            borrarRol() {

            this.tipoPeticion = 'BORRAR_ROL';

               if (_.isEmpty(this.datosFormulario.rol_id) || Number(this.datosFormulario.rol_id) == 0) {
                   const msg = 'Primero debe seleccionar un rol';
                   this.showMsgBoxOne(msg, 'Notificación');
                   return false;
               }

               //console.log({borrarRol_click: this.datosFormulario});

               const controlador = 'usuarios/ajaxBorrarRol/'+this.datosFormulario.rol_id;

              /* const formData = new FormData();
               formData.append('rol_id', this.datosFormulario.rol_id);
               formData.append('descripcion', this.datosFormulario.descripcion);
               formData.append('_method', 'patch');]/
               /*const datos = {
                   rol_id: Number(this.datosFormulario.rol_id),
                   descripcion: this.datosFormulario.descripcion
               };*/

              this.spinners.borrarRol = true;

               this.cargarDatos(controlador, {}, 'DELETE')
                   .then(response => {
                       const data = response.data;
                       this.showMsgBoxOne(data.message);
                       const rolId = data.rol_id;
                       this.borrarRolDelListadoPorRolId(rolId);
                       this.datosFormulario.rol_id = 0;
                       this.listados.permisos = [];
                       this.pararSpinners();
                   }).catch(error => {
                       this.pararSpinners();
                       const msg = this.errorHandling(
                           error,
                           'Error al intentar realizar la operación'
                       );
                       this.showMsgBoxOne(msg, 'Notificación');

                   });

                   return false;
           },

           borrarRolDelListadoPorRolId(rolId) {
             const arr = this.listados.roles;
             const newArray = arr.filter(object => {
                    //console.log({object: object });
                    return object.id !== rolId;
                });

                this.listados.roles = [];
                this.listados.roles = newArray;
            },

            actualizarRolPermisos_click() {
             //   console.log({datosFormulario: this.datosFormulario.rol_id});
                if (_.isEmpty(this.datosFormulario.rol_id) || Number(this.datosFormulario.rol_id) == 0) {
                    const msg = 'Primero debe seleccionar un rol';
                    this.showMsgBoxOne(msg, 'Notificación');
                    return false;
                }

                const controlador = 'usuarios/ajaxActualizarRolPermisos';
                //const tempPermisos = [];
                //const listado = this.listados.permisos;
                const datos = {
                    rol_id: Number(this.datosFormulario.rol_id),
                    permisos: this.listados.permisos,
                };

                this.spinners.actualizarRoles = true;
                this.enviarDatos(datos, controlador)
                    .then(response => {
                        const data = response.data;
                        this.showMsgBoxOne(data.message);
                        this.pararSpinners();
                    }).catch(error => {
                        this.pararSpinners();
                        const msg = this.errorHandling(
                            error,
                            'Error al intentar realizar la operación'
                        );
                        this.showAlertMsg(msg, 'error');

                    });

                    return false;
            },

            mostrarModalRolUsuario_click(tipoPeticion) {
                this.tipoPeticion = tipoPeticion;
               /* console.log({
                    tipoPeticion: tipoPeticion
                });
                console.log({
                    datosFormulario: this.datosFormulario
                });*/

                if (tipoPeticion == 'CAMBIAR_NOMBRE_ROL') {

                    if (_.isEmpty(this.datosFormulario.rol_id) ||
                        Number(this.datosFormulario.rol_id) == 0) {
                        const msg = 'Primero debe seleccionar un rol';
                        this.showMsgBoxOne(msg, 'Notificación');
                        return false;
                    }

                    if (_.isEmpty(this.datosFormulario.descripcion)) {
                        const msg = 'Debe especificar la descripción del rol';
                        this.showMsgBoxOne(msg, 'Notificación');
                        return false;
                    }

                } else if(tipoPeticion == 'NUEVO_ROL')  {
                  this.errors = {};

                }

              console.log({datosFormulario: vueData.datosFormulario });

               // vueData.datosFormulario.descripcion = '';
               // console.log({tipoPeticion: tipoPeticion });
                this.$bvModal.show('modalRolUsuario');
            },


            verificarPermisoAsignado(rolPermisos, permiso) {
                // const rolPermisos = this.listados.permisos;
                for (index in rolPermisos) {
                    const item = rolPermisos[index];
                    if (item.id === permiso.id) {
                        return true;
                    }
                }
                /* Object.keys(rolPermisos).forEach(index => {
                     const item = lstPermisos[index];

                 });*/
            },

            cargarRegistros(controlador, listado, params = {}) {
                //  const ruta = 'general/ajaxCargarRegistros/' + tabla;
                //  const params = {};
                this.cargarDatos(controlador, params)
                    .then(response => {
                        this.listados[listado] = response.data.listado;
                        this.pararSpinners();
                    })
                    .catch(error => {
                        this.pararSpinners();
                        const msg = this.errorHandling(
                            error,
                            'Error al intentar realizar la operación'
                        );
                        this.showAlertMsg(msg, 'error');
                    }).finally(() => {

                    });

                    return false;
            },



        },
        mounted() {
            //EVENTO LANZADO TAN PRONTO CARGA EL FORMULARIO
            window.addEventListener('load', () => {
                this.cargarRoles();
                //  this.cargarPermisos();
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
