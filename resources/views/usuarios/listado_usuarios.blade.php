@extends('layouts.default')
@section('content')
<div>
    <div class="card" id="vueapp" v-cloak>
        <div class="card-header">
            <b>Listado de usuarios</b>
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
                            <form method="post" action="{{ URL::to('usuarios/index') }}">
                                @csrf
                                <div class="input-group">
                                    <input type="text" autocomplete="off" name="txtFiltro" maxlength="50" class="form-control form-control-sm" placeholder="Identificación">
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
                                        <th scope="col">Identificacion</th>
                                        <th scope="col">Nombres</th>
                                        <th scope="col">Apellidos</th>
                                        <th scope="col">Télefono</th>
                                        <th scope="col">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $index = 0;
                                    $col = 4;
                                    foreach ($usuarios as $usuario) :
                                        $detalle = 'detalle' . $index;
                                    ?>

                                        <tr>
                                            <td><?= $usuario->identificacion ?></td>
                                            <td><?= $usuario->nombres ?></td>
                                            <td><?= $usuario->apellidos ?></td>
                                            <td><?= $usuario->telefono ?></td>
                                            <td>
                                                <button data-toggle="collapse" data-target="<?= '#' . $detalle ?>" class="btn btn-outline-secondary btn-sm">
                                                    <span class="fa fa-bars"></span>
                                                </button>

                                                @if(Gate::check('check-authorization', PERMISO_EDITAR_USUARIOS))
                                                <a href="{{ URL::to('usuarios/edit', $usuario->id) }}" class="btn btn-outline-secondary btn-sm">
                                                    <span class="fa fa-edit"></span>
                                                </a>
                                                @endif
                                            </td>
                                        </tr>

                                        <tr id="<?= $detalle ?>" class="collapse">
                                            <td colspan="5">
                                                @include('includes.fila_detalle_general', ['datos' => $filasDetalle[$index], 'col' => $col])
                                            </td>

                                        </tr>
                                    <?php $index++;
                                    endforeach;
                                    ?>

                                </tbody>

                            </table>

                            {{ $usuarios->links('pagination::bootstrap-4') }}

                        </div>

                    </div>
                </div>
                <hr />
                <div class="form-row">
                    <div class="col-8">
                        <div class="form-group">
                            <a href="{{ URL::to('usuarios/add') }}" class="btn btn-secondary btn-sm">Nuevo usuario</a>
                        </div>
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
            formCargado: false
        },
        components: {
            // vuejsDatepicker
        },

        methods: {


            

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