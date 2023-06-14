@extends('layouts.default')
@section('content')

<div class="card" id="vueapp" v-cloak>
    <div class="card-header">
        <b>Listado de bancos</b>
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
                        <form method="get" action="{{ URL::to('bancos/index') }}">
                            <div class="form-row">
                                <div class="col-md-4 col-xs-4 col-md-4 col-lg-4">
                                    <input type="text" autocomplete="off" name="titulo" maxlength="25" v-model="filtros.filtroTitulo" class="form-control form-control-sm" placeholder="ID o Palabra clave">
                                </div>

                                <div class="col">
                                    <div class="float-left">
                                        <button class="btn btn-secondary btn-sm" type="submit">
                                            <i class="fa fa-search"></i> Buscar
                                        </button>
                                        <a href="{{ URL::to('bancos/index') }}" class="btn btn-secondary btn-sm">Limpiar filtros</a>
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
                                foreach ($bancos as $banco) :

                                ?>

                                    <tr>

                                        <td><?= $banco->descripcion ?></td>

                                        <td>

                                            <a @click='mostrarModalFormBanco_click("EDIT",<?= $banco ?>)' class="btn btn-outline-secondary btn-sm">
                                                <span class="fa fa-edit"></span>
                                            </a>
                                            <a @click='borrarBanco_click(<?= $banco->id ?>)' class="btn btn-outline-secondary btn-sm">
                                                <span class="fa fa-trash"></span>
                                            </a>
                                        </td>
                                    </tr>


                                <?php $index++;
                                endforeach;
                                ?>

                            </tbody>

                        </table>

                        {{ $bancos->links('pagination::bootstrap-4') }}

                    </div>

                </div>
            </div>
            <hr />
            <div class="row">
                <div class="col-8">
                    <div class="form-group">
                        <button @click="mostrarModalFormBanco_click('ADD')" class="btn btn-secondary btn-sm">Nuevo banco</button>
                        <a href="{{ URL::to('bancos/index') }}" class="btn btn-secondary btn-sm">Recargar listado</a>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <!-- MODAL FORM BANCOS -->
    @include('includes.bancos.modal_form_bancos')
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
            modalFormBanco: {
                id: '',
                descripcion: ''
            },
            spinners: {
                guardarBanco: false,
                editarBanco: false
            },
            editarBanco: false
        },

        components: {
            // vuejsDatepicker
        },

        methods: {

            guardarBanco_click() {
                const path = 'bancos/ajaxGuardarBanco';
                const datos = this.modalFormBanco;
                this.enviarDatosForm(datos, path, 'POST', true);
            },

            editarBanco_click() {
                const path = 'bancos/ajaxEditarBanco/' + this.modalFormBanco.id;
                const datos = this.modalFormBanco;
                this.enviarDatosForm(datos, path, 'PUT', true);
            },

            borrarBanco_click(id) {
                const result = confirm('¿Está seguro?');
                if (result) {
                    const path = 'bancos/ajaxBorrarBanco/' + id;
                    const datos = this.modalFormBanco;
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

                this.modalFormBanco.id = '';
                this.modalFormBanco.descripcion = '';
                this.editarBanco = false;

                if (tipo === 'ADD') {
                    this.$bvModal.show('modalFormBanco');
                } else {
                    this.modalFormBanco.id = item.id;
                    this.modalFormBanco.descripcion = item.descripcion;
                    this.editarBanco = true;
                }

                this.$bvModal.show('modalFormBanco');
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
