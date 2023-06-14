@extends('layouts.default')
@section('content')

<div class="card" id="vueapp" v-cloak>
    <div class="card-header">
        <b>Tipos de vinculación</b>
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
                <div class="col-md-12">
                    <div class="form-group">
                        <form method="get" action="{{ URL::to('tipo_vinculaciones/index') }}">
                            <div class="form-row">
                                <div class="col-md-4 col-xs-4 col-md-4 col-lg-4">
                                    <input type="text" autocomplete="off" name="titulo" maxlength="25" v-model="filtros.filtroTitulo" class="form-control form-control-sm" placeholder="ID o Palabra clave">
                                </div>

                                <div class="col">
                                    <div class="float-left">
                                        <button class="btn btn-secondary btn-sm" type="submit">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                                        <a href="{{ URL::to('tipo_vinculaciones/index') }}" class="btn btn-secondary btn-sm">Limpiar filtros</a>
                                    </div>
                                    <input type="hidden" name="filtrar" value="true" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="row">

                <div class="col-12">
                    <div class="form-group">

                        <table class="table table-bordered table-hover ">
                            <thead>
                                <tr>


                                    <th scope="col">Descripción</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $index = 0;
                                foreach ($tiposVinculacion as $tipoVinculacion) :

                                ?>

                                    <tr>

                                        <td><?= $tipoVinculacion->descripcion ?></td>

                                        <td>

                                            <a @click='mostrarModalFormBanco_click("EDIT",<?= $tipoVinculacion ?>)' class="btn btn-outline-secondary btn-sm">
                                                <span class="fa fa-edit"></span>
                                            </a>
                                            <a @click='borrarBanco_click(<?= $tipoVinculacion->id ?>)' class="btn btn-outline-secondary btn-sm">
                                                <span class="fa fa-trash"></span>
                                            </a>
                                        </td>
                                    </tr>


                                <?php $index++;
                                endforeach;
                                ?>

                            </tbody>

                        </table>

                        {{ $tiposVinculacion->links('pagination::bootstrap-4') }}

                    </div>

                </div>
            </div>
            <hr />
            <div class="row">
                <div class="col-8">
                    <div class="form-group">
                        <button @click="mostrarModalFormBanco_click('ADD')" class="btn btn-secondary btn-sm">Nuevo tipo</button>
                        <a href="{{ URL::to('tipo_vinculaciones/index') }}" class="btn btn-secondary btn-sm">Recargar listado</a>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <!-- MODAL FORM BANCOS -->
    <b-modal id="modalTipoVinculacion" title="Tipo de vinculación" scrollable>
        <div>
            <!-- FILA  -->
            @include('elements.alertMsgJs')
            <div class="form-row">

                <div class="col-12">
                    <div class="form-group">
                        <label for="txtDescripcion"><b>Descripción</b></label>
                        <input type="text" maxlength="150" autocomplete="off" v-model="modalTipoVinculacion.descripcion" class="form-control form-control-sm" name="txtDescripcion" placeholder=""></input>
                        <span v-if="errors?.descripcion" class="custom_error">@{{ errors . descripcion }}</span>
                    </div>
                </div>

            </div>


        </div>

        <template #modal-footer="{ ok, cancel, hide }">

            <button v-if="!editarTipo" class="btn btn-success btn-sm" @click="guardarTipoVinculacion_click">
                <span v-if="spinners.guardarTipo" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Guardar
            </button>
            <button v-if="editarTipo" class="btn btn-success btn-sm" @click="editarTipoVinculacion_click">
                <span v-if="spinners.editarTipo" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Editar
            </button>
            <b-button size="sm" variant="secondary" @click="cancel()">
                Cerrar
            </b-button>

        </template>
    </b-modal>
</div>


<script type="application/javascript">
    const vueData = new Vue({
        el: '#vueapp',
        // mixins: [],
        mixins: [myMixin, Vue2Filters.mixin],
        data: {
            formCargado: false,
            filtros: {
                filtroTitulo: <?= "'" . $filtroTitulo . "'"  ?>
            },
            modalTipoVinculacion: {
                id: '',
                descripcion: ''
            },
            spinners: {
                guardarTipo: false,
                editarTipo: false
            },
            editarTipo: false
        },

        components: {
            // vuejsDatepicker
        },

        methods: {

            guardarTipoVinculacion_click() {
                const path = 'tipo_vinculaciones/ajaxGuardarRegistro';
                const datos = this.modalTipoVinculacion;
                this.enviarDatosForm(datos, path, 'POST', true);
            },

            editarTipoVinculacion_click() {
                const path = 'tipo_vinculaciones/ajaxEditarRegistro/' + this.modalTipoVinculacion.id;
                const datos = this.modalTipoVinculacion;
                this.enviarDatosForm(datos, path, 'PUT', true);
            },

            borrarBanco_click(id) {
                const result = confirm('¿Está seguro?');
                if (result) {
                    const path = 'tipo_vinculaciones/ajaxBorrarRegistro/' + id;
                    const datos = this.modalTipoVinculacion;
                    this.enviarDatosForm({}, path, 'DELETE', true);
                }
            },

            enviarDatosForm(datos, path, tipo, reload = false) {
               /* console.log({
                    datosEnviados: datos
                }); */
                this.enviarDatos(datos, path, tipo)
                    .then(response => {
                        const data = response.data;
                        this.showMsgBoxOne(data.message);
                        this.errors = {};
                        if (reload) {
                            window.location.reload();
                        }
                    }).catch(error => {
                        console.log({
                            error: error
                        });
                        const errorMsg = this.errorHandling(error, 'Error al intentar realizar la operación');
                        this.showAlertMsg(errorMsg, 'error');
                    });

                    return false;
            },

            mostrarModalFormBanco_click(tipo = 'ADD', item = null) {

                this.modalTipoVinculacion.id = '';
                this.modalTipoVinculacion.descripcion = '';
                this.editarTipo = false;

                if (tipo === 'ADD') {
                    this.$bvModal.show('modalTipoVinculacion');
                } else {
                    this.modalTipoVinculacion.id = item.id;
                    this.modalTipoVinculacion.descripcion = item.descripcion;
                    this.editarTipo = true;
                }

                this.$bvModal.show('modalTipoVinculacion');
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
